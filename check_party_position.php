<?php
require "config.php";
require "auth.php";
header('Content-Type: application/json');

$party = $_POST['party'] ?? '';
$position = $_POST['position'] ?? '';

if ($party && $position) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM candidates WHERE party_name=? AND position=?");
    $stmt->bind_param("ss", $party, $position);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode(['exists' => $result['count'] > 0 ? 1 : 0]);
} else {
    echo json_encode(['exists' => 0]);
}