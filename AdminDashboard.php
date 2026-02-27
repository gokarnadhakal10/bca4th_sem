<?php
require "config.php";
require "auth.php";
admin_required();

// ===== DASHBOARD COUNTS =====
$totalVoters = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='Voter'")->fetch_assoc()['total'];
$activeVoters = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='Voter' AND status='Active'")->fetch_assoc()['total'];
$totalCandidates = $conn->query("SELECT COUNT(*) AS total FROM candidates")->fetch_assoc()['total'];
$totalVotes = $conn->query("SELECT COUNT(*) AS total FROM votes")->fetch_assoc()['total'];

// Voting session
$vote_session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc() ?? [
    'start_time' => date('Y-m-d H:i:s'),
    'end_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'status' => 'Pending'
];

// Nomination session
$nomination_session = $conn->query("SELECT * FROM nomination_session WHERE id=1")->fetch_assoc() ?? [
    'start_time' => date('Y-m-d H:i:s'),
    'end_time' => date('Y-m-d H:i:s', strtotime('+1 day')),
    'status' => 'Pending'
];

// Candidates
$candidates = $conn->query("SELECT * FROM candidates ORDER BY id DESC");

// Pending requests
$requests = $conn->query("SELECT r.*, u.name AS voter_name FROM candidate_requests r JOIN users u ON r.voter_id=u.id WHERE r.status='pending'");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
body { font-family: Arial; margin:0; background:#f4f4f4; display:flex; }
.sidebar { width:220px; background:#1f2a47; color:white; height:100vh; padding:20px; position:fixed; }
.sidebar h2 { margin-bottom:20px; font-size:18px; }
.sidebar a { color:white; display:block; padding:10px; text-decoration:none; margin-bottom:5px; border-radius:5px; }
.sidebar a:hover { background:#374785; }
.main { margin-left:240px; padding:20px; flex:1; }
.stats { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.stat { background:white; flex:1; min-width:120px; padding:15px; border-radius:5px; text-align:center; box-shadow:0 2px 5px #ccc; }
.stat h3 { margin:0; }
.section { background:white; padding:15px; border-radius:5px; margin-bottom:20px; box-shadow:0 2px 5px #ccc; }
button { padding:8px 12px; margin-right:5px; border:none; border-radius:5px; cursor:pointer; color:white; }
.start { background:green; } .pause { background:orange; } .resume { background:blue; } .end { background:red; }
.accept { background:green; } .reject { background:red; }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
img { width:40px; height:40px; border-radius:50%; object-fit:cover; }

/* Quick Actions */
.quick-actions { display:flex; gap:15px; flex-wrap:wrap; margin-bottom:20px; }
.quick-card { flex:1; min-width:150px; background:white; padding:20px; border-radius:10px; text-align:center; box-shadow:0 2px 5px #ccc; cursor:pointer; transition:0.3s; }
.quick-card:hover { transform:scale(1.05); }
.quick-card i { font-size:24px; display:block; margin-bottom:10px; }
.quick-card .btn { padding:5px 10px; border:none; border-radius:5px; cursor:pointer; color:white; margin-top:5px; display:inline-block; text-decoration:none; }
.add { background:green; } .voter { background:blue; } .notice { background:purple; } .result { background:orange; } .hero { background:red; }

/* Modal */
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; }
.modal-content { background:white; padding:20px; border-radius:5px; width:300px; }
</style>
<!-- Use fontawesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="AdminDashboard.php">Dashboard</a>
    <a href="voters.php">Users</a>
    <a href="candidates.php">Candidates</a>
    <a href="admin_result.php">Results</a>
    <a href="admin_notices.php">Notices</a>
    <a href="logout.php">Logout</a>
</div>

<div class="main">
    <h1>Dashboard</h1>

    <div class="stats">
        <div class="stat"><h3><?= $totalVoters ?></h3>Total Voters</div>
        <div class="stat"><h3><?= $activeVoters ?></h3>Active Voters</div>
        <div class="stat"><h3><?= $totalCandidates ?></h3>Candidates</div>
        <div class="stat"><h3><?= $totalVotes ?></h3>Total Votes</div>
    </div>

    <!-- Quick Actions -->
    <div class="section">
        <h3> Quick Actions</h3>
        <div class="quick-actions">
            <div class="quick-card">
                <i class="fa-solid fa-user-plus"></i>
                <strong>Add Candidate</strong>
                <a href="add_candidates.php" class="btn add">Go to Add</a>
            </div>
            <div class="quick-card">
                <i class="fa-solid fa-user-graduate"></i>
                <strong>Add Voter</strong>
                <a href="studentRegistration.html" class="btn voter">Register Voter</a>
            </div>
            <div class="quick-card">
                <i class="fa-solid fa-bullhorn"></i>
                <strong>Manage Notices</strong>
                <a href="admin_notices.php" class="btn notice">View Notices</a>
            </div>
            <div class="quick-card">
                <i class="fa-solid fa-chart-bar"></i>
                <strong>Results Control</strong>
                <a href="admin_result.php" class="btn result">Publish Results</a>
            </div>
            <div class="quick-card">
                <i class="fa-solid fa-image"></i>
                <strong>Hero Image</strong>
                <a href="hero_upload.php" class="btn hero">Update Hero</a>
            </div>
        </div>
    </div>

    <!-- Voting Session -->
    <div class="section">
        <h3>Voting Session (<?= $vote_session['status'] ?>)</h3>
        <form method="post" action="manage_session.php">
            Start: <input type="datetime-local" name="start" value="<?= date('Y-m-d\TH:i', strtotime($vote_session['start_time'])) ?>" required>
            End: <input type="datetime-local" name="end" value="<?= date('Y-m-d\TH:i', strtotime($vote_session['end_time'])) ?>" required><br><br>
            <button class="start" name="action" value="start">Start</button>
            <button class="pause" name="action" value="pause">Pause</button>
            <button class="resume" name="action" value="resume">Resume</button>
            <button class="end" name="action" value="end">End</button>
        </form>
    </div>

    <!-- Nomination Session -->
    <div class="section">
        <h3>Nomination Session (<?= $nomination_session['status'] ?>)</h3>
        <form method="post" action="manage_nomination.php">
            Start: <input type="datetime-local" name="start" value="<?= date('Y-m-d\TH:i', strtotime($nomination_session['start_time'])) ?>" required>
            End: <input type="datetime-local" name="end" value="<?= date('Y-m-d\TH:i', strtotime($nomination_session['end_time'])) ?>" required><br><br>
            <button class="start" name="action" value="start">Start</button>
            <button class="pause" name="action" value="pause">Pause</button>
            <button class="resume" name="action" value="resume">Resume</button>
            <button class="end" name="action" value="end">End</button>
        </form>
    </div>

    <!-- Pending Requests -->
    <?php if($requests->num_rows>0): ?>
    <div class="section">
        <h3>Pending Candidate Requests (<?= $requests->num_rows ?>)</h3>
        <table>
            <tr><th>Photo</th>
            <th>Name</th><th>Party</th><th>Position</th><th>Requested By</th><th>Action</th></tr>
            <?php while($r=$requests->fetch_assoc()): ?>
            <tr>
                <td><?php if($r['photo']!=""): ?><img src="uploads/<?= $r['photo'] ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($r['candidate_name']) ?></td>
                <td><?= htmlspecialchars($r['party_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($r['position']) ?></td>
                <td><?= htmlspecialchars($r['voter_name']) ?></td>
                <td>
                    <form style="display:inline;" action="approve_request.php" method="post">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button class="accept" name="action" value="accept">Accept</button>
                    </form>
                    <button class="reject" onclick="openModal(<?= $r['id'] ?>)">Reject</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- Candidates -->
    <div class="section">
        <h3>All Candidates</h3>
        <a href="add_candidates.php" style="background:blue;color:white;padding:5px 10px;text-decoration:none;border-radius:5px;">Add New</a>
        <table>
            <tr><th>ID</th><th>Name</th><th>Photo</th><th>Party</th><th>Position</th><th>Votes</th><th>Action</th></tr>
            <?php while($c=$candidates->fetch_assoc()):
                $votes = $conn->query("SELECT COUNT(*) FROM votes WHERE candidate_id={$c['id']}")->fetch_row()[0];
            ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td><?php if($c['photo']!=""): ?><img src="uploads/<?= $c['photo'] ?>"><?php endif; ?></td>
                <td><?= htmlspecialchars($c['party_name'] ?? '') ?></td>
                <td><?= htmlspecialchars($c['position']) ?></td>
                <td><?= $votes ?></td>
                <td>
                    <a href="edit_candidates.php?id=<?= $c['id'] ?>" style="background:green;color:white;padding:5px;border-radius:5px;">Edit</a>
                    <a href="delete_candidate.php?id=<?= $c['id'] ?>" onclick="return confirm('Delete?')" style="background:red;color:white;padding:5px;border-radius:5px;">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>

</div>

<!-- Reject Modal -->
<div id="modal" class="modal">
    <div class="modal-content">
        <h3>Reject Candidate Request</h3>
        <form action="approve_request.php" method="post">
            <input type="hidden" name="id" id="modal_id">
            <input type="hidden" name="action" value="reject">
            <label>Reason:</label>
            <textarea name="reason" required></textarea><br><br>
            <button type="button" onclick="closeModal()">Cancel</button>
            <button type="submit">Reject</button>
        </form>
    </div>
</div>

<script>
function openModal(id){
    document.getElementById('modal_id').value = id;
    document.getElementById('modal').style.display = 'flex';
}
function closeModal(){
    document.getElementById('modal').style.display = 'none';
}
window.onclick = function(e){
    if(e.target==document.getElementById('modal')) closeModal();
}
</script>

</body>
</html>