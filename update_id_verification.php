<?php
// update_id_verification.php
// Admin approves or rejects a resident's submitted ID from admin-review-id.php.
require_once 'session_check_admin.php';
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$resident_id = (int)($_POST['resident_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($resident_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Missing resident ID.']);
    exit;
}

if (!in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    exit;
}

$new_status = $action === 'approve' ? 'verified' : 'rejected';

$stmt = $conn->prepare("UPDATE residents SET id_verification_status = ? WHERE id = ?");
$stmt->bind_param("si", $new_status, $resident_id);

if ($stmt->execute()) {
    $stmt->close();

    // Clear the admin notification for this resident's ID request
    $conn->query("UPDATE notifications SET is_read = 1 WHERE type = 'id_verification' AND resident_id = " . $resident_id);

    echo json_encode(['success' => true, 'status' => $new_status]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
    $stmt->close();
}
?>
