<footer class="footer navbar">
    <div class="container d-flex justify-content-between align-items-center">
        <span>© <?= date("Y") ?> Student Project Scheduler</span>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'instructor'): ?>
            <a class="nav-link" href="InstructorSchedule.php">Schedule Appointment</a>
        <?php endif; ?>
    </div>
</footer>
