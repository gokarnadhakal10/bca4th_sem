<?php
require "config.php";
require "auth.php";
admin_required();

// ===== DASHBOARD COUNTS =====
$totalVoters = $conn->query(
    "SELECT COUNT(*) AS total FROM users WHERE role='Voter'"
)->fetch_assoc()['total'];

$activeVoters = $conn->query(
    "SELECT COUNT(*) AS total FROM users 
     WHERE role='Voter' AND status='Active'"
)->fetch_assoc()['total'];

$totalCandidates = $conn->query(
    "SELECT COUNT(*) AS total FROM candidates"
)->fetch_assoc()['total'];

$totalVotes = $conn->query(
    "SELECT COUNT(*) AS total FROM votes"
)->fetch_assoc()['total'];

// Fetch voting session
$vote_session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();

// Fetch candidate nomination session
$nomination_session = $conn->query("SELECT * FROM nomination_session WHERE id=1")->fetch_assoc();

// Fetch candidates - Updated to match your table structure
$candidates = $conn->query("SELECT * FROM candidates ORDER BY id DESC");

// Fetch pending candidate requests
$requests = $conn->query("SELECT r.*, u.name AS voter_name 
                          FROM candidate_requests r 
                          JOIN users u ON r.voter_id=u.id 
                          WHERE r.status='pending'");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Online Voting System</title>
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

    .welcome-text h2 { color: var(--dark); font-size: 24px; }
    .welcome-text p { color: #666; font-size: 14px; }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.3s;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .stat-card:hover { transform: translateY(-5px); }

    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: white;
    }

    .stat-info h3 { font-size: 28px; color: var(--dark); margin-bottom: 5px; }
    .stat-info p { color: #666; font-size: 14px; }

    /* Dashboard Sections */
    .dashboard-section {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .section-title { font-size: 18px; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 10px; }

    /* Grid for Session Controls */
    .session-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 30px;
    }

    /* Forms */
    .control-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        align-items: end;
    }

    .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }

    /* Buttons */
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        font-size: 14px;
    }

    .btn-primary { background: var(--primary); color: white; }
    .btn-success { background: #2ecc71; color: white; }
    .btn-warning { background: #f1c40f; color: white; }
    .btn-danger { background: #e74c3c; color: white; }
    .btn-info { background: #3498db; color: white; }
    .btn-purple { background: #9b59b6; color: white; }
    
    .btn:hover { opacity: 0.9; transform: translateY(-2px); }

    /* Tables */
    .table-responsive { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; color: #555; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
    td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
    
    .candidate-img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    /* Quick Actions Grid */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .action-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .action-card:hover {
        background: white;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        transform: translateY(-3px);
    }

    .action-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        margin-bottom: 5px;
    }

    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .main-content { margin-left: 0; }
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Modal Styles */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 1000; 
        left: 0;
        top: 0;
        width: 100%; 
        height: 100%; 
        background-color: rgba(0,0,0,0.5);
        align-items: center;
        justify-content: center;
    }
    .modal-content {
        background-color: #fff;
        padding: 25px;
        border-radius: 12px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        animation: slideDown 0.3s ease;
    }
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
        <li><a href="AdminDashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="voters.php"><i class="fas fa-users"></i> Voters</a></li>
        <li><a href="candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
        <li><a href="admin_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
        <li><a href="admin_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="header">
        <div class="welcome-text">
            <h2>Dashboard Overview</h2>
            <p>Welcome back, Admin! Here's what's happening today.</p>
        </div>
        <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <!-- Notification for Pending Requests -->
    <?php if($requests->num_rows > 0): ?>
    <div style="background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #ffeeba; display: flex; align-items: center; gap: 15px; animation: slideDown 0.5s ease; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
        <div style="background: #ffc107; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;"><i class="fas fa-bell"></i></div>
        <div><strong>Action Required:</strong> You have <?= $requests->num_rows ?> new candidate nomination request(s) pending approval.</div>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: #4361ee;"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3><?= $totalVoters ?></h3>
                <p>Total Voters</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #2ecc71;"><i class="fas fa-user-check"></i></div>
            <div class="stat-info">
                <h3><?= $activeVoters ?></h3>
                <p>Active Voters</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #7209b7;"><i class="fas fa-user-tie"></i></div>
            <div class="stat-info">
                <h3><?= $totalCandidates ?></h3>
                <p>Candidates</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: #f72585;"><i class="fas fa-vote-yea"></i></div>
            <div class="stat-info">
                <h3><?= $totalVotes ?></h3>
                <p>Total Votes</p>
            </div>
        </div>
    </div>

    <div class="session-grid">
        <!-- Voting Session Control -->
        <div class="dashboard-section">
            <div class="section-header">
                <div class="section-title"><i class="fas fa-clock"></i> Voting Session</div>
                <span class="btn btn-info" style="padding: 5px 10px; font-size: 12px;">
                    <?= $vote_session['status'] ?? 'Pending' ?>
                </span>
            </div>
            <form action="manage_session.php" method="post" class="control-form" style="grid-template-columns: 1fr;">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="datetime-local" name="start" class="form-control" value="<?= date('Y-m-d\TH:i',strtotime($vote_session['start_time'])) ?>" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="datetime-local" name="end" class="form-control" value="<?= date('Y-m-d\TH:i',strtotime($vote_session['end_time'])) ?>" required>
                </div>
                <div class="form-group" style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <button type="submit" name="action" value="start" class="btn btn-success" style="flex: 1;"><i class="fas fa-play"></i> Start</button>
                    <button type="submit" name="action" value="pause" class="btn btn-warning" style="flex: 1;"><i class="fas fa-pause"></i> Pause</button>
                    <button type="submit" name="action" value="resume" class="btn btn-info" style="flex: 1;"><i class="fas fa-redo"></i> Resume</button>
                    <button type="submit" name="action" value="end" class="btn btn-danger" style="flex: 1;"><i class="fas fa-stop"></i> End</button>
                </div>
            </form>
        </div>

        <!-- Nomination Session -->
        <div class="dashboard-section">
            <div class="section-header">
                <div class="section-title"><i class="fas fa-calendar-alt"></i> Nomination Period</div>
                <span class="btn btn-info" style="padding: 5px 10px; font-size: 12px;">
                    <?= $nomination_session['status'] ?? 'Pending' ?>
                </span>
            </div>
            <form action="manage_nomination.php" method="post" class="control-form" style="grid-template-columns: 1fr;">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="datetime-local" name="start" class="form-control" value="<?= date('Y-m-d\TH:i',strtotime($nomination_session['start_time'])) ?>" required>
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="datetime-local" name="end" class="form-control" value="<?= date('Y-m-d\TH:i',strtotime($nomination_session['end_time'])) ?>" required>
                </div>
                <div class="form-group" style="display: flex; gap: 5px; flex-wrap: wrap;">
                    <button type="submit" name="action" value="start" class="btn btn-success" style="flex: 1;"><i class="fas fa-play"></i> Start</button>
                    <button type="submit" name="action" value="pause" class="btn btn-warning" style="flex: 1;"><i class="fas fa-pause"></i> Pause</button>
                    <button type="submit" name="action" value="resume" class="btn btn-info" style="flex: 1;"><i class="fas fa-redo"></i> Resume</button>
                    <button type="submit" name="action" value="end" class="btn btn-danger" style="flex: 1;"><i class="fas fa-stop"></i> End</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pending Requests -->
    <?php if($requests->num_rows > 0): ?>
    <div class="dashboard-section">
        <div class="section-header">
            <div class="section-title">
                <i class="fas fa-user-clock"></i> Pending Candidate Requests
                <span style="background: #e74c3c; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; margin-left: 10px; display: flex; align-items: center; gap: 5px;"><i class="fas fa-exclamation-circle"></i> <?= $requests->num_rows ?> New</span>
            </div>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Candidate Name</th>
                        <th>Party</th>
                        <th>Position</th>
                        <th>Requested By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($r=$requests->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <?php if(!empty($r['photo'])): ?>
                                <img src="uploads/<?= htmlspecialchars($r['photo']) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($r['candidate_name']) ?></td>
                        <td><?= htmlspecialchars($r['party'] ?? '') ?></td>
                        <td><?= htmlspecialchars($r['position']) ?></td>
                        <td><?= htmlspecialchars($r['voter_name']) ?></td>
                        <td>
                            <div style="display:inline-flex; gap: 5px;">
                                <form action="approve_request.php" method="post">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <button type="submit" name="action" value="accept" class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">Accept</button>
                                </form>
                                <button type="button" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" onclick="openRejectModal(<?= $r['id'] ?>)">Reject</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Candidates Table - CORRECTED SECTION -->
    <div class="dashboard-section">
        <div class="section-header">
            <div class="section-title"><i class="fas fa-users"></i> All Candidates</div>
            <a href="add_candidates.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New</a>
        </div>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Photo</th>
                        <th>Party-Symbol</th>
                        <th>Position</th>
                        <th>Faculty</th>
                        <th>Class</th>
                        <th>Votes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($c=$candidates->fetch_assoc()):
                    $votes = $conn->query("SELECT COUNT(*) as total_votes FROM votes WHERE candidate_id={$c['id']}")->fetch_assoc()['total_votes'];
                    ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                                                <td>
                            <?php if(!empty($c['photo'])): ?>
                                <img src="uploads/<?= $c['photo'] ?>" class="candidate-img" alt="Photo">
                            <?php else: ?>
                                <div class="candidate-img" style="background: #ddd; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php if(!empty($c['party_image'])): ?>
                                    <img src="uploads/<?= htmlspecialchars($c['party_image']) ?>" style="width: 30px; height: 30px; object-fit: contain; border-radius: 4px;">
                                <?php endif; ?>
                                <?= htmlspecialchars($c['party_name'] ?? '') ?>
                            </div>
                        </td>
                        <td><span style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?= htmlspecialchars($c['position']) ?></span></td>
                        <td><?= htmlspecialchars($c['faculty']) ?></td>
                        <td><?= htmlspecialchars($c['class']) ?></td>
                        <td><strong><?= $votes ?></strong></td>
                        <td>
                            <a href="edit_candidates.php?id=<?= $c['id'] ?>" class="btn btn-info" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-edit"></i></a>
                            <a href="delete_candidate.php?id=<?= $c['id'] ?>" onclick="return confirm('Are you sure you want to delete this candidate?')" class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="dashboard-section">
        <div class="section-header">
            <div class="section-title"><i class="fas fa-bolt"></i> Quick Actions</div>
        </div>
        <div class="quick-actions">
            <div class="action-card">
                <div class="action-icon" style="background: #4361ee;"><i class="fas fa-user-plus"></i></div>
                <h4>Add Candidate</h4>
                <a href="add_candidates.php" class="btn btn-success">Go to Add</a>
            </div>
            <div class="action-card">
                <div class="action-icon" style="background: #3f37c9;"><i class="fas fa-user-graduate"></i></div>
                <h4>Add Voter</h4>
                <a href="studentRegistration.html" class="btn btn-info">Register Voter</a>
            </div>
            <div class="action-card">
                <div class="action-icon" style="background: #7209b7;"><i class="fas fa-bullhorn"></i></div>
                <h4>Manage Notices</h4>
                <a href="admin_notices.php?tab=manage" class="btn btn-purple">View Notices</a>
            </div>
            <div class="action-card">
                <div class="action-icon" style="background: #f72585;"><i class="fas fa-poll"></i></div>
                <h4>Results Control</h4>
                <a href="admin_notices.php?tab=results" class="btn btn-warning">Publish Results</a>
            </div>
            <div class="action-card">
                <div class="action-icon" style="background: #4cc9f0;"><i class="fas fa-image"></i></div>
                <h4>Hero Image</h4>
                <a href="hero_upload.php" class="btn btn-danger">Update Hero</a>
            </div>
        </div>
    </div>
</div>

<!-- Reject Reason Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h3 style="margin-bottom: 15px; color: var(--dark);">Reject Candidate Request</h3>
        <form action="approve_request.php" method="post">
            <input type="hidden" name="id" id="reject_request_id">
            <input type="hidden" name="action" value="reject">
            
            <div class="form-group">
                <label>Reason for Rejection</label>
                <textarea name="reason" class="form-control" rows="4" required placeholder="Please explain why this request is being rejected..."></textarea>
            </div>
            
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-info" style="background: #95a5a6;" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-danger">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(id) {
        document.getElementById('reject_request_id').value = id;
        document.getElementById('rejectModal').style.display = 'flex';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('rejectModal')) {
            document.getElementById('rejectModal').style.display = 'none';
        }
    }
</script>

</body>
</html>