<?php
require "connection_database.php";

$id = $_GET['id'];

// Fetch voter data
$voter = $mysqli->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();

if (isset($_POST['update'])) {
    $name  = $_POST['name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];

    $mysqli->query("UPDATE voters SET 
        name='$name', 
        email='$email', 
        mobile='$mobile' 
        WHERE id=$id");

    header("Location: voters.php");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Voter</title>
</head>
<body>

<h2>Edit Voter</h2>

<form method="post">
    Name: <br>
    <input type="text" name="name" value="<?php echo $voter['name']; ?>"><br><br>

    Email: <br>
    <input type="email" name="email" value="<?php echo $voter['email']; ?>"><br><br>

    moblile: <br>
    <input type="text" name="phone" value="<?php echo $voter['phone']; ?>"><br><br>

    <button type="submit" name="update">Update</button>
</form>

</body>
</html>
