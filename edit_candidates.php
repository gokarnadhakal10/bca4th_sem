<?php
require 'config.php';
require 'auth.php';

// Only allow admin
admin_required();

// Helper function to escape output
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Get candidate ID from GET
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: AdminDashboard.php');
    exit;
}

// Fetch candidate from DB
$stmt = $conn->prepare("SELECT * FROM candidates WHERE id=?");

$stmt->bind_param('i', $id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();

if (!$c) {
    header('Location: AdminDashboard.php');
    exit;
}

// Handle form submission
if (isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $party_name = trim($_POST['party_name']);
    $position = trim($_POST['position']);
    $faculty = trim($_POST['faculty']);
    $class = trim($_POST['class']);
    $platform = trim($_POST['platform']);
    
    $photo = $c['photo']; // keep current photo if not changed
    $party_image = $c['party_image']; // keep current party image

    // Handle photo upload
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $new_photo = time() . "_p." . $ext;
        if(move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $new_photo)){
            $photo = $new_photo;
        }
    }

    // Handle party image upload
    if (!empty($_FILES['party_image']['name'])) {
        $ext = pathinfo($_FILES['party_image']['name'], PATHINFO_EXTENSION);
        $new_party_img = time() . "_party." . $ext;
        if(move_uploaded_file($_FILES['party_image']['tmp_name'], "uploads/" . $new_party_img)){
            $party_image = $new_party_img;
        }
    }

    // Update candidate in DB
    $stmt = $conn->prepare("UPDATE candidates SET name=?, party_name=?, position=?, faculty=?, class=?, platform=?, photo=?, party_image=? WHERE id=?");
    $stmt->bind_param('ssssssssi', $name, $party_name, $position, $faculty, $class, $platform, $photo, $party_image, $id);
    
    if($stmt->execute()){
        echo "<script>alert('Candidate updated successfully!'); window.location='AdminDashboard.php';</script>";
    } else {
        $error = "Error updating candidate: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Candidate - Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
    
    body { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
        min-height: 100vh; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        padding: 20px; 
    }

    .form-container {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 600px;
    }

    h2 { text-align: center; color: #333; margin-bottom: 30px; font-size: 28px; }
    
    .form-group { margin-bottom: 20px; }
    
    label { display: block; margin-bottom: 8px; color: #555; font-weight: 600; }
    
    input[type="text"], select, textarea, input[type="file"] {
        width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px;
        transition: border-color 0.3s;
    }
    
    input:focus, select:focus, textarea:focus { border-color: #667eea; outline: none; }
    
    textarea { resize: vertical; height: 100px; }
    
    button {
        width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
    
    .back-link { display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; font-weight: 500; }
    .back-link:hover { color: #667eea; }
    
    .current-img { width: 60px; height: 60px; object-fit: cover; border-radius: 4px; margin-top: 10px; border: 1px solid #ddd; }
    .img-preview-container { display: flex; align-items: center; gap: 15px; }
</style>
</head>
<body>

<div class="form-container">
    <h2><i class="fas fa-user-edit"></i> Edit Candidate</h2>
    
    <?php if(isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label>Name</label>
            <input type="text" name="name" value="<?= h($c['name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Position</label>
            <input type="text" name="position" value="<?= h($c['position']) ?>" required>
        </div>

        <div class="form-group">
            <label>Party Name</label>
            <input type="text" name="party_name" value="<?= h($c['party_name']) ?>" required>
        </div>

        <div class="form-group">
            <label>Party Symbol (Image)</label>
            <div class="img-preview-container">
                <?php if ($c['party_image']): ?>
                    <img src="uploads/<?= h($c['party_image']) ?>" class="current-img" title="Current Symbol">
                <?php endif; ?>
                <input type="file" name="party_image" accept="image/*">
            </div>
        </div>

        <div class="form-group">
            <label>Faculty</label>
            <select name="faculty" required>
                <option value="">-- Select Faculty --</option>
                <?php 
                $faculties = ["BCA", "BBS", "B.ED"];
                foreach($faculties as $fac) {
                    $selected = ($c['faculty'] == $fac) ? 'selected' : '';
                    echo "<option value='$fac' $selected>$fac</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Class/Semester</label>
            <select name="class" required>
                <option value="">-- Select Class --</option>
                <?php 
                $classes = ["1st Semester", "2nd Semester", "3rd Semester", "4th Semester", "5th Semester", "6th Semester", "7th Semester", "8th Semester", "1st Year", "2nd Year", "3rd Year", "4th Year"];
                foreach($classes as $cls) {
                    $selected = ($c['class'] == $cls) ? 'selected' : '';
                    echo "<option value='$cls' $selected>$cls</option>";
                }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label>Platform/Manifesto</label>
            <textarea name="platform"><?= h($c['platform'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>Candidate Photo</label>
            <div class="img-preview-container">
                <?php if ($c['photo']): ?>
                    <img src="uploads/<?= h($c['photo']) ?>" class="current-img" title="Current Photo">
                <?php endif; ?>
                <input type="file" name="photo" accept="image/*">
            </div>
        </div>

        <button type="submit" name="update">Update Candidate</button>
        <a href="AdminDashboard.php" class="back-link">Back to Dashboard</a>
    </form>
</div>

</body>
</html>
