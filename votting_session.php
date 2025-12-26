






<?php
require 'config.php';
require 'auth.php';

admin_required();

$action = $_POST['action'] ?? '';
$start  = $_POST['start'] ?? null;
$end    = $_POST['end'] ?? null;

switch ($action) {

    case 'start':
        $stmt = $conn->prepare(
            "UPDATE voting_session 
             SET start_time=?, end_time=?, status='active',
                 message='Voting has started'
             WHERE id=1"
        );
        $stmt->bind_param('ss', $start, $end);
        $stmt->execute();
        break;

    case 'pause':
        $conn->query(
            "UPDATE voting_session 
             SET status='inactive',
                 message='Voting paused temporarily'
             WHERE id=1"
        );
        break;

    case 'resume':
        $conn->query(
            "UPDATE voting_session 
             SET status='active',
                 message='Voting resumed'
             WHERE id=1"
        );
        break;

    case 'end':
        $conn->query(
            "UPDATE voting_session 
             SET status='ended',
                 message='Voting has ended'
             WHERE id=1"
        );
        break;
}

header('Location: AdminDashboard.php');
exit;
