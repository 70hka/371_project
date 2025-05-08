<footer class="footer">
    <div class="container d-flex justify-content-between align-items-center">
        <span>© <?= date("Y") ?> Student Project Scheduler</span>

        <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === 'student'): ?>
                <a class="text-white text-decoration-none" href="studentdash.php">My Dashboard</a>
            <?php elseif ($_SESSION['role'] === 'instructor'): ?>
                <a class="text-white text-decoration-none" href="InstructorSchedule.php">Schedule Appointment</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</footer>
