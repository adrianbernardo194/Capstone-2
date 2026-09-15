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

// ── Fetch all approved complaints that have a resident-requested schedule ──
// Status = 'Approved' OR 'In Remediation' (after resident picked schedule)
// and appointment_date is set (resident already chose their preferred slot)
$pending_assign = $conn->query("
    SELECT c.*,
           r.full_name AS resident_fullname,
           r.email     AS resident_email
    FROM complaints c
    LEFT JOIN residents r ON r.id = c.resident_id
    WHERE c.appointment_date IS NOT NULL
      AND c.appointment_date != ''
      AND c.status NOT IN ('Completed','Rejected','Cannot Be Handled')
    ORDER BY c.appointment_date ASC, c.appointment_time ASC
");

// ── Fetch all active lupon members ──
$lupon_all = $conn->query("SELECT * FROM lupon_members WHERE is_active=1 ORDER BY name");
$lupon_members = [];
while ($lm = $lupon_all->fetch_assoc()) $lupon_members[] = $lm;

// Highlight a specific complaint card if linked from a notification
$highlight_id = (int)($_GET['complaint_id'] ?? 0);
if ($highlight_id > 0) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE complaint_id=$highlight_id AND type='new_appointment'");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupon Assignments - Barangay San Roque</title>
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

        * { box-sizing: border-box; }
        * { font-family: 'Poppins', sans-serif; }

        html, body { margin: 0; padding: 0; overflow-x: hidden; }
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

        /* ── Page header ── */
        .page-header { margin-bottom:32px; }
        .page-header h1 { font-size:24px;color:#1a202c;font-weight:700;margin:0 0 4px; }
        .page-header p  { font-size:14px;color:#64748b;margin:0; }

        /* ── Stats row ── */
        .assign-stats { display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:32px; }
        .astat { background:#fff;border-radius:12px;padding:20px 24px;border:1px solid #edf2f7;display:flex;align-items:center;gap:16px;min-width:0; }
        .astat-icon { width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0; }
        .astat-icon.blue   { background:#eff6ff; }
        .astat-icon.green  { background:#f0fdf4; }
        .astat-icon.amber  { background:#fffbeb; }
        .astat-info { min-width:0; }
        .astat-info span   { font-size:12px;color:#64748b;display:block;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .astat-info strong { font-size:22px;font-weight:700;color:#1a202c; }

        /* ── Assignment card ── */
        .assign-card {
            background:#fff;border-radius:14px;border:1px solid #edf2f7;
            margin-bottom:20px;overflow:hidden;
            transition:box-shadow 0.2s;
        }
        .assign-card:hover { box-shadow:0 4px 20px rgba(0,0,0,0.06); }

        /* Highlighted card (linked from notification) */
        .assign-card.highlight-card {
            border-color:#2563eb;
            box-shadow:0 0 0 3px rgba(37,99,235,0.15);
            animation: highlightPulse 1.8s ease-in-out 2;
        }
        @keyframes highlightPulse {
            0%, 100% { box-shadow:0 0 0 3px rgba(37,99,235,0.15); }
            50%      { box-shadow:0 0 0 6px rgba(37,99,235,0.25); }
        }

        /* Card header stripe */
        .assign-card-header {
            display:grid;
            grid-template-columns:180px 1fr auto;
            align-items:center;
            gap:20px;
            padding:18px 24px;
            border-bottom:1px solid #f1f5f9;
            background:#fafbfc;
        }
        .assign-card-header > div { min-width:0; }
        .case-num { font-size:13px;font-weight:700;color:#1B4332; }
        .case-subject { font-size:14px;font-weight:600;color:#1a202c;margin-bottom:2px;overflow-wrap:anywhere; }
        .case-complainant { font-size:12px;color:#64748b;overflow-wrap:anywhere; }
        .assign-status-pill { padding:4px 12px;border-radius:99px;font-size:11px;font-weight:700; }
        .assign-status-pill.approved       { background:#d1fae5;color:#065f46; }
        .assign-status-pill.in-process { background:#dbeafe;color:#1e40af; }
        .assign-status-pill.pending        { background:#fef3c7;color:#92400e; }

        /* Card body */
        .assign-card-body { display:grid;grid-template-columns:1fr 1fr;gap:0; }

        /* Left: resident's request */
        .resident-request {
            padding:20px 24px;
            border-right:1px solid #f1f5f9;
        }
        .resident-request h4 {
            font-size:12px;font-weight:700;color:#94a3b8;
            text-transform:uppercase;letter-spacing:.05em;
            margin:0 0 14px;
        }

        .req-info-row { display:flex;gap:8px;align-items:flex-start;margin-bottom:10px; }
        .req-label { font-size:11px;color:#94a3b8;font-weight:600;min-width:80px;padding-top:1px; }
        .req-value { font-size:13px;color:#374151;font-weight:500; }

        /* Preferred lupon pills */
        .lupon-pref-list { display:flex;flex-wrap:wrap;gap:6px;margin-top:4px; }
        .lupon-pref-pill {
            font-size:12px;font-weight:600;
            padding:4px 10px;border-radius:99px;
            background:#f0fdf4;color:#065f46;
            border:1px solid #bbf7d0;
        }

        /* Right: admin assignment form */
        .admin-assign {
            padding:20px 24px;
        }
        .admin-assign h4 {
            font-size:12px;font-weight:700;color:#1B4332;
            text-transform:uppercase;letter-spacing:.05em;
            margin:0 0 14px;display:flex;align-items:center;gap:6px;
        }

        .assign-field { margin-bottom:14px; }
        .assign-field label { font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px; }
        .assign-input {
            width:100%;padding:9px 12px;border:1px solid #e2e8f0;
            border-radius:8px;font-size:13px;font-family:inherit;
            box-sizing:border-box;background:#f8fafc;transition:border-color .15s;
        }
        .assign-input:focus { outline:none;border-color:#1B4332;background:#fff; }

        /* Lupon checkbox list */
        .lupon-check-list { display:flex;flex-direction:column;gap:6px;max-height:200px;overflow-y:auto; }
        .lupon-check-item {
            display:flex;align-items:center;gap:10px;
            padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:8px;
            cursor:pointer;transition:all .15s;font-size:13px;
        }
        .lupon-check-item:hover { border-color:#a7c4b5;background:#f7fdfb; }
        .lupon-check-item.checked { border-color:#1B4332;background:#f0fdf4; }
        .lupon-check-item.unavailable { opacity:.45;cursor:not-allowed;background:#f8fafc; }
        .lupon-check-item input[type=checkbox] { accent-color:#1B4332;width:14px;height:14px;flex-shrink:0; }
        .lupon-check-name { font-weight:600;color:#1a202c;flex:1; }
        .lupon-check-pos  { font-size:11px;color:#64748b; }
        .lupon-booked-badge {
            font-size:10px;font-weight:700;padding:2px 8px;
            border-radius:99px;background:#fee2e2;color:#991b1b;
            margin-left:auto;flex-shrink:0;
        }
        .lupon-avail-badge {
            font-size:10px;font-weight:700;padding:2px 8px;
            border-radius:99px;background:#d1fae5;color:#065f46;
            margin-left:auto;flex-shrink:0;
        }
        .pref-match-badge {
            font-size:10px;font-weight:700;padding:2px 8px;
            border-radius:99px;background:#eff6ff;color:#1e40af;margin-right:2px;
        }

        /* Availability note */
        .avail-note { font-size:12px;color:#64748b;margin-bottom:8px;min-height:16px; }
        .avail-note.warn { color:#b45309; }

        /* Selected count */
        .sel-count { font-size:12px;color:#1B4332;font-weight:600;margin-bottom:8px; }

        /* Action buttons */
        .assign-actions { display:flex;gap:10px;margin-top:16px; }
        .btn-assign {
            flex:1;padding:10px;background:#1B4332;color:#fff;
            border:none;border-radius:8px;font-weight:600;font-size:13px;
            cursor:pointer;font-family:inherit;transition:background .2s;
        }
        .btn-assign:hover { background:#004d2c; }
        .btn-assign:disabled { opacity:.5;cursor:not-allowed; }
        .btn-assign-secondary {
            padding:10px 16px;background:#fff;color:#374151;
            border:1px solid #e2e8f0;border-radius:8px;font-weight:500;font-size:13px;
            cursor:pointer;font-family:inherit;
        }

        /* Already assigned indicator */
        .assigned-indicator {
            display:flex;align-items:center;gap:10px;
            background:#f0fdf4;border:1px solid #86efac;border-radius:8px;
            padding:12px 14px;font-size:13px;color:#065f46;font-weight:500;
        }
        .assigned-indicator .edit-btn {
            margin-left:auto;font-size:12px;color:#1B4332;font-weight:700;
            cursor:pointer;text-decoration:underline;background:none;border:none;font-family:inherit;
        }

        /* Empty state */
        .empty-state { text-align:center;padding:60px 20px;color:#94a3b8; }
        .empty-state .empty-icon { font-size:48px;margin-bottom:16px; }
        .empty-state h3 { font-size:18px;color:#64748b;margin:0 0 8px; }
        .empty-state p  { font-size:14px;margin:0; }

        /* Toast */
        .toast { position:fixed;bottom:26px;right:26px;z-index:99999;background:#1B4332;color:#fff;padding:13px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.18);transform:translateY(70px);opacity:0;transition:all .32s cubic-bezier(0.34,1.56,0.64,1);pointer-events:none; }
        .toast.show { transform:translateY(0);opacity:1; }
        .toast.error { background:#dc2626; }

        /* Notification bell (same as dashboard) */
        .notif-wrapper{position:relative;cursor:pointer;opacity:0.6;transition:opacity 0.2s;}
        .notif-wrapper:hover{opacity:1;}
        .notif-badge{ position: absolute;
    box-sizing: border-box;
    top: 4px;
    right: 4px;
    background: #e53e3e;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid #004d2c;
    line-height: 1;}
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
        .n-body strong{display:block;font-size:13px;color:#1a202c;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px;}
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
        }
        @media screen and (max-width: 768px) {
            .topbar-eyebrow { display: none; }
            .topbar-title { font-size: 0.95rem; }
            .portal-content { padding: 20px 16px 24px !important; }

            .page-header h1 { font-size: 19px; }
            .page-header p { font-size: 13px; }

            .assign-stats { grid-template-columns: 1fr; gap: 12px; }
            .astat { padding: 14px 16px; gap: 10px; }
            .astat-icon { width: 38px; height: 38px; font-size: 17px; }
            .astat-info strong { font-size: 18px; }

            .assign-card-header { grid-template-columns: 1fr; gap: 8px; }
            .assign-card-body { grid-template-columns: 1fr; }
            .resident-request { border-right: none; border-bottom: 1px solid #f1f5f9; }
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
                <span class="topbar-title">Lupon Assignments</span>
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
            <a href="admin-dashboard.php"        class="nav-item" title="Dashboard"><img src="dashboard.png" title="Dashboard"></a>
            <a href="admin-lupon-assignments.php" class="nav-item active"><img src="people.png" title="Lupon Assignments"></a>
            <a href="admin-committee.php"         class="nav-item" title="Committee"><img src="committee.png" title="Committee"></a>
            <a href="admin-calendar.php"            class="nav-item" title="Calendar"><img src="calendar.png"></a>
            <a href="admin-file-maintenance.php"    class="nav-item" title="File Maintenance"><img src="file.png"></a>
            <a href="admin-audit-trail.php"          class="nav-item" title="Audit Trail"><img src="audit.png"></a>
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

    <main class="portal-content">
        <div class="page-header">
            <h1>Lupon Assignment</h1>
            <p>Review resident-requested schedules and assign Lupon Tagapamayapa members for each hearing.</p>
        </div>

        <?php
        // Stats
        $total_q    = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE appointment_date IS NOT NULL AND appointment_date != '' AND status NOT IN ('Completed','Rejected','Cannot Be Handled')")->fetch_assoc();
        $pending_q  = $conn->query("SELECT COUNT(*) as c FROM complaints WHERE appointment_date IS NOT NULL AND appointment_date != '' AND lupon_preference != '' AND status = 'Approved'")->fetch_assoc();
        $today_q    = $conn->query("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc();
        ?>
        <div class="assign-stats">
            <div class="astat">
                <div class="astat-icon blue">📋</div>
                <div class="astat-info"><span>Pending Assignment</span><strong><?php echo $total_q['c']; ?></strong></div>
            </div>
            <div class="astat">
                <div class="astat-icon amber">⏳</div>
                <div class="astat-info"><span>Awaiting Lupon Assign</span><strong><?php echo $pending_q['c']; ?></strong></div>
            </div>
            <div class="astat">
                <div class="astat-icon green">📅</div>
                <div class="astat-info"><span>Hearings Today</span><strong><?php echo $today_q['c']; ?></strong></div>
            </div>
        </div>

        <?php
        $rows = [];
        while ($r = $pending_assign->fetch_assoc()) $rows[] = $r;
        if (empty($rows)):
        ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No pending assignments</h3>
            <p>Residents with approved cases will appear here once they choose a preferred schedule.</p>
        </div>
        <?php else: foreach ($rows as $c):
            // Get already-assigned lupon for this complaint
            $assigned_res = $conn->query("
                SELECT a.lupon_member_id, lm.name, lm.position
                FROM appointments a
                INNER JOIN lupon_members lm ON lm.id = a.lupon_member_id
                WHERE a.complaint_id = {$c['id']}
            ");
            $assigned = [];
            while ($ar = $assigned_res->fetch_assoc()) $assigned[] = $ar;

            // Parse resident's preferred lupon names
            $pref_names = array_filter(array_map('trim', explode(',', $c['lupon_preference'] ?? '')));

            $status_cls = strtolower(str_replace(' ','-',$c['status']));
        ?>
        <div class="assign-card<?php echo ($highlight_id === (int)$c['id']) ? ' highlight-card' : ''; ?>" id="card_<?php echo $c['id']; ?>">

            <!-- Card header -->
            <div class="assign-card-header">
                <div>
                    <div class="case-num">BRGY-2026-0<?php echo $c['id']; ?></div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                        Filed <?php echo date("M j, Y", strtotime($c['date_filed'])); ?>
                    </div>
                </div>
                <div>
                    <div class="case-subject"><?php echo htmlspecialchars($c['subject']); ?></div>
                    <div class="case-complainant">
                        <?php echo htmlspecialchars($c['complainant_name']); ?>
                        <?php if ($c['resident_email']): ?>
                        · <span style="color:#64748b;"><?php echo htmlspecialchars($c['resident_email']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="assign-status-pill <?php echo $status_cls; ?>"><?php echo $c['status']; ?></span>
            </div>

            <!-- Card body -->
            <div class="assign-card-body">

                <!-- Left: Resident's requested schedule -->
                <div class="resident-request">
                    <h4>👤 Resident's Request</h4>

                    <div class="req-info-row">
                        <span class="req-label">Date</span>
                        <span class="req-value">
                            <?php echo !empty($c['appointment_date'])
                                ? date("l, F j, Y", strtotime($c['appointment_date']))
                                : '<em style="color:#94a3b8;">Not set</em>'; ?>
                        </span>
                    </div>
                    <div class="req-info-row">
                        <span class="req-label">Time</span>
                        <span class="req-value">
                            <?php echo !empty($c['appointment_time'])
                                ? htmlspecialchars($c['appointment_time'])
                                : '<em style="color:#94a3b8;">Not set</em>'; ?>
                        </span>
                    </div>
                    <div class="req-info-row">
                        <span class="req-label">Preferred</span>
                        <div>
                            <?php if (!empty($pref_names)): ?>
                            <div class="lupon-pref-list">
                                <?php foreach ($pref_names as $pn): ?>
                                <span class="lupon-pref-pill">👤 <?php echo htmlspecialchars($pn); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php else: ?>
                            <span class="req-value" style="color:#94a3b8;font-style:italic;">No preference set</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($assigned)): ?>
                    <div style="margin-top:16px;">
                        <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:8px;">Currently Assigned</div>
                        <div class="assigned-indicator">
                            <span>✅ <?php echo implode(', ', array_column($assigned, 'name')); ?></span>
                            <button class="edit-btn" onclick="editAssignment(<?php echo $c['id']; ?>)">Edit</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right: Admin assignment form -->
                <div class="admin-assign" id="assign_form_<?php echo $c['id']; ?>">
                    <h4>🗓️ Set Official Schedule</h4>

                    <div class="assign-field">
                        <label>Hearing Date</label>
                        <input type="date" class="assign-input" id="adate_<?php echo $c['id']; ?>"
                               value="<?php echo htmlspecialchars($c['appointment_date'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>"
                               onchange="onAdminDateTimeChange(<?php echo $c['id']; ?>)">
                    </div>

                    <div class="assign-field">
                        <label>Hearing Time</label>
                        <select class="assign-input" id="atime_<?php echo $c['id']; ?>"
                                onchange="onAdminDateTimeChange(<?php echo $c['id']; ?>)">
                            <option value="">— Select time —</option>
                            <?php foreach(['9:00 AM','9:30 AM','10:00 AM','10:30 AM','11:00 AM','11:30 AM','12:00 PM'] as $ts):
                                $sel = ($c['appointment_time'] === $ts) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $ts; ?>" <?php echo $sel; ?>><?php echo $ts; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="assign-field">
                        <label>Assign Lupon Members (max 3)</label>
                        <p class="avail-note" id="anote_<?php echo $c['id']; ?>">
                            <?php echo (!empty($c['appointment_date']) && !empty($c['appointment_time']))
                                ? 'Checking availability…'
                                : 'Select date and time to see availability.'; ?>
                        </p>
                        <p class="sel-count" id="acount_<?php echo $c['id']; ?>">0 / 3 selected</p>
                        <div class="lupon-check-list" id="alupon_<?php echo $c['id']; ?>">
                            <p style="color:#94a3b8;font-size:13px;">Loading…</p>
                        </div>
                    </div>

                    <div class="assign-actions">
                        <button class="btn-assign" id="abtn_<?php echo $c['id']; ?>"
                                onclick="saveAssignment(<?php echo $c['id']; ?>)" disabled>
                            <?php echo !empty($assigned) ? 'Update Assignment' : 'Confirm Assignment'; ?>
                        </button>
                        <a href="admin-view-complaint.php?id=<?php echo $c['id']; ?>"
                           class="btn-assign-secondary">View Case</a>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Auto-load availability if date+time already set
        (function() {
            const cid  = <?php echo $c['id']; ?>;
            const date = '<?php echo $c['appointment_date'] ?? ''; ?>';
            const time = '<?php echo $c['appointment_time'] ?? ''; ?>';
            const alreadyAssigned = <?php echo json_encode(array_column($assigned, 'lupon_member_id')); ?>;
            const prefNames = <?php echo json_encode($pref_names); ?>;
            if (date && time) {
                fetchAdminAvailability(cid, date, time, alreadyAssigned, prefNames);
            }
        })();
        </script>

        <?php endforeach; endif; ?>
    </main>

    <script>
        // ── Per-card state ──
        const cardState = {};   // cid -> { selected: Map(id->name) }

        function getState(cid) {
            if (!cardState[cid]) cardState[cid] = { selected: new Map() };
            return cardState[cid];
        }

        // ── Date/time change handler ──
        function onAdminDateTimeChange(cid) {
            const date = document.getElementById('adate_' + cid).value;
            const time = document.getElementById('atime_' + cid).value;
            if (!date || !time) return;

            // Reset selection
            getState(cid).selected = new Map();
            updateSelCount(cid);
            document.getElementById('abtn_' + cid).disabled = true;

            const note = document.getElementById('anote_' + cid);
            note.textContent = 'Checking availability…';
            note.className = 'avail-note';

            fetchAdminAvailability(cid, date, time, [], []);
        }

        // ── Fetch availability ──
        function fetchAdminAvailability(cid, date, time, preSelect, prefNames) {
            const url = `get_admin_availability.php?date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}&complaint_id=${cid}`;
            fetch(url)
                .then(r => r.json())
                .then(d => renderAdminLupon(cid, d.members || [], preSelect, prefNames))
                .catch(() => {
                    document.getElementById('anote_' + cid).textContent = 'Could not load availability.';
                });
        }

        // ── Render lupon checklist ──
        function renderAdminLupon(cid, members, preSelect, prefNames) {
            const list  = document.getElementById('alupon_' + cid);
            const note  = document.getElementById('anote_' + cid);
            const state = getState(cid);

            // Pre-select already-assigned members if available
            preSelect.forEach(pid => {
                const m = members.find(m => m.id == pid);
                if (m && m.is_available) state.selected.set(String(pid), m.name);
            });
            updateSelCount(cid);

            const availCount = members.filter(m => m.is_available).length;
            if (availCount === 0) {
                note.textContent = 'All Lupon members are booked at this slot. Please choose a different time.';
                note.className = 'avail-note warn';
            } else {
                note.textContent = `${availCount} of ${members.length} Lupon members available.`;
                note.className = 'avail-note';
            }

            list.innerHTML = members.map(m => {
                const avail    = m.is_available == 1;
                const isChecked = state.selected.has(String(m.id));
                const isPref   = prefNames.some(p => p.trim().toLowerCase() === m.name.toLowerCase());

                return `
                <div class="lupon-check-item ${avail ? (isChecked ? 'checked' : '') : 'unavailable'}"
                     id="ali_${cid}_${m.id}"
                     onclick="${avail ? `toggleAdminLupon(${cid}, ${m.id}, '${escJs(m.name)}', this)` : ''}">
                    <input type="checkbox" id="alc_${cid}_${m.id}"
                           ${isChecked ? 'checked' : ''} ${avail ? '' : 'disabled'}
                           onclick="event.stopPropagation()">
                    <div style="flex:1;min-width:0;">
                        <div class="lupon-check-name">
                            ${isPref ? `<span class="pref-match-badge">Preferred</span>` : ''}
                            ${escHtml(m.name)}
                        </div>
                        <div class="lupon-check-pos">${escHtml(m.position)}</div>
                    </div>
                    ${avail
                        ? `<span class="lupon-avail-badge">Available</span>`
                        : `<span class="lupon-booked-badge">Booked</span>`
                    }
                </div>`;
            }).join('');

            // Enable button if something is already selected
            document.getElementById('abtn_' + cid).disabled = state.selected.size === 0;
        }

        // ── Toggle lupon checkbox ──
        function toggleAdminLupon(cid, id, name, el) {
            const cb    = document.getElementById(`alc_${cid}_${id}`);
            const state = getState(cid);
            if (!cb || cb.disabled) return;

            if (cb.checked) {
                cb.checked = false;
                el.classList.remove('checked');
                state.selected.delete(String(id));
            } else {
                if (state.selected.size >= 3) {
                    showToast('Maximum 3 Lupon members per hearing.', true);
                    return;
                }
                cb.checked = true;
                el.classList.add('checked');
                state.selected.set(String(id), name);
            }
            updateSelCount(cid);
            document.getElementById('abtn_' + cid).disabled = state.selected.size === 0;
        }

        function updateSelCount(cid) {
            const count = getState(cid).selected.size;
            document.getElementById('acount_' + cid).textContent = `${count} / 3 selected`;
        }

        // ── Edit already-assigned (show form again) ──
        function editAssignment(cid) {
            const date = document.getElementById('adate_' + cid).value;
            const time = document.getElementById('atime_' + cid).value;
            getState(cid).selected = new Map();
            updateSelCount(cid);
            if (date && time) fetchAdminAvailability(cid, date, time, [], []);
        }

        // ── Save assignment ──
        function saveAssignment(cid) {
            const date  = document.getElementById('adate_' + cid).value;
            const time  = document.getElementById('atime_' + cid).value;
            const state = getState(cid);

            if (!date)            { showToast('Please select a hearing date.', true); return; }
            if (!time)            { showToast('Please select a hearing time.', true); return; }
            if (state.selected.size === 0) { showToast('Please assign at least 1 Lupon member.', true); return; }

            const btn = document.getElementById('abtn_' + cid);
            btn.disabled = true;
            btn.textContent = 'Saving…';

            const fd = new FormData();
            fd.append('complaint_id', cid);
            fd.append('date', date);
            fd.append('time', time);
            state.selected.forEach((name, id) => {
                fd.append('lupon_ids[]',   id);
                fd.append('lupon_names[]', name);
            });

            fetch('admin_assign_lupon.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showToast('Assignment saved successfully!');
                        setTimeout(() => location.reload(), 1200);
                    } else if (d.conflicts) {
                        showToast('Conflict: ' + d.conflicts.join(', ') + ' already booked.', true);
                        btn.disabled = false;
                        btn.textContent = 'Confirm Assignment';
                        fetchAdminAvailability(cid, date, time, [], []);
                    } else {
                        showToast(d.error || 'Error saving.', true);
                        btn.disabled = false;
                        btn.textContent = 'Confirm Assignment';
                    }
                })
                .catch(() => {
                    showToast('Network error.', true);
                    btn.disabled = false;
                    btn.textContent = 'Confirm Assignment';
                });
        }

        // ── Helpers ──
        function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
        function escJs(s)   { return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }

        // ── Scroll to and highlight a specific case (from notification link) ──
        (function scrollToHighlight() {
            const el = document.querySelector('.highlight-card');
            if (el) {
                setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'center' }), 200);
            }
        })();


        function showToast(msg, isError) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast show' + (isError ? ' error' : '');
            setTimeout(() => t.className = 'toast', 3200);
        }

        // ── Notification bell ──
        const bellBtn=document.getElementById('bellBtn'),dropdown=document.getElementById('notifDropdown'),overlay=document.getElementById('notifOverlay'),badge=document.getElementById('notifBadge'),nList=document.getElementById('nList'),nLabel=document.getElementById('nLabel'),markBtn=document.getElementById('markReadBtn');
        let bellOpen=false;
        function taAgo(d){const s=Math.floor((new Date()-new Date(d))/1000);if(s<60)return'just now';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';}
        function escN(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
        function renderBell({notifications,unread_count}){
            if(unread_count>0){badge.textContent=unread_count>99?'99+':unread_count;badge.classList.add('has-notif');}
            else badge.classList.remove('has-notif');
            nLabel.textContent=unread_count>0?`${unread_count} unread`:'All caught up!';
            nList.innerHTML=notifications.length?notifications.map(n=>`<a class="n-item ${n.is_read==0?'unread':''}" href="${n.type === 'id_verification' ? 'admin-review-id.php?resident_id=' + n.resident_id : (n.type === 'new_appointment' || n.type === 'unassigned_warning') ? 'admin-lupon-assignments.php?complaint_id=' + n.complaint_id : 'admin-view-complaint.php?id=' + n.complaint_id}">
<div class="n-dot"><img src="file.png"></div><div class="n-body"><strong>${escN(n.subject)}</strong><p>${escN(n.message)}</p><time>${taAgo(n.created_at)}</time></div></a>`).join(''):`<div class="n-empty"><img src="bell.png"><p>No notifications yet.</p></div>`;
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
