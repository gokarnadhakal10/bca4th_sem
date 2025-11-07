<!-- <?php
// Start session
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "voting_system");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$email = $_POST["email"];
$password = password_hash ($_POST["password"],PASSWORD_DEFAULT);
$role = $_POST["role"];

// Validation
if (empty($email) || empty($password)) {
    echo "Please fill in all fields.<br>";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format.<br>";
} else {
    // Check if email, password, and role exist in the database
    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password' AND role='$role'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Store session variables
        $_SESSION["email"] = $email;
        $_SESSION["role"] = $role;

        echo "Login Successful!<br>";
        echo "Welcome, " . htmlspecialchars($email) . "<br>";

        // Redirect based on role (optional)
        if ($role == "Admin") {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit;
    } else {
        echo "Invalid email, password, or role.<br>";
    }
}

$conn->close();
?>

 -->



 

 <?php
// Start session
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
    echo "Please fill in all fields.<br>";
    exit;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format.<br>";
    exit;
}

// Prepare statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM user WHERE email=? AND role=?");
$stmt->bind_param("ss", $email, $role);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verify password against hashed password in database
    if (password_verify($password, $user['password'])) {
        // Store session variables
        $_SESSION["email"] = $email;
        $_SESSION["role"] = $role;

        // Redirect based on role
        if ($role === "admin") {
            header("Location: admin_dashboard.php");
        } else { // voter
            header("Location: voter_dashboard.php");
        }
        exit;
    } else {
        echo "<p style='color:red;'>Incorrect password.</p>";
        echo "<p><a href='login.html'>Go back to login</a></p>";
    }
} else {
    echo "<p style='color:red;'>Invalid email or role.</p>";
    echo "<p><a href='login.html'>Go back to login</a></p>";
}

$conn->close();
?>
