<footer class="bg-primary text-white py-3 mt-auto">
    <div class="container d-flex justify-content-between align-items-center">
        <span>© <?= date("Y") ?> Project Scheduler</span>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
            <a class="text-white text-decoration-none" href="InstructorSchedule.php">Schedule Appointment</a>
        <?php endif; ?>
    </div>
</footer>
