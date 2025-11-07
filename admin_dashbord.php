<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.html"); // redirect if not admin
    exit;
}

echo "<h1>Welcome, Admin!</h1>";
echo "<p>This is your admin dashboard.</p>";

// Here you can create forms to add candidates or manage elections
