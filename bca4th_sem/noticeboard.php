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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* Header */
        header {
            width: 100%;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .logo-text {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        nav {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        nav a {
            padding: 10px 20px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        nav a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
        }

        nav a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Hero Section */
        .hero {
            margin-top: 80px;
            padding: 60px 40px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
            text-align: center;
            color: white;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero p {
            font-size: 18px;
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto 30px;
        }

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 12px 25px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .filter-tab:hover:not(.active) {
            background: #e4e6e9;
        }

        /* Section Titles */
        .section-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
            color: #333;
        }

        /* Notices Grid */
        .notices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .notice-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .notice-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 30px rgba(102, 126, 234, 0.3);
        }

        .notice-header {
            padding: 25px 25px 15px;
            position: relative;
        }

        .notice-badge {
            position: absolute;
            top: 20px;
            right: 25px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-general { background: #e3f2fd; color: #1976d2; }
        .badge-election { background: #e8f5e9; color: #388e3c; }
        .badge-result { background: #fff3e0; color: #f57c00; }
        .badge-announcement { background: #f3e5f5; color: #7b1fa2; }
        .badge-urgent { background: #ffebee; color: #d32f2f; }

        .priority-tag {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .priority-urgent { background: linear-gradient(135deg, #ff5252 0%, #ff1744 100%); color: white; }
        .priority-high { background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; }
        .priority-medium { background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%); color: white; }
        .priority-low { background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%); color: white; }

        .notice-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: #333;
            line-height: 1.4;
        }

        .notice-body {
            padding: 0 25px 25px;
        }

        .notice-content {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .notice-footer {
            padding: 20px 25px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notice-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
        }

        .notice-meta i {
            margin-right: 5px;
            color: #667eea;
        }

        /* Results Section */
        .results-section {
            background: white;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 60px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .position-results {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
        }

        .position-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .result-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .candidate-rank {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .candidate-info {
            flex: 1;
        }

        .candidate-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .candidate-party {
            color: #666;
            font-size: 14px;
        }

        .vote-stats {
            text-align: right;
        }

        .vote-count {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .vote-percentage {
            font-size: 14px;
            color: #666;
        }

        .progress-bar {
            width: 150px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 4px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
        }

        .footer-links a:hover {
            opacity: 0.8;
            text-decoration: underline;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: 0.3s;
        }

        .social-links a:hover {
            background: white;
            color: #667eea;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header-container {
                padding: 15px 20px;
            }

            .hero {
                padding: 40px 20px;
            }

            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .main-container {
                padding: 20px;
            }

            .notices-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .filter-tabs {
                justify-content: center;
                gap: 10px;
            }

            .filter-tab {
                padding: 10px 20px;
                font-size: 14px;
            }

            .section-title {
                font-size: 28px;
            }

            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .logo-text {
                font-size: 18px;
            }

            .hero h1 {
                font-size: 26px;
            }

            .result-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .candidate-rank {
                margin-bottom: 10px;
            }

            .vote-stats {
                width: 100%;
                text-align: left;
                margin-top: 10px;
            }

            .progress-bar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <span class="logo-text">Voting System</span>
            </div>
            
            <nav id="mainNav">
                <a href="firstpage.php">Home</a>
                <a href="login.html">Login</a>
                <a href="noticeboard.php" class="active">Notice Board</a>
                <a href="help.html">Help</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Notice Board & Announcements</h1>
        <p>
            Stay informed with the latest election updates, important notices, and official results. 
            All campus election information in one place, updated in real-time.
        </p>
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterNotices('all')">All Notices</button>
            <button class="filter-tab" onclick="filterNotices('election')">Election Updates</button>
        </div>
    </section>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Notices Section -->
        <h2 class="section-title">Latest Notices & Announcements</h2>
        
        <?php if ($notices && $notices->num_rows > 0): ?>
        <div class="notices-grid" id="noticesContainer">
            <?php while($notice = $notices->fetch_assoc()): 
                $category = strtolower($notice['category']);
                $priority = strtolower($notice['priority']);
                $published_date = date('M d, Y - h:i A', strtotime($notice['published_at']));
            ?>
            <div class="notice-card" data-category="<?php echo $category; ?>">
                <div class="notice-header">
                    <span class="priority-tag priority-<?php echo $priority; ?>">
                        <?php echo $notice['priority']; ?>
                    </span>
                    <span class="notice-badge badge-<?php echo $category; ?>">
                        <?php echo $notice['category']; ?>
                    </span>
                    <h3><?php echo htmlspecialchars($notice['title']); ?></h3>
                </div>
                
                <div class="notice-body">
                    <div class="notice-content">
                        <?php echo nl2br(htmlspecialchars(substr($notice['content'], 0, 150))); ?>
                        <?php if (strlen($notice['content']) > 150): ?>
                        ... <a href="#" class="read-more" onclick="toggleReadMore(this)">Read More</a>
                        <span style="display: none;"><?php echo nl2br(htmlspecialchars(substr($notice['content'], 150))); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="notice-footer">
                    <div class="notice-meta">
                        <span><i class="far fa-calendar"></i> <?php echo $published_date; ?></span>
                        <?php if ($notice['author_name']): ?>
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($notice['author_name']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-newspaper"></i>
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
                <a href="noticeboard.php">Notice Board</a>
                <a href="help.html">Help</a>
            </div>
            
            <p style="margin-top: 20px; opacity: 0.9;">
                &copy; <?php echo date('Y'); ?> Online Voting System. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        // Filter notices by category
        function filterNotices(category) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Filter notices
            const notices = document.querySelectorAll('.notice-card');
            notices.forEach(notice => {
                if (category === 'all' || notice.dataset.category === category) {
                    notice.style.display = 'block';
                } else {
                    notice.style.display = 'none';
                }
            });
            
            // Show empty state if no notices
            const visibleNotices = Array.from(notices).filter(n => n.style.display !== 'none');
            const noticesContainer = document.getElementById('noticesContainer');
            
            if (visibleNotices.length === 0 && noticesContainer) {
                const existingEmptyMsg = noticesContainer.nextElementSibling;
                if (!existingEmptyMsg || !existingEmptyMsg.classList.contains('empty-state')) {
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className = 'empty-state';
                    emptyMsg.innerHTML = `
                        <div class="empty-icon">
                            <i class="fas fa-inbox"></i>
                        </div>
                        <h3>No ${category.charAt(0).toUpperCase() + category.slice(1)} Notices</h3>
                        <p>There are no notices in this category at the moment.</p>
                    `;
                    noticesContainer.parentNode.insertBefore(emptyMsg, noticesContainer);
                }
                noticesContainer.style.display = 'none';
            } else {
                const existingEmptyMsg = noticesContainer.nextElementSibling;
                if (existingEmptyMsg && existingEmptyMsg.classList.contains('empty-state')) {
                    existingEmptyMsg.remove();
                }
                noticesContainer.style.display = 'grid';
            }
        }
        
        // Toggle read more/less
        function toggleReadMore(link) {
            const contentDiv = link.parentNode;
            const hiddenText = link.nextElementSibling;
            const currentText = contentDiv.firstChild.textContent;
            
            if (link.textContent === 'Read More') {
                contentDiv.innerHTML = currentText + hiddenText.textContent + 
                    ' <a href="#" class="read-more" onclick="toggleReadMore(this)">Read Less</a>';
            } else {
                contentDiv.innerHTML = currentText.substring(0, 150) + '... ' + 
                    '<a href="#" class="read-more" onclick="toggleReadMore(this)">Read More</a>' +
                    '<span style="display: none;">' + currentText.substring(150) + '</span>';
            }
            
            return false;
        }
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>