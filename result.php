<?php
require "config.php";
require "auth.php";
admin_required(); // Only admin can see results

// Fetch voting session
$session = $conn->query("SELECT * FROM voting_session WHERE id=1")->fetch_assoc();

// Block access if voting not ended
if (!$session || $session['status'] !== 'Ended') {
    echo "<h2 style='text-align:center;margin-top:100px;color:red;'>
            Results are available only after voting ends.
          </h2>";
    exit;
}

// Fetch positions
$positions = $conn->query("SELECT DISTINCT position FROM candidates");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Election Results</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #4361ee;
        }
        .position-box {
            background: white;
            padding: 20px;
            margin: 30px auto;
            width: 90%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #4361ee;
            color: white;
        }
        .rank-1 {
            background: #d4edda;
            font-weight: bold;
        }
        .rank-2 {
            background: #fff3cd;
        }
        .rank-3 {
            background: #f8d7da;
        }
        .winner {
            text-align: center;
            font-size: 20px;
            color: green;
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<h1>🏆 Election Results</h1>

<?php while ($pos = $positions->fetch_assoc()): ?>
<?php
    // Fetch candidates by position with vote count
    $stmt = $conn->prepare("
        SELECT c.id, c.name, c.party,
        COUNT(v.id) AS votes
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id
        WHERE c.position = ?
        GROUP BY c.id
        ORDER BY votes DESC
        LIMIT 3
    ");
    $stmt->bind_param("s", $pos['position']);
    $stmt->execute();
    $results = $stmt->get_result();
?>
<div class="position-box">
    <h2>Position: <?= htmlspecialchars($pos['position']) ?></h2>

    <table>
        <tr>
            <th>Rank</th>
            <th>Candidate Name</th>
            <th>Party</th>
            <th>Total Votes</th>
        </tr>

        <?php
        $rank = 1;
        $winnerName = "";
        while ($row = $results->fetch_assoc()):
            $class = $rank == 1 ? "rank-1" : ($rank == 2 ? "rank-2" : "rank-3");
            if ($rank == 1) $winnerName = $row['name'];
        ?>
        <tr class="<?= $class ?>">
            <td><?= $rank ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['party']) ?></td>
            <td><?= $row['votes'] ?></td>
        </tr>
        <?php $rank++; endwhile; ?>
    </table>

    <?php if($winnerName): ?>
    <div class="winner">
        🎉 Congratulations <strong><?= htmlspecialchars($winnerName) ?></strong> for winning
        the position of <strong><?= htmlspecialchars($pos['position']) ?></strong>!
    </div>
    <?php endif; ?>

</div>
<?php endwhile; ?>

</body>
</html>
