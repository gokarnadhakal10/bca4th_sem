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

// Fetch candidates
$candidates = $conn->query("SELECT * FROM candidates");

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
<title>Admin Dashboard</title>
<style>
body{margin:0;font-family:sans-serif;
    background:#eef2f7;}
/* Sidebar */
.sidebar{width:220px;
    height:100vh;
    position:fixed;
    background:#222; 
    color:white; 
    padding-top:20px;}
.sidebar h2{text-align:center;
}
.sidebar a{color:white;
    text-decoration:none;
    display:block;padding:12px 20px;}
.sidebar a:hover{
    background:#444;
}
/* Header */
.header{margin-left:220px;
    background:#0d6efd;
    color:white;
    padding:15px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.header button{padding:8px 15px;
    border:none;border-radius:5px;
    cursor:pointer;
    color:white;
    background:#f44336;
}
/* Main */
.main{margin-left:220px;
    padding:20px;}
button{padding:8px 15px; 
    border:none;border-radius:5px; 
    cursor:pointer;
    margin:2px;}
/* Dashboard grid */
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin-bottom:20px;}
.box{background:white;padding:20px;border-radius:6px;text-align:center;font-size:16px;}
/* Table */
.table-container{overflow-x:auto;}
table{width:100%;border-collapse:collapse;background:white;table-layout:fixed;word-wrap:break-word;}
th, td{padding:10px;border:1px solid #ddd;text-align:center;}
tr:nth-child(even){background:#f9f9f9;}
/* Buttons */
.btn{padding:6px 14px;margin:2px;font-size:14px;font-weight:bold;color:white;border:none;border-radius:5px;cursor:pointer;}
.btn-edit{background:#4CAF50;} .btn-edit:hover{background:#45a049;}
.btn-delete{background:#f44336;} .btn-delete:hover{background:#da190b;}
.btn-accept{background:green;} .btn-reject{background:red;}


table img {
    width: 40px;       /* smaller width */
    height: 40px;      /* smaller height */
    object-fit: cover; /* maintain aspect ratio and crop if needed */
    border-radius: 4px; /* optional: rounded corners */
}


</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
<h2>Admin Panel</h2>
<a href="admin_dashboard.php">Dashboard</a>
<a href="voters.php">Voters Management</a>
<a href="candidates.php">Candidates</a>
<a href="result.php">Results</a>
<a href="logout.php">Logout</a>
</div>

<!-- Header -->
<div class="header">
<h2>Admin Dashboard</h2>
<a href="logout.php"><button>Logout</button></a>
</div>

<!-- Main Content -->
<div class="main">

<!-- ===== Dashboard Statistics ===== -->
<h3>System Overview</h3>
<div class="grid">
    <div class="box">Total Voters <br><b><?= $totalVoters ?></b></div>
    <div class="box">Active Voters <br><b><?= $activeVoters ?></b></div>
    <div class="box">Candidates <br><b><?= $totalCandidates ?></b></div>
    <div class="box">Total Votes <br><b><?= $totalVotes ?></b></div>
</div>

<!-- ===== Voting Session Control ===== -->
<h3>Voting Session Control</h3>
<form action="manage_session.php" method="post">
<label>Start Time:</label>
<input type="datetime-local" name="start" value="<?= date('Y-m-d\TH:i',strtotime($vote_session['start_time'])) ?>" required>
<label>End Time:</label>
<input type="datetime-local" name="end" value="<?= date('Y-m-d\TH:i',strtotime($vote_session['end_time'])) ?>" required>
<button type="submit" name="action" value="start" style="background:green;">Start</button>
<button type="submit" name="action" value="pause" style="background:orange;">Pause</button>
<button type="submit" name="action" value="resume" style="background:blue;">Resume</button>
<button type="submit" name="action" value="end" style="background:red;">End</button>
</form>

<hr>

<!-- ===== Nomination Session ===== -->
<h3>Candidate Nomination Session</h3>
<form action="manage_nomination.php" method="post">
<label>Start Time:</label>
<input type="datetime-local" name="start" value="<?= date('Y-m-d\TH:i',strtotime($nomination_session['start_time'])) ?>" required>
<label>End Time:</label>
<input type="datetime-local" name="end" value="<?= date('Y-m-d\TH:i',strtotime($nomination_session['end_time'])) ?>" required>
<button type="submit" style="background:purple;">Save Nomination Period</button>
</form>

<hr>

<!-- ===== Pending Candidate Requests ===== -->
<h3>Pending Candidate Requests</h3>
<?php while($r=$requests->fetch_assoc()): ?>
<p><?= $r['candidate_name'] ?> (<?= $r['position'] ?>) by <?= $r['voter_name'] ?>
<form action="approve_request.php" method="post" style="display:inline-block;">
<input type="hidden" name="id" value="<?= $r['id'] ?>">
<button type="submit" name="action" value="accept" class="btn btn-accept">Accept</button>
<button type="submit" name="action" value="reject" class="btn btn-reject">Reject</button>
</form>
</p>
<?php endwhile; ?>

<hr>

<!-- ===== All Candidates Table ===== -->
<h3>All Candidates</h3>
<div class="table-container">
<table>
<tr>
<th>SN</th>
<th>Name</th>
<th>Party</th>
<th>Position</th>
<th>Class</th>
<th>Faculty</th>
<th>Photo</th
><th>Votes</th>
<th>Action</th>
</tr>
<?php while($c=$candidates->fetch_assoc()):
$votes = $conn->query("SELECT COUNT(*) as total_votes FROM votes WHERE candidate_id={$c['id']}")->fetch_assoc()['total_votes'];
?>
<tr>
<td><?= $c['id'] ?></td>
<td><?= htmlspecialchars($c['name']) ?></td>
<td><?= htmlspecialchars($c['party']) ?></td>
<td><?= htmlspecialchars($c['position']) ?></td>
<td><?= htmlspecialchars($c['class']) ?></td>
<td><?= htmlspecialchars($c['faculty']) ?></td>
<td><img src="uploads/<?= $c['photo'] ?>" alt="Photo"></td>
<td><?= $votes ?></td>
<td>
<a href="edit_candidates.php?id=<?= $c['id'] ?>"><button class="btn btn-edit">Edit</button></a>
<!-- <a href="delete_candidates.php?id=<?= $c['id'] ?>" onclick="return confirm('Delete?')"><button class="btn btn-delete">Delete</button></a> -->
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<hr>

<!-- ===== Quick Actions ===== -->
<h3>Quick Actions</h3>
<a href="add_candidates.php"><button style="background:#4CAF50;">Add Candidate</button></a>
<a href="studentRegistration.html"><button style="background:#2196F3;">Add Voter</button></a>
<a href="result.php"><button style="background:#f39c12;">Publish Result</button></a>
<a href="hero_upload.php"><button style="background:#9b59b6;">Hero Page</button></a>

</div>
</body>
</html>
