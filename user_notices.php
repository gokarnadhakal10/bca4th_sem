<?php
session_start();
require "config.php";

if(!isset($_SESSION['voter_id'])){
    header("Location: login.php");
    exit;
}

$voter_id = $_SESSION['voter_id'];

// Fetch voter info
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $voter_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();

// Fetch notices
$current_date = date('Y-m-d H:i:s');
$notices = $conn->query("
    SELECT * FROM notices 
    WHERE is_active=1 
    AND (expires_at IS NULL OR expires_at > '$current_date') 
    ORDER BY published_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notice Board | User Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root { --primary: #667eea; --secondary: #764ba2; --card-bg: #ffffff; --text-dark: #333; --border: #e0e0e0; }
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }

.main-container { max-width: 1200px; margin: 120px auto; padding: 20px; }

.notice-card {
    background: white;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 20px;
    border-left: 5px solid var(--primary);
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.notice-meta {
    font-size: 13px;
    color: #888;
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
}

.badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 11px;
    background: #eee;
}

.not-published {
    color: gray;
    font-style: italic;
}
</style>
</head>
<body>

<div class="main-container">
    <h2 style="color:white; margin-bottom:20px;">Campus Notices</h2>

    <div style="margin-bottom:20px;">
    <a href="userDashboard.php" 
       style="
            background:white;
            padding:8px 15px;
            border-radius:6px;
            text-decoration:none;
            font-weight:600;
            color:#667eea;
            box-shadow:0 2px 6px rgba(0,0,0,0.1);
       ">
        Back to Dashboard
    </a>
</div>

<?php if($notices->num_rows > 0): ?>
    <?php while($row = $notices->fetch_assoc()): ?>

        <?php
            // SAFE DATE FIX
            if (!empty($row['published_at'])) {
                $published_date = date('M d, Y', strtotime($row['published_at']));
            } else {
                $published_date = null;
            }
        ?>

        <div class="notice-card">
            <div class="notice-meta">
                <span>
                    <i class="far fa-calendar"></i>
                    <?php if($published_date): ?>
                        <?= $published_date ?>
                    <?php else: ?>
                        <span class="not-published">Not Published</span>
                    <?php endif; ?>
                </span>

                <span class="badge">
                    <?= htmlspecialchars($row['category']) ?>
                </span>
            </div>

            <h3 style="margin-bottom:10px; color: var(--primary);">
                <?= htmlspecialchars($row['title']) ?>
            </h3>

            <p style="color:#555; line-height:1.6;">
                <?= nl2br(htmlspecialchars($row['content'])) ?>
            </p>
        </div>

    <?php endwhile; ?>
<?php else: ?>
    <div class="notice-card" style="text-align:center; color:#888;">
        No active notices found.
    </div>
<?php endif; ?>

</div>

</body>
</html>