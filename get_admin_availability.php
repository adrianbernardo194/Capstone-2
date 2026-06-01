<?php
require_once 'session_check_admin.php';
include 'db.php';
header('Content-Type: application/json');

$action       = $_GET['action'] ?? 'lupon';
$date         = mysqli_real_escape_string($conn, $_GET['date']         ?? '');
$time         = mysqli_real_escape_string($conn, $_GET['time']         ?? '');
$complaint_id = (int)($_GET['complaint_id'] ?? 0);

// ── Get available time slots for a date ──────────────────────────────────────
// Slot is fully blocked if ANY appointment exists on that date+time
// for a DIFFERENT complaint.
if ($action === 'timeslots') {
    if (!$date) { echo json_encode(['slots' => []]); exit; }

    $all_slots = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM'];

    $taken_res = $conn->query(
        "SELECT DISTINCT appointment_time FROM appointments
         WHERE appointment_date = '$date'
           AND complaint_id != $complaint_id"
    );
    $taken = [];
    while ($t = $taken_res->fetch_assoc()) $taken[] = $t['appointment_time'];

    $slots = array_map(fn($s) => [
        'time'      => $s,
        'available' => !in_array($s, $taken),
    ], $all_slots);

    echo json_encode(['slots' => $slots, 'taken' => $taken]);
    exit;
}

// ── Get lupon availability for a date+time ────────────────────────────────────
if (!$date || !$time) {
    echo json_encode(['members' => [], 'error' => 'Date and time required']); exit;
}

$sql = "SELECT lm.*,
            CASE WHEN EXISTS (
                SELECT 1 FROM appointments a
                WHERE a.lupon_member_id = lm.id
                  AND a.appointment_date = '$date'
                  AND a.appointment_time = '$time'
                  AND a.complaint_id != $complaint_id
            ) THEN 0 ELSE 1 END AS is_available
        FROM lupon_members lm
        WHERE lm.is_active = 1
        ORDER BY lm.name";

$result  = $conn->query($sql);
$members = [];
while ($r = $result->fetch_assoc()) {
    $members[] = [
        'id'           => (int)$r['id'],
        'name'         => $r['name'],
        'position'     => $r['position'],
        'is_available' => (int)$r['is_available'],
    ];
}
echo json_encode(['members' => $members]);
?>
