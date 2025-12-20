
<?php
// Start session if needed
session_start();
require "config.php"; // Database connection
 require "auth.php";
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "voting_system";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if voter_id is provided
if (isset($_GET['voter_id'])) {
    $voter_id = intval($_GET['voter_id']); // sanitize input

    // Update status to 'Blocked'
    $sql = "UPDATE students SET status='Blocked' WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $voter_id);

    if ($stmt->execute()) {
        echo "Voter blocked successfully.";
    } else {
        echo "Error blocking voter: " . $conn->error;
    }

    $stmt->close();
} else {
    echo "No voter selected.";
}

$conn->close();
?>
