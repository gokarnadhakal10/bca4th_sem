<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'voter') {
    header("Location: login.html"); // redirect if not logged in
    exit;
}

echo "<h1>Welcome, " . $_SESSION['email'] . "!</h1>";
echo "<p>This is your voter dashboard.</p>";



