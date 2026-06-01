<?php
require_once 'session_check_admin.php';
include 'db.php';
include 'audit.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$action = $_POST['action'] ?? '';

// ── Add a holiday ─────────────────────────────────────────────────────────────
if ($action === 'add_holiday') {
    $date  = mysqli_real_escape_string($conn, $_POST['date']  ?? '');
    $label = mysqli_real_escape_string($conn, $_POST['label'] ?? '');
    if (!$date || !$label) { echo json_encode(['error' => 'Date and label are required.']); exit; }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        echo json_encode(['error' => 'Invalid date format.']); exit;
    }

    $admin_id = (int)$_SESSION['user_id'];
    $res = $conn->query("INSERT INTO holidays (holiday_date, label, created_by_admin_id)
                         VALUES ('$date', '$label', $admin_id)
                         ON DUPLICATE KEY UPDATE label='$label'");
    if ($res) {
        log_audit($conn, 'HOLIDAY_ADD', 'Calendar',
            "Blocked date $date with label: $label.",
            null, $date, null, "$date — $label");
        echo json_encode(['success' => true]);
    }
    else      echo json_encode(['error' => 'Failed to save holiday.']);
}

// ── Delete a holiday ──────────────────────────────────────────────────────────
if ($action === 'delete_holiday') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    // Get label before deleting
    $h = $conn->query("SELECT holiday_date, label FROM holidays WHERE id=$id")->fetch_assoc();
    $conn->query("DELETE FROM holidays WHERE id=$id");
    log_audit($conn, 'HOLIDAY_DELETE', 'Calendar',
        "Removed blocked date: " . ($h['holiday_date'] ?? '') . " (" . ($h['label'] ?? '') . ").",
        $id, ($h['holiday_date'] ?? ''),
        ($h['holiday_date'] ?? '') . ' — ' . ($h['label'] ?? ''),
        null);
    echo json_encode(['success' => true]);
}

// ── Update booking window ─────────────────────────────────────────────────────
if ($action === 'update_window') {
    $days = (int)($_POST['days'] ?? 7);
    if ($days < 1 || $days > 365) {
        echo json_encode(['error' => 'Window must be between 1 and 365 days.']); exit;
    }
    // Get old value
    $old_w = $conn->query("SELECT setting_value FROM schedule_settings WHERE setting_key='booking_window_days'")->fetch_assoc();
    $old_days = $old_w['setting_value'] ?? '7';
    $conn->query("INSERT INTO schedule_settings (setting_key, setting_value)
                  VALUES ('booking_window_days', '$days')
                  ON DUPLICATE KEY UPDATE setting_value='$days'");
    log_audit($conn, 'WINDOW_UPDATE', 'Calendar',
        "Changed booking window from $old_days days to $days days.",
        null, 'Booking Window', "$old_days days", "$days days");
    echo json_encode(['success' => true, 'days' => $days]);
}
?>
