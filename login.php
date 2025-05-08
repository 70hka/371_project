<?php
session_start();
require 'connect.php'; // Connect to the database

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($password === $user['password']) {

			// Set session vars
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

			// Set cookies for 7 days
            setcookie("user_id", $user['user_id'], time() + (86400 * 7), "/");
            setcookie("username", $user['username'], time() + (86400 * 7), "/");
            setcookie("role", $user['role'], time() + (86400 * 7), "/");
            setcookie("email", $user['email'], time() + (86400 * 7), "/");

            header("Location: " . ($user['role'] === 'instructor' ? "instructordash.php" : "studentdash.php"));
            exit;
        } else {
            $error = "Incorrect password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-box">
    <h2>Login</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit">Login</button>
    </form>
    <p style="color:red;"><?= $error ?></p>
</div>
</body>
</html>
