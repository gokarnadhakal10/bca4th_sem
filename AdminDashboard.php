<?php
require "config.php";
require "auth.php";
admin_required();

// Fetch candidates
$candidates = $conn->query("SELECT * FROM candidates");

// Fetch voting session
$session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();

// Get statistics
$total_voters = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='Voter'")->fetch_assoc()['total'];
$total_candidates = $conn->query("SELECT COUNT(*) as total FROM candidates")->fetch_assoc()['total'];
$total_votes = $conn->query("SELECT COUNT(*) as total FROM votes")->fetch_assoc()['total'];
$active_voters = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='Voter' AND status='Active'")->fetch_assoc()['total'];

// Get recent activities
$recent_votes = $conn->query("
    SELECT v.*, u.name as voter_name, c.name as candidate_name 
    FROM votes v 
    JOIN users u ON v.voter_id = u.id 
    JOIN candidates c ON v.candidate_id = c.id 
    ORDER BY v.id DESC  -- Changed from v.created_at DESC to v.id DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Voting System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #f72585;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --gray: #6c757d;
            --card-bg: #ffffff;
            --sidebar-bg: #1a1a2e;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.07);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.1);
            --radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Header */
        .admin-header {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-lg);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .header-container {
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .admin-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 50px;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
        }

        .logout-btn {
            padding: 10px 24px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        /* Main Layout */
        .dashboard-layout {
            display: flex;
            min-height: calc(100vh - 80px);
            margin-top: 80px;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: var(--sidebar-bg);
            color: white;
            position: fixed;
            height: calc(100vh - 80px);
            overflow-y: auto;
            box-shadow: var(--shadow-lg);
            z-index: 900;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.2rem;
            color: white;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 4px solid transparent;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: var(--primary);
        }

        .menu-item.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: var(--primary);
        }

        .menu-item i {
            font-size: 1.1rem;
            width: 24px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
            background: rgba(255,255,255,0.95);
            min-height: calc(100vh - 80px);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }

        .stat-icon.voters {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }

        .stat-icon.candidates {
            background: linear-gradient(135deg, var(--accent), #d63384);
        }

        .stat-icon.votes {
            background: linear-gradient(135deg, var(--success), #00b4d8);
        }

        .stat-icon.active {
            background: linear-gradient(135deg, var(--warning), #f3722c);
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark);
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
            margin: 5px 0 0;
        }

        /* Voting Control Card */
        .control-card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 25px;
        }

        .section-title i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            padding: 12px;
            border-radius: 10px;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), #00b4d8);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 201, 240, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning), #f3722c);
            color: white;
            box-shadow: 0 4px 15px rgba(248, 150, 30, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(248, 150, 30, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger), #d63384);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 37, 133, 0.4);
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .action-btn {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: var(--dark);
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .action-btn i {
            font-size: 2rem;
            color: var(--primary);
        }

        .action-btn span {
            font-weight: 600;
            font-size: 0.95rem;
        }

        /* Table */
        .table-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-top: 20px;
        }

        .table-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            color: var(--dark);
        }

        tr:hover {
            background: rgba(67, 97, 238, 0.02);
        }

        .candidate-photo {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
        }

        .status-paused {
            background: rgba(248, 150, 30, 0.1);
            color: var(--warning);
        }

        .status-ended {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger);
        }

        /* Recent Activity */
        .activity-list {
            background: white;
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-md);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .activity-content {
            flex: 1;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--gray);
        }

        /* Session Status */
        .session-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 15px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }
            .main-content {
                margin-left: 220px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                display: none;
            }
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            .dashboard-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            .table-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="admin-header">
    <div class="header-container">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <span class="logo-text">Admin Dashboard</span>
        </div>
        
        <div class="admin-nav">
            <div class="admin-info">
                <div class="admin-avatar">
                    A
                </div>
                <div>
                    <div style="font-weight: 600; color: var(--dark);">Administrator</div>
                    <div style="font-size: 0.85rem; color: var(--gray);">Super Admin</div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<!-- Main Layout -->
