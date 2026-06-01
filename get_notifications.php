<?php
header('Content-Type: application/json');
include 'db.php';

$action = $_GET['action'] ?? 'fetch';

if ($action === 'fetch') {
    $result = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20");
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $unread = $conn->query("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")->fetch_assoc();
    echo json_encode([
        'notifications' => $notifications,
        'unread_count'  => (int)$unread['count']
    ]);
}

if ($action === 'mark_read') {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
    echo json_encode(['success' => true]);
}
?>
