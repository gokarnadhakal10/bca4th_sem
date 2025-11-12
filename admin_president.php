<?php
session_start();

// ✅ Only allow admin to access this page
if (!isset($_SESSION['email']) || $_SESSION['email'] != 'admin@gmail.com') {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "voting");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$position = 'president'; // Fixed position for College President

// ✅ Add new candidate
if (isset($_POST['add'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $conn->query("INSERT INTO candidates (name, position) VALUES ('$name', '$position')");
    }
    header("Location: admin_president.php");
    exit;
}

// ✅ Delete candidate
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM candidates WHERE id=$id");
    header("Location: admin_president.php");
    exit;
}

// ✅ Edit candidate
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $conn->query("UPDATE candidates SET name='$name' WHERE id=$id");
    header("Location: admin_president.php");
    exit;
}

// ✅ Fetch all candidates for president
$result = $conn->query("SELECT * FROM candidates WHERE position='$position'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Panel - College President</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #4CAF50, #2e8b57);
    color: white;
    text-align: center;
    margin: 0;
    padding: 40px;
}
.container {
    background: white;
    color: #333;
    border-radius: 12px;
    padding: 25px;
    max-width: 800px;
    margin: auto;
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
}
h1 {
    color: #2e7d32;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    border-bottom: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}
th {
    background-color: #4CAF50;
    color: white;
}
input[type="text"] {
    padding: 8px;
    width: 60%;
    border: 1px solid #ccc;
    border-radius: 6px;
}
button {
    background-color: #4CAF50;
    border: none;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    cursor: pointer;
}
button:hover {
    background-color: #45a049;
}
form {
    margin: 0;
}
.add-form {
    margin-top: 30px;
}
</style>
</head>
<body>

<div class="container">
    <h1>Admin Panel – Manage College President Candidates</h1>

    <table>
        <tr>
            <th>ID</th>
            <th>Candidate Name</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
            </td>
            <td>
                    <button type="submit" name="edit">Update</button>
                    <a href="?delete=<?php echo $row['id']; ?>">
                        <button type="button" style="background-color:red;">Delete</button>
                    </a>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>

    <div class="add-form">
        <h3>Add New Candidate</h3>
        <form method="POST">
            <input type="text" name="name" placeholder="Enter candidate name" required>
            <button type="submit" name="add">Add Candidate</button>
        </form>
    </div>
</div>

</body>
</html>
