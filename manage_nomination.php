<?php
require "config.php";
require "auth.php";
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $start_time = date('Y-m-d H:i:s', strtotime($_POST['start']));
    $end_time = date('Y-m-d H:i:s', strtotime($_POST['end']));
    
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
    
    $stmt = $conn->prepare("UPDATE nomination_session SET start_time=?, end_time=?, status=? WHERE id=1");
    $stmt->bind_param("sss", $start_time, $end_time, $status);
    
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