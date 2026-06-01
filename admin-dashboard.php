<?php
require_once 'session_check_admin.php';
include 'db.php';

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
    <title>Admin Dashboard - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f8fafc; }

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
            padding:14px 0;border-bottom:1px solid #f8fafc;
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
        .notif-dropdown.open{left:65px;}
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
    </style>
</head>
<body class="admin-dashboard-layout">

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-top">
            <a href="admin-dashboard.php"          class="nav-item active" title="Dashboard"><img src="dashboard.png"></a>
            <a href="admin-lupon-assignments.php"   class="nav-item" title="Lupon Assignments"><img src="people.png"></a>
            <a href="admin-committee.php"           class="nav-item" title="Committee"><img src="file.png"></a>
            <a href="admin-calendar.php"            class="nav-item" title="Calendar"><img src="clock.png"></a>
            <a href="admin-file-maintenance.php"    class="nav-item" title="File Maintenance"><img src="dashboard.png"></a>
            <a href="admin-audit-trail.php"          class="nav-item" title="Audit Trail"><img src="file.png"></a>
            <div class="nav-item notif-wrapper" id="bellBtn">
                <img src="bell.png" id="bellIcon">
                <span class="notif-badge" id="notifBadge"></span>
            </div>
        </div>
        <div class="sidebar-bottom">
            <a href="logout.php" class="nav-item" title="Logout"><img src="logout.png"></a>
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

    <main class="portal-content" style="padding:30px 36px;">

        <header style="margin-bottom:24px;">
            <h1 style="font-size:22px;font-weight:700;color:#1a202c;margin:0 0 4px;">Dashboard</h1>
            <p style="font-size:13px;color:#64748b;margin:0;">Overview of all community complaints — <?php echo date('F j, Y'); ?></p>
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
                <span style="font-size:12px;color:#94a3b8;"><?php echo $total; ?> total</span>
            </div>
            <?php
            $complaints->data_seek(0);
            while ($row = $complaints->fetch_assoc()):
                $sc = strtolower(str_replace([' ','_'], '-', $row['status']));
            ?>
            <div class="complaint-row">
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
                <div>
                    <a href="admin-view-complaint.php?id=<?php echo $row['id']; ?>" class="view-btn">View details</a>
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

        // Notification bell
        const bellBtn=document.getElementById('bellBtn'),dropdown=document.getElementById('notifDropdown'),overlay=document.getElementById('notifOverlay'),badge=document.getElementById('notifBadge'),nList=document.getElementById('nList'),nLabel=document.getElementById('nLabel'),markBtn=document.getElementById('markReadBtn');
        let bellOpen=false;
        function taAgo(d){const s=Math.floor((new Date()-new Date(d))/1000);if(s<60)return'just now';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';}
        function escN(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
        function renderBell({notifications,unread_count}){
            if(unread_count>0){badge.textContent=unread_count>99?'99+':unread_count;badge.classList.add('has-notif');}
            else badge.classList.remove('has-notif');
            nLabel.textContent=unread_count>0?`${unread_count} unread`:'All caught up!';
            nList.innerHTML=notifications.length?notifications.map(n=>`<a class="n-item ${n.is_read==0?'unread':''}" href="admin-view-complaint.php?id=${n.complaint_id}"><div class="n-dot"><img src="file.png"></div><div class="n-body"><strong>${escN(n.subject)}</strong><p>${escN(n.message)}</p><time>${taAgo(n.created_at)}</time></div></a>`).join(''):`<div class="n-empty"><img src="bell.png"><p>No notifications.</p></div>`;
        }
        function fetchBell(){fetch('get_notifications.php?action=fetch').then(r=>r.json()).then(renderBell).catch(()=>{});}
        bellBtn.addEventListener('click',()=>{if(bellOpen){dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;}else{dropdown.classList.add('open');overlay.classList.add('show');bellOpen=true;}});
        overlay.addEventListener('click',()=>{dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;});
        markBtn.addEventListener('click',()=>fetch('get_notifications.php?action=mark_read').then(r=>r.json()).then(()=>fetchBell()));
        fetchBell();setInterval(fetchBell,15000);
    </script>
</body>
</html>
