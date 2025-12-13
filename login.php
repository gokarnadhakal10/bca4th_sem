<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST values
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

// Check empty fields
if (empty($email) || empty($password) || empty($role)) {
    header("Location: login.htm?error=fill_fields");
    exit;
}

// Prepare SQL to check user
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // Redirect based on role
        if ($role === "Admin") {
            header("Location: AdminDashboard.php");
        } else {
            header("Location: userDashboard.php");
        }
        exit;
    } else {
        // Wrong password
        header("Location: login.htm?error=incorrect_password");
        exit;
    }

} else {
    // Email or role not found
    header("Location: login.htm?error=invalid_email");
    exit;
}

$conn->close();
?>
