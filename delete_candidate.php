<?php
session_start();
require 'config.php';

 require "auth.php";
admin_required();

// Check voting session status to prevent deletion during an active election
$session_check = $conn->query("SELECT status FROM voting_session WHERE id = 1");
if ($session_check && $session_check->num_rows > 0) {
    $session = $session_check->fetch_assoc();
    if (isset($session['status']) && in_array($session['status'], ['Active', 'Paused'])) {
        $_SESSION['error'] = "Candidates cannot be deleted while a voting session is active or paused for security reasons.";
        header('Location: AdminDashboard.php');
        exit;
    }
}

$id = intval($_GET['id'] ?? 0);
if ($id){
$stmt = $conn->prepare("DELETE FROM candidates WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();
$stmt->close();

// Re-sequence IDs to ensure no gaps (Serial Order)
$result = $conn->query("SELECT id FROM candidates ORDER BY id ASC");
$new_id = 1;
while ($row = $result->fetch_assoc()) {
    $current_id = $row['id'];
    if ($current_id != $new_id) {
        // Update votes first to maintain relationship
        $conn->query("UPDATE votes SET candidate_id = $new_id WHERE candidate_id = $current_id");
        // Update candidate ID
        $conn->query("UPDATE candidates SET id = $new_id WHERE id = $current_id");
    }
    $new_id++;
}
$conn->query("ALTER TABLE candidates AUTO_INCREMENT = 1");
}
header('Location: AdminDashboard.php');
exit;