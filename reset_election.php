<?php
require "config.php";
require "auth.php";
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_reset'])) {
    $reset_type = $_POST['reset_type'] ?? 'none';

    // Function to delete files
    function delete_upload($filename) {
        if (!empty($filename) && file_exists("uploads/" . $filename)) {
            unlink("uploads/" . $filename);
        }
    }

    // --- Always reset election-specific data ---

    // 1. Get file paths from candidates and requests before deleting records
    $files_to_delete = [];
    $cand_files = $conn->query("SELECT photo, party_image FROM candidates");
    while($row = $cand_files->fetch_assoc()) {
        $files_to_delete[] = $row['photo'];
        $files_to_delete[] = $row['party_image'];
    }
    $req_files = $conn->query("SELECT photo, party_image FROM candidate_requests");
    while($row = $req_files->fetch_assoc()) {
        $files_to_delete[] = $row['photo'];
        $files_to_delete[] = $row['party_image'];
    }




    // // 2. Truncate election tables
    // $conn->query("TRUNCATE TABLE votes");
    // $conn->query("TRUNCATE TABLE candidates");
    // $conn->query("TRUNCATE TABLE candidate_requests");





    // Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$conn->query("TRUNCATE TABLE votes");
$conn->query("TRUNCATE TABLE candidates");
$conn->query("TRUNCATE TABLE candidate_requests");

// Enable back foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");







    // 3. Reset session statuses
    $conn->query("UPDATE voting_session SET status = 'Pending', start_time = NULL, end_time = NULL, results_published = 0 WHERE id = 1");
    $conn->query("UPDATE nomination_session SET status = 'Pending', start_time = NULL, end_time = NULL WHERE id = 1");

    // 4. Delete the collected files
    foreach (array_unique($files_to_delete) as $file) {
        if($file) delete_upload($file);
    }

    $message = "Election data (votes, candidates, requests) has been reset successfully.";

    // --- Handle full reset if selected ---
    if ($reset_type === 'full_reset') {
        // 1. Get user photos to delete
        $user_photos = [];
        $user_files = $conn->query("SELECT photo FROM users WHERE role != 'Admin'");
        while($row = $user_files->fetch_assoc()) {
            $user_photos[] = $row['photo'];
        }

        // 2. Delete non-admin users
        // This is a critical step. The "WHERE role != 'Admin'" clause ensures that admin accounts are NEVER deleted.
        $conn->query("DELETE FROM users WHERE role != 'Admin'");

        // 3. Delete user photos
        foreach (array_unique($user_photos) as $file) {
            if($file) delete_upload($file);
        }
        
        $message = "Complete system reset successful. All election data and non-admin users have been deleted.";
    }

    $_SESSION['message'] = $message;
    header("Location: AdminDashboard.php");
    exit();

} else {
    // If it's a GET request, show a confirmation page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Election Reset</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); max-width: 700px; width: 100%; border-top: 5px solid #e74c3c; }
        h1 { color: #e74c3c; margin-bottom: 10px; text-align: center; }
        .container > p { text-align: center; color: #555; margin-bottom: 30px; }
        .step { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .step h2 { font-size: 18px; color: #3f37c9; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .step h2 .num { background: #3f37c9; color: white; border-radius: 50%; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; }
        
        .radio-group label { display: block; background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
        .radio-group input[type="radio"] { display: none; }
        .radio-group input[type="radio"]:checked + label { border-color: #4361ee; background: #eef1ff; }
        .radio-group strong { color: #333; }
        .radio-group p { font-size: 14px; color: #666; margin: 5px 0 0; }

        .btn { padding: 12px 25px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; font-size: 16px; transition: all 0.3s; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .btn-danger:disabled { background: #f5c6cb; cursor: not-allowed; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-info { background: #3498db; color: white; }
        .btn-info:hover { background: #2980b9; }

        .confirmation-box { margin-top: 20px; padding: 15px; background: #fffbe6; border: 1px solid #ffe58f; border-radius: 8px; }
        .confirmation-box label { display: flex; align-items: center; gap: 10px; font-weight: bold; color: #d46b08; cursor: pointer; }
        .buttons { display: flex; justify-content: center; gap: 20px; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-redo"></i> Election Reset</h1>
        <p>This process will permanently delete data. Please proceed with caution.</p>

        <form method="POST">
            <div class="step">
                <h2><span class="num">1</span> Data Backup (Optional)</h2>
                <p style="color: #555; margin-bottom: 15px;">Before resetting, you can download a full report of the election data, including results and user lists. You can save this report as a PDF from your browser's print menu.</p>
                <a href="generate_report.php" target="_blank" class="btn btn-info" style="display: inline-flex; align-items: center; gap: 8px;"><i class="fas fa-download"></i> Download Full Report</a>
            </div>

            <div class="step">
                <h2><span class="num">2</span> Choose Reset Type</h2>
                <div class="radio-group">
                    <input type="radio" id="reset_election" name="reset_type" value="election_data" checked>
                    <label for="reset_election">
                        <strong>Reset Election Data Only</strong>
                        <p>This will delete all votes, candidates, and candidate requests. It will also reset the voting and nomination sessions. <strong>User accounts will NOT be deleted.</strong></p>
                    </label>

                    <input type="radio" id="full_reset" name="reset_type" value="full_reset">
                    <label for="full_reset">
                        <strong>Complete System Reset</strong>
                        <p>This performs a factory reset. It deletes all election data (as above) <strong>AND deletes all non-admin user accounts.</strong> Use this only when starting a completely new academic year.</p>
                    </label>
                </div>
            </div>

            <div class="confirmation-box">
                <label><input type="checkbox" id="confirm_checkbox"> I understand that this action is permanent and cannot be undone.</label>
            </div>

            <div class="buttons">
                <a href="AdminDashboard.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" name="confirm_reset" id="resetBtn" class="btn btn-danger" disabled>Reset Now</button>
            </div>
        </form>
    </div>
    <script>
        const checkbox = document.getElementById('confirm_checkbox');
        const resetBtn = document.getElementById('resetBtn');
        checkbox.addEventListener('change', function() {
            resetBtn.disabled = !this.checked;
        });
    </script>
</body>
</html>
<?php
    exit();
}
?>