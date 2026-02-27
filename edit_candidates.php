<?php
require 'config.php';
require 'auth.php';
admin_required();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: AdminDashboard.php");
    exit;
}

// Fetch candidate
$stmt = $conn->prepare("SELECT * FROM candidates WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$candidate = $stmt->get_result()->fetch_assoc();

if (!$candidate) {
    header("Location: AdminDashboard.php");
    exit;
}

$error = "";

if (isset($_POST['update'])) {

    $name       = trim($_POST['name']);
    $position   = trim($_POST['position']);
    $party_name = trim($_POST['party_name']);
    $faculty    = trim($_POST['faculty']);
    $class      = trim($_POST['class']);
    $platform   = trim($_POST['platform']);

    // 🔥 CHECK DUPLICATE (Option A logic)
    $check = $conn->prepare("
        SELECT id FROM candidates 
        WHERE party_name=? AND position=? AND id != ?
    ");
    $check->bind_param("ssi", $party_name, $position, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {

        $error = "This party already has a candidate for this position!";

    } else {

        // Upload images only if valid
        $photo = $candidate['photo'];
        $party_image = $candidate['party_image'];

        if (!empty($_FILES['photo']['name'])) {
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $new_photo = time() . "_candidate." . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/" . $new_photo)) {
                $photo = $new_photo;
            }
        }

        if (!empty($_FILES['party_image']['name'])) {
            $ext = pathinfo($_FILES['party_image']['name'], PATHINFO_EXTENSION);
            $new_party = time() . "_party." . $ext;
            if (move_uploaded_file($_FILES['party_image']['tmp_name'], "uploads/" . $new_party)) {
                $party_image = $new_party;
            }
        }

        // Update record
        $update = $conn->prepare("
            UPDATE candidates
            SET name=?, position=?, party_name=?, faculty=?, class=?, platform=?, photo=?, party_image=?
            WHERE id=?
        ");

        $update->bind_param(
            "ssssssssi",
            $name,
            $position,
            $party_name,
            $faculty,
            $class,
            $platform,
            $photo,
            $party_image,
            $id
        );

        $update->execute();

        echo "<script>
                alert('Candidate updated successfully!');
                window.location='AdminDashboard.php';
              </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit Candidate</title>
<style>
body { font-family: Arial; background:#f4f4f4; padding:20px; }
.container { max-width:600px; margin:auto; background:#fff; padding:30px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.2); }
h2 { text-align:center; }
.form-group { margin-bottom:15px; }
label { display:block; font-weight:bold; margin-bottom:5px; }
input, select, textarea { width:100%; padding:10px; border:1px solid #ccc; border-radius:4px; }
textarea { height:100px; }
button { width:100%; padding:12px; background:#4CAF50; color:white; border:none; border-radius:4px; cursor:pointer; }
button:hover { background:#45a049; }
.error { color:red; text-align:center; margin-bottom:10px; font-weight:bold; }
.img-preview { width:70px; height:70px; object-fit:cover; margin-top:5px; border:1px solid #ccc; }
.back { display:block; text-align:center; margin-top:10px; }
</style>
</head>
<body>

<div class="container">
<h2>Edit Candidate</h2>

<?php if($error): ?>
<div class="error"><?= h($error) ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">

<div class="form-group">
<label>Name</label>
<input type="text" name="name" value="<?= h($candidate['name']) ?>" required>
</div>

<div class="form-group">
<label>Position</label>
<select name="position" required>
<option value="">-- Select Position --</option>
<?php
$positions = ["President","Vice President","Secretary","Treasurer"];
foreach ($positions as $pos) {
    $sel = ($candidate['position'] == $pos) ? "selected" : "";
    echo "<option value='$pos' $sel>$pos</option>";
}
?>
</select>
</div>

<div class="form-group">
<label>Party Name</label>
<input type="text" name="party_name" value="<?= h($candidate['party_name']) ?>" required>
</div>

<div class="form-group">
<label>Faculty</label>
<input type="text" name="faculty" value="<?= h($candidate['faculty']) ?>" required>
</div>

<div class="form-group">
<label>Class</label>
<input type="text" name="class" value="<?= h($candidate['class']) ?>" required>
</div>

<div class="form-group">
<label>Platform</label>
<textarea name="platform"><?= h($candidate['platform']) ?></textarea>
</div>

<div class="form-group">
<label>Candidate Photo</label><br>
<?php if($candidate['photo']): ?>
<img src="uploads/<?= h($candidate['photo']) ?>" class="img-preview">
<?php endif; ?>
<input type="file" name="photo">
</div>

<div class="form-group">
<label>Party Symbol</label><br>
<?php if($candidate['party_image']): ?>
<img src="uploads/<?= h($candidate['party_image']) ?>" class="img-preview">
<?php endif; ?>
<input type="file" name="party_image">
</div>

<button type="submit" name="update">Update Candidate</button>
<a href="AdminDashboard.php" class="back">Back</a>

</form>
</div>

</body>
</html>