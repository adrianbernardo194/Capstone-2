<?php
require_once 'session_check_admin.php';
include 'db.php';

// ── Filters ───────────────────────────────────────────────────────────────────
$filter_module = $_GET['module']    ?? '';
$filter_action = $_GET['action']    ?? '';
$filter_admin  = $_GET['admin']     ?? '';
$filter_date   = $_GET['date']      ?? '';
$filter_search = $_GET['search']    ?? '';
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 20;
$offset        = ($page - 1) * $per_page;

// ── Build WHERE clause ────────────────────────────────────────────────────────
$where = ['1=1'];
if ($filter_module) $where[] = "module = '" . mysqli_real_escape_string($conn, $filter_module) . "'";
if ($filter_action) $where[] = "action = '" . mysqli_real_escape_string($conn, $filter_action) . "'";
if ($filter_admin)  $where[] = "admin_username LIKE '%" . mysqli_real_escape_string($conn, $filter_admin) . "%'";
if ($filter_date)   $where[] = "DATE(created_at) = '" . mysqli_real_escape_string($conn, $filter_date) . "'";
if ($filter_search) $where[] = "(description LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%'
                                  OR target_label LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%'
                                  OR admin_username LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%')";
$where_sql = implode(' AND ', $where);

// ── Fetch total count ─────────────────────────────────────────────────────────
$total_count = (int)$conn->query("SELECT COUNT(*) as c FROM audit_trail WHERE $where_sql")->fetch_assoc()['c'];
$total_pages = max(1, ceil($total_count / $per_page));

// ── Fetch records ─────────────────────────────────────────────────────────────
$logs_res = $conn->query("SELECT * FROM audit_trail WHERE $where_sql ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
$logs = [];
while ($r = $logs_res->fetch_assoc()) $logs[] = $r;

// ── Distinct modules/actions for filter dropdowns ────────────────────────────
$modules_res = $conn->query("SELECT DISTINCT module FROM audit_trail ORDER BY module");
$modules = [];
while ($r = $modules_res->fetch_assoc()) $modules[] = $r['module'];

$actions_res = $conn->query("SELECT DISTINCT action FROM audit_trail ORDER BY action");
$actions = [];
while ($r = $actions_res->fetch_assoc()) $actions[] = $r['action'];

// ── Stats ─────────────────────────────────────────────────────────────────────
$today_count  = (int)$conn->query("SELECT COUNT(*) as c FROM audit_trail WHERE DATE(created_at)=CURDATE()")->fetch_assoc()['c'];
$total_all    = (int)$conn->query("SELECT COUNT(*) as c FROM audit_trail")->fetch_assoc()['c'];
$del_count    = (int)$conn->query("SELECT COUNT(*) as c FROM audit_trail WHERE action LIKE '%DELETE%'")->fetch_assoc()['c'];
$login_count  = (int)$conn->query("SELECT COUNT(*) as c FROM audit_trail WHERE action='LOGIN'")->fetch_assoc()['c'];

// ── Action config (colors + icons) ───────────────────────────────────────────
function action_style($action) {
    $map = [
        'LOGIN'            => ['bg'=>'#f0fdf4','color'=>'#065f46','icon'=>'🔐'],
        'LOGOUT'           => ['bg'=>'#f8fafc','color'=>'#64748b','icon'=>'🚪'],
        'STATUS_UPDATE'    => ['bg'=>'#eff6ff','color'=>'#1e40af','icon'=>'✏️'],
        'LUPON_ASSIGN'     => ['bg'=>'#f5f3ff','color'=>'#5b21b6','icon'=>'👥'],
        'RESCHEDULE'       => ['bg'=>'#fef3c7','color'=>'#92400e','icon'=>'📅'],
        'HOLIDAY_ADD'      => ['bg'=>'#fff7ed','color'=>'#9a3412','icon'=>'🚫'],
        'HOLIDAY_DELETE'   => ['bg'=>'#fef2f2','color'=>'#991b1b','icon'=>'🗑️'],
        'WINDOW_UPDATE'    => ['bg'=>'#eff6ff','color'=>'#1e40af','icon'=>'⏱️'],
        'PAPER_CREATE'     => ['bg'=>'#fef3c7','color'=>'#92400e','icon'=>'📄'],
        'PAPER_UPDATE'     => ['bg'=>'#eff6ff','color'=>'#1e40af','icon'=>'📝'],
        'PAPER_DELETE'     => ['bg'=>'#fef2f2','color'=>'#991b1b','icon'=>'🗑️'],
        'LUPON_ADD'        => ['bg'=>'#f0fdf4','color'=>'#065f46','icon'=>'➕'],
        'LUPON_EDIT'       => ['bg'=>'#eff6ff','color'=>'#1e40af','icon'=>'✏️'],
        'LUPON_DEACTIVATE' => ['bg'=>'#fff7ed','color'=>'#9a3412','icon'=>'🔒'],
    ];
    return $map[$action] ?? ['bg'=>'#f8fafc','color'=>'#374151','icon'=>'📋'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f8fafc; }

        .page-header { margin-bottom:22px; }
        .page-header h1 { font-size:22px;font-weight:700;color:#1a202c;margin:0 0 4px; }
        .page-header p  { font-size:13px;color:#64748b;margin:0; }

        /* Stats */
        .at-stats { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px; }
        .at-stat  { background:#fff;border-radius:12px;padding:16px 18px;border:1px solid #edf2f7;display:flex;align-items:center;gap:12px; }
        .at-stat-icon { width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0; }
        .at-stat-info span   { font-size:11px;color:#64748b;display:block;margin-bottom:2px; }
        .at-stat-info strong { font-size:20px;font-weight:700;color:#1a202c; }

        /* Filter bar */
        .filter-bar {
            background:#fff;border-radius:12px;border:1px solid #edf2f7;
            padding:16px 20px;margin-bottom:18px;
            display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;
        }
        .filter-group { display:flex;flex-direction:column;gap:4px;min-width:130px; }
        .filter-group label { font-size:11px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.04em; }
        .filter-input {
            padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;
            font-size:12px;font-family:inherit;background:#f8fafc;
            color:var(--color-text-primary);
        }
        .filter-input:focus { outline:none;border-color:#1B4332; }
        .filter-search { flex:1;min-width:200px; }
        .btn-filter {
            padding:8px 18px;background:#1B4332;color:#fff;border:none;
            border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;
            font-family:inherit;align-self:flex-end;
        }
        .btn-reset {
            padding:8px 14px;border:1px solid #e2e8f0;background:#fff;
            border-radius:7px;font-size:12px;cursor:pointer;font-family:inherit;
            align-self:flex-end;color:#374151;
        }
        .btn-export {
            padding:8px 14px;border:1px solid #e2e8f0;background:#fff;
            border-radius:7px;font-size:12px;cursor:pointer;font-family:inherit;
            align-self:flex-end;color:#374151;display:flex;align-items:center;gap:5px;
        }

        /* Table card */
        .at-card { background:#fff;border-radius:14px;border:1px solid #edf2f7;overflow:hidden; }
        .at-card-header {
            display:flex;align-items:center;justify-content:space-between;
            padding:16px 20px;border-bottom:1px solid #f1f5f9;
        }
        .at-card-header h3 { font-size:14px;font-weight:600;color:#1a202c;margin:0; }
        .at-card-header span { font-size:12px;color:#94a3b8; }

        /* Log table */
        .at-table { width:100%;border-collapse:collapse;table-layout:fixed; }
        .at-table thead th {
            font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;
            letter-spacing:.04em;padding:10px 14px;text-align:left;
            border-bottom:1px solid #f1f5f9;background:#fafbfc;
        }
        .at-table tbody td {
            font-size:12px;color:#374151;padding:12px 14px;
            border-bottom:1px solid #f8fafc;vertical-align:top;
        }
        .at-table tbody tr:last-child td { border-bottom:none; }
        .at-table tbody tr:hover td    { background:#fafbfc; }

        /* Action badge */
        .action-badge {
            display:inline-flex;align-items:center;gap:4px;
            padding:3px 10px;border-radius:99px;
            font-size:10px;font-weight:700;white-space:nowrap;
        }

        /* Module tag */
        .module-tag {
            display:inline-block;padding:2px 8px;border-radius:4px;
            font-size:10px;font-weight:600;background:#f1f5f9;color:#64748b;
        }

        /* Change columns */
        .change-block {
            font-size:11px;line-height:1.5;
        }
        .change-old { color:#dc2626; }
        .change-new { color:#059669; }
        .change-arrow { color:#94a3b8;margin:0 3px; }

        /* Description */
        .desc-text { font-size:12px;color:#374151;line-height:1.5; }
        .target-label { font-size:11px;color:#94a3b8;margin-top:2px; }

        /* Admin cell */
        .admin-cell { display:flex;align-items:center;gap:7px; }
        .admin-avatar {
            width:26px;height:26px;border-radius:50%;
            background:linear-gradient(135deg,#1B4332,#2D6A4F);
            display:flex;align-items:center;justify-content:center;
            font-size:10px;font-weight:700;color:#fff;flex-shrink:0;
        }
        .admin-name { font-size:12px;font-weight:500;color:#1a202c; }

        /* Timestamp */
        .ts-date { font-size:12px;font-weight:500;color:#374151; }
        .ts-time { font-size:11px;color:#94a3b8; }

        /* IP */
        .ip-text { font-size:11px;color:#94a3b8;font-family:monospace; }

        /* Pagination */
        .pagination { display:flex;align-items:center;justify-content:center;gap:6px;padding:18px; }
        .pg-btn {
            padding:6px 12px;border:1px solid #e2e8f0;border-radius:7px;
            font-size:12px;cursor:pointer;background:#fff;color:#374151;
            text-decoration:none;font-family:inherit;transition:all .15s;
        }
        .pg-btn:hover { border-color:#1B4332;color:#1B4332; }
        .pg-btn.active { background:#1B4332;color:#fff;border-color:#1B4332; }
        .pg-btn.disabled { opacity:.4;cursor:not-allowed;pointer-events:none; }

        /* Empty */
        .at-empty { text-align:center;padding:50px 20px;color:#94a3b8; }
        .at-empty-icon { font-size:36px;margin-bottom:10px; }

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
            <a href="admin-dashboard.php"          class="nav-item" title="Dashboard"><img src="dashboard.png"></a>
            <a href="admin-lupon-assignments.php"   class="nav-item" title="Lupon Assignments"><img src="people.png"></a>
            <a href="admin-committee.php"           class="nav-item" title="Committee"><img src="file.png"></a>
            <a href="admin-calendar.php"            class="nav-item" title="Calendar"><img src="clock.png"></a>
            <a href="admin-file-maintenance.php"    class="nav-item" title="File Maintenance"><img src="dashboard.png"></a>
            <a href="admin-audit-trail.php"         class="nav-item active" title="Audit Trail"><img src="bell.png"></a>
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

    <main class="portal-content" style="padding:28px 36px;">

        <div class="page-header">
            <h1>🔍 Audit Trail</h1>
            <p>Complete record of all admin actions — who did what, when, and what changed.</p>
        </div>

        <!-- Stats -->
        <div class="at-stats">
            <div class="at-stat">
                <div class="at-stat-icon" style="background:#f0fdf4;">📋</div>
                <div class="at-stat-info"><span>Total actions logged</span><strong><?php echo number_format($total_all); ?></strong></div>
            </div>
            <div class="at-stat">
                <div class="at-stat-icon" style="background:#eff6ff;">📅</div>
                <div class="at-stat-info"><span>Actions today</span><strong><?php echo $today_count; ?></strong></div>
            </div>
            <div class="at-stat">
                <div class="at-stat-icon" style="background:#fef2f2;">🗑️</div>
                <div class="at-stat-info"><span>Deletions logged</span><strong><?php echo $del_count; ?></strong></div>
            </div>
            <div class="at-stat">
                <div class="at-stat-icon" style="background:#f0fdf4;">🔐</div>
                <div class="at-stat-info"><span>Total logins</span><strong><?php echo $login_count; ?></strong></div>
            </div>
        </div>

        <!-- Filter bar -->
        <form method="GET" action="">
            <div class="filter-bar">
                <div class="filter-group filter-search">
                    <label>Search</label>
                    <input type="text" name="search" class="filter-input"
                           placeholder="Search description, case, admin…"
                           value="<?php echo htmlspecialchars($filter_search); ?>">
                </div>
                <div class="filter-group">
                    <label>Module</label>
                    <select name="module" class="filter-input">
                        <option value="">All modules</option>
                        <?php foreach ($modules as $m): ?>
                        <option value="<?php echo htmlspecialchars($m); ?>"
                            <?php echo $filter_module === $m ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($m); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Action</label>
                    <select name="action" class="filter-input">
                        <option value="">All actions</option>
                        <?php foreach ($actions as $a): ?>
                        <option value="<?php echo htmlspecialchars($a); ?>"
                            <?php echo $filter_action === $a ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($a); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" class="filter-input"
                           value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="filter-group">
                    <label>Admin</label>
                    <input type="text" name="admin" class="filter-input"
                           placeholder="Username…"
                           value="<?php echo htmlspecialchars($filter_admin); ?>">
                </div>
                <button type="submit" class="btn-filter">Filter</button>
                <a href="admin-audit-trail.php" class="btn-reset">Reset</a>
                <button type="button" class="btn-export" onclick="exportCSV()">
                    ⬇️ Export CSV
                </button>
            </div>
        </form>

        <!-- Table -->
        <div class="at-card">
            <div class="at-card-header">
                <h3>Activity Log</h3>
                <span>
                    <?php echo number_format($total_count); ?> record<?php echo $total_count !== 1 ? 's' : ''; ?>
                    <?php if ($filter_module || $filter_action || $filter_date || $filter_search || $filter_admin): ?>
                    — filtered
                    <?php endif; ?>
                </span>
            </div>

            <?php if (empty($logs)): ?>
            <div class="at-empty">
                <div class="at-empty-icon">📭</div>
                <p>No audit records found<?php echo ($filter_module || $filter_action || $filter_date || $filter_search) ? ' matching your filters' : ' yet'; ?>.</p>
            </div>
            <?php else: ?>
            <table class="at-table" id="auditTable">
                <thead>
                    <tr>
                        <th style="width:130px">Timestamp</th>
                        <th style="width:110px">Admin</th>
                        <th style="width:130px">Action</th>
                        <th style="width:100px">Module</th>
                        <th>Description</th>
                        <th style="width:160px">Change</th>
                        <th style="width:90px">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log):
                    $style = action_style($log['action']);
                    $dt    = new DateTime($log['created_at']);
                    $init  = strtoupper(substr($log['admin_username'] ?? 'S', 0, 1));
                ?>
                <tr>
                    <!-- Timestamp -->
                    <td>
                        <div class="ts-date"><?php echo $dt->format('M j, Y'); ?></div>
                        <div class="ts-time"><?php echo $dt->format('g:i:s A'); ?></div>
                    </td>

                    <!-- Admin -->
                    <td>
                        <div class="admin-cell">
                            <div class="admin-avatar"><?php echo htmlspecialchars($init); ?></div>
                            <span class="admin-name"><?php echo htmlspecialchars($log['admin_username'] ?? 'System'); ?></span>
                        </div>
                    </td>

                    <!-- Action badge -->
                    <td>
                        <span class="action-badge"
                              style="background:<?php echo $style['bg']; ?>;color:<?php echo $style['color']; ?>;">
                            <?php echo $style['icon']; ?> <?php echo htmlspecialchars($log['action']); ?>
                        </span>
                    </td>

                    <!-- Module -->
                    <td><span class="module-tag"><?php echo htmlspecialchars($log['module']); ?></span></td>

                    <!-- Description -->
                    <td>
                        <div class="desc-text"><?php echo htmlspecialchars($log['description']); ?></div>
                        <?php if ($log['target_label']): ?>
                        <div class="target-label">📌 <?php echo htmlspecialchars($log['target_label']); ?></div>
                        <?php endif; ?>
                    </td>

                    <!-- Change (old → new) -->
                    <td>
                        <?php if ($log['old_value'] || $log['new_value']): ?>
                        <div class="change-block">
                            <?php if ($log['old_value']): ?>
                            <div class="change-old">− <?php echo htmlspecialchars($log['old_value']); ?></div>
                            <?php endif; ?>
                            <?php if ($log['old_value'] && $log['new_value']): ?>
                            <div style="color:#94a3b8;font-size:10px;">↓</div>
                            <?php endif; ?>
                            <?php if ($log['new_value']): ?>
                            <div class="change-new">+ <?php echo htmlspecialchars($log['new_value']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="color:#94a3b8;font-size:11px;">—</span>
                        <?php endif; ?>
                    </td>

                    <!-- IP -->
                    <td><span class="ip-text"><?php echo htmlspecialchars($log['ip_address'] ?? '—'); ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>"
                   class="pg-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">← Prev</a>

                <?php
                $start = max(1, $page - 2);
                $end   = min($total_pages, $page + 2);
                for ($i = $start; $i <= $end; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                   class="pg-btn <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>

                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>"
                   class="pg-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">Next →</a>
            </div>
            <?php endif; ?>

            <?php endif; ?>
        </div>

    </main>

    <script>
        // ── CSV Export ──
        function exportCSV() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.location.href = 'export_audit.php?' + params.toString();
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
