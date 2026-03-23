<?php
session_start();
require "config.php"; // Database connection
require "auth.php";

$success = "";
$error = "";

// Add candidate (Admin)
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);



    $party_name = trim($_POST['party_name']);
    $position = trim($_POST['position']);
    $faculty = trim($_POST['faculty']);
    $class = trim($_POST['class']);
    $platform = trim($_POST['platform']);
    // $user_id = $_POST['user_id'];

    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($user_id == 0) {
    $error = "Please select a student!";
}


    // File uploads
    $photo = $_FILES['photo']['name'];
    $party_image = $_FILES['party_image']['name'];
    $photo_tmp = $_FILES['photo']['tmp_name'];
    $party_image_tmp = $_FILES['party_image']['tmp_name'];

    // Image Validation
    $valid_upload = true;
    $upload_msg = "";
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Validate Photo
    if (!empty($photo)) {
        $ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($photo_tmp);
        
        if ($_FILES['photo']['size'] > $max_size) {
            $valid_upload = false;
            $error = "Candidate photo is too large (Max 5MB).";
        } elseif (!in_array($ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $valid_upload = false;
            $error = "Invalid candidate photo format. Only JPG, PNG, GIF allowed.";
        }
    }

    // Validate Party Image
    
    
    if ($valid_upload && $user_id > 0 && !empty($party_image)) {
        $ext = strtolower(pathinfo($party_image, PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($party_image_tmp);
        
        if ($_FILES['party_image']['size'] > $max_size) {
            $valid_upload = false;
            $error = "Party image is too large (Max 5MB).";
        } elseif (!in_array($ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $valid_upload = false;
            $error = "Invalid party image format. Only JPG, PNG, GIF allowed.";
        }
    }

    // Create uploads folder if not exists
    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    if ($valid_upload) {
        // Check for duplicate candidate


// Check if user exists
$user_check = $conn->prepare("SELECT id FROM users WHERE id=?");
$user_check->bind_param("i", $user_id);
$user_check->execute();
$user_result = $user_check->get_result();

if ($user_result->num_rows == 0) {
    $error = "Selected student does not exist!";
}


        $check_stmt = $conn->prepare("SELECT * FROM candidates WHERE name=? AND party_name=? AND position=? AND faculty=? AND class=?");
        $check_stmt->bind_param("sssss", $name, $party_name, $position, $faculty, $class);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if($check_result->num_rows > 0){
            $error = "Candidate already exists!";
        } else {
            // Move uploaded files
            $upload_success = true;
            $photo_path = "";
            $party_image_path = "";
            
            if(!empty($photo) && move_uploaded_file($photo_tmp, "uploads/".$photo)){
                $photo_path = $photo;
            } else {
                $upload_success = false; 


                $error = "Failed to upload candidate photo!";
            }
            
            if(!empty($party_image) && move_uploaded_file($party_image_tmp, "uploads/".$party_image)){
                $party_image_path = $party_image;
            } else {
                // Party image might be optional, so don't fail the whole process
                $party_image_path = "";
            }
            
           if($upload_success && empty($error)){
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



                $stmt = $conn->prepare("INSERT INTO candidates(id, user_id, name, position, party_name, party_image, faculty, class, platform, photo) VALUES(?,?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("iissssssss", $next_id, $user_id,$name, $position, $party_name, $party_image_path, $faculty, $class, $platform, $photo_path);
                  




                if($stmt->execute()){
                    $success = "Candidate added successfully!";
                } else {
                    $error = "Error: ".$stmt->error;
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Candidate - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #4361ee;
        --secondary: #3f37c9;
        --success: #4cc9f0;
        --danger: #f72585;
        --warning: #f8961e;
        --info: #4895ef;
        --dark: #1a1a2e;
        --light: #f8f9fa;
        --sidebar-width: 260px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
    
    body {
        background: #f0f2f5;
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
        width: var(--sidebar-width);
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
        color: white;
        position: fixed;
        height: 100vh;
        padding: 20px;
        z-index: 100;
        transition: all 0.3s;
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 30px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        margin-bottom: 20px;
    }

    .logo-icon {
        width: 40px;
        height: 40px;
        background: var(--primary);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .nav-links { list-style: none; }
    .nav-links li { margin-bottom: 10px; }
    
    .nav-links a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .nav-links a:hover, .nav-links a.active {
        background: rgba(67, 97, 238, 0.2);
        color: white;
        transform: translateX(5px);
    }

    /* Main Content */
    .main-content {
        margin-left: var(--sidebar-width);
        flex: 1;
        padding: 30px;
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .dashboard-section {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        max-width: 800px;
        margin: 0 auto;
    }

    .section-title { font-size: 20px; font-weight: 600; color: var(--dark); margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }

    .form-group { margin-bottom: 20px; }
    label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
    
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .form-control:focus {
        border-color: var(--primary);
        outline: none;
    }
    
    textarea.form-control { min-height: 120px; resize: vertical; }

    .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px; }
    .btn-primary { background: var(--primary); color: white; }
    .btn-secondary { background: #95a5a6; color: white; }
    .btn:hover { opacity: 0.9; transform: translateY(-2px); }

    .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

    @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .main-content { margin-left: 0; } }
</style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-vote-yea"></i></div>
            <h3>Admin Panel</h3>
        </div>
        <ul class="nav-links">
            <li><a href="AdminDashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="voters.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="candidates.php" class="active"><i class="fas fa-user-tie"></i> Candidates</a></li>
            <li><a href="admin_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
            <li><a href="admin_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <li><a href="reset_election.php" style="color: #f72585;"><i class="fas fa-redo"></i> Reset Election</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div class="welcome-text">
                <h2>Add New Candidate</h2>
                <p>Register a new candidate for the election.</p>
            </div>
            <a href="candidates.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
        </div>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-section">
            <div class="section-title"><i class="fas fa-user-plus"></i> Candidate Details</div>
            
            <form method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                   


<div class="form-group">
    <label>Select Student *</label>
    <select name="user_id" class="form-control" required>
        <option value="">-- Select Student --</option>
        <?php
        $res = $conn->query("SELECT id,name FROM users WHERE role='Voter'");
        while($u=$res->fetch_assoc()){
            echo "<option value='{$u['id']}'>{$u['name']}</option>";
        }
        ?>
    </select>
</div>


                         <div class="form-group">
                        <label for="name">Candidate Name *</label>
                        <input type="text" name="name" id="name" class="form-control" required placeholder="Enter full name">
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position *</label>
                        <input type="text" name="position" id="position" class="form-control" required placeholder="e.g. President">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="party_name">Party Name *</label>
                    <input type="text" name="party_name" id="party_name" class="form-control" required placeholder="Enter party name">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="faculty">Faculty *</label>
                        <select id="faculty" name="faculty" class="form-control" required>
                            <option value="">-- Select Faculty --</option>
                            <option value="BCA">BCA</option>
                            <option value="BBS">BBS</option>
                            <option value="B.ED">B.ED</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="class">Class/Semester *</label>
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
                </div>
                
                <div class="form-group">
                    <label for="platform">Platform/Manifesto</label>
                    <textarea name="platform" id="platform" class="form-control" placeholder="Enter candidate's platform or manifesto..."></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="photo">Candidate Photo *</label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*" required>
                        <small style="color: #666;">Max size: 5MB. Formats: JPG, PNG, GIF</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="party_image">Party Logo/Image (Optional)</label>
                        <input type="file" name="party_image" id="party_image" class="form-control" accept="image/*">
                    </div>
                </div>

                <div style="margin-top: 10px;">
                    <button type="submit" name="add" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fas fa-save"></i> Add Candidate
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>