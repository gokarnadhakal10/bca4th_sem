<?php
session_start();
require "config.php";

// Check if voter is logged in
if (!isset($_SESSION['voter_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $voter_id = $_SESSION['voter_id'];
    $candidate_name = trim($_POST['candidate_name'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $vision = trim($_POST['vision'] ?? '');
    
    // Check if nomination session is active
    $session = $conn->query("SELECT * FROM nomination_session WHERE id=1")->fetch_assoc();
    $current_time = date('Y-m-d H:i:s');
    
    if (strcasecmp($session['status'] ?? '', 'Active') !== 0) {
        $_SESSION['error'] = "Nomination period is closed!";
        header("Location: userDashboard.php");
        exit();
    }
    
    // Check if already requested
    $check = $conn->prepare("SELECT id FROM candidate_requests WHERE voter_id=? AND (status='pending' OR status='approved')");
    $check->bind_param("i", $voter_id);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = "You already have a pending or approved nomination request.";
        header("Location: userDashboard.php");
        exit();
    }
    
    // Create uploads folder if not exists
    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    // Handle Photo Upload
    $photo = $_FILES['photo']['name'] ?? '';
    $photo_tmp = $_FILES['photo']['tmp_name'] ?? '';
    $photo_path = "";

    if (!empty($photo)) {
        $ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed_extensions)) {
            $new_name = time() . "_" . $voter_id . "_candidate." . $ext;
            if (move_uploaded_file($photo_tmp, "uploads/" . $new_name)) {
                $photo_path = $new_name;
            } else {
                $_SESSION['error'] = "Failed to upload candidate photo.";
                header("Location: userDashboard.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type for photo. Only JPG, JPEG, PNG, GIF are allowed.";
            header("Location: userDashboard.php");
            exit();
        }
    }

    // Handle Party Image Upload
    $party_image = $_FILES['party_image']['name'] ?? '';
    $party_image_tmp = $_FILES['party_image']['tmp_name'] ?? '';
    $party_image_path = "";

    if (!empty($party_image)) {
        $ext = strtolower(pathinfo($party_image, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed_extensions)) {
            $new_party_img = time() . "_party_" . $voter_id . "." . $ext;
            if (move_uploaded_file($party_image_tmp, "uploads/" . $new_party_img)) {
                $party_image_path = $new_party_img;
            } else {
                $_SESSION['error'] = "Failed to upload party image.";
                header("Location: userDashboard.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid file type for party image. Only JPG, JPEG, PNG, GIF are allowed.";
            header("Location: userDashboard.php");
            exit();
        }
    }
    
    // First, let's check what columns exist in the candidate_requests table
    $result = $conn->query("SHOW COLUMNS FROM candidate_requests");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Prepare the INSERT query based on available columns
    if (in_array('party_image', $columns) && in_array('photo', $columns)) {
        // Full table with both image columns
        $stmt = $conn->prepare("INSERT INTO candidate_requests (voter_id, candidate_name, position, party_image, photo, vision, request_time) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssss", $voter_id, $candidate_name, $position, $party_image_path, $photo_path, $vision);
    } elseif (in_array('photo', $columns)) {
        // Only photo column exists
        $stmt = $conn->prepare("INSERT INTO candidate_requests (voter_id, candidate_name, position, photo, vision, request_time) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $voter_id, $candidate_name, $position, $photo_path, $vision);
    } else {
        // Minimal table structure
        $stmt = $conn->prepare("INSERT INTO candidate_requests (voter_id, candidate_name, position, vision, request_time) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $voter_id, $candidate_name, $position, $vision);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Nomination request submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting request: " . $stmt->error;
    }
    
    $stmt->close();
    $check->close();
    
    header("Location: userDashboard.php");
    exit();
} else {
    // If not a POST request, redirect to dashboard
    header("Location: userDashboard.php");
    exit();
}