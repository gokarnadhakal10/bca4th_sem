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
    header('Location: add_candidates.php');
    exit;
}

// Fetch candidate from DB
$stmt = $conn->prepare("SELECT * FROM candidates WHERE id=?");

$stmt->bind_param('i', $id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();

if (!$c) {
    header('Location: add_candidates.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $party = $_POST['party'] ?? '';
    $position = $_POST['position'] ?? '';
    $photo = $c['photo']; // keep current photo if not changed

    // Handle photo upload
    if (!empty($_FILES['photo']['name'])) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = time() . "." . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/uploads/' . $photo);
    }

    // Update candidate in DB
    $stmt = $conn->prepare("UPDATE candidates SET name=?, party=?, position=?, photo=? WHERE id=?");
    $stmt->bind_param('ssssi', $name, $party, $position, $photo, $id);
    $stmt->execute();

    // Redirect to Add Candidates page after update
    header('Location: add_candidates.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Candidate</title>
<style>
body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
form { background: #fff; padding: 20px; border-radius: 6px; max-width: 500px; }
input[type=text], input[type=file] { width: 100%; padding: 8px; margin: 8px 0; }
button { padding: 10px 20px; background: #4CAF50; color: #fff; border: none; border-radius: 5px; cursor: pointer; }
button:hover { background: #45a049; }
img { margin-top: 10px; border-radius: 4px; }
</style>
</head>
<body>

<h2>Edit Candidate</h2>
<form method="post" enctype="multipart/form-data">
    Name: <input type="text" name="name" value="<?= h($c['name']) ?>" required><br>
    Party: <input type="text" name="party" value="<?= h($c['party']) ?>"><br>
    Position: <input type="text" name="position" value="<?= h($c['position']) ?>" required><br>
    Current Photo: <?php if ($c['photo']) echo "<br><img src='uploads/".h($c['photo'])."' width='100'>"; ?><br>
    Change Photo: <input type="file" name="photo" accept="image/*"><br><br>
    <button type="submit">Save Changes</button>
</form>

</body>
</html>
