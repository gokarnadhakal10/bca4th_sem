<?php
session_start();
require "config.php";

$success = "";
$error = "";

/* ====== STATIC OPTIONS ====== */
$positions = ["President", "Vice President", "Secretary", "Treasurer"];
$faculties = ["BCA", "BBS", "B.Ed"];
$classes   = ["1st Year", "2nd Year", "3rd Year", "4th Year","1st semester", "2nd semester","3rd semester","4th semester","5th semester","6th  semester","7th semester","8 th semester"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name       = trim($_POST['name']);
    $position   = $_POST['position'];
    $party_name = trim($_POST['party_name']);
    $faculty    = $_POST['faculty'];
    $class      = $_POST['class'];
    $platform   = trim($_POST['platform']);

    // Upload photo
    $photo_path = "";
    if (!empty($_FILES["photo"]["name"])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $photo_path = $target_dir . time() . "_" . basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $photo_path);
    }

    // Upload party image
    $party_image_path = "";
    if (!empty($_FILES["party_image"]["name"])) {
        $target_dir = "uploads/";
        $party_image_path = $target_dir . time() . "_" . basename($_FILES["party_image"]["name"]);
        move_uploaded_file($_FILES["party_image"]["tmp_name"], $party_image_path);
    }

    // CHECK: One party one position
    $check = $conn->prepare("SELECT id FROM candidates WHERE party_name=? AND position=?");
    $check->bind_param("ss", $party_name, $position);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = " This party already has a candidate for this position!";
    } else {

        $stmt = $conn->prepare("INSERT INTO candidates 
            (name, position, party_name, party_image, faculty, class, platform, photo) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssss", 
            $name, $position, $party_name, 
            $party_image_path, $faculty, 
            $class, $platform, $photo_path
        );

        if ($stmt->execute()) {
            $success = " Candidate added successfully!";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Add Candidate</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 40px;
}

.container {
    background: white;
    max-width: 600px;
    margin: auto;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

input, select, textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

button {
    width: 100%;
    padding: 12px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #5563c1;
}

.success {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
}

.error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
}
</style>

<script>
function validateForm() {

    let name = document.forms["candidateForm"]["name"].value;
    let position = document.forms["candidateForm"]["position"].value;
    let faculty = document.forms["candidateForm"]["faculty"].value;
    let classValue = document.forms["candidateForm"]["class"].value;

    if (name === "" || position === "" || faculty === "" || classValue === "") {
        alert("All required fields must be selected!");
        return false;
    }

    return true;
}
</script>

</head>
<body>

<div class="container">
    <h2>Add Candidate</h2>
<div style="margin-bottom:15px;">
    <a href="adminDashboard.php" 
       style="
            text-decoration:none;
            background:#764ba2;
            color:white;
            padding:8px 14px;
            border-radius:6px;
            font-size:14px;
            display:inline-block;
       ">
       Back to Dashboard
    </a>
</div>
    <?php if($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form name="candidateForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">

        <input type="text" name="name" placeholder="Candidate Name" required>

        <!-- Position Dropdown -->
        <select name="position" required>
            <option value="">-- Select Position --</option>
            <?php foreach($positions as $pos): ?>
                <option value="<?= $pos ?>"><?= $pos ?></option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="party_name" placeholder="Party Name" required>

        <!-- Faculty Dropdown -->
        <select name="faculty" required>
            <option value="">-- Select Faculty --</option>
            <?php foreach($faculties as $fac): ?>
                <option value="<?= $fac ?>"><?= $fac ?></option>
            <?php endforeach; ?>
        </select>

        <!-- Class Dropdown -->
        <select name="class" required>
            <option value="">-- Select Class --</option>
            <?php foreach($classes as $cls): ?>
                <option value="<?= $cls ?>"><?= $cls ?></option>
            <?php endforeach; ?>
        </select>

        <textarea name="platform" placeholder="Candidate Platform"></textarea>

        <label>Candidate Photo:</label>
        <input type="file" name="photo">

        <label>Party Logo:</label>
        <input type="file" name="party_image">

        <button type="submit">Add Candidate</button>
        
    </form>
</div>

</body>
</html>