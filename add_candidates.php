
<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add candidate (Admin)
if (isset($_POST['add'])) {

    $name = trim($_POST['name']);
    $party = trim($_POST['party']);
    $position = trim($_POST['position']);
    $faculty = trim($_POST['faculty']);
    $class = trim($_POST['class']);
    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    // Create uploads folder if not exists
    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    // Check for duplicate candidate
    $check_stmt = $conn->prepare("SELECT * FROM candidates WHERE name=? AND party=? AND faculty=? AND class=? AND position=?");
    $check_stmt->bind_param("sssss", $name, $party, $faculty, $class, $position);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if($check_result->num_rows > 0){
        echo "<p style='color:red; text-align:center;'>Candidate already exists!</p>";
    } else {
        // Move uploaded file
        if(move_uploaded_file($tmp, "uploads/".$image)){
            // Insert candidate using prepared statement
            $stmt = $conn->prepare("INSERT INTO candidates(name, party, position, faculty, class, photo) VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $name, $party, $position, $faculty, $class, $image);
            
            if($stmt->execute()){
                echo "<p style='color:green; text-align:center;'>Candidate added successfully!</p>";
            } else {
                echo "<p style='color:red; text-align:center;'>Error: ".$stmt->error."</p>";
            }
            $stmt->close();
        } else {
            echo "<p style='color:red; text-align:center;'>Failed to upload photo!</p>";
        }
    }

    $check_stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin : Add Candidate</title>
<style>
body { font-family: Arial; background:#eef2f7; }
form { background:#fff; padding:20px; width:400px; border-radius:10px; box-shadow:0 0 10px #ccc; margin:20px auto; }
button { padding:10px; width:100%; background:#2196F3; color:white; border:none; cursor:pointer; }
button:hover { background:#0b66c0; }
input, select { width:100%; padding:8px; margin:5px 0 15px 0; border:1px solid #aaa; border-radius:5px; }
</style>
</head>
<body>

<h2 style="text-align:center;">Admin : Add Candidate</h2>

<form method="POST" enctype="multipart/form-data">
    Name:<br>
    <input type="text" name="name" required><br>

    Party:<br>
    <input type="text" name="party" required><br>

    Position:<br>
    <input type="text" name="position" required><br>

    Faculty:<br>
    <input type="text" name="faculty" required><br>

    Class:<br>
    <input type="text" name="class" required><br>

    Photo:<br>
    <input type="file" name="image" required><br>

    <button type="submit" name="add">Add Candidate</button><br><br>
    <a href="AdminDashboard.php" style="text-align:center; display:block;">Go to Admin Panel</a>
</form>

</body>
</html>
