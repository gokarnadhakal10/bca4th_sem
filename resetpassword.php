<?php
$conn = new mysqli("localhost", "root", "", "voting_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = $_POST['email'];
$phone = $_POST['phone'];

// Check if user exists
$stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND phone=?");
$stmt->bind_param("ss", $email, $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Invalid email or phone number!";
    exit;
}

// Generate new password
$newPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 8);

// Hash new password
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);

// Update DB with NEW password
$update = $conn->prepare("UPDATE users SET password=? WHERE email=?");
$update->bind_param("ss", $hashed, $email);
$update->execute();

// Show the new password to the user
echo "<h3>Your new password is: <span style='color:red;'>$newPassword</span></h3>";
echo "<p>Please login and change your password immediately.</p>";
?>
