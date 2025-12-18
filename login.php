<?php
session_start();

// -------------------------
// 1. Database connection
// -------------------------
$conn = new mysqli("localhost", "root", "", "voting_system");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -------------------------
// 2. Get POST values
// -------------------------
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// -------------------------
// 3. Check empty fields
// -------------------------
if (empty($email) || empty($password)) {
    header("Location: login.html?error=fill_fields"); // redirect back to login form
    exit;
}

// -------------------------
// 4. Prepare SQL to fetch user by email
// -------------------------
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// -------------------------
// 5. Check if user exists
// -------------------------
if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // -------------------------
    // 6. Verify password
    // -------------------------
    if (password_verify($password, $user['password'])) {
        // -------------------------
        // 7. Set session variables
        // -------------------------
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];

        // -------------------------
        // 8. Redirect based on role
        // -------------------------
        if ($user['role'] === "Admin") {
            header("Location: AdminDashboard.php"); // admin dashboard
            exit;
        } else {
            header("Location: userDashboard.php"); // voter dashboard
            exit;
        }

    } else {
        // Wrong password
        header("Location: login.html?error=incorrect_password");
        exit;
    }

} else {
    // Email not found
    header("Location: login.html?error=invalid_email");
    exit;
}

// -------------------------
// 9. Close connection
// -------------------------
$stmt->close();
$conn->close();
?>
