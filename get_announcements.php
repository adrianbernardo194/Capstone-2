<?php
require_once 'session_check_resident.php'; // same include used in resident-portal.php — any logged-in resident, verified or not
include 'db.php';
header('Content-Type: application/json');

$result = $conn->query("
    SELECT a.id, a.title, a.body, a.image, a.is_pinned, a.created_at, u.username AS posted_by_name
    FROM announcements a
    LEFT JOIN admins u ON a.posted_by = u.id
    ORDER BY a.is_pinned DESC, a.created_at DESC
");

$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = [
        'id'         => (int)$row['id'],
        'title'      => $row['title'],
        'body'       => $row['body'],
        'image'      => $row['image'],
        'is_pinned'  => (bool)$row['is_pinned'],
        'posted_by'  => $row['posted_by_name'] ?? 'Admin',
        'created_at' => date('M j, Y g:i A', strtotime($row['created_at'])),
    ];
}

echo json_encode(['success' => true, 'announcements' => $announcements]);
