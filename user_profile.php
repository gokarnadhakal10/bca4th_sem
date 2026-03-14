<?php
session_start();
require "config.php";

if(!isset($_SESSION['voter_id'])){ header("Location: login.php"); exit; }
$voter_id = $_SESSION['voter_id'];

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update Contact Info
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET email=?, mobile=? WHERE id=?");
            $stmt->bind_param("ssi", $email, $mobile, $voter_id);
            if ($stmt->execute()) {
                $message = "Profile updated successfully!";
            } else {
                $error = "Error updating profile: " . $conn->error;
            }
        }
    }
    
    // Change Password
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
        $stmt->bind_param("i", $voter_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if (password_verify($current, $res['password'])) {
            if ($new === $confirm) {
                if (strlen($new) >= 8) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
                    $upd->bind_param("si", $hashed, $voter_id);
                    if ($upd->execute()) {
                        $message = "Password changed successfully!";
                    } else {
                        $error = "Error updating password.";
                    }
                } else {
                    $error = "New password must be at least 8 characters.";
                }
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}

// Fetch latest user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings | User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --card-bg: #ffffff; --text-dark: #333; --light-bg: #f4f6f9; --border: #e0e0e0; --success: #38a169; --danger: #e53e3e; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: var(--text-dark); }
        
        header { width: 100%; background: rgba(255, 255, 255, 0.98); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
        .logo-text { font-size: 20px; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        nav { display: flex; gap: 15px; align-items: center; }
        nav a { padding: 10px 20px; color: #333; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; transition: all 0.3s ease; }
        nav a:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; transform: translateY(-2px); }
        nav a.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }

        .main-container { max-width: 1400px; margin: 100px auto 20px; padding: 0 20px; display: grid; grid-template-columns: 300px 1fr; gap: 20px; }
        .sidebar { background: var(--card-bg); border-radius: 8px; padding: 20px; height: fit-content; }
        .profile-section { text-align: center; padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .profile-avatar { width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 32px; font-weight: 600; overflow: hidden; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-name { font-size: 18px; font-weight: 600; margin-bottom: 5px; color: var(--text-dark); }
        .profile-details { text-align: left; margin-top: 15px; }
        .detail-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; color: var(--text-light); font-size: 14px; }
        .detail-item i { width: 20px; color: var(--secondary); }
        .status-badge { display: inline-flex; align-items: center; gap: 5px; background: #e8f5e9; color: var(--success); padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; margin-top: 10px; }
        
        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 5px; }
        .nav-link { display: flex; align-items: center; gap: 10px; padding: 12px 15px; color: var(--text-dark); text-decoration: none; border-radius: 6px; transition: all 0.3s; }
        .nav-link:hover { background: #f0f2f5; color: var(--primary); }
        .nav-link.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .nav-link i { width: 20px; }

        .main-content { display: grid; gap: 20px; }
        .card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: 0.3s; }
        .form-control:focus { border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        
        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; color: white; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        @media (max-width: 1024px) { .main-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo"><div class="logo-icon"><i class="fas fa-vote-yea"></i></div><span class="logo-text">Voting System</span></div>
            <nav>
                 <a href="userDashboard.php">Dashboard</a>
                 <a href="user_results.php">Result</a>
                 <a href="user_notices.php">Notice Board</a>
                 <a href="user_help.php">Help</a>
                 <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>
    <div class="main-container">
        <aside class="sidebar">
            <div class="profile-section">
                <div class="profile-avatar">
                    <?php if(!empty($voter['photo']) && file_exists($voter['photo'])): ?>
                        <img src="<?= htmlspecialchars($voter['photo']) ?>" alt="Profile">
                    <?php else: ?>
                        <?php echo strtoupper(substr($voter['name'] ?? 'U', 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($voter['name'] ?? 'User') ?></div>
                <div class="profile-details">
                    <div class="detail-item"><i class="fas fa-graduation-cap"></i> <span><?= htmlspecialchars($voter['class'] ?? 'N/A') ?></span></div>
                    <div class="detail-item"><i class="fas fa-envelope"></i> <span><?= htmlspecialchars($voter['email'] ?? '') ?></span></div>
                    <div class="detail-item"><i class="fas fa-phone"></i> <span><?= htmlspecialchars($voter['mobile'] ?? 'N/A') ?></span></div>
                </div>
                <div class="status-badge"><i class="fas fa-circle"></i> <?= htmlspecialchars($voter['status'] ?? 'Active') ?></div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="userDashboard.php" class="nav-link"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li class="nav-item"><a href="user_results.php" class="nav-link"><i class="fas fa-vote-yea"></i> <span>Result</span></a></li>
                <li class="nav-item"><a href="user_notices.php" class="nav-link"><i class="fas fa-bullhorn"></i> <span>Notice Board</span></a></li>
                <li class="nav-item"><a href="user_profile.php" class="nav-link active"><i class="fas fa-user-cog"></i> <span>Profile Settings</span></a></li>
                <li class="nav-item"><a href="user_help.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Help</span></a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="card">
                <h2 style="margin-bottom: 20px; color: var(--text-dark);">Profile Settings</h2>
                <?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
                <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <form method="POST" style="margin-bottom: 40px;">
                    <h3 style="margin-bottom: 15px; font-size: 18px; color: var(--primary);">Contact Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>Email Address</label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($voter['email']) ?>" required></div>
                        <div class="form-group"><label>Mobile Number</label><input type="text" name="mobile" class="form-control" value="<?= htmlspecialchars($voter['mobile']) ?>" required></div>
                    </div>
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">
                <form method="POST">
                    <h3 style="margin-bottom: 15px; font-size: 18px; color: var(--primary);">Change Password</h3>
                    <div class="form-group"><label>Current Password</label><input type="password" name="current_password" class="form-control" required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>New Password</label><input type="password" name="new_password" class="form-control" required minlength="8"></div>
                        <div class="form-group"><label>Confirm New Password</label><input type="password" name="confirm_password" class="form-control" required minlength="8"></div>
                    </div>
                    <button type="submit" name="change_password" class="btn">Change Password</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>