



<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add candidate (Admin)
if (isset($_POST['add'])) {

    $name = $_POST['name'];
    $party = $_POST['party'];
    $position = $_POST['position'];

    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    // Create uploads folder if it doesn't exist
    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    // Check for duplicate candidate
    $check = $conn->query("SELECT * FROM candidates WHERE name='$name' AND party='$party' AND position='$position'");

    if($check->num_rows > 0){
        echo "<p style='color:red; text-align:center;'>Candidate already exists!</p>";
    } else {
        move_uploaded_file($tmp, "uploads/".$image);
        $conn->query("INSERT INTO candidates(name, party, position, photo) VALUES('$name','$party','$position','$image')");
        echo "<p style='color:green; text-align:center;'>Candidate added successfully!</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Voting System</title>
<style>
body{
    font-family: Arial;
    background:#eef2f7;
}
form{
    background:#fff;
    padding:20px;
    width:350px;
    border-radius:10px;
    box-shadow:0 0 10px #ccc;
    margin:20px auto;
}
button{
    padding:10px;
    width:100%;
    background:#2196F3;
    color:white;
    border:none;
}
</style>
</head>

<body>

<h2 style="text-align:center;">Admin : Add Candidate</h2>

<form method="POST" enctype="multipart/form-data">
    Name:<br>
    <input type="text" name="name" required><br><br>

    Party:<br>
    <input type="text" name="party" required><br><br>

    Position:<br>
    <input type="text" name="position" required><br><br>

    Photo:<br>
    <input type="file" name="image" required><br><br>

    <button type="submit" name="add">Add Candidate</button>
    <a href ="AdminDashboard.php">go to admin panel </a>
</form>

</body>
</html>
