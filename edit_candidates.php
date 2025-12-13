<?php
require 'config.php';
admin_required();
$id = intval($_GET['id'] ?? 0);
$stmt = $mysqli->prepare("SELECT * FROM candidates WHERE id=?");
$stmt->bind_param('i',$id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
if (!$c) { header('Location: AdminDashboard.php'); exit; }


if ($_SERVER['REQUEST_METHOD'] === 'POST'){
$name = $_POST['Name'];
$party = $_POST['Party'];
$position = $_POST['Position'];
$photo = $c['Photo'];
if (!empty($_FILES['Photo']['Name'])){
$ext = pathinfo($_FILES['Photo']['Name'], PATHINFO_EXTENSION);
$photo = time().".".$ext;
move_uploaded_file($_FILES['Photo']['tmp_name'], __DIR__.'/uploads/'.$photo);
}
$stmt = $mysqli->prepare("UPDATE candidates SET Name=?, Party=?,Position=?, Photo=? WHERE id=?");
$stmt->bind_param('sssi',$name,$party,$photo,$id);
$stmt->execute();
header('Location: AdminDashboard.php');
exit;
}
?>
<!doctype html>
<html><head><title>Edit Candidate</title></head><body>
<h2>Edit Candidate</h2>
<form method="post" enctype="multipart/form-data">
Name: <input name="name" value="<?= h($c['name']) ?>" required><br>
Party: <input name="party" value="<?= h($c['party']) ?>"><br>
Current Photo: <?php if($c['photo']) echo "<img src='uploads/".h($c['photo'])."' width='60'>"; ?><br>
Change Photo: <input type="file" name="photo" accept="image/*"><br>
<button type="submit">Save</button>
</form>
</body></html>