<div class="dashboard-layout">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-cogs"></i> Admin Panel</h3>
        </div>
        <div class="sidebar-menu">
            <a href="AdminDashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="voters.php" class="menu-item">
                <i class="fas fa-users"></i> Voters Management
            </a>
            <a href="candidates.php" class="menu-item">
                <i class="fas fa-user-tie"></i> Candidates
            </a>
            <a href="result.php" class="menu-item">
                <i class="fas fa-chart-bar"></i> Results
            </a>
            <a href="admin_notices.php" class="menu-item">
                <i class="fas fa-bullhorn"></i> Manage Notices
            </a>
            <a href="firstpage.php" class="menu-item">
                <i class="fas fa-globe"></i> View Website
            </a>
            <div style="margin: 30px 25px 0; padding: 20px 0; border-top: 1px solid rgba(255,255,255,0.1);">
                <div style="color: rgba(255,255,255,0.6); font-size: 0.9rem; margin-bottom: 10px;">Quick Links</div>
                <a href="add_candidates.php" class="menu-item" style="padding: 10px 25px; font-size: 0.9rem;">
                    <i class="fas fa-user-plus"></i> Add Candidate
                </a>
                <a href="studentRegistration.html" class="menu-item" style="padding: 10px 25px; font-size: 0.9rem;">
                    <i class="fas fa-user-plus"></i> Add Voter
                </a>
                <a href="hero_upload.php" class="menu-item" style="padding: 10px 25px; font-size: 0.9rem;">
                    <i class="fas fa-image"></i> Hero Page
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Statistics Grid -->
        <div class="dashboard-grid animate-fade">
            <div class="stat-card">
                <div class="stat-icon voters">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_voters; ?></h3>
                    <p>Total Voters</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon candidates">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_candidates; ?></h3>
                    <p>Total Candidates</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon votes">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_votes; ?></h3>
                    <p>Total Votes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $active_voters; ?></h3>
                    <p>Active Voters</p>
                </div>
            </div>
        </div>

        <!-- Voting Control Card -->
        <div class="control-card animate-fade">
            <div class="section-title">
                <i class="fas fa-sliders-h"></i>
                <span>Voting Session Control</span>
                <?php if($session): ?>
                    <span class="session-status <?php 
                        echo $session['status'] == 'Active' ? 'status-active' : 
                             ($session['status'] == 'Paused' ? 'status-paused' : 'status-ended'); 
                    ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo $session['status']; ?>
                    </span>
                <?php endif; ?>
            </div>

            <form action="voting_session.php" method="POST" class="animate-fade">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px;">
                    <div class="form-group">
                        <label class="form-label">Start Time</label>
                        <input type="datetime-local" name="start" class="form-control" required
                               value="<?= $session ? htmlspecialchars($session['start_time']) : '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="datetime-local" name="end" class="form-control" required
                               value="<?= $session ? htmlspecialchars($session['end_time']) : '' ?>">
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <button type="submit" name="action" value="start" class="btn btn-primary">
                        <i class="fas fa-play"></i> Start Voting
                    </button>
                    <button type="submit" name="action" value="pause" class="btn btn-warning">
                        <i class="fas fa-pause"></i> Pause Voting
                    </button>
                    <button type="submit" name="action" value="resume" class="btn btn-success">
                        <i class="fas fa-play-circle"></i> Resume Voting
                    </button>
                    <button type="submit" name="action" value="end" class="btn btn-danger">
                        <i class="fas fa-stop"></i> End Voting
                    </button>
                </div>
            </form>
        </div>

        <!-- Quick Actions -->
        <div class="control-card animate-fade">
            <div class="section-title">
                <i class="fas fa-bolt"></i>
                <span>Quick Actions</span>
            </div>
            
            <div class="actions-grid">
                <a href="add_candidates.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New Candidate</span>
                </a>
                <a href="studentRegistration.html" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Add New Voter</span>
                </a>
                <a href="result.php" class="action-btn">
                    <i class="fas fa-chart-bar"></i>
                    <span>Publish Results</span>
                </a>
                <a href="hero_upload.php" class="action-btn">
                    <i class="fas fa-image"></i>
                    <span>Update Hero Page</span>
                </a>
                <a href="admin_notices.php" class="action-btn">
                    <i class="fas fa-bullhorn"></i>
                    <span>Manage Notices</span>
                </a>
                <a href="voters.php" class="action-btn">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Voters</span>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <?php if($recent_votes && $recent_votes->num_rows > 0): ?>
        <div class="control-card animate-fade">
            <div class="section-title">
                <i class="fas fa-history"></i>
                <span>Recent Activity</span>
            </div>
            <div class="activity-list">
                <?php while($activity = $recent_votes->fetch_assoc()): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-vote-yea"></i>
                    </div>
                    <div class="activity-content">
                        <div style="font-weight: 500; color: var(--dark);">
                            <?php echo htmlspecialchars($activity['voter_name']); ?> voted for <?php echo htmlspecialchars($activity['candidate_name']); ?>
                        </div>
                        <div class="activity-time">
                            <?php echo date('M d, Y - h:i A', strtotime($activity['created_at'])); ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Candidates Table -->
        <div class="control-card animate-fade">
            <div class="table-header">
                <div class="section-title" style="margin: 0;">
                    <i class="fas fa-user-tie"></i>
                    <span>All Candidates</span>
                </div>
                <a href="add_candidates.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Candidate
                </a>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Party</th>
                            <th>Position</th>
                            <th>Class</th>
                            <th>Faculty</th>
                            <th>Votes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($c = $candidates->fetch_assoc()): 
                            // Fetch votes for this candidate
                            $vote_count_sql = "SELECT COUNT(*) as total_votes FROM votes WHERE candidate_id=?";
                            $stmt_votes = $conn->prepare($vote_count_sql);
                            $stmt_votes->bind_param("i", $c['id']);
                            $stmt_votes->execute();
                            $vote_result = $stmt_votes->get_result();
                            $vote_data = $vote_result->fetch_assoc();
                        ?>
                        <tr>
                            <td><?= $c['id'] ?></td>
                            <td>
                                <img src="uploads/<?= htmlspecialchars($c['photo']) ?>" 
                                     alt="Candidate Photo" 
                                     class="candidate-photo"
                                     onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($c['name']) ?>&background=4361ee&color=fff'">
                            </td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><?= htmlspecialchars($c['party']) ?></td>
                            <td>
                                <span class="status-badge status-active">
                                    <?= htmlspecialchars($c['position']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['class']) ?></td>
                            <td><?= htmlspecialchars($c['faculty']) ?></td>
                            <td>
                                <strong style="color: var(--primary);"><?= $vote_data['total_votes'] ?></strong>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_candidates.php?id=<?= $c['id'] ?>" class="btn btn-success btn-sm">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_candidates.php?id=<?= $c['id'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this candidate?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
