<?php
session_start();

function require_login($required_role = null) {
    // Set session from cookies if needed
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
        $_SESSION['username'] = $_COOKIE['username'];
        $_SESSION['role'] = $_COOKIE['role'];
        $_SESSION['email'] = $_COOKIE['email'];
    }

    // If no user ID send to login
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // If role is required and doesn't match, redirect to proper dashboard
    if ($required_role !== null && $_SESSION['role'] !== $required_role) {
        if ($_SESSION['role'] === 'student') {
            header("Location: studentdash.php");
        } elseif ($_SESSION['role'] === 'instructor') {
            header("Location: instructordash.php");
        } else {
            header("Location: login.php"); // fallback condition
        }
        exit;
    }
}
?>
