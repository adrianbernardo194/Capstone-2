<?php
// get_calendar.php
// Public-ish endpoint (used by both resident and admin pages)
// Returns: holidays list + booking window setting
session_start();
include 'db.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'all';

// ── Get booking window (days) ─────────────────────────────────────────────────
function get_booking_window($conn) {
    $r = $conn->query("SELECT setting_value FROM schedule_settings WHERE setting_key='booking_window_days'")->fetch_assoc();
    return $r ? (int)$r['setting_value'] : 7;
}

// ── Get all holidays ──────────────────────────────────────────────────────────
function get_holidays($conn) {
    $res  = $conn->query("SELECT * FROM holidays ORDER BY holiday_date ASC");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

if ($action === 'all') {
    $window   = get_booking_window($conn);
    $holidays = get_holidays($conn);
    // Also compute the allowed date range for residents
    $min_date = date('Y-m-d', strtotime('+1 day'));
    $max_date = date('Y-m-d', strtotime('+' . $window . ' days'));
    echo json_encode([
        'booking_window_days' => $window,
        'min_date'            => $min_date,
        'max_date'            => $max_date,
        'holidays'            => $holidays,
        'holiday_dates'       => array_column($holidays, 'holiday_date'),
    ]);
}

if ($action === 'window') {
    echo json_encode(['booking_window_days' => get_booking_window($conn)]);
}

if ($action === 'holidays') {
    echo json_encode(['holidays' => get_holidays($conn)]);
}
?>
