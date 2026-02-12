<?php
session_start();

// Collect form data
$name = $_POST['name'];
$email = $_POST['email'];
$mobile = $_POST['mobile'];
$role = 'Voter';
$faculty = $_POST['faculty']; 
$class = $_POST['class'];
$password = $_POST['password'];
$cpassword = $_POST['cpassword'];

// Check file upload
if(!isset($_FILES['photo']) || $_FILES['photo']['error'] != 0){
    die("Error: Please upload a photo");
}

$photoName = $_FILES['photo']['name'];
$tmpName   = $_FILES['photo']['tmp_name'];
$fileSize  = $_FILES['photo']['size'];

$ext = strtolower(pathinfo($photoName, PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png'];

// Check file type
if(!in_array($ext, $allowed)){
    die("Error: Only JPG, JPEG, PNG allowed");
}

// Check file size (max 2MB)
if($fileSize > 2*1024*1024){
    die("Error: Image size must be less than 2MB");
}

// Prepare upload folder
$uploadDir = "uploads/";
if(!is_dir($uploadDir)){
    mkdir($uploadDir, 0777, true);
}

// Move uploaded file
$photoPath = $uploadDir . time() . "_" . $photoName;
if(!move_uploaded_file($tmpName, $photoPath)){
    die("Error uploading photo");
}

$image_path = "";
$image_path = "";
        if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $file_type = $_FILES['item_image']['type'];
            $file_size = $FILES['item_image']['size'];
            
            if (in_array($file_type, $allowed_types)) {
                if ($file_size < 5000000) { // 5MB limit
                    $image_name = time() . '' . basename($_FILES['item_image']['name']);
                    $target_dir = "../Uploads/items/";
                    $target_file = $target_dir . $image_name;
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    
                    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_file)) {
                        $image_path = "items/" . $image_name;
                    } else {
                        $error_message = "Sorry, there was an error uploading your image.";
                    }
                } else {
                    $error_message = "Image size is too large. Max 5MB allowed.";
                }
            } else {
                $error_message = "Only JPG, PNG & GIF files are allowed.";
            }
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
$conn = new mysqli("localhost","root","","voting_system");
if($conn->connect_error){
    die("Connection failed: ".$conn->connect_error);
}

// Check if email or mobile exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR mobile=?");
$stmt->bind_param("ss",$email,$mobile);
$stmt->execute();
$stmt->store_result();
if($stmt->num_rows > 0){
    die("Error: Email or mobile already exists!");
}
$stmt->close();

// Insert user
$stmt = $conn->prepare("INSERT INTO users (name,email,mobile,role,faculty,class,photo,password) VALUES (?,?,?,?,?,?,?,?)");
$stmt->bind_param("ssssssss",$name,$email,$mobile,$role,$faculty,$class,$photoPath,$hashedPassword);

if($stmt->execute()){
    header("Location: login.html");
    exit;
} else {
    die("Database Error: ".$stmt->error);
}

$stmt->close();
$conn->close();
?>