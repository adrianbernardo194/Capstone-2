<?php
require_once 'session_check_resident.php';
$conn = new mysqli("localhost","root","","barangay_db");
if ($conn->connect_error) die("Connection failed.");

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Complaint - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="form-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
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
<?php include 'resident-sidebar.php'; ?>

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
            <button class="submit-btn" onclick="window.location.href='resident-portal.php'">Done</button>
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
</body>
</html>
