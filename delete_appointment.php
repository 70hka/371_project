<?php
require_once 'connect.php';
require_once 'auth.php';
require_login('student');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $student_id = $_SESSION['user_id'];

    // First, get the availability_id linked to this appointment
    $stmt = $conn->prepare("SELECT availability_id FROM Appointments WHERE appointment_id = ? AND booked_by = ?");
    $stmt->bind_param("ii", $appointment_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $availability_id = $row['availability_id'];

        // Delete the appointment
        $del = $conn->prepare("DELETE FROM Appointments WHERE appointment_id = ?");
        $del->bind_param("i", $appointment_id);
        $del->execute();

        // Set the slot back to available
        $update = $conn->prepare("UPDATE InstructorAvailability SET is_booked = 0 WHERE availability_id = ?");
        $update->bind_param("i", $availability_id);
        $update->execute();
    }
}

header("Location: studentdash.php");
exit;
