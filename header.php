<?php
require_once 'auth.php';
require_login();
?>

<<<<<<< HEAD
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
=======
<div class="navbar">
    <img src="logo.png" alt="Logo" class="logo">
    <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?> (<?= htmlspecialchars($_SESSION['email']) ?>)</span>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor'): ?>
        <a class="nav-link" href="InstructorSchedule.php">Schedule Appointment</a>
    <?php endif; ?>

    <a href="logout.php" class="logout-link">Logout</a>
>>>>>>> 560f91e7b14ed6b83c278e855a41b7bf72c42cdc
</div>
