<?php
require_once 'auth.php';
require_login();
?>

<div class="navbar">
    <img src="logo.png" alt="Logo" class="logo">
    <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)</span>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor'): ?>
        <a class="nav-link" href="InstructorSchedule.php">Schedule Appointment</a>
    <?php endif; ?>

    <a href="logout.php" class="logout-link">Logout</a>
</div>
