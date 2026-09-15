<?php
require_once 'session_check_admin.php';
include 'db.php';

// Avatar initials from the admin's name
$admin_display_name = $session_admin_name ?? 'Admin';
$admin_name_parts = preg_split('/\s+/', trim($admin_display_name));
$admin_initials = strtoupper(substr($admin_name_parts[0], 0, 1) . substr($admin_name_parts[count($admin_name_parts) - 1] ?? '', 0, 1));
if (count($admin_name_parts) < 2) {
    $admin_initials = strtoupper(substr($admin_name_parts[0], 0, 2));
}

// Load current settings
$window_row = $conn->query("SELECT setting_value FROM schedule_settings WHERE setting_key='booking_window_days'")->fetch_assoc();
$booking_window = $window_row ? (int)$window_row['setting_value'] : 7;

// Load all holidays
$holidays_res = $conn->query("SELECT * FROM holidays ORDER BY holiday_date ASC");
$holidays = [];
while ($h = $holidays_res->fetch_assoc()) $holidays[] = $h;

// Group holidays by year-month for calendar rendering
$holiday_map = [];
foreach ($holidays as $h) $holiday_map[$h['holiday_date']] = $h;

// ── Load all scheduled hearings (from appointments + complaints) ──
// Group by date, listing complainant/respondent and assigned lupon members
$hearings_res = $conn->query("
    SELECT a.appointment_date, a.appointment_time,
           c.id AS complaint_id, c.subject, c.complainant_name, c.respondent_name,
           lm.name AS lupon_name
    FROM appointments a
    INNER JOIN complaints c ON c.id = a.complaint_id
    LEFT JOIN lupon_members lm ON lm.id = a.lupon_member_id
    WHERE c.status NOT IN ('Completed','Rejected','Cannot Be Handled')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");

$hearing_map = []; // date => [ complaint_id => {time, subject, complainant, respondent, lupon: []} ]
while ($hr = $hearings_res->fetch_assoc()) {
    $date = $hr['appointment_date'];
    $cid  = $hr['complaint_id'];
    if (!isset($hearing_map[$date])) $hearing_map[$date] = [];
    if (!isset($hearing_map[$date][$cid])) {
        $hearing_map[$date][$cid] = [
            'complaint_id' => $cid,
            'time'         => $hr['appointment_time'],
            'subject'      => $hr['subject'],
            'complainant'  => $hr['complainant_name'],
            'respondent'   => $hr['respondent_name'],
            'lupon'        => [],
        ];
    }
    if (!empty($hr['lupon_name'])) {
        $hearing_map[$date][$cid]['lupon'][] = $hr['lupon_name'];
    }
}
// Re-index each date's hearings as a plain array
foreach ($hearing_map as $date => $cases) {
    $hearing_map[$date] = array_values($cases);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Management - Barangay San Roque</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-900: #1B4332;
            --green-700: #2D6A4F;
            --green-500: #40916c;
            --green-100: #e8f5e9;
            --border: #e0ede5;
            --muted: #52796f;
        }

        /* =============================================
           TOP BAR + SIDEBAR RAIL — matches admin-dashboard.php
           ============================================= */

        * { font-family: 'Poppins', sans-serif; }

        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; }
        body {
            background:#f8fafc;
            opacity: 0;
            animation: pageFadeIn .4s ease forwards;
        }
        @keyframes pageFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        body.page-exit {
            animation: pageFadeOut .28s ease forwards;
        }
        @keyframes pageFadeOut {
            from { opacity: 1; }
            to   { opacity: 0; }
        }

        .sidebar {
            width: 76px;
            height: 100vh;
            background: #004d2c;
            position: fixed;
            top: 0;
            left: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            z-index: 9200;
            transform: translateX(0);
            transition: transform 0.3s ease;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.25) transparent;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.25); border-radius: 4px; }

        .sidebar-top, .sidebar-bottom {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            width: 100%;
        }

        .nav-item {
            width: 46px;
            height: 46px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
            opacity: 0.65;
            border-radius: 12px;
            text-decoration: none;
            margin-bottom: 4px;
        }
        .nav-item.active, .nav-item:hover { opacity: 1; background-color: rgba(255,255,255,0.14); }
        .nav-item img { width: 22px; height: 22px; filter: brightness(0) invert(1); }

        .sidebar-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.14);
            border: 1.5px solid rgba(255,255,255,0.35);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        @media screen and (max-height: 700px) {
            .nav-item { width: 36px; height: 36px; }
            .nav-item img { width: 17px; height: 17px; }
            .sidebar-top, .sidebar-bottom { gap: 4px; }
            .sidebar { padding: 10px 0; }
        }

        .sidebar-close-btn {
            display: none;
            position: absolute;
            top: 14px;
            right: 14px;
            background: none;
            border: none;
            color: #fff;
            opacity: 0.75;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
        }
        .sidebar-close-btn:hover { opacity: 1; background: rgba(255,255,255,0.14); }
        .sidebar-close-btn svg { width: 18px; height: 18px; }

        .app-topbar {
            position: fixed;
            top: 0;
            left: 76px;
            right: 0;
            height: 64px;
            background: #fff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 28px;
            z-index: 200;
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .hamburger-btn {
            display: none;
            background: none;
            border: none;
            color: var(--green-900);
            width: 34px;
            height: 34px;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .hamburger-btn:hover { background: var(--green-100); }
        .hamburger-btn img {
            width: 20px;
            height: 20px;
            filter: invert(17%) sepia(35%) saturate(1352%) hue-rotate(115deg) brightness(94%) contrast(92%);
        }
        .topbar-titles { display: flex; flex-direction: column; line-height: 1.25; }
        .topbar-eyebrow {
            font-size: 0.66rem; font-weight: 700; letter-spacing: 0.14em;
            text-transform: uppercase; color: var(--muted);
        }
        .topbar-title { font-size: 1rem; font-weight: 600; color: var(--green-900); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--green-700); color: #fff;
            font-size: 0.75rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .portal-content {
            position: fixed;
            top: 64px;
            left: 76px;
            right: 0;
            bottom: 0;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 30px 36px 30px;
            animation: contentFadeIn .4s ease forwards;
        }
        @keyframes contentFadeIn {
            from { transform: translateY(8px); }
            to   { transform: translateY(0); }
        }
        body.page-exit .portal-content {
            animation: contentFadeOut .28s ease forwards;
        }
        @keyframes contentFadeOut {
            from { transform: translateY(0) scale(1); }
            to   { transform: translateY(-6px) scale(0.99); }
        }

        /* ── Page layout ── */
        .cal-layout {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: flex-start;
        }

        /* ── Page header ── */
        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:24px;color:#1a202c;font-weight:700;margin:0 0 4px; }
        .page-header p  { font-size:14px;color:#64748b;margin:0; }

        /* ══════════════════════════════
           CALENDAR
        ══════════════════════════════ */
        .cal-card {
            background:#fff;border-radius:14px;border:1px solid #edf2f7;overflow:hidden;
        }

        .cal-nav {
            display:flex;align-items:center;justify-content:space-between;
            padding:18px 24px;border-bottom:1px solid #f1f5f9;
            background:#fafbfc;
        }
        .cal-nav h2 { font-size:16px;font-weight:700;color:#1a202c;margin:0; }
        .cal-nav-btn {
            width:34px;height:34px;border-radius:8px;border:1px solid #e2e8f0;
            background:#fff;cursor:pointer;font-size:16px;display:flex;
            align-items:center;justify-content:center;transition:all .15s;
        }
        .cal-nav-btn:hover { background:#f1f5f9;border-color:#cbd5e0; }

        /* Day-of-week header */
        .cal-dow {
            display:grid;grid-template-columns:repeat(7,1fr);
            padding:10px 16px 6px;gap:4px;
        }
        .cal-dow span {
            text-align:center;font-size:11px;font-weight:700;
            color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;
        }

        /* Calendar grid */
        .cal-grid {
            display:grid;grid-template-columns:repeat(7,1fr);
            padding:0 16px 16px;gap:4px;
        }

        .cal-day {
            aspect-ratio:1;border-radius:10px;display:flex;
            flex-direction:column;align-items:center;justify-content:center;
            font-size:13px;font-weight:500;color:#374151;
            cursor:pointer;transition:all .15s;position:relative;
            border:2px solid transparent;
            padding:2px;overflow:hidden;
        }
        .cal-day:hover:not(.empty):not(.past) {
            background:#eff6ff;border-color:#93c5fd;
        }
        .cal-day.empty  { cursor:default; }
        .cal-day.past   { color:#d1d5db;cursor:not-allowed; }
        .cal-day.today  {
            background:#f0fdf4;border-color:#2D6A4F;
            color:#1B4332;font-weight:700;
        }
        .cal-day.holiday {
            background:#fef2f2;border-color:#fca5a5;color:#991b1b;
        }
        .cal-day.holiday:hover { background:#fee2e2;border-color:#f87171; }

        /* Within booking window highlight */
        .cal-day.in-window:not(.holiday):not(.past) {
            background:#f0fdf4;
        }
        .cal-day.in-window.today {
            background:#dcfce7;border-color:#2D6A4F;
        }

        /* Holiday dot indicator */
        .cal-day .h-dot {
            width:5px;height:5px;border-radius:50%;
            background:#e53e3e;margin-top:2px;flex-shrink:0;
        }

        /* Hearing dot indicator */
        .cal-day .hearing-dot {
            width:5px;height:5px;border-radius:50%;
            background:#2563eb;margin-top:2px;flex-shrink:0;
        }
        .cal-day .dot-row { display:flex;gap:3px;margin-top:2px; }
        .cal-day .hearing-count-label {
            font-size:8.5px;font-weight:700;color:#1e40af;
            line-height:1;margin-top:1px;letter-spacing:.01em;
            white-space:nowrap;
        }
        .cal-day.has-hearing:not(.holiday) {
            background:#eff6ff;border-color:#bfdbfe;
        }
        .cal-day.has-hearing:hover:not(.past) {
            background:#dbeafe;border-color:#60a5fa;
        }

        /* ── Hearing details modal ── */
        .hearing-modal-bg {
            display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
            z-index:99999;justify-content:center;align-items:center;
            padding:20px;box-sizing:border-box;overflow-y:auto;
        }
        .hearing-modal-bg.open { display:flex; }
        .hearing-modal-box {
            background:#fff;border-radius:16px;padding:26px;
            width:100%;max-width:480px;max-height:85vh;overflow-y:auto;
            position:relative;animation:hmIn .2s ease;
        }
        @keyframes hmIn { from{transform:translateY(14px);opacity:0} to{transform:translateY(0);opacity:1} }
        .hearing-modal-box h2 { font-size:17px;color:#1a202c;margin:0 0 4px; }
        .hearing-modal-box .hmsub { font-size:13px;color:#64748b;margin:0 0 18px; }
        .hearing-modal-close {
            position:absolute;top:14px;right:16px;background:none;border:none;
            font-size:22px;cursor:pointer;color:#94a3b8;line-height:1;
        }
        .hearing-case-card {
            border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px;
            margin-bottom:12px;
        }
        .hearing-case-card:last-child { margin-bottom:0; }
        .hearing-time-badge {
            display:inline-block;background:#eff6ff;color:#1e40af;
            font-size:11px;font-weight:700;padding:3px 10px;
            border-radius:99px;margin-bottom:8px;
        }
        .hearing-subject { font-size:14px;font-weight:600;color:#1a202c;margin-bottom:8px; }
        .hearing-row { display:flex;gap:8px;font-size:12px;color:#64748b;margin-bottom:4px; }
        .hearing-row strong { color:#374151;min-width:90px;display:inline-block; }
        .hearing-lupon-list { display:flex;flex-wrap:wrap;gap:6px;margin-top:6px; }
        .hearing-lupon-pill {
            font-size:11px;font-weight:600;padding:3px 10px;border-radius:99px;
            background:#f0fdf4;color:#065f46;border:1px solid #bbf7d0;
        }
        .hearing-view-link {
            display:inline-block;margin-top:8px;font-size:12px;font-weight:700;
            color:#1B4332;text-decoration:underline;
        }
        .no-hearings { text-align:center;padding:24px;color:#94a3b8;font-size:13px;font-style:italic; }

        /* Day number */
        .cal-day .day-num { line-height:1; }

        /* Legend */
        .cal-legend {
            display:flex;gap:16px;flex-wrap:wrap;
            padding:14px 24px;border-top:1px solid #f1f5f9;
            background:#fafbfc;
        }
        .legend-item { display:flex;align-items:center;gap:6px;font-size:12px;color:#64748b; }
        .legend-dot  { width:12px;height:12px;border-radius:4px;flex-shrink:0; }
        .legend-dot.window  { background:#f0fdf4;border:1px solid #86efac; }
        .legend-dot.holiday { background:#fef2f2;border:1px solid #fca5a5; }
        .legend-dot.today   { background:#f0fdf4;border:2px solid #2D6A4F; }

        /* ══════════════════════════════
           RIGHT PANEL
        ══════════════════════════════ */
        .right-panel { display:flex;flex-direction:column;gap:18px; }

        .panel-card {
            background:#fff;border-radius:14px;border:1px solid #edf2f7;
            padding:22px;
        }
        .panel-card h3 {
            font-size:14px;font-weight:700;color:#1a202c;
            margin:0 0 16px;display:flex;align-items:center;gap:8px;
        }

        /* Booking window slider */
        .window-display {
            text-align:center;margin-bottom:14px;
        }
        .window-number {
            font-size:48px;font-weight:700;color:#1B4332;line-height:1;
        }
        .window-label {
            font-size:13px;color:#64748b;margin-top:4px;
        }
        .window-note {
            font-size:12px;color:#94a3b8;margin-top:2px;
        }

        input[type=range] {
            width:100%;accent-color:#1B4332;cursor:pointer;
            height:6px;margin:8px 0 14px;
        }

        .preset-btns {
            display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;
        }
        .preset-btn {
            padding:6px 12px;border-radius:8px;border:1px solid #e2e8f0;
            background:#fff;font-size:12px;font-weight:600;cursor:pointer;
            color:#374151;transition:all .15s;font-family:inherit;
        }
        .preset-btn:hover  { border-color:#1B4332;color:#1B4332;background:#f0fdf4; }
        .preset-btn.active { border-color:#1B4332;background:#1B4332;color:#fff; }

        .btn-save-window {
            width:100%;padding:10px;background:#1B4332;color:#fff;
            border:none;border-radius:8px;font-weight:600;font-size:13px;
            cursor:pointer;font-family:inherit;transition:background .2s;
        }
        .btn-save-window:hover { background:#004d2c; }

        /* Add holiday form */
        .add-h-field { margin-bottom:12px; }
        .add-h-field label { font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px; }
        .add-h-input {
            width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;
            font-size:13px;font-family:inherit;box-sizing:border-box;background:#f8fafc;
        }
        .add-h-input:focus { outline:none;border-color:#1B4332;background:#fff; }
        .btn-add-holiday {
            width:100%;padding:10px;background:#2563eb;color:#fff;
            border:none;border-radius:8px;font-weight:600;font-size:13px;
            cursor:pointer;font-family:inherit;transition:background .2s;
        }
        .btn-add-holiday:hover { background:#1d4ed8; }

        /* Holiday list */
        .holiday-list { max-height:260px;overflow-y:auto; }
        .holiday-item {
            display:flex;align-items:center;gap:10px;
            padding:10px 0;border-bottom:1px solid #f8fafc;
        }
        .holiday-item:last-child { border-bottom:none; }
        .h-date-badge {
            background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;
            border-radius:8px;padding:4px 10px;font-size:11px;font-weight:700;
            white-space:nowrap;flex-shrink:0;
        }
        .h-label-text { font-size:13px;color:#374151;flex:1;min-width:0;font-weight:500; }
        .btn-del-holiday {
            background:none;border:none;cursor:pointer;color:#94a3b8;
            font-size:18px;line-height:1;padding:2px;transition:color .15s;flex-shrink:0;
        }
        .btn-del-holiday:hover { color:#dc2626; }

        .no-holidays {
            text-align:center;padding:20px;color:#94a3b8;font-size:13px;
            font-style:italic;
        }

        /* Toast */
        .toast{position:fixed;bottom:26px;right:26px;z-index:99999;color:#fff;padding:13px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.18);transform:translateY(70px);opacity:0;transition:all .32s cubic-bezier(0.34,1.56,0.64,1);pointer-events:none;}
        .toast.show{transform:translateY(0);opacity:1;}

        /* Notification bell */
        .notif-wrapper{position:relative;cursor:pointer;opacity:0.6;transition:opacity 0.2s;}
        .notif-wrapper:hover{opacity:1;}
        .notif-badge{box-sizing:border-box;position:absolute;top:4px;right:4px;background:#e53e3e;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:none;align-items:center;justify-content:center;padding:0 4px;border:2px solid #004d2c;line-height:1;}
        .notif-badge.has-notif{display:flex;}
        .notif-overlay{display:none;position:fixed;inset:0;z-index:9000;}
        .notif-overlay.show{display:block;}
        .notif-dropdown{position:fixed;top:0;left:-420px;width:360px;height:100vh;background:#fff;border-radius:0 16px 16px 0;box-shadow:6px 0 30px rgba(0,0,0,.15);z-index:9999;display:flex;flex-direction:column;transition:left .32s cubic-bezier(0.4,0,0.2,1);overflow:hidden;}
        .notif-dropdown.open{left:76px;}
        .notif-panel-header{background:#004d2c;color:#fff;padding:20px 22px 16px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;}
        .notif-panel-header h3{font-size:15px;font-weight:600;margin:0 0 3px;}
        .notif-panel-header small{font-size:11px;opacity:.75;display:block;}
        .mark-all-read-btn{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;}
        .notif-list{overflow-y:auto;flex:1;}
        .n-item{display:flex;gap:14px;padding:15px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background .15s;}
        .n-item:hover{background:#f8fafc;}
        .n-item.unread{background:#f0fdf4;border-left:3px solid #2D6A4F;}
        .n-dot{width:36px;height:36px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .n-dot img{width:16px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg);}
        .n-item.unread .n-dot{background:#2D6A4F;}
        .n-item.unread .n-dot img{filter:brightness(0) invert(1);}
        .n-body strong{display:block;font-size:13px;color:#1a202c;margin-bottom:2px;overflow:hidden;text-overflow:ellipsis;max-width:240px;}
        .n-body p{font-size:12px;color:#64748b;margin:0 0 3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
        .n-body time{font-size:11px;color:#94a3b8;}
        .n-empty{text-align:center;padding:50px 20px;color:#94a3b8;}
        .n-empty img{width:40px;opacity:.28;display:block;margin:0 auto 10px;}

        /* ===================================
           RESPONSIVE — placed last so these actually win the cascade
           =================================== */
        @media screen and (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: 6px 0 24px rgba(0,0,0,0.18); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .hamburger-btn { display: flex; }
            .app-topbar { left: 0; padding: 0 16px; }
            .portal-content { left: 0; }
            .notif-dropdown.open { left: 0 !important; }
            .cal-layout { grid-template-columns: 1fr; }
        }
        @media screen and (max-width: 768px) {
            .topbar-eyebrow { display: none; }
            .topbar-title { font-size: 0.95rem; }
            .portal-content { padding: 20px 16px 24px !important; }
            .page-header h1 { font-size: 19px; }
            .page-header p { font-size: 13px; }
            .notif-dropdown { width: 100%; max-width: 100vw; }
        }
        @media screen and (max-width: 480px) {
            .page-header h1 { font-size: 18px; }
        }
    </style>
</head>
<body class="admin-dashboard-layout">

    <!-- Top bar -->
    <header class="app-topbar">
        <div class="topbar-left">
            <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
                <img src="menu.png" alt="Menu">
            </button>
            <div class="topbar-titles">
                <span class="topbar-eyebrow">Admin Panel</span>
                <span class="topbar-title">Calendar</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-avatar" title="<?php echo htmlspecialchars($admin_display_name); ?>">
                <?php echo htmlspecialchars($admin_initials); ?>
            </div>
        </div>
    </header>

    <!-- Sidebar drawer backdrop (mobile only) -->
    <div class="res-notif-overlay" id="sidebarDrawerOverlay" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.25);"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebarNav">
        <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close menu">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        <div class="sidebar-top">
            <a href="admin-dashboard.php"          class="nav-item" title="Dashboard"><img src="dashboard.png"></a>
            <a href="admin-lupon-assignments.php"   class="nav-item" title="Lupon Assignments"><img src="people.png"></a>
            <a href="admin-committee.php"           class="nav-item" title="Committee"><img src="committee.png"></a>
            <a href="admin-calendar.php"            class="nav-item active" title="Calendar"><img src="calendar.png"></a>
            <a href="admin-file-maintenance.php"    class="nav-item" title="File Maintenance"><img src="file.png"></a>
            <a href="admin-audit-trail.php"         class="nav-item" title="Audit Trail"><img src="audit.png"></a>
            <div class="nav-item notif-wrapper" id="bellBtn">
                <img src="bell.png" id="bellIcon">
                <span class="notif-badge" id="notifBadge"></span>
            </div>
            <a href="#" onclick="openLogoutModal(); return false;" class="nav-item logout-item" title="Logout">
                <img src="logout.png">
            </a>
        </div>
        <div class="sidebar-bottom">
            <div class="sidebar-avatar" title="<?php echo htmlspecialchars($admin_display_name); ?>">
                <?php echo htmlspecialchars($admin_initials); ?>
            </div>
        </div>
    </nav>

    <!-- Notification panel -->
    <div class="notif-overlay" id="notifOverlay"></div>
    <div class="notif-dropdown" id="notifDropdown">
        <div class="notif-panel-header">
            <div><h3>Notifications</h3><small id="nLabel">Loading…</small></div>
            <button class="mark-all-read-btn" id="markReadBtn">Mark all read</button>
        </div>
        <div class="notif-list" id="nList">
            <div class="n-empty"><img src="bell.png"><p>No notifications yet.</p></div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <!-- ── Hearing Details Modal ── -->
    <div class="hearing-modal-bg" id="hearingModal">
        <div class="hearing-modal-box">
            <button class="hearing-modal-close" onclick="closeHearingModal()">×</button>
            <h2 id="hmTitle">Scheduled Hearings</h2>
            <p class="hmsub" id="hmSub"></p>
            <div id="hmList"></div>
        </div>
    </div>


    <main class="portal-content">

        <div class="page-header">
            <h1>Calendar Management</h1>
            <p>Control when residents can schedule appointments. Mark holidays and set the booking window.</p>
        </div>

        <div class="cal-layout">

            <!-- ── LEFT: Calendar ── -->
            <div>
                <div class="cal-card">
                    <div class="cal-nav">
                        <button class="cal-nav-btn" onclick="changeMonth(-1)">‹</button>
                        <h2 id="calTitle"></h2>
                        <button class="cal-nav-btn" onclick="changeMonth(1)">›</button>
                    </div>
                    <div class="cal-dow">
                        <span>Sun</span><span>Mon</span><span>Tue</span>
                        <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div class="cal-grid" id="calGrid"></div>
                    <div class="cal-legend">
                        <div class="legend-item"><div class="legend-dot today"></div> Today</div>
                        <div class="legend-item"><div class="legend-dot window"></div> Booking window</div>
                        <div class="legend-item"><div class="legend-dot holiday"></div> Holiday / blocked</div>
                        <div class="legend-item"><div class="legend-dot" style="background:#eff6ff;border:1px solid #bfdbfe;"></div> Has hearing(s)</div>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT: Controls ── -->
            <div class="right-panel">

                <!-- Booking window -->
                <div class="panel-card">
                    <h3>📅 Booking Window</h3>
                    <div class="window-display">
                        <div class="window-number" id="windowNum"><?php echo $booking_window; ?></div>
                        <div class="window-label">days ahead</div>
                        <div class="window-note" id="windowNote"></div>
                    </div>
                    <input type="range" id="windowSlider" min="1" max="60" step="1"
                           value="<?php echo $booking_window; ?>"
                           oninput="onWindowSlide(this.value)"
                           onchange="saveWindow()">
                    <div class="preset-btns" id="presetBtns">
                        <button class="preset-btn" onclick="setPreset(3)">3 days</button>
                        <button class="preset-btn" onclick="setPreset(7)">1 week</button>
                        <button class="preset-btn" onclick="setPreset(14)">2 weeks</button>
                        <button class="preset-btn" onclick="setPreset(30)">1 month</button>
                    </div>
                    <button class="btn-save-window" onclick="saveWindow()">Save Booking Window</button>
                </div>

                <!-- Add holiday -->
                <div class="panel-card">
                    <h3>🚫 Block a Date / Holiday</h3>
                    <div class="add-h-field">
                        <label>Date</label>
                        <input type="date" id="hDate" class="add-h-input"
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button class="btn-add-holiday" onclick="addHoliday()">Block Date</button>
                </div>

                <!-- Holiday list -->
                <div class="panel-card">
                    <h3>📋 Blocked Dates</h3>
                    <div class="holiday-list" id="holidayList">
                        <?php if (empty($holidays)): ?>
                        <div class="no-holidays">No dates blocked yet.</div>
                        <?php else: foreach ($holidays as $h): ?>
                        <div class="holiday-item" id="hitem_<?php echo $h['id']; ?>">
                            <span class="h-date-badge"><?php echo date("M j, Y", strtotime($h['holiday_date'])); ?></span>
                            <span class="h-label-text"><?php echo htmlspecialchars($h['label']); ?></span>
                            <button class="btn-del-holiday" onclick="deleteHoliday(<?php echo $h['id']; ?>, '<?php echo $h['holiday_date']; ?>')" title="Remove">×</button>
                        </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        // ── State ──────────────────────────────────────────────────────────────
        // Restore the previously-viewed month from the URL if present, so a
        // page reload (or someone bookmarking/sharing the link) doesn't dump
        // the admin back to the current month.
        const urlParams = new URLSearchParams(window.location.search);
        let currentYear  = urlParams.has('y') ? parseInt(urlParams.get('y')) : new Date().getFullYear();
        let currentMonth = urlParams.has('m') ? parseInt(urlParams.get('m')) : new Date().getMonth(); // 0-indexed
        let bookingWindow = <?php echo $booking_window; ?>;

        // Holidays keyed by YYYY-MM-DD
        let holidayMap = {};
        <?php foreach ($holidays as $h): ?>
        holidayMap['<?php echo $h['holiday_date']; ?>'] = { id: <?php echo $h['id']; ?>, label: '<?php echo addslashes($h['label']); ?>' };
        <?php endforeach; ?>

        // Hearings keyed by YYYY-MM-DD -> array of cases
        const hearingMap = <?php echo json_encode($hearing_map, JSON_HEX_TAG); ?>;

        const today = new Date();
        today.setHours(0,0,0,0);

        // ── Calendar rendering ─────────────────────────────────────────────────
        function renderCalendar() {
            const title  = document.getElementById('calTitle');
            const grid   = document.getElementById('calGrid');
            const months = ['January','February','March','April','May','June',
                            'July','August','September','October','November','December'];

            title.textContent = months[currentMonth] + ' ' + currentYear;
            grid.innerHTML    = '';

            const firstDay = new Date(currentYear, currentMonth, 1).getDay(); // 0=Sun
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            // Compute booking window max date
            const maxDate = new Date(today);
            maxDate.setDate(maxDate.getDate() + bookingWindow);

            // Empty cells before first day
            for (let i = 0; i < firstDay; i++) {
                const el = document.createElement('div');
                el.className = 'cal-day empty';
                grid.appendChild(el);
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj  = new Date(currentYear, currentMonth, d);
                dateObj.setHours(0,0,0,0);
                const dateStr  = formatDate(dateObj);

                const el = document.createElement('div');
                el.className   = 'cal-day';

                const isToday   = dateObj.getTime() === today.getTime();
                const isPast    = dateObj < today;
                const isHoliday = !!holidayMap[dateStr];
                const inWindow  = !isPast && dateObj <= maxDate && !isToday;
                const hasHearing = !!(hearingMap[dateStr] && hearingMap[dateStr].length > 0);

                if (isPast && !isToday) el.classList.add('past');
                if (isToday)   el.classList.add('today');
                if (isHoliday) el.classList.add('holiday');
                if (inWindow && !isHoliday)  el.classList.add('in-window');
                if (hasHearing) el.classList.add('has-hearing');

                const numSpan = document.createElement('span');
                numSpan.className   = 'day-num';
                numSpan.textContent = d;
                el.appendChild(numSpan);

                // Dot row (holiday + hearing indicators)
                if (isHoliday || hasHearing) {
                    const dotRow = document.createElement('div');
                    dotRow.className = 'dot-row';
                    if (isHoliday) {
                        const dot = document.createElement('div');
                        dot.className = 'h-dot';
                        dotRow.appendChild(dot);
                    }
                    if (hasHearing) {
                        const dot = document.createElement('div');
                        dot.className = 'hearing-dot';
                        dotRow.appendChild(dot);
                    }
                    el.appendChild(dotRow);
                }

                // Tiny case-count label
                if (hasHearing) {
                    const countLabel = document.createElement('span');
                    countLabel.className = 'hearing-count-label';
                    const n = hearingMap[dateStr].length;
                    countLabel.textContent = n === 1 ? '1 case' : `${n} cases`;
                    el.appendChild(countLabel);
                }

                if (isHoliday) {
                    el.title = holidayMap[dateStr].label + (hasHearing ? ` · ${hearingMap[dateStr].length} hearing(s)` : '');
                } else if (hasHearing) {
                    el.title = `${hearingMap[dateStr].length} hearing(s) scheduled`;
                }

                // Click: show hearing details if any, otherwise toggle holiday (future dates only)
                if (hasHearing) {
                    el.style.cursor = 'pointer';
                    el.addEventListener('click', () => openHearingModal(dateStr, dateObj));
                } else if (!isPast || isToday) {
                    el.addEventListener('click', () => clickDay(dateStr, dateObj, el));
                    if (!isPast && !isToday) el.style.cursor = 'pointer';
                }


                grid.appendChild(el);
            }

            updatePresetHighlight();
        }

        function changeMonth(dir) {
            currentMonth += dir;
            if (currentMonth > 11) { currentMonth = 0;  currentYear++; }
            if (currentMonth < 0)  { currentMonth = 11; currentYear--; }
            syncMonthToUrl();
            renderCalendar();
        }

        function syncMonthToUrl() {
            const params = new URLSearchParams(window.location.search);
            params.set('y', currentYear);
            params.set('m', currentMonth);
            history.replaceState(null, '', '?' + params.toString());
        }

        function formatDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        // ── Hearing details modal ───────────────────────────────────────────────
        function escH(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

        function openHearingModal(dateStr, dateObj) {
            const cases = hearingMap[dateStr] || [];
            const title = document.getElementById('hmTitle');
            const sub   = document.getElementById('hmSub');
            const list  = document.getElementById('hmList');

            const dateLabel = dateObj.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            title.textContent = '📅 ' + dateLabel;
            sub.textContent = cases.length === 1 ? '1 hearing scheduled' : `${cases.length} hearings scheduled`;

            if (cases.length === 0) {
                list.innerHTML = '<div class="no-hearings">No hearings scheduled on this date.</div>';
            } else {
                list.innerHTML = cases.map(c => `
                    <div class="hearing-case-card">
                        <span class="hearing-time-badge">🕐 ${escH(c.time)}</span>
                        <div class="hearing-subject">${escH(c.subject)}</div>
                        <div class="hearing-row"><strong>Complainant</strong> ${escH(c.complainant)}</div>
                        <div class="hearing-row"><strong>Respondent</strong> ${escH(c.respondent)}</div>
                        ${c.lupon.length ? `
                        <div class="hearing-row" style="align-items:flex-start;">
                            <strong>Lupon Panel</strong>
                            <div class="hearing-lupon-list">
                                ${c.lupon.map(n => `<span class="hearing-lupon-pill">👤 ${escH(n)}</span>`).join('')}
                            </div>
                        </div>` : `
                        <div class="hearing-row"><strong>Lupon Panel</strong> <em style="color:#94a3b8;">Not yet assigned</em></div>
                        `}
                        <a class="hearing-view-link" href="admin-view-complaint.php?id=${c.complaint_id}">View Case →</a>
                    </div>
                `).join('');
            }

            document.getElementById('hearingModal').classList.add('open');
        }

        function closeHearingModal() {
            document.getElementById('hearingModal').classList.remove('open');
        }

        document.getElementById('hearingModal').addEventListener('click', e => {
            if (e.target.id === 'hearingModal') closeHearingModal();
        });


        function clickDay(dateStr, dateObj, el) {
            if (dateObj < today) return;

            if (holidayMap[dateStr]) {
                // Already a holiday — delete it
                deleteHoliday(holidayMap[dateStr].id, dateStr);
            } else {
                // Block it immediately — no label required, no side panel needed
                const dateLabel = dateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
                if (confirm(`Block ${dateLabel}? Residents won't be able to book this date.`)) {
                    blockDate(dateStr, '');
                }
            }
        }

        // ── Booking window ─────────────────────────────────────────────────────
        function onWindowSlide(val) {
            bookingWindow = parseInt(val);
            document.getElementById('windowNum').textContent = val;
            const maxD = new Date(today);
            maxD.setDate(maxD.getDate() + parseInt(val));
            document.getElementById('windowNote').textContent =
                'Residents can book up to ' + formatDate(maxD);
            updatePresetHighlight();
            renderCalendar();
        }

        function setPreset(days) {
            bookingWindow = days;
            document.getElementById('windowSlider').value = days;
            onWindowSlide(days);
            saveWindow();
        }

        function updatePresetHighlight() {
            document.querySelectorAll('.preset-btn').forEach(btn => {
                const v = parseInt(btn.textContent);
                btn.classList.toggle('active',
                    v === bookingWindow ||
                    (btn.textContent === '1 week'  && bookingWindow === 7)  ||
                    (btn.textContent === '2 weeks' && bookingWindow === 14) ||
                    (btn.textContent === '1 month' && bookingWindow === 30) ||
                    (btn.textContent === '3 days'  && bookingWindow === 3)
                );
            });
        }

        function saveWindow() {
            const fd = new FormData();
            fd.append('action', 'update_window');
            fd.append('days',   bookingWindow);
            fetch('save_calendar.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) showToast(`Booking window set to ${d.days} days.`);
                    else           showToast(d.error || 'Failed to save.', '#dc2626');
                })
                .catch(() => showToast('Network error.', '#dc2626'));
        }

        // ── Holidays ───────────────────────────────────────────────────────────
        function blockDate(dateStr, label) {
            label = (label || '').trim() || 'Blocked';

            const fd = new FormData();
            fd.append('action', 'add_holiday');
            fd.append('date',   dateStr);
            fd.append('label',  label);

            fetch('save_calendar.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showToast('Date blocked successfully!');

                        if (d.id) {
                            // Update in place — no reload, so the currently
                            // viewed month and any unsaved UI state survive.
                            holidayMap[dateStr] = { id: d.id, label: label };

                            const list = document.getElementById('holidayList');
                            const empty = list.querySelector('.no-holidays');
                            if (empty) empty.remove();

                            const item = document.createElement('div');
                            item.className = 'holiday-item';
                            item.id = 'hitem_' + d.id;
                            const dateLabel = new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                            item.innerHTML = `<span class="h-date-badge">${dateLabel}</span><span class="h-label-text">${label.replace(/</g,'&lt;')}</span>`;
                            const removeBtn = document.createElement('button');
                            removeBtn.className = 'btn-del-holiday';
                            removeBtn.title = 'Remove';
                            removeBtn.textContent = '×';
                            removeBtn.onclick = () => deleteHoliday(d.id, dateStr);
                            item.appendChild(removeBtn);
                            list.prepend(item);

                            renderCalendar();
                        } else {
                            // Server didn't return the new id for some reason —
                            // fall back to a reload. The month is still safe
                            // since it's now tracked in the URL.
                            location.reload();
                        }
                    } else {
                        showToast(d.error || 'Failed to block date.', '#dc2626');
                    }
                })
                .catch(() => showToast('Network error.', '#dc2626'));
        }

        function addHoliday() {
            const date = document.getElementById('hDate').value;
            if (!date) { showToast('Please select a date.', '#b45309'); return; }

            blockDate(date, '');
            document.getElementById('hDate').value = '';
        }

        function deleteHoliday(id, dateStr) {
            if (!confirm('Remove this blocked date?')) return;
            const fd = new FormData();
            fd.append('action', 'delete_holiday');
            fd.append('id',     id);
            fetch('save_calendar.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        delete holidayMap[dateStr];
                        // Remove from list
                        const item = document.getElementById('hitem_' + id);
                        if (item) item.remove();
                        // Check if list is empty
                        const list = document.getElementById('holidayList');
                        if (!list.querySelector('.holiday-item')) {
                            list.innerHTML = '<div class="no-holidays">No dates blocked yet.</div>';
                        }
                        renderCalendar();
                        showToast('Blocked date removed.');
                    } else {
                        showToast(d.error || 'Failed to remove.', '#dc2626');
                    }
                })
                .catch(() => showToast('Network error.', '#dc2626'));
        }

        // ── Toast ──────────────────────────────────────────────────────────────
        function showToast(msg, bg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.background = bg || '#1B4332';
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // ── Notification bell ──────────────────────────────────────────────────
        const bellBtn=document.getElementById('bellBtn'),dropdown=document.getElementById('notifDropdown'),overlay=document.getElementById('notifOverlay'),badge=document.getElementById('notifBadge'),nList=document.getElementById('nList'),nLabel=document.getElementById('nLabel'),markBtn=document.getElementById('markReadBtn');
        let bellOpen=false;
        function taAgo(d){const s=Math.floor((new Date()-new Date(d))/1000);if(s<60)return'just now';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';}
        function escN(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
        function renderBell({notifications,unread_count}){
            if(unread_count>0){badge.textContent=unread_count>99?'99+':unread_count;badge.classList.add('has-notif');}
            else badge.classList.remove('has-notif');
            nLabel.textContent=unread_count>0?`${unread_count} unread`:'All caught up!';
            nList.innerHTML=notifications.length?notifications.map(n=>`<a class="n-item ${n.is_read==0?'unread':''}" href="${n.type === 'id_verification' ? 'admin-review-id.php?resident_id=' + n.resident_id : (n.type === 'new_appointment' || n.type === 'unassigned_warning') ? 'admin-lupon-assignments.php?complaint_id=' + n.complaint_id : 'admin-view-complaint.php?id=' + n.complaint_id}">
<div class="n-dot"><img src="file.png"></div><div class="n-body"><strong>${escN(n.subject)}</strong><p>${escN(n.message)}</p><time>${taAgo(n.created_at)}</time></div></a>`).join(''):`<div class="n-empty"><img src="bell.png"><p>No notifications.</p></div>`;
        }
        function fetchBell(){fetch('get_notifications.php?action=fetch').then(r=>r.json()).then(renderBell).catch(()=>{});}
        bellBtn.addEventListener('click',()=>{if(bellOpen){dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;}else{dropdown.classList.add('open');overlay.classList.add('show');bellOpen=true;}});
        overlay.addEventListener('click',()=>{dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;});
        markBtn.addEventListener('click',()=>fetch('get_notifications.php?action=mark_read').then(r=>r.json()).then(()=>fetchBell()));
        fetchBell();
fetch('check_upcoming_unassigned.php')
    .then(r => r.json())
    .then(d => { if (d.flagged_count > 0) fetchBell(); });
setInterval(fetchBell,15000);

// ── Sidebar drawer (mobile) ──
(function() {
    const sidebar   = document.getElementById('sidebarNav');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn  = document.getElementById('sidebarCloseBtn');
    const drawerOv  = document.getElementById('sidebarDrawerOverlay');
    let drawerOpen = false;

    function openDrawer()  { sidebar.classList.add('open'); drawerOv.style.display = 'block'; drawerOpen = true; }
    function closeDrawer() { sidebar.classList.remove('open'); drawerOv.style.display = 'none'; drawerOpen = false; }

    if (hamburger) hamburger.addEventListener('click', () => drawerOpen ? closeDrawer() : openDrawer());
    if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
    drawerOv.addEventListener('click', closeDrawer);
})();

        // ── Init ───────────────────────────────────────────────────────────────
        onWindowSlide(bookingWindow);
        renderCalendar();
    </script>

<!-- ── Logout confirmation modal ── -->
<div id="logoutOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99999;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(3px);" onclick="if(event.target===this)closeLogoutModal()">
    <div style="background:#fff;border-radius:18px;padding:36px 32px 28px;width:100%;max-width:380px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,0.18);animation:lgIn .22s cubic-bezier(.34,1.2,.64,1);position:relative;">
        <button onclick="closeLogoutModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
        <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <img src="logout.png" style="width:28px;height:28px;filter:invert(29%) sepia(91%) saturate(1500%) hue-rotate(330deg) brightness(90%);">
        </div>
        <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:700;color:#1a202c;margin:0 0 8px;">Log out of your account?</h2>
        <p style="font-family:'Poppins',sans-serif;font-size:13px;color:#64748b;margin:0 0 28px;line-height:1.6;">You will be returned to the login page.<br>Any unsaved changes will be lost.</p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <a href="logout.php" style="display:block;background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;padding:13px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;text-decoration:none;box-shadow:0 4px 14px rgba(220,38,38,0.28);">Yes, log me out</a>
            <button onclick="closeLogoutModal()" style="background:#f8fafc;color:#374151;border:1.5px solid #e2e8f0;padding:13px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Cancel, stay logged in</button>
        </div>
    </div>
</div>
<style>
@keyframes lgIn { from{transform:translateY(16px) scale(.97);opacity:0} to{transform:translateY(0) scale(1);opacity:1} }
</style>
<script>
function openLogoutModal()  { const o=document.getElementById('logoutOverlay'); o.style.display='flex'; }
function closeLogoutModal() { document.getElementById('logoutOverlay').style.display='none'; }
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLogoutModal(); });
</script>

</body>
</html>
