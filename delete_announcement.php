<?php
require_once 'session_check_admin.php'; // same include used in admin-dashboard.php
include 'db.php';
header('Content-Type: application/json');

$id = $_POST['id'] ?? '';

if (!$id || !ctype_digit((string)$id)) {
    echo json_encode(['error' => 'Invalid announcement ID.']); exit;
}

// Clean up the associated image file, if any, before deleting the row
$row = $conn->query("SELECT image FROM announcements WHERE id=" . (int)$id)->fetch_assoc();
if ($row && !empty($row['image']) && file_exists($row['image'])) {
    @unlink($row['image']);
}

if ($conn->query("DELETE FROM announcements WHERE id=" . (int)$id) !== true) {
    echo json_encode(['error' => 'Failed to delete announcement.']); exit;
}

echo json_encode(['success' => true]);
