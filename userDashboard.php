<?php
session_start();
require "config.php"; // database connection

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit;
}

$voter_id = $_SESSION['voter_id'];



// ✅ CHECK VOTING SESSION STATUS
$session = $conn->query("SELECT * FROM voting_session WHERE id=0")->fetch_assoc();

$now = date('Y-m-d H:i:s');

if ($session['status'] !== 'active') {
    echo "<h3 style='color:red;text-align:center'>{$session['message']}</h3>";
    exit;
}

if ($now < $session['start_time'] || $now > $session['end_time']) {
    echo "<h3 style='color:red;text-align:center'>Voting is not available at this time</h3>";
    exit;
}






// Fetch user info
$voter_sql = "SELECT name, faculty,photo, class, status FROM users WHERE id=?";
$stmt = $conn->prepare($voter_sql);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter_result = $stmt->get_result();
$voter = $voter_result->fetch_assoc();

// Fetch candidates
$candidates_sql = "SELECT * FROM candidates";
$candidates_result = $conn->query($candidates_sql);

// Fetch votes already cast by this voter
$votes_sql = "SELECT position FROM votes WHERE voter_id=?";
$stmt = $conn->prepare($votes_sql);
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$votes_result = $stmt->get_result();

$voted_positions = [];
while ($row = $votes_result->fetch_assoc()) {
    $voted_positions[] = $row['position'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voter Dashboard</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f0f2f5;
    margin: 0;
    padding: 0;
}
header {
    background: #4CAF50;
    color: #fff;
    padding: 15px;
    text-align: center;
}
.container {
    width: 90%;
    margin: 20px auto;
}
.voter-info {
    background: #fff;
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 6px;
    overflow: hidden;
    box-shadow: 0 0 5px rgba(0,0,0,0.1);
}
th, td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
tr:hover {
    background-color: #f1f1f1;
}
.candidate-photo {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 50%;
}
.btn {
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    color: #fff;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}
.btn-vote {
    background-color: #4CAF50;
}
.btn-vote:hover {
    background-color: #45a049;
}
.btn-disabled {
    background-color: #ccc;
    cursor: not-allowed;
}
.status-blocked {
    color: red;
    font-weight: bold;
}
.status-active {
    color: green;
    font-weight: bold;
}
</style>
</head>
<body>

<header>
    <h1>Voter Dashboard</h1>
</header>

<div class="container">

  <!-- voter info -->

    <div class="voter-info">
    <p><strong>Name:</strong> <?= $voter['name'] ?></p>
    <p><strong>Faculty:</strong> <?= $voter['faculty'] ?></p>
    <p><strong>Class:</strong> <?= $voter['class'] ?></p>
    
    <p><strong>Photo:</strong></p>
    <img src="<?= $voter['photo'] ?>" alt="Voter Photo" class="candidate-photo">
    
    <p><strong>Status:</strong> 
        <span class="<?= $voter['status']=='Blocked'?'status-blocked':'status-active' ?>">
            <?= $voter['status'] ?>
        </span>
    </p>
</div>


    <!-- Candidates Table -->
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Party</th>
                <th>Position</th>
                <th>Photo</th>
                <th>Vote</th>
            </tr>
        </thead>
        <tbody>
            <?php while($c = $candidates_result->fetch_assoc()): ?>
                <tr>
                    <td><?= $c['id'] ?></td>
                    <td><?= $c['name'] ?></td>
                    <td><?= $c['party'] ?></td>
                    <td><?= $c['position'] ?></td>
                    <td><img src="uploads/<?= $c['photo'] ?>" alt="photo" class="candidate-photo"></td>
                    <td>
                        <?php 
                        if($voter['status'] == 'Blocked' || in_array($c['position'], $voted_positions)): 
                        ?>
                            <button class="btn btn-disabled" disabled>
                                <?= in_array($c['position'], $voted_positions) ? "Voted" : "Blocked" ?>
                            </button>
                        <?php else: ?>
                            <form action="vote.php" method="post" style="display:inline-block;">
                                <input type="hidden" name="voter_id" value="<?= $voter_id ?>">
                                <input type="hidden" name="candidate_id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-vote" onclick="return confirm('Confirm your vote?')">Vote</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

</div>

</body>
</html>
