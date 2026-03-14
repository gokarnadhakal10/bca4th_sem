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
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #f72585;
            --success: #38a169;
            --warning: #f39c12;
            --card-bg: #ffffff;
            --text-dark: #333;
            --text-light: #666;
            --light-bg: #f4f6f9;
            --border: #e0e0e0;
            --shadow-sm: 0 2px 10px rgba(0,0,0,0.05);
            --shadow-md: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; color: var(--text-dark); }
        
        /* Header */
        header { width: 100%; background: rgba(255, 255, 255, 0.98); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); position: fixed; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; }
        .logo-text { font-size: 20px; font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        nav { display: flex; gap: 15px; align-items: center; }
        nav a { padding: 10px 20px; color: #333; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: 600; transition: all 0.3s ease; }
        nav a:hover { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; transform: translateY(-2px); }
        nav a.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }

        /* Layout */
        .main-container { max-width: 1400px; margin: 100px auto 20px; padding: 0 20px; display: grid; grid-template-columns: 300px 1fr; gap: 20px; }
        
        /* Sidebar */
        .sidebar { background: var(--card-bg); border-radius: 12px; padding: 25px; height: fit-content; box-shadow: var(--shadow-sm); }
        .profile-section { text-align: center; padding-bottom: 20px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .profile-avatar { width: 90px; height: 90px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; margin: 0 auto 15px; display: flex; align-items: center; justify-content: center; color: white; font-size: 36px; font-weight: 600; overflow: hidden; border: 3px solid #f0f0f0; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-name { font-size: 18px; font-weight: 700; margin-bottom: 5px; color: var(--text-dark); }
        .profile-details { text-align: left; margin-top: 15px; }
        .detail-item { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; color: var(--text-light); font-size: 14px; }
        .detail-item i { width: 20px; color: var(--primary); text-align: center; }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; background: #e8f5e9; color: var(--success); padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-top: 10px; }
        
        .nav-menu { list-style: none; }
        .nav-item { margin-bottom: 8px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 18px; color: var(--text-dark); text-decoration: none; border-radius: 10px; transition: all 0.3s; font-weight: 500; }
        .nav-link:hover { background: #f0f2f5; color: var(--primary); transform: translateX(5px); }
        .nav-link.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .nav-link i { width: 22px; font-size: 18px; }

        /* Main Content */
        .main-content { display: flex; flex-direction: column; gap: 25px; }
        
        .page-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title h2 { font-size: 24px; color: var(--text-dark); margin-bottom: 5px; }
        .page-title p { color: var(--text-light); font-size: 14px; }

        /* Notices Grid */
        .notices-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 25px; }
        
        .notice-card { 
            background: white; 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: var(--shadow-sm); 
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
            animation: fadeInUp 0.5s ease backwards;
        }
        
        .notice-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-md); }
        
        .notice-header {
            padding: 20px 25px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .notice-date {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 8px;
            min-width: 60px;
            border: 1px solid #eee;
        }
        .date-day { font-size: 18px; font-weight: 700; color: var(--primary); line-height: 1; }
        .date-month { font-size: 11px; text-transform: uppercase; color: var(--text-light); font-weight: 600; margin-top: 2px; }
        
        .notice-badges { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Category Colors */
        .cat-general { background: #e3f2fd; color: #1976d2; }
        .cat-election { background: #e8f5e9; color: #2e7d32; }
        .cat-result { background: #fff3e0; color: #ef6c00; }
        .cat-urgent { background: #ffebee; color: #c62828; }
        .cat-announcement { background: #f3e5f5; color: #7b1fa2; }
        
        .notice-body { padding: 25px; flex-grow: 1; }
        .notice-title { font-size: 18px; font-weight: 700; color: var(--text-dark); margin-bottom: 12px; line-height: 1.4; }
        .notice-content { color: var(--text-light); font-size: 14px; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
        
        .notice-footer { padding: 15px 25px; background: #fcfcfc; border-top: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #888; }
        
        .priority-indicator { display: flex; align-items: center; gap: 5px; font-weight: 600; }
        .prio-high { color: var(--accent); }
        .prio-medium { color: var(--warning); }
        .prio-low { color: var(--success); }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 16px;
            color: var(--text-light);
        }
        .empty-icon { font-size: 48px; color: #ddd; margin-bottom: 15px; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1024px) { .main-container { grid-template-columns: 1fr; } .sidebar { display: none; } }
        @media (max-width: 768px) { 
            .header-container { padding: 15px 20px; }
            .notices-grid { grid-template-columns: 1fr; }
            .sidebar { display: block; margin-bottom: 20px; }
        }
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
            <div class="page-header">
                <div class="page-title">
                    <h2>Campus Notices</h2>
                    <p>Stay updated with the latest announcements and election news.</p>
                </div>
                <div style="background: #e3f2fd; width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 24px;">
                    <i class="fas fa-bell"></i>
                </div>
            </div>

            <div class="notices-grid">
                <?php if($notices->num_rows > 0): 
                    $delay = 0;
                    while($row = $notices->fetch_assoc()): 
                        $delay += 0.1;
                        // Category Styling
                        $cat = strtolower($row['category']);
                        $cat_class = 'cat-general';
                        if(strpos($cat, 'election') !== false) $cat_class = 'cat-election';
                        elseif(strpos($cat, 'result') !== false) $cat_class = 'cat-result';
                        elseif(strpos($cat, 'urgent') !== false) $cat_class = 'cat-urgent';
                        elseif(strpos($cat, 'announcement') !== false) $cat_class = 'cat-announcement';

                        // Priority Styling
                        $prio = strtolower($row['priority']);
                        $prio_class = 'prio-low';
                        $prio_icon = 'fa-info-circle';
                        if($prio == 'high' || $prio == 'urgent') {
                            $prio_class = 'prio-high';
                            $prio_icon = 'fa-exclamation-circle';
                        } elseif($prio == 'medium') {
                            $prio_class = 'prio-medium';
                            $prio_icon = 'fa-bell';
                        }
                ?>
                <div class="notice-card" style="animation-delay: <?= $delay ?>s;">
                    <div class="notice-header">
                        <div class="notice-date">
                            <span class="date-day"><?= date('d', strtotime($row['published_at'])) ?></span>
                            <span class="date-month"><?= date('M', strtotime($row['published_at'])) ?></span>
                        </div>
                        <div class="notice-badges">
                            <span class="badge <?= $cat_class ?>"><?= htmlspecialchars($row['category']) ?></span>
                        </div>
                    </div>
                    <div class="notice-body">
                        <h3 class="notice-title"><?= htmlspecialchars($row['title']) ?></h3>
                        <div class="notice-content">
                            <?= nl2br(htmlspecialchars($row['content'])) ?>
                        </div>
                    </div>
                    <div class="notice-footer">
                        <div class="priority-indicator <?= $prio_class ?>">
                            <i class="fas <?= $prio_icon ?>"></i> <?= ucfirst($row['priority']) ?> Priority
                        </div>
                        <?php if($row['expires_at']): ?>
                        <div title="Expires on">
                            <i class="far fa-clock"></i> <?= date('M d', strtotime($row['expires_at'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; else: ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open empty-icon"></i>
                    <h3>No Active Notices</h3>
                    <p>There are no announcements to display at this time.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>