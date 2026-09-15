<?php
// submit_id_verification.php
// Handles a resident submitting (or resubmitting) their ID photo from the
// "Get Verified" modal on resident-portal.php.
require_once 'session_check_resident.php'; // sets $session_resident_id, $session_id_verification_status, $conn

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

if ($session_id_verification_status === 'verified') {
    echo json_encode(['success' => false, 'error' => 'Your account is already verified.']);
    exit;
}

$id_type = trim($_POST['id_type'] ?? '');
if ($id_type === '') {
    echo json_encode(['success' => false, 'error' => 'Please select your ID type.']);
    exit;
}

if (!isset($_FILES['id_photo']) || $_FILES['id_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Please upload a photo of your ID.']);
    exit;
}

$file = $_FILES['id_photo'];

$max_bytes = 5 * 1024 * 1024;
if ($file['size'] > $max_bytes) {
    echo json_encode(['success' => false, 'error' => 'File is too large. Maximum size is 5MB.']);
    exit;
}

$allowed_ext  = ['jpg', 'jpeg', 'png'];
$allowed_mime = ['image/jpeg', 'image/png'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$actual_mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($ext, $allowed_ext, true) || !in_array($actual_mime, $allowed_mime, true)) {
    echo json_encode(['success' => false, 'error' => 'Please upload a JPG or PNG image.']);
    exit;
}

$target_dir = "uploads/ids/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$unique_name = 'id_' . $session_resident_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

if (!move_uploaded_file($file['tmp_name'], $target_dir . $unique_name)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save the uploaded file. Please try again.']);
    exit;
}

$stmt = $conn->prepare(
    "UPDATE residents SET id_type = ?, id_photo = ?, id_verification_status = 'pending' WHERE id = ?"
);
$stmt->bind_param("ssi", $id_type, $unique_name, $session_resident_id);

if ($stmt->execute()) {
    $stmt->close();

    // Notify the admin so it shows up in their notification panel
    $notif_subject = 'ID Verification Request';
    $notif_message = $session_resident_name . ' submitted a ' . $id_type . ' for identity verification.';
    $notif_ok = false;
    $notif_error = null;

    $notif_stmt = $conn->prepare(
        "INSERT INTO notifications (complaint_id, resident_id, complainant_name, subject, message, type, is_read)
         VALUES (NULL, ?, ?, ?, ?, 'id_verification', 0)"
    );
    if ($notif_stmt) {
        $notif_stmt->bind_param("isss", $session_resident_id, $session_resident_name, $notif_subject, $notif_message);
        $notif_ok = $notif_stmt->execute();
        if (!$notif_ok) { $notif_error = $notif_stmt->error; }
        $notif_stmt->close();
    } else {
        $notif_error = $conn->error;
    }

    if (!$notif_ok) {
        // Don't fail the whole request over this — the resident's ID was
        // saved fine — but log it so it's actually diagnosable.
        error_log('submit_id_verification.php: notification insert failed — ' . $notif_error);
    }

    echo json_encode(['success' => true, 'notified' => $notif_ok, 'notify_error' => $notif_error]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
    $stmt->close();
}
?>
