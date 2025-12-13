
<?php
require "config.php";
// admin_required();

// Fetch candidates
$candidates = $mysqli->query("SELECT * FROM candidates");

// Fetch voting session
$session = $mysqli->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<style>
body{margin:0;
    font-family:sans-serif;
background:#eef2f7;
}
.sidebar{width:220px;
    height:100vh;
position:fixed;
background:#222;
color:white;
padding-top:20px;}
.sidebar a{color:white;
text-decoration:none;
display:block;
padding:12px 20px;}
.sidebar a:hover{
background:#444;
}
.header{margin-left:220px;
    background:#0d6efd;
color:white;
padding:15px;display:flex;justify-content:space-between;}
.main{margin-left:220px;
padding:20px;}
button{padding:8px 15px;
border:none;
background:#0d6efd;
color:white;
border-radius:5px;
cursor:pointer;
}
table{width:100%;
border-collapse:collapse;
background:white;
}
th,td{padding:10px;
border-bottom:1px solid #ddd;}
</style>
</head>
<body>

<div class="sidebar">
    <h2 style="text-align:center;">Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="voters.php">Voters</a>
    <a href="candidates.php">Candidates</a>
    <a href="result.php">Results</a>
    <a href="logout.php">Logout</a>
</div>

<div class="header">
    <h2>Admin Dashboard</h2>
    <a href="logout.php"><button>Logout</button></a>
</div>

<div class="main">

<h3>Voting Control</h3>

<form action="voting_session.php" method="POST">
Start Time: <input type="datetime-local" name="start" value="<?= $session['start_time'] ?>"><br><br>
End Time: <input type="datetime-local" name="end" value="<?= $session['end_time'] ?>"><br><br>

<button type="submit" name="action" value="start">Start Voting</button>
<button type="submit" name="action" value="end">End Voting</button>
</form>

<hr>

<h3>Quick Actions</h3>
<a href="add_candidate.php"><button>Add New Candidate</button></a>
<a href="add_voter.php"><button>Add New Voter</button></a>
<a href="result.php"><button>Publish Result</button></a>

<hr>

<h3>All Candidates</h3>

<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Party</th>
    <th>Position</th>
    <th>Photo</th>
    <th>Action</th>
</tr>

<?php while($c = $candidates->fetch_assoc()): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= $c['Name'] ?></td>
    <td><?= $c['Party'] ?></td>
    <td><?= $c['Position'] ?></td>
    <td><img src="uploads/<?= $c['Photo'] ?>" width="50"></td>
    <td>
        <a href="edit_candidate.php?id=<?= $c['id'] ?>">Edit</a> |
        <a href="delete_candidate.php?id=<?= $c['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
    </td>
</tr>
<?php endwhile;
 ?>

</table>

</div>
</body>
</html>
