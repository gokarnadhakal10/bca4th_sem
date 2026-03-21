<?php
session_start();
require "config.php";

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit();
}

$voter_id = $_SESSION['voter_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if there is a pending request
    $stmt = $conn->prepare("SELECT id, photo, party_image FROM candidate_requests WHERE voter_id=? AND status='pending'");
    $stmt->bind_param("i", $voter_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $request = $result->fetch_assoc();
        
        // Delete uploaded files if they exist
        if (!empty($request['photo']) && file_exists("uploads/" . $request['photo'])) {
            unlink("uploads/" . $request['photo']);
        }
        if (!empty($request['party_image']) && file_exists("uploads/" . $request['party_image'])) {
            unlink("uploads/" . $request['party_image']);
        }

        // Delete the request
        $del_stmt = $conn->prepare("DELETE FROM candidate_requests WHERE id=?");
        $del_stmt->bind_param("i", $request['id']);
        
        if ($del_stmt->execute()) {
            $_SESSION['message'] = "Nomination request withdrawn successfully.";
        } else {
            $_SESSION['error'] = "Error withdrawing request: " . $conn->error;
        }
        $del_stmt->close();
    } else {
        $_SESSION['error'] = "No pending nomination request found to withdraw.";
    }

    $stmt->close();
}

header("Location: userDashboard.php");
exit();
?>