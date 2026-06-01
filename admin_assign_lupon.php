<?php
require_once 'session_check_admin.php';
include 'db.php';
include 'audit.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$complaint_id = (int)($_POST['complaint_id'] ?? 0);
$date         = mysqli_real_escape_string($conn, $_POST['date'] ?? '');
$time         = mysqli_real_escape_string($conn, $_POST['time'] ?? '');
$lupon_ids    = array_map('intval',  (array)($_POST['lupon_ids']   ?? []));
$lupon_names  = array_map(fn($n) => mysqli_real_escape_string($conn, $n), (array)($_POST['lupon_names'] ?? []));

$allowed_times = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM'];

if (!$complaint_id || !$date || !$time || empty($lupon_ids)) {
    echo json_encode(['error' => 'Missing required fields.']); exit;
}
if (!in_array($time, $allowed_times)) {
    echo json_encode(['error' => 'Invalid time slot.']); exit;
}

$comp = $conn->query("SELECT * FROM complaints WHERE id=$complaint_id")->fetch_assoc();
if (!$comp) { echo json_encode(['error' => 'Complaint not found.']); exit; }

// ── Slot-level conflict check ──────────────────────────────────────────────────
$slot_taken = (int)$conn->query(
    "SELECT COUNT(*) as c FROM appointments
     WHERE appointment_date='$date' AND appointment_time='$time'
       AND complaint_id != $complaint_id"
)->fetch_assoc()['c'];

if ($slot_taken > 0) {
    echo json_encode([
        'error'      => 'This time slot is already taken by another complaint. Please choose a different time.',
        'slot_taken' => true,
    ]);
    exit;
}

// ── Per-lupon conflict check (belt-and-suspenders) ────────────────────────────
$conflicts = [];
foreach ($lupon_ids as $lid) {
    $booked = $conn->query(
        "SELECT id FROM appointments
         WHERE lupon_member_id=$lid
           AND appointment_date='$date'
           AND appointment_time='$time'
           AND complaint_id != $complaint_id"
    )->num_rows;
    if ($booked > 0) {
        $m = $conn->query("SELECT name FROM lupon_members WHERE id=$lid")->fetch_assoc();
        $conflicts[] = $m['name'] ?? "Lupon #$lid";
    }
}
if (!empty($conflicts)) {
    echo json_encode(['error' => 'Conflicts found.', 'conflicts' => $conflicts]); exit;
}

// ── Save ──────────────────────────────────────────────────────────────────────
$lupon_str = mysqli_real_escape_string($conn, implode(', ', $lupon_names));
$conn->query("
    UPDATE complaints
    SET appointment_date='$date', appointment_time='$time',
        lupon_preference='$lupon_str', status='In Process'
    WHERE id=$complaint_id
");

$conn->query("DELETE FROM appointments WHERE complaint_id=$complaint_id");
foreach ($lupon_ids as $lid) {
    if ($lid > 0) {
        $conn->query(
            "INSERT INTO appointments (complaint_id, lupon_member_id, appointment_date, appointment_time)
             VALUES ($complaint_id, $lid, '$date', '$time')"
        );
    }
}

// ── Notify the resident ───────────────────────────────────────────────────────
$formatted_date = date("F j, Y", strtotime($date));
$msg     = "Your hearing has been officially scheduled. Date: $formatted_date at $time. Assigned Lupon: $lupon_str.";
$esc_msg = mysqli_real_escape_string($conn, $msg);
$conn->query("INSERT INTO resident_notifications (complaint_id, type, message, is_read)
              VALUES ($complaint_id, 'approved', '$esc_msg', 0)");

// ── Audit log ─────────────────────────────────────────────────────────────────
$case_label = 'BRGY-2026-0' . $complaint_id . ' (' . ($comp['complainant_name'] ?? '') . ' vs. ' . ($comp['respondent_name'] ?? '') . ')';
log_audit(
    $conn,
    'LUPON_ASSIGN',
    'Appointments',
    "Assigned Lupon panel to case #$complaint_id. Hearing: $date at $time. Panel: $lupon_str.",
    $complaint_id,
    $case_label,
    null,
    "Date: $date | Time: $time | Panel: $lupon_str"
);

echo json_encode(['success' => true]);
?>
