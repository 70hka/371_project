<footer class="footer">
    <div class="container d-flex justify-content-between align-items-center">
        <span>© <?= date("Y") ?> Student Project Scheduler</span>

        <?php if (isset($_SESSION['role'])): ?>
            <?php if ($_SESSION['role'] === 'student'): ?>
                <a class="text-white text-decoration-none" href="studentdash.php">My Dashboard</a>
            <?php elseif ($_SESSION['role'] === 'instructor'): ?>
                <?php if (basename($_SERVER['PHP_SELF']) === 'groupeditor.php'): ?>
                    <a class="text-white text-decoration-none" href="instructordash.php">Return to Dashboard</a>
					<a class="text-white text-decoration-none" href="InstructorSchedule.php">Schedule Appointments</a>
				<?php elseif (basename($_SERVER['PHP_SELF']) === 'InstructorSchedule.php'): ?>
					<a class="text-white text-decoration-none" href="instructordash.php">Return to Dashboard</a>
					<a class="text-white text-decoration-none" href="groupeditor.php">Edit Groups</a>
                <?php else: ?>
					<a class="text-white text-decoration-none" href="InstructorSchedule.php">Schedule Appointments</a>
                    <a class="text-white text-decoration-none" href="groupeditor.php">Edit Groups</a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</footer>