// Toggle mobile sidebar
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.style.display = sidebar.style.display === 'block' ? 'none' : 'block';
}

// Add active class to clicked menu items
document.querySelectorAll('.menu-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.menu-item').forEach(i => i.classList.remove('active'));
        this.classList.add('active');
    });
});

// Auto-refresh page every 2 minutes
setTimeout(() => {
    console.log('Auto-refreshing admin dashboard...');
    location.reload();
}, 120000);

// Voting session status update
function updateSessionStatus(status) {
    const statusElement = document.querySelector('.session-status');
    if (statusElement) {
        statusElement.className = 'session-status ' + 
            (status === 'Active' ? 'status-active' : 
             status === 'Paused' ? 'status-paused' : 'status-ended');
        statusElement.innerHTML = `<i class="fas fa-circle"></i> ${status}`;
    }
}

// Confirm before ending voting session
document.querySelector('button[value="end"]')?.addEventListener('click', function(e) {
    if (!confirm('Are you sure you want to end the voting session? This cannot be undone.')) {
        e.preventDefault();
    }
});

// Smooth scroll for page sections
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const target = document.querySelector(targetId);
        if (target) {
            window.scrollTo({
                top: target.offsetTop - 100,
                behavior: 'smooth'
            });
        }
    });
});
</script>

</body>
</html>