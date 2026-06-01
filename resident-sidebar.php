<?php
// resident-sidebar.php — include at top of every resident page
// Assumes session_check_resident.php was already included (sets $session_resident_name)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.res-notif-wrapper { position:relative;display:flex;justify-content:center;align-items:center;padding:15px;cursor:pointer;opacity:0.6;transition:opacity 0.2s; }
.res-notif-wrapper:hover { opacity:1; }
.res-notif-wrapper img { width:24px;filter:brightness(0) invert(1); }
.res-notif-badge { position:absolute;top:6px;right:6px;background:#e53e3e;color:#fff;font-size:10px;font-weight:700;min-width:17px;height:17px;border-radius:9px;display:none;align-items:center;justify-content:center;padding:0 3px;border:2px solid #004d2c;line-height:1;pointer-events:none; }
.res-notif-badge.on { display:flex;animation:resBadgePop 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resBadgePop{0%{transform:scale(0)}65%{transform:scale(1.2)}100%{transform:scale(1)}}

.res-notif-overlay { display:none;position:fixed;inset:0;z-index:9000; }
.res-notif-overlay.show { display:block; }

.res-notif-panel { position:fixed;top:0;left:-420px;width:360px;height:100vh;background:#fff;border-radius:0 16px 16px 0;box-shadow:6px 0 30px rgba(0,0,0,0.14);z-index:9999;display:flex;flex-direction:column;transition:left 0.32s cubic-bezier(0.4,0,0.2,1);overflow:hidden; }
.res-notif-panel.open { left:65px; }

.res-panel-header { background:#1B4332;color:#fff;padding:20px 22px 16px;display:flex;justify-content:space-between;align-items:center;flex-shrink:0; }
.res-panel-header h3 { font-size:15px;font-weight:600;margin:0 0 2px; }
.res-panel-header small { font-size:11px;opacity:0.75;display:block; }
.res-panel-user { font-size:12px;opacity:0.85;margin-bottom:4px; }
.res-mark-read { background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;font-size:11px;font-weight:600;padding:5px 12px;border-radius:20px;cursor:pointer;white-space:nowrap;transition:background 0.2s; }
.res-mark-read:hover { background:rgba(255,255,255,0.28); }

.res-notif-list { overflow-y:auto;flex:1; }
.res-notif-item { display:flex;gap:13px;padding:15px 18px;border-bottom:1px solid #f1f5f9;text-decoration:none;color:inherit;transition:background 0.15s;cursor:pointer; }
.res-notif-item:hover { background:#f8fafc; }
.res-notif-item.unread { background:#f0fdf4;border-left:3px solid #2D6A4F; }
.res-notif-item.unread:hover { background:#e8f5e9; }

/* Type dot colors */
.rn-dot { width:38px;height:38px;border-radius:50%;background:#e8f5e9;display:flex;align-items:center;justify-content:center;flex-shrink:0; }
.rn-dot img { width:17px;height:17px;filter:invert(29%) sepia(61%) saturate(446%) hue-rotate(105deg); }
.type-approved  .rn-dot { background:#d1fae5; }
.unread.type-approved  .rn-dot { background:#059669; }
.unread.type-approved  .rn-dot img { filter:brightness(0) invert(1); }
.type-rejected  .rn-dot { background:#fee2e2; }
.unread.type-rejected  .rn-dot { background:#dc2626; }
.unread.type-rejected  .rn-dot img { filter:brightness(0) invert(1); }
.type-cannot_handle .rn-dot { background:#ffedd5; }
.unread.type-cannot_handle .rn-dot { background:#ea580c; }
.unread.type-cannot_handle .rn-dot img { filter:brightness(0) invert(1); }
.type-followup .rn-dot { background:#dbeafe; }
.unread.type-followup .rn-dot { background:#2563eb; }
.unread.type-followup .rn-dot img { filter:brightness(0) invert(1); }

.rn-body { flex:1;min-width:0; }
.rn-body strong { display:block;font-size:13px;color:#1a202c;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.rn-body p { font-size:12px;color:#64748b;line-height:1.45;margin:0 0 4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden; }
.rn-body time { font-size:11px;color:#94a3b8; }
.rn-view-link { font-size:11px;font-weight:600;color:#2D6A4F;margin-top:3px;display:block; }

.res-empty { text-align:center;padding:50px 20px;color:#94a3b8; }
.res-empty img { width:42px;opacity:0.28;display:block;margin:0 auto 12px; }
.res-empty p { font-size:13px; }

@keyframes resBellPulse{0%{box-shadow:0 0 0 0 rgba(229,62,62,0.5)}70%{box-shadow:0 0 0 7px rgba(229,62,62,0)}100%{box-shadow:0 0 0 0 rgba(229,62,62,0)}}
.res-bell-pulse { animation:resBellPulse 2s ease-out infinite;border-radius:50%; }
</style>

<nav class="sidebar">
    <div class="sidebar-top">
        <a href="resident-portal.php" class="nav-item <?php echo $current_page==='resident-portal.php'?'active':''; ?>">
            <img src="dashboard.png" alt="Dashboard">
        </a>
        <a href="complaint-form.php" class="nav-item <?php echo $current_page==='complaint-form.php'?'active':''; ?>">
            <img src="file.png" alt="File Complaint">
        </a>
        <div class="res-notif-wrapper" id="resBellBtn">
            <img src="bell.png" alt="Notifications" id="resBellIcon">
            <span class="res-notif-badge" id="resBadge"></span>
        </div>
        <a href="logout.php" class="nav-item" title="Logout">
            <img src="people.png" alt="Logout">
        </a>
    </div>
</nav>

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

<script>
(function() {
    const btn     = document.getElementById('resBellBtn');
    const panel   = document.getElementById('resPanel');
    const overlay = document.getElementById('resOverlay');
    const badge   = document.getElementById('resBadge');
    const list    = document.getElementById('resNotifList');
    const label   = document.getElementById('resUnreadLabel');
    const markBtn = document.getElementById('resMarkRead');
    let open = false;

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
            document.getElementById('resBellIcon').classList.add('res-bell-pulse');
        } else {
            badge.classList.remove('on');
            document.getElementById('resBellIcon').classList.remove('res-bell-pulse');
        }
        label.textContent = unread_count > 0
            ? `${unread_count} new update${unread_count>1?'s':''}`
            : "You're all caught up!";

        if (!notifications.length) {
            list.innerHTML = `<div class="res-empty"><img src="bell.png"><p>No notifications yet.</p></div>`;
            return;
        }
        list.innerHTML = notifications.map(n => `
            <a class="res-notif-item ${n.is_read==0?'unread':''} type-${esc(n.type)}"
               href="view-complaint.php?id=${n.complaint_id}">
                <div class="rn-dot"><img src="bell.png" alt=""></div>
                <div class="rn-body">
                    <strong>${typeLabel(n.type)}</strong>
                    <p>${esc(n.message)}</p>
                    <time>${timeAgo(n.created_at)}</time>
                    <span class="rn-view-link">View complaint →</span>
                </div>
            </a>`).join('');
    }

    function fetch_notifs() {
        fetch('get_resident_notifications.php?action=fetch')
            .then(r=>r.json()).then(render).catch(()=>{});
    }

    function openPanel()  { panel.classList.add('open'); overlay.classList.add('show'); open=true; }
    function closePanel() { panel.classList.remove('open'); overlay.classList.remove('show'); open=false; }

    btn.addEventListener('click', ()=> open ? closePanel() : openPanel());
    overlay.addEventListener('click', closePanel);
    markBtn.addEventListener('click', ()=>{
        fetch('get_resident_notifications.php?action=mark_read')
            .then(r=>r.json()).then(()=>fetch_notifs());
    });

    fetch_notifs();
    setInterval(fetch_notifs, 15000);
})();
</script>
