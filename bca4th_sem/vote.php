<?php
session_start();
require "config.php";

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voter_id = $_SESSION['voter_id'];
    $candidate_id = intval($_POST['candidate_id']);
    $position = trim($_POST['position']);
    
    // Check if voting session is active
    $session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();
    $current_time = date('Y-m-d H:i:s');
    
    if ($session['status'] !== 'Active') {
        $_SESSION['error'] = "Voting session is not active!";
        header("Location: userDashboard.php");
        exit();
    }
    
    // Check if voter is active
    $voter_status = $conn->query("SELECT status FROM users WHERE id=$voter_id")->fetch_assoc()['status'];
    if ($voter_status !== 'Active') {
        $_SESSION['error'] = "Your account is not active!";
        header("Location: userDashboard.php");
        exit();
    }
    
    // Check if already voted for this position
    $check = $conn->prepare("SELECT id FROM votes WHERE voter_id=? AND position=?");
    $check->bind_param("is", $voter_id, $position);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = "You have already voted for this position!";
        header("Location: userDashboard.php");
        exit();
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert vote
        $stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, position) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $voter_id, $candidate_id, $position);
        $stmt->execute();
        
        // Update candidate vote count
        $update = $conn->prepare("UPDATE candidates SET votes = votes + 1 WHERE id = ?");
        $update->bind_param("i", $candidate_id);
        $update->execute();
        
        $conn->commit();
        $_SESSION['success'] = "Vote cast successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error casting vote: " . $e->getMessage();
    }
    
    header("Location: userDashboard.php");
    exit();
}
?>