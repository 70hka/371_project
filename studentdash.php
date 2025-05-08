<?php
require_once 'connect.php';
require_once 'auth.php';
require_login('student');

$student_id = $_SESSION['user_id'];

$sql = "SELECT a.appointment_id, a.project_name, a.group_id, ia.available_date, ia.available_time
        FROM Appointments a
        JOIN InstructorAvailability ia ON a.availability_id = ia.availability_id
        WHERE a.booked_by = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Student Dashboard</h1>

    <?php if ($result->num_rows > 0): 
        $appointment = $result->fetch_assoc(); ?>

        <div class="card p-4 shadow-sm">
            <h5 class="card-title mb-3"><?= htmlspecialchars($appointment['project_name']) ?></h5>
            <p><strong>Date:</strong> <?= $appointment['available_date'] ?> @ <?= $appointment['available_time'] ?></p>
            <p><strong>Group ID:</strong> <?= htmlspecialchars($appointment['group_id']) ?></p>

            <form action="delete_appointment.php" method="post" class="mt-3">
                <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">
                <button type="submit" class="btn btn-danger">Delete Appointment</button>
            </form>
        </div>

    <?php else: ?>
        <div class="alert alert-info">
            You have no current appointment.
        </div>
        <a href="book_appointment.php" class="btn btn-primary">Book an Appointment</a>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
