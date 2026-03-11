<?php
require "config.php";
require "auth.php";
admin_required();

// Fetch all data
$voting_session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();
$nomination_session = $conn->query("SELECT * FROM nomination_session WHERE id=1")->fetch_assoc();
$total_voters = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='Voter'")->fetch_assoc()['c'];
$total_candidates = $conn->query("SELECT COUNT(*) as c FROM candidates")->fetch_assoc()['c'];
$total_votes = $conn->query("SELECT COUNT(*) as c FROM votes")->fetch_assoc()['c'];

// Fetch results
$results_by_position = [];
$positions_res = $conn->query("SELECT DISTINCT position FROM candidates ORDER BY position ASC");
if ($positions_res) {
    while ($pos_row = $positions_res->fetch_assoc()) {
        $pos = $pos_row['position'];
        $stmt = $conn->prepare("
            SELECT c.name, c.party_name, COUNT(v.id) as vote_count
            FROM candidates c
            LEFT JOIN votes v ON c.id = v.candidate_id
            WHERE c.position = ?
            GROUP BY c.id
            ORDER BY vote_count DESC
        ");
        $stmt->bind_param("s", $pos);
        $stmt->execute();
        $results = $stmt->get_result();
        while($res_row = $results->fetch_assoc()){
            $results_by_position[$pos][] = $res_row;
        }
    }
}

// Fetch all voters
$voters_res = $conn->query("SELECT name, email, mobile, faculty, class, status FROM users WHERE role='Voter' ORDER BY name ASC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Election Data Report - <?= date('Y-m-d') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; line-height: 1.5; color: #333; }
        .container { max-width: 800px; margin: 20px auto; padding: 20px; }
        h1, h2, h3 { color: #1a1a2e; border-bottom: 2px solid #f0f2f5; padding-bottom: 10px; margin-top: 40px; margin-bottom: 20px; }
        h1 { text-align: center; border: none; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f8f9fa; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .summary-item { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .no-print { text-align: center; margin-bottom: 20px; display: flex; justify-content: center; gap: 10px; }
        .btn { padding: 10px 20px; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #4361ee; }
        .btn-secondary { background: #6c757d; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            .container { max-width: 100%; margin: 0; padding: 0; box-shadow: none; }
            h2, h3 { page-break-after: avoid; }
            table { page-break-inside: auto; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <a href="reset_election.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Reset Page</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print or Save as PDF</button>
        </div>

        <h1>Election Report</h1>
        <p style="text-align: center; color: #666;">Generated on: <?= date('F j, Y, g:i a') ?></p>

        <h2>Summary</h2>
        <div class="summary-grid">
            <div class="summary-item"><strong>Total Voters:</strong> <?= $total_voters ?></div>
            <div class="summary-item"><strong>Total Candidates:</strong> <?= $total_candidates ?></div>
            <div class="summary-item"><strong>Total Votes Cast:</strong> <?= $total_votes ?></div>
            <div class="summary-item"><strong>Voting Session Status:</strong> <?= htmlspecialchars($voting_session['status'] ?? 'N/A') ?></div>
        </div>

        <h2>Final Results</h2>
        <?php if (empty($results_by_position)): ?>
            <p>No results to display.</p>
        <?php else: ?>
            <?php foreach ($results_by_position as $position => $candidates): ?>
                <h3>Position: <?= htmlspecialchars($position) ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Candidate Name</th>
                            <th>Party</th>
                            <th>Vote Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; foreach ($candidates as $candidate): ?>
                        <tr>
                            <td><?= $rank++ ?></td>
                            <td><?= htmlspecialchars($candidate['name']) ?></td>
                            <td><?= htmlspecialchars($candidate['party_name']) ?></td>
                            <td><strong><?= $candidate['vote_count'] ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2>Registered Voters</h2>
        <?php if (!$voters_res || $voters_res->num_rows === 0): ?>
            <p>No voters registered.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Faculty</th><th>Class</th><th>Status</th></tr></thead>
                <tbody>
                    <?php while($voter = $voters_res->fetch_assoc()): ?>
                    <tr><td><?= htmlspecialchars($voter['name']) ?></td><td><?= htmlspecialchars($voter['email']) ?></td><td><?= htmlspecialchars($voter['faculty']) ?></td><td><?= htmlspecialchars($voter['class']) ?></td><td><?= htmlspecialchars($voter['status']) ?></td></tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>