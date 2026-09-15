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

// ── Summary stats ─────────────────────────────────────────────────────────────
$total      = (int)$conn->query("SELECT COUNT(*) as c FROM complaints")->fetch_assoc()['c'];
$pending    = (int)$conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Pending'")->fetch_assoc()['c'];
$in_process = (int)$conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='In Process'")->fetch_assoc()['c'];
$completed  = (int)$conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Completed'")->fetch_assoc()['c'];
$approved   = (int)$conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Approved'")->fetch_assoc()['c'];
$rescheduled= (int)$conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Rescheduled'")->fetch_assoc()['c'];
$cannot     = (int)$conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Cannot Be Handled'")->fetch_assoc()['c'];

// ── Monthly submissions — last 6 months ───────────────────────────────────────
$monthly = [];
for ($i = 5; $i >= 0; $i--) {
    $y   = date('Y', strtotime("-$i months"));
    $m   = date('m', strtotime("-$i months"));
    $lbl = date('M Y', strtotime("-$i months"));
    $cnt = (int)$conn->query("SELECT COUNT(*) as c FROM complaints
                               WHERE YEAR(date_filed)='$y' AND MONTH(date_filed)='$m'")->fetch_assoc()['c'];
    $monthly[] = ['label' => $lbl, 'count' => $cnt];
}

// ── Monthly resolved — last 6 months ─────────────────────────────────────────
$monthly_resolved = [];
for ($i = 5; $i >= 0; $i--) {
    $y   = date('Y', strtotime("-$i months"));
    $m   = date('m', strtotime("-$i months"));
    $cnt = (int)$conn->query("SELECT COUNT(*) as c FROM complaints
                               WHERE status='Completed'
                                 AND YEAR(date_filed)='$y' AND MONTH(date_filed)='$m'")->fetch_assoc()['c'];
    $monthly_resolved[] = $cnt;
}

// ── Most common complaint subjects (top 5) ────────────────────────────────────
$subjects_res = $conn->query("SELECT subject, COUNT(*) as cnt FROM complaints
                               GROUP BY subject ORDER BY cnt DESC LIMIT 5");
$top_subjects = [];
while ($r = $subjects_res->fetch_assoc()) $top_subjects[] = $r;

// ── Status breakdown ──────────────────────────────────────────────────────────
$statuses = [
    'Pending'          => $pending,
    'Approved'         => $approved,
    'In Process'       => $in_process,
    'Rescheduled'      => $rescheduled,
    'Cannot Be Handled'=> $cannot,
    'Completed'        => $completed,
];

// ── Resolution rate ───────────────────────────────────────────────────────────
$resolution_rate = $total > 0 ? round(($completed / $total) * 100) : 0;

// ── Average days to resolve ───────────────────────────────────────────────────
$avg_res = $conn->query("SELECT AVG(DATEDIFF(NOW(), date_filed)) as avg_days
                          FROM complaints WHERE status='Completed'")->fetch_assoc();
$avg_days = $avg_res['avg_days'] ? round($avg_res['avg_days']) : 0;

// ── This month vs last month ──────────────────────────────────────────────────
$this_month = (int)$conn->query("SELECT COUNT(*) as c FROM complaints
    WHERE YEAR(date_filed)=YEAR(NOW()) AND MONTH(date_filed)=MONTH(NOW())")->fetch_assoc()['c'];
$last_month = (int)$conn->query("SELECT COUNT(*) as c FROM complaints
    WHERE YEAR(date_filed)=YEAR(DATE_SUB(NOW(),INTERVAL 1 MONTH))
      AND MONTH(date_filed)=MONTH(DATE_SUB(NOW(),INTERVAL 1 MONTH))")->fetch_assoc()['c'];

// ── Most active month (all time) ──────────────────────────────────────────────
$active_month_r = $conn->query("SELECT DATE_FORMAT(date_filed,'%M %Y') as mo, COUNT(*) as cnt
    FROM complaints GROUP BY mo ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$active_month = $active_month_r ? $active_month_r['mo'] . ' (' . $active_month_r['cnt'] . ')' : 'N/A';

// ── Complaint list ────────────────────────────────────────────────────────────
$complaints = $conn->query("SELECT * FROM complaints ORDER BY date_filed DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Barangay San Roque</title>
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        :root {
            --green-900: #1B4332;
            --green-700: #2D6A4F;
            --green-500: #40916c;
            --green-100: #e8f5e9;
            --border: #e0ede5;
            --muted: #52796f;
        }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; overflow-x: hidden; }

        /* =============================================
           TOP BAR + SIDEBAR RAIL — matches resident-portal.php
           ============================================= */

        * { font-family: 'Poppins', sans-serif; }

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
            overflow: visible;
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
            position: relative;
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

        /* Visible label beside each sidebar icon */
        .sidebar .nav-item[data-tooltip]::after {
            content: attr(data-tooltip);
            position: absolute;
            left: 58px;
            top: 50%;
            transform: translateY(-50%) translateX(-6px);
            background: #1B4332;
            color: #fff;
            padding: 7px 11px;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.2;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,.18);
            transition: opacity .15s ease, transform .15s ease;
        }

        .sidebar .nav-item[data-tooltip]:hover::after,
        .sidebar .nav-item[data-tooltip]:focus-visible::after {
            opacity: 1;
            visibility: visible;
            transform: translateY(-50%) translateX(0);
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
            width: calc(100% - 76px);
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
            margin-left: 76px;
            width: 100%;
            min-height: 100vh;
        }

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

        /* Complaint rows: staggered slide-in on load + hover glow */
        .complaint-row {
            border-radius: 10px;
            margin: 0 -12px;
            transition: transform .15s ease, box-shadow .25s ease, background .25s ease;
            opacity: 0;
            animation: rowSlideIn .45s ease forwards;
        }
        @keyframes rowSlideIn {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .complaint-row:hover {
            background: #F6FBF8;
            box-shadow: 0 0 0 1px rgba(45,106,79,0.12),
                        0 10px 26px rgba(45,106,79,0.10),
                        0 0 20px rgba(56,142,60,0.22);
        }
        .complaint-row:active { transform: scale(0.995); }
        .view-btn { transition: transform .15s ease, opacity .15s ease; }
        .view-btn:active { transform: scale(0.96); }

        /* ── Top stat cards ── */
        .stats-container { display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:24px; }
        .stat-card { background:#fff;border-radius:12px;padding:20px;border:1px solid #edf2f7;display:flex;align-items:center;justify-content:space-between; }
        .stat-info span { font-size:12px;color:#64748b;display:block;margin-bottom:4px; }
        .stat-info h2   { font-size:28px;font-weight:700;color:#1a202c;margin:0; }
        .stat-icon-bg   { width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center; }
        .stat-icon-bg img { width:22px; }
        .stat-card.total     .stat-icon-bg { background:#f0fdf4; }
        .stat-card.pending   .stat-icon-bg { background:#fef3c7; }
        .stat-card.in-process .stat-icon-bg{ background:#dbeafe; }
        .stat-card.completed  .stat-icon-bg{ background:#e0e7ff; }

        /* ── Analytics toggle ── */
        .analytics-toggle-bar {
            display:flex;align-items:center;justify-content:space-between;
            background:#fff;border:1px solid #edf2f7;border-radius:12px;
            padding:16px 22px;margin-bottom:6px;cursor:pointer;
            transition:background .15s;user-select:none;
        }
        .analytics-toggle-bar:hover { background:#fafbfc; }
        .atb-left { display:flex;align-items:center;gap:12px; }
        .atb-left h3 { font-size:15px;font-weight:600;color:#1a202c;margin:0; }
        .atb-left span { font-size:12px;color:#64748b; }
        .atb-chevron { width:20px;height:20px;color:#94a3b8;transition:transform .25s;flex-shrink:0; }
        .atb-chevron.open { transform:rotate(180deg); }

        /* ── Analytics panel ── */
        .analytics-panel { display:none;margin-bottom:24px; }
        .analytics-panel.open { display:block; }

        /* Grid of analytics cards */
        .analytics-grid {
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
            margin-top:16px;
        }
        .analytics-grid.wide { grid-template-columns:1fr; }

        .an-card {
            background:#fff;border-radius:12px;border:1px solid #edf2f7;padding:20px;
        }
        .an-card h4 {
            font-size:13px;font-weight:600;color:#374151;
            margin:0 0 16px;display:flex;align-items:center;gap:7px;
        }

        /* ── KPI strip ── */
        .kpi-strip { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px; }
        .kpi-box {
            background:#fff;border-radius:10px;border:1px solid #edf2f7;
            padding:16px;text-align:center;
        }
        .kpi-val  { font-size:26px;font-weight:700;color:#1B4332;line-height:1; }
        .kpi-lbl  { font-size:11px;color:#64748b;margin-top:4px; }
        .kpi-sub  { font-size:11px;font-weight:600;margin-top:3px; }
        .kpi-sub.up   { color:#059669; }
        .kpi-sub.down { color:#dc2626; }
        .kpi-sub.same { color:#94a3b8; }

        /* ── Bar chart ── */
        .bar-chart { display:flex;align-items:flex-end;gap:10px;height:120px;padding-bottom:24px;position:relative; }
        .bar-wrap  { flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%; }
        .bar-col   { width:100%;border-radius:4px 4px 0 0;min-height:4px;transition:height .3s; }
        .bar-col.submitted { background:#2D6A4F; }
        .bar-col.resolved  { background:#86efac; }
        .bar-lbl   { font-size:10px;color:#94a3b8;white-space:nowrap;margin-top:2px; }
        .bar-num   { font-size:10px;font-weight:600;color:#374151; }
        .bar-chart-wrap { position:relative; }
        .bar-legend { display:flex;gap:16px;margin-top:8px;justify-content:flex-end; }
        .bl-item { display:flex;align-items:center;gap:5px;font-size:11px;color:#64748b; }
        .bl-dot  { width:10px;height:10px;border-radius:3px;flex-shrink:0; }

        /* ── Status donut (CSS only) ── */
        .donut-wrap { display:flex;align-items:center;gap:20px; }
        .donut-svg  { flex-shrink:0; }
        .donut-list { flex:1;display:flex;flex-direction:column;gap:7px; }
        .dl-row     { display:flex;align-items:center;gap:8px;font-size:12px; }
        .dl-dot     { width:10px;height:10px;border-radius:50%;flex-shrink:0; }
        .dl-label   { flex:1;color:#374151; }
        .dl-count   { font-weight:600;color:#1a202c; }
        .dl-pct     { color:#94a3b8;font-size:11px;min-width:32px;text-align:right; }

        /* ── Top subjects ── */
        .subject-bar-row { margin-bottom:10px; }
        .sbr-top  { display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px; }
        .sbr-name { color:#374151;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:75%; }
        .sbr-cnt  { color:#1B4332;font-weight:700; }
        .sbr-track{ height:7px;background:#f1f5f9;border-radius:99px;overflow:hidden; }
        .sbr-fill { height:100%;border-radius:99px;background:#2D6A4F;transition:width .4s; }

        /* ── Month comparison ── */
        .month-cmp { display:flex;gap:14px; }
        .mc-box {
            flex:1;border-radius:10px;padding:14px 16px;border:1px solid #edf2f7;
            text-align:center;
        }
        .mc-box.this { background:#f0fdf4;border-color:#86efac; }
        .mc-box.last { background:#f8fafc; }
        .mc-val { font-size:28px;font-weight:700;color:#1B4332; }
        .mc-lbl { font-size:11px;color:#64748b;margin-top:2px; }

        /* ── Complaint list ── */
        .list-header { display:flex;align-items:center;justify-content:space-between;margin-bottom:16px; }
        .list-header h3 { font-size:16px;font-weight:600;color:#1a202c;margin:0; }
        .complaint-list { background:#fff;border-radius:12px;border:1px solid #edf2f7;padding:20px; }
        .complaint-row {
            display:flex;align-items:center;gap:16px;
            padding:14px 12px;border-bottom:1px solid #f8fafc;
        }
        .complaint-row:last-child { border-bottom:none; }
        .row-id-section { min-width:130px; }
        .case-id-label { font-size:11px;font-weight:700;color:#1B4332;display:block;margin-bottom:4px; }
        .row-details { flex:1;display:flex;gap:24px; }
        .detail-item label  { font-size:10px;color:#94a3b8;text-transform:uppercase;display:block;margin-bottom:2px; }
        .detail-item strong { font-size:13px;color:#1a202c; }
        .view-btn {
            padding:7px 16px;background:#1B4332;color:#fff;border-radius:7px;
            font-size:12px;font-weight:600;text-decoration:none;
            transition:background .15s;white-space:nowrap;
        }
        .view-btn:hover { background:#004d2c; }

        /* Badge */
        .badge { display:inline-block;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700; }
        .badge.pending           { background:#fef3c7;color:#92400e; }
        .badge.approved          { background:#d1fae5;color:#065f46; }
        .badge.in-process        { background:#dbeafe;color:#1e40af; }
        .badge.rescheduled       { background:#f5f3ff;color:#5b21b6; }
        .badge.cannot-be-handled { background:#ffedd5;color:#9a3412; }
        .badge.completed         { background:#e0e7ff;color:#3730a3; }

        /* Notification bell */
        .notif-wrapper{position:relative;display:flex;justify-content:center;align-items:center;padding:15px;cursor:pointer;opacity:0.6;transition:opacity 0.2s;}
        .notif-wrapper:hover{opacity:1;}
        .notif-badge{position:absolute;top:4px;right:4px;background:#e53e3e;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;display:none;align-items:center;justify-content:center;padding:0 4px;border:2px solid #004d2c;line-height:1;}
        .notif-badge.has-notif{display:flex;}
        .notif-overlay{display:none;position:fixed;inset:0;z-index:9000;}
        .notif-overlay.show{display:block;}
        .notif-dropdown{position:fixed;top:0;left:-420px;width:360px;height:100vh;background:#fff;border-radius:0 16px 16px 0;box-shadow:6px 0 30px rgba(0,0,0,.15);z-index:9999;display:flex;flex-direction:column;transition:left .32s cubic-bezier(0.4,0,0.2,1);overflow:hidden;}
        .notif-dropdown.open{left:76px;}
        .notif-panel-header{background:#004d2c;color:#fff;padding:20px 22px 16px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;}
        .notif-panel-header h3{font-size:15px;font-weight:600;margin:0 0 3px;}
        .notif-panel-header small{font-size:11px;opacity:.75;display:block;}
        .mark-all-read-btn{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;}
        .res-panel-actions { display:flex; align-items:center; gap:10px; flex-shrink:0; }
.res-panel-close {
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 15px;
            line-height: 1;
            flex-shrink: 0;
            transition: background 0.2s;
        }
        .res-panel-close:hover { background: rgba(255,255,255,0.26); }
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

/* ── Delete button ── */
.delete-btn {
    display:inline-flex;align-items:center;gap:5px;
    padding:7px 12px;border-radius:8px;
    border:1px solid #fee2e2;background:#fff5f5;
    cursor:pointer;text-decoration:none;
    font-size:12px;font-weight:500;color:#dc2626;
    transition:background .15s,border-color .15s;
    margin-left:8px;
}
.delete-btn:hover { background:#fee2e2;border-color:#fca5a5; }
.delete-btn img { width:14px;height:14px; }

/* ===================================
   RESPONSIVE — placed last so these actually win the cascade
   =================================== */
@media screen and (max-width: 992px) {
    .sidebar { transform: translateX(-100%); box-shadow: 6px 0 24px rgba(0,0,0,0.18); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-close-btn { display: flex; }
    .hamburger-btn { display: flex; }
    .app-topbar { left: 0; width: 100%; padding: 0 16px; }
    .portal-content { margin-left: 0; width: 100%; }
    .notif-dropdown.open { left: 0 !important; }
}
@media screen and (max-width: 768px) {
    .topbar-eyebrow { display: none; }
    .topbar-title { font-size: 0.95rem; }
    .portal-content { padding: 84px 16px 24px !important; }

    /* Stat cards, KPI strip, analytics grid: collapse to fewer columns */
    .stats-container { grid-template-columns: repeat(2, 1fr); gap: 12px; }
    .kpi-strip { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .analytics-grid { grid-template-columns: 1fr; }
    .month-cmp { flex-direction: column; }

    .stat-card { padding: 14px; }
    .stat-info h2 { font-size: 22px; }
    .an-card { padding: 14px; }

    /* Complaint rows: stack instead of forcing a rigid row */
    .complaint-row { flex-wrap: wrap; align-items: flex-start; gap: 10px; }
    .row-id-section { min-width: 0; width: 100%; }
    .row-details { flex-wrap: wrap; gap: 12px 20px; width: 100%; }
    .view-btn, .delete-btn { margin-left: 0; }

    .notif-dropdown { width: 100%; max-width: 100vw; }
}
@media screen and (max-width: 480px) {
    .stats-container { grid-template-columns: 1fr; }
    .kpi-strip { grid-template-columns: 1fr; }
    .list-header { flex-wrap: wrap; gap: 8px; }
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
                <span class="topbar-title">Dashboard</span>
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
            <a href="admin-dashboard.php"          class="nav-item active" title="Dashboard" data-tooltip="Dashboard"><img src="dashboard.png"></a>
            <a href="admin-lupon-assignments.php"   class="nav-item" title="Lupon Assignments" data-tooltip="Lupon Assignments"><img src="people.png"></a>
            <a href="admin-committee.php"           class="nav-item" title="Committee" data-tooltip="Committee"><img src="committee.png"></a>
            <a href="admin-calendar.php"            class="nav-item" title="Calendar" data-tooltip="Calendar"><img src="calendar.png"></a>
            <a href="admin-announcements.php"       class="nav-item" title="Announcements" data-tooltip="Announcements"><img src="announcements.png"></a>
            <a href="admin-file-maintenance.php"    class="nav-item" title="File Maintenance" data-tooltip="File Maintenance"><img src="file.png"></a>
            <a href="admin-audit-trail.php"          class="nav-item" title="Audit Trail" data-tooltip="Audit Trail"><img src="audit.png"></a>
            <div class="nav-item notif-wrapper" id="bellBtn" data-tooltip="Notifications">
                <img src="bell.png" id="bellIcon">
                <span class="notif-badge" id="notifBadge"></span>
            </div>
            <a href="logout.php" onclick="openLogoutModal(); return false;" class="nav-item logout-item" title="Logout" data-tooltip="Logout">
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
            <div class = "res-panel-actions">
            <button class="mark-all-read-btn" id="markReadBtn">Mark all read</button>
            <button class="res-panel-close" id="resPanelClose" aria-label="Close notifications">✕</button>
            </div>
        </div>
        <div class="notif-list" id="nList">
            <div class="n-empty"><img src="bell.png"><p>No notifications yet.</p></div>
        </div>
    </div>

    <main class="portal-content" style="padding:94px 36px 30px;">

        <header style="margin-bottom:24px;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-size:22px;font-weight:700;color:#1a202c;margin:0 0 4px;">Dashboard</h1>
                <p style="font-size:13px;color:#64748b;margin:0;">Overview of all community complaints — <?php echo date('F j, Y'); ?></p>
            </div>
            <div style="display:flex;gap:10px;flex-shrink:0;">
                <a href="admin-announcements.php" style="text-decoration:none;">
                    <button title = "Post Announcements" style="display:flex;align-items:center;gap:8px;background:#2D6A4F;color:#fff;border:none;padding:11px 20px;border-radius:9px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.2s;">
                        <title>Post an Announcement</title>
                        <img src="post.png" style="width:15px;height:15px;filter:brightness(0) invert(1);"> 
                    </button>
                </a>
                <button id="exportPdfBtn" title = "Export statistics into PDF" style="display:flex;align-items:center;gap:8px;background:#1B4332;color:#fff;border:none;padding:11px 20px;border-radius:9px;font-family:'Poppins',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:background 0.2s;">
                    <title>Export statistics into PDF</title>
                    <img src="download.png" style="width:15px;height:15px;filter:brightness(0) invert(1);">
                </button>
            </div>
        </header>

        <!-- ── Top summary cards ── -->
        <section class="stats-container">
            <div class="stat-card total">
                <div class="stat-info"><span>Total complaints</span><h2><?php echo $total; ?></h2></div>
                <div class="stat-icon-bg"><img src="dashboard.png"></div>
            </div>
            <div class="stat-card pending">
                <div class="stat-info"><span>Pending approval</span><h2><?php echo $pending; ?></h2></div>
                <div class="stat-icon-bg"><img src="clock.png"></div>
            </div>
            <div class="stat-card in-process">
                <div class="stat-info"><span>In process</span><h2><?php echo $in_process; ?></h2></div>
                <div class="stat-icon-bg"><img src="clock.png"></div>
            </div>
            <div class="stat-card completed">
                <div class="stat-info"><span>Completed</span><h2><?php echo $completed; ?></h2></div>
                <div class="stat-icon-bg"><img src="completed.png"></div>
            </div>
        </section>

        <!-- ── Analytics toggle bar ── -->
        <div class="analytics-toggle-bar" id="analyticsToggle" onclick="toggleAnalytics()">
            <div class="atb-left">
                <span style="font-size:18px;">📊</span>
                <div>
                    <h3>Analytics &amp; Insights</h3>
                    <span>Monthly trends, complaint breakdown, resolution stats</span>
                </div>
            </div>
            <svg class="atb-chevron" id="atbChevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M6 9l6 6 6-6"/>
            </svg>
        </div>

        <!-- ── Analytics panel ── -->
        <div class="analytics-panel" id="analyticsPanel">

            <!-- KPI strip -->
            <div class="kpi-strip">
                <div class="kpi-box">
                    <div class="kpi-val"><?php echo $resolution_rate; ?>%</div>
                    <div class="kpi-lbl">Resolution rate</div>
                    <div class="kpi-sub <?php echo $resolution_rate >= 50 ? 'up' : 'down'; ?>">
                        <?php echo $resolution_rate >= 50 ? '↑ On track' : '↓ Needs attention'; ?>
                    </div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-val"><?php echo $avg_days; ?></div>
                    <div class="kpi-lbl">Avg. days to resolve</div>
                    <div class="kpi-sub same">from date filed</div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-val"><?php echo $this_month; ?></div>
                    <div class="kpi-lbl">Filed this month</div>
                    <div class="kpi-sub <?php echo $this_month >= $last_month ? 'up' : 'down'; ?>">
                        <?php
                        $diff = $this_month - $last_month;
                        echo ($diff >= 0 ? '↑ +' : '↓ ') . abs($diff) . ' vs last month';
                        ?>
                    </div>
                </div>
                <div class="kpi-box">
                    <div class="kpi-val" style="font-size:15px;padding-top:4px;"><?php echo $active_month; ?></div>
                    <div class="kpi-lbl">Most active month</div>
                    <div class="kpi-sub same">all time</div>
                </div>
            </div>

            <!-- Row 1: Monthly bar chart + Status breakdown -->
            <div class="analytics-grid">

                <!-- Bar chart: submissions vs resolved -->
                <div class="an-card">
                    <h4>📅 Monthly submissions vs resolved <span style="font-weight:400;color:#94a3b8;font-size:11px;">(last 6 months)</span></h4>
                    <?php
                    $max_bar = max(array_merge(array_column($monthly, 'count'), $monthly_resolved, [1]));
                    ?>
                    <div class="bar-chart-wrap">
                        <div class="bar-chart">
                            <?php foreach ($monthly as $i => $mo): ?>
                            <div class="bar-wrap">
                                <div style="flex:1;display:flex;align-items:flex-end;gap:2px;width:100%;">
                                    <div class="bar-col submitted"
                                         style="height:<?php echo round(($mo['count']/$max_bar)*100); ?>%;flex:1;"
                                         title="Submitted: <?php echo $mo['count']; ?>"></div>
                                    <div class="bar-col resolved"
                                         style="height:<?php echo round(($monthly_resolved[$i]/$max_bar)*100); ?>%;flex:1;"
                                         title="Resolved: <?php echo $monthly_resolved[$i]; ?>"></div>
                                </div>
                                <div class="bar-num"><?php echo $mo['count']; ?></div>
                                <div class="bar-lbl"><?php echo substr($mo['label'],0,3); ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="bar-legend">
                            <div class="bl-item"><div class="bl-dot" style="background:#2D6A4F;"></div> Submitted</div>
                            <div class="bl-item"><div class="bl-dot" style="background:#86efac;"></div> Resolved</div>
                        </div>
                    </div>
                </div>

                <!-- Status breakdown donut -->
                <div class="an-card">
                    <h4>🗂 Status breakdown</h4>
                    <?php
                    $status_colors = [
                        'Pending'          => '#fbbf24',
                        'Approved'         => '#34d399',
                        'In Process'       => '#60a5fa',
                        'Rescheduled'      => '#a78bfa',
                        'Cannot Be Handled'=> '#fb923c',
                        'Completed'        => '#818cf8',
                    ];
                    $donut_total = max(array_sum(array_values($statuses)), 1);

                    $circumference = 2 * pi() * 40;
                    $offset = 0;
                    $segments = [];
                    foreach ($statuses as $lbl => $cnt) {
                        $pct  = $cnt / $donut_total;
                        $dash = $pct * $circumference;
                        $segments[] = [
                            'label'  => $lbl,
                            'count'  => $cnt,
                            'pct'    => round($pct * 100),
                            'dash'   => $dash,
                            'offset' => $circumference - $offset,
                            'color'  => $status_colors[$lbl] ?? '#94a3b8',
                        ];
                        $offset += $dash;
                    }
                    ?>
                    <div class="donut-wrap">
                        <svg class="donut-svg" width="110" height="110" viewBox="0 0 110 110">
                            <circle cx="55" cy="55" r="40" fill="none" stroke="#f1f5f9" stroke-width="18"/>
                            <?php foreach ($segments as $seg): if ($seg['count'] === 0) continue; ?>
                            <circle cx="55" cy="55" r="40" fill="none"
                                    stroke="<?php echo $seg['color']; ?>"
                                    stroke-width="18"
                                    stroke-dasharray="<?php echo $seg['dash']; ?> <?php echo $circumference - $seg['dash']; ?>"
                                    stroke-dashoffset="<?php echo $seg['offset']; ?>"
                                    transform="rotate(-90 55 55)"/>
                            <?php endforeach; ?>
                            <text x="55" y="51" text-anchor="middle" font-size="16" font-weight="700" fill="#1a202c" font-family="Poppins,sans-serif"><?php echo $total; ?></text>
                            <text x="55" y="64" text-anchor="middle" font-size="9" fill="#94a3b8" font-family="Poppins,sans-serif">total</text>
                        </svg>
                        <div class="donut-list">
                            <?php foreach ($segments as $seg): ?>
                            <div class="dl-row">
                                <div class="dl-dot" style="background:<?php echo $seg['color']; ?>;"></div>
                                <span class="dl-label"><?php echo $seg['label']; ?></span>
                                <span class="dl-count"><?php echo $seg['count']; ?></span>
                                <span class="dl-pct"><?php echo $seg['pct']; ?>%</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Top subjects + Month comparison -->
            <div class="analytics-grid" style="margin-top:16px;">

                <!-- Top complaint subjects -->
                <div class="an-card">
                    <h4>🔥 Most common complaint types <span style="font-weight:400;color:#94a3b8;font-size:11px;">(top 5)</span></h4>
                    <?php if (empty($top_subjects)): ?>
                    <p style="font-size:13px;color:#94a3b8;text-align:center;padding:20px 0;">No data yet.</p>
                    <?php else:
                        $max_s = max(array_column($top_subjects, 'cnt'));
                        foreach ($top_subjects as $idx => $s):
                            $bar_w = $max_s > 0 ? round(($s['cnt']/$max_s)*100) : 0;
                    ?>
                    <div class="subject-bar-row">
                        <div class="sbr-top">
                            <span class="sbr-name"><?php echo htmlspecialchars($s['subject']); ?></span>
                            <span class="sbr-cnt"><?php echo $s['cnt']; ?> case<?php echo $s['cnt'] != 1 ? 's' : ''; ?></span>
                        </div>
                        <div class="sbr-track">
                            <div class="sbr-fill" style="width:<?php echo $bar_w; ?>%;opacity:<?php echo 1 - ($idx * 0.12); ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>

                <!-- This month vs last month + resolution rate ring -->
                <div class="an-card">
                    <h4>📆 This month vs last month</h4>
                    <div class="month-cmp" style="margin-bottom:20px;">
                        <div class="mc-box this">
                            <div class="mc-val"><?php echo $this_month; ?></div>
                            <div class="mc-lbl"><?php echo date('F Y'); ?></div>
                        </div>
                        <div class="mc-box last">
                            <div class="mc-val" style="color:#64748b;"><?php echo $last_month; ?></div>
                            <div class="mc-lbl"><?php echo date('F Y', strtotime('-1 month')); ?></div>
                        </div>
                    </div>

                    <h4 style="margin-top:4px;">✅ Overall resolution rate</h4>
                    <div style="display:flex;align-items:center;gap:16px;">
                        <svg width="80" height="80" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="30" fill="none" stroke="#f1f5f9" stroke-width="12"/>
                            <circle cx="40" cy="40" r="30" fill="none" stroke="#2D6A4F" stroke-width="12"
                                    stroke-dasharray="<?php echo round(($resolution_rate/100)*188.5); ?> 188.5"
                                    stroke-dashoffset="47.1"
                                    transform="rotate(-90 40 40)"/>
                            <text x="40" y="37" text-anchor="middle" font-size="14" font-weight="700" fill="#1a202c" font-family="Poppins,sans-serif"><?php echo $resolution_rate; ?>%</text>
                            <text x="40" y="50" text-anchor="middle" font-size="8" fill="#94a3b8" font-family="Poppins,sans-serif">resolved</text>
                        </svg>
                        <div style="font-size:12px;color:#64748b;line-height:1.7;">
                            <strong style="display:block;font-size:13px;color:#1a202c;"><?php echo $completed; ?> of <?php echo $total; ?> cases resolved</strong>
                            Average <?php echo $avg_days; ?> days to complete<br>
                            <?php echo $pending; ?> case<?php echo $pending!=1?'s':''; ?> awaiting review
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Complaint list ── -->
        <section class="complaint-list">
            <div class="list-header">
                <h3>Recent complaints</h3>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:12px;color:#94a3b8;" id="list-count"><?php echo $total; ?> total</span>
                    <select id="statusFilter" onchange="filterComplaints(this.value)" style="
                        padding:7px 30px 7px 12px;
                        border:1.5px solid #e2e8f0;
                        border-radius:8px;
                        font-family:'Poppins',sans-serif;
                        font-size:12px;
                        font-weight:500;
                        color:#374151;
                        background:#fff url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E\") no-repeat right 10px center;
                        appearance:none;-webkit-appearance:none;
                        cursor:pointer;outline:none;
                        transition:border-color .15s;
                    " onfocus="this.style.borderColor='#2D6A4F'" onblur="this.style.borderColor='#e2e8f0'">
                        <option value="all">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="In Process">In Process</option>
                        <option value="Rescheduled">Rescheduled</option>
                        <option value="Cannot Be Handled">Cannot Be Handled</option>
                        <option value="Completed">Completed</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>
            <?php
            $complaints->data_seek(0);
            while ($row = $complaints->fetch_assoc()):
                $sc = strtolower(str_replace([' ','_'], '-', $row['status']));
            ?>
            <div class="complaint-row" data-status="<?php echo htmlspecialchars($row['status']); ?>">
                <div class="row-id-section">
                    <span class="case-id-label">BRGY-2026-0<?php echo $row['id']; ?></span>
                    <span class="badge <?php echo $sc; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                    <?php if (!empty($row['is_paper_based'])): ?>
                    <span class="badge" style="background:#fef3c7;color:#92400e;margin-top:3px;">📄 Paper</span>
                    <?php endif; ?>
                </div>
                <div class="row-details">
                    <div class="detail-item">
                        <label>Subject</label>
                        <strong><?php echo htmlspecialchars($row['subject']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <label>Complainant</label>
                        <strong><?php echo htmlspecialchars($row['complainant_name']); ?></strong>
                    </div>
                    <div class="detail-item">
                        <label>Date filed</label>
                        <strong><?php echo date("M j, Y", strtotime($row['date_filed'])); ?></strong>
                    </div>
                </div>
                <div style="display:flex;align-items:center;flex-wrap:wrap;gap:4px;">
    <a href="admin-view-complaint.php?id=<?php echo $row['id']; ?>" class="view-btn">View details</a>
    <a href="#" class="delete-btn" data-id="<?php echo $row['id']; ?>"
   onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['complainant_name'])); ?>'); return false;">
    <img src="delete.png" alt="Delete"> Delete
</a>
</div>
            </div>
            <?php endwhile; ?>
        </section>

    </main>

    <script>
        function toggleAnalytics() {
            const panel   = document.getElementById('analyticsPanel');
            const chevron = document.getElementById('atbChevron');
            const open    = panel.classList.toggle('open');
            chevron.classList.toggle('open', open);
        }

        function filterComplaints(status) {
            const rows    = document.querySelectorAll('.complaint-row');
            const counter = document.getElementById('list-count');
            let visible   = 0;

            rows.forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const show      = status === 'all' || rowStatus === status;
                row.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            // Update counter
            counter.textContent = status === 'all'
                ? `${rows.length} total`
                : `${visible} of ${rows.length} shown`;

            // Show empty state if no results
            const existing = document.getElementById('no-results-row');
            if (existing) existing.remove();
            if (visible === 0) {
                const empty = document.createElement('div');
                empty.id = 'no-results-row';
                empty.style.cssText = 'text-align:center;padding:32px;color:#94a3b8;font-size:13px;';
                empty.textContent = `No complaints with status "${status}" found.`;
                document.querySelector('.complaint-list').appendChild(empty);
            }
        }

        // Notification bell
        const bellBtn=document.getElementById('bellBtn'),dropdown=document.getElementById('notifDropdown'),overlay=document.getElementById('notifOverlay'),badge=document.getElementById('notifBadge'),nList=document.getElementById('nList'),nLabel=document.getElementById('nLabel'),markBtn=document.getElementById('markReadBtn');
        let bellOpen=false;
        function taAgo(d){const s=Math.floor((new Date()-new Date(d))/1000);if(s<60)return'just now';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';}
        function escN(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
        function renderBell({notifications,unread_count}){
            if(unread_count>0){badge.textContent=unread_count>99?'99+':unread_count;badge.classList.add('has-notif');}
            else badge.classList.remove('has-notif');
            nLabel.textContent=unread_count>0?`${unread_count} unread`:'All caught up!';
            nList.innerHTML=notifications.length?notifications.map(n=>`<a class="n-item ${n.is_read==0?'unread':''}" href="${n.type === 'id_verification' ? 'admin-review-id.php?resident_id=' + n.resident_id : (n.type === 'new_appointment' || n.type === 'unassigned_warning') ? 'admin-lupon-assignments.php?complaint_id=' + n.complaint_id : 'admin-view-complaint.php?id=' + n.complaint_id}">
<div class="n-dot"><img src="${n.type === 'id_verification' ? 'people.png' : 'file.png'}"></div><div class="n-body"><strong>${escN(n.subject)}</strong><p>${escN(n.message)}</p><time>${taAgo(n.created_at)}</time></div></a>`).join(''):`<div class="n-empty"><img src="bell.png"><p>No notifications.</p></div>`;
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

// ── Delete modal ──
(function() {
    const overlay = document.createElement('div');
    overlay.id = 'del-overlay';
    overlay.style.cssText = 'display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;align-items:center;justify-content:center;';
    overlay.innerHTML = `
        <div style="background:#fff;border-radius:14px;padding:28px 28px 22px;width:360px;max-width:90vw;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:40px;height:40px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <img src="delete.png" style="width:20px;height:20px;">
                </div>
                <div>
                    <div style="font-weight:600;font-size:15px;color:#1a202c;">Delete complaint?</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">This action cannot be undone.</div>
                </div>
            </div>
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#991b1b;margin-bottom:20px;" id="del-info"></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <button id="del-cancel" style="padding:8px 18px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;font-size:13px;font-weight:500;color:#374151;cursor:pointer;">Cancel</button>
                <button id="del-confirm" style="padding:8px 18px;border-radius:8px;border:none;background:#dc2626;color:#fff;font-size:13px;font-weight:500;cursor:pointer;">Yes, delete</button>
            </div>
        </div>`;
    document.body.appendChild(overlay);

    let pendingId = null;

    overlay.addEventListener('click', function(e) { if (e.target === overlay) closeModal(); });
    document.getElementById('del-cancel').addEventListener('click', closeModal);
    document.getElementById('del-confirm').addEventListener('click', function() {
        if (!pendingId) return;
        const id = pendingId;
        closeModal();
        fetch('delete_complaint.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                const btn = document.querySelector(`.delete-btn[data-id="${id}"]`);
                if (btn) {
                    const row = btn.closest('.complaint-row');
                    row.style.transition = 'opacity .3s';
                    row.style.opacity = '0';
                    setTimeout(() => row.remove(), 300);
                }
            } else {
                alert('Error: ' + (d.message || 'Could not delete complaint.'));
            }
        })
        .catch(() => alert('Network error. Please try again.'));
    });

    function closeModal() {
        overlay.style.display = 'none';
        pendingId = null;
    }

    window.confirmDelete = function(id, name) {
        pendingId = id;
        document.getElementById('del-info').textContent = 'BRGY-2026-0' + id + ' — ' + name;
        overlay.style.display = 'flex';
    };
})();
    </script>

<!-- ── Export PDF confirmation modal ── -->
<div id="exportOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99999;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(3px);" onclick="if(event.target===this)closeExportModal()">
    <div style="background:#fff;border-radius:18px;padding:36px 32px 28px;width:100%;max-width:400px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,0.18);position:relative;">
        <button onclick="closeExportModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
        <div style="width:64px;height:64px;border-radius:50%;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
            <img src="file.png" style="width:26px;height:26px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg);">
        </div>
        <h2 style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:700;color:#1a202c;margin:0 0 8px;">Export dashboard report?</h2>
        <p style="font-family:'Poppins',sans-serif;font-size:13px;color:#64748b;margin:0 0 28px;line-height:1.6;">This will generate a PDF snapshot of today's stats, key metrics, monthly trends, and status breakdown — based on the numbers currently on screen.</p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <button onclick="confirmExportPdf()" style="background:#1B4332;color:#fff;border:none;padding:13px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Yes, export PDF</button>
            <button onclick="closeExportModal()" style="background:#f8fafc;color:#374151;border:1.5px solid #e2e8f0;padding:13px;border-radius:10px;font-family:'Poppins',sans-serif;font-size:14px;font-weight:600;cursor:pointer;">Cancel</button>
        </div>
    </div>
</div>

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
document.addEventListener('keydown', e => { if(e.key==='Escape') { closeLogoutModal(); closeExportModal(); } });
</script>

<script>
    // ---- Smooth transition into a complaint's detail view ----
    // Delegated listener so it also catches notification links injected later via innerHTML.
    (function() {
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href^="admin-view-complaint.php"], a[href^="admin-lupon-assignments.php"]');
            if (!link) return;
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            e.preventDefault();
            const href = link.getAttribute('href');
            document.body.classList.add('page-exit');
            setTimeout(function() { window.location.href = href; }, 260);
        });
        // Reset in case the page is restored from bfcache (browser back button)
        window.addEventListener('pageshow', function() {
            document.body.classList.remove('page-exit');
        });
    })();

    // ---- Stagger the complaint rows' slide-in on load ----
    (function() {
        document.querySelectorAll('.complaint-row').forEach(function(row, i) {
            row.style.animationDelay = (Math.min(i, 14) * 45) + 'ms';
        });
    })();
</script>

<script>
// ── PDF export data, populated from PHP ──
const reportData = {
    generatedOn: <?php echo json_encode(date('F j, Y \a\t g:i A')); ?>,
    summary: {
        total: <?php echo (int)$total; ?>,
        pending: <?php echo (int)$pending; ?>,
        inProcess: <?php echo (int)$in_process; ?>,
        completed: <?php echo (int)$completed; ?>
    },
    kpis: {
        resolutionRate: <?php echo (int)$resolution_rate; ?>,
        avgDays: <?php echo (int)$avg_days; ?>,
        filedThisMonth: <?php echo (int)$this_month; ?>,
        filedLastMonth: <?php echo (int)$last_month; ?>,
        activeMonth: <?php echo json_encode($active_month); ?>
    },
    monthly: <?php
        echo json_encode(array_map(function($mo, $resolved) {
            return ['label' => $mo['label'], 'submitted' => $mo['count'], 'resolved' => $resolved];
        }, $monthly, $monthly_resolved));
    ?>,
    statusBreakdown: <?php
        $status_rows = [];
        foreach ($statuses as $lbl => $cnt) {
            $status_rows[] = ['label' => $lbl, 'count' => $cnt, 'pct' => $total > 0 ? round(($cnt / $total) * 100) : 0];
        }
        echo json_encode($status_rows);
    ?>,
    topSubjects: <?php echo json_encode(array_map(function($s) { return ['subject' => $s['subject'], 'count' => (int)$s['cnt']]; }, $top_subjects)); ?>
};

document.getElementById('exportPdfBtn').addEventListener('click', function() {
    document.getElementById('exportOverlay').style.display = 'flex';
});

function closeExportModal() {
    document.getElementById('exportOverlay').style.display = 'none';
}

function confirmExportPdf() {
    closeExportModal();
    generateDashboardPdf();
}

function generateDashboardPdf() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ unit: 'pt', format: 'a4' });
    const pageWidth = doc.internal.pageSize.getWidth();
    const marginX = 40;
    let y = 50;

    // ── Letterhead ──
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(15);
    doc.setTextColor(27, 67, 50); // #1B4332
    doc.text('Barangay San Roque — Complaint Management System', marginX, y);
    y += 20;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.setTextColor(100, 116, 139);
    doc.text('Dashboard Report — generated ' + reportData.generatedOn, marginX, y);
    y += 8;
    doc.setDrawColor(27, 67, 50);
    doc.setLineWidth(1);
    doc.line(marginX, y, pageWidth - marginX, y);
    y += 25;

    // ── Summary stats ──
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.setTextColor(27, 67, 50);
    doc.text('Summary', marginX, y);
    y += 10;

    doc.autoTable({
        startY: y,
        margin: { left: marginX, right: marginX },
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 10, cellPadding: 6 },
        headStyles: { fillColor: [27, 67, 50], textColor: 255, fontStyle: 'bold' },
        head: [['Total Complaints', 'Pending Approval', 'In Process', 'Completed']],
        body: [[
            String(reportData.summary.total),
            String(reportData.summary.pending),
            String(reportData.summary.inProcess),
            String(reportData.summary.completed)
        ]],
    });
    y = doc.lastAutoTable.finalY + 25;

    // ── KPIs ──
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.setTextColor(27, 67, 50);
    doc.text('Key Metrics', marginX, y);
    y += 10;

    doc.autoTable({
        startY: y,
        margin: { left: marginX, right: marginX },
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 10, cellPadding: 6 },
        headStyles: { fillColor: [27, 67, 50], textColor: 255, fontStyle: 'bold' },
        head: [['Resolution Rate', 'Avg. Days to Resolve', 'Filed This Month', 'Filed Last Month', 'Most Active Month']],
        body: [[
            reportData.kpis.resolutionRate + '%',
            String(reportData.kpis.avgDays),
            String(reportData.kpis.filedThisMonth),
            String(reportData.kpis.filedLastMonth),
            reportData.kpis.activeMonth
        ]],
    });
    y = doc.lastAutoTable.finalY + 25;

    // ── Monthly submissions vs resolved ──
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.setTextColor(27, 67, 50);
    doc.text('Monthly Submissions vs. Resolved (Last 6 Months)', marginX, y);
    y += 10;

    doc.autoTable({
        startY: y,
        margin: { left: marginX, right: marginX },
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 10, cellPadding: 6 },
        headStyles: { fillColor: [27, 67, 50], textColor: 255, fontStyle: 'bold' },
        head: [['Month', 'Submitted', 'Resolved']],
        body: reportData.monthly.map(m => [m.label, String(m.submitted), String(m.resolved)]),
    });
    y = doc.lastAutoTable.finalY + 25;

    // ── Status breakdown ──
    if (y > 620) { doc.addPage(); y = 50; }
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(12);
    doc.setTextColor(27, 67, 50);
    doc.text('Status Breakdown', marginX, y);
    y += 10;

    doc.autoTable({
        startY: y,
        margin: { left: marginX, right: marginX },
        theme: 'grid',
        styles: { font: 'helvetica', fontSize: 10, cellPadding: 6 },
        headStyles: { fillColor: [27, 67, 50], textColor: 255, fontStyle: 'bold' },
        head: [['Status', 'Count', '% of Total']],
        body: reportData.statusBreakdown.map(s => [s.label, String(s.count), s.pct + '%']),
    });
    y = doc.lastAutoTable.finalY + 25;

    // ── Top complaint subjects ──
    if (reportData.topSubjects.length > 0) {
        if (y > 620) { doc.addPage(); y = 50; }
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(12);
        doc.setTextColor(27, 67, 50);
        doc.text('Most Common Complaint Types', marginX, y);
        y += 10;

        doc.autoTable({
            startY: y,
            margin: { left: marginX, right: marginX },
            theme: 'grid',
            styles: { font: 'helvetica', fontSize: 10, cellPadding: 6 },
            headStyles: { fillColor: [27, 67, 50], textColor: 255, fontStyle: 'bold' },
            head: [['Subject', 'Cases']],
            body: reportData.topSubjects.map(s => [s.subject, String(s.count)]),
        });
    }

    // ── Footer on every page ──
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        doc.setTextColor(148, 163, 184);
        doc.text('Barangay San Roque — Katarungang Pambarangay', marginX, 820);
        doc.text('Page ' + i + ' of ' + pageCount, pageWidth - marginX, 820, { align: 'right' });
    }

    doc.save('barangay-san-roque-dashboard-report-' + new Date().toISOString().slice(0,10) + '.pdf');
}
</script>
</body>
</html>
