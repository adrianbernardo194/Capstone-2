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

$resident_id = (int)($_GET['resident_id'] ?? 0);
$resident = null;
$not_found = true;

if ($resident_id > 0) {
    $stmt = $conn->prepare("SELECT id, full_name, email, address, id_type, id_photo, id_verification_status FROM residents WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $resident = $result->fetch_assoc();
    $stmt->close();
    $not_found = !$resident;
}

// Mark the related notification(s) as read once the admin opens this page
if (!$not_found) {
    $conn->query("UPDATE notifications SET is_read = 1 WHERE type = 'id_verification' AND resident_id = " . (int)$resident_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review ID Verification - Barangay San Roque</title>
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

        * { box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        html, body { margin: 0; padding: 0; }

        body {
            background:#f8fafc;
            opacity: 0;
            animation: pageFadeIn .4s ease forwards;
        }
        @keyframes pageFadeIn { from { opacity: 0; } to { opacity: 1; } }
        body.page-exit { animation: pageFadeOut .28s ease forwards; }
        @keyframes pageFadeOut { from { opacity: 1; } to { opacity: 0; } }

        /* =============================================
           TOP BAR + SIDEBAR RAIL — matches admin-dashboard.php
           ============================================= */

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
            padding: 16px 0;
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
            display: flex; flex-direction: column; align-items: center;
            gap: 6px; width: 100%; flex-shrink: 0;
        }

        .nav-item {
            width: 46px; height: 46px; display: flex; justify-content: center; align-items: center;
            cursor: pointer; transition: background 0.2s, opacity 0.2s; opacity: 0.65;
            border-radius: 12px; text-decoration: none; flex-shrink: 0; margin-bottom: 4px;
        }
        .nav-item.active, .nav-item:hover { opacity: 1; background-color: rgba(255,255,255,0.14); }
        .nav-item img { width: 22px; height: 22px; filter: brightness(0) invert(1); }

        @media screen and (max-height: 700px) {
            .nav-item { width: 36px; height: 36px; }
            .nav-item img { width: 17px; height: 17px; }
            .sidebar-top, .sidebar-bottom { gap: 4px; }
            .sidebar { padding: 10px 0; }
        }

        .sidebar-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255,255,255,0.14); border: 1.5px solid rgba(255,255,255,0.35);
            color: #fff; font-size: 0.72rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center; margin-bottom: 10px; flex-shrink: 0;
        }

        .sidebar-close-btn {
            display: none; position: absolute; top: 14px; right: 14px;
            background: none; border: none; color: #fff; opacity: 0.75;
            width: 32px; height: 32px; align-items: center; justify-content: center;
            cursor: pointer; border-radius: 8px;
        }
        .sidebar-close-btn:hover { opacity: 1; background: rgba(255,255,255,0.14); }
        .sidebar-close-btn svg { width: 18px; height: 18px; }

        .app-topbar {
            position: fixed; top: 0; left: 76px; width: calc(100% - 76px); height: 64px;
            background: #fff; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 28px; z-index: 200;
        }
        .topbar-left { display: flex; align-items: center; gap: 16px; }
        .hamburger-btn {
            display: none; background: none; border: none; color: var(--green-900);
            width: 34px; height: 34px; align-items: center; justify-content: center;
            cursor: pointer; border-radius: 8px; flex-shrink: 0;
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

        .portal-content { margin-left: 76px; width: 100%; min-height: 100vh; }

        @media screen and (max-width: 992px) {
            .sidebar { transform: translateX(-100%); box-shadow: 6px 0 24px rgba(0,0,0,0.18); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-close-btn { display: flex; }
            .hamburger-btn { display: flex; }
            .app-topbar { left: 0; width: 100%; padding: 0 16px; }
            .portal-content { margin-left: 0; width: 100%; }
        }
        @media screen and (max-width: 768px) {
            .topbar-eyebrow { display: none; }
            .topbar-title { font-size: 0.95rem; }
            .review-container { padding: 20px !important; }
        }

        /* =============================================
           REVIEW CARD
           ============================================= */

        .back-link {
            display: inline-flex; align-items: center; gap: 6px;
            color: var(--green-700); font-size: 0.9rem; font-weight: 600;
            text-decoration: none; margin-bottom: 20px;
        }
        .back-link:hover { color: var(--green-900); }

        .review-container {
            max-width: 720px; margin: 0 auto; padding: 30px;
        }

        .review-card {
            background: #fff; border-radius: 16px; border: 1px solid #edf2f7;
            padding: 32px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);
        }

        .review-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .review-header h1 { font-size: 1.3rem; font-weight: 700; color: var(--green-900); margin: 0; }

        .status-pill { display: inline-block; padding: 5px 14px; border-radius: 99px; font-size: 0.75rem; font-weight: 700; }
        .status-pill.pending    { background: #fef3c7; color: #92400e; }
        .status-pill.verified   { background: #d1fae5; color: #065f46; }
        .status-pill.rejected   { background: #fee2e2; color: #991b1b; }
        .status-pill.unverified { background: #f1f5f9; color: #475569; }

        .resident-info { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 24px; margin-bottom: 28px; }
        .info-item label { display: block; font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
        .info-item strong { font-size: 0.92rem; color: #1a202c; }

        .id-photo-wrap {
            background: #f8fafc; border: 1px solid #edf2f7; border-radius: 12px;
            padding: 16px; text-align: center; margin-bottom: 28px;
        }
        .id-photo-wrap img { max-width: 100%; max-height: 420px; border-radius: 8px; }
        .id-photo-wrap .no-photo { padding: 60px 20px; color: #94a3b8; font-size: 0.9rem; }

        .review-actions { display: flex; gap: 12px; }
        .approve-btn, .reject-btn {
            flex: 1; padding: 13px; border-radius: 10px; font-weight: 600; font-size: 0.92rem;
            cursor: pointer; border: none; transition: transform 0.15s, background 0.2s;
        }
        .approve-btn { background: #1B4332; color: #fff; }
        .approve-btn:hover { background: #2D6A4F; transform: translateY(-1px); }
        .reject-btn { background: #fff; color: #dc2626; border: 2px solid #fecaca; }
        .reject-btn:hover { background: #fef2f2; }
        .approve-btn:disabled, .reject-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

        .already-decided-note {
            text-align: center; padding: 16px; border-radius: 10px; font-size: 0.85rem;
            background: #f8fafc; color: #64748b; margin-bottom: 4px;
        }

        .action-alert {
            display: none; font-size: 0.85rem; padding: 12px 16px; border-radius: 10px; margin-bottom: 18px;
        }
        .action-alert.show { display: block; }
        .action-alert.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .action-alert.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }

        .not-found-box {
            max-width: 480px; margin: 60px auto; text-align: center;
            background: #fff; border-radius: 16px; border: 1px solid #edf2f7; padding: 40px 32px;
        }
        .not-found-box h2 { color: var(--green-900); margin-bottom: 8px; }
        .not-found-box p { color: var(--muted); font-size: 0.9rem; margin-bottom: 20px; }
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
                <span class="topbar-title">Review ID Verification</span>
            </div>
        </div>
        <div class="topbar-right">
            <div class="topbar-avatar" title="<?php echo htmlspecialchars($admin_display_name); ?>">
                <?php echo htmlspecialchars($admin_initials); ?>
            </div>
        </div>
    </header>

    <!-- Sidebar drawer backdrop (mobile only) -->
    <div id="sidebarDrawerOverlay" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.25);"></div>

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
    <div class="notif-overlay" id="notifOverlay" style="display:none;position:fixed;inset:0;z-index:9000;"></div>
    <div class="notif-dropdown" id="notifDropdown" style="position:fixed;top:0;left:-420px;width:360px;height:100vh;background:#fff;border-radius:0 16px 16px 0;box-shadow:6px 0 30px rgba(0,0,0,.15);z-index:9999;display:flex;flex-direction:column;transition:left .32s cubic-bezier(0.4,0,0.2,1);overflow:hidden;">
        <div style="background:#004d2c;color:#fff;padding:20px 22px 16px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
            <div><h3 style="font-size:15px;font-weight:600;margin:0 0 3px;">Notifications</h3><small id="nLabel" style="font-size:11px;opacity:.75;display:block;">Loading…</small></div>
            <button id="markReadBtn" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;">Mark all read</button>
        </div>
        <div id="nList" style="overflow-y:auto;flex:1;">
            <div style="text-align:center;padding:50px 20px;color:#94a3b8;"><img src="bell.png" style="width:40px;opacity:.28;display:block;margin:0 auto 10px;"><p>No notifications yet.</p></div>
        </div>
    </div>

    <main class="portal-content">
        <div class="review-container">
            <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>

            <?php if ($not_found): ?>
                <div class="not-found-box">
                    <h2>Resident Not Found</h2>
                    <p>This resident record doesn't exist or may have been removed.</p>
                    <a href="admin-dashboard.php" class="back-link">← Back to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="review-card">
                    <div class="review-header">
                        <h1>ID Verification Review</h1>
                        <span class="status-pill <?php echo htmlspecialchars($resident['id_verification_status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($resident['id_verification_status'])); ?>
                        </span>
                    </div>

                    <div class="action-alert" id="actionAlert"></div>

                    <div class="resident-info">
                        <div class="info-item">
                            <label>Full Name</label>
                            <strong><?php echo htmlspecialchars($resident['full_name']); ?></strong>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <strong><?php echo htmlspecialchars($resident['email']); ?></strong>
                        </div>
                        <div class="info-item">
                            <label>Address</label>
                            <strong><?php echo htmlspecialchars($resident['address'] ?? '—'); ?></strong>
                        </div>
                        <div class="info-item">
                            <label>ID Type Submitted</label>
                            <strong><?php echo htmlspecialchars($resident['id_type'] ?? '—'); ?></strong>
                        </div>
                    </div>

                    <div class="id-photo-wrap">
                        <?php if (!empty($resident['id_photo']) && file_exists('uploads/ids/' . $resident['id_photo'])): ?>
                            <img src="uploads/ids/<?php echo htmlspecialchars($resident['id_photo']); ?>" alt="Submitted ID">
                        <?php else: ?>
                            <div class="no-photo">No ID photo on file.</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($resident['id_verification_status'] === 'verified'): ?>
                        <div class="already-decided-note">✅ This resident's ID has already been verified.</div>
                    <?php else: ?>
                        <div class="review-actions">
                            <button class="approve-btn" id="approveBtn" data-resident="<?php echo (int)$resident['id']; ?>">Approve</button>
                            <button class="reject-btn" id="rejectBtn" data-resident="<?php echo (int)$resident['id']; ?>">Reject</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        <?php if (!$not_found): ?>
        const alertBox = document.getElementById('actionAlert');
        function showAlert(msg, type) {
            alertBox.textContent = msg;
            alertBox.className = 'action-alert show ' + type;
        }

        function decide(action) {
            const approveBtn = document.getElementById('approveBtn');
            const rejectBtn = document.getElementById('rejectBtn');
            approveBtn.disabled = true;
            rejectBtn.disabled = true;

            fetch('update_id_verification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'resident_id=<?php echo (int)$resident_id; ?>&action=' + action
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showAlert(action === 'approve' ? 'Resident verified successfully.' : 'Resident\'s ID rejected.', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showAlert(data.error || 'Something went wrong.', 'error');
                    approveBtn.disabled = false;
                    rejectBtn.disabled = false;
                }
            })
            .catch(() => {
                showAlert('Network error. Please try again.', 'error');
                approveBtn.disabled = false;
                rejectBtn.disabled = false;
            });
        }

        document.getElementById('approveBtn')?.addEventListener('click', () => decide('approve'));
        document.getElementById('rejectBtn')?.addEventListener('click', () => decide('reject'));
        <?php endif; ?>

        // ── Notification bell ──
        const bellBtn=document.getElementById('bellBtn'),dropdown=document.getElementById('notifDropdown'),overlay=document.getElementById('notifOverlay'),badge=document.getElementById('notifBadge'),nList=document.getElementById('nList'),nLabel=document.getElementById('nLabel'),markBtn=document.getElementById('markReadBtn');
        let bellOpen=false;
        function taAgo(d){const s=Math.floor((new Date()-new Date(d))/1000);if(s<60)return'just now';if(s<3600)return Math.floor(s/60)+'m ago';if(s<86400)return Math.floor(s/3600)+'h ago';return Math.floor(s/86400)+'d ago';}
        function escN(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
        function renderBell({notifications,unread_count}){
            if(unread_count>0){badge.style.display='flex';badge.textContent=unread_count>99?'99+':unread_count;}
            else badge.style.display='none';
            nLabel.textContent=unread_count>0?`${unread_count} unread`:'All caught up!';
            nList.innerHTML=notifications.length?notifications.map(n=>`<a class="n-item" style="display:flex;gap:14px;padding:15px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;${n.is_read==0?'background:#f0fdf4;border-left:3px solid #2D6A4F;':''}" href="${n.type === 'id_verification' ? 'admin-review-id.php?resident_id=' + n.resident_id : (n.type === 'new_appointment' || n.type === 'unassigned_warning') ? 'admin-lupon-assignments.php?complaint_id=' + n.complaint_id : 'admin-view-complaint.php?id=' + n.complaint_id}">
<div style="width:36px;height:36px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><img src="${n.type === 'id_verification' ? 'people.png' : 'file.png'}" style="width:16px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg);"></div><div><strong style="display:block;font-size:13px;color:#1a202c;margin-bottom:2px;">${escN(n.subject)}</strong><p style="font-size:12px;color:#64748b;margin:0 0 3px;">${escN(n.message)}</p><time style="font-size:11px;color:#94a3b8;">${taAgo(n.created_at)}</time></div></a>`).join(''):`<div style="text-align:center;padding:50px 20px;color:#94a3b8;"><img src="bell.png" style="width:40px;opacity:.28;display:block;margin:0 auto 10px;"><p>No notifications.</p></div>`;
        }
        function fetchBell(){fetch('get_notifications.php?action=fetch').then(r=>r.json()).then(renderBell).catch(()=>{});}
        bellBtn.addEventListener('click',()=>{if(bellOpen){dropdown.style.left='-420px';overlay.style.display='none';bellOpen=false;}else{dropdown.style.left='76px';overlay.style.display='block';bellOpen=true;}});
        overlay.addEventListener('click',()=>{dropdown.style.left='-420px';overlay.style.display='none';bellOpen=false;});
        markBtn.addEventListener('click',()=>fetch('get_notifications.php?action=mark_read').then(r=>r.json()).then(()=>fetchBell()));
        fetchBell();
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
        <div style="background:#fff;border-radius:18px;padding:36px 32px 28px;width:100%;max-width:380px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,0.18);position:relative;">
            <button onclick="closeLogoutModal()" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;">×</button>
            <div style="width:64px;height:64px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
                <img src="logout.png" style="width:28px;height:28px;filter:invert(29%) sepia(91%) saturate(1500%) hue-rotate(330deg) brightness(90%);">
            </div>
            <h2 style="font-size:18px;font-weight:700;color:#1a202c;margin:0 0 8px;">Log out of your account?</h2>
            <p style="font-size:13px;color:#64748b;margin:0 0 28px;line-height:1.6;">You will be returned to the login page.<br>Any unsaved changes will be lost.</p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <a href="logout.php" style="display:block;background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;padding:13px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;">Yes, log me out</a>
                <button onclick="closeLogoutModal()" style="background:#f8fafc;color:#374151;border:1.5px solid #e2e8f0;padding:13px;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">Cancel, stay logged in</button>
            </div>
        </div>
    </div>
    <script>
        function openLogoutModal()  { document.getElementById('logoutOverlay').style.display='flex'; }
        function closeLogoutModal() { document.getElementById('logoutOverlay').style.display='none'; }
        document.addEventListener('keydown', e => { if(e.key==='Escape') closeLogoutModal(); });
    </script>

</body>
</html>
