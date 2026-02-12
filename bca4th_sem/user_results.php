<?php
session_start();
require "config.php";

// Check login
if(!isset($_SESSION['voter_id'])){
    header("Location: login.php");
    exit;
}

$voter_id = $_SESSION['voter_id'];

// Fetch voter info for sidebar
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// Fetch voting session for results status
$session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();
$published = ($session && isset($session['results_published']) && $session['results_published']);

// Fetch positions
$positions = $conn->query("SELECT DISTINCT position FROM candidates");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results | User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --accent: #e53e3e;
            --success: #38a169;
            --light-bg: #f4f6f9;
            --card-bg: #ffffff;
            --text-dark: #333;
            --text-light: #666;
            --border: #e0e0e0;
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
        .sidebar { background: var(--card-bg); border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; height: fit-content; }
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

        /* Content */
        .main-content { display: flex; flex-direction: column; gap: 20px; }
        .card { background: var(--card-bg); border-radius: 8px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        /* Results Table */
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px; background: #f8f9fa; color: #666; font-size: 13px; text-transform: uppercase; }
        td { padding: 12px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .rank-badge { width: 25px; height: 25px; border-radius: 50%; background: #ddd; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; color: white; }
        .rank-1 { background: #ffd700; } .rank-2 { background: #c0c0c0; } .rank-3 { background: #cd7f32; }
        .progress-bar { height: 6px; background: #eee; border-radius: 3px; overflow: hidden; margin-top: 5px; }
        .progress-fill { height: 100%; background: var(--primary); }

        @media (max-width: 1024px) { .main-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <div class="logo-icon"><i class="fas fa-vote-yea"></i></div>
                <span class="logo-text">Voting System</span>
            </div>
            <nav>
                 <a href="userDashboard.php">Dashboard</a>
                 <a href="user_results.php" class="active">Result</a>
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
                    <div class="detail-item">
                        <i class="fas fa-graduation-cap"></i>
                        <span><?= htmlspecialchars($voter['class'] ?? 'N/A') ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($voter['email'] ?? '') ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($voter['mobile'] ?? 'N/A') ?></span>
                    </div>
                </div>
                <div class="status-badge">
                    <i class="fas fa-circle"></i> <?= htmlspecialchars($voter['status'] ?? 'Active') ?>
                </div>
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="userDashboard.php" class="nav-link"><i class="fas fa-th-large"></i> <span>Dashboard</span></a></li>
                <li class="nav-item"><a href="user_results.php" class="nav-link active"><i class="fas fa-vote-yea"></i> <span>Result</span></a></li>
                <li class="nav-item"><a href="user_notices.php" class="nav-link"><i class="fas fa-bullhorn"></i> <span>Notice Board</span></a></li>
                <li class="nav-item"><a href="user_help.php" class="nav-link"><i class="fas fa-question-circle"></i> <span>Help</span></a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="card">
                <h2 style="margin-bottom: 10px; color: var(--text-dark);">Election Results</h2>
                <p style="color: var(--text-light);">View the latest standing of candidates.</p>
            </div>

            <?php if (!$published): ?>
                <div class="card" style="text-align: center; padding: 50px;">
                    <i class="fas fa-lock" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>Results Not Published</h3>
                    <p style="color: #666;">The election results have not been released yet. Please check back later.</p>
                </div>
            <?php else: ?>
                <?php while ($pos = $positions->fetch_assoc()): 
                    $stmt = $conn->prepare("SELECT c.name, c.party_name, c.photo, COUNT(v.id) AS votes FROM candidates c LEFT JOIN votes v ON c.id = v.candidate_id WHERE c.position = ? GROUP BY c.id ORDER BY votes DESC");
                    $stmt->bind_param("s", $pos['position']);
                    $stmt->execute();
                    $results = $stmt->get_result();
                    
                    // Total votes for percentage
                    $total_query = $conn->query("SELECT COUNT(*) as total FROM votes WHERE position = '".$pos['position']."'");
                    $total_votes = $total_query->fetch_assoc()['total'];
                    if ($total_votes == 0) $total_votes = 1;
                ?>
                <div class="card">
                    <h3 style="margin-bottom: 15px; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <?= htmlspecialchars($pos['position']) ?>
                    </h3>
                    <table>
                        <thead><tr><th>Rank</th><th>Candidate</th><th>Party</th><th>Votes</th><th>%</th></tr></thead>
                        <tbody>
                            <?php $rank = 1; while ($row = $results->fetch_assoc()): 
                                $pct = round(($row['votes'] / $total_votes) * 100, 1);
                            ?>
                            <tr>
                                <td><div class="rank-badge rank-<?= $rank ?>"><?= $rank ?></div></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if(!empty($row['photo'])): ?><img src="uploads/<?= $row['photo'] ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;"><?php endif; ?>
                                        <span style="font-weight: 500;"><?= htmlspecialchars($row['name']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['party_name']) ?></td>
                                <td><strong><?= $row['votes'] ?></strong></td>
                                <td width="20%">
                                    <div style="font-size: 12px; margin-bottom: 2px;"><?= $pct ?>%</div>
                                    <div class="progress-bar"><div class="progress-fill" style="width: <?= $pct ?>%"></div></div>
                                </td>
                            </tr>
                            <?php $rank++; endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>