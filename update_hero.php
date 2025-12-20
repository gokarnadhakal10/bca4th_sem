<?php
session_start();
require "config.php"; // DB connection


 require "auth.php";
admin_required();
// Only admin can update
if(!isset($_SESSION['admin_id'])){
    header("Location: login.php");
    exit;
}

if(isset($_FILES['hero_image'])){
    $file_name = $_FILES['hero_image']['name'];
    $tmp_name = $_FILES['hero_image']['tmp_name'];
    $upload_dir = "uploads/";

    if(move_uploaded_file($tmp_name, $upload_dir.$file_name)){
        // Insert into database
        $sql = "INSERT INTO hero_image (image) VALUES (?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $file_name);
        $stmt->execute();
        $stmt->close();

        header("Location: admin_dashboard.php");
        exit;
    } else {
        echo "Failed to upload image.";
    }
}
?>
