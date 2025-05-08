<?php
require 'header.php';
require_login('instructor');
require 'connect.php'; 

// date filtering
$selectedDate = $_GET['date'] ?? null;

// Fetch booked appointments 
if ($selectedDate) {
	$stmt = $conn->prepare("
		SELECT 
			a.project_name, 
			sg.group_name, 
			ia.available_date, 
			ia.available_time, 
			sg.group_id,
			sg.leader_id
		FROM Appointments a
		JOIN InstructorAvailability ia ON a.availability_id = ia.availability_id
		JOIN StudentGroup sg ON a.group_id = sg.group_id
		WHERE ia.available_date = ?
		ORDER BY ia.available_date, ia.available_time
	");
	$stmt->bind_param("s", $selectedDate);
} else {
	$stmt = $conn->prepare("
		SELECT 
			a.project_name, 
			sg.group_name, 
			ia.available_date, 
			ia.available_time, 
			sg.group_id,
			sg.leader_id
		FROM Appointments a
		JOIN InstructorAvailability ia ON a.availability_id = ia.availability_id
		JOIN StudentGroup sg ON a.group_id = sg.group_id
		WHERE ia.available_date >= CURDATE()
		ORDER BY ia.available_date, ia.available_time
	");
}
$stmt->execute();
$appointments = $stmt->get_result();

// Fetch all available slots 
if ($selectedDate) {
	$availStmt = $conn->prepare("
		SELECT * FROM InstructorAvailability 
		WHERE available_date = ? AND is_booked = FALSE 
		ORDER BY available_date, available_time
	");
	$availStmt->bind_param("s", $selectedDate);
} else {
	$availStmt = $conn->prepare("
		SELECT * FROM InstructorAvailability 
		WHERE available_date >= CURDATE() AND is_booked = FALSE 
		ORDER BY available_date, available_time
	");
}
$availStmt->execute();
$availableSlots = $availStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Instructor Dashboard</title>
	<meta charset="UTF-8">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container mt-5">
	<h1 class="mb-4">Instructor Dashboard</h1>

	<!-- Filter form -->
	<form class="row g-3 mb-4" method="GET" action="instructordash.php">
		<div class="col-auto">
			<label for="date" class="col-form-label">Filter by date:</label>
		</div>
		<div class="col-auto">
			<input type="date" name="date" class="form-control" value="<?= $selectedDate ?>">
		</div>
		<div class="col-auto">
			<button type="submit" class="btn btn-primary">Filter</button>
			<a href="instructordash.php" class="btn btn-secondary">Clear Filter</a>
		</div>
	</form>

	<!-- Booked appointments -->
	<h2 class="mb-3">Booked Appointments</h2>
	<?php if ($appointments->num_rows > 0): ?>
		<div class="row g-4">
			<?php while ($row = $appointments->fetch_assoc()): ?>
				<div class="col-md-6">
					<div class="card h-100 p-3 shadow-sm">
						<h5 class="card-title mb-2"><?= htmlspecialchars($row['project_name']) ?></h5>
						<p><strong>Date:</strong> <?= $row['available_date'] ?> @ <?= $row['available_time'] ?></p>
						<p><strong>Group:</strong> <?= htmlspecialchars($row['group_name']) ?></p>

						<?php
						$groupId = $row['group_id'];
						$memStmt = $conn->prepare("
							SELECT u.user_id, u.username 
							FROM StudentGroupMembers gm 
							JOIN Users u ON gm.user_id = u.user_id 
							WHERE gm.group_id = ?
						");
						$memStmt->bind_param("i", $groupId);
						$memStmt->execute();
						$members = $memStmt->get_result();
						?>
						<p><strong>Members:</strong>
							<?php 
							$memList = [];
							while ($mem = $members->fetch_assoc()) {
								$isLeader = ($mem['user_id'] == $row['leader_id']);
								$name = htmlspecialchars($mem['username']);
								$memList[] = $isLeader ? "<strong class='text-primary'>$name</strong>" : $name;
							}
							echo implode(', ', $memList) ?: '<em>None</em>';
							?>
						</p>
					</div>
				</div>
			<?php endwhile; ?>
		</div>
	<?php else: ?>
		<div class="alert alert-info">No appointments booked<?= $selectedDate ? " for $selectedDate" : "" ?>.</div>
	<?php endif; ?>

	<!-- Available slots -->
	<h2 class="mt-5 mb-3">Available Time Slots</h2>

	<?php
	$currentDate = null;
	$slotsByDate = [];

	// Group the results by date in PHP first update comment later is test works
	while ($row = $availableSlots->fetch_assoc()) {
		$slotsByDate[$row['available_date']][] = $row['available_time'];
	}
	?>

	<div class="row row-cols-1 row-cols-md-2 g-4">
		<?php foreach ($slotsByDate as $date => $times): ?>
			<div class="col">
				<div class="card h-100">
					<div class="card-header bg-match">
						<strong><?= htmlspecialchars($date) ?></strong>
					</div>
					<ul class="list-group list-group-flush">
						<?php foreach ($times as $time): ?>
							<li class="list-group-item small"><?= htmlspecialchars($time) ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
