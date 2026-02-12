<?php
require "config.php";
require "auth.php";
admin_required();

// Handle Publish/Unpublish Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['publish_results'])) {
        $conn->query("UPDATE voting_session SET results_published=TRUE WHERE id=1");
        $success = "Results have been published to the student portal.";
    }
    if (isset($_POST['unpublish_results'])) {
        $conn->query("UPDATE voting_session SET results_published=FALSE WHERE id=1");
        $success = "Results have been hidden from the student portal.";
    }
}

// Fetch voting session
$session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();

// Fetch positions
$positions = $conn->query("SELECT DISTINCT position FROM candidates");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results (Admin) - Online Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        
        body {
            background: #f0f2f5;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            padding: 20px;
            z-index: 100;
            transition: all 0.3s;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .nav-links { list-style: none; }
        .nav-links li { margin-bottom: 10px; }
        
        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-links a:hover, .nav-links a.active {
            background: rgba(67, 97, 238, 0.2);
            color: white;
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .dashboard-section {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            animation: slideUp 0.5s ease forwards;
        }

        .section-title { font-size: 18px; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 10px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }

        table { width: 100%; border-collapse: collapse; }
        th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; color: #555; text-transform: uppercase; font-size: 12px; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }

        .rank-badge { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; background: #ccc; }
        .rank-1 .rank-badge { background: #ffd700; box-shadow: 0 2px 5px rgba(255, 215, 0, 0.4); }
        .rank-2 .rank-badge { background: #c0c0c0; }
        .rank-3 .rank-badge { background: #cd7f32; }

        .winner-banner { margin-top: 20px; padding: 15px; background: rgba(67, 97, 238, 0.1); border-radius: 8px; text-align: center; color: var(--primary); font-weight: 600; border: 1px solid rgba(67, 97, 238, 0.2); }
        
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }

        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-vote-yea"></i></div>
            <h3>Admin Panel</h3>
        </div>
        <ul class="nav-links">
            <li><a href="AdminDashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="voters.php"><i class="fas fa-users"></i> Voters</a></li>
            <li><a href="candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
            <li><a href="admin_result.php" class="active"><i class="fas fa-chart-bar"></i> Results</a></li>
            <li><a href="admin_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="welcome-text">
                <h2>Election Results</h2>
                <p>Real-time vote counting and ranking.</p>
            </div>
            <a href="AdminDashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <!-- Publication Status Control -->
        <div class="dashboard-section" style="display: flex; justify-content: space-between; align-items: center; border-left: 5px solid <?php echo $session['results_published'] ? '#2ecc71' : '#e74c3c'; ?>;">
            <div>
                <h3 style="color: var(--dark); margin-bottom: 5px; display: flex; align-items: center; gap: 10px;">
                    Status: 
                    <span style="color: <?php echo $session['results_published'] ? '#2ecc71' : '#e74c3c'; ?>; text-transform: uppercase;">
                        <?php echo $session['results_published'] ? 'Published' : 'Unpublished'; ?>
                    </span>
                </h3>
                <p style="color: #666; font-size: 14px;">
                    <?php echo $session['results_published'] ? 'Results are currently visible to all students on the result page.' : 'Results are hidden from students. Only admins can view them.'; ?>
                </p>
            </div>
            <form method="POST">
                <?php if($session['results_published']): ?>
                    <button type="submit" name="unpublish_results" class="btn btn-danger"><i class="fas fa-eye-slash"></i> Unpublish Results</button>
                <?php else: ?>
                    <button type="submit" name="publish_results" class="btn btn-success"><i class="fas fa-eye"></i> Publish Results</button>
                <?php endif; ?>
            </form>
        </div>

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
            <div class="dashboard-section">
                <div class="section-title">
                    <i class="fas fa-award" style="color: var(--primary);"></i>
                    <?= htmlspecialchars($pos['position']) ?>
                </div>

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
    </div>

</body>
</html>