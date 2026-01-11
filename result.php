<?php
require "config.php";

// Fetch voting session
$session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();

// Check if results are published
$published = ($session && isset($session['results_published']) && $session['results_published']);

// Fetch positions
$positions = $conn->query("SELECT DISTINCT position FROM candidates");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Online Voting System</title>
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
            display: flex;
            flex-direction: column;
        }

        /* Header Styles */
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

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            margin-bottom: 40px;
            color: white;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .position-box {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 40px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.5s ease forwards;
        }

        .position-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .position-title i {
            color: #667eea;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th {
            background: #f8f9fa;
            color: #666;
            font-weight: 600;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #333;
            vertical-align: middle;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            background: #ccc;
        }

        .rank-1 .rank-badge { background: #ffd700; box-shadow: 0 2px 5px rgba(255, 215, 0, 0.4); }
        .rank-2 .rank-badge { background: #c0c0c0; }
        .rank-3 .rank-badge { background: #cd7f32; }

        .winner-banner {
            margin-top: 20px;
            padding: 15px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 10px;
            text-align: center;
            color: #667eea;
            font-weight: 600;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        /* Empty State / Error */
        .message-container {
            text-align: center;
            background: white;
            padding: 60px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            margin: 0 auto;
        }

        .message-icon {
            font-size: 64px;
            color: #e53e3e;
            margin-bottom: 20px;
        }

        .message-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: transform 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        /* Footer */
        footer {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 20px;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            opacity: 0.9;
            transition: 0.3s;
        }

        .footer-links a:hover {
            opacity: 1;
            text-decoration: underline;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .header-container { padding: 15px 20px; }
            .section-title { font-size: 28px; }
            th, td { padding: 10px; font-size: 14px; }
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
                 <a href="result.php" class="active">Result</a>
                 <a href="about.html">About Us</a>
                 <a href="noticeboard.php">Notice Board</a>
            </nav>
        </div>
    </header>

    <div class="main-container">
        <?php if (!$published): ?>
            <div class="message-container">
                <div class="message-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h2 class="message-title">Results Not Published</h2>
                <p style="color: #666; line-height: 1.6;">
                    The election results have not been published yet. <br>
                    Please check back later or keep an eye on the notice board for updates.
                </p>
                <a href="firstpage.php" class="btn">Back to Home</a>
            </div>
        <?php else: ?>
            <h1 class="section-title">🏆 Election Results</h1>

            <?php while ($pos = $positions->fetch_assoc()): ?>
                <?php
                    // Fetch candidates by position with vote count
                    $stmt = $conn->prepare("
                        SELECT c.id, c.name, c.party_name AS party, c.photo,
                        COUNT(v.id) AS votes
                        FROM candidates c
                        LEFT JOIN votes v ON c.id = v.candidate_id
                        WHERE c.position = ?
                        GROUP BY c.id
                        ORDER BY votes DESC
                    ");
                    $stmt->bind_param("s", $pos['position']);
                    $stmt->execute();
                    $results = $stmt->get_result();
                    
                    // Calculate total votes for percentage
                    $total_votes_query = $conn->prepare("SELECT COUNT(*) as total FROM votes WHERE position = ?");
                    $total_votes_query->bind_param("s", $pos['position']);
                    $total_votes_query->execute();
                    $total_votes = $total_votes_query->get_result()->fetch_assoc()['total'];
                    if ($total_votes == 0) $total_votes = 1; // Avoid division by zero
                ?>
                <div class="position-box">
                    <h2 class="position-title">
                        <i class="fas fa-award"></i>
                        <?= htmlspecialchars($pos['position']) ?>
                    </h2>

                    <table>
                        <thead>
                            <tr>
                                <th width="10%">Rank</th>
                                <th>Candidate</th>
                                <th>Party</th>
                                <th width="20%">Votes</th>
                                <th width="15%">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 1;
                            $winnerName = "";
                            $maxVotes = -1;
                            
                            while ($row = $results->fetch_assoc()):
                                $isWinner = false;
                                if ($rank == 1) {
                                    $winnerName = $row['name'];
                                    $maxVotes = $row['votes'];
                                    $isWinner = true;
                                }
                                
                                $percentage = round(($row['votes'] / $total_votes) * 100, 1);
                                $rankClass = $rank <= 3 ? "rank-$rank" : "";
                            ?>
                            <tr class="<?= $rankClass ?>">
                                <td>
                                    <div class="rank-badge"><?= $rank ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if(!empty($row['photo'])): ?>
                                            <img src="uploads/<?= htmlspecialchars($row['photo']) ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                                 onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <span style="font-weight: 600;"><?= htmlspecialchars($row['name']) ?></span>
                                        <?php if($isWinner && $row['votes'] > 0): ?>
                                            <i class="fas fa-crown" style="color: #ffd700;"></i>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['party']) ?></td>
                                <td>
                                    <div style="font-weight: bold;"><?= $row['votes'] ?></div>
                                </td>
                                <td>
                                    <div style="background: #e9ecef; border-radius: 10px; height: 6px; width: 100%; margin-bottom: 5px;">
                                        <div style="background: #667eea; height: 100%; border-radius: 10px; width: <?= $percentage ?>%;"></div>
                                    </div>
                                    <div style="font-size: 12px; color: #666;"><?= $percentage ?>%</div>
                                </td>
                            </tr>
                            <?php $rank++; endwhile; ?>
                        </tbody>
                    </table>

                    <?php if($winnerName && $maxVotes > 0): ?>
                    <div class="winner-banner">
                        🎉 Congratulations <strong><?= htmlspecialchars($winnerName) ?></strong> on winning the position!
                    </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
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
            <p style="margin-top: 20px; opacity: 0.8; font-size: 14px;">
                &copy; <?= date('Y') ?> Voting System. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>
