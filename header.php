<?php
require_once 'auth.php';
require_login();
?>

<!-- Custom Navbar -->
<div class="custom-navbar px-4 py-3 text-white">
    <div class="d-flex align-items-center gap-3">
        <img src="logo.png" alt="Logo" class="logo">
        <span class="fw-bold">
            Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)
        </span>
    </div>

    <div class="d-flex align-items-center gap-2">
        <?php if (isset ($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <a href="InstructorSchedule.php" class="btn btn-outline-light">Schedule Appointment</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn-light">Logout</a>
    </div>
</div>
