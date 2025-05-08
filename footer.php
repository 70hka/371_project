<<<<<<< HEAD
<footer class="bg-primary text-white py-3 mt-auto">
    <div class="container d-flex justify-content-between align-items-center">
        <span>© <?= date("Y") ?> Project Scheduler</span>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <a class="text-white text-decoration-none" href="InstructorSchedule.php">Schedule Appointment</a>
=======
<footer class="footer navbar">
    <div class="container d-flex justify-content-between align-items-center">
        <span>© <?= date("Y") ?> Student Project Scheduler</span>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor'): ?>
            <a class="nav-link" href="InstructorSchedule.php">Schedule Appointment</a>
>>>>>>> 560f91e7b14ed6b83c278e855a41b7bf72c42cdc
        <?php endif; ?>
    </div>
</footer>
