<?php
// Database connection
$conn = new mysqli("localhost","root","","voting_system");
if($conn->connect_error){ die("Connection failed: ".$conn->connect_error); }

// Get POST data
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$mobile = $_POST['mobile'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = 'voter'; // default role

$errors = [];

// Validation
if(empty($name)||empty($email)||empty($student_id)||empty($mobile)||empty($password)||empty($confirm_password)){
    $errors[]="All fields are required.";
}
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){
    $errors[]="Invalid email format.";
}
if(!preg_match('/^[0-9]{10}$/',$mobile)){
    $errors[]="Mobile number must be 10 digits.";
}
if($password !== $confirm_password){
    $errors[]="Password and Confirm Password do not match.";
}

// Check if email or student_id exists
$stmt=$conn->prepare("SELECT * FROM user WHERE email=? OR student_id=?");
$stmt->bind_param("ss",$email,$student_id);
$stmt->execute();
$result=$stmt->get_result();
if($result->num_rows>0){
    $errors[]="Email or Student ID already registered.";
}

// Show errors
if(!empty($errors)){
    foreach($errors as $err){ echo "<p style='color:red;'>$err</p>"; }
    echo "<p><a href='studentRegistration.html'>Go back</a></p>";
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO user (name,email,student_id,mobile,password,role) VALUES (?,?,?,?,?,?)");
$stmt->bind_param("ssssss",$name,$email,$student_id,$mobile,$hashed_password,$role);

if($stmt->execute()){
    echo "<p style='color:green;'>Registration successful!</p>";
    echo "<p><a href='login.html'>Go to Login</a></p>";
}else{
    echo "<p style='color:red;'>Error: ".$conn->error."</p>";
}

$conn->close();
?>
