<?php
require "config.php";

// Set header to return JSON
header('Content-Type: application/json');

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$current_time = date('Y-m-d H:i:s');
$response = [];

// 1. Check Voting Session
$v_query = $conn->query("SELECT * FROM voting_session WHERE id=1");
$vote_session = $v_query->fetch_assoc();

if ($vote_session && in_array($vote_session['status'], ['Active', 'Paused'])) {
    if ($vote_session['end_time'] < $current_time) {
        $conn->query("UPDATE voting_session SET status = 'Ended' WHERE id = 1");
        $vote_session['status'] = 'Ended';
    }
}
$response['voting'] = $vote_session;

// 2. Check Nomination Session
$n_query = $conn->query("SELECT * FROM nomination_session WHERE id=1");
$nom_session = $n_query->fetch_assoc();

if ($nom_session && in_array($nom_session['status'], ['Active', 'Paused'])) {
    if ($nom_session['end_time'] < $current_time) {
        $conn->query("UPDATE nomination_session SET status = 'Ended' WHERE id = 1");
        $nom_session['status'] = 'Ended';
    }
}
$response['nomination'] = $nom_session;
$response['server_time'] = $current_time;

echo json_encode($response);
?>