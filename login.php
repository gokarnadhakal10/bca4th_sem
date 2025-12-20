






<?php
session_start();
require "config.php"; // $conn = mysqli connection

if (!isset($_POST['login'])) {
    header("Location: login.html");
    exit;
}

$email = trim($_POST['email']);
$password = trim($_POST['password']);
$role = trim($_POST['role']);

if ($email == "" || $password == "" || $role == "") {
    header("Location: login.html?error=Please fill all fields");
    exit;
}

// Fetch user
$sql = "SELECT id, password, role, status FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: login.html?error=Email not found");
    exit;
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    header("Location: login.html?error=Incorrect password");
    exit;
}

// **Check if user is blocked**
if ($user['status'] == 'Blocked') {
    header("Location: login.html?error=Your account is blocked. Contact admin.");
    exit;
}

// Check role
if ($user['role'] !== $role) {
    header("Location: login.html?error=Wrong role selected");
    exit;
}

// Role-based login
if ($role === "Admin") {
    $_SESSION['admin_id'] = $user['id'];
    header("Location: AdminDashboard.php");
    exit;
}

if ($role === "Voter") {
    $_SESSION['voter_id'] = $user['id'];
    header("Location: userDashboard.php");
    exit;
}

// fallback
header("Location: login.html?error=Login failed");
exit;
?>
