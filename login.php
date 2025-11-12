<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data safely
$email = $_POST["email"] ?? '';
$password = $_POST["password"] ?? '';
$role = $_POST["role"] ?? '';

// Validation
if (empty($email) || empty($password) || empty($role)) {
    echo "<p style='color:red;'>Please fill in all fields.</p>";
    echo "<p><a href='login.html'>Go back</a></p>";
    exit;
}

// Prepare statement to find user (case-insensitive for role)
$stmt = $conn->prepare("SELECT * FROM user WHERE email=? AND LOWER(role)=LOWER(?)");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // ✅ Plain password check
    if ($password === $user['password']) {
        $_SESSION["email"] = $user['email'];
        $_SESSION["role"] = strtolower($user['role']);
        $_SESSION["name"] = $user['name'];

        // Redirect based on role
        if ($_SESSION['role'] === "admin") {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: voter_dashboard.php");
        }
        exit;
    } else {
        echo "<p style='color:red;'>Incorrect password.</p>";
        echo "<p><a href='login.html'>Try again</a></p>";
    }
} else {
    echo "<p style='color:red;'>Invalid email or role.</p>";
    echo "<p><a href='login.html'>Try again</a></p>";
}

$conn->close();
?>
