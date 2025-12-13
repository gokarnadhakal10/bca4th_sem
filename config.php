<?php
session_start();

$mysqli = new mysqli("localhost", "root", "", "voting_system");

if ($mysqli->connect_error) {
    die("DB connection failed: " . $mysqli->connect_error);
}

function admin_required() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
}
?>
