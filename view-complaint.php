<?php
require_once 'session_check_resident.php';
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = $conn->query("SELECT * FROM complaints WHERE id=$id AND resident_id=$session_resident_id");
if ($result->num_rows > 0) $row = $result->fetch_assoc();
else { echo "<p style='padding:40px;font-family:Poppins,sans-serif;'>Complaint not found or access denied.</p>"; exit; }

$evidence_files = !empty($row['evidence_pic'])
    ? array_filter(array_map('trim', explode(',', $row['evidence_pic'])),
        fn($f) => $f !== '' && $f !== 'placeholder.jpg')
    : [];

$notif_q      = $conn->query("SELECT * FROM resident_notifications WHERE complaint_id=$id AND is_read=0 ORDER BY created_at DESC LIMIT 1");
$latest_notif = $notif_q->num_rows > 0 ? $notif_q->fetch_assoc() : null;
$status       = $row['status'];
$sc           = strtolower(str_replace([' ','_'], '-', $status));

// Avatar initials from the resident's name
$name_parts = preg_split('/\s+/', trim($session_resident_name ?? 'Resident'));
$initials = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[count($name_parts) - 1] ?? '', 0, 1));
if (count($name_parts) < 2) {
    $initials = strtoupper(substr($name_parts[0], 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaint - Barangay San Roque</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-900: #1B4332;
            --green-700: #2D6A4F;
            --green-500: #40916c;
            --green-100: #e8f5e9;
            --border: #e0ede5;
            --muted: #52796f;
            --paper: #f9fbf9;
        }

        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        html, body { margin: 0; padding: 0; }
        body { background: var(--paper); display: flex; min-height: 100vh; }

        /* =============================================
           TOP BAR + SIDEBAR RAIL — matches resident-portal.php
           ============================================= */

        .sidebar {
            width: 76px; height: 100vh; background: #004d2c; position: fixed; top: 0; left: 0;
            display: flex; flex-direction: column; justify-content: space-between; align-items: center;
            padding: 20px 0; z-index: 9200; transform: translateX(0); transition: transform 0.3s ease;
        }
        .sidebar-top, .sidebar-bottom { display: flex; flex-direction: column; align-items: center; gap: 6px; width: 100%; }
        .nav-item {
            width: 46px; height: 46px; display: flex; justify-content: center; align-items: center;
            cursor: pointer; transition: background 0.2s, opacity 0.2s; opacity: 0.65;
            border-radius: 12px; text-decoration: none;
        }
        .nav-item.active, .nav-item:hover { opacity: 1; background-color: rgba(255,255,255,0.14); }
        .nav-item img { width: 22px; height: 22px; filter: brightness(0) invert(1); }
        .sidebar-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.14);
            border: 1.5px solid rgba(255,255,255,0.35); color: #fff; font-size: 0.72rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center; margin-bottom: 10px;
        }
        .logout-item { margin-bottom: 0; }

        .sidebar-close-btn {
            display: none; position: absolute; top: 14px; right: 14px; background: none; border: none;
            color: #fff; opacity: 0.75; width: 32px; height: 32px; align-items: center; justify-content: center;
            cursor: pointer; border-radius: 8px;
        }
        .sidebar-close-btn:hover { opacity: 1; background: rgba(255,255,255,0.14); }
        .sidebar-close-btn svg { width: 18px; height: 18px; }

        .app-topbar {
            position: fixed; top: 0; left: 76px; width: calc(100% - 76px); height: 64px;
            background: #fff; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; padding: 0 28px; z-index: 200;
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .hamburger-btn {
            display: none; background: none; border: none; color: var(--green-900);
            width: 34px; height: 34px; align-items: center; justify-content: center; cursor: pointer;
            border-radius: 8px; flex-shrink: 0;
        }
        .hamburger-btn:hover { background: var(--green-100); }
        .hamburger-btn img {
            width: 20px; height: 20px;
            filter: invert(17%) sepia(35%) saturate(1352%) hue-rotate(115deg) brightness(94%) contrast(92%);
        }
        .topbar-titles { display: flex; flex-direction: column; line-height: 1.25; }
        .topbar-eyebrow { font-size: 0.66rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: var(--muted); }
        .topbar-title { font-size: 1rem; font-weight: 600; color: var(--green-900); }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: var(--green-700); color: #fff;
            font-size: 0.75rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }

        .portal-content {
            margin-left: 76px; width: 100%; padding: 96px 50px 50px;
            display: flex; flex-direction: column; align-items: center; min-height: 100vh;
        }

        /* =============================================
           RESIDENT NOTIFICATION PANEL
           ============================================= */
        .res-notif-wrapper { position:relative;display:flex;justify-content:center;align-items:center;padding:15px;cursor:pointer;opacity:0.65;transition:opacity 0.2s;border-radius:12px; }
        .res-notif-wrapper:hover { opacity:1; }
        .res-notif-wrapper img { width:22px;filter:brightness(0) invert(1); }
        .res-notif-badge { position:absolute;top:8px;right:8px;background:#e53e3e;color:#fff;font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:none;align-items:center;justify-content:center;padding:0 3px;border:2px solid #004d2c;line-height:1; }
        .res-notif-badge.on { display:flex; }
        .res-notif-overlay { display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.25); }
        .res-notif-overlay.show { display:block; }
        .res-notif-panel { position:fixed;top:0;left:-420px;width:360px;max-width:90vw;height:100vh;background:#fff;border-radius:0 16px 16px 0;box-shadow:6px 0 30px rgba(0,0,0,0.14);z-index:9999;display:flex;flex-direction:column;transition:left 0.32s cubic-bezier(0.4,0,0.2,1);overflow:hidden; }
        .res-notif-panel.open { left:76px; }
        .res-panel-header { background:#1B4332;color:#fff;padding:20px 22px 16px;display:flex;justify-content:space-between;align-items:center;gap:12px; }
        .res-panel-header h3 { font-size:15px;font-weight:600;margin:0 0 2px; }
        .res-panel-header small { font-size:11px;opacity:0.75;display:block; }
        .res-panel-user { font-size:12px;opacity:0.85;margin-bottom:4px; }
        .res-mark-read { background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap; }
        .res-notif-list { overflow-y:auto;flex:1; }
        .res-notif-item { display:flex;gap:13px;padding:15px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit; }
        .res-notif-item.unread { background:#f0fdf4;border-left:3px solid #2D6A4F; }
        .rn-dot { width:38px;height:38px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
        .rn-dot img { width:17px;height:17px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg); }
        .rn-body { flex:1;min-width:0; }
        .rn-body strong { display:block;font-size:13px;color:#1a202c;margin-bottom:3px; }
        .rn-body p { font-size:12px;color:#64748b;line-height:1.45;margin:0 0 4px; }
        .rn-body time { font-size:11px;color:#94a3b8; }
        .res-empty { text-align:center;padding:50px 20px;color:#94a3b8; }
        .res-empty img { width:42px;opacity:0.28;display:block;margin:0 auto 12px; }

        /* =============================================
           FORM / DATA DISPLAY (shared with complaint-form.php)
           ============================================= */
        .top-nav { width: 100%; max-width: 800px; margin-bottom: 18px; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; color: #2D6A4F; font-size: 0.9rem; font-weight: 600; text-decoration: none; transition: gap 0.15s, color 0.15s; }
        .back-link:hover { gap: 9px; color: #1B4332; }
        .form-container { width: 100%; max-width: 800px; background: #fff; border-radius: 18px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); padding: 44px 48px; margin-bottom: 50px; }
        .form-title { color: #1B4332; font-size: 1.6rem; font-weight: 700; text-align: center; letter-spacing: 0.08em; margin-bottom: 14px; }
        .form-intro { color: #52796f; font-size: 0.88rem; line-height: 1.7; text-align: center; max-width: 620px; margin: 0 auto 32px; }
        .section-header { display: flex; align-items: center; gap: 12px; margin: 34px 0 18px; padding-bottom: 10px; border-bottom: 1px solid #e0ede5; }
        .section-header:first-of-type { margin-top: 0; }
        .section-icon { width: 34px; height: 34px; padding: 7px; background: #e8f5e9; border-radius: 50%; filter: contrast(0.5); flex-shrink: 0; }
        .section-header h3 { color: #1B4332; font-size: 1rem; font-weight: 600; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 18px; min-width: 0; }
        .form-group label { font-size: 0.82rem; font-weight: 600; color: #1B4332; margin-bottom: 7px; }
        .form-actions { display: flex; gap: 14px; margin-top: 32px; }
        .submit-btn {
            flex: 2; background: #1B4332; color: #fff; border: none; padding: 15px; border-radius: 10px;
            font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: background 0.2s, transform 0.15s;
        }
        .submit-btn:hover { background: #2D6A4F; transform: translateY(-1px); }

        .status-badge{padding:5px 16px;border-radius:99px;font-size:.8rem;font-weight:600;display:inline-block;margin-bottom:10px;}
        .status-badge.pending          {background:#fef3c7;color:#92400e;}
        .status-badge.approved         {background:#d1fae5;color:#065f46;}
        .status-badge.in-process       {background:#dbeafe;color:#1e40af;}
        .status-badge.rescheduled      {background:#f5f3ff;color:#5b21b6;}
        .status-badge.cannot-be-handled{background:#ffedd5;color:#9a3412;}
        .status-badge.completed        {background:#e0e7ff;color:#3730a3;}

        .data-display{background:#fff;padding:12px 15px;border-radius:6px;border:1px solid #e0e0e0;color:#333;min-height:48px;display:flex;align-items:center;font-size:.95rem;}
        .evidence-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(155px,1fr));gap:12px;}
        .ev-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;text-align:center;}
        .ev-card img.thumb{width:100%;height:140px;object-fit:cover;display:block;}
        .ev-card .ev-icon-wrap{height:140px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;}
        .ev-card .ev-icon-wrap img{width:34px;opacity:.4;}
        .ev-card .ev-label{font-size:11px;color:#64748b;padding:7px 8px;border-top:1px solid #e8edf2;word-break:break-all;background:#fff;}
        .no-evidence{color:#94a3b8;font-style:italic;padding:25px;text-align:center;background:#f8fafc;border-radius:8px;border:1px dashed #dde3e8;}
        .admin-comment-display{background:#fff8e7;border:1px solid #fcd34d;border-radius:8px;padding:14px 16px;margin-bottom:18px;}
        .admin-comment-display .aclabel{font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;margin-bottom:5px;display:block;}
        .admin-comment-display p{font-size:14px;color:#78350f;margin:0;line-height:1.6;}

        /* ---- Transition: drills in from the dashboard, slides back out ---- */
        .portal-content {
            animation: contentSlideIn .4s cubic-bezier(.16,1,.3,1) both;
        }
        @keyframes contentSlideIn {
            from { opacity: 0; transform: translateX(24px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .portal-content.page-exit {
            animation: contentSlideOut .22s ease forwards;
        }
        @keyframes contentSlideOut {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(24px); }
        }
        .back-link { display: inline-block; transition: transform .15s ease, opacity .15s ease; }
        .back-link:active { transform: translateX(-3px); }

        /* ===================================
           RESPONSIVE — sidebar becomes a drawer on mobile
           =================================== */
        @media screen and (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: 6px 0 24px rgba(0,0,0,0.18); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .hamburger-btn { display: flex; }
            .app-topbar { left: 0; width: 100%; padding: 0 16px; }
            .portal-content { margin-left: 0; width: 100%; padding: 84px 20px 40px; }
            .res-notif-panel.open { left: 0; }
        }
        @media screen and (max-width: 768px) {
            .topbar-eyebrow { display: none; }
            .topbar-title { font-size: 0.95rem; }
            .portal-content { padding: 78px 16px 32px; }
            .form-container { padding: 26px 20px; border-radius: 14px; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
            .res-notif-panel { width: 100%; max-width: 100vw; }
        }
        @media screen and (max-width: 480px) {
            .portal-content { padding: 74px 12px 28px; }
            .form-container { padding: 20px 16px; }
        }

        /* Modals */
        .modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;justify-content:center;align-items:center;}
        .modal-bg.open{display:flex;}
        .modal-box{background:#fff;border-radius:16px;padding:30px;width:92%;max-width:500px;position:relative;animation:mIn .24s ease;max-height:92vh;overflow-y:auto;}
        @keyframes mIn{from{transform:translateY(14px);opacity:0}to{transform:translateY(0);opacity:1}}
        .modal-close-btn{position:absolute;top:13px;right:15px;background:none;border:none;font-size:22px;cursor:pointer;color:#94a3b8;line-height:1;}
        .case-tbl{width:100%;border-collapse:collapse;margin-bottom:16px;}
        .case-tbl td{padding:7px 4px;font-size:13px;border-bottom:1px solid #f1f5f9;}
        .case-tbl td:first-child{color:#94a3b8;font-size:11px;font-weight:600;text-transform:uppercase;width:38%;}
        .approved-banner{background:#ecfdf5;border:1px solid #6ee7b7;border-radius:10px;padding:14px 16px;margin-bottom:16px;}
        .approved-banner p{color:#065f46;font-size:14px;margin:0;line-height:1.6;}
        .warning-banner{background:#fff7ed;border:1px solid #fdba74;border-radius:10px;padding:14px 16px;margin-bottom:16px;}
        .warning-banner p{color:#9a3412;font-size:14px;margin:0;line-height:1.6;}
        .reject-banner{background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px 16px;margin-bottom:16px;}
        .reject-banner p{color:#991b1b;font-size:14px;margin:0;line-height:1.6;}
        .followup-banner{background:#eff6ff;border:1px solid #93c5fd;border-radius:10px;padding:14px 16px;margin-bottom:16px;}
        .followup-banner p{color:#1e40af;font-size:14px;margin:0;line-height:1.6;}
        .next-steps-box{background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;padding:14px 16px;margin:14px 0;}
        .next-steps-box h4{font-size:13px;font-weight:700;color:#1e40af;margin:0 0 5px;}
        .next-steps-box p{font-size:13px;color:#1e40af;margin:0;line-height:1.6;}
        .reject-comment{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin:12px 0;font-size:14px;color:#374151;line-height:1.6;}
        .reject-comment .rl{font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:6px;display:block;}
        .modal-two-btns{display:flex;gap:12px;margin-top:18px;}
        .btn-later{flex:1;padding:12px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:14px;font-weight:500;font-family:inherit;}
        .btn-schedule-now{flex:1.5;padding:12px;background:#2563eb;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:700;font-family:inherit;}
        .btn-ok,.btn-understand{width:100%;padding:13px;background:#1B4332;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;margin-top:4px;font-family:inherit;}
        .btn-understand-blue{width:100%;padding:13px;background:#2563eb;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;margin-top:4px;font-family:inherit;}
        .appt-done-box{background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:16px;margin:14px 0;text-align:center;}
        .appt-done-box h3{color:#065f46;font-size:16px;margin:0 0 6px;}
        .appt-done-box p{color:#166534;font-size:13px;margin:0;}

        /* Schedule modal */
        .sched-section{margin-bottom:18px;}
        .sched-section h4{font-size:13px;color:#374151;font-weight:600;margin:0 0 9px;display:flex;align-items:center;gap:6px;}
        .sched-input{width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:14px;font-family:inherit;box-sizing:border-box;}
        .sched-input:focus{outline:none;border-color:#2563eb;}

        /* Time slot grid — 4 slots only, 2 per row */
        .time-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
        .time-slot{
            padding:11px 8px;border:2px solid #e2e8f0;border-radius:8px;
            text-align:center;font-size:13px;font-weight:600;
            transition:all .15s;color:#374151;user-select:none;
        }
        .time-slot.available{cursor:pointer;}
        .time-slot.available:hover{border-color:#93c5fd;background:#eff6ff;}
        .time-slot.available.picked{border-color:#2563eb;background:#2563eb;color:#fff;}
        /* Fully blocked slot — greyed out, strikethrough */
        .time-slot.blocked{
            cursor:not-allowed;opacity:.45;background:#f8fafc;
            text-decoration:line-through;color:#94a3b8;
        }
        .slot-taken-label{font-size:10px;font-weight:700;color:#dc2626;display:block;margin-top:2px;}

        /* Availability note */
        .avail-hint{font-size:12px;color:#64748b;margin-bottom:8px;min-height:16px;}
        .avail-hint.warn{color:#b45309;}
        .avail-hint.loading{color:#94a3b8;font-style:italic;}

        /* Lupon list */
        .lupon-count{font-size:12px;color:#64748b;margin-bottom:6px;}
        .lupon-list{display:flex;flex-direction:column;gap:8px;max-height:200px;overflow-y:auto;}
        .lupon-item{display:flex;align-items:center;gap:12px;padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;transition:all .15s;font-size:13px;position:relative;}
        .lupon-item.available{cursor:pointer;}
        .lupon-item.available:hover{border-color:#a7c4b5;background:#f7fdfb;}
        .lupon-item.available.picked{border-color:#2D6A4F;background:#f0fdf4;}
        .lupon-item.unavailable{cursor:not-allowed;opacity:.5;background:#f8fafc;}
        .lupon-item input[type=checkbox]{accent-color:#2D6A4F;width:15px;height:15px;flex-shrink:0;}
        .lupon-name{font-weight:600;color:#1a202c;}
        .lupon-pos{font-size:11px;color:#64748b;}
        .unavail-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:#fee2e2;color:#991b1b;margin-left:auto;white-space:nowrap;flex-shrink:0;}
        .avail-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:#d1fae5;color:#065f46;margin-left:auto;white-space:nowrap;flex-shrink:0;}

        .btn-confirm-appt{width:100%;padding:13px;background:#2D6A4F;color:#fff;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;transition:background .2s;margin-top:4px;font-family:inherit;}
        .btn-confirm-appt:hover{background:#1B4332;}
        .btn-confirm-appt:disabled{opacity:.5;cursor:not-allowed;}
        .btn-back-appt{width:100%;padding:11px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:13px;margin-top:8px;font-family:inherit;}
        .conflict-alert{background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-size:13px;color:#991b1b;display:none;}
        .conflict-alert.show{display:block;}

        /* Date hint */
        .date-note{font-size:12px;color:#64748b;margin-top:5px;}
    </style>
</head>
<body>

<!-- Top bar -->
<header class="app-topbar">
    <div class="topbar-left">
        <button class="hamburger-btn" id="hamburgerBtn" aria-label="Open menu">
            <img src="menu.png" alt="Menu">
        </button>
        <div class="topbar-titles">
            <span class="topbar-eyebrow">Resident Portal</span>
            <span class="topbar-title">View Complaint</span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-avatar" title="<?php echo htmlspecialchars($session_resident_name ?? 'Resident'); ?>">
            <?php echo htmlspecialchars($initials); ?>
        </div>
    </div>
</header>

<!-- Sidebar -->
<nav class="sidebar" id="sidebarNav">
    <button class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Close menu">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 6L18 18M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
    </button>
    <div class="sidebar-top">
        <a href="resident-portal.php" class="nav-item" title="Dashboard">
            <img src="dashboard.png" alt="Dashboard">
        </a>
        <a href="complaint-form.php" class="nav-item" title="File Complaint">
            <img src="file.png" alt="File Complaint">
        </a>
        <div class="res-notif-wrapper nav-item" id="resBellBtn" title="Notifications">
            <img src="bell.png" alt="Notifications" id="resBellIcon">
            <span class="res-notif-badge" id="resBadge"></span>
        </div>
    </div>
    <div class="sidebar-bottom">
        <div class="sidebar-avatar" title="<?php echo htmlspecialchars($session_resident_name ?? 'Resident'); ?>">
            <?php echo htmlspecialchars($initials); ?>
        </div>
        <a href="logout.php" class="nav-item logout-item" title="Logout">
            <img src="people.png" alt="Logout">
        </a>
    </div>
</nav>

<!-- Backdrop shared by the mobile drawer and the notification panel -->
<div class="res-notif-overlay" id="resOverlay"></div>

<div class="res-notif-panel" id="resPanel">
    <div class="res-panel-header">
        <div>
            <p class="res-panel-user">👤 <?php echo htmlspecialchars($session_resident_name ?? 'Resident'); ?></p>
            <h3>My Notifications</h3>
            <small id="resUnreadLabel">Loading…</small>
        </div>
        <button class="res-mark-read" id="resMarkRead">Mark all read</button>
    </div>
    <div class="res-notif-list" id="resNotifList">
        <div class="res-empty"><img src="bell.png"><p>No notifications yet.</p></div>
    </div>
</div>


<!-- APPROVED MODAL -->
<div class="modal-bg" id="approvedModal">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeAll()">×</button>
        <h2 style="color:#059669;margin:0 0 4px;">✅ Case Approved</h2>
        <p style="color:#64748b;font-size:13px;margin:0 0 16px;">Your complaint has been reviewed and approved.</p>
        <div class="approved-banner"><p>Your case has been reviewed and approved. You may now schedule an appointment with the Lupon.</p></div>
        <strong style="display:block;margin-bottom:8px;font-size:13px;color:#374151;">Case Details</strong>
        <table class="case-tbl">
            <tr><td>Case Number</td><td>BRGY-2026-0<?php echo $row['id']; ?></td></tr>
            <tr><td>Case Type</td><td><?php echo htmlspecialchars($row['subject']); ?></td></tr>
            <tr><td>Filed Date</td><td><?php echo date("M j, Y", strtotime($row['date_filed'])); ?></td></tr>
            <tr><td>Status</td><td style="color:#059669;font-weight:700;">Approved</td></tr>
            <tr><td>Complainant</td><td><?php echo htmlspecialchars($row['complainant_name']); ?></td></tr>
            <tr><td>Respondent</td><td><?php echo htmlspecialchars($row['respondent_name']); ?></td></tr>
        </table>
        <?php if (!empty($row['appointment_date'])): ?>
        <div class="appt-done-box">
            <h3>📅 Appointment Scheduled</h3>
            <p><?php echo date("F j, Y", strtotime($row['appointment_date'])); ?> at <?php echo htmlspecialchars($row['appointment_time']); ?></p>
            <p style="margin-top:4px;">Lupon: <?php echo htmlspecialchars($row['lupon_preference']); ?></p>
        </div>
        <button class="btn-ok" onclick="closeAll()">Done</button>
        <?php else: ?>
        <div class="modal-two-btns">
            <button class="btn-later" onclick="closeAll()">Later</button>
            <button class="btn-schedule-now" onclick="openSchedule()">Schedule an Appointment</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- SCHEDULE MODAL -->
<div class="modal-bg" id="scheduleModal">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeAll()">×</button>
        <h2 style="margin:0 0 4px;">📅 Schedule Appointment</h2>
        <p style="color:#64748b;font-size:13px;margin:0 0 18px;">Select a date, then choose an available time slot and up to 3 Lupon members.</p>

        <div class="conflict-alert" id="conflictAlert"></div>

        <!-- Step 1: Date -->
        <div class="sched-section">
            <h4>📆 Select Date</h4>
            <input type="date" id="apptDate" class="sched-input"
                   onchange="onDateChange()">
            <p class="date-note" id="dateNote">Loading available dates…</p>
        </div>

        <!-- Step 2: Time slots (loaded dynamically) -->
        <div class="sched-section" id="slotSection" style="display:none;">
            <h4>🕐 Select Time <span style="font-weight:400;color:#94a3b8;font-size:12px;">(9 AM – 12 PM only)</span></h4>
            <div class="time-grid" id="timeGrid">
                <!-- Populated by JS -->
            </div>
        </div>

        <!-- Step 3: Lupon (loaded after slot picked) -->
        <div class="sched-section" id="luponSection" style="display:none;">
            <h4>👥 Preferred Lupon <span style="font-weight:400;color:#94a3b8;font-size:12px;">(max 3)</span></h4>
            <p class="avail-hint" id="availHint"></p>
            <p class="lupon-count" id="luponCount">0 / 3 selected</p>
            <div class="lupon-list" id="luponList"></div>
        </div>

        <button class="btn-confirm-appt" id="confirmApptBtn" onclick="confirmAppt()" disabled>Confirm Appointment</button>
        <button class="btn-back-appt" onclick="openApproved()">← Back</button>
    </div>
</div>

<!-- CANNOT HANDLE MODAL -->
<div class="modal-bg" id="cannotModal">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeAll()">×</button>
        <h2 style="color:#ea580c;margin:0 0 4px;">⚠️ Case Cannot Be Handled</h2>
        <p style="color:#64748b;font-size:13px;margin:0 0 14px;">Your complaint has been reviewed.</p>
        <div class="warning-banner"><p>Your complaint has been reviewed. Unfortunately, this is a major case that cannot be handled at the barangay level.</p></div>
        <table class="case-tbl">
            <tr><td>Case Number</td><td>BRGY-2026-0<?php echo $row['id']; ?></td></tr>
            <tr><td>Complainant</td><td><?php echo htmlspecialchars($row['complainant_name']); ?></td></tr>
            <tr><td>Case Type</td><td><?php echo htmlspecialchars($row['subject']); ?></td></tr>
            <tr><td>Filed Date</td><td><?php echo date("M j, Y", strtotime($row['date_filed'])); ?></td></tr>
        </table>
        <?php if (!empty($row['admin_comment'])): ?>
        <div class="reject-comment"><span class="rl">Admin Note</span><?php echo nl2br(htmlspecialchars($row['admin_comment'])); ?></div>
        <?php endif; ?>
        <div class="next-steps-box">
            <h4>Next Steps</h4>
            <p>Please visit the barangay office to request a <strong>Certificate to File Action</strong>.</p>
        </div>
        <button class="btn-understand-blue" onclick="closeAll()">I Understand</button>
    </div>
</div>

<!-- RESCHEDULED MODAL -->
<div class="modal-bg" id="rescheduledModal">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeAll()">×</button>
        <h2 style="color:#5b21b6;margin:0 0 4px;">📅 Hearing Rescheduled</h2>
        <p style="color:#64748b;font-size:13px;margin:0 0 14px;">Your hearing has been rescheduled by the admin.</p>
        <div style="background:#f5f3ff;border:1px solid #c4b5fd;border-radius:10px;padding:14px 16px;margin-bottom:16px;">
            <p style="color:#5b21b6;font-size:14px;margin:0;line-height:1.6;">
                The previous mediation was unsuccessful. The admin has set a new hearing date for your case.
                Please make sure you are available on the rescheduled date.
            </p>
        </div>
        <table class="case-tbl">
            <tr><td>Case Number</td><td>BRGY-2026-0<?php echo $row['id']; ?></td></tr>
            <tr><td>Case Type</td><td><?php echo htmlspecialchars($row['subject']); ?></td></tr>
            <?php if (!empty($row['appointment_date'])): ?>
            <tr><td>New Date</td><td style="color:#5b21b6;font-weight:700;"><?php echo date("F j, Y", strtotime($row['appointment_date'])); ?></td></tr>
            <tr><td>New Time</td><td style="color:#5b21b6;font-weight:700;"><?php echo htmlspecialchars($row['appointment_time']); ?></td></tr>
            <tr><td>Lupon Panel</td><td><?php echo htmlspecialchars($row['lupon_preference'] ?: 'To be assigned'); ?></td></tr>
            <?php endif; ?>
            <tr><td>Complainant</td><td><?php echo htmlspecialchars($row['complainant_name']); ?></td></tr>
            <tr><td>Respondent</td><td><?php echo htmlspecialchars($row['respondent_name']); ?></td></tr>
        </table>
        <?php if (!empty($row['admin_comment'])): ?>
        <div class="reject-comment"><span class="rl">Admin Note</span><?php echo nl2br(htmlspecialchars($row['admin_comment'])); ?></div>
        <?php endif; ?>
        <button class="btn-ok" onclick="closeAll()">I Understand</button>
    </div>
</div>

<!-- IN PROCESS MODAL -->
<div class="modal-bg" id="inProcessModal">
    <div class="modal-box">
        <button class="modal-close-btn" onclick="closeAll()">×</button>
        <h2 style="color:#2563eb;margin:0 0 4px;">🔄 Follow-Up Required</h2>
        <p style="color:#64748b;font-size:13px;margin:0 0 14px;">Additional information is needed.</p>
        <div class="followup-banner"><p>Your complaint requires additional information or documents before it can proceed.</p></div>
        <table class="case-tbl">
            <tr><td>Case Number</td><td>BRGY-2026-0<?php echo $row['id']; ?></td></tr>
            <tr><td>Case Type</td><td><?php echo htmlspecialchars($row['subject']); ?></td></tr>
        </table>
        <?php if (!empty($row['admin_comment'])): ?>
        <div class="reject-comment"><span class="rl">Required Action</span><?php echo nl2br(htmlspecialchars($row['admin_comment'])); ?></div>
        <?php endif; ?>
        <button class="btn-ok" onclick="closeAll()">Understood</button>
    </div>
</div>

<main class="portal-content">
    <div class="top-nav"><a href="resident-portal.php" class="back-link">← Back to Dashboard</a></div>
    <section class="form-container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
            <span class="status-badge <?php echo $sc; ?>"><?php echo htmlspecialchars($status); ?></span>
            <?php if ($status === 'Approved' && empty($row['appointment_date'])): ?>
            <button onclick="openSchedule()" style="background:#2563eb;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer;font-size:13px;font-family:inherit;">
                📅 Schedule Appointment
            </button>
            <?php endif; ?>
        </div>

        <h1 class="form-title">Complaint Summary</h1>
        <p class="form-intro">This is a digital copy of your KP Form No. 9 submission.</p>

        <div class="form-row">
            <div class="form-group"><label>Subject</label><div class="data-display"><?php echo htmlspecialchars($row['subject']); ?></div></div>
            <div class="form-group"><label>Date Filed</label><div class="data-display"><?php echo htmlspecialchars($row['date_filed']); ?></div></div>
        </div>
        <div class="section-header"><img src="people.png" class="section-icon"><h3>Complainant Details</h3></div>
        <div class="form-group"><label>Full Name</label><div class="data-display"><?php echo htmlspecialchars($row['complainant_name']); ?></div></div>
        <div class="form-row">
            <div class="form-group"><label>Address</label><div class="data-display"><?php echo htmlspecialchars($row['complainant_address']); ?></div></div>
            <div class="form-group"><label>Contact</label><div class="data-display"><?php echo htmlspecialchars($row['complainant_contact']); ?></div></div>
        </div>
        <div class="section-header"><img src="people.png" class="section-icon"><h3>Respondent Details</h3></div>
        <div class="form-group"><label>Name</label><div class="data-display"><?php echo htmlspecialchars($row['respondent_name']); ?></div></div>
        <div class="form-group"><label>Address</label><div class="data-display"><?php echo htmlspecialchars($row['respondent_address']); ?></div></div>
        <div class="section-header"><img src="file.png" class="section-icon"><h3>Statement</h3></div>
        <div class="form-group"><div class="data-display" style="align-items:flex-start;padding-top:10px;min-height:100px;"><?php echo nl2br(htmlspecialchars($row['narrative'])); ?></div></div>

        <?php if (!empty($row['admin_comment'])): ?>
        <div class="admin-comment-display"><span class="aclabel">Admin Comment</span><p><?php echo nl2br(htmlspecialchars($row['admin_comment'])); ?></p></div>
        <?php endif; ?>

        <div class="section-header"><img src="upload.png" class="section-icon"><h3>Uploaded Evidence</h3></div>
        <div class="form-group">
            <?php if (!empty($evidence_files)): ?>
            <div class="evidence-grid">
                <?php foreach ($evidence_files as $file):
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $is_img = in_array($ext,['jpg','jpeg','png','gif','webp','bmp']);
                ?>
                <div class="ev-card">
                    <?php if ($is_img): ?>
                        <a href="uploads/<?php echo htmlspecialchars($file); ?>" target="_blank"><img class="thumb" src="uploads/<?php echo htmlspecialchars($file); ?>"></a>
                    <?php else: ?>
                        <a href="uploads/<?php echo htmlspecialchars($file); ?>" target="_blank" style="text-decoration:none;"><div class="ev-icon-wrap"><img src="file.png"><span style="font-size:11px;color:#64748b;font-weight:700;"><?php echo strtoupper($ext); ?></span></div></a>
                    <?php endif; ?>
                    <div class="ev-label"><?php echo htmlspecialchars($file); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?><div class="no-evidence">No evidence was uploaded.</div><?php endif; ?>
        </div>

        <?php if (!empty($row['appointment_date'])): ?>
        <div class="section-header"><h3>📅 Scheduled Appointment</h3></div>
        <div class="form-group">
            <div class="data-display" style="gap:20px;flex-wrap:wrap;">
                <span><strong><?php echo date("F j, Y", strtotime($row['appointment_date'])); ?></strong> at <?php echo htmlspecialchars($row['appointment_time']); ?></span>
                <span>Lupon: <?php echo htmlspecialchars($row['lupon_preference']); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="form-actions" style="margin-top:36px;">
            <button class="submit-btn" onclick="goBackWithTransition('resident-portal.php')">Done</button>
        </div>
    </section>
</main>

<script>
    const COMPLAINT_ID = <?php echo $id; ?>;
    const TIME_SLOTS   = ['9:00 AM', '10:00 AM', '11:00 AM', '12:00 PM'];

    let pickedTime    = '';
    let pickedLupon   = new Map();
    let calendarData  = null;   // loaded from get_calendar.php

    // ── Load calendar settings (booking window + holidays) ──
    function loadCalendar() {
        return fetch('get_calendar.php?action=all')
            .then(r => r.json())
            .then(d => {
                calendarData = d;
                const inp = document.getElementById('apptDate');
                inp.min = d.min_date;
                inp.max = d.max_date;
                const note = document.getElementById('dateNote');
                note.textContent = `You can schedule between ${fmtDate(d.min_date)} and ${fmtDate(d.max_date)} (${d.booking_window_days}-day window). Holidays are blocked.`;
            })
            .catch(() => {
                // Fallback: use +1 day with no max
                const inp = document.getElementById('apptDate');
                inp.min = addDays(new Date(), 1);
                document.getElementById('dateNote').textContent = 'Time slots will appear after selecting a date.';
            });
    }

    function addDays(date, n) {
        const d = new Date(date); d.setDate(d.getDate() + n);
        return d.toISOString().split('T')[0];
    }

    function fmtDate(str) {
        const d = new Date(str + 'T00:00:00');
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    // ── Modal helpers ──
    function closeAll() {
        document.querySelectorAll('.modal-bg').forEach(m => m.classList.remove('open'));
        fetch('get_resident_notifications.php?action=mark_one_read&complaint_id=' + COMPLAINT_ID).catch(()=>{});
    }
    function openApproved() { closeAll(); document.getElementById('approvedModal').classList.add('open'); }
    function openSchedule() {
        closeAll();
        // Reset state
        pickedTime  = '';
        pickedLupon = new Map();
        document.getElementById('apptDate').value             = '';
        document.getElementById('slotSection').style.display  = 'none';
        document.getElementById('luponSection').style.display = 'none';
        document.getElementById('confirmApptBtn').disabled    = true;
        document.getElementById('conflictAlert').className    = 'conflict-alert';
        document.getElementById('scheduleModal').classList.add('open');
        // Load booking window + holidays from admin settings
        loadCalendar();
    }

    // Auto-open modal based on status
    (function() {
        const status   = <?php echo json_encode($status); ?>;
        const hasNotif = <?php echo $latest_notif ? 'true' : 'false'; ?>;
        if (status === 'Approved') { openApproved(); return; }
        if (!hasNotif) return;
        switch (status) {
            case 'Cannot Be Handled': document.getElementById('cannotModal').classList.add('open');      break;
            case 'Rescheduled':       document.getElementById('rescheduledModal').classList.add('open'); break;
            case 'In Process':        document.getElementById('inProcessModal').classList.add('open');   break;
        }
    })();

    document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if (e.target===m) closeAll(); }));

    // ── Step 1: Date selected → validate then fetch available time slots ──
    function onDateChange() {
        const date = document.getElementById('apptDate').value;
        if (!date) return;

        // Check if this date is a blocked holiday
        if (calendarData && calendarData.holiday_dates && calendarData.holiday_dates.includes(date)) {
            document.getElementById('apptDate').value = '';
            const holiday = calendarData.holidays.find(h => h.holiday_date === date);
            alert(`This date is blocked: ${holiday ? holiday.label : 'Holiday'}. Please choose another date.`);
            return;
        }

        // Reset downstream
        pickedTime  = '';
        pickedLupon = new Map();
        document.getElementById('luponSection').style.display = 'none';
        document.getElementById('confirmApptBtn').disabled    = true;
        document.getElementById('luponCount').textContent     = '0 / 3 selected';

        const grid = document.getElementById('timeGrid');
        grid.innerHTML = '<p style="color:#94a3b8;font-size:13px;grid-column:1/-1;">Checking availability…</p>';
        document.getElementById('slotSection').style.display = 'block';

        fetch(`get_resident_notifications.php?action=get_timeslots&date=${encodeURIComponent(date)}&complaint_id=${COMPLAINT_ID}`)
            .then(r => r.json())
            .then(d => renderSlots(d.slots || []))
            .catch(() => {
                grid.innerHTML = '<p style="color:#e53e3e;font-size:13px;grid-column:1/-1;">Could not check slots. Please try again.</p>';
            });
    }

    // ── Render time slot buttons ──
    function renderSlots(slots) {
        const grid = document.getElementById('timeGrid');
        if (!slots.length) {
            grid.innerHTML = '<p style="color:#94a3b8;font-size:13px;grid-column:1/-1;">No slots available.</p>';
            return;
        }
        grid.innerHTML = slots.map(s => {
            if (s.available) {
                return `<div class="time-slot available" onclick="pickSlot(this, '${s.time}')">${s.time}</div>`;
            } else {
                return `<div class="time-slot blocked" title="This slot is already taken">
                            ${s.time}
                            <span class="slot-taken-label">Taken</span>
                        </div>`;
            }
        }).join('');
    }

    // ── Step 2: Slot picked → fetch lupon availability ──
    function pickSlot(el, val) {
        // Only available slots are clickable
        document.querySelectorAll('.time-slot.available').forEach(t => t.classList.remove('picked'));
        el.classList.add('picked');
        pickedTime  = val;
        pickedLupon = new Map();
        document.getElementById('luponCount').textContent     = '0 / 3 selected';
        document.getElementById('confirmApptBtn').disabled    = true;

        const hint = document.getElementById('availHint');
        hint.textContent = 'Checking Lupon availability…';
        hint.className   = 'avail-hint loading';
        document.getElementById('luponList').innerHTML = '<p style="color:#94a3b8;font-size:13px;">Loading…</p>';
        document.getElementById('luponSection').style.display = 'block';

        const date = document.getElementById('apptDate').value;
        fetch(`get_resident_notifications.php?action=get_lupon_availability&date=${encodeURIComponent(date)}&time=${encodeURIComponent(val)}&complaint_id=${COMPLAINT_ID}`)
            .then(r => r.json())
            .then(d => renderLupon(d.members || []))
            .catch(() => {
                document.getElementById('luponList').innerHTML = '<p style="color:#e53e3e;font-size:13px;">Could not load members.</p>';
            });
    }

    // ── Render lupon list ──
    function renderLupon(members) {
        const hint  = document.getElementById('availHint');
        const list  = document.getElementById('luponList');
        const avail = members.filter(m => m.is_available).length;

        hint.textContent = `${avail} of ${members.length} Lupon members available at this slot.`;
        hint.className   = avail === 0 ? 'avail-hint warn' : 'avail-hint';

        list.innerHTML = members.map(m => {
            const a = m.is_available == 1;
            return `
            <div class="lupon-item ${a ? 'available' : 'unavailable'}" id="lw_${m.id}"
                 onclick="${a ? `toggleLupon(${m.id},'${escJs(m.name)}',this)` : ''}">
                <input type="checkbox" id="lc_${m.id}" ${a ? '' : 'disabled'} onclick="event.stopPropagation()">
                <div style="flex:1;min-width:0;">
                    <div class="lupon-name">${escHtml(m.name)}</div>
                    <div class="lupon-pos">${escHtml(m.position)}</div>
                </div>
                ${a ? `<span class="avail-badge">Available</span>` : `<span class="unavail-badge">Booked</span>`}
            </div>`;
        }).join('');
    }

    function toggleLupon(id, name, el) {
        const cb = document.getElementById('lc_' + id);
        if (!cb || cb.disabled) return;
        if (cb.checked) {
            cb.checked = false; el.classList.remove('picked'); pickedLupon.delete(id);
        } else {
            if (pickedLupon.size >= 3) { alert('Maximum 3 Lupon members.'); return; }
            cb.checked = true; el.classList.add('picked'); pickedLupon.set(id, name);
        }
        document.getElementById('luponCount').textContent = pickedLupon.size + ' / 3 selected';
        document.getElementById('confirmApptBtn').disabled = pickedLupon.size === 0;
    }

    // ── Confirm ──
    function confirmAppt() {
        const date = document.getElementById('apptDate').value;
        if (!date)            { alert('Please select a date.'); return; }
        if (!pickedTime)      { alert('Please select a time slot.'); return; }
        if (pickedLupon.size === 0) { alert('Please select at least 1 Lupon member.'); return; }

        const btn = document.getElementById('confirmApptBtn');
        btn.disabled = true; btn.textContent = 'Saving…';

        const fd = new FormData();
        fd.append('complaint_id',     COMPLAINT_ID);
        fd.append('appointment_date', date);
        fd.append('appointment_time', pickedTime);
        pickedLupon.forEach((name, id) => { fd.append('lupon_ids[]', id); fd.append('lupon_names[]', name); });

        fetch('save_appointment.php', { method:'POST', body:fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { closeAll(); location.reload(); }
                else if (d.slot_taken) {
                    // Slot just got taken — refresh slots
                    document.getElementById('conflictAlert').textContent = d.error;
                    document.getElementById('conflictAlert').className = 'conflict-alert show';
                    btn.disabled = false; btn.textContent = 'Confirm Appointment';
                    onDateChange(); // re-fetch slots
                } else {
                    alert(d.error || 'Could not save. Please try again.');
                    btn.disabled = false; btn.textContent = 'Confirm Appointment';
                }
            })
            .catch(() => { alert('Network error.'); btn.disabled=false; btn.textContent='Confirm Appointment'; });
    }

    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function escJs(s)   { return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
</script>

<script>
    // ---- Smooth transition back to the dashboard ----
    function goBackWithTransition(href) {
        const el = document.querySelector('.portal-content') || document.body;
        el.classList.add('page-exit');
        setTimeout(function() { window.location.href = href; }, 200);
    }
    document.addEventListener('click', function(e) {
        const link = e.target.closest('.back-link');
        if (!link) return;
        if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        goBackWithTransition(link.getAttribute('href'));
    });
    // Reset in case the page is restored from bfcache (browser back button)
    window.addEventListener('pageshow', function() {
        const el = document.querySelector('.portal-content');
        if (el) el.classList.remove('page-exit');
    });
</script>

<script>
// Sidebar drawer + notifications
(function() {
    const bellBtn   = document.getElementById('resBellBtn');
    const panel     = document.getElementById('resPanel');
    const overlay   = document.getElementById('resOverlay');
    const badge     = document.getElementById('resBadge');
    const list      = document.getElementById('resNotifList');
    const label     = document.getElementById('resUnreadLabel');
    const markBtn   = document.getElementById('resMarkRead');
    const sidebar   = document.getElementById('sidebarNav');
    const hamburger = document.getElementById('hamburgerBtn');
    const closeBtn  = document.getElementById('sidebarCloseBtn');
    let panelOpen = false;
    let drawerOpen = false;

    function timeAgo(d) {
        const s = Math.floor((new Date() - new Date(d)) / 1000);
        if (s < 60)    return 'just now';
        if (s < 3600)  return Math.floor(s/60)   + 'm ago';
        if (s < 86400) return Math.floor(s/3600)  + 'h ago';
        return Math.floor(s/86400) + 'd ago';
    }
    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
    function typeLabel(t) {
        switch(t) {
            case 'approved':      return '✅ Case Approved';
            case 'rejected':      return '❌ Case Rejected';
            case 'cannot_handle': return '⚠️ Cannot Be Handled';
            case 'followup':      return '🔄 Follow-Up Required';
            default:              return '🔔 Status Update';
        }
    }

    function render(data) {
        const { notifications, unread_count } = data;
        if (unread_count > 0) {
            badge.textContent = unread_count > 99 ? '99+' : unread_count;
            badge.classList.add('on');
        } else {
            badge.classList.remove('on');
        }
        label.textContent = unread_count > 0
            ? `${unread_count} new update${unread_count>1?'s':''}`
            : "You're all caught up!";

        if (!notifications.length) {
            list.innerHTML = `<div class="res-empty"><img src="bell.png"><p>No notifications yet.</p></div>`;
            return;
        }
        list.innerHTML = notifications.map(n => `
            <a class="res-notif-item ${n.is_read==0?'unread':''}"
               href="view-complaint.php?id=${n.complaint_id}">
                <div class="rn-dot"><img src="bell.png" alt=""></div>
                <div class="rn-body">
                    <strong>${typeLabel(n.type)}</strong>
                    <p>${esc(n.message)}</p>
                    <time>${timeAgo(n.created_at)}</time>
                </div>
            </a>`).join('');
    }

    function fetch_notifs() {
        fetch('get_resident_notifications.php?action=fetch')
            .then(r=>r.json()).then(render).catch(()=>{});
    }

    function closeAll() {
        panel.classList.remove('open');
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
        panelOpen = false;
        drawerOpen = false;
    }
    function openPanel()  { closeAll(); panel.classList.add('open'); overlay.classList.add('show'); panelOpen = true; }
    function openDrawer() { closeAll(); sidebar.classList.add('open'); overlay.classList.add('show'); drawerOpen = true; }

    bellBtn.addEventListener('click', () => panelOpen ? closeAll() : openPanel());
    if (hamburger) hamburger.addEventListener('click', () => drawerOpen ? closeAll() : openDrawer());
    if (closeBtn) closeBtn.addEventListener('click', closeAll);
    overlay.addEventListener('click', closeAll);
    markBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        fetch('get_resident_notifications.php?action=mark_read')
            .then(r=>r.json()).then(()=>fetch_notifs());
    });

    fetch_notifs();
    setInterval(fetch_notifs, 15000);
})();
</script>
</body>
</html>
