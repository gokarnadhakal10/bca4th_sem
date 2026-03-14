<?php
require "config.php";
require "auth.php";
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $start_time = date('Y-m-d H:i:s', strtotime($_POST['start']));
    $end_time = date('Y-m-d H:i:s', strtotime($_POST['end']));
    
    // --- Server-side Validation ---
    
    // 1. Validate End Time > Start Time
    if (strtotime($end_time) <= strtotime($start_time)) {
        $_SESSION['error'] = "End time must be after the start time.";
        header("Location: AdminDashboard.php");
        exit();
    }

    // Fetch current status to determine if we should enforce "future start date"
    $current_session_query = $conn->query("SELECT status FROM nomination_session WHERE id=1");
    $current_status = ($current_session_query->num_rows > 0) ? $current_session_query->fetch_assoc()['status'] : 'Pending';

    // 2. Validate Start Time not in past (only for new sessions, allow 1 min buffer)
    if (!in_array($current_status, ['Active', 'Paused']) && strtotime($start_time) < (time() - 60)) {
        $_SESSION['error'] = "Start time cannot be in the past.";
        header("Location: AdminDashboard.php");
        exit();
    }

    // 3. Prevent Session Overlap (Nomination cannot start if Voting is Active/Paused)
    $vote_status_query = $conn->query("SELECT status FROM voting_session WHERE id=1");
    $vote_status = ($vote_status_query->num_rows > 0) ? $vote_status_query->fetch_assoc()['status'] : 'Pending';
    if ($action === 'start' && in_array($vote_status, ['Active', 'Paused'])) {
        $_SESSION['error'] = "Cannot start nomination session while voting session is active.";
        header("Location: AdminDashboard.php");
        exit();
    }

    $status = 'Pending';
    switch($action) {
        case 'start':
            $status = 'Active';
            break;
        case 'pause':
            $status = 'Paused';
            break;
        case 'resume':
            $status = 'Active';
            break;
        case 'end':
            $status = 'Ended';
            break;
        default:
            $status = 'Pending';
    }
    
    // Check if session exists
    $check = $conn->query("SELECT id FROM nomination_session WHERE id=1");
    
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE nomination_session SET start_time=?, end_time=?, status=? WHERE id=1");
        $stmt->bind_param("sss", $start_time, $end_time, $status);
    } else {
        $stmt = $conn->prepare("INSERT INTO nomination_session (id, start_time, end_time, status) VALUES (1, ?, ?, ?)");
        $stmt->bind_param("sss", $start_time, $end_time, $status);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Nomination session updated successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: AdminDashboard.php");
    exit();
}
?>