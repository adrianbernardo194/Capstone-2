<?php
session_start();
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'resident') {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$complaint_id = (int)$_POST['complaint_id'];
$date         = mysqli_real_escape_string($conn, $_POST['appointment_date']);
$time         = mysqli_real_escape_string($conn, $_POST['appointment_time']);
$lupon_ids    = array_map('intval',  (array)($_POST['lupon_ids']   ?? []));
$lupon_names  = array_map(fn($n) => mysqli_real_escape_string($conn, $n), (array)($_POST['lupon_names'] ?? []));

$allowed_times = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM'];
if (!$complaint_id || !$date || !$time || empty($lupon_ids)) {
    echo json_encode(['error' => 'Missing required fields.']); exit;
}
if (!in_array($time, $allowed_times)) {
    echo json_encode(['error' => 'Invalid time slot.']); exit;
}

// Verify complaint belongs to this resident
$check = $conn->query("SELECT id FROM complaints WHERE id=$complaint_id AND resident_id={$_SESSION['user_id']}")->fetch_assoc();
if (!$check) { echo json_encode(['error' => 'Complaint not found.']); exit; }

// ── Slot-level conflict: is this entire date+time already taken by another complaint? ──
$slot_taken = (int)$conn->query(
    "SELECT COUNT(*) as c FROM appointments
     WHERE appointment_date='$date' AND appointment_time='$time'
       AND complaint_id != $complaint_id"
)->fetch_assoc()['c'];

if ($slot_taken > 0) {
    echo json_encode([
        'error'     => 'This time slot has already been taken by another complaint. Please choose a different time.',
        'slot_taken' => true,
    ]);
    exit;
}

// ── Save ──────────────────────────────────────────────────────────────────────
$lupon_str = mysqli_real_escape_string($conn, implode(', ', $lupon_names));
$conn->query(
    "UPDATE complaints
     SET appointment_date='$date', appointment_time='$time',
         lupon_preference='$lupon_str', status='In Process'
     WHERE id=$complaint_id"
);

$conn->query("DELETE FROM appointments WHERE complaint_id=$complaint_id");
foreach ($lupon_ids as $lid) {
    if ($lid > 0) {
        $conn->query(
            "INSERT INTO appointments (complaint_id, lupon_member_id, appointment_date, appointment_time)
             VALUES ($complaint_id, $lid, '$date', '$time')"
        );
    }
}

// ── Notify admin about new appointment ──
$comp_info = $conn->query("SELECT complainant_name, subject FROM complaints WHERE id=$complaint_id")->fetch_assoc();
$c_name    = mysqli_real_escape_string($conn, $comp_info['complainant_name']);
$subject   = mysqli_real_escape_string($conn, $comp_info['subject']);
$notif_msg = mysqli_real_escape_string($conn,
    "{$comp_info['complainant_name']} has scheduled an appointment on $date at $time regarding \"{$comp_info['subject']}\"."
);

$conn->query(
    "INSERT INTO notifications (complaint_id, complainant_name, subject, message, type, is_read)
     VALUES ($complaint_id, '$c_name', '$subject', '$notif_msg', 'new_appointment', 0)"
);

echo json_encode(['success' => true]);
?>
