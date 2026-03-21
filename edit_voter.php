<?php
session_start();
require 'config.php';
require 'auth.php';

admin_required();

// Helper function for security
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Check if election is locked
$is_election_locked = false;
$session_check = $conn->query("SELECT status FROM voting_session WHERE id = 1");
if ($session_check && $session_check->num_rows > 0) {
    $session = $session_check->fetch_assoc();
    if (isset($session['status']) && in_array($session['status'], ['Active', 'Paused'])) {
        $is_election_locked = true;
    }
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: voters.php");
    exit;
}

// Fetch voter data using prepared statement
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$voter = $result->fetch_assoc();
$stmt->close();

if (!$voter) {
    header("Location: voters.php");
    exit;
}

$success = "";
$error = "";

// Handle form submission
if (isset($_POST['update'])) {
    if ($is_election_locked) {
        $error = "Voters cannot be edited while an election is active.";
    } else {
        $name   = trim($_POST['name']);
        $email  = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        $faculty = trim($_POST['faculty']);
        $class = trim($_POST['class']);
        
        $photo_path = $voter['photo']; // Keep old photo by default

        // Handle photo upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            $photo_name = $_FILES['photo']['name'];
            $photo_tmp = $_FILES['photo']['tmp_name'];
            $photo_size = $_FILES['photo']['size'];
            
            $ext = strtolower(pathinfo($photo_name, PATHINFO_EXTENSION));
            
            if ($photo_size > $max_size) {
                $error = "Photo is too large (Max 5MB).";
            } elseif (!in_array($ext, $allowed_exts)) {
                $error = "Invalid photo format. Only JPG, PNG, GIF allowed.";
            } else {
                $uploadDir = "uploads/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $new_photo_name = time() . "_" . basename($photo_name);
                $new_photo_path = $uploadDir . $new_photo_name;

                if (move_uploaded_file($photo_tmp, $new_photo_path)) {
                    if (!empty($photo_path) && file_exists($photo_path) && $photo_path !== $new_photo_path) {
                        unlink($photo_path);
                    }
                    $photo_path = $new_photo_path;
                } else {
                    $error = "Failed to upload new photo.";
                }
            }
        }

        if (empty($error)) {
            $update_stmt = $conn->prepare("UPDATE users SET name=?, email=?, mobile=?, faculty=?, class=?, photo=? WHERE id=?");
            $update_stmt->bind_param("ssssssi", $name, $email, $mobile, $faculty, $class, $photo_path, $id);

            if ($update_stmt->execute()) {
                // Add a notification for the user whose profile was updated.
                // This requires a `notifications` table with (id, user_id, message, is_read, created_at)
                $notification_message = "Your profile details were updated by an administrator.";
                $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $notify_stmt->bind_param("is", $id, $notification_message);
                $notify_stmt->execute();
                $notify_stmt->close();

                $_SESSION['message'] = "Voter details updated successfully!";
                header("Location: voters.php");
                exit;
            } else {
                $error = "Error updating voter: " . $update_stmt->error;
            }
            $update_stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Voter - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="add_candidates.php"> <!-- Reusing styles from add_candidates.php -->
    <style>
        :root { --primary: #4361ee; --secondary: #3f37c9; --success: #4cc9f0; --danger: #f72585; --warning: #f8961e; --info: #4895ef; --dark: #1a1a2e; --light: #f8f9fa; --sidebar-width: 260px; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #f0f2f5; display: flex; min-height: 100vh; }
        .sidebar { width: var(--sidebar-width); background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%); color: white; position: fixed; height: 100vh; padding: 20px; z-index: 100; }
        .sidebar-header { display: flex; align-items: center; gap: 10px; padding-bottom: 30px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .logo-icon { width: 40px; height: 40px; background: var(--primary); border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .nav-links { list-style: none; }
        .nav-links li { margin-bottom: 10px; }
        .nav-links a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: rgba(255,255,255,0.8); text-decoration: none; border-radius: 8px; transition: all 0.3s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(67, 97, 238, 0.2); color: white; transform: translateX(5px); }
        .main-content { margin-left: var(--sidebar-width); flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .dashboard-section { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto; }
        .section-title { font-size: 20px; font-weight: 600; color: var(--dark); margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; transition: border-color 0.3s; }
        .form-control:focus { border-color: var(--primary); outline: none; }
        .form-control:disabled { background-color: #f8f9fa; cursor: not-allowed; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .current-img { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; border: 3px solid #eee; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-icon"><i class="fas fa-vote-yea"></i></div>
            <h3>Admin Panel</h3>
        </div>
        <ul class="nav-links">
            <li><a href="AdminDashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="voters.php" class="active"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="candidates.php"><i class="fas fa-user-tie"></i> Candidates</a></li>
            <li><a href="admin_result.php"><i class="fas fa-chart-bar"></i> Results</a></li>
            <li><a href="admin_notices.php"><i class="fas fa-bullhorn"></i> Notices</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            <li><a href="reset_election.php" style="color: #f72585;"><i class="fas fa-redo"></i> Reset Election</a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="welcome-text">
                <h2>Edit Voter Details</h2>
                <p>Update information for voter #<?php echo h($voter['id']); ?></p>
            </div>
            <a href="voters.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Voters List</a>
        </div>

        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="dashboard-section">
            <div class="section-title"><i class="fas fa-user-edit"></i> Voter Information</div>
            
            <form method="post" enctype="multipart/form-data">
                <div class="form-group" style="text-align: center;">
                    <?php if (!empty($voter['photo']) && file_exists($voter['photo'])): ?>
                        <img src="<?php echo h($voter['photo']); ?>" alt="Current Photo" class="current-img">
                    <?php else: ?>
                        <div class="current-img" style="display: inline-flex; align-items: center; justify-content: center; background: #eee; font-size: 32px; color: #aaa;">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" name="name" id="name" class="form-control" value="<?php echo h($voter['name']); ?>" required <?php if($is_election_locked) echo 'disabled'; ?>>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" name="email" id="email" class="form-control" value="<?php echo h($voter['email']); ?>" required <?php if($is_election_locked) echo 'disabled'; ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number *</label>
                    <input type="text" name="mobile" id="mobile" class="form-control" value="<?php echo h($voter['mobile']); ?>" required <?php if($is_election_locked) echo 'disabled'; ?>>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label for="faculty">Faculty *</label>
                        <select id="faculty" name="faculty" class="form-control" required <?php if($is_election_locked) echo 'disabled'; ?>>
                            <option value="BCA" <?php if($voter['faculty'] == 'BCA') echo 'selected'; ?>>BCA</option>
                            <option value="BBS" <?php if($voter['faculty'] == 'BBS') echo 'selected'; ?>>BBS</option>
                            <option value="B.ED" <?php if($voter['faculty'] == 'B.ED') echo 'selected'; ?>>B.ED</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="class">Class/Semester *</label>
                        <select id="class" name="class" class="form-control" required <?php if($is_election_locked) echo 'disabled'; ?>>
                            <option value="1st Semester" <?php if($voter['class'] == '1st Semester') echo 'selected'; ?>>1st Semester</option>
                            <option value="2nd Semester" <?php if($voter['class'] == '2nd Semester') echo 'selected'; ?>>2nd Semester</option>
                            <option value="3rd Semester" <?php if($voter['class'] == '3rd Semester') echo 'selected'; ?>>3rd Semester</option>
                            <option value="4th Semester" <?php if($voter['class'] == '4th Semester') echo 'selected'; ?>>4th Semester</option>
                            <option value="5th Semester" <?php if($voter['class'] == '5th Semester') echo 'selected'; ?>>5th Semester</option>
                            <option value="6th Semester" <?php if($voter['class'] == '6th Semester') echo 'selected'; ?>>6th Semester</option>
                            <option value="7th Semester" <?php if($voter['class'] == '7th Semester') echo 'selected'; ?>>7th Semester</option>
                            <option value="8th Semester" <?php if($voter['class'] == '8th Semester') echo 'selected'; ?>>8th Semester</option>
                            <option value="1st Year" <?php if($voter['class'] == '1st Year') echo 'selected'; ?>>1st Year</option>
                            <option value="2nd Year" <?php if($voter['class'] == '2nd Year') echo 'selected'; ?>>2nd Year</option>
                            <option value="3rd Year" <?php if($voter['class'] == '3rd Year') echo 'selected'; ?>>3rd Year</option>
                            <option value="4th Year" <?php if($voter['class'] == '4th Year') echo 'selected'; ?>>4th Year</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="photo">Update Photo (Optional)</label>
                    <input type="file" name="photo" id="photo" class="form-control" accept="image/*" <?php if($is_election_locked) echo 'disabled'; ?>>
                    <small style="color: #666;">Leave blank to keep the current photo. Max size: 5MB.</small>
                </div>

                <div style="margin-top: 30px;">
                    <?php if ($is_election_locked): ?>
                        <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; text-align: center;">
                            <i class="fas fa-lock"></i> Editing is disabled during an active election.
                        </div>
                    <?php else: ?>
                        <button type="submit" name="update" class="btn btn-primary" style="width: 100%; justify-content: center;">
                            <i class="fas fa-save"></i> Update Voter
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
