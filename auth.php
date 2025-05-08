<?php
session_start();

function require_login($required_role = null) {
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['username'] = $_COOKIE['username'];
        $_SESSION['role'] = $_COOKIE['role'];
        $_SESSION['email'] = $_COOKIE['email'];
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    if ($required_role !== null && $_SESSION['role'] !== $required_role) {
        header("Location: login.php");
        exit;
    }
}
?>
