<?php
require 'header.php';
require_login('instructor');
require 'connect.php';

$message = '';
$instructor_id = $_SESSION['user_id'];

// Handle deletion
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);

    // Check if this slot is booked
    $check = $conn->prepare("SELECT a.appointment_id, sg.group_name
        FROM Appointments a
        JOIN StudentGroup sg ON a.group_id = sg.group_id
        WHERE a.availability_id = ?");
    $check->bind_param("i", $deleteId);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($appointmentId, $groupName);
        $check->fetch();
        $check->close();

        // Delete the appointment
        $delApp = $conn->prepare("DELETE FROM Appointments WHERE appointment_id = ?");
        $delApp->bind_param("i", $appointmentId);
        $delApp->execute();
        $delApp->close();

        $message = "<div class='alert alert-warning'>
            Slot was booked by group <strong>$groupName</strong>. Appointment has been removed.
        </div>";
    } else {
        $check->close();
    }

    // delete the actual availability slot
    $stmt = $conn->prepare("DELETE FROM InstructorAvailability WHERE availability_id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $deleteId, $instructor_id);
    if ($stmt->execute()) {
        $message .= "<div class='alert alert-success'>Timeslot deleted.</div>";
    } else {
        $message .= "<div class='alert alert-danger'>Failed to delete slot.</div>";
    }
    $stmt->close();
}


// Handle save edited slot
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_id'])) {
    $edit_id = intval($_POST['edit_id']);
    $newDate = $_POST['edit_date'];
    $newTime = $_POST['edit_time'];
    $start = $newTime;
    $end = date("H:i:s", strtotime($newTime) + 20 * 60);

    $stmt = $conn->prepare("SELECT 1 FROM InstructorAvailability WHERE instructor_id = ? AND available_date = ? AND availability_id != ? AND TIME(?) < ADDTIME(available_time, '00:20:00') AND TIME(?) >= available_time LIMIT 1");
    $stmt->bind_param("isiis", $instructor_id, $newDate, $edit_id, $end, $start);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $message = "<div class='alert alert-danger'>This timeslot overlaps with another existing one.</div>";
    } else {
        $hour = date('H', strtotime($newTime));
        $stmt->close();

        $stmt = $conn->prepare("SELECT 1 FROM InstructorAvailability WHERE instructor_id = ? AND available_date = ? AND availability_id != ? AND TIME(?) < ADDTIME(available_time, '00:20:00') AND TIME(?) >= available_time LIMIT 1");
		$stmt->bind_param("issss", $instructor_id, $newDate, $edit_id, $start, $start);

        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count >= 3) {
            $message = "<div class='alert alert-danger'>Cannot update: already 3 slots in this hour.</div>";
        } else {
            $stmt = $conn->prepare("UPDATE InstructorAvailability SET available_date = ?, available_time = ? WHERE availability_id = ? AND instructor_id = ?");
            $stmt->bind_param("ssii", $newDate, $newTime, $edit_id, $instructor_id);
            if ($stmt->execute()) {
				$stmt->close();
				header("Location: InstructorSchedule.php?updated=1");
				exit;
			} else {
				$message = "<div class='alert alert-danger'>Failed to update timeslot.</div>";
				$stmt->close();
			}			
        }
    }
}

// Handle new slot creation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['date']) && isset($_POST['time']) && !isset($_POST['edit_id'])) {
    $date = $_POST['date'];
    $time = $_POST['time'];
    $start = $time;
    $end = date("H:i:s", strtotime($start) + 20 * 60);

    $stmt = $conn->prepare("SELECT available_time FROM InstructorAvailability WHERE instructor_id = ? AND available_date = ? AND TIME(?) < ADDTIME(available_time, '00:20:00') AND TIME(?) >= available_time ORDER BY available_time LIMIT 1");
	$stmt->bind_param("isss", $instructor_id, $date, $start, $start);


    $stmt->execute();
    $stmt->bind_result($conflict_time);
    $hasConflict = $stmt->fetch();
    $stmt->close();

    if ($hasConflict) {
        $current = strtotime($conflict_time) + 20 * 60;
        $suggested = null;

        while (!$suggested) {
            $next_start = date("H:i:s", $current);
            $next_end = date("H:i:s", $current + 20 * 60);

            $check = $conn->prepare("SELECT 1 FROM InstructorAvailability WHERE instructor_id = ? AND available_date = ? AND TIME(?) < ADDTIME(available_time, '00:20:00') AND TIME(?) >= available_time LIMIT 1");
			$check->bind_param("isss", $instructor_id, $date, $next_start, $next_start);


            $check->execute();
            $check->store_result();

            if ($check->num_rows === 0) {
                $suggested = date("H:i", $current);
            } else {
                $current += 20 * 60;
            }
            $check->close();
        }

        $message = "<div class='alert alert-danger'>This timeslot overlaps with an existing one. Next available time: <strong>$suggested</strong></div>";
    } else {
        $hour = date('H', strtotime($start));
        $stmt = $conn->prepare("SELECT COUNT(*) FROM InstructorAvailability WHERE instructor_id = ? AND available_date = ? AND HOUR(available_time) = ?");
        $stmt->bind_param("isi", $instructor_id, $date, $hour);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count >= 3) {
            $message = "<div class='alert alert-danger'>Only 3 timeslots are allowed per hour.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO InstructorAvailability (instructor_id, available_date, available_time, is_booked) VALUES (?, ?, ?, FALSE)");
            $stmt->bind_param("iss", $instructor_id, $date, $start);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Timeslot successfully created.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Error creating timeslot.</div>";
            }
            $stmt->close();
        }
    }
}

