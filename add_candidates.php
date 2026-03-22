<?php
session_start();
require "config.php";
require "auth.php";
admin_required();
$success = "";
$error = "";

// Add candidate (Admin)
if (isset($_POST['add'])) {
    $user_id = $_POST['user_id'];
    $name = trim($_POST['name']);
    $party_name = strtolower(trim($_POST['party_name'])); 
    $position = trim($_POST['position']);
    $faculty = trim($_POST['faculty']);
    $class = trim($_POST['class']);
    $platform = trim($_POST['platform']);
    
    // File uploads
    $photo = $_FILES['photo']['name'];
    $party_image = $_FILES['party_image']['name'];
    $photo_tmp = $_FILES['photo']['tmp_name'];
    $party_image_tmp = $_FILES['party_image']['tmp_name'];

    $valid_upload = true;
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024;

    // Validate Photo
    if (!empty($photo)) {
        $ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($photo_tmp);

        if ($_FILES['photo']['size'] > $max_size) {
            $valid_upload = false;
            $error = "Candidate photo is too large!";
        } elseif (!in_array($ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $valid_upload = false;
            $error = "Invalid candidate photo format!";
        }
    }

    // Validate Party Image
    if ($valid_upload && !empty($party_image)) {
        $ext = strtolower(pathinfo($party_image, PATHINFO_EXTENSION));
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($party_image_tmp);

        if ($_FILES['party_image']['size'] > $max_size) {
            $valid_upload = false;
            $error = "Party image too large!";
        } elseif (!in_array($ext, $allowed_exts) || !in_array($mime, $allowed_mimes)) {
            $valid_upload = false;
            $error = "Invalid party image!";
        }
    }

    if (!is_dir("uploads")) {
        mkdir("uploads");
    }

    if ($valid_upload) {

        // Check user already candidate
        $check_stmt = $conn->prepare("SELECT * FROM candidates WHERE user_id=?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "This student is already a candidate!";
        } else {

            // Upload files
            $upload_success = true;
            $photo_path = "";
            $party_image_path = "";

            if (!empty($photo) && move_uploaded_file($photo_tmp, "uploads/".$photo)) {
                $photo_path = $photo;
            } else {
                $upload_success = false;
                $error = "Photo upload failed!";
            }

            if (!empty($party_image) && move_uploaded_file($party_image_tmp, "uploads/".$party_image)) {
                $party_image_path = $party_image;
            }

            if ($upload_success) {

                //  No duplicate party or logo in same position
                $party_check = $conn->prepare("
                    SELECT * FROM candidates 
                    WHERE position=? AND (party_name=? OR party_image=?)
                ");
                $party_check->bind_param("sss", $position, $party_name, $party_image_path);
                $party_check->execute();
                $party_result = $party_check->get_result();

                if ($party_result->num_rows > 0) {
                    $error = "Same party or logo already exists for this position!";
                } else {

                    // Generate next ID
                    $id_result = $conn->query("SELECT id FROM candidates ORDER BY id ASC");
                    $next_id = 1;
                    while ($row = $id_result->fetch_assoc()) {
                        if ($row['id'] == $next_id) {
                            $next_id++;
                        } else {
                            break;
                        }
                    }

                    // Insert
                    $stmt = $conn->prepare("
                        INSERT INTO candidates
                        (id, user_id, name, position, party_name, party_image, faculty, class, platform, photo)
                        VALUES (?,?,?,?,?,?,?,?,?,?)
                    ");

                    $stmt->bind_param(
                        "iissssssss",
                        $next_id,
                        $user_id,
                        $name,
                        $position,
                        $party_name,
                        $party_image_path,
                        $faculty,
                        $class,
                        $platform,
                        $photo_path
                    );

                    if ($stmt->execute()) {
                        $conn->query("UPDATE users SET nomination_status='applied' WHERE id=".$user_id);
                        $success = "Candidate added successfully!";
                    } else {
                        $error = $stmt->error;
                    }

                    $stmt->close();
                }

                $party_check->close();
            }
        }

        $check_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Add Candidate</title>

<style>
body { font-family: Arial; background:#f4f6f9; }
.container { max-width:800px; margin:auto; background:#fff; padding:20px; border-radius:10px; }
input, select, textarea { width:100%; padding:10px; margin:8px 0; border:1px solid #ccc; border-radius:6px; }
button { background:#4361ee; color:#fff; padding:12px; border:none; width:100%; border-radius:6px; cursor:pointer; }
button:hover { background:#3f37c9; }
.alert { padding:10px; margin-bottom:10px; border-radius:5px; }
.success { background:#d4edda; color:#155724; }
.error { background:#f8d7da; color:#721c24; }
</style>

</head>

<body>

<div class="container">

<h2>Add Candidate</h2>

<?php if($success) echo "<div class='alert success'>$success</div>"; ?>
<?php if($error) echo "<div class='alert error'>$error</div>"; ?>

<form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">

<label>Select Student</label>
<select name="user_id" required>
<option value="">--Select--</option>
<?php
$res = $conn->query("SELECT id,name FROM users WHERE role='Voter' AND nomination_status='not_applied'");
while($u=$res->fetch_assoc()){
echo "<option value='{$u['id']}'>{$u['name']}</option>";
}
?>
</select>

<input type="text" name="name" placeholder="Candidate Name" required>
<input type="text" name="position" placeholder="Position" required>
<input type="text" name="party_name" placeholder="Party Name" required>

<select name="faculty" required>
<option value="">Faculty</option>
<option>BCA</option>
<option>BBS</option>
<option>B.ED</option>
</select>

<select name="class" required>
<option value="">Class</option>
<option>1st Semester</option>
<option>2nd Semester</option>
<option>3rd Semester</option>
</select>

<textarea name="platform" placeholder="Manifesto"></textarea>

<label>Photo</label>
<input type="file" name="photo" id="photo" required>

<label>Party Logo</label>
<input type="file" name="party_image" id="party_image">

<button type="submit" name="add">Add Candidate</button>

</form>
</div>

<script>
function validateForm() {
    let photo = document.getElementById("photo").files[0];

    if(photo){
        if(photo.size > 5 * 1024 * 1024){
            alert("Photo too large!");
            return false;
        }
    }
    return true;
}
</script>

</body>
</html>