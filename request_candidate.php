<?php
session_start();
require "config.php";

if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $voter_id = $_SESSION['voter_id'];
    $candidate_name = trim($_POST['candidate_name']);
    $party_name = trim($_POST['party_name']);
    $position = trim($_POST['position']);
    $vision = trim($_POST['vision']);

    // Check nomination session
    $session = $conn->query("SELECT status FROM nomination_session WHERE id=1")->fetch_assoc();
    if (($session['status'] ?? '') !== "Active") {
        die("Nomination period is closed.");
    }

    // Check duplicate request
    $check = $conn->prepare("SELECT id FROM candidate_requests WHERE voter_id=? AND (status='pending' OR status='approved')");
    $check->bind_param("i", $voter_id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        die("You already submitted nomination request.");
    }

    // Create uploads folder
    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    // Upload Candidate Photo
    $photo_path = "";
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_path = time() . "_candidate." . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $photo_path);
    }

    // Upload Party Image
    $party_image_path = "";
    if (!empty($_FILES['party_image']['name'])) {
        $ext = pathinfo($_FILES['party_image']['name'], PATHINFO_EXTENSION);
        $party_image_path = time() . "_party." . $ext;
        move_uploaded_file($_FILES['party_image']['tmp_name'], "uploads/" . $party_image_path);
    }

    // Insert Properly (IMPORTANT: party_name included)
    $stmt = $conn->prepare("INSERT INTO candidate_requests 
        (voter_id, candidate_name, party_name, position, vision, photo, party_image, request_time) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param("issssss", 
        $voter_id,
        $candidate_name,
        $party_name,
        $position,
        $vision,
        $photo_path,
        $party_image_path
    );

    if ($stmt->execute()) {
        echo "<script>alert('Nomination Request Submitted Successfully'); window.location='userDashboard.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Request Candidacy</title>

<style>
body{
    font-family: Arial;
    background: #f4f6f9;
}
.form-box{
    width: 450px;
    margin: 50px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 10px #ccc;
}
h2{
    text-align:center;
}
input, select, textarea{
    width:100%;
    padding:10px;
    margin:8px 0;
}
button{
    width:100%;
    padding:12px;
    background:#4CAF50;
    color:white;
    border:none;
    cursor:pointer;
}
button:hover{
    background:#45a049;
}
</style>

<script>
function validateForm(){
    let name = document.forms["reqForm"]["candidate_name"].value;
    let party = document.forms["reqForm"]["party_name"].value;
    let position = document.forms["reqForm"]["position"].value;

    if(name=="" || party=="" || position==""){
        alert("All fields are required!");
        return false;
    }
}
</script>

</head>
<body>

<div class="form-box">
<h2>Candidate Nomination Form</h2>

<form name="reqForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">

<label>Candidate Name</label>
<input type="text" name="candidate_name" required>

<label>Party Name</label>
<input type="text" name="party_name" required>

<label>Position</label>
<select name="position" required>
    <option value="">Select Position</option>
    <option>President</option>
    <option>Vice President</option>
    <option>Secretary</option>
    <option>Treasurer</option>
</select>

<label>Vision / Manifesto</label>
<textarea name="vision" required></textarea>

<label>Candidate Photo</label>
<input type="file" name="photo" accept="image/*" required>

<label>Party Symbol</label>
<input type="file" name="party_image" accept="image/*" required>

<button type="submit">Submit Request</button>

</form>
</div>

</body>
</html>