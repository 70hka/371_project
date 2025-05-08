<?php
require_once 'connect.php';
require_once 'auth.php';
require_login('student');

$student_id = $_SESSION['user_id'];

// Fetch student's appointment
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

        <div class="card p-4 shadow-sm mb-4">
            <h5 class="card-title mb-3"><?= htmlspecialchars($appointment['project_name']) ?></h5>
            <p><strong>Date:</strong> <?= $appointment['available_date'] ?> @ <?= $appointment['available_time'] ?></p>
            <p><strong>Group ID:</strong> <?= htmlspecialchars($appointment['group_id']) ?></p>

            <form action="delete_appointment.php" method="post" class="mt-3">
                <input type="hidden" name="appointment_id" value="<?= $appointment['appointment_id'] ?>">
                <button type="submit" class="btn btn-danger">Delete Appointment</button>
            </form>
        </div>

    <?php else: ?>
        <div class="alert alert-info mb-4">
            You have no current appointment.
        </div>
        <a href="book_appointment.php" class="btn btn-primary mb-4">Book an Appointment</a>
    <?php endif; ?>

    <!-- Group Info Section -->
    <h2 class="mb-3">My Group</h2>
    <?php
    // Get group ID, name, and leader ID
    $groupQuery = $conn->prepare("
        SELECT sg.group_id, sg.group_name, sg.leader_id
        FROM StudentGroupMembers gm
        JOIN StudentGroup sg ON gm.group_id = sg.group_id
        WHERE gm.user_id = ?
    ");
    $groupQuery->bind_param("i", $student_id);
    $groupQuery->execute();
    $groupResult = $groupQuery->get_result();

    if ($groupRow = $groupResult->fetch_assoc()) {
        $group_id = $groupRow['group_id'];
        $group_name = $groupRow['group_name'];
        $leader_id = $groupRow['leader_id'];

        echo "<p><strong>Group Name:</strong> " . htmlspecialchars($group_name) . "</p>";

        // Get all members in the group
        $membersStmt = $conn->prepare("
            SELECT u.username, u.user_id
            FROM StudentGroupMembers gm
            JOIN Users u ON gm.user_id = u.user_id
            WHERE gm.group_id = ?
        ");
        $membersStmt->bind_param("i", $group_id);
        $membersStmt->execute();
        $membersResult = $membersStmt->get_result();

        echo "<ul class='list-group'>";
        while ($member = $membersResult->fetch_assoc()) {
            $isLeader = $member['user_id'] == $leader_id;
            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
            echo htmlspecialchars($member['username']);
            if ($isLeader) {
                echo "<span class='badge bg-primary'>Leader</span>";
            }
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<div class='alert alert-warning mt-3'>You are not in a group.</div>";
    }
    ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
