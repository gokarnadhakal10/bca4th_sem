<?php
require "config.php"; // Database connection

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
body{
    margin:0;
    font-family:sans-serif;
    background:#eef2f7;
}

/* Sidebar */
.sidebar{
    width:220px;
    height:100vh;
    position:fixed;
    background:#222;
    color:white;
    padding-top:20px;
}
.sidebar a{
    color:white;
    text-decoration:none;
    display:block;
    padding:12px 20px;
}
.sidebar a:hover{
    background:#444;
}

/* Header */
.header{
    margin-left:220px;
    background:#0d6efd;
    color:white;
    padding:15px;
    display:flex;
    justify-content:space-between;
}

/* Main content */
.main{
    margin-left:220px;
    padding:20px;
}

/* Buttons */
button{
    padding:8px 15px;
    border:none;
    background:#0d6efd;
    color:white;
    border-radius:5px;
    cursor:pointer;
}

/* Table */

.table-container{
    overflow-x:auto; /* scroll only if needed */
}
table{
    width:100%;
     max-width: 100%;
   
    border-collapse:collapse;
    background:white;
    table-layout: fixed;
    word-wrap: break-word;
}






/* Make table fit container */
.table-container table {
    width: 100%;
    border-collapse: collapse; /* Optional, nicer look */
}

/* Table styling */
.table-container th, 
.table-container td {
    padding: 10px;
    text-align: left;
    border: 1px solid #ddd;
}

/* Optional: striped rows */
.table-container tr:nth-child(even) {
    background-color: #f9f9f9;
}




th, td{
    padding:10px;
    border-bottom:1px solid #ddd;
    text-align:center;
}
img{
    width:50px;
    height:auto;
}





/* Common button style */
.btn {
    padding: 6px 14px;
    margin: 2px;
    font-size: 14px;
    font-weight: bold;
    color: #fff;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: 0.3s;
}

/* Edit button */
.btn-edit {
    background-color: #4CAF50; /* Green */
}

.btn-edit:hover {
    background-color: #45a049;
}

/* Delete button */
.btn-delete {
    background-color: #f44336; /* Red */
}

.btn-delete:hover {
    background-color: #da190b;
}


</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2 style="text-align:center;">Admin Panel</h2>
    <a href="AdminDashboard.php">Dashboard</a>
    <a href="voters.php">Voters management</a>
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

<h3>Voting Control</h3>
<form action="voting_session.php" method="POST">
Start Time: <input type="datetime-local" name="start" value="<?= $session['start_time'] ?>"><br><br>
End Time: <input type="datetime-local" name="end" value="<?= $session['end_time'] ?>"><br><br>

<button type="submit" name="action" value="start">Start Voting</button>
<button type="submit" name="action" value="end">End Voting</button>
</form>

<hr>

<h3>Quick Actions</h3>
<a href="add_candidates.php"><button>Add New Candidate</button></a>
<a href="studentRegistration.html"><button>Add New Voter</button></a>
<a href="result.php"><button>Publish Result</button></a>
<a href="image1.php"><button>Hero page </button></a>
<hr>

<h3>All Candidates</h3>
<div class="table-container">
<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Party</th>
    <th>Position</th>
    <th>Class</th>
    <th>Faculty</th>
    <th>Photo</th>
     <th>Votes</th>
      <th>Action</th>
</tr>

<?php while($c = $candidates->fetch_assoc()): ?>
<tr>
    <td><?= $c['id'] ?></td>
    <td><?= $c['name'] ?></td>
    <td><?= $c['party'] ?></td>
    <td><?= $c['position'] ?></td>
    <td><?= $c['class'] ?></td>
    <td><?= $c['faculty'] ?></td>
    <td><img src="uploads/<?= $c['photo'] ?>" alt="photo"></td>
    <td><?= $c['votes'] ?></td>
    

<td>
  <form action="edit_candidates.php" method="get" style="display:inline-block;">
    <input type="hidden" name="id" value="<?= $c['id'] ?>">
    <button type="submit" class="btn btn-edit">Edit</button>
  </form>

  <form action="delete_candidates.php" method="get" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
    <input type="hidden" name="id" value="<?= $c['id'] ?>">
    <button type="submit" class="btn btn-delete">Delete</button>
  </form>
</td>


</tr>
<?php endwhile; ?>

</table>
</div>

</div>
</body>
</html>
