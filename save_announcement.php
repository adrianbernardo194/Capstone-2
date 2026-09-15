<?php
require_once 'session_check_admin.php'; // sets $session_admin_name — same include used in admin-dashboard.php
include 'db.php';
header('Content-Type: application/json');

$id           = $_POST['id'] ?? '';
$title        = $conn->real_escape_string(trim($_POST['title'] ?? ''));
$body         = $conn->real_escape_string(trim($_POST['body'] ?? ''));
$is_pinned    = ($_POST['is_pinned'] ?? '0') === '1' ? 1 : 0;
$remove_image = ($_POST['remove_image'] ?? '0') === '1';

// Look up the admin's id by their session username against the real 'admins' table
// ($session_admin_name holds the admin's username, since 'admins' has no full_name column).
// If your session_check_admin.php exposes an id directly (e.g. $session_admin_id),
// replace this block with: $admin_id = $session_admin_id;
$admin_id = null;
if (!empty($session_admin_name)) {
    $name_esc = $conn->real_escape_string($session_admin_name);
    $arow = $conn->query("SELECT id FROM admins WHERE username='$name_esc' LIMIT 1")->fetch_assoc();
    $admin_id = $arow['id'] ?? null;
}

if (!$title || !$body) {
    echo json_encode(['error' => 'Title and message are required.']); exit;
}
if (!$admin_id) {
    echo json_encode(['error' => 'Admin session not found. Check the $admin_id lookup in save_announcement.php.']); exit;
}

$is_update  = ($id && ctype_digit((string)$id));
$old_image  = null;

if ($is_update) {
    $existing  = $conn->query("SELECT image FROM announcements WHERE id=" . (int)$id)->fetch_assoc();
    $old_image = $existing['image'] ?? null;
}

// ── Handle image upload (optional) ──
$image_path    = $is_update ? $old_image : null; // keep existing image by default on update
$new_image_set = false;

if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    $mime    = mime_content_type($_FILES['image']['tmp_name']);

    if (!in_array($mime, $allowed)) {
        echo json_encode(['error' => 'Invalid image type. Only JPG, PNG, or WEBP are accepted.']); exit;
    }
    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['error' => 'Image is too large. Maximum 5MB.']); exit;
    }

    $upload_dir = 'uploads/announcements/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $filename = 'ann_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $new_path = $upload_dir . $filename;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $new_path)) {
        echo json_encode(['error' => 'Failed to upload image. Please try again.']); exit;
    }

    $image_path    = $new_path;
    $new_image_set = true;
} elseif ($remove_image) {
    $image_path = null;
}

// If a new image replaced an old one, or the old one was explicitly removed, delete the old file
if ($is_update && $old_image && ($new_image_set || $remove_image) && $old_image !== $image_path) {
    if (file_exists($old_image)) @unlink($old_image);
}

$image_escaped = $image_path ? "'" . $conn->real_escape_string($image_path) . "'" : 'NULL';

if ($is_update) {
    // ── Update existing ──
    $sql = "UPDATE announcements
            SET title='$title', body='$body', image=$image_escaped, is_pinned=$is_pinned
            WHERE id=" . (int)$id;
} else {
    // ── Create new ──
    $sql = "INSERT INTO announcements (title, body, image, posted_by, is_pinned, created_at)
            VALUES ('$title', '$body', $image_escaped, " . (int)$admin_id . ", $is_pinned, NOW())";
}

if ($conn->query($sql) !== true) {
    echo json_encode(['error' => 'Failed to save announcement.']); exit;
}

echo json_encode(['success' => true, 'id' => $is_update ? (int)$id : $conn->insert_id]);
