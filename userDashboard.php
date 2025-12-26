<?php
session_start();
require "config.php";

// Check login
if(!isset($_SESSION['voter_id'])){
    header("Location: login.php");
    exit;
}

$voter_id = $_SESSION['voter_id'];

// Fetch voter info
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// If no voter found
if (!$voter) {
    echo "<p style='color:red;'>User info not found.</p>";
    exit;
}

// Fetch voting session safely
$vote_session = $conn->query("SELECT * FROM voting_session WHERE id=1");
$vote_session = $vote_session ? $vote_session->fetch_assoc() : null;

// Determine if voting is active
$now = date('Y-m-d H:i:s');
$can_vote = false;
if ($vote_session) {
    $can_vote = ($vote_session['status'] ?? '') == 'active'
                && $now >= ($vote_session['start_time'] ?? '')
                && $now <= ($vote_session['end_time'] ?? '');
}

// Fetch nomination session safely
$nomination_session = $conn->query("SELECT * FROM nomination_session WHERE id=1");
$nomination_session = $nomination_session ? $nomination_session->fetch_assoc() : null;

// Determine if requests are allowed
$can_request = false;
if ($nomination_session) {
    $can_request = $now >= ($nomination_session['start_time'] ?? '')
                   && $now <= ($nomination_session['end_time'] ?? '');
}

