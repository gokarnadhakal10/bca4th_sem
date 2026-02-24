<?php
session_start();
require "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    
    if (empty($email) || empty($password)) {
        header("Location: login.html?error=empty_fields");
        exit();
    } else {
        // Check user in database
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login Success
                if (strcasecmp($user['role'], 'Admin') == 0) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    header("Location: AdminDashboard.php");
                } else {
                    $_SESSION['voter_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    header("Location: userDashboard.php");
                }
                exit();
            } else {
                header("Location: login.html?error=invalid_credentials");
                exit();
            }
        } else {
            header("Location: login.html?error=account_not_found");
            exit();
        }
        $stmt->close();
    }
}
?>