<?php
require 'config.php';
require 'auth.php';

admin_required();

$id = $_GET['id'];

// Fetch voter data
$result = $conn->query("SELECT * FROM users WHERE id=$id");
$voter = $result->fetch_assoc();

if (isset($_POST['update'])) {
    $name   = $_POST['name'];
    $email  = $_POST['email'];
    $mobile = $_POST['mobile'];

    $conn->query("UPDATE users SET 
        name='$name',
        email='$email',
        mobile='$mobile'
        WHERE id=$id
    ");

    header("Location: voters.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Voter</title>

<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
}

.container {
    width: 400px;
    margin: 80px auto;
    background: #fff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

label {
    font-weight: bold;
}

input[type="text"],
input[type="email"] {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

button {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.update-btn {
    background: #28a745;
    color: #fff;
}

.back-btn {
    background: #6c757d;
    color: #fff;
    text-decoration: none;
    padding: 10px 15px;
    border-radius: 5px;
    margin-left: 10px;
}

.buttons {
    text-align: center;
}
</style>

</head>
<body>

<div class="container">
    <h2>Edit Section</h2>

    <form method="post">
        <label>Name</label>
        <input type="text" name="name" value="<?php echo $voter['name']; ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?php echo $voter['email']; ?>" required>

        <label>Mobile</label>
        <input type="text" name="mobile" value="<?php echo $voter['mobile']; ?>" required>

        <div class="buttons">
            <button type="submit" name="update" class="update-btn">Update</button>
            <a href="adminDashboard.php" class="back-btn">Back</a>
        </div>
    </form>
</div>

</body>
</html>
