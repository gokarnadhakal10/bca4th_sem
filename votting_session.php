<?php
require "config.php";
require "auth.php";
admin_required();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST['action'] ?? '';
    $start  = $_POST['start'] ?? null;
    $end    = $_POST['end'] ?? null;

    // Convert datetime-local to MySQL format
    if ($start) {
        $start = date("Y-m-d H:i:s", strtotime($start));
    }
    if ($end) {
        $end = date("Y-m-d H:i:s", strtotime($end));
    }

    switch ($action) {

        /* ================= START VOTING ================= */
        case "start":
            $stmt = $conn->prepare("
                UPDATE voting_session 
                SET start_time = ?, end_time = ?, status = 'Active'
                WHERE id = 1
            ");
            $stmt->bind_param("ss", $start, $end);
            $stmt->execute();
            break;

        /* ================= PAUSE VOTING ================= */
        case "pause":
            $conn->query("
                UPDATE voting_session 
                SET status = 'Paused'
                WHERE id = 1 AND status = 'Active'
            ");
            break;

        /* ================= RESUME VOTING ================= */
        case "resume":
            $conn->query("
                UPDATE voting_session 
                SET status = 'Active'
                WHERE id = 1 AND status = 'Paused'
            ");
            break;

        /* ================= END VOTING ================= */
        case "end":
            $conn->query("
                UPDATE voting_session 
                SET status = 'Ended'
                WHERE id = 1
            ");
            break;
    }

    // Redirect back to dashboard
    header("Location: AdminDashboard.php");
    exit;
}
?>
