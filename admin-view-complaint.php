<?php
require_once 'session_check_admin.php';
include 'db.php';

$id  = (int)($_GET['id'] ?? 0);
$row = $conn->query("SELECT * FROM complaints WHERE id=$id")->fetch_assoc();
if (!$row) { echo "<p style='padding:40px;font-family:Poppins,sans-serif;'>Complaint not found.</p>"; exit; }

$evidence_files = !empty($row['evidence_pic'])
    ? array_filter(array_map('trim', explode(',', $row['evidence_pic'])),
        fn($f) => $f !== '' && $f !== 'placeholder.jpg')
    : [];

$status_cls = strtolower(str_replace([' ','_'], '-', $row['status']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Case View - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="case-view.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f8fafc; }

        /* ── Layout ── */
        .content-flex-container { display:flex; gap:24px; align-items:flex-start; width:100%; }
        .details-column { flex:1; min-width:0; }
        .side-panel { width:270px; flex-shrink:0; }

        /* ── Header ── */
        .case-header-bar {
            display:flex; justify-content:space-between; align-items:center;
            margin-bottom:24px; padding-bottom:18px; border-bottom:1px solid #e8edf2;
        }
        .case-title    { font-size:22px; color:#1a202c; margin:4px 0 0; font-weight:700; }
        .case-category { font-size:13px; color:#2D6A4F; font-weight:600; }
        .btn-update-status {
            background:#1B4332; color:#fff; border:none;
            padding:10px 20px; border-radius:8px; cursor:pointer;
            font-weight:600; font-size:13px; font-family:inherit;
            transition:background .2s;
        }
        .btn-update-status:hover { background:#004d2c; }

        /* ── Status pills ── */
        .status-pill { display:inline-flex;align-items:center;padding:5px 14px;border-radius:99px;font-size:12px;font-weight:600; }
        .status-pill.pending           { background:#fef3c7;color:#92400e; }
        .status-pill.approved          { background:#d1fae5;color:#065f46; }
        .status-pill.in-process        { background:#dbeafe;color:#1e40af; }
        .status-pill.rescheduled       { background:#f5f3ff;color:#5b21b6; }
        .status-pill.cannot-be-handled { background:#ffedd5;color:#9a3412; }
        .status-pill.completed         { background:#e0e7ff;color:#3730a3; }

        /* ── Detail cards ── */
        .detail-card { background:#fff;border-radius:12px;padding:24px;margin-bottom:18px;border:1px solid #edf2f7; }
        .detail-card h3 { font-size:14px;font-weight:600;color:#374151;margin:0 0 14px;display:flex;align-items:center;gap:8px; }
        .split-cards { display:flex;gap:16px;margin-bottom:18px; }
        .detail-card.half { flex:1;min-width:0; }
        label { font-size:11px;color:#94a3b8;text-transform:uppercase;font-weight:600;display:block;margin:12px 0 3px; }
        label:first-child { margin-top:0; }
        span, strong { font-size:14px;color:#2d3748;display:block;font-weight:500; }
        .narrative-text {
            font-size:14px;color:#4a5568;line-height:1.7;
            background:#f8fafc;padding:12px 15px;border-radius:6px;
            border:1px solid #edf2f7;margin-top:6px;
        }
        .info-split { display:flex;gap:24px;margin-top:14px;flex-wrap:wrap; }
        .info-split > div { flex:1;min-width:120px; }

        /* ── Evidence grid ── */
        .evidence-container { padding-top:10px; }
        .evidence-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:12px; }
        .ev-card { background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;text-align:center; }
        .ev-card img.thumb { width:100%;height:150px;object-fit:cover;display:block; }
        .ev-card .ev-icon-wrap { height:150px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px; }
        .ev-card .ev-icon-wrap img { width:36px;opacity:0.4; }
        .ev-card .ev-label { font-size:11px;color:#64748b;padding:7px 8px;border-top:1px solid #e8edf2;word-break:break-all;background:#fff; }
        .no-evidence { color:#94a3b8;font-style:italic;padding:28px;text-align:center;background:#f8fafc;border-radius:8px;border:1px dashed #dde3e8; }

        /* ── Admin comment display ── */
        .admin-comment-box { background:#fff8e7;border:1px solid #fcd34d;border-radius:8px;padding:14px 16px;margin-top:14px; }
        .admin-comment-box .clabel { font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;margin-bottom:5px;display:block; }
        .admin-comment-box p { font-size:14px;color:#78350f;margin:0;line-height:1.6; }

        /* ── Side panel ── */
        .side-card { background:#fff;border-radius:12px;padding:20px;border:1px solid #edf2f7;margin-bottom:14px; }
        .side-card h3 { font-size:13px;color:#374151;margin:0 0 12px;font-weight:600; }
        .side-info-row { margin-bottom:10px; }
        .side-info-row label { font-size:10px;margin:0 0 2px; }
        .side-info-row span  { font-size:13px;font-weight:500; }

        /* ── Notification bell ── */
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

        /* ════════════════════════════════════════
           STATUS MODAL — FULLY SCROLLABLE FIX
           ════════════════════════════════════════ */

        /* Overlay: covers full screen, centers content, has padding so modal
           never touches screen edges, enables scroll when modal is taller than viewport */
        .modal-bg {
            display: none;
            position: fixed;
            inset: 0;                          /* top/right/bottom/left all 0 */
            background: rgba(0,0,0,.52);
            z-index: 99999;
            /* Flex centers the modal box */
            justify-content: center;
            align-items: flex-start;           /* flex-start so tall modals start from top */
            /* Padding so modal never clips against viewport edge */
            padding: 24px 16px;
            box-sizing: border-box;
            /* THIS is the key: the overlay itself scrolls when modal is taller */
            overflow-y: auto;
        }
        .modal-bg.open { display: flex; }

        /* Modal box: NO fixed height, grows with content, scrolls only on tiny screens */
        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            width: 100%;
            max-width: 500px;
            position: relative;
            animation: mIn .22s ease;
            /* Let it grow naturally — the overlay handles scroll */
            margin: auto;                      /* vertically centers when content is short */
            box-sizing: border-box;
        }
        @keyframes mIn { from{transform:translateY(14px);opacity:0} to{transform:translateY(0);opacity:1} }

        .modal-close {
            position: absolute; top:14px; right:16px;
            background:none; border:none; font-size:22px;
            cursor:pointer; color:#94a3b8; line-height:1;
        }
        .modal-box h2  { font-size:18px;color:#1a202c;margin:0 0 4px; }
        .modal-box .msub { font-size:13px;color:#64748b;margin:0 0 20px; }

        /* Status option cards */
        .status-opts { display:flex;flex-direction:column;gap:9px;margin-bottom:18px; }
        .sopt {
            display:flex;align-items:flex-start;gap:12px;
            padding:12px 14px;border-radius:10px;border:2px solid #e2e8f0;
            cursor:pointer;transition:all .15s;background:#fff;
        }
        .sopt:hover { border-color:#a7c4b5;background:#f7fdfb; }
        .sopt.sel   { border-color:#2D6A4F;background:#f0fdf4; }
        .sopt input[type=radio] { accent-color:#2D6A4F;width:15px;height:15px;margin-top:3px;flex-shrink:0; }
        .sopt .oi strong { display:block;font-size:14px;color:#1a202c;margin-bottom:2px; }
        .sopt .oi small  { font-size:12px;color:#64748b;line-height:1.4; }

        /* Per-option accent colors when selected */
        .sopt[data-v="Approved"].sel         { border-color:#059669;background:#ecfdf5; }
        .sopt[data-v="In Process"].sel       { border-color:#2563eb;background:#eff6ff; }
        .sopt[data-v="Cannot Be Handled"].sel{ border-color:#ea580c;background:#fff7ed; }
        .sopt[data-v="Rescheduled"].sel      { border-color:#7c3aed;background:#f5f3ff; }
        .sopt[data-v="Completed"].sel        { border-color:#059669;background:#ecfdf5; }

        /* Reschedule date/time panel — hidden until Rescheduled is picked */
        .reschedule-panel {
            display: none;
            background: #f5f3ff;
            border: 1px solid #c4b5fd;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 18px;
        }
        .reschedule-panel.show { display: block; }
        .reschedule-panel h4 {
            font-size: 13px; font-weight: 700; color: #5b21b6;
            margin: 0 0 12px;
        }
        .rs-row { display: flex; gap: 12px; }
        .rs-field { flex: 1; }
        .rs-field label {
            font-size: 11px; font-weight: 600; color: #374151;
            display: block; margin-bottom: 5px; text-transform: uppercase;
        }
        .rs-input {
            width: 100%; padding: 9px 12px; border: 1px solid #c4b5fd;
            border-radius: 8px; font-size: 13px; font-family: inherit;
            box-sizing: border-box; background: #fff;
        }
        .rs-input:focus { outline: none; border-color: #7c3aed; }
        .rs-taken-note {
            font-size: 12px; color: #dc2626; margin-top: 6px; display: none;
        }
        .rs-taken-note.show { display: block; }

        /* Comment textarea */
        .comment-area {
            width:100%;padding:11px;border:1px solid #e2e8f0;border-radius:8px;
            font-size:13px;resize:vertical;min-height:85px;
            font-family:inherit;margin-bottom:18px;box-sizing:border-box;
        }
        .comment-area:focus { outline:none;border-color:#2D6A4F; }

        /* Action buttons */
        .mactions { display:flex;gap:10px; }
        .btn-confirm {
            flex:1;background:#1B4332;color:#fff;border:none;
            padding:12px;border-radius:8px;font-weight:600;
            font-size:14px;cursor:pointer;font-family:inherit;
        }
        .btn-confirm:hover { background:#004d2c; }
        .btn-mcancel {
            padding:12px 22px;border:1px solid #e2e8f0;border-radius:8px;
            background:#fff;cursor:pointer;font-size:13px;font-weight:500;font-family:inherit;
        }

        /* ── Toast ── */
        .toast{position:fixed;bottom:26px;right:26px;z-index:999999;background:#1B4332;color:#fff;padding:13px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.18);transform:translateY(70px);opacity:0;transition:all .32s cubic-bezier(0.34,1.56,0.64,1);pointer-events:none;}
        .toast.show{transform:translateY(0);opacity:1;}
    </style>
</head>
<body class="admin-dashboard-layout">

    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-top">
            <a href="admin-dashboard.php"          class="nav-item active"><img src="dashboard.png"></a>
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

    <!-- ── Status Update Modal ── -->
    <div class="modal-bg" id="statusModal">
        <div class="modal-box">
            <button class="modal-close" onclick="closeModal()">×</button>
            <h2>Update Case Status</h2>
            <p class="msub">Select a new status and optionally add a note for the complainant.</p>

            <div class="status-opts">
                <label class="sopt" data-v="Approved">
                    <input type="radio" name="ns" value="Approved">
                    <div class="oi">
                        <strong>✅ Approved</strong>
                        <small>Case is valid — resident can schedule a Lupon appointment.</small>
                    </div>
                </label>
                <label class="sopt" data-v="In Process">
                    <input type="radio" name="ns" value="In Process">
                    <div class="oi">
                        <strong>🔄 In Process / Follow-Up</strong>
                        <small>Case is now in process. Use for follow-up or requests for more info.</small>
                    </div>
                </label>
                <label class="sopt" data-v="Cannot Be Handled">
                    <input type="radio" name="ns" value="Cannot Be Handled">
                    <div class="oi">
                        <strong>⚠️ Cannot Be Handled</strong>
                        <small>Serious case beyond barangay jurisdiction. Resident needs Certificate to File Action.</small>
                    </div>
                </label>
                <label class="sopt" data-v="Rescheduled">
                    <input type="radio" name="ns" value="Rescheduled">
                    <div class="oi">
                        <strong>📅 Rescheduled</strong>
                        <small>Mediation was unsuccessful. Set a new hearing date and time (admin's choice).</small>
                    </div>
                </label>
                <label class="sopt" data-v="Completed">
                    <input type="radio" name="ns" value="Completed">
                    <div class="oi">
                        <strong>✔️ Completed</strong>
                        <small>Case has been fully resolved.</small>
                    </div>
                </label>
            </div>

            <!-- Reschedule panel — only visible when Rescheduled is selected -->
            <div class="reschedule-panel" id="reschedulePanel">
                <h4>📅 Set New Hearing Schedule</h4>
                <div class="rs-row">
                    <div class="rs-field">
                        <label>New Date</label>
                        <input type="date" id="rsDate" class="rs-input"
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               onchange="checkSlotAvailability()">
                    </div>
                    <div class="rs-field">
                        <label>New Time</label>
                        <select id="rsTime" class="rs-input" onchange="checkSlotAvailability()">
                            <option value="">— Select time —</option>
                            <option value="9:00 AM">9:00 AM</option>
                            <option value="10:00 AM">10:00 AM</option>
                            <option value="11:00 AM">11:00 AM</option>
                            <option value="12:00 PM">12:00 PM</option>
                        </select>
                    </div>
                </div>
                <p class="rs-taken-note" id="rsTakenNote">⚠ This slot is already taken. Please choose a different date or time.</p>
            </div>

            <textarea class="comment-area" id="adminComment"
                      placeholder="Add a comment or note for the complainant (optional)…"></textarea>

            <div class="mactions">
                <button class="btn-mcancel" onclick="closeModal()">Cancel</button>
                <button class="btn-confirm" onclick="submitStatus()">Confirm Update</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <main class="portal-content" style="padding:30px 40px;align-items:stretch;">
        <div style="margin-bottom:18px;">
            <a href="admin-dashboard.php" style="text-decoration:none;color:#64748b;font-size:13px;font-weight:500;">← Back to Dashboard</a>
        </div>

        <div class="content-flex-container">
            <div class="details-column">

                <!-- Header -->
                <header class="case-header-bar">
                    <div>
                        <span class="case-category"><?php echo htmlspecialchars($row['subject']); ?></span>
                        <h1 class="case-title">Case #: BRGY-2026-0<?php echo $row['id']; ?></h1>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span class="status-pill <?php echo $status_cls; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                        <button class="btn-update-status" onclick="openModal()">Update Status</button>
                    </div>
                </header>

                <!-- Complaint Info -->
                <section class="detail-card">
                    <h3>📄 Complaint Information</h3>
                    <label>Description</label>
                    <p class="narrative-text"><?php echo nl2br(htmlspecialchars($row['narrative'])); ?></p>
                    <div class="info-split">
                        <div><label>Complainant</label><strong><?php echo htmlspecialchars($row['complainant_name']); ?></strong></div>
                        <div><label>Address</label><strong><?php echo htmlspecialchars($row['complainant_address']); ?></strong></div>
                        <div><label>Contact</label><strong><?php echo htmlspecialchars($row['complainant_contact']); ?></strong></div>
                        <div><label>Date Filed</label><strong><?php echo date("M j, Y", strtotime($row['date_filed'])); ?></strong></div>
                    </div>
                    <?php if (!empty($row['admin_comment'])): ?>
                    <div class="admin-comment-box">
                        <span class="clabel">Admin Comment</span>
                        <p><?php echo nl2br(htmlspecialchars($row['admin_comment'])); ?></p>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- Party Details -->
                <div class="split-cards">
                    <section class="detail-card half">
                        <h3>Complainant</h3>
                        <label>Name</label><span><?php echo htmlspecialchars($row['complainant_name']); ?></span>
                        <label>Address</label><span><?php echo htmlspecialchars($row['complainant_address']); ?></span>
                        <label>Contact</label><span><?php echo htmlspecialchars($row['complainant_contact']); ?></span>
                    </section>
                    <section class="detail-card half">
                        <h3>Respondent</h3>
                        <label>Name</label><span><?php echo htmlspecialchars($row['respondent_name']); ?></span>
                        <label>Address</label><span><?php echo htmlspecialchars($row['respondent_address']); ?></span>
                    </section>
                </div>

                <!-- Evidence -->
                <section class="detail-card">
                    <h3>📎 Evidence & Documents</h3>
                    <div class="evidence-container">
                        <?php if (!empty($evidence_files)): ?>
                            <div class="evidence-grid">
                                <?php foreach ($evidence_files as $file):
                                    $ext    = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    $is_img = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']);
                                ?>
                                <div class="ev-card">
                                    <?php if ($is_img): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($file); ?>" target="_blank">
                                            <img class="thumb" src="uploads/<?php echo htmlspecialchars($file); ?>" alt="evidence">
                                        </a>
                                    <?php else: ?>
                                        <a href="uploads/<?php echo htmlspecialchars($file); ?>" target="_blank" style="text-decoration:none;">
                                            <div class="ev-icon-wrap">
                                                <img src="file.png" alt="file">
                                                <span style="font-size:11px;color:#64748b;font-weight:700;"><?php echo strtoupper($ext); ?></span>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <div class="ev-label"><?php echo htmlspecialchars($file); ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="no-evidence">No evidence was uploaded with this complaint.</div>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Appointment (if scheduled) -->
                <?php if (!empty($row['appointment_date'])): ?>
                <section class="detail-card">
                    <h3>📅 Scheduled Appointment</h3>
                    <div class="info-split">
                        <div><label>Date</label><strong><?php echo date("F j, Y", strtotime($row['appointment_date'])); ?></strong></div>
                        <div><label>Time</label><strong><?php echo htmlspecialchars($row['appointment_time']); ?></strong></div>
                        <div><label>Lupon Panel</label><strong><?php echo htmlspecialchars($row['lupon_preference']); ?></strong></div>
                    </div>
                </section>
                <?php endif; ?>

            </div><!-- /details-column -->

            <!-- Side panel -->
            <aside class="side-panel">
                <div class="side-card">
                    <h3>📋 Case Summary</h3>
                    <div class="side-info-row"><label>Case #</label><span>BRGY-2026-0<?php echo $row['id']; ?></span></div>
                    <?php if (!empty($row['is_paper_based'])): ?>
                    <div class="side-info-row">
                        <label>Record type</label>
                        <span style="background:#fef3c7;color:#92400e;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;display:inline-block;margin-top:2px;">📄 Paper-based record</span>
                    </div>
                    <?php endif; ?>
                    <div class="side-info-row">
                        <label>Status</label>
                        <span class="status-pill <?php echo $status_cls; ?>" style="margin-top:4px;font-size:11px;">
                            <?php echo htmlspecialchars($row['status']); ?>
                        </span>
                    </div>
                    <div class="side-info-row"><label>Filed</label><span><?php echo date("M j, Y", strtotime($row['date_filed'])); ?></span></div>
                    <div class="side-info-row"><label>Complainant</label><span><?php echo htmlspecialchars($row['complainant_name']); ?></span></div>
                    <div class="side-info-row"><label>Respondent</label><span><?php echo htmlspecialchars($row['respondent_name']); ?></span></div>
                    <?php if (!empty($row['appointment_date'])): ?>
                    <div class="side-info-row">
                        <label>Hearing</label>
                        <span><?php echo date("M j, Y", strtotime($row['appointment_date'])); ?> · <?php echo htmlspecialchars($row['appointment_time']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </main>

    <script>
        const COMPLAINT_ID = <?php echo $id; ?>;

        // ── Modal open/close ──
        function openModal()  { document.getElementById('statusModal').classList.add('open'); }
        function closeModal() { document.getElementById('statusModal').classList.remove('open'); }

        // Highlight selected option + show/hide reschedule panel
        document.querySelectorAll('.sopt input[type=radio]').forEach(r => {
            r.addEventListener('change', () => {
                document.querySelectorAll('.sopt').forEach(o => o.classList.remove('sel'));
                r.closest('.sopt').classList.add('sel');
                // Show reschedule panel only for Rescheduled
                const panel = document.getElementById('reschedulePanel');
                panel.classList.toggle('show', r.value === 'Rescheduled');
                // Clear slot warning when switching options
                document.getElementById('rsTakenNote').classList.remove('show');
            });
        });

        // Check if chosen reschedule slot is available
        function checkSlotAvailability() {
            const date = document.getElementById('rsDate').value;
            const time = document.getElementById('rsTime').value;
            const note = document.getElementById('rsTakenNote');
            note.classList.remove('show');
            if (!date || !time) return;

            fetch(`get_admin_availability.php?action=timeslots&date=${encodeURIComponent(date)}&complaint_id=${COMPLAINT_ID}`)
                .then(r => r.json())
                .then(d => {
                    const slot = (d.slots || []).find(s => s.time === time);
                    if (slot && !slot.available) note.classList.add('show');
                })
                .catch(() => {});
        }

        // Close on backdrop click (but NOT when clicking inside the modal box)
        document.getElementById('statusModal').addEventListener('click', e => {
            if (e.target === document.getElementById('statusModal')) closeModal();
        });

        // ── Toast ──
        function showToast(msg, bg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.background = bg || '#1B4332';
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // ── Submit status ──
        function submitStatus() {
            const sel = document.querySelector('input[name=ns]:checked');
            if (!sel) { showToast('Please select a status first.', '#b45309'); return; }

            // Validate reschedule fields if Rescheduled is chosen
            if (sel.value === 'Rescheduled') {
                const rsDate = document.getElementById('rsDate').value;
                const rsTime = document.getElementById('rsTime').value;
                if (!rsDate) { showToast('Please select a new hearing date.', '#b45309'); return; }
                if (!rsTime) { showToast('Please select a new hearing time.', '#b45309'); return; }
                // Block if slot is taken
                if (document.getElementById('rsTakenNote').classList.contains('show')) {
                    showToast('That slot is already taken. Choose a different date or time.', '#dc2626'); return;
                }
            }

            const fd = new FormData();
            fd.append('complaint_id', COMPLAINT_ID);
            fd.append('status',       sel.value);
            fd.append('comment',      document.getElementById('adminComment').value.trim());

            if (sel.value === 'Rescheduled') {
                fd.append('reschedule_date', document.getElementById('rsDate').value);
                fd.append('reschedule_time', document.getElementById('rsTime').value);
            }

            fetch('update_status.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { closeModal(); showToast('Status updated!'); setTimeout(() => location.reload(), 1100); }
                    else showToast('Error: ' + (d.error || 'unknown'), '#dc2626');
                })
                .catch(() => showToast('Network error.', '#dc2626'));
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
