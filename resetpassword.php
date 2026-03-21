<?php
session_start();
require "config.php";

$step = 1;
$error = "";
$success = "";

// Step 1: Verify User
if (isset($_POST['verify_user'])) {
    $email = trim($_POST['email']);
    $mobile = trim($_POST['mobile']);
    
    // Check if user exists with matching email and mobile
    $stmt = $conn->prepare("SELECT id, name FROM users WHERE email=? AND mobile=?");
    $stmt->bind_param("ss", $email, $mobile);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['reset_email'] = $email;
        $step = 2; // Move to password reset step
    } else {
        $error = "Invalid email or mobile number. Please check your details and try again.";
    }
}

// Step 2: Reset Password
if (isset($_POST['reset_password'])) {
    if (!isset($_SESSION['reset_email'])) {
        header("Location: forgotpassword.php");
        exit;
    }
    
    $pass = $_POST['password'];
    $cpass = $_POST['cpassword'];
    $email = $_SESSION['reset_email'];
    
    if ($pass !== $cpass) {
        $error = "Passwords do not match!";
        $step = 2;
    } elseif (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters!";
        $step = 2;
    } else {
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE email=?");
        $stmt->bind_param("ss", $hashed, $email);
        
        if ($stmt->execute()) {
            $success = "Password updated successfully!";
            session_unset();
            session_destroy();
            $step = 3; // Success step
        } else {
            $error = "Error updating password: " . $conn->error;
            $step = 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Online Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .container { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 450px; text-align: center; }
        h2 { color: #333; margin-bottom: 10px; font-size: 28px; }
        p { color: #666; margin-bottom: 30px; font-size: 14px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        input { width: 100%; padding: 12px; border: 2px solid #e1e1e1; border-radius: 8px; font-size: 16px; transition: all 0.3s; }
        input:focus { border-color: #667eea; outline: none; }
        button { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: left; font-size: 14px; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .back-link { display: block; margin-top: 20px; color: #666; text-decoration: none; font-size: 14px; }
        .back-link:hover { color: #667eea; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($step == 1): ?>
            <div style="font-size: 48px; color: #e74c3c; margin-bottom: 20px;"><i class="fas fa-exclamation-circle"></i></div>
            <h2>Verification Failed</h2>
            <p><?php echo $error ? $error : "Invalid request."; ?></p>
            <a href="forgotpassword.php" style="display:inline-block; padding:12px 30px; background:#667eea; color:white; text-decoration:none; border-radius:8px; font-weight:600;">Try Again</a>
        
        <?php elseif ($step == 2): ?>
            <h2>Reset Password</h2>
            <p>Create a new strong password for your account.</p>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Enter new password" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="cpassword" placeholder="Confirm new password" required>
                </div>
                <button type="submit" name="reset_password">Change Password</button>
            </form>
            <a href="login.html" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
            
        <?php elseif ($step == 3): ?>
            <div style="font-size: 48px; color: #2ecc71; margin-bottom: 20px;"><i class="fas fa-check-circle"></i></div>
            <h2>Success!</h2>
            <p>Your password has been updated successfully.</p>
            <a href="login.html" style="display:inline-block; padding:12px 30px; background:#667eea; color:white; text-decoration:none; border-radius:8px; font-weight:600;">Login Now</a>
        <?php endif; ?>
    </div>
</body>
</html>
