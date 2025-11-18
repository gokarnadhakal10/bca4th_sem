<?php
session_start();
if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost","root","","voting_system");
$position = $_GET['position'] ?? '';

if (empty($position)) {
    echo "<h2>No position selected.</h2>";
    echo "<a href='admin_dashboard.php'>Back</a>";
    exit;
}

$result = $conn->query("SELECT * FROM candidates WHERE position='$position'");
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Candidates</title>
<style>
table { width: 70%; margin: auto; border-collapse: collapse; }
table, th, td { border: 1px solid black; padding: 10px; }
a.button { padding: 6px 12px; background: blue; color: white; text-decoration: none; }
</style>
</head>
<body>

<h2 style="text-align:center;">Manage Candidates - <?= ucfirst($position) ?></h2>
<p style="text-align:center;">
    <a class="button" href="add_candidate.php?position=<?= $position ?>">Add Candidate</a>
</p>

<table>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Class</th>
    <th>Actions</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['name'] ?></td>
    <td><?= $row['class'] ?></td>
    <td>
        <a class="button" href="edit_candidate.php?id=<?= $row['id'] ?>">Edit</a>
        <a class="button" style="background:red;" href="delete_candidate.php?id=<?= $row['id'] ?>">Delete</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
