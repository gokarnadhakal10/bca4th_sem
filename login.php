<?php
session_start();
require "config.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
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
                $error = "Invalid password.";
            }
        } else {
            $error = "Account not found.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: #333; }
        
        /* Header Styles */
        header { width: 100%; background: rgba(255, 255, 255, 0.98); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 50px; height: 50px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; }
        .logo-text { font-size: 24px; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        nav { display: flex; gap: 15px; align-items: center; }
        nav a { padding: 10px 20px; color: #333; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; transition: all 0.3s ease; }
        nav a:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; transform: translateY(-2px); }
        nav a.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }

        /* Login Form Styles */
        .login-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 100px 20px 40px; }
        .login-card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); width: 100%; max-width: 450px; }
        .login-header { text-align: center; margin-bottom: 30px; }
        .login-header h2 { color: #333; font-size: 28px; margin-bottom: 10px; }
        .login-header p { color: #666; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; }
        .form-control { width: 100%; padding: 12px; border: 2px solid #e1e1e1; border-radius: 8px; font-size: 16px; transition: all 0.3s; }
        .form-control:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn-login { width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
        .error-msg { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-size: 14px; }
        .form-footer { text-align: center; margin-top: 20px; color: #666; }
        .form-footer a { color: #667eea; text-decoration: none; font-weight: 600; }
        .form-footer a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-vote-yea"></i></div>
                <span class="logo-text">Online Voting System</span>
            </div>
            <nav>
                 <a href="firstpage.php">Home</a>
                 <a href="login.php" class="active">Login</a>
                 <a href="studentRegistration.html">Register</a>
                 <a href="about.html">About Us</a>
                 <a href="noticeboard.php">Notice Board</a>
            </nav>
        </div>
    </header>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Please login to your account</p>
            </div>
            
            <?php if($error): ?>
                <div class="error-msg">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="studentRegistration.html">Register here</a></p>
                <p style="margin-top: 10px;"><a href="forgotpassword.php">Forgot Password?</a></p>
            </div>
        </div>
    </div>
</body>
</html>