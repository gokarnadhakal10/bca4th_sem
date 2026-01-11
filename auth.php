<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function admin_required() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}

function voter_required() {
    if (!isset($_SESSION['voter_id'])) {
        header("Location: login.php");
        exit();
    }
}

function get_current_user_id() {
    if (isset($_SESSION['admin_id'])) {
        return $_SESSION['admin_id'];
    } elseif (isset($_SESSION['voter_id'])) {
        return $_SESSION['voter_id'];
    }
    return null;
}

function is_admin() {
    return isset($_SESSION['admin_id']);
}

function is_voter() {
    return isset($_SESSION['voter_id']);
}
?>