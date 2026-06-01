<?php
require_once 'session_check_admin.php';
include 'db.php';

// ── All lupon members (always show all, regardless of assignments) ──
$lupon_result = $conn->query("SELECT * FROM lupon_members ORDER BY id ASC");
$lupon_list   = [];
while ($lm = $lupon_result->fetch_assoc()) $lupon_list[] = $lm;

// ── Check appointments table exists ──
$appt_exists = $conn->query("SHOW TABLES LIKE 'appointments'")->num_rows > 0;

// ── Helper: upcoming hearings for a lupon member ──
function get_hearings($conn, $lupon_id, $appt_exists) {
    if (!$appt_exists) return [];
    $res  = $conn->query("
        SELECT a.appointment_date, a.appointment_time,
               c.id AS complaint_id, c.subject,
               c.complainant_name, c.respondent_name, c.status
        FROM appointments a
        INNER JOIN complaints c ON c.id = a.complaint_id
        WHERE a.lupon_member_id = $lupon_id
          AND a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date ASC, a.appointment_time ASC
    ");
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    return $rows;
}

// ── Helper: past hearing count ──
function get_past_count($conn, $lupon_id, $appt_exists) {
    if (!$appt_exists) return 0;
    return (int)$conn->query("
        SELECT COUNT(*) as c FROM appointments
        WHERE lupon_member_id=$lupon_id AND appointment_date < CURDATE()
    ")->fetch_assoc()['c'];
}

// ── Helper: today count ──
function get_today_count($conn, $lupon_id, $appt_exists) {
    if (!$appt_exists) return 0;
    return (int)$conn->query("
        SELECT COUNT(*) as c FROM appointments
        WHERE lupon_member_id=$lupon_id AND appointment_date = CURDATE()
    ")->fetch_assoc()['c'];
}

// ── Overall stats ──
$total_lupon    = count($lupon_list);
$total_upcoming = $appt_exists ? (int)$conn->query("SELECT COUNT(DISTINCT complaint_id) as c FROM appointments WHERE appointment_date >= CURDATE()")->fetch_assoc()['c'] : 0;
$total_today    = $appt_exists ? (int)$conn->query("SELECT COUNT(DISTINCT complaint_id) as c FROM appointments WHERE appointment_date = CURDATE()")->fetch_assoc()['c']  : 0;
$total_done     = (int)$conn->query("SELECT COUNT(*) as c FROM complaints WHERE status='Completed'")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Committee - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f8fafc; }

        .page-header { margin-bottom:28px; }
        .page-header h1 { font-size:24px;color:#1a202c;font-weight:700;margin:0 0 4px; }
        .page-header p  { font-size:14px;color:#64748b;margin:0; }

        /* Stats bar */
        .stats-bar { display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:30px; }
        .sbar { background:#fff;border-radius:12px;padding:18px 20px;border:1px solid #edf2f7;display:flex;align-items:center;gap:14px; }
        .sbar-icon { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0; }
        .sbar-icon.green  { background:#f0fdf4; }
        .sbar-icon.blue   { background:#eff6ff; }
        .sbar-icon.amber  { background:#fffbeb; }
        .sbar-icon.purple { background:#f5f3ff; }
        .sbar-info span   { font-size:11px;color:#64748b;display:block;margin-bottom:1px; }
        .sbar-info strong { font-size:20px;font-weight:700;color:#1a202c; }

        /* Committee grid */
        .committee-grid { display:grid;grid-template-columns:1fr 1fr;gap:18px; }

        /* Member card */
        .member-card { background:#fff;border-radius:14px;border:1px solid #edf2f7;overflow:hidden;transition:box-shadow .2s; }
        .member-card:hover { box-shadow:0 4px 20px rgba(0,0,0,.07); }

        /* Clickable header */
        .member-header {
            display:flex;align-items:center;gap:14px;
            padding:16px 20px;cursor:pointer;
            transition:background .15s;user-select:none;
        }
        .member-header:hover { background:#fafbfc; }
        .member-header.expanded { border-bottom:1px solid #f1f5f9; }

        /* Avatar */
        .member-avatar {
            width:42px;height:42px;border-radius:50%;
            background:linear-gradient(135deg,#1B4332,#2D6A4F);
            display:flex;align-items:center;justify-content:center;
            font-size:15px;font-weight:700;color:#fff;flex-shrink:0;
        }

        .member-info { flex:1;min-width:0; }
        .member-name { font-size:14px;font-weight:600;color:#1a202c;margin-bottom:2px; }
        .member-pos  { font-size:12px;color:#64748b; }

        .badge-row { display:flex;align-items:center;gap:6px;margin-left:auto;flex-shrink:0; }
        .today-badge    { font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px;background:#fef3c7;color:#92400e;white-space:nowrap; }
        .upcoming-badge { font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px;background:#dbeafe;color:#1e40af;white-space:nowrap; }
        .free-badge     { font-size:10px;font-weight:600;padding:3px 9px;border-radius:99px;background:#f1f5f9;color:#94a3b8;white-space:nowrap; }

        /* Chevron */
        .chevron { width:20px;height:20px;color:#94a3b8;flex-shrink:0;transition:transform .25s; }
        .member-header.expanded .chevron { transform:rotate(180deg); }

        /* Expandable body */
        .member-body { display:none; }
        .member-body.open { display:block; }

        /* Hearing row */
        .hearing-row {
            display:grid;grid-template-columns:110px 1fr auto;
            align-items:center;gap:16px;
            padding:14px 20px;border-bottom:1px solid #f8fafc;
            transition:background .12s;
        }
        .hearing-row:last-child { border-bottom:none; }
        .hearing-row:hover { background:#fafbfc; }

        /* Date block */
        .hearing-date-block { text-align:center;background:#f8fafc;border-radius:10px;padding:8px 6px;border:1px solid #edf2f7; }
        .hdb-month { font-size:10px;font-weight:700;color:#1B4332;text-transform:uppercase;letter-spacing:.05em; }
        .hdb-day   { font-size:22px;font-weight:700;color:#1a202c;line-height:1.1; }
        .hdb-year  { font-size:10px;color:#94a3b8; }
        .hdb-today { background:#f0fdf4;border-color:#86efac; }
        .hdb-today .hdb-month { color:#059669; }
        .hdb-today .hdb-day   { color:#065f46; }

        /* Hearing details */
        .hd-case    { font-size:11px;font-weight:700;color:#1B4332;text-transform:uppercase;letter-spacing:.04em;margin-bottom:3px; }
        .hd-subject { font-size:13px;font-weight:600;color:#1a202c;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px; }
        .hd-parties { font-size:11px;color:#64748b; }
        .hd-time    { font-size:12px;color:#374151;font-weight:600;margin-top:4px; }
        .h-view-link{ font-size:12px;font-weight:600;color:#1B4332;text-decoration:none;display:block;margin-top:3px; }
        .h-view-link:hover { text-decoration:underline; }

        /* Status pill */
        .h-status { padding:4px 10px;border-radius:99px;font-size:10px;font-weight:700;white-space:nowrap; }
        .h-status.in-process   { background:#dbeafe;color:#1e40af; }
        .h-status.approved     { background:#d1fae5;color:#065f46; }
        .h-status.pending      { background:#fef3c7;color:#92400e; }
        .h-status.rescheduled  { background:#f5f3ff;color:#5b21b6; }
        .h-status.completed    { background:#e0e7ff;color:#3730a3; }

        /* Section labels */
        .today-separator    { font-size:10px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.06em;padding:8px 20px 4px;background:#f0fdf4;border-bottom:1px solid #bbf7d0; }
        .upcoming-separator { font-size:10px;font-weight:700;color:#1e40af;text-transform:uppercase;letter-spacing:.06em;padding:8px 20px 4px;background:#eff6ff;border-bottom:1px solid #bfdbfe; }

        /* Empty state inside card */
        .member-empty { padding:20px;text-align:center;color:#94a3b8;font-size:13px; }
        .member-empty span { display:block;font-size:22px;margin-bottom:6px; }

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
        .n-body strong{display:block;font-size:13px;color:#1a202c;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px;}
        .n-body p{font-size:12px;color:#64748b;margin:0 0 3px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
        .n-body time{font-size:11px;color:#94a3b8;}
        .n-empty{text-align:center;padding:50px 20px;color:#94a3b8;}
        .n-empty img{width:40px;opacity:.28;display:block;margin:0 auto 10px;}
    </style>
</head>
<body class="admin-dashboard-layout">

    <nav class="sidebar">
        <div class="sidebar-top">
            <a href="admin-dashboard.php"          class="nav-item" title="Dashboard"><img src="dashboard.png"></a>
            <a href="admin-lupon-assignments.php"   class="nav-item" title="Lupon Assignments"><img src="people.png"></a>
            <a href="admin-committee.php"           class="nav-item active" title="Committee"><img src="file.png"></a>
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

    <main class="portal-content">

        <div class="page-header">
            <h1>Lupon Tagapamayapa Committee</h1>
            <p>All committee members and their upcoming hearing assignments.</p>
        </div>

        <!-- Stats bar -->
        <div class="stats-bar">
            <div class="sbar">
                <div class="sbar-icon green">👥</div>
                <div class="sbar-info"><span>Committee Members</span><strong><?php echo $total_lupon; ?></strong></div>
            </div>
            <div class="sbar">
                <div class="sbar-icon amber">📅</div>
                <div class="sbar-info"><span>Today's Hearings</span><strong><?php echo $total_today; ?></strong></div>
            </div>
            <div class="sbar">
                <div class="sbar-icon blue">🗓️</div>
                <div class="sbar-info"><span>Upcoming Hearings</span><strong><?php echo $total_upcoming; ?></strong></div>
            </div>
            <div class="sbar">
                <div class="sbar-icon purple">✅</div>
                <div class="sbar-info"><span>Completed Cases</span><strong><?php echo $total_done; ?></strong></div>
            </div>
        </div>

        <!-- Committee grid — ALL lupon always shown -->
        <div class="committee-grid">
        <?php foreach ($lupon_list as $lm):
            $hearings    = get_hearings($conn, $lm['id'], $appt_exists);
            $today_count = get_today_count($conn, $lm['id'], $appt_exists);
            $past_count  = get_past_count($conn, $lm['id'], $appt_exists);
            $upcoming    = count($hearings);
            $words       = explode(' ', $lm['name']);
            $initials    = strtoupper(($words[0][0] ?? '') . ($words[1][0] ?? ''));
            $today_h     = array_filter($hearings, fn($h) => $h['appointment_date'] === date('Y-m-d'));
            $future_h    = array_filter($hearings, fn($h) => $h['appointment_date'] >  date('Y-m-d'));
        ?>
        <div class="member-card" id="mcard_<?php echo $lm['id']; ?>">

            <div class="member-header" id="mheader_<?php echo $lm['id']; ?>"
                 onclick="toggleMember(<?php echo $lm['id']; ?>)">

                <div class="member-avatar"><?php echo htmlspecialchars($initials); ?></div>

                <div class="member-info">
                    <div class="member-name"><?php echo htmlspecialchars($lm['name']); ?></div>
                    <div class="member-pos">
                        <?php echo htmlspecialchars($lm['position']); ?>
                        · <span style="color:#94a3b8;"><?php echo $past_count; ?> past</span>
                    </div>
                </div>

                <div class="badge-row">
                    <?php if ($today_count > 0): ?>
                        <span class="today-badge">📅 Today (<?php echo $today_count; ?>)</span>
                    <?php endif; ?>
                    <?php if ($upcoming > 0): ?>
                        <span class="upcoming-badge"><?php echo $upcoming; ?> upcoming</span>
                    <?php else: ?>
                        <span class="free-badge">No schedule</span>
                    <?php endif; ?>
                </div>

                <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M6 9l6 6 6-6"/>
                </svg>
            </div>

            <div class="member-body" id="mbody_<?php echo $lm['id']; ?>">
                <?php if (empty($hearings)): ?>
                    <div class="member-empty">
                        <span>📭</span>
                        No upcoming hearings assigned yet.
                    </div>
                <?php else: ?>
                    <?php if (!empty($today_h)): ?>
                    <div class="today-separator">📅 Today — <?php echo date('F j, Y'); ?></div>
                    <?php foreach ($today_h as $h): ?>
                        <?php echo renderRow($h, true); ?>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($future_h)): ?>
                    <?php if (!empty($today_h)): ?>
                    <div class="upcoming-separator">🗓️ Upcoming</div>
                    <?php endif; ?>
                    <?php foreach ($future_h as $h): ?>
                        <?php echo renderRow($h, false); ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

    </main>

    <script>
        function toggleMember(id) {
            const header = document.getElementById('mheader_' + id);
            const body   = document.getElementById('mbody_'   + id);
            body.classList.toggle('open');
            header.classList.toggle('expanded');
        }

        // Auto-expand members with today's hearings
        document.querySelectorAll('.today-badge').forEach(badge => {
            const card = badge.closest('.member-card');
            const id   = card.id.replace('mcard_', '');
            document.getElementById('mbody_'   + id).classList.add('open');
            document.getElementById('mheader_' + id).classList.add('expanded');
        });

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
<?php
function renderRow($h, $is_today) {
    $d    = new DateTime($h['appointment_date']);
    $tc   = $is_today ? 'hdb-today' : '';
    $sc   = strtolower(str_replace([' ','_'], '-', $h['status']));
    $cnum = 'BRGY-2026-0' . $h['complaint_id'];
    ob_start(); ?>
    <div class="hearing-row">
        <div class="hearing-date-block <?php echo $tc; ?>">
            <div class="hdb-month"><?php echo $d->format('M'); ?></div>
            <div class="hdb-day"><?php echo $d->format('j'); ?></div>
            <div class="hdb-year"><?php echo $d->format('Y'); ?></div>
        </div>
        <div>
            <div class="hd-case"><?php echo $cnum; ?></div>
            <div class="hd-subject" title="<?php echo htmlspecialchars($h['subject']); ?>"><?php echo htmlspecialchars($h['subject']); ?></div>
            <div class="hd-parties"><?php echo htmlspecialchars($h['complainant_name']); ?> vs. <?php echo htmlspecialchars($h['respondent_name']); ?></div>
            <div class="hd-time">🕐 <?php echo htmlspecialchars($h['appointment_time']); ?></div>
            <a class="h-view-link" href="admin-view-complaint.php?id=<?php echo (int)$h['complaint_id']; ?>">View case →</a>
        </div>
        <span class="h-status <?php echo $sc; ?>"><?php echo htmlspecialchars($h['status']); ?></span>
    </div>
    <?php return ob_get_clean();
}
?>
