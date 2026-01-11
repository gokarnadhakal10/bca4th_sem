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

if (!$voter) {
    echo "<p style='color:red;'>User info not found.</p>";
    exit;
}

// Fetch voting session
$vote_session_result = $conn->query("SELECT * FROM voting_session WHERE id=1");
$vote_session = $vote_session_result ? $vote_session_result->fetch_assoc() : null;

// Fetch nomination session
$nomination_session_result = $conn->query("SELECT * FROM nomination_session WHERE id=1");
$nomination_session = $nomination_session_result ? $nomination_session_result->fetch_assoc() : null;

// Check if user has already requested nomination
$my_request = null;
$stmt = $conn->prepare("SELECT * FROM candidate_requests WHERE voter_id=? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$my_request = $stmt->get_result()->fetch_assoc();

// ===== VISIBILITY LOGIC =====
$current_time = date('Y-m-d H:i:s');

// Check Nomination Status
$is_nomination_active = false;
if ($nomination_session) {
    $nomination_status = $nomination_session['status'] ?? '';
    $is_nomination_active = (strcasecmp($nomination_status, 'Active') == 0) &&
                            ($current_time >= ($nomination_session['start_time'] ?? '')) &&
                            ($current_time <= ($nomination_session['end_time'] ?? ''));
}

// Check Voting Status
$is_voting_active = false;
if ($vote_session) {
    $voting_status = $vote_session['status'] ?? '';
    $is_voting_active = (strcasecmp($voting_status, 'Active') == 0) &&
                        ($current_time >= ($vote_session['start_time'] ?? '')) &&
                        ($current_time <= ($vote_session['end_time'] ?? ''));
}

// Determine what to show
$show_nomination = $is_nomination_active;
$show_voting = $is_voting_active;

// Enforce Sequence: If voting session has ever started or is active, hide nomination
if ($vote_session && in_array($vote_session['status'] ?? '', ['Active', 'Paused'])) {
    $show_nomination = false;
}

// Fetch all positions
$positions_result = $conn->query("SELECT DISTINCT position FROM candidates");
$positions = $positions_result ? $positions_result->fetch_all(MYSQLI_ASSOC) : [];

// Fetch votes cast by this voter - CORRECTED TO MATCH YOUR TABLE STRUCTURE
$stmt = $conn->prepare("
    SELECT v.position, v.candidate_id, c.name as candidate_name, c.party_name, c.photo 
    FROM votes v 
    LEFT JOIN candidates c ON v.candidate_id = c.id 
    WHERE v.voter_id = ?
");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$res = $stmt->get_result();

$voted_positions = [];
$voted_candidates = [];
$vote_details = [];
while ($row = $res->fetch_assoc()) {
    $voted_positions[] = $row['position'];
    $voted_candidates[$row['position']] = $row['candidate_id'];
    $vote_details[] = $row;
}

// Calculate voting progress
$total_positions = count($positions);
$voted_count = count($voted_candidates);
$voting_progress = $total_positions > 0 ? round(($voted_count / $total_positions) * 100) : 0;

// Check if all votes are completed
$all_votes_completed = ($voted_count == $total_positions && $total_positions > 0);

// Get current position to show (first unvoted position)
$current_position_index = 0;
foreach ($positions as $index => $pos) {
    if (!in_array($pos['position'], $voted_positions)) {
        $current_position_index = $index;
        break;
    }
}

// Allow navigation via GET parameter
if (isset($_GET['position']) && is_numeric($_GET['position'])) {
    $req_pos = intval($_GET['position']);
    if ($req_pos >= 0 && $req_pos < $total_positions) {
        $current_position_index = $req_pos;
    }
}

// Handle AJAX vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_position'])) {
    if (!$is_voting_active) {
        echo json_encode(['success' => false, 'message' => 'Voting session is not active']);
        exit;
    }

    $position = $_POST['vote_position'] ?? '';
    $candidate_id = $_POST['candidate_id'] ?? '';
    
    // Validate inputs
    if (empty($position) || empty($candidate_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid vote data']);
        exit;
    }
    
    // Check if already voted for this position
    $check_stmt = $conn->prepare("SELECT id FROM votes WHERE voter_id=? AND position=?");
    $check_stmt->bind_param("is", $voter_id, $position);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        // Update existing vote
        $update_stmt = $conn->prepare("UPDATE votes SET candidate_id=? WHERE voter_id=? AND position=?");
        $update_stmt->bind_param("iis", $candidate_id, $voter_id, $position);
        $update_stmt->execute();
    } else {
        // Insert new vote
        $insert_stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id, position, voted_at) VALUES (?, ?, ?, NOW())");
        $insert_stmt->bind_param("iis", $voter_id, $candidate_id, $position);
        $insert_stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Vote recorded successfully']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting System | Dashboard</title>
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
            --warning: #f39c12;
            --header-bg: rgba(255, 255, 255, 0.98);
        }

        
        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 2000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 30px;
            border: 1px solid #888;
            width: 90%;
            max-width: 450px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: modalFadeIn 0.3s;
        }

        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .btn-confirm {
            background: var(--success);
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn-confirm:hover { background: #219653; }

        .btn-cancel {
            background: #e74c3c;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .btn-cancel:hover { background: #c0392b; }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid var(--success);
        }
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid var(--accent);
        }
        .alert.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid var(--warning);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: var(--text-dark);
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

        /* Main Layout */
        .main-container {
            max-width: 1400px;
            margin: 100px auto 20px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 20px;
        }

        /* Sidebar */
        .sidebar {
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 20px;
            height: fit-content;
        }

        .profile-section {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 600;
            overflow: hidden;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-dark);
        }

        .profile-details {
            text-align: left;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            color: var(--text-light);
            font-size: 14px;
        }

        .detail-item i {
            width: 20px;
            color: var(--secondary);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: #e8f5e9;
            color: var(--success);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 10px;
        }

        /* Navigation */
        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: var(--text-dark);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: #f0f2f5;
            color: var(--primary);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-link i {
            width: 20px;
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Voting Session Info */
        .session-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 10px;
        }

        .session-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .session-dates {
            display: flex;
            gap: 30px;
            color: var(--text-light);
            font-size: 14px;
        }

        .date-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .date-label {
            font-weight: 500;
            color: var(--text-dark);
        }
        
        .phase-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .phase-icon {
            font-size: 24px;
            background: rgba(255,255,255,0.2);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Voting Section */
        .voting-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            min-height: 400px;
            display: flex;
            flex-direction: column;
        }

        .position-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }

        .position-title {
            font-size: 28px;
            color: #333;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .position-progress {
            font-size: 14px;
            color: var(--text-light);
        }

        .position-progress .current {
            color: var(--primary);
            font-weight: 600;
        }

        .position-progress .total {
            color: #333;
            color: var(--text-dark);
            font-weight: 600;
        }

        /* Candidates Grid */
        .candidates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            flex-grow: 1;
        }

        .candidate-card {
            background: white;
            border: 2px solid var(--border);
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 280px;
        }

        .candidate-card:hover {
            border-color: var(--primary);
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .candidate-card.selected {
            border-color: var(--success);
            background: rgba(39, 174, 96, 0.05);
        }

        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #f0f0f0;
        }

        .candidate-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            width: 100%;
        }

        .candidate-party {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 8px 0;
        }

        .candidate-class {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 5px;
        }

        .vote-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 15px;
            transition: all 0.3s;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .vote-btn:hover:not(:disabled) {
            background: #219653;
            transform: translateY(-2px);
        }

        .vote-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }

        /* Navigation Buttons */
        .voting-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .nav-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .nav-btn:hover:not(:disabled) {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .nav-btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            transform: none;
        }

        .nav-btn.prev {
            background: var(--text-light);
        }

        .nav-btn.prev:hover:not(:disabled) {
            background: #6c7a89;
        }

        .completion-btn {
            background: var(--success);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            margin: 0 auto;
        }

        .completion-btn:hover {
            background: #219653;
            transform: scale(1.05);
        }

        /* Completion Message */
        .completion-message {
            text-align: center;
            padding: 40px 20px;
            display: none;
        }

        .completion-icon {
            font-size: 64px;
            color: var(--success);
            margin-bottom: 20px;
        }

        .completion-title {
            font-size: 24px;
            color: var(--success);
            margin-bottom: 10px;
        }

        .completion-text {
            color: var(--text-light);
            margin-bottom: 30px;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Positions List */
        .positions-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }

        .position-tag {
            background: var(--light-bg);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .position-tag.voted {
            background: rgba(39, 174, 96, 0.1);
            color: var(--success);
        }

        .position-tag.current {
            background: #e3f2fd;
            color: var(--primary);
            font-weight: 600;
        }
        
        /* Nomination Form */
        .nomination-form {
            display: grid;
            gap: 15px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Welcome Section */
        .welcome-section {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .session-dates {
                flex-direction: column;
                gap: 10px;
            }
            
            .candidates-grid {
                grid-template-columns: 1fr;
            }
            
            .main-container {
                padding: 0 10px;
            }
            
            .session-info {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        
        /* Loading spinner */
        .spinner {
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
                <span class="logo-text">Voting System</span>
            </div>
            
            <nav id="mainNav">
                 <a href="userDashboard.php" class="active">Dashboard</a>
                 <a href="result.php">Result</a>
                 <a href="noticeboard.php">Notice Board</a>
                 <a href="help.html">Help</a>
                 <a href="logout.php">Logout</a>
            </nav>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
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
                <li class="nav-item">
                    <a href="result.php" class="nav-link">
                        <i class="fas fa-vote-yea"></i>
                        <span>Result</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="noticeboard.php" class="nav-link">
                        <i class="fas fa-bullhorn"></i>
                        <span>Notice Board</span>
                    </a>
                </li>
                <?php if ($show_nomination): ?>
                <li class="nav-item">
                    <a href="#nomination-section" class="nav-link">
                        <i class="fas fa-user-plus"></i>
                        <span>Apply for Required Position</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="reviewVotes(); return false;">
                        <i class="fas fa-list-check"></i>
                        <span>My Votes</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="help.html" class="nav-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Help</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            
            <!-- Welcome Banner -->
            <div class="welcome-section">
                <div>
                    <h2 style="color: var(--primary); margin-bottom: 5px;">Hello, <?= htmlspecialchars($voter['name']) ?>! 👋</h2>
                    <p style="color: var(--text-light);">Welcome to your voting dashboard. Your voice matters.</p>
                </div>
                <div style="font-size: 14px; color: var(--text-light); background: var(--light-bg); padding: 8px 15px; border-radius: 20px;"><i class="far fa-calendar-alt"></i> <?= date('l, F j, Y') ?></div>
            </div>
            
            <!-- Alerts -->
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Phase Banner -->
            <?php if ($show_nomination && $nomination_session): ?>
                <div class="phase-banner">
                    <div class="phase-icon"><i class="fas fa-user-plus"></i></div>
                    <div>
                        <h3 style="margin:0;">Nomination Phase Active</h3>
                        <p style="margin:0; opacity:0.9; font-size:14px;">You can request to be a candidate until <?= date('M d, h:i A', strtotime($nomination_session['end_time'])) ?></p>
                    </div>
                </div>
            <?php elseif ($show_voting && $vote_session): ?>
                <div class="phase-banner" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);">
                    <div class="phase-icon"><i class="fas fa-vote-yea"></i></div>
                    <div>
                        <h3 style="margin:0;">Voting Phase Active</h3>
                        <p style="margin:0; opacity:0.9; font-size:14px;">Cast your votes before <?= date('M d, h:i A', strtotime($vote_session['end_time'])) ?></p>
                    </div>
                </div>
            <?php elseif ($vote_session && !$show_voting): ?>
                <div class="phase-banner" style="background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);">
                    <div class="phase-icon"><i class="fas fa-clock"></i></div>
                    <div>
                        <h3 style="margin:0;">Voting Session Inactive</h3>
                        <p style="margin:0; opacity:0.9; font-size:14px;">
                            <?php if (date('Y-m-d H:i:s') < $vote_session['start_time']): ?>
                                Voting starts on <?= date('M d, h:i A', strtotime($vote_session['start_time'])) ?>
                            <?php else: ?>
                                Voting ended on <?= date('M d, h:i A', strtotime($vote_session['end_time'])) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Voting Session Info -->
            <?php if ($vote_session && $show_voting): ?>
            <div class="session-card">
                <div class="session-info">
                    <h3>Voting Session</h3>
                    <div class="session-dates">
                        <div class="date-item">
                            <span class="date-label">Start Date:</span>
                            <span><?= date('m/d/Y', strtotime($vote_session['start_time'])) ?></span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">End Date:</span>
                            <span><?= date('m/d/Y', strtotime($vote_session['end_time'])) ?></span>
                        </div>
                    </div>
                </div>
                <div class="positions-list">
                    <?php foreach ($positions as $index => $pos): 
                        $position_name = $pos['position'] ?? '';
                        $is_voted = in_array($position_name, $voted_positions);
                        $is_current = ($index == $current_position_index);
                    ?>
                    <a href="?position=<?= $index ?>" class="position-tag <?= $is_voted ? 'voted' : '' ?> <?= $is_current ? 'current' : '' ?>" style="text-decoration: none; cursor: pointer;">
                        <?php if ($is_voted): ?>
                            <i class="fas fa-check"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($position_name) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nomination Session Info -->
            <?php if ($nomination_session && $show_nomination): ?>
            <div class="session-card">
                <div class="session-info">
                    <h3>Nomination Session</h3>
                    <div class="session-dates">
                        <div class="date-item">
                            <span class="date-label">Start Date:</span>
                            <span><?= date('M d, Y h:i A', strtotime($nomination_session['start_time'])) ?></span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">End Date:</span>
                            <span><?= date('M d, Y h:i A', strtotime($nomination_session['end_time'])) ?></span>
                        </div>
                    </div>
                </div>
                <a href="#nomination-section" class="vote-btn" style="text-decoration:none; display:inline-block; text-align:center; width:auto; background: var(--primary);">
                    <i class="fas fa-user-plus"></i> Apply for Required Position
                </a>
            </div>
            <?php endif; ?>

            <!-- Nomination Section -->
            <?php if ($show_nomination): ?>
            <div class="voting-card" id="nomination-section">
                <div class="position-header">
                    <h1 class="position-title">Candidate Nomination</h1>
                    <p style="color: var(--text-light);">Submit your request to become a candidate in the upcoming election.</p>
                </div>

                <?php if ($my_request): ?>
                    <div style="text-align: center; padding: 30px;">
                        <div style="font-size: 48px; margin-bottom: 20px; color: 
                            <?= $my_request['status'] == 'approved' ? 'var(--success)' : 
                               ($my_request['status'] == 'rejected' ? 'var(--accent)' : 'var(--warning)') ?>">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3>Request Status: <span style="text-transform: capitalize; color: 
                            <?= $my_request['status'] == 'approved' ? 'var(--success)' : 
                               ($my_request['status'] == 'rejected' ? 'var(--accent)' : 'var(--warning)') ?>">
                            <?= htmlspecialchars($my_request['status'] ?? 'pending') ?></span>
                        </h3>
                        <p style="margin-top: 10px; color: var(--text-light);">
                            Position: <strong><?= htmlspecialchars($my_request['position'] ?? '') ?></strong><br>
                            Submitted on: <?= date('M d, Y', strtotime($my_request['request_time'] ?? 'now')) ?>
                        </p>
                        <?php if($my_request['status'] == 'rejected' && !empty($my_request['rejection_reason'])): ?>
                            <div class="alert error" style="margin-top: 15px; text-align: left; display: inline-block;">
                                <strong>Reason for Rejection:</strong><br>
                                <?= nl2br(htmlspecialchars($my_request['rejection_reason'])) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form action="request_candidate.php" method="POST" class="nomination-form" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Candidate Name (You)</label>
                            <input type="text" name="candidate_name" class="form-control" value="<?= htmlspecialchars($voter['name'] ?? '') ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Position</label>
                            <select name="position" class="form-control" required>
                                <option value="">-- Select Position --</option>
                                <?php foreach($positions as $pos): ?>
                                    <option value="<?= htmlspecialchars($pos['position'] ?? '') ?>"><?= htmlspecialchars($pos['position'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Party Name</label>
                            <input type="text" name="party" class="form-control" required placeholder="Enter party name">
                        </div>

                        <div class="form-group">
                            <label>Party Symbol/Image (Optional)</label>
                            <input type="file" name="party_image" class="form-control" accept="image/*">
                        </div>

                        <div class="form-group">
                            <label>Candidate Photo</label>
                            <input type="file" name="photo" class="form-control" required accept="image/*">
                        </div>
                        
                        <div class="form-group">
                            <label>Vision / Manifesto (Optional)</label>
                            <textarea name="vision" class="form-control" placeholder="Why should students vote for you?"></textarea>
                        </div>
                        
                        <button type="submit" class="vote-btn" style="background: var(--primary);">
                            <i class="fas fa-paper-plane"></i> Submit Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Voting Card -->
            <?php if ($show_voting): ?>
            <div class="voting-card" id="voting-section">
                <?php if ($all_votes_completed): ?>
                    <!-- Completion Message -->
                    <div class="completion-message" id="completionMessage">
                        <div class="completion-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h2 class="completion-title">Voting Completed!</h2>
                        <p class="completion-text">
                            You have successfully voted for all positions. Thank you for participating in the election.
                            Your votes have been recorded and will be counted when the voting session ends.
                        </p>
                        <button class="completion-btn" onclick="reviewVotes()">
                            <i class="fas fa-eye"></i> Review Your Votes
                        </button>
                    </div>
                <?php elseif ($is_voting_active && isset($positions[$current_position_index])): 
                    $current_position = $positions[$current_position_index] ?? [];
                    $position_name = $current_position['position'] ?? '';
                    
                    if (empty($position_name)) {
                        echo '<div class="alert warning">No position found. Please contact administrator.</div>';
                    } else {
                        // Fetch candidates for current position - UPDATED TO MATCH YOUR TABLE STRUCTURE
                        $stmt = $conn->prepare("SELECT * FROM candidates WHERE position=?");
                        $stmt->bind_param("s", $position_name);
                        $stmt->execute();
                        $candidates = $stmt->get_result();
                ?>
                    <!-- Position Header -->
                    <div class="position-header">
                        <h1 class="position-title"><?= htmlspecialchars($position_name) ?></h1>
                        <div class="position-progress">
                            Position <span class="current"><?= $current_position_index + 1 ?></span> 
                            of <span class="total"><?= $total_positions ?></span>
                        </div>
                    </div>

                    <!-- Candidates Grid -->
                    <div class="candidates-grid" id="candidatesGrid">
                        <?php 
                        if ($candidates->num_rows === 0): ?>
                            <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                                <div style="font-size: 64px; color: #bdc3c7; margin-bottom: 20px;">
                                    <i class="fas fa-user-slash"></i>
                                </div>
                                <h3 style="color: var(--text-light);">No candidates for this position</h3>
                                <p style="color: var(--text-light);">No candidates have been nominated for this position yet.</p>
                            </div>
                        <?php else: 
                            while ($candidate = $candidates->fetch_assoc()): 
                                $is_selected = isset($voted_candidates[$position_name]) && $voted_candidates[$position_name] == $candidate['id'];
                        ?>
                        <div class="candidate-card <?= $is_selected ? 'selected' : '' ?>"
                             data-candidate-id="<?= $candidate['id'] ?>" data-candidate-name="<?= htmlspecialchars($candidate['name'] ?? '', ENT_QUOTES) ?>"
                             onclick="openVoteModal(this, <?= $candidate['id'] ?>)">
                            <?php if(!empty($candidate['photo'])): ?>
                                <img src="uploads/<?= $candidate['photo'] ?>" class="candidate-photo" alt="<?= htmlspecialchars($candidate['name']) ?>">
                            <?php else: ?>
                                <div class="candidate-photo" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: bold;">
                                    <?= strtoupper(substr($candidate['name'] ?? '?', 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="candidate-info">
                                <h3 style="font-size: 18px; margin-bottom: 5px; color: var(--primary);"><?= htmlspecialchars($candidate['name'] ?? '') ?></h3>
                                
                                <div class="candidate-party">
                                    <?php if(!empty($candidate['party_image'])): ?>
                                        <img src="uploads/<?= $candidate['party_image'] ?>" style="width: 20px; height: 20px; object-fit: contain; border-radius: 2px;">
                                    <?php endif; ?>
                                    <span style="font-weight: 500;"><?= htmlspecialchars($candidate['party_name'] ?? '') ?></span>
                                </div>
                                
                                <div style="font-size: 14px; color: var(--text-light);">
                                    <?= htmlspecialchars($candidate['faculty'] ?? '') ?> - <?= htmlspecialchars($candidate['class'] ?? '') ?>
                                </div>
                            </div>
                            
                            <button class="vote-btn" <?= $is_selected ? 'disabled' : '' ?>>
                                <i class="fas fa-check"></i>
                                <?= $is_selected ? 'Voted' : 'Vote' ?>
                            </button>
                        </div>
                        <?php endwhile; 
                        endif; ?>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="voting-navigation">
                        <button class="nav-btn prev" onclick="previousPosition()" <?= $current_position_index == 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        
                        <button class="nav-btn" onclick="nextPosition()" id="nextBtn">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                <?php 
                    }
                else: ?>
                    <!-- No Voting Session or No Positions -->
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 64px; color: #bdc3c7; margin-bottom: 20px;">
                            <i class="fas fa-ban"></i>
                        </div>
                        <h2 style="color: var(--text-light); margin-bottom: 10px;">
                            No Positions Available
                        </h2>
                        <p style="color: var(--text-light); max-width: 500px; margin: 0 auto;">
                            There are no positions configured for this election yet.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            <?php elseif (!$show_nomination): ?>
                <!-- Fallback when neither session is active -->
                <div class="voting-card" style="text-align: center; padding: 60px;">
                    <div style="font-size: 64px; color: #bdc3c7; margin-bottom: 20px;"><i class="fas fa-clock"></i></div>
                    <h2>No Active Session</h2>
                    <p style="color: var(--text-light);">There is currently no active nomination or voting session. Please check the notice board for updates.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Vote Confirmation Modal -->
    <div id="voteModal" class="modal">
        <div class="modal-content">
            <div style="font-size: 48px; color: var(--warning); margin-bottom: 15px;">
                <i class="fas fa-question-circle"></i>
            </div>
            <h2 style="margin-bottom: 10px; color: var(--primary);">Confirm Your Vote</h2>
            <p style="color: var(--text-dark); font-size: 16px; line-height: 1.5;">
                Are you sure you want to vote for <br>
                <strong id="modalCandidateName" style="font-size: 18px; color: var(--secondary);"></strong>?
            </p>
            <p style="color: var(--text-light); font-size: 13px; margin-top: 15px;">
                <i class="fas fa-info-circle"></i> You cannot change your vote after submission.
            </p>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeVoteModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmVote()">Yes, Vote</button>
            </div>
        </div>
    </div>

    <!-- Review Votes Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <h2 style="margin-bottom: 20px; color: var(--primary);">Your Votes</h2>
            
            <div class="review-list" style="text-align: left; max-height: 400px; overflow-y: auto;">
                <?php if (empty($vote_details)): ?>
                    <div style="text-align: center; padding: 20px; color: var(--text-light);">
                        <i class="fas fa-vote-yea" style="font-size: 32px; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>You haven't cast any votes yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vote_details as $vote): ?>
                        <div class="review-item" style="display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #eee;">
                            <div class="review-img" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; flex-shrink: 0; background: #f0f0f0;">
                                <?php if(!empty($vote['photo'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($vote['photo']) ?>" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none'">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #888; font-weight: bold;">
                                        <?= strtoupper(substr($vote['candidate_name'] ?? '?', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="review-info">
                                <div style="font-weight: 600; color: var(--primary); font-size: 16px;"><?= htmlspecialchars($vote['position'] ?? '') ?></div>
                                <div style="color: var(--text-dark); font-weight: 500;"><?= htmlspecialchars($vote['candidate_name'] ?? '') ?></div>
                                <div style="font-size: 13px; color: var(--text-light);"><?= htmlspecialchars($vote['party_name'] ?? '') ?></div>
                            </div>
                            <div style="margin-left: auto; color: var(--success);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeReviewModal()" style="background: var(--secondary);">Close</button>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div style="font-size: 48px; color: var(--primary); margin-bottom: 15px;">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2 style="margin-bottom: 10px; color: var(--primary);">Confirm Logout</h2>
            <p style="color: var(--text-dark); font-size: 16px; line-height: 1.5;">
                Are you sure you want to finish voting and logout?
            </p>
            <div class="modal-buttons">
                <button class="btn-cancel" onclick="closeLogoutModal()" style="background: #95a5a6;">Cancel</button>
                <button class="btn-confirm" onclick="confirmLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>

    <script>
        let currentPositionIndex = <?= $current_position_index ?>;
        let totalPositions = <?= $total_positions ?>;
        let votedCandidates = <?= json_encode($voted_candidates) ?>;
        let currentPosition = <?= json_encode(isset($positions[$current_position_index]) && isset($positions[$current_position_index]['position']) ? $positions[$current_position_index]['position'] : '') ?>;
        
        // Modal State
        let pendingVoteBtn = null;
        let pendingCandidateId = null;

        function openVoteModal(cardElement, candidateId) {
            const btnElement = cardElement.querySelector('.vote-btn');
            // Do nothing if already voted
            if (btnElement.disabled) {
                return;
            }
            
            pendingVoteBtn = btnElement;
            pendingCandidateId = candidateId;
            
            const candidateName = cardElement.dataset.candidateName;
            document.getElementById('modalCandidateName').textContent = candidateName;
            document.getElementById('voteModal').style.display = 'flex';
        }

        function closeVoteModal() {
            document.getElementById('voteModal').style.display = 'none';
            pendingVoteBtn = null;
            pendingCandidateId = null;
        }

        function confirmVote() {
            if (pendingVoteBtn && pendingCandidateId) {
                submitVote(pendingVoteBtn, pendingCandidateId);
            }
            closeVoteModal();
        }

        // Submit vote for current position
        function submitVote(voteBtn, candidateId) {
            if (!currentPosition) {
                alert('Error: No position selected');
                return;
            }
            
            // Show loading state
            const originalText = voteBtn.innerHTML;
            voteBtn.innerHTML = '<div class="spinner"></div> Voting...';
            voteBtn.disabled = true;
            
            // Submit vote via AJAX
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `vote_position=${encodeURIComponent(currentPosition)}&candidate_id=${candidateId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI - disable all cards for this position
                    document.querySelectorAll('.candidate-card').forEach(card => {
                        card.classList.remove('selected');
                        const btn = card.querySelector('.vote-btn');
                        if (btn) {
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-check"></i> Vote';
                        }
                        // Make all cards unclickable
                        card.onclick = null;
                        card.style.cursor = 'not-allowed';
                    });
                    
                    // Mark current card as voted
                    const currentCard = voteBtn.closest('.candidate-card');
                    if (currentCard) {
                        currentCard.classList.add('selected');
                        voteBtn.innerHTML = '<i class="fas fa-check"></i> Voted';
                        
                        // Enable next button
                        const nextBtn = document.getElementById('nextBtn');
                        if (nextBtn) nextBtn.disabled = false;
                        
                        // Update voted positions list
                        updateVotedPositions(currentPosition);
                        
                        // Update global votedCandidates
                        votedCandidates[currentPosition] = candidateId;
                    }
                } else {
                    alert(data.message || 'Error submitting vote. Please try again.');
                    voteBtn.innerHTML = originalText;
                    voteBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error submitting vote. Please try again.');
                voteBtn.innerHTML = originalText;
                voteBtn.disabled = false;
            });
        }

        // Navigate to next position
        function nextPosition() {
            if (currentPositionIndex < totalPositions - 1) {
                // Check if current position is voted
                if (!votedCandidates[currentPosition]) {
                    if (!confirm('You have not selected a candidate for this position. Are you sure you want to skip it?')) {
                        return;
                    }
                }
                
                currentPositionIndex++;
                window.location.href = `?position=${currentPositionIndex}#voting-section`;
            } else {
                // This is the last position, check if all positions are voted
                if (!votedCandidates[currentPosition]) {
                    if (!confirm('You have not selected a candidate for this position. Are you sure you want to finish voting?')) {
                        return;
                    }
                }
                checkAllVotesCompleted();
            }
        }

        // Navigate to previous position
        function previousPosition() {
            if (currentPositionIndex > 0) {
                currentPositionIndex--;
                window.location.href = `?position=${currentPositionIndex}#voting-section`;
            }
        }

        // Check if all votes are completed
        function checkAllVotesCompleted() {
            // Refresh page to check completion status
            window.location.reload();
        }

        // Update voted positions list
        function updateVotedPositions(position) {
            // Find the position tag and mark it as voted
            document.querySelectorAll('.position-tag').forEach(tag => {
                if (tag.textContent.includes(position)) {
                    tag.classList.add('voted');
                    const text = tag.textContent.replace('✓', '').trim();
                    tag.innerHTML = '<i class="fas fa-check"></i> ' + text;
                }
            });
        }

        // Review votes
        function reviewVotes() {
            document.getElementById('reviewModal').style.display = 'flex';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        // Logout Modal Functions
        function openLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = 'logout.php';
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // If all votes are completed, show completion message
            if (<?= $all_votes_completed ? 'true' : 'false' ?>) {
                const completionMessage = document.getElementById('completionMessage');
                const votingElements = document.querySelector('.voting-card');
                
                if (completionMessage && votingElements) {
                    completionMessage.style.display = 'block';
                    const childElements = votingElements.children;
                    for (let i = 0; i < childElements.length; i++) {
                        if (!childElements[i].classList.contains('completion-message')) {
                            childElements[i].style.display = 'none';
                        }
                    }
                }
            }
            
            // If current position is already voted, enable next button
            if (votedCandidates[currentPosition]) {
                const nextBtn = document.getElementById('nextBtn');
                if (nextBtn) {
                    nextBtn.disabled = false;
                }
            }
        });
    </script>
</body>
</html>