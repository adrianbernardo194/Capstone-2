<?php
require_once 'session_check_admin.php';
include 'db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Calendar Management - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f8fafc; }

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
            padding:2px;
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
            <a href="admin-calendar.php"            class="nav-item active" title="Calendar"><img src="clock.png"></a>
            <a href="admin-file-maintenance.php"    class="nav-item" title="File Maintenance"><img src="dashboard.png"></a>
            <a href="admin-audit-trail.php"         class="nav-item" title="Audit Trail"><img src="file.png"></a>
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

    <div class="toast" id="toast"></div>

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
                           oninput="onWindowSlide(this.value)">
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
                    <div class="add-h-field">
                        <label>Label / Reason</label>
                        <input type="text" id="hLabel" class="add-h-input"
                               placeholder="e.g., Christmas Day, Barangay Fiesta, No Hearing">
                    </div>
                    <button class="btn-add-holiday" onclick="addHoliday()">Block This Date</button>
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
        let currentYear  = new Date().getFullYear();
        let currentMonth = new Date().getMonth(); // 0-indexed
        let bookingWindow = <?php echo $booking_window; ?>;

        // Holidays keyed by YYYY-MM-DD
        let holidayMap = {};
        <?php foreach ($holidays as $h): ?>
        holidayMap['<?php echo $h['holiday_date']; ?>'] = { id: <?php echo $h['id']; ?>, label: '<?php echo addslashes($h['label']); ?>' };
        <?php endforeach; ?>

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

                if (isPast && !isToday) el.classList.add('past');
                if (isToday)   el.classList.add('today');
                if (isHoliday) el.classList.add('holiday');
                if (inWindow && !isHoliday)  el.classList.add('in-window');

                const numSpan = document.createElement('span');
                numSpan.className   = 'day-num';
                numSpan.textContent = d;
                el.appendChild(numSpan);

                if (isHoliday) {
                    const dot = document.createElement('div');
                    dot.className = 'h-dot';
                    el.appendChild(dot);
                    el.title = holidayMap[dateStr].label;
                }

                // Click: toggle holiday (only future dates)
                if (!isPast || isToday) {
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
            renderCalendar();
        }

        function formatDate(d) {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        }

        // ── Click a day to quickly add/remove as holiday ───────────────────────
        function clickDay(dateStr, dateObj, el) {
            if (dateObj < today) return;

            if (holidayMap[dateStr]) {
                // Already a holiday — delete it
                deleteHoliday(holidayMap[dateStr].id, dateStr);
            } else {
                // Pre-fill the add holiday form
                document.getElementById('hDate').value  = dateStr;
                document.getElementById('hLabel').focus();
                showToast('Date pre-filled. Add a label and click "Block This Date".', '#1B4332');
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
        function addHoliday() {
            const date  = document.getElementById('hDate').value;
            const label = document.getElementById('hLabel').value.trim();
            if (!date)  { showToast('Please select a date.', '#b45309'); return; }
            if (!label) { showToast('Please enter a label.', '#b45309'); return; }

            const fd = new FormData();
            fd.append('action', 'add_holiday');
            fd.append('date',   date);
            fd.append('label',  label);

            fetch('save_calendar.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showToast('Date blocked successfully!');
                        document.getElementById('hDate').value  = '';
                        document.getElementById('hLabel').value = '';
                        // Reload to refresh calendar + list
                        location.reload();
                    } else {
                        showToast(d.error || 'Failed to block date.', '#dc2626');
                    }
                })
                .catch(() => showToast('Network error.', '#dc2626'));
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
            nList.innerHTML=notifications.length?notifications.map(n=>`<a class="n-item ${n.is_read==0?'unread':''}" href="admin-view-complaint.php?id=${n.complaint_id}"><div class="n-dot"><img src="file.png"></div><div class="n-body"><strong>${escN(n.subject)}</strong><p>${escN(n.message)}</p><time>${taAgo(n.created_at)}</time></div></a>`).join(''):`<div class="n-empty"><img src="bell.png"><p>No notifications.</p></div>`;
        }
        function fetchBell(){fetch('get_notifications.php?action=fetch').then(r=>r.json()).then(renderBell).catch(()=>{});}
        bellBtn.addEventListener('click',()=>{if(bellOpen){dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;}else{dropdown.classList.add('open');overlay.classList.add('show');bellOpen=true;}});
        overlay.addEventListener('click',()=>{dropdown.classList.remove('open');overlay.classList.remove('show');bellOpen=false;});
        markBtn.addEventListener('click',()=>fetch('get_notifications.php?action=mark_read').then(r=>r.json()).then(()=>fetchBell()));
        fetchBell();setInterval(fetchBell,15000);

        // ── Init ───────────────────────────────────────────────────────────────
        onWindowSlide(bookingWindow);
        renderCalendar();
    </script>
</body>
</html>
