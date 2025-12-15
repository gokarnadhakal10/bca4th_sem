<!-- 


require "connection_database.php";

$id = $_GET['id'];

// Fetch voter info
$voter = $mysqli->query("SELECT role FROM voters WHERE id=$id")->fetch_assoc();

// Prevent blocking admin
if($voter['role'] == 'admin'){
    die("You cannot block the admin!");
}

// Toggle status
$new_status = ($voter['status'] == 'active') ? 'blocked' : 'active';
$mysqli->query("UPDATE voters SET status='$new_status' WHERE id=$id");

header("Location: voters.php");
exit;

 -->
