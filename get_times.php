<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

require_once 'connect.php';

// STEP 1: Validate input
if (!isset($_GET['date'])) {
    echo json_encode(["error" => "Missing date parameter"]);
    exit;
}

$date = $_GET['date'];

// STEP 2: Check DB connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// STEP 3: Prepare query
$query = "
    SELECT ia.availability_id, ia.available_time, u.username AS instructor
    FROM InstructorAvailability ia
    JOIN Users u ON ia.instructor_id = u.user_id
    WHERE ia.available_date = ? AND ia.is_booked = 0
    ORDER BY ia.available_time
";

$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param("s", $date);

if (!$stmt->execute()) {
    echo json_encode(["error" => "Execute failed: " . $stmt->error]);
    exit;
}

$result = $stmt->get_result();
if (!$result) {
    echo json_encode(["error" => "Fetching result failed: " . $stmt->error]);
    exit;
}

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = $row;
}

echo json_encode($slots);
