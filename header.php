<?php
require_once 'auth.php';
require_login();
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="navbar">
    <div class="d-flex align-items-center gap-3">
        <img src="logo.png" alt="Logo" class="logo">
        <span class="fw-bold">
            Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)
        </span>
    </div>

    <div class="d-flex align-items-center gap-2">
        <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === 'student'): ?>
                <a href="studentdash.php" class="btn btn-outline-light">My Dashboard</a>
            <?php elseif ($_SESSION['role'] === 'instructor'): ?>
                <?php if ($currentPage === 'InstructorSchedule.php'): ?>
                    <a href="instructordash.php" class="btn btn-outline-light">Return to Dashboard</a>
                <?php else: ?>
                    <a href="InstructorSchedule.php" class="btn btn-outline-light">Schedule Appointment</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-light">Logout</a>
    </div>
</div>
