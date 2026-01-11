<?php
session_start();
require "config.php";
require "auth.php";
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $action = $_POST['action'];
    
    if ($action === 'accept') {
        // Get request details
        $stmt = $conn->prepare("SELECT * FROM candidate_requests WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
        
        if ($request) {
            // Get user details (Faculty/Class)
            $u_stmt = $conn->prepare("SELECT faculty, class FROM users WHERE id=?");
            $u_stmt->bind_param("i", $request['voter_id']);
            $u_stmt->execute();
            $user = $u_stmt->get_result()->fetch_assoc();

            // Find the lowest available ID (Gap Detection)
            $id_result = $conn->query("SELECT id FROM candidates ORDER BY id ASC");
            $next_id = 1;
            while ($row = $id_result->fetch_assoc()) {
                if ($row['id'] == $next_id) {
                    $next_id++;
                } else {
                    break;
                }
            }

            // Insert as candidate
            $insert = $conn->prepare("INSERT INTO candidates (id, name, position, party_name, party_image, faculty, class, photo, platform) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param("issssssss", $next_id, $request['candidate_name'], $request['position'], $request['party'], $request['party_image'], $user['faculty'], $user['class'], $request['photo'], $request['vision']);
            $insert->execute();
            
            // Update request status
            $update = $conn->prepare("UPDATE candidate_requests SET status='approved' WHERE id=?");
            $update->bind_param("i", $id);
            $update->execute();
            
            $_SESSION['message'] = "Candidate request approved!";
        }
    } elseif ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        $stmt = $conn->prepare("UPDATE candidate_requests SET status='rejected', rejection_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $id);
        $stmt->execute();
        $_SESSION['message'] = "Candidate request rejected!";
    }
    
    header("Location: AdminDashboard.php");
    exit();
}
?>