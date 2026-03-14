<?php
require "config.php";
require "auth.php";
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $start_time = date('Y-m-d H:i:s', strtotime($_POST['start']));
    $end_time = date('Y-m-d H:i:s', strtotime($_POST['end']));
    $current_time = date('Y-m-d H:i:s');
    
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
    $check = $conn->query("SELECT id FROM voting_session WHERE id=1");
    
    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE voting_session SET start_time=?, end_time=?, status=? WHERE id=1");
        $stmt->bind_param("sss", $start_time, $end_time, $status);
    } else {
        $stmt = $conn->prepare("INSERT INTO voting_session (id, start_time, end_time, status) VALUES (1, ?, ?, ?)");
        $stmt->bind_param("sss", $start_time, $end_time, $status);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Session updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating session: " . $conn->error;
    }
    
    $stmt->close();
    header("Location: AdminDashboard.php");
    exit();
}
?>