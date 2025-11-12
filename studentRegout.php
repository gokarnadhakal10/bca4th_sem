<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "voting_system";

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data safely
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$student_id = $_POST['student_id'] ?? '';
$mobile = $_POST['number'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Initialize errors array
$errors = [];

// --- Validation ---

// Check for empty fields
if (empty($name) || empty($email) || empty($student_id) || empty($mobile) || empty($password) || empty($confirm_password)) {
    $errors[] = "All fields are required.";
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
}

// Validate mobile number (10 digits)
if (!preg_match('/^[0-9]{10}$/', $mobile)) {
    $errors[] = "Mobile number must be exactly 10 digits.";
}

// Validate password length
if (strlen($password) < 6 || strlen($password) > 100) {
    $errors[] = "Password must be between 6 and 100 characters.";
}

// Confirm password
if ($password !== $confirm_password) {
    $errors[] = "Password and Confirm Password do not match.";
}

// Check if email or student_id already exists
$stmt = $conn->prepare("SELECT * FROM user WHERE email=? OR student_id=?");
$stmt->bind_param("ss", $email, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $errors[] = "Email or Student ID already registered.";
}

// If there are errors, display them and stop
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<p><a href='studentRegistration.html'>Go back to registration</a></p>";
    exit;
}

// Plain password (matches login.php)
$plain_password = $password;

//  Automatically assign role as 'voter'
$role = 'voter';

// Insert data into database
$stmt = $conn->prepare("INSERT INTO user (name, email, student_id, mobile, password, role) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $name, $email, $student_id, $mobile, $plain_password, $role);

if ($stmt->execute()) {
    echo "<p style='color:green;'>Registration successful!</p>";
    echo "<p><a href='login.html'>Go to Login</a></p>";
} else {
    echo "<p style='color:red;'>Error: " . $conn->error . "</p>";
}

$conn->close();
?>


