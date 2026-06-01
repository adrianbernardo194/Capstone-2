<?php
require_once 'session_check_admin.php';
include 'db.php';

// ── Load all paper-based records ──────────────────────────────────────────────
$records_res = $conn->query("
    SELECT c.*, a.username as encoded_by
    FROM complaints c
    LEFT JOIN admins a ON a.id = c.encoded_by_admin_id
    WHERE c.is_paper_based = 1
    ORDER BY c.original_filed_date DESC
");
$records = [];
while ($r = $records_res->fetch_assoc()) $records[] = $r;

// ── Stats ─────────────────────────────────────────────────────────────────────
$total_paper    = count($records);
$pending_paper  = count(array_filter($records, fn($r) => $r['status'] === 'Pending'));
$completed_paper= count(array_filter($records, fn($r) => $r['status'] === 'Completed'));
$this_month     = count(array_filter($records, fn($r) =>
    date('Y-m', strtotime($r['original_filed_date'])) === date('Y-m')
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Maintenance - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="admin-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background:#f8fafc; }

        /* ── Page header ── */
        .page-header { margin-bottom:24px; }
        .page-header h1 { font-size:22px;font-weight:700;color:#1a202c;margin:0 0 4px; }
        .page-header p  { font-size:13px;color:#64748b;margin:0; }

        /* ── Stats strip ── */
        .fm-stats { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px; }
        .fm-stat  { background:#fff;border-radius:12px;padding:18px 20px;border:1px solid #edf2f7;display:flex;align-items:center;gap:14px; }
        .fm-stat-icon { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0; }
        .fm-stat-icon.brown  { background:#fef3c7; }
        .fm-stat-icon.red    { background:#fee2e2; }
        .fm-stat-icon.green  { background:#f0fdf4; }
        .fm-stat-icon.blue   { background:#eff6ff; }
        .fm-stat-info span   { font-size:11px;color:#64748b;display:block;margin-bottom:2px; }
        .fm-stat-info strong { font-size:22px;font-weight:700;color:#1a202c; }

        /* ── Layout: form left, table right ── */
        .fm-layout { display:grid;grid-template-columns:360px 1fr;gap:20px;align-items:flex-start; }

        /* ── Form card ── */
        .fm-form-card {
            background:#fff;border-radius:14px;border:1px solid #edf2f7;
            padding:24px;position:sticky;top:20px;
        }
        .fm-form-card h3 { font-size:15px;font-weight:700;color:#1a202c;margin:0 0 18px;display:flex;align-items:center;gap:8px; }

        .fm-field { margin-bottom:14px; }
        .fm-field label {
            font-size:11px;font-weight:600;color:#374151;
            text-transform:uppercase;display:block;margin-bottom:5px;
        }
        .fm-field label .req { color:#dc2626; }
        .fm-input {
            width:100%;padding:9px 12px;border:1px solid #e2e8f0;
            border-radius:8px;font-size:13px;font-family:inherit;
            box-sizing:border-box;background:#f8fafc;
            transition:border-color .15s;
        }
        .fm-input:focus { outline:none;border-color:#1B4332;background:#fff; }
        textarea.fm-input { resize:vertical;min-height:90px; }
        select.fm-input  { cursor:pointer; }

        .fm-row { display:grid;grid-template-columns:1fr 1fr;gap:12px; }

        /* Status indicator on form */
        .fm-status-note {
            font-size:11px;color:#64748b;margin-top:4px;
            padding:6px 10px;background:#f8fafc;border-radius:6px;
            border-left:3px solid #cbd5e0;
        }

        /* File upload */
        .fm-upload-area {
            border:2px dashed #e2e8f0;border-radius:8px;padding:16px;
            text-align:center;cursor:pointer;transition:all .15s;
        }
        .fm-upload-area:hover { border-color:#1B4332;background:#f0fdf4; }
        .fm-upload-area input { display:none; }
        .fm-upload-area .up-icon { font-size:22px;margin-bottom:4px; }
        .fm-upload-area .up-text { font-size:12px;color:#64748b; }
        .fm-upload-area .up-sub  { font-size:11px;color:#94a3b8;margin-top:2px; }
        #fileNames { font-size:11px;color:#1B4332;margin-top:6px;min-height:14px; }

        .btn-encode {
            width:100%;padding:12px;background:#1B4332;color:#fff;border:none;
            border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;
            font-family:inherit;transition:background .2s;margin-top:4px;
        }
        .btn-encode:hover { background:#004d2c; }
        .btn-encode:disabled { opacity:.5;cursor:not-allowed; }

        .btn-cancel-edit {
            width:100%;padding:10px;border:1px solid #e2e8f0;border-radius:8px;
            background:#fff;cursor:pointer;font-size:13px;font-family:inherit;
            margin-top:8px;display:none;
        }

        /* Alert strip */
        .fm-alert {
            padding:10px 14px;border-radius:8px;font-size:13px;
            margin-bottom:14px;display:none;
        }
        .fm-alert.success { background:#f0fdf4;color:#065f46;border:1px solid #86efac; }
        .fm-alert.error   { background:#fef2f2;color:#991b1b;border:1px solid #fca5a5; }
        .fm-alert.show    { display:block; }

        /* ── Records table ── */
        .fm-table-card {
            background:#fff;border-radius:14px;border:1px solid #edf2f7;
            overflow:hidden;
        }
        .fm-table-header {
            display:flex;align-items:center;justify-content:space-between;
            padding:18px 22px;border-bottom:1px solid #f1f5f9;
        }
        .fm-table-header h3 { font-size:15px;font-weight:700;color:#1a202c;margin:0; }

        /* Search */
        .fm-search {
            padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;
            font-size:13px;font-family:inherit;width:200px;
        }
        .fm-search:focus { outline:none;border-color:#1B4332; }

        /* Table */
        .fm-table { width:100%;border-collapse:collapse; }
        .fm-table th {
            font-size:11px;font-weight:700;color:#94a3b8;
            text-transform:uppercase;padding:12px 16px;
            text-align:left;border-bottom:1px solid #f1f5f9;
            background:#fafbfc;
        }
        .fm-table td {
            font-size:13px;color:#374151;padding:13px 16px;
            border-bottom:1px solid #f8fafc;vertical-align:middle;
        }
        .fm-table tr:last-child td { border-bottom:none; }
        .fm-table tr:hover td { background:#fafbfc; }

        .case-num { font-size:11px;font-weight:700;color:#1B4332; }
        .paper-badge {
            display:inline-flex;align-items:center;gap:4px;
            background:#fef3c7;color:#92400e;
            font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;
        }
        .name-cell strong { display:block;font-size:13px;color:#1a202c; }
        .name-cell span   { font-size:11px;color:#64748b; }

        .status-pill { padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;white-space:nowrap; }
        .status-pill.pending           { background:#fef3c7;color:#92400e; }
        .status-pill.approved          { background:#d1fae5;color:#065f46; }
        .status-pill.in-process        { background:#dbeafe;color:#1e40af; }
        .status-pill.rescheduled       { background:#f5f3ff;color:#5b21b6; }
        .status-pill.cannot-be-handled { background:#ffedd5;color:#9a3412; }
        .status-pill.completed         { background:#e0e7ff;color:#3730a3; }

        /* Action btns */
        .tbl-actions { display:flex;gap:6px; }
        .btn-tbl {
            padding:5px 12px;border-radius:6px;font-size:11px;font-weight:600;
            cursor:pointer;border:1px solid;font-family:inherit;transition:all .15s;
        }
        .btn-tbl.edit   { background:#eff6ff;color:#1e40af;border-color:#bfdbfe; }
        .btn-tbl.edit:hover { background:#dbeafe; }
        .btn-tbl.view   { background:#f0fdf4;color:#065f46;border-color:#86efac; }
        .btn-tbl.view:hover { background:#dcfce7; }
        .btn-tbl.del    { background:#fef2f2;color:#991b1b;border-color:#fca5a5; }
        .btn-tbl.del:hover { background:#fee2e2; }

        /* Empty state */
        .fm-empty {
            text-align:center;padding:50px 20px;color:#94a3b8;
        }
        .fm-empty .fm-empty-icon { font-size:40px;margin-bottom:10px; }
        .fm-empty p { font-size:13px;margin:0; }

        /* Edit mode indicator */
        .edit-mode-bar {
            background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;
            padding:10px 14px;margin-bottom:14px;font-size:12px;color:#1e40af;
            font-weight:600;display:none;align-items:center;gap:8px;
        }
        .edit-mode-bar.show { display:flex; }

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

        /* Toast */
        .toast{position:fixed;bottom:26px;right:26px;z-index:99999;color:#fff;padding:13px 20px;border-radius:10px;font-size:14px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,.18);transform:translateY(70px);opacity:0;transition:all .32s cubic-bezier(0.34,1.56,0.64,1);pointer-events:none;}
        .toast.show{transform:translateY(0);opacity:1;}
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
            <a href="admin-file-maintenance.php"    class="nav-item active" title="File Maintenance"><img src="dashboard.png"></a>
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

    <div class="toast" id="toast"></div>

    <main class="portal-content" style="padding:28px 36px;">

        <div class="page-header">
            <h1>📁 File Maintenance</h1>
            <p>Encode and manage paper-based Sumbong forms submitted at the barangay office. All encoded records are counted in the system analytics.</p>
        </div>

        <!-- Stats -->
        <div class="fm-stats">
            <div class="fm-stat">
                <div class="fm-stat-icon brown">📄</div>
                <div class="fm-stat-info"><span>Total encoded</span><strong><?php echo $total_paper; ?></strong></div>
            </div>
            <div class="fm-stat">
                <div class="fm-stat-icon red">⏳</div>
                <div class="fm-stat-info"><span>Pending</span><strong><?php echo $pending_paper; ?></strong></div>
            </div>
            <div class="fm-stat">
                <div class="fm-stat-icon green">✅</div>
                <div class="fm-stat-info"><span>Completed</span><strong><?php echo $completed_paper; ?></strong></div>
            </div>
            <div class="fm-stat">
                <div class="fm-stat-icon blue">📅</div>
                <div class="fm-stat-info"><span>Filed this month</span><strong><?php echo $this_month; ?></strong></div>
            </div>
        </div>

        <div class="fm-layout">

            <!-- ── LEFT: Encode form ── -->
            <div class="fm-form-card">
                <div class="edit-mode-bar" id="editModeBar">
                    ✏️ <span id="editModeLabel">Editing record</span>
                </div>
                <div class="fm-alert" id="formAlert"></div>

                <h3 id="formTitle">📝 Encode Paper Record</h3>

                <input type="hidden" id="editId" value="">

                <!-- Original filing date -->
                <div class="fm-field">
                    <label>Date filed on paper <span class="req">*</span></label>
                    <input type="date" id="fOrigDate" class="fm-input"
                           max="<?php echo date('Y-m-d'); ?>">
                    <p class="fm-status-note">Use the date written on the physical Sumbong form.</p>
                </div>

                <!-- Subject -->
                <div class="fm-field">
                    <label>Subject / Case type <span class="req">*</span></label>
                    <select id="fSubject" class="fm-input">
                        <option value="">— Select subject —</option>
                        <option>Noise and disturbance</option>
                        <option>Property boundary dispute</option>
                        <option>Physical altercation</option>
                        <option>Oral defamation / slander</option>
                        <option>Damage to property</option>
                        <option>Debt / money collection</option>
                        <option>Threat and intimidation</option>
                        <option>Domestic dispute</option>
                        <option>Trespassing</option>
                        <option>Theft of minor value</option>
                        <option>Animal-related complaint</option>
                        <option>Other</option>
                    </select>
                </div>

                <!-- Complainant -->
                <div class="fm-field">
                    <label>Complainant name <span class="req">*</span></label>
                    <input type="text" id="fCompName" class="fm-input" placeholder="Full name">
                </div>
                <div class="fm-row">
                    <div class="fm-field">
                        <label>Address</label>
                        <input type="text" id="fCompAddr" class="fm-input" placeholder="Address">
                    </div>
                    <div class="fm-field">
                        <label>Contact no.</label>
                        <input type="text" id="fCompContact" class="fm-input" placeholder="e.g. 09xxxxxxxxx">
                    </div>
                </div>

                <!-- Respondent -->
                <div class="fm-field">
                    <label>Respondent name <span class="req">*</span></label>
                    <input type="text" id="fRespName" class="fm-input" placeholder="Full name">
                </div>
                <div class="fm-field">
                    <label>Respondent address</label>
                    <input type="text" id="fRespAddr" class="fm-input" placeholder="Address">
                </div>

                <!-- Narrative -->
                <div class="fm-field">
                    <label>Narrative / complaint details <span class="req">*</span></label>
                    <textarea id="fNarrative" class="fm-input" placeholder="Summarize or copy the complaint narrative from the paper form…"></textarea>
                </div>

                <!-- Status -->
                <div class="fm-field">
                    <label>Current status</label>
                    <select id="fStatus" class="fm-input">
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="In Process">In Process</option>
                        <option value="Rescheduled">Rescheduled</option>
                        <option value="Cannot Be Handled">Cannot Be Handled</option>
                        <option value="Completed">Completed</option>
                    </select>
                    <p class="fm-status-note">Set the status that reflects the current state of this paper case.</p>
                </div>

                <!-- Scanned evidence (optional) -->
                <div class="fm-field">
                    <label>Scanned documents <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                    <div class="fm-upload-area" onclick="document.getElementById('fEvidence').click()">
                        <input type="file" id="fEvidence" multiple accept=".jpg,.jpeg,.png,.pdf"
                               onchange="showFileNames(this)">
                        <div class="up-icon">📎</div>
                        <div class="up-text">Click to upload scanned documents</div>
                        <div class="up-sub">JPG, PNG, PDF accepted</div>
                    </div>
                    <div id="fileNames"></div>
                </div>

                <button class="btn-encode" id="encodeBtn" onclick="submitRecord()">
                    📁 Save to System
                </button>
                <button class="btn-cancel-edit" id="cancelEditBtn" onclick="cancelEdit()">
                    Cancel editing
                </button>
            </div>

            <!-- ── RIGHT: Records table ── -->
            <div class="fm-table-card">
                <div class="fm-table-header">
                    <h3>Encoded paper records
                        <span style="font-size:12px;font-weight:400;color:#94a3b8;margin-left:8px;">
                            <?php echo $total_paper; ?> total
                        </span>
                    </h3>
                    <input type="text" class="fm-search" id="searchInput"
                           placeholder="Search name or subject…"
                           oninput="filterTable(this.value)">
                </div>

                <?php if (empty($records)): ?>
                <div class="fm-empty">
                    <div class="fm-empty-icon">📭</div>
                    <p>No paper records encoded yet.<br>Use the form on the left to add the first one.</p>
                </div>
                <?php else: ?>
                <table class="fm-table" id="fmTable">
                    <thead>
                        <tr>
                            <th>Case #</th>
                            <th>Date filed</th>
                            <th>Complainant</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Encoded by</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($records as $rec):
                        $sc = strtolower(str_replace([' ','_'], '-', $rec['status']));
                    ?>
                    <tr data-search="<?php echo strtolower($rec['complainant_name'] . ' ' . $rec['subject'] . ' ' . $rec['respondent_name']); ?>">
                        <td>
                            <div class="case-num">BRGY-2026-0<?php echo $rec['id']; ?></div>
                            <span class="paper-badge">📄 Paper</span>
                        </td>
                        <td><?php echo date("M j, Y", strtotime($rec['original_filed_date'] ?: $rec['date_filed'])); ?></td>
                        <td>
                            <div class="name-cell">
                                <strong><?php echo htmlspecialchars($rec['complainant_name']); ?></strong>
                                <span>vs. <?php echo htmlspecialchars($rec['respondent_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($rec['subject']); ?></td>
                        <td><span class="status-pill <?php echo $sc; ?>"><?php echo htmlspecialchars($rec['status']); ?></span></td>
                        <td style="font-size:12px;color:#64748b;"><?php echo htmlspecialchars($rec['encoded_by'] ?? 'Admin'); ?></td>
                        <td>
                            <div class="tbl-actions">
                                <a href="admin-view-complaint.php?id=<?php echo $rec['id']; ?>" class="btn-tbl view">View</a>
                                <button class="btn-tbl edit" onclick="editRecord(<?php echo $rec['id']; ?>)">Edit</button>
                                <button class="btn-tbl del"  onclick="deleteRecord(<?php echo $rec['id']; ?>, '<?php echo addslashes($rec['complainant_name']); ?>')">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <script>
        // ── File names display ──
        function showFileNames(input) {
            const names = Array.from(input.files).map(f => f.name).join(', ');
            document.getElementById('fileNames').textContent = names || '';
        }

        // ── Table search filter ──
        function filterTable(q) {
            const rows = document.querySelectorAll('#fmTable tbody tr');
            rows.forEach(r => {
                r.style.display = r.dataset.search.includes(q.toLowerCase()) ? '' : 'none';
            });
        }

        // ── Submit (create or update) ──
        function submitRecord() {
            const editId = document.getElementById('editId').value;
            const isEdit = editId !== '';

            const origDate = document.getElementById('fOrigDate').value;
            const subject  = document.getElementById('fSubject').value;
            const compName = document.getElementById('fCompName').value.trim();
            const respName = document.getElementById('fRespName').value.trim();
            const narrative= document.getElementById('fNarrative').value.trim();

            if (!origDate) { showAlert('Please select the date filed on the paper form.', 'error'); return; }
            if (!subject)  { showAlert('Please select a subject/case type.', 'error'); return; }
            if (!compName) { showAlert('Please enter the complainant name.', 'error'); return; }
            if (!respName) { showAlert('Please enter the respondent name.', 'error'); return; }
            if (!narrative){ showAlert('Please enter the complaint narrative.', 'error'); return; }

            const btn = document.getElementById('encodeBtn');
            btn.disabled = true;
            btn.textContent = isEdit ? 'Saving changes…' : 'Saving to system…';

            const fd = new FormData();
            fd.append('action',                isEdit ? 'update' : 'create');
            if (isEdit) fd.append('id',        editId);
            fd.append('original_filed_date',   origDate);
            fd.append('subject',               subject);
            fd.append('complainant_name',      compName);
            fd.append('complainant_address',   document.getElementById('fCompAddr').value.trim());
            fd.append('complainant_contact',   document.getElementById('fCompContact').value.trim());
            fd.append('respondent_name',       respName);
            fd.append('respondent_address',    document.getElementById('fRespAddr').value.trim());
            fd.append('narrative',             narrative);
            fd.append('status',                document.getElementById('fStatus').value);

            // Evidence files (create only)
            if (!isEdit) {
                const files = document.getElementById('fEvidence').files;
                for (let f of files) fd.append('evidence[]', f);
            }

            fetch('save_paper_complaint.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showToast(isEdit ? 'Record updated successfully!' : 'Record saved to system!');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showAlert(d.error || 'Failed to save.', 'error');
                        btn.disabled = false;
                        btn.textContent = isEdit ? '💾 Save Changes' : '📁 Save to System';
                    }
                })
                .catch(() => {
                    showAlert('Network error. Please try again.', 'error');
                    btn.disabled = false;
                    btn.textContent = isEdit ? '💾 Save Changes' : '📁 Save to System';
                });
        }

        // ── Edit ──
        function editRecord(id) {
            const fd = new FormData();
            fd.append('action', 'fetch');
            fd.append('id', id);
            fetch('save_paper_complaint.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (!d.success) { showToast('Could not load record.', '#dc2626'); return; }
                    const rec = d.record;
                    document.getElementById('editId').value      = rec.id;
                    document.getElementById('fOrigDate').value   = rec.original_filed_date || rec.date_filed;
                    document.getElementById('fSubject').value    = rec.subject;
                    document.getElementById('fCompName').value   = rec.complainant_name;
                    document.getElementById('fCompAddr').value   = rec.complainant_address;
                    document.getElementById('fCompContact').value= rec.complainant_contact;
                    document.getElementById('fRespName').value   = rec.respondent_name;
                    document.getElementById('fRespAddr').value   = rec.respondent_address;
                    document.getElementById('fNarrative').value  = rec.narrative;
                    document.getElementById('fStatus').value     = rec.status;
                    // UI
                    document.getElementById('formTitle').textContent  = '✏️ Edit Paper Record';
                    document.getElementById('editModeBar').classList.add('show');
                    document.getElementById('editModeLabel').textContent = `Editing BRGY-2026-0${rec.id}`;
                    document.getElementById('encodeBtn').textContent    = '💾 Save Changes';
                    document.getElementById('cancelEditBtn').style.display = 'block';
                    // Scroll form into view
                    document.querySelector('.fm-form-card').scrollIntoView({ behavior:'smooth' });
                });
        }

        function cancelEdit() {
            document.getElementById('editId').value = '';
            document.getElementById('fOrigDate').value   = '';
            document.getElementById('fSubject').value    = '';
            document.getElementById('fCompName').value   = '';
            document.getElementById('fCompAddr').value   = '';
            document.getElementById('fCompContact').value= '';
            document.getElementById('fRespName').value   = '';
            document.getElementById('fRespAddr').value   = '';
            document.getElementById('fNarrative').value  = '';
            document.getElementById('fStatus').value     = 'Pending';
            document.getElementById('formTitle').textContent     = '📝 Encode Paper Record';
            document.getElementById('editModeBar').classList.remove('show');
            document.getElementById('encodeBtn').textContent     = '📁 Save to System';
            document.getElementById('encodeBtn').disabled        = false;
            document.getElementById('cancelEditBtn').style.display = 'none';
            document.getElementById('fileNames').textContent     = '';
        }

        // ── Delete ──
        function deleteRecord(id, name) {
            if (!confirm(`Delete the record for "${name}"? This cannot be undone.`)) return;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', id);
            fetch('save_paper_complaint.php', { method:'POST', body:fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { showToast('Record deleted.'); setTimeout(() => location.reload(), 800); }
                    else showToast('Failed to delete.', '#dc2626');
                });
        }

        // ── Alert (inside form) ──
        function showAlert(msg, type) {
            const el = document.getElementById('formAlert');
            el.textContent = msg;
            el.className = `fm-alert ${type} show`;
            setTimeout(() => el.classList.remove('show'), 4000);
        }

        // ── Toast ──
        function showToast(msg, bg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.background = bg || '#1B4332';
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
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
