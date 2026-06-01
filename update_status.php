<?php
session_start();
include 'db.php';
include 'audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$id      = (int)$_POST['complaint_id'];
$status  = mysqli_real_escape_string($conn, $_POST['status']);
$comment = mysqli_real_escape_string($conn, $_POST['comment'] ?? '');

// Reschedule fields (only used when status = Rescheduled)
$new_date = mysqli_real_escape_string($conn, $_POST['reschedule_date'] ?? '');
$new_time = mysqli_real_escape_string($conn, $_POST['reschedule_time'] ?? '');

$allowed = ['Pending', 'Approved', 'In Process', 'Rescheduled', 'Cannot Be Handled', 'Completed'];
if (!in_array($status, $allowed)) {
    echo json_encode(['error' => 'Invalid status']); exit;
}

$comp = $conn->query("SELECT * FROM complaints WHERE id = $id")->fetch_assoc();
if (!$comp) { echo json_encode(['error' => 'Complaint not found']); exit; }

// Validate reschedule fields
if ($status === 'Rescheduled') {
    $allowed_times = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM'];
    if (!$new_date) {
        echo json_encode(['error' => 'Please select a new hearing date.']); exit;
    }
    if (!in_array($new_time, $allowed_times)) {
        echo json_encode(['error' => 'Please select a valid time slot.']); exit;
    }

    // Check if the new slot is already taken by another complaint
    $slot_taken = (int)$conn->query(
        "SELECT COUNT(*) as c FROM appointments
         WHERE appointment_date='$new_date'
           AND appointment_time='$new_time'
           AND complaint_id != $id"
    )->fetch_assoc()['c'];

    if ($slot_taken > 0) {
        echo json_encode(['error' => 'That time slot is already taken. Please choose a different date or time.']); exit;
    }

    // Update complaint with new schedule (keep existing lupon assignment)
    $conn->query("
        UPDATE complaints
        SET status          = 'Rescheduled',
            admin_comment   = '$comment',
            appointment_date = '$new_date',
            appointment_time = '$new_time',
            resident_notified = 0
        WHERE id = $id
    ");

    // Update appointments table to the new date+time (keep same lupon members)
    $conn->query("
        UPDATE appointments
        SET appointment_date = '$new_date',
            appointment_time = '$new_time'
        WHERE complaint_id = $id
    ");

    $formatted = date("F j, Y", strtotime($new_date));
    $msg        = "Your mediation hearing for Case #BRGY-2026-0{$id} has been rescheduled by the admin. "
                . "New date: $formatted at $new_time."
                . ($comment ? " Note: $comment" : '');
    $notif_type = 'rescheduled';

} else {
    // All other statuses — normal update
    $conn->query("UPDATE complaints SET status='$status', admin_comment='$comment', resident_notified=0 WHERE id=$id");

    switch ($status) {
        case 'Approved':
            $msg        = "Your complaint (Case #BRGY-2026-0{$id}) has been reviewed and approved. You may now schedule an appointment with the Lupon.";
            $notif_type = 'approved';
            break;
        case 'Cannot Be Handled':
            $msg        = "Your complaint (Case #BRGY-2026-0{$id}) has been reviewed. Unfortunately, this is a major case that cannot be handled at the barangay level. Please visit the barangay office for a Certificate to File Action.";
            $notif_type = 'cannot_handle';
            break;
        case 'In Process':
            $msg        = "Your complaint (Case #BRGY-2026-0{$id}) requires follow-up or additional information." . ($comment ? " Note: $comment" : '');
            $notif_type = 'in_process';
            break;
        case 'Completed':
            $msg        = "Your complaint (Case #BRGY-2026-0{$id}) has been resolved and marked as completed." . ($comment ? " Note: $comment" : '');
            $notif_type = 'status_update';
            break;
        default:
            $msg        = "Your complaint (Case #BRGY-2026-0{$id}) status has been updated to: $status." . ($comment ? " Note: $comment" : '');
            $notif_type = 'status_update';
    }
}

$esc_msg = mysqli_real_escape_string($conn, $msg);
$conn->query("INSERT INTO resident_notifications (complaint_id, type, message, is_read)
              VALUES ($id, '$notif_type', '$esc_msg', 0)");

// ── Audit log ─────────────────────────────────────────────────────────────────
$case_label = 'BRGY-2026-0' . $id . ' — ' . $comp['complainant_name'] . ' vs. ' . $comp['respondent_name'];
$old_status = $comp['status'];

if ($status === 'Rescheduled') {
    log_audit(
        $conn,
        'RESCHEDULE',
        'Complaints',
        "Rescheduled hearing for case #$id to $new_date at $new_time." . ($comment ? " Note: $comment" : ''),
        $id,
        $case_label,
        "Status: $old_status | Date: " . ($comp['appointment_date'] ?? 'none') . " | Time: " . ($comp['appointment_time'] ?? 'none'),
        "Status: Rescheduled | Date: $new_date | Time: $new_time"
    );
} else {
    log_audit(
        $conn,
        'STATUS_UPDATE',
        'Complaints',
        "Updated status of case #$id from '$old_status' to '$status'." . ($comment ? " Comment: $comment" : ''),
        $id,
        $case_label,
        $old_status,
        $status
    );
}

echo json_encode(['success' => true, 'status' => $status]);
?>
