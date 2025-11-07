


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    



<?php
session_start();
if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'voter') {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "voting_system");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get voter id
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id FROM user WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$voter_result = $stmt->get_result();
$voter = $voter_result->fetch_assoc();
$voter_id = $voter['id'];

// Check if voter already voted
$vote_check = $conn->prepare("SELECT * FROM votes WHERE voter_id=?");
$vote_check->bind_param("i", $voter_id);
$vote_check->execute();
if ($vote_check->get_result()->num_rows > 0) {
    echo "<h2>You have already voted!</h2>";
    exit;
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidate_id = $_POST['candidate_id'] ?? 0;
    if ($candidate_id) {
        $stmt = $conn->prepare("INSERT INTO votes (voter_id, candidate_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $voter_id, $candidate_id);
        $stmt->execute();
        echo "<h2>Vote submitted successfully!</h2>";
        exit;
    }
}

// Fetch all candidates
$candidates = $conn->query("SELECT * FROM candidates");
?>

<h1>Voter Dashboard</h1>
<h2>Cast Your Vote</h2>
<form method="post">
    <?php while ($row = $candidates->fetch_assoc()): ?>
        <input type="radio" name="candidate_id" value="<?php echo $row['id']; ?>" required>
        <?php echo $row['name'] . " (" . $row['symbol'] . ")"; ?><br>
    <?php endwhile; ?>
    <br>
    <input type="submit" value="Vote">
</form>

<a href="logout.php">Logout</a>
</body>
</html>