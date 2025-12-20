<?php
require "config.php"; // Database connection
require "auth.php";

admin_required();

// Define helper function
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

$candidates = $conn->query("SELECT * FROM candidates ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Candidates</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    padding: 20px;
}
h2 {
    text-align: center;
    margin-bottom: 20px;
}
a.add-new {
    display: inline-block;
    margin-bottom: 15px;
    padding: 8px 15px;
    background: #0d6efd;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}
a.add-new:hover {
    background: #0b5ed7;
}
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}
th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
th {
    background: #0d6efd;
    color: white;
}
tr:nth-child(even) {
    background: #f9f9f9;
}
img {
    width: 60px;
    height: auto;
    border-radius: 5px;
}
</style>
</head>
<body>

<h2>Candidates</h2>
<a class="add-new" href="add_candidates.php">Add New</a>
<a class="add-new" href="AdminDashboard.php">Back</a>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Party</th>
    <th>Position</th>
    <th>Class</th>
    <th>Faculty</th>
    <th>Votes</th>
    <th>Photo</th>
</tr>
</thead>
<tbody>
<?php while($r = $candidates->fetch_assoc()): ?>
<tr>
    <td><?= h($r['id']) ?></td>
    <td><?= h($r['name']) ?></td>
    <td><?= h($r['party']) ?></td>
    <td><?= h($r['position']) ?></td>
    <td><?= h($r['class']) ?></td>
    <td><?= h($r['faculty']) ?></td>
   


<?php
// Fetch number of votes for this candidate
$vote_count_sql = "SELECT COUNT(*) as total_votes FROM votes WHERE candidate_id=?";
$stmt_votes = $conn->prepare($vote_count_sql);
$stmt_votes->bind_param("i", $c['id']);
$stmt_votes->execute();
$vote_result = $stmt_votes->get_result();
$vote_data = $vote_result->fetch_assoc();
?>
<td><?= $vote_data['total_votes'] ?></td>



    <td>
        <?php if($r['photo']) echo "<img src='uploads/".h($r['photo'])."' alt='photo'>"; ?>
    </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
