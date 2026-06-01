<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'resident') {
    echo json_encode(['notifications'=>[],'unread_count'=>0]); exit;
}

include 'db.php';
$resident_id = (int)$_SESSION['user_id'];
$action      = $_GET['action'] ?? 'fetch';

// ── Fetch resident's own notifications ────────────────────────────────────────
if ($action === 'fetch') {
    $result = $conn->query(
        "SELECT rn.* FROM resident_notifications rn
         INNER JOIN complaints c ON c.id = rn.complaint_id
         WHERE c.resident_id = $resident_id
         ORDER BY rn.created_at DESC LIMIT 30"
    );
    $notifs = [];
    while ($r = $result->fetch_assoc()) $notifs[] = $r;

    $uq = $conn->query(
        "SELECT COUNT(*) as cnt FROM resident_notifications rn
         INNER JOIN complaints c ON c.id = rn.complaint_id
         WHERE c.resident_id = $resident_id AND rn.is_read = 0"
    );
    echo json_encode(['notifications' => $notifs, 'unread_count' => (int)$uq->fetch_assoc()['cnt']]);
}

// ── Mark all read ─────────────────────────────────────────────────────────────
if ($action === 'mark_read') {
    $conn->query(
        "UPDATE resident_notifications rn
         INNER JOIN complaints c ON c.id = rn.complaint_id
         SET rn.is_read = 1
         WHERE c.resident_id = $resident_id"
    );
    echo json_encode(['success' => true]);
}

// ── Mark one complaint read ───────────────────────────────────────────────────
if ($action === 'mark_one_read') {
    $cid = (int)($_GET['complaint_id'] ?? 0);
    $conn->query(
        "UPDATE resident_notifications rn
         INNER JOIN complaints c ON c.id = rn.complaint_id
         SET rn.is_read = 1
         WHERE c.resident_id = $resident_id AND rn.complaint_id = $cid"
    );
    echo json_encode(['success' => true]);
}

// ── Get available time slots for a specific date ──────────────────────────────
// A slot is FULLY BLOCKED if ANY appointment already exists at that date+time
// (regardless of which complaint or lupon member).
// Allowed slots: 9:00 AM, 10:00 AM, 11:00 AM, 12:00 PM
if ($action === 'get_timeslots') {
    $date         = mysqli_real_escape_string($conn, $_GET['date'] ?? '');
    $complaint_id = (int)($_GET['complaint_id'] ?? 0);

    if (!$date) { echo json_encode(['slots' => []]); exit; }

    $all_slots = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM'];

    // Find which slots are already taken on this date by OTHER complaints
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
}

// ── Get lupon availability for a chosen date+time ─────────────────────────────
// Only called AFTER a slot is confirmed available.
// Returns all active lupon members with is_available flag.
if ($action === 'get_lupon_availability') {
    $date         = mysqli_real_escape_string($conn, $_GET['date'] ?? '');
    $time         = mysqli_real_escape_string($conn, $_GET['time'] ?? '');
    $complaint_id = (int)($_GET['complaint_id'] ?? 0);

    if (!$date || !$time) {
        echo json_encode(['members' => []]); exit;
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
}

// ── Get all lupon (sidebar use) ───────────────────────────────────────────────
if ($action === 'get_lupon') {
    $result  = $conn->query("SELECT * FROM lupon_members WHERE is_active=1 ORDER BY name");
    $members = [];
    while ($r = $result->fetch_assoc()) $members[] = $r;
    echo json_encode(['members' => $members]);
}
?>
