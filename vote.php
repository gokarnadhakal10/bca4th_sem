<?php
session_start();
require "config.php";

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voter_id = $_POST['voter_id'];
    $candidate_id = $_POST['candidate_id'];

    // Get candidate's position
    $stmt = $conn->prepare("SELECT position FROM candidates WHERE id=?");
    $stmt->bind_param("i", $candidate_id);
    $stmt->execute();
    $candidate_result = $stmt->get_result();

    if ($candidate_result->num_rows !== 1) {
        die("Candidate not found");
    }

    $candidate = $candidate_result->fetch_assoc();
    $position = $candidate['position'];

    // Check if voter has already voted for this position
    $stmt = $conn->prepare("SELECT * FROM votes WHERE voter_id=? AND position=?");
    $stmt->bind_param("is", $voter_id, $position);
    $stmt->execute();
    $vote_result = $stmt->get_result();

    if ($vote_result->num_rows > 0) {
        // Already voted for this position
        die("You have already voted for the position of $position. You cannot vote again.");
    }

    // Insert vote
    $stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, position) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $voter_id, $candidate_id, $position);
    if ($stmt->execute()) {
        header("Location: userDashboard.php?success=Vote cast successfully");
        exit;
    } else {
        die("Error casting vote: " . $conn->error);
    }
}
?>
