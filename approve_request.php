<?php
session_start();
require "config.php";
require "auth.php";
admin_required();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'accept' && $id > 0) {

        // 1️⃣ Get request data from database
        $stmt = $conn->prepare("SELECT * FROM candidate_requests WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();

        if ($request) {

            // 2️⃣ Get faculty and class from users table
            $u_stmt = $conn->prepare("SELECT faculty, class FROM users WHERE id=?");
            $u_stmt->bind_param("i", $request['voter_id']);
            $u_stmt->execute();
            $user = $u_stmt->get_result()->fetch_assoc();

            // 3️⃣ Insert into candidates table
            $insert = $conn->prepare("
                INSERT INTO candidates
                (name, position, party_name, party_image, faculty, class, photo, platform)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insert->bind_param(
                "ssssssss",
                $request['candidate_name'],
                $request['position'],
                $request['party_name'],
                $request['party_image'],
                $user['faculty'],
                $user['class'],
                $request['photo'],
                $request['vision']
            );

            if ($insert->execute()) {

                // 4️⃣ Update request status
                $update = $conn->prepare("UPDATE candidate_requests SET status='approved' WHERE id=?");
                $update->bind_param("i", $id);
                $update->execute();

                $_SESSION['message'] = "Candidate approved successfully!";
            } else {
                $_SESSION['error'] = "Insert failed: " . $conn->error;
            }
        }
    }

    elseif ($action === 'reject' && $id > 0) {

        $reason = trim($_POST['reason'] ?? '');

        $stmt = $conn->prepare("UPDATE candidate_requests SET status='rejected', rejection_reason=? WHERE id=?");
        $stmt->bind_param("si", $reason, $id);
        $stmt->execute();

        $_SESSION['message'] = "Candidate rejected!";
    }

    header("Location: AdminDashboard.php");
    exit();
}
?>