$slots = $conn->prepare("SELECT availability_id, available_date, available_time, is_booked FROM InstructorAvailability WHERE instructor_id = ? ORDER BY available_date, available_time");
$slots->bind_param("i", $instructor_id);
$slots->execute();
$result = $slots->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Schedule Appointment</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Create New Appointment Slot</h1>
    <?php if (!empty($message)): ?>
		<div style="max-width: fit-content; padding-right: 1.5rem;">
			<?= $message ?>
		</div>
	<?php endif; ?>

    <form method="POST" class="row g-3 mb-4 align-items-end">
        <div class="col-auto">
            <label for="date" class="form-label mb-1">Date</label>
            <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col-auto">
            <label for="time" class="form-label mb-1">Start Time</label>
            <input type="time" name="time" class="form-control" required>
        </div>
        <div class="col-auto d-flex align-items-end">
            <button type="submit" class="btn btn-primary btn-sm">Create Slot</button>
        </div>
    </form>
    <p class="text-muted" style="margin-top: -10px; margin-left: 10px;">Each slot will be 20 minutes</p>

    <?php if ($editSlot): ?>
        <hr>
        <h4 class="mt-5">Edit Timeslot #<?= $editSlot['availability_id'] ?></h4>
        <form method="POST" class="row g-3 mb-4 align-items-end">
            <input type="hidden" name="edit_id" value="<?= $editSlot['availability_id'] ?>">
            <div class="col-auto">
                <label class="form-label mb-1">Date</label>
                <input type="date" name="edit_date" class="form-control" value="<?= $editSlot['available_date'] ?>" required>
            </div>
            <div class="col-auto">
                <label class="form-label mb-1">Start Time</label>
                <input type="time" name="edit_time" class="form-control" value="<?= substr($editSlot['available_time'], 0, 5) ?>" required>
            </div>
            <div class="col-auto d-flex align-items-end">
                <button type="submit" class="btn btn-success btn-sm">Save Changes</button>
            </div>
        </form>
    <?php endif; ?>

    <h3>Existing Slots</h3>
    <?php if ($result->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
				<?php while ($row = $result->fetch_assoc()): ?>
					<?php if (isset($_GET['edit']) && $_GET['edit'] == $row['availability_id']): ?>
						<tr>
							<form method="POST">
								<input type="hidden" name="edit_id" value="<?= $row['availability_id'] ?>">
								<td>
									<input type="date" name="edit_date" class="form-control"
										value="<?= $row['available_date'] ?>" required>
								</td>
								<td>
									<input type="time" name="edit_time" class="form-control"
										value="<?= substr($row['available_time'], 0, 5) ?>" required>
								</td>
								<td>
								<?php
									if ($row['is_booked']) {
										// Lookup group name from Appointments
										$bookedStmt = $conn->prepare("
											SELECT sg.group_name
											FROM Appointments a
											JOIN StudentGroup sg ON a.group_id = sg.group_id
											WHERE a.availability_id = ?
											LIMIT 1
										");
										$bookedStmt->bind_param("i", $row['availability_id']);
										$bookedStmt->execute();
										$bookedStmt->bind_result($groupName);
										$bookedStmt->fetch();
										$bookedStmt->close();

										echo "<span class='text-danger'>Booked by: " . htmlspecialchars($groupName) . "</span>";
									} else {
										echo "<span class='text-success'>Available</span>";
									}
									?>
								</td>
								<td>
									<button type="submit" class="btn btn-sm btn-success">Save</button>
									<a href="InstructorSchedule.php" class="btn btn-sm btn-secondary">Cancel</a>
								</td>
							</form>
						</tr>
					<?php else: ?>
						<tr>
							<td><?= date("m/d/Y", strtotime($row['available_date'])) ?></td>
							<td><?= date("g:i A", strtotime($row['available_time'])) ?></td>
							<td>
							<?php
								if ($row['is_booked']) {
									// Lookup group name from Appointments
									$bookedStmt = $conn->prepare("
										SELECT sg.group_name
										FROM Appointments a
										JOIN StudentGroup sg ON a.group_id = sg.group_id
										WHERE a.availability_id = ?
										LIMIT 1
									");
									$bookedStmt->bind_param("i", $row['availability_id']);
									$bookedStmt->execute();
									$bookedStmt->bind_result($groupName);
									$bookedStmt->fetch();
									$bookedStmt->close();
									echo "<span class='text-danger'>Booked by: " . htmlspecialchars($groupName) . "</span>";
								} else {
									echo "<span class='text-success'>Available</span>";
								}
								?>
							</td>
							<td>
								<a href="InstructorSchedule.php?delete=<?= $row['availability_id'] ?>" class="btn btn-sm btn-danger"
									onclick="return confirm('Delete this slot?')">Delete</a>
								<a href="InstructorSchedule.php?edit=<?= $row['availability_id'] ?>" class="btn btn-sm btn-secondary">Edit</a>
							</td>
						</tr>
					<?php endif; ?>
				<?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No slots found yet.</div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
