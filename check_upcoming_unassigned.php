<?php
/**
 * check_upcoming_unassigned.php
 *
 * Checks complaints whose resident-preferred appointment date is
 * approaching (1 or 2 days away) but have NOT yet been assigned
 * to any Lupon member. Sends a warning notification to admin.
 *
 * HOW TO USE:
 * Call this once per admin page load via fetch() — it's a lightweight
 * query and won't duplicate notifications for the same complaint + day.
 *
 * fetch('check_upcoming_unassigned.php')
 *   .then(r => r.json())
 *   .then(d => { if (d.flagged_count > 0) fetchBell(); });
 */

include 'db.php';
header('Content-Type: application/json');

$today    = date('Y-m-d');
$day1     = date('Y-m-d', strtotime('+1 day')); // tomorrow
$day2     = date('Y-m-d', strtotime('+2 days')); // day after tomorrow

// Find complaints where:
// 1. Resident has a preferred appointment_date set
// 2. That date is 1 or 2 days from today
// 3. Complaint is still active
// 4. NOT yet assigned (no row in appointments table)
// 5. No warning notification already sent for this complaint on this specific trigger day
$sql = "
    SELECT
        c.id,
        c.subject,
        c.complainant_name,
        c.appointment_date,
        c.appointment_time,
        DATEDIFF(c.appointment_date, '$today') AS days_away
    FROM complaints c
    WHERE c.appointment_date IN ('$day1', '$day2')
      AND c.status NOT IN ('Completed', 'Rejected', 'Cannot Be Handled')
      AND NOT EXISTS (
          SELECT 1 FROM appointments a WHERE a.complaint_id = c.id
      )
      AND NOT EXISTS (
          SELECT 1 FROM notifications n
          WHERE n.complaint_id = c.id
            AND n.type = 'unassigned_warning'
            AND DATE(n.created_at) = '$today'
      )
";

$result  = $conn->query($sql);
$flagged = [];

while ($row = $result->fetch_assoc()) {
    $cid       = (int)$row['id'];
    $days_away = (int)$row['days_away'];
    $subject   = $conn->real_escape_string($row['subject']);
    $c_name    = $conn->real_escape_string($row['complainant_name']);
    $appt_date = date('F j, Y', strtotime($row['appointment_date']));
    $appt_time = !empty($row['appointment_time']) ? ' at ' . $row['appointment_time'] : '';

    $day_label = $days_away === 1 ? 'tomorrow' : 'in 2 days';

    $message = $conn->real_escape_string(
        "⚠ REMINDER: The complaint \"{$row['subject']}\" filed by {$row['complainant_name']} " .
        "has a preferred appointment on $appt_date{$appt_time} ($day_label) " .
        "but has not been assigned to any Lupon member yet. Please assign it now."
    );

    $conn->query(
        "INSERT INTO notifications
            (complaint_id, complainant_name, subject, message, type, is_read)
         VALUES
            ($cid, '$c_name', '$subject', '$message', 'unassigned_warning', 0)"
    );

    $flagged[] = [
        'complaint_id'     => $cid,
        'subject'          => $row['subject'],
        'complainant_name' => $row['complainant_name'],
        'appointment_date' => $row['appointment_date'],
        'days_away'        => $days_away,
    ];
}

echo json_encode([
    'success'       => true,
    'checked_at'    => $today,
    'flagged_count' => count($flagged),
    'flagged'       => $flagged,
]);
?>
