<?php
session_start();
require "config.php"; // Database connection
require "auth.php";

// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add candidate (Admin)
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $party_name = trim($_POST['party_name']);
    $position = trim($_POST['position']);
    $faculty = trim($_POST['faculty']);
    $class = trim($_POST['class']);
    $platform = trim($_POST['platform']);
    
    // File uploads
    $photo = $_FILES['photo']['name'];
    $party_image = $_FILES['party_image']['name'];
    $photo_tmp = $_FILES['photo']['tmp_name'];
    $party_image_tmp = $_FILES['party_image']['tmp_name'];

    // Create uploads folder if not exists
    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    // Check for duplicate candidate
    $check_stmt = $conn->prepare("SELECT * FROM candidates WHERE name=? AND party_name=? AND position=? AND faculty=? AND class=?");
    $check_stmt->bind_param("sssss", $name, $party_name, $position, $faculty, $class);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if($check_result->num_rows > 0){
        echo "<p style='color:red; text-align:center;'>Candidate already exists!</p>";
    } else {
        // Move uploaded files
        $upload_success = true;
        $photo_path = "";
        $party_image_path = "";
        
        if(!empty($photo) && move_uploaded_file($photo_tmp, "uploads/".$photo)){
            $photo_path = $photo;
        } else {
            $upload_success = false;
            echo "<p style='color:red; text-align:center;'>Failed to upload candidate photo!</p>";
        }
        
        if(!empty($party_image) && move_uploaded_file($party_image_tmp, "uploads/".$party_image)){
            $party_image_path = $party_image;
        } else {
            // Party image might be optional, so don't fail the whole process
            $party_image_path = "";
        }
        
        if($upload_success){
            // Find the lowest available ID (Gap Detection)
            $id_result = $conn->query("SELECT id FROM candidates ORDER BY id ASC");
            $next_id = 1;
            while ($row = $id_result->fetch_assoc()) {
                if ($row['id'] == $next_id) {
                    $next_id++;
                } else {
                    break;
                }
            }
            // Insert candidate using prepared statement - matches your table structure
            $stmt = $conn->prepare("INSERT INTO candidates(id, name, position, party_name, party_image, faculty, class, platform, photo) VALUES(?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issssssss", $next_id, $name, $position, $party_name, $party_image_path, $faculty, $class, $platform, $photo_path);
            
            if($stmt->execute()){
                echo "<p style='color:green; text-align:center;'>Candidate added successfully!</p>";
            } else {
                echo "<p style='color:red; text-align:center;'>Error: ".$stmt->error."</p>";
            }
            $stmt->close();
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
form { background:#fff; padding:20px; width:500px; border-radius:10px; box-shadow:0 0 10px #ccc; margin:20px auto; }
button { padding:10px; width:100%; background:#2196F3; color:white; border:none; cursor:pointer; }
button:hover { background:#0b66c0; }
input, select, textarea { width:100%; padding:8px; margin:5px 0 15px 0; border:1px solid #aaa; border-radius:5px; }
textarea { height:100px; resize:vertical; }
.form-group { margin-bottom:15px; }
.form-group label { display:block; margin-bottom:5px; font-weight:bold; }
</style>
</head>
<body>

<h2 style="text-align:center;">Admin : Add Candidate</h2>

<form method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="name">Candidate Name:</label>
        <input type="text" name="name" required>
    </div>
    
    <div class="form-group">
        <label for="position">Position:</label>
        <input type="text" name="position" required>
    </div>
    
    <div class="form-group">
        <label for="party_name">Party Name:</label>
        <input type="text" name="party_name" required>
    </div>
    
    <div class="form-group">
        <label for="faculty">Faculty:</label>
        <select id="faculty" name="faculty" class="form-control" required>
            <option value="">-- Select Faculty --</option>
            <option value="BCA">BCA</option>
            <option value="BBS">BBS</option>
            <option value="B.ED">B.ED</option>
            <!-- Add more faculties as needed -->
        </select>
    </div>
    
    <div class="form-group">
        <label for="class">Class/Semester:</label>
        <select id="class" name="class" class="form-control" required>
            <option value="">-- Select Class/Semester --</option>
            <option value="1st Semester">1st Semester</option>
            <option value="2nd Semester">2nd Semester</option>
            <option value="3rd Semester">3rd Semester</option>
            <option value="4th Semester">4th Semester</option>
            <option value="5th Semester">5th Semester</option>
            <option value="6th Semester">6th Semester</option>
            <option value="7th Semester">7th Semester</option>
            <option value="8th Semester">8th Semester</option>
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year">3rd Year</option>
            <option value="4th Year">4th Year</option>
        </select>
    </div>
    
    <div class="form-group">
        <label for="platform">Platform/Manifesto:</label>
        <textarea name="platform" placeholder="Enter candidate's platform or manifesto..."></textarea>
    </div>
    
    <div class="form-group">
        <label for="photo">Candidate Photo:</label>
        <input type="file" name="photo" accept="image/*" required>
    </div>
    
    <div class="form-group">
        <label for="party_image">Party Logo/Image (Optional):</label>
        <input type="file" name="party_image" accept="image/*">
    </div>

    <button type="submit" name="add">Add Candidate</button><br><br>
    <a href="AdminDashboard.php" style="text-align:center; display:block;">Go to Admin Panel</a>
</form>

</body>
</html>