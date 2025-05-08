<?php
require_once 'connect.php';
require_once 'auth.php';
require_login('student');

$student_id = $_SESSION['user_id'];
$group_id = null;
$is_leader = false;

// Check if this student is a group leader
$groupQuery = $conn->prepare("SELECT group_id FROM StudentGroup WHERE leader_id = ?");
$groupQuery->bind_param("i", $student_id);
$groupQuery->execute();
$groupResult = $groupQuery->get_result();

if ($group = $groupResult->fetch_assoc()) {
    $group_id = $group['group_id'];
    $is_leader = true;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && $is_leader) {
    $project_name = $_POST['project_name'];
    $availability_id = $_POST['availability_id'];

    $stmt = $conn->prepare("INSERT INTO Appointments (availability_id, project_name, booked_by, group_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isii", $availability_id, $project_name, $student_id, $group_id);
    $stmt->execute();

    $update = $conn->prepare("UPDATE InstructorAvailability SET is_booked = 1 WHERE availability_id = ?");
    $update->bind_param("i", $availability_id);
    $update->execute();

    header("Location: studentdash.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book an Appointment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4">Book an Appointment</h1>

    <?php if ($is_leader): ?>
        <form method="POST" class="card p-4 shadow-sm" id="bookingForm">
            <div class="mb-3">
                <label for="project_name" class="form-label">Project Name</label>
                <input type="text" name="project_name" id="project_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="selected_date" class="form-label">Select a Date</label>
                <input type="date" name="selected_date" id="selected_date" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="availability_id" class="form-label">Available Times</label>
                <select name="availability_id" id="availability_id" class="form-select" required>
                    <option value="">Select a date first</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Book Appointment</button>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">
            Only group leaders can book appointments.
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<!-- JavaScript to load times dynamically -->
<script>
document.getElementById('selected_date').addEventListener('change', function () {
    const selectedDate = this.value;
    const dropdown = document.getElementById('availability_id');

    dropdown.innerHTML = '<option>Loading...</option>';

    fetch('get_times.php?date=' + selectedDate)
        .then(response => response.json())
        .then(data => {
            dropdown.innerHTML = '';
            if (data.length > 0) {
                data.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.availability_id;
                    option.textContent = `${slot.available_time} with ${slot.instructor}`;
                    dropdown.appendChild(option);
                });
            } else {
                const option = document.createElement('option');
                option.text = 'No available times for this date';
                option.disabled = true;
                dropdown.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error fetching times:', error);
            dropdown.innerHTML = '<option>Error loading times</option>';
        });
});
</script>

</body>
</html>
