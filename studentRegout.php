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
$photoName = $_FILES['photo']['name'];
$tmpName   = $_FILES['photo']['tmp_name'];

$uploadDir = "uploads/";
$photoPath = $uploadDir . time() . "_" . $photoName;

if (!move_uploaded_file($tmpName, $photoPath))
    
    // Image validation
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0) {
    die("Please upload a photo");
}

$photoName = $_FILES['photo']['name'];
$tmpName   = $_FILES['photo']['tmp_name'];
$fileSize  = $_FILES['photo']['size'];

$ext = strtolower(pathinfo($photoName, PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png'];

// Check file type
if (!in_array($ext, $allowed)) {
    die("Only JPG, JPEG, and PNG images are allowed");
}

// Check file size (max 2MB)
if ($fileSize > 2 * 1024 * 1024) {
    die("Image size must be less than 2MB");
}

    
    {
    die("Error uploading photo");
}

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
$stmt = $conn->prepare("INSERT INTO users (name, email, mobile, role, faculty, class,photo, password) VALUES (?, ?, ?, ?, ?, ?,?, ?)");
$stmt->bind_param("ssssssss", $name, $email, $mobile, $role, $faculty, $class, $photoPath, $hashedPassword);

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
