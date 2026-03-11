<?php
require 'config.php';

// Get all active notices
$current_date = date('Y-m-d H:i:s');
$notices_query = "
    SELECT n.*, u.name as author_name 
    FROM notices n 
    LEFT JOIN users u ON n.created_by = u.id 
    WHERE n.is_active = TRUE 
    AND (n.expires_at IS NULL OR n.expires_at > '$current_date')
    ORDER BY 
        CASE priority 
            WHEN 'Urgent' THEN 1
            WHEN 'High' THEN 2
            WHEN 'Medium' THEN 3
            WHEN 'Low' THEN 4
        END,
        n.published_at DESC
";
$notices = $conn->query($notices_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board - Online Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            background-attachment: fixed;
        }

        /* Header */
        header {
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .logo-text {
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        nav a {
            padding: 8px 18px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background: #f0f2f5;
            color: #667eea;
        }

        nav a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        /* Main Container */
        .main-container {
            max-width: 900px;
            margin: 100px auto 40px;
            padding: 0 20px;
        }

        .page-title {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 30px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .notices-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .notice-item {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
            animation: fadeIn 0.5s ease forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notice-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #f0f0f0;
        }

        .notice-item-header h2 {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .notice-item-date {
            font-size: 13px;
            color: #666;
            font-weight: 500;
        }

        .notice-item-body p {
            font-size: 15px;
            line-height: 1.7;
            color: #555;
        }

        .notice-item-footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-general { background: #e3f2fd; color: #1976d2; }
        .badge-election { background: #e8f5e9; color: #388e3c; }
        .badge-result { background: #fff3e0; color: #f57c00; }
        .badge-announcement { background: #f3e5f5; color: #7b1fa2; }
        .badge-urgent { background: #ffebee; color: #d32f2f; }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin: 40px 0;
        }

        .empty-icon {
            font-size: 64px;
            color: #667eea;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 28px;
            color: #333;
            margin-bottom: 12px;
        }

        .empty-state p {
            color: #666;
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Footer */
        footer {
            background: transparent;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 40px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            opacity: 0.8;
            transition: opacity 0.3s;
        }

        .footer-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 20px;
            }

            .page-title {
                font-size: 28px;
            }

            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
        }

    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <a href="firstpage.php" class="logo-icon">
                    <i class="fas fa-vote-yea"></i>
                </a>
                <a href="firstpage.php" class="logo-text">Voting System</a>
            </div>
            
            <nav id="mainNav">
                <a href="firstpage.php">Home</a>
                <a href="login.html">Login</a>
                <a href="noticeboard.php" class="active">Notice Board</a>
                <a href="help.html">Help</a>
            </nav>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <h1 class="page-title">Notice Board</h1>
        
        <?php if ($notices && $notices->num_rows > 0): ?>
        <div class="notices-list">
            <?php while($notice = $notices->fetch_assoc()): 
                $category = strtolower($notice['category']);
                $published_date = date('F d, Y', strtotime($notice['published_at']));
            ?>
            <div class="notice-item">
                <div class="notice-item-header">
                    <h2><?php echo htmlspecialchars($notice['title']); ?></h2>
                    <span class="notice-item-date"><?php echo $published_date; ?></span>
                </div>
                <div class="notice-item-body">
                    <p><?php echo nl2br(htmlspecialchars($notice['content'])); ?></p>
                </div>
                <div class="notice-item-footer">
                    <div class="notice-meta">
                        <span class="badge badge-<?php echo $category; ?>"><?php echo htmlspecialchars($notice['category']); ?></span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="far fa-newspaper"></i>
            </div>
            <h3>No Notices Available</h3>
            <p>There are currently no notices or announcements. Please check back later for updates.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <h3>Online Voting System</h3>
            <div class="footer-links">
                <a href="firstpage.php">Home</a>
                <a href="login.html">Login</a>
                <a href="help.html">Help</a>
            </div>
            
            <p style="margin-top: 20px; opacity: 0.9;">
                &copy; <?php echo date('Y'); ?> Online Voting System. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>