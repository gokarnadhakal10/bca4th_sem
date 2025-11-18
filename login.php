<?php
session_start();
$conn = new mysqli("localhost","root","","voting_system");
if($conn->connect_error){ die("Connection failed: ".$conn->connect_error); }

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = strtolower($_POST['role'] ?? ''); // lowercase role

if(empty($email)||empty($password)||empty($role)){
    echo "<p style='color:red;'>Please fill in all fields.</p>";
    echo "<p><a href='login.html'>Go back</a></p>"; 
    exit;
}

// Fetch user by email and role
$stmt = $conn->prepare("SELECT * FROM user WHERE email=? AND role=?");
$stmt->bind_param("ss",$email,$role);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows===1){
    $user = $result->fetch_assoc();

    if($role === 'admin'){
        // Admin password is stored in plain text
        if($password === $user['password']){
            $_SESSION['email']=$user['email'];
            $_SESSION['role']=$user['role'];
            $_SESSION['name']=$user['name'];
            header("Location: admin_dashboard.php");
            exit;
        }else{
            echo "<p style='color:red;'>Incorrect password.</p><p><a href='login.html'>Try again</a></p>";
        }
    } else {
        // Voter password is hashed
        if(password_verify($password,$user['password'])){
            $_SESSION['email']=$user['email'];
            $_SESSION['role']=$user['role'];
            $_SESSION['name']=$user['name'];
            header("Location: voter_dashboard.php");
            exit;
        }else{
            echo "<p style='color:red;'>Incorrect password.</p><p><a href='login.html'>Try again</a></p>";
        }
    }

}else{
    echo "<p style='color:red;'>Invalid email or role.</p><p><a href='login.html'>Try again</a></p>";
}

$conn->close();
?>

