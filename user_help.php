<?php
session_start();
require "config.php";
if(!isset($_SESSION['voter_id'])){ header("Location: login.php"); exit; }
$voter_id = $_SESSION['voter_id'];
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
    <title>Help | User Dashboard</title>
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
        .card { background: white; border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .faq-item { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
        .faq-question { font-weight: 600; color: var(--primary); margin-bottom: 5px; cursor: pointer; display: flex; justify-content: space-between; }
        .faq-answer { color: #555; font-size: 14px; line-height: 1.6; display: none; margin-top: 10px; }
        .faq-item.active .faq-answer { display: block; }
        
        @media (max-width: 1024px) { .main-container { grid-template-columns: 1fr; } }
    </style>
    <script>
        function toggleFaq(element) {
            element.parentElement.classList.toggle('active');
        }
    </script>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo"><div class="logo-icon"><i class="fas fa-vote-yea"></i></div><span class="logo-text">Voting System</span></div>
            <nav>
                 <a href="userDashboard.php">Dashboard</a>
                 <a href="user_results.php">Result</a>
                 <a href="user_notices.php">Notice Board</a>
                 <a href="user_help.php" class="active">Help</a>
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
                <li class="nav-item"><a href="user_notices.php" class="nav-link"><i class="fas fa-bullhorn"></i> <span>Notice Board</span></a></li>
                <li class="nav-item"><a href="user_help.php" class="nav-link active"><i class="fas fa-question-circle"></i> <span>Help</span></a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="card">
                <h2>Frequently Asked Questions</h2>
                <p style="color:#666; margin-bottom:20px;">Find answers to common questions about the voting process.</p>
                
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">How do I cast my vote? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">Go to the Dashboard, select a position, choose your preferred candidate, and click the "Vote" button. Confirm your choice in the popup.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">Can I change my vote? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">No, once a vote is submitted, it cannot be changed or revoked to ensure election integrity.</div>
                </div>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">How do I apply for candidacy? <i class="fas fa-chevron-down"></i></div>
                    <div class="faq-answer">During the active nomination phase, a "Apply for Required Position" button will appear on your dashboard sidebar. Click it to fill out the nomination form.</div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>