<?php
require 'header.php';
require_login('instructor');
require 'connect.php';

$search = $_GET['search'] ?? '';
$groups = [];

// Create a new group
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_group']) && !empty($_POST['new_group_name'])) {
    $new_name = trim($_POST['new_group_name']);
    $leader_id = intval($_POST['new_leader_id']);

    // Insert into StudentGroup
    $stmt = $conn->prepare("INSERT INTO StudentGroup (group_name, leader_id) VALUES (?, ?)");
    $stmt->bind_param("si", $new_name, $leader_id);
    $stmt->execute();
    $newGroupId = $stmt->insert_id;
    $stmt->close();

    // Add leader to StudentGroupMembers
    $stmt = $conn->prepare("INSERT INTO StudentGroupMembers (group_id, user_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $newGroupId, $leader_id);
    $stmt->execute();
    $stmt->close();

    header("Location: groupeditor.php?created=1");
    exit;
}

// Update leader
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_leader'])) {
    $group_id = intval($_POST['group_id']);
    $new_leader = intval($_POST['leader_id']);
    $stmt = $conn->prepare("UPDATE StudentGroup SET leader_id = ? WHERE group_id = ?");
    $stmt->bind_param("ii", $new_leader, $group_id);
    $stmt->execute();
    $stmt->close();
    header("Location: groupeditor.php?updated_leader=1");
    exit;
}

// Search for groups by name or member
if ($search) {
    $stmt = $conn->prepare("
        SELECT DISTINCT sg.group_id
        FROM StudentGroup sg
        LEFT JOIN StudentGroupMembers sgm ON sg.group_id = sgm.group_id
        LEFT JOIN Users u ON sgm.user_id = u.user_id
        WHERE sg.group_name LIKE ? OR u.username LIKE ?
    ");
    $like = '%' . $search . '%';
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $groupIdsResult = $stmt->get_result();
    $groupIds = [];
    while ($row = $groupIdsResult->fetch_assoc()) {
        $groupIds[] = $row['group_id'];
    }
    $stmt->close();

    if (!empty($groupIds)) {
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $types = str_repeat('i', count($groupIds));

        $stmt = $conn->prepare("
            SELECT sg.group_id, sg.group_name, u.username AS leader_name, sg.leader_id
            FROM StudentGroup sg
            JOIN Users u ON sg.leader_id = u.user_id
            WHERE sg.group_id IN ($placeholders)
            ORDER BY sg.group_id
        ");
        $stmt->bind_param($types, ...$groupIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $groups[] = $row;
        }
        $stmt->close();
    }
} else {
    $stmt = $conn->prepare("
        SELECT sg.group_id, sg.group_name, u.username AS leader_name, sg.leader_id
        FROM StudentGroup sg
        JOIN Users u ON sg.leader_id = u.user_id
        ORDER BY sg.group_id
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $groups[] = $row;
    }
    $stmt->close();
}


// Add a member to a group
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_member'])) {
    $group_id = intval($_POST['group_id']);
    $user_id = intval($_POST['new_member_id']);

    // Prevent duplicate entry in the same group
    $stmt = $conn->prepare("SELECT 1 FROM StudentGroupMembers WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO StudentGroupMembers (group_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
    }

    header("Location: groupeditor.php");
    exit;
}

// Group deletion (and related appointment deletion)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_group'])) {
    $group_id = intval($_POST['group_id']);

    // Delete from Appointments
    $stmt = $conn->prepare("DELETE FROM Appointments WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $stmt->close();

    // Delete from StudentGroupMembers
    $stmt = $conn->prepare("DELETE FROM StudentGroupMembers WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $stmt->close();

    // Delete from StudentGroup
    $stmt = $conn->prepare("DELETE FROM StudentGroup WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $stmt->close();

    header("Location: groupeditor.php?deleted=1");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Group Editor</title>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Group Editor</h1>

    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by Group name or Member" value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <?php if (count($groups) > 0): ?>
        <?php foreach ($groups as $group): ?>
            <div class="card mb-3">
				<div class="card-header bg-match d-flex justify-content-between align-items-center">
					<form method="POST" class="d-flex gap-2">
						<input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
						<input type="text" name="group_name" class="form-control form-control-sm" value="<?= htmlspecialchars($group['group_name']) ?>" required>
						<button type="submit" name="update_name" class="btn btn-sm btn-light">Rename</button>
					</form>

					<div class="d-flex align-items-center gap-2">
						<form method="POST">
							<input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
							<select name="leader_id" class="form-select form-select-sm" required>
								<?php
								$stmt = $conn->prepare("SELECT u.user_id, u.username FROM StudentGroupMembers sgm JOIN Users u ON sgm.user_id = u.user_id WHERE sgm.group_id = ?");
								$stmt->bind_param("i", $group['group_id']);
								$stmt->execute();
								$leaders = $stmt->get_result();
								while ($l = $leaders->fetch_assoc()):
									$selected = ($l['user_id'] == $group['leader_id']) ? 'selected' : '';
									echo "<option value='{$l['user_id']}' $selected>{$l['username']}</option>";
								endwhile;
								$stmt->close();
								?>
							</select>
							<button type="submit" name="update_leader" class="btn btn-sm btn-light">Update Leader</button>
						</form>

						<!-- DELETE FORM -->
						<form method="POST">
							<input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
							<button type="submit" name="delete_group" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this group and its appointment (if any)?')">Delete Group</button>
						</form>
					</div>
				</div>

                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php
                        $stmt = $conn->prepare("SELECT u.user_id, u.username FROM StudentGroupMembers sgm JOIN Users u ON sgm.user_id = u.user_id WHERE sgm.group_id = ?");
                        $stmt->bind_param("i", $group['group_id']);
                        $stmt->execute();
                        $members = $stmt->get_result();
                        while ($member = $members->fetch_assoc()):
                        ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
								<?php
									$isLeader = ($member['user_id'] == $group['leader_id']);
								?>
								<span<?= $isLeader ? ' class="fw-bold text-primary"' : '' ?>>
									<?= htmlspecialchars($member['username']) ?>
								</span>

                                <form method="POST" class="m-0">
                                    <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                    <input type="hidden" name="user_id" value="<?= $member['user_id'] ?>">
                                    <button type="submit" name="remove_member" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </li>
                        <?php endwhile; ?>
                        <?php $stmt->close(); ?>
                    </ul>

                    <form method="POST" class="mt-3 d-flex gap-2 align-items-center">
                        <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                        <select name="new_member_id" class="form-select form-select-sm" required>
                            <?php
                            $stmt = $conn->prepare("SELECT user_id, username FROM Users WHERE role = 'student' AND user_id NOT IN (SELECT user_id FROM StudentGroupMembers WHERE group_id = ?)");
                            $stmt->bind_param("i", $group['group_id']);
                            $stmt->execute();
                            $newUsers = $stmt->get_result();
                            while ($u = $newUsers->fetch_assoc()):
                                echo "<option value='{$u['user_id']}'>{$u['username']}</option>";
                            endwhile;
                            $stmt->close();
                            ?>
                        </select>
                        <button type="submit" name="add_member" class="btn btn-sm btn-outline-success">Add Member</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No groups found.</div>
    <?php endif; ?>

    <div class="mt-5">
		<h4>Create New Group</h4>
		<form method="POST" class="row g-3 align-items-end">
			<div class="col-md-4">
				<label class="form-label">Group Name</label>
				<input type="text" name="new_group_name" class="form-control" required>
			</div>
			<div class="col-md-4">
				<label class="form-label">Select Group Leader</label>
				<select name="new_leader_id" class="form-select" required>
					<?php
					$stmt = $conn->prepare("SELECT user_id, username FROM Users WHERE role = 'student'");
					$stmt->execute();
					$allUsers = $stmt->get_result();
					while ($u = $allUsers->fetch_assoc()):
						echo "<option value='{$u['user_id']}'>{$u['username']}</option>";
					endwhile;
					$stmt->close();
					?>
				</select>
			</div>
			<div class="col-md-4">
				<button type="submit" name="create_group" class="btn btn-success">Create Group</button>
			</div>
		</form>
	</div>

</div>

<?php include 'footer.php'; ?>
</body>
</html>
