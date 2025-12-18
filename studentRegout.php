<?php
session_start();

// Collect form data
$name = $_POST['name'];
$email = $_POST['email'];
$mobile = $_POST['mobile'];
$role = $_POST['role'];
$faculty = $_POST['faculty']; 
$class = $_POST['class'];
$password = $_POST['password'];
$cpassword = $_POST['cpassword'];

// Password validation
if($password !== $cpassword){
    die("Error: Passwords do not match!");
}

if(strlen($password) < 8 || !preg_match("/[A-Z]/",$password) || 
   !preg_match("/[a-z]/",$password) || !preg_match("/[0-9]/",$password) || 
   !preg_match("/[@$#]/",$password)){
    die("Error: Password does not meet security requirements!");
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// Check if email or mobile already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR mobile=?");
$stmt->bind_param("ss", $email, $mobile);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0){
    die("Error: Email or mobile already exists!");
}
$stmt->close();

// Insert user into database
$stmt = $conn->prepare("INSERT INTO users (name, email, mobile, role, faculty, class, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $name, $email, $mobile, $role, $faculty, $class, $hashedPassword);

if($stmt->execute()){
    // Registration successful
    header("Location: login.html");
    exit;
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
