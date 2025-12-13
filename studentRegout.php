<?php
session_start();

$name = $_POST['name'];
$email = $_POST['email'];
$mobile = $_POST['mobile'];
$role = $_POST['role'];
$password = $_POST['password'];
$cpassword = $_POST['cpassword'];

if($password !== $cpassword){
    die("Error: Passwords do not match!");
}

if(strlen($password)<8 || !preg_match("/[A-Z]/",$password) || 
   !preg_match("/[a-z]/",$password) || !preg_match("/[0-9]/",$password) || 
   !preg_match("/[@$#]/",$password)){
    die("Error: Password does not meet security requirements!");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$conn = new mysqli("localhost","root","","voting_system");

if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR mobile=?");
$stmt->bind_param("ss", $email, $mobile);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0){
    die("Error: Email or mobile already exists!");
}

$stmt->close();

$stmt = $conn->prepare("INSERT INTO users (name,email,mobile,role,password) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss", $name, $email, $mobile, $role, $hashedPassword);

if($stmt->execute()){
    header("Location: login.html");
    exit;
} else {
    echo "Error: ".$stmt->error;
}

$stmt->close();
$conn->close();
?>
