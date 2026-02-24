<?php
session_start();
require "config.php";

if(!isset($_SESSION['voter_id'])){ header("Location: login.php"); exit; }
$voter_id = $_SESSION['voter_id'];

// Fetch voter info
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// Fetch notices
$current_date = date('Y-m-d H:i:s');
$notices = $conn->query("SELECT * FROM notices WHERE is_active=1 AND (expires_at IS NULL OR expires_at > '$current_date') ORDER BY published_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board | User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #667eea; --secondary: #764ba2; --card-bg: #ffffff; --text-dark: #333; --light-bg: #f4f6f9; --border: #e0e0e0; }
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
        .notice-card { background: white; border-radius: 8px; padding: 25px; border-left: 5px solid var(--primary); box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .notice-meta { font-size: 12px; color: #888; margin-bottom: 10px; display: flex; justify-content: space-between; }
        .badge { padding: 3px 10px; border-radius: 12px; font-size: 11px; background: #eee; }
        
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
                 <a href="user_notices.php" class="active">Notice Board</a>
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
                <div class="status-badge">
                    <i class="fas fa-circle"></i> <?= htmlspecialchars($voter['status'] ?? 'Active') ?>
                </div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="userDashboard.php" class="nav-link"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li class="nav-item"><a href="user_results.php" class="nav-link"><i class="fas fa-vote-yea"></i> <span>Result</span></a></li>
                <li class="nav-item"><a href="user_notices.php" class="nav-link active"><i class="fas fa-bullhorn"></i> <span>Notice Board</span></a></li>
                <li class="nav-item"><a href="user_help.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Help</span></a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div style="background: white; padding: 20px; border-radius: 8px;"><h2>Campus Notices</h2></div>
            <?php if($notices->num_rows > 0): while($row = $notices->fetch_assoc()): ?>
            <div class="notice-card">
                <div class="notice-meta">
                    <span><i class="far fa-calendar"></i> <?= date('M d, Y', strtotime($row['published_at'])) ?></span>
                    <span class="badge"><?= htmlspecialchars($row['category']) ?></span>
                </div>
                <h3 style="margin-bottom: 10px; color: var(--primary);"><?= htmlspecialchars($row['title']) ?></h3>
                <p style="color: #555; line-height: 1.6;"><?= nl2br(htmlspecialchars($row['content'])) ?></p>
            </div>
            <?php endwhile; else: ?>
            <div class="notice-card" style="text-align:center; color:#888;">No active notices found.</div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>