// Fetch all positions
$positions_result = $conn->query("SELECT DISTINCT position FROM candidates");
$positions = $positions_result ? $positions_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch votes cast by this voter
$stmt = $conn->prepare("SELECT position, candidate_id FROM votes WHERE voter_id=?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$res = $stmt->get_result();

$voted_positions = [];
$voted_candidates = [];
while ($row = $res->fetch_assoc()) {
    $voted_positions[] = $row['position'];
    $voted_candidates[$row['position']] = $row['candidate_id'];
}

// Fetch pending candidate request
$has_requested = false;
$request_status = '';
$cr = $conn->query("SELECT * FROM candidate_requests WHERE voter_id={$voter_id}");
if ($cr && $cr->num_rows > 0) {
    $has_requested = true;
    $request_data = $cr->fetch_assoc();
    $request_status = $request_data['status'];
}

// Fetch recent activities
// SIMPLE FIX - Change this line only:
$activities = $conn->query("
    SELECT 'vote' as type, c.name as candidate_name, v.position, 
           NOW() as timestamp 
    FROM votes v 
    LEFT JOIN candidates c ON v.candidate_id = c.id 
    WHERE v.voter_id = $voter_id 
    ORDER BY v.id DESC 
    LIMIT 5
");

// Calculate voting progress
$total_positions = count($positions);
$voted_count = count($voted_candidates);
$voting_progress = $total_positions > 0 ? round(($voted_count / $total_positions) * 100) : 0;

// Fetch upcoming elections (if any)
$upcoming_elections = $conn->query("
    SELECT * FROM voting_session 
    WHERE start_time > NOW() 
    ORDER BY start_time ASC 
    LIMIT 2
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard | Online Voting System</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
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
        header {
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
            max-width: 1400px;
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

        .user-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 50px;
        }

        .user-avatar {
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

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 100px auto 50px;
            padding: 0 30px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar */
        .sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }

        .profile-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            text-align: center;
            margin-bottom: 25px;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            margin: 0 auto 20px;
            position: relative;
        }

        .profile-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            border: 4px solid white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .profile-name {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }

        .profile-details {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--gray);
            font-size: 0.95rem;
        }

        .detail-item i {
            color: var(--primary);
            width: 20px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.2));
            color: #2e7d32;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
        }

        .status-blocked {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1), rgba(244, 67, 54, 0.2));
            color: #d32f2f;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .stat-icon.votes {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }

        .stat-icon.nomination {
            background: linear-gradient(135deg, var(--accent), #d63384);
        }

        .stat-icon.progress {
            background: linear-gradient(135deg, var(--warning), #f3722c);
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark);
        }

        .stat-info p {
            color: var(--gray);
            font-size: 0.9rem;
            margin: 5px 0 0;
        }

        /* Progress Bar */
        .progress-section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 25px;
            box-shadow: var(--shadow-md);
            margin-bottom: 25px;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 5px;
            width: <?php echo $voting_progress; ?>%;
            transition: width 1s ease-in-out;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        /* Dashboard Cards */
        .dashboard-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(0,0,0,0.05);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark);
        }

        .card-title i {
            color: var(--primary);
            background: rgba(67, 97, 238, 0.1);
            padding: 12px;
            border-radius: 10px;
        }

        /* Alerts */
        .alert {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            border-left: 4px solid;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-icon {
            font-size: 1.5rem;
            margin-top: 2px;
        }

        .alert-success {
            border-left-color: var(--success);
            background: linear-gradient(135deg, rgba(76, 201, 240, 0.05), rgba(76, 201, 240, 0.1));
        }

        .alert-success .alert-icon { color: var(--success); }

        .alert-info {
            border-left-color: var(--primary);
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(67, 97, 238, 0.1));
        }

        .alert-info .alert-icon { color: var(--primary); }

        .alert-warning {
            border-left-color: var(--warning);
            background: linear-gradient(135deg, rgba(248, 150, 30, 0.05), rgba(248, 150, 30, 0.1));
        }

        .alert-warning .alert-icon { color: var(--warning); }

        .alert-danger {
            border-left-color: var(--danger);
            background: linear-gradient(135deg, rgba(247, 37, 133, 0.05), rgba(247, 37, 133, 0.1));
        }

        .alert-danger .alert-icon { color: var(--danger); }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
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
            padding: 14px 28px;
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
            gap: 10px;
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

        .btn-accent {
            background: linear-gradient(135deg, var(--accent), #d63384);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
        }

        .btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(247, 37, 133, 0.4);
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

        .btn-disabled {
            background: #e9ecef;
            color: #adb5bd;
            cursor: not-allowed;
        }

        /* Candidate Cards */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .candidate-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 2px solid transparent;
            transition: var(--transition);
            cursor: pointer;
        }

        .candidate-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .candidate-card.selected {
            border-color: var(--primary);
            background: rgba(67, 97, 238, 0.03);
        }

        .candidate-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .candidate-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
        }

        .candidate-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: var(--dark);
        }

        .candidate-party {
            font-size: 0.9rem;
            color: var(--gray);
            margin-top: 5px;
        }

        .candidate-badge {
            display: inline-block;
            padding: 5px 12px;
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 10px;
        }

        /* Voting Wizard */
        .voting-wizard {
            position: relative;
        }

        .wizard-progress {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            position: relative;
        }

        .wizard-progress::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 4px;
            background: #e9ecef;
            z-index: 1;
        }

        .progress-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }

        .step-circle {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: white;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
            color: var(--gray);
            transition: var(--transition);
        }

        .step-circle.active {
            border-color: var(--primary);
            background: var(--primary);
            color: white;
            transform: scale(1.1);
        }

        .step-circle.completed {
            border-color: var(--success);
            background: var(--success);
            color: white;
        }

        .step-label {
            font-size: 0.9rem;
            color: var(--gray);
            transition: var(--transition);
        }

        .step-circle.active + .step-label {
            color: var(--primary);
            font-weight: 600;
        }

        .position-step {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .position-step.active {
            display: block;
        }

        /* Navigation Controls */
        .wizard-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid rgba(0,0,0,0.05);
        }

        /* Activity Timeline */
        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary), transparent);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-left: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -29px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            border: 3px solid white;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
        }

        .timeline-content {
            background: rgba(67, 97, 238, 0.03);
            padding: 15px;
            border-radius: 10px;
            border-left: 3px solid var(--primary);
        }

        .timeline-time {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 5px;
        }

        /* Upcoming Elections */
        .elections-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .election-card {
            background: linear-gradient(135deg, rgba(67, 97, 238, 0.05), rgba(114, 9, 183, 0.05));
            border-radius: var(--radius);
            padding: 20px;
            border: 1px solid rgba(67, 97, 238, 0.1);
            transition: var(--transition);
        }

        .election-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .main-container {
                padding: 0 15px;
                margin-top: 130px;
            }

            .candidates-grid {
                grid-template-columns: 1fr;
            }

            .wizard-progress {
                flex-wrap: wrap;
                gap: 10px;
            }

            .progress-step {
                flex: none;
                width: calc(50% - 5px);
            }
        }

        /* Animations */
        .animate-pop {
            animation: pop 0.3s ease;
        }

        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
            <span class="logo-text">Voter Dashboard</span>
        </div>
        
        <div class="user-nav">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($voter['name'], 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600; color: var(--dark);"><?= htmlspecialchars($voter['name']) ?></div>
                    <div style="font-size: 0.85rem; color: var(--gray);">Voter ID: <?= $voter_id ?></div>
                </div>
            </div>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</header>

<!-- Main Dashboard -->
<div class="main-container">
    
    <!-- Sidebar -->
    <aside class="sidebar">
        <!-- Profile Card -->
        <div class="profile-card">
            <div class="profile-avatar">
                <?php if (!empty($voter['photo'])): ?>
                    <img src="<?= htmlspecialchars($voter['photo']) ?>" class="profile-img" alt="Profile">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h3 class="profile-name"><?= htmlspecialchars($voter['name']) ?></h3>
            <div class="profile-details">
                <div class="detail-item">
                    <i class="fas fa-university"></i>
                    <span><?= htmlspecialchars($voter['faculty']) ?></span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span><?= htmlspecialchars($voter['class']) ?></span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-envelope"></i>
                    <span><?= htmlspecialchars($voter['email']) ?></span>
                </div>
                <div class="detail-item">
                    <i class="fas fa-phone"></i>
                    <span><?= htmlspecialchars($voter['mobile']) ?></span>
                </div>
            </div>
            <div class="status-badge <?= ($voter['status'] == 'Active') ? '' : 'status-blocked' ?>">
                <i class="fas fa-circle"></i>
                <?= htmlspecialchars($voter['status']) ?>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon votes">
                    <i class="fas fa-vote-yea"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $voted_count ?></h3>
                    <p>Positions Voted</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon nomination">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stat-info">
                    <h3><?= $has_requested ? '1' : '0' ?></h3>
                    <p>Nomination Request</p>
                </div>
            </div>
        </div>

        <!-- Voting Progress -->
        <div class="progress-section">
            <div class="progress-header">
                <div style="font-weight: 600; color: var(--dark);">
                    <i class="fas fa-tasks"></i> Voting Progress
                </div>
                <div style="font-weight: 600; color: var(--primary);">
                    <?= $voting_progress ?>%
                </div>
            </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; color: var(--gray);">
                <span><?= $voted_count ?> of <?= $total_positions ?> positions</span>
                <span><?= $total_positions - $voted_count ?> remaining</span>
            </div>
        </div>

        <!-- Recent Activity -->
        <?php if ($activities && $activities->num_rows > 0): ?>
        <div class="progress-section">
            <h4 style="margin-bottom: 20px; color: var(--dark); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-history"></i> Recent Activity
            </h4>
            <div class="timeline">
                <?php while($activity = $activities->fetch_assoc()): ?>
                <div class="timeline-item">
                    <div class="timeline-content">
                        <div>
                            <strong>Voted for <?= htmlspecialchars($activity['candidate_name']) ?></strong>
                            <div style="color: var(--gray); font-size: 0.9rem; margin-top: 5px;">
                                Position: <?= htmlspecialchars($activity['position']) ?>
                            </div>
                        </div>
                        <div class="timeline-time">
                            <i class="far fa-clock"></i>
                            <?= date('M d, h:i A', strtotime($activity['timestamp'])) ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Upcoming Elections -->
        <?php if ($upcoming_elections && $upcoming_elections->num_rows > 0): ?>
        <div class="progress-section">
            <h4 style="margin-bottom: 20px; color: var(--dark); display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-calendar-alt"></i> Upcoming Elections
            </h4>
            <div class="elections-grid">
                <?php while($election = $upcoming_elections->fetch_assoc()): ?>
                <div class="election-card">
                    <div style="font-weight: 600; color: var(--primary); margin-bottom: 10px;">
                        <i class="fas fa-vote-yea"></i> Election
                    </div>
                    <div style="font-size: 0.9rem; color: var(--gray);">
                        <div><i class="far fa-clock"></i> Starts: <?= date('M d, h:i A', strtotime($election['start_time'])) ?></div>
                        <div><i class="fas fa-clock"></i> Ends: <?= date('M d, h:i A', strtotime($election['end_time'])) ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        
        <!-- Nomination Section -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-user-tie"></i>
                    <span>Candidate Nomination</span>
                </div>
                <div class="status-badge <?= $can_request ? '' : 'status-blocked' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-<?= $can_request ? 'check-circle' : 'clock' ?>"></i>
                    <?= $can_request ? 'Open' : 'Closed' ?>
                </div>
            </div>

            <?php if (($voter['status'] ?? '') != 'Active'): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-ban alert-icon"></i>
                    <div>
                        <strong>Account Blocked</strong><br>
                        Your account is currently blocked. You cannot submit nomination requests.
                    </div>
                </div>
            <?php elseif ($has_requested): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle alert-icon"></i>
                    <div>
                        <strong>Request Submitted</strong><br>
                        Your candidate request is <span style="text-transform: lowercase;"><?= $request_status ?></span>.
                        <?php if($request_status == 'pending'): ?>
                            <br><small>We'll review your request soon.</small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (!$can_request): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-clock alert-icon"></i>
                    <div>
                        <strong>Nomination Period Closed</strong><br>
                        <?php if($nomination_session): ?>
                            Next session starts on <?= date('F j, Y', strtotime($nomination_session['start_time'])) ?>
                        <?php else: ?>
                            No upcoming nomination sessions scheduled.
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle alert-icon"></i>
                    <div>
                        <strong>Nomination Period Open</strong><br>
                        Submit your request to become a candidate for the upcoming election.
                    </div>
                </div>

                <form action="request_candidate.php" method="post" onsubmit="return validateNomination()">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark);">Candidate Name</label>
                        <input type="text" name="candidate_name" class="form-control" 
                               value="<?= htmlspecialchars($voter['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark);">Position</label>
                        <select name="position" class="form-control" required>
                            <option value="">Select Position</option>
                            <?php foreach($positions as $pos): ?>
                                <option value="<?= htmlspecialchars($pos['position']) ?>">
                                    <?= htmlspecialchars($pos['position']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--dark);">Your Vision (Optional)</label>
                        <textarea name="vision" class="form-control" rows="3" 
                                  placeholder="Briefly describe your vision for this position..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Submit Nomination Request
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Voting Section -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-box-open"></i>
                    <span>Cast Your Vote</span>
                </div>
                <div class="status-badge <?= $can_vote ? '' : 'status-blocked' ?>" style="font-size: 0.8rem;">
                    <i class="fas fa-<?= $can_vote ? 'play-circle' : 'pause-circle' ?>"></i>
                    <?= $can_vote ? 'Active Now' : 'Inactive' ?>
                </div>
            </div>

            <?php if (!$can_vote || !$vote_session): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle alert-icon"></i>
                    <div>
                        <strong>Voting Session Inactive</strong><br>
                        <?php if($vote_session): ?>
                            Next voting session starts on <?= date('F j, Y', strtotime($vote_session['start_time'])) ?>
                            at <?= date('h:i A', strtotime($vote_session['start_time'])) ?>
                        <?php else: ?>
                            No voting session scheduled at the moment.
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif(empty($positions)): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                    <div>
                        <strong>No Positions Available</strong><br>
                        There are no positions to vote for at this time.
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle alert-icon"></i>
                    <div>
                        <strong>Voting Session Active</strong><br>
                        Cast your vote for each position below. You can only vote once per position.
                    </div>
                </div>

                <!-- Voting Wizard -->
                <div class="voting-wizard">
                    <!-- Progress Steps -->
                    <div class="wizard-progress">
                        <?php foreach ($positions as $index => $pos): ?>
                        <div class="progress-step">
                            <div class="step-circle <?= $index == 0 ? 'active' : ($index < $voted_count ? 'completed' : '') ?>" 
                                 data-step="<?= $index ?>">
                                <?= $index < $voted_count ? '<i class="fas fa-check"></i>' : ($index + 1) ?>
                            </div>
                            <div class="step-label"><?= htmlspecialchars($pos['position']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <form id="votingForm" action="vote.php" method="post">
                        <?php foreach ($positions as $index => $pos):
                            $pos_name = $pos['position'];
                            $is_voted = in_array($pos_name, $voted_positions);
                        ?>
                        <div class="position-step <?= $index == 0 ? 'active' : '' ?>" data-step="<?= $index ?>">
                            <h3 style="margin-bottom: 20px; color: var(--dark);">
                                Position: <span style="color: var(--primary);"><?= htmlspecialchars($pos_name) ?></span>
                                <?php if($is_voted): ?>
                                    <span class="candidate-badge" style="margin-left: 10px;">
                                        <i class="fas fa-check"></i> Already Voted
                                    </span>
                                <?php endif; ?>
                            </h3>
                            
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM candidates WHERE position=?");
                            $stmt->bind_param("s", $pos_name);
                            $stmt->execute();
                            $candidates = $stmt->get_result();
                            
                            if($candidates->num_rows > 0): ?>
                                <div class="candidates-grid">
                                    <?php while ($c = $candidates->fetch_assoc()): 
                                        $checked = (isset($voted_candidates[$pos_name]) && $voted_candidates[$pos_name] == $c['id']);
                                        $avatar_bg = '#' . substr(md5($c['name']), 0, 6);
                                    ?>
                                    <label class="candidate-card <?= $checked ? 'selected' : '' ?>">
                                        <input type="radio" name="vote_<?= htmlspecialchars($pos_name) ?>"
                                               value="<?= $c['id'] ?>" 
                                               <?= $is_voted ? 'disabled' : '' ?>
                                               <?= $checked ? 'checked' : '' ?>
                                               style="display: none;">
                                        <div class="candidate-header">
                                            <?php if(!empty($c['photo'])): ?>
                                                <img src="uploads/<?= htmlspecialchars($c['photo']) ?>" 
                                                     alt="<?= htmlspecialchars($c['name']) ?>"
                                                     class="candidate-avatar"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <?php endif; ?>
                                            <div class="candidate-avatar" style="<?= !empty($c['photo']) ? 'display:none;' : '' ?> background: <?= $avatar_bg ?>; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem;">
                                                <?= strtoupper(substr($c['name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="candidate-name"><?= htmlspecialchars($c['name']) ?></div>
                                                <div class="candidate-party"><?= htmlspecialchars($c['party']) ?></div>
                                            </div>
                                        </div>
                                        <?php if(!empty($c['faculty']) || !empty($c['class'])): ?>
                                        <div style="margin-top: 10px; font-size: 0.9rem; color: var(--gray);">
                                            <?php if(!empty($c['faculty'])): ?>
                                                <div><i class="fas fa-university"></i> <?= htmlspecialchars($c['faculty']) ?></div>
                                            <?php endif; ?>
                                            <?php if(!empty($c['class'])): ?>
                                                <div><i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($c['class']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if($checked): ?>
                                        <div class="candidate-badge" style="background: rgba(76, 201, 240, 0.1); color: var(--success); margin-top: 15px;">
                                            <i class="fas fa-check-circle"></i> Your Choice
                                        </div>
                                        <?php endif; ?>
                                    </label>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-user-slash alert-icon"></i>
                                    <div>No candidates available for this position.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <!-- Navigation Controls -->
                        <div class="wizard-nav">
                            <button type="button" id="prevBtn" class="btn btn-disabled" onclick="prevStep()" disabled>
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                            <div>
                                <button type="button" id="nextBtn" class="btn btn-primary" onclick="nextStep()">
                                    Next <i class="fas fa-arrow-right"></i>
                                </button>
                                <button type="submit" id="submitBtn" class="btn btn-success" style="display:none;">
                                    <i class="fas fa-paper-plane"></i> Submit All Votes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="fas fa-bolt"></i>
                    <span>Quick Actions</span>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="noticeboard.php" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                    <i class="fas fa-bullhorn"></i> View Notice Board
                </a>
                <a href="firstpage.php" class="btn" style="background: #6c757d; color: white; text-align: center; text-decoration: none;">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="help.html" class="btn" style="background: var(--warning); color: white; text-align: center; text-decoration: none;">
                    <i class="fas fa-question-circle"></i> Help & Support
                </a>
                <button class="btn" style="background: var(--accent); color: white;" onclick="showVotingRules()">
                    <i class="fas fa-info-circle"></i> Voting Rules
                </button>
            </div>
        </div>
    </main>
</div>

<!-- Voting Rules Modal -->
<div id="votingRulesModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 15px; padding: 30px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: var(--primary);"><i class="fas fa-gavel"></i> Voting Rules</h3>
            <button onclick="hideVotingRules()" style="background: none; border: none; font-size: 1.5rem; color: var(--gray); cursor: pointer;">×</button>
        </div>
        <div style="color: var(--gray);">
            <p><strong>1. One Vote per Position:</strong> You can vote only once for each position.</p>
            <p><strong>2. No Changes:</strong> Once submitted, votes cannot be changed.</p>
            <p><strong>3. Time Limit:</strong> Votes must be cast within the voting session timeframe.</p>
            <p><strong>4. Account Status:</strong> Only active accounts can vote.</p>
            <p><strong>5. Fair Elections:</strong> Any attempt to manipulate votes will result in disqualification.</p>
        </div>
        <button onclick="hideVotingRules()" class="btn btn-primary" style="margin-top: 20px; width: 100%;">I Understand</button>
    </div>
</div>

<script>
// Voting Wizard Logic
let currentStep = 0;
const steps = document.querySelectorAll('.position-step');
const stepCircles = document.querySelectorAll('.step-circle');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const submitBtn = document.getElementById('submitBtn');

function updateStepIndicators() {
    stepCircles.forEach((circle, index) => {
        circle.classList.remove('active', 'completed');
        if (index === currentStep) {
            circle.classList.add('active');
        } else if (index < currentStep) {
            circle.classList.add('completed');
            circle.innerHTML = '<i class="fas fa-check"></i>';
        } else {
            circle.innerHTML = index + 1;
        }
    });
}

function showStep(n) {
    if (n < 0 || n >= steps.length) return;
    
    steps.forEach((step, index) => {
        step.classList.remove('active');
        if (index === n) {
            step.classList.add('active');
            step.style.display = 'block';
        } else {
            step.style.display = 'none';
        }
    });
    
    currentStep = n;
    updateStepIndicators();
    
    // Update buttons
    if (prevBtn) {
        prevBtn.disabled = n === 0;
        prevBtn.className = n === 0 ? 'btn btn-disabled' : 'btn btn-primary';
    }
    
    if (nextBtn && submitBtn) {
        if (n === steps.length - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-flex';
        } else {
            nextBtn.style.display = 'inline-flex';
            submitBtn.style.display = 'none';
        }
    }
}

function nextStep() {
    const currentStepElement = steps[currentStep];
    const radioButtons = currentStepElement.querySelectorAll('input[type="radio"]');
    const isVoted = currentStepElement.querySelector('.candidate-badge')?.textContent.includes('Already Voted');
    
    // Check if a vote is selected (only if not already voted)
    if (!isVoted) {
        let hasSelection = false;
        radioButtons.forEach(radio => {
            if (radio.checked) hasSelection = true;
        });
        
        if (!hasSelection && radioButtons.length > 0) {
            alert('Please select a candidate before proceeding.');
            return;
        }
    }
    
    if (currentStep < steps.length - 1) {
        showStep(currentStep + 1);
    }
}

function prevStep() {
    if (currentStep > 0) {
        showStep(currentStep - 1);
    }
}

// Initialize
showStep(currentStep);

// Add click handlers to candidate cards
document.querySelectorAll('.candidate-card').forEach(card => {
    card.addEventListener('click', function(e) {
        if (e.target.type === 'radio') return;
        
        const radio = this.querySelector('input[type="radio"]');
        if (radio && !radio.disabled) {
            radio.checked = true;
            
            // Update UI
            document.querySelectorAll('.candidate-card').forEach(c => {
                c.classList.remove('selected');
            });
            this.classList.add('selected');
            
            // Add animation
            this.classList.add('animate-pop');
            setTimeout(() => this.classList.remove('animate-pop'), 300);
        }
    });
});

// Add step click handlers
stepCircles.forEach((circle, index) => {
    circle.addEventListener('click', () => {
        if (index <= currentStep) {
            showStep(index);
        }
    });
});

// Form submission confirmation
document.getElementById('votingForm')?.addEventListener('submit', function(e) {
    const allVoted = <?= $voted_count ?> === <?= $total_positions ?>;
    if (!allVoted) {
        const confirmed = confirm('Are you sure you want to submit your votes? You cannot change them later.');
        if (!confirmed) {
            e.preventDefault();
        }
    }
});

// Validate nomination form
function validateNomination() {
    const position = document.querySelector('select[name="position"]');
    if (!position.value) {
        alert('Please select a position.');
        return false;
    }
    return true;
}

// Voting Rules Modal
function showVotingRules() {
    document.getElementById('votingRulesModal').style.display = 'flex';
}

function hideVotingRules() {
    document.getElementById('votingRulesModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('votingRulesModal').addEventListener('click', function(e) {
    if (e.target === this) hideVotingRules();
});

// Auto-refresh page every 5 minutes to check for new sessions
setTimeout(() => {
    console.log('Refreshing dashboard for session updates...');
    location.reload();
}, 300000); // 5 minutes

// Progress bar animation
const progressFill = document.querySelector('.progress-fill');
if (progressFill) {
    setTimeout(() => {
        progressFill.style.transition = 'width 1.5s ease-in-out';
    }, 500);
}
</script>

</body>
</html>s