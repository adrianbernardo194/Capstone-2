<?php
require_once 'session_check_admin.php';
include 'db.php';
include 'audit.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$action = $_POST['action'] ?? 'create';

// ── CREATE new paper record ──────────────────────────────────────────────────
if ($action === 'create') {
    $subject              = mysqli_real_escape_string($conn, trim($_POST['subject']          ?? ''));
    $original_filed_date  = mysqli_real_escape_string($conn, trim($_POST['original_filed_date'] ?? ''));
    $complainant_name     = mysqli_real_escape_string($conn, trim($_POST['complainant_name']  ?? ''));
    $complainant_address  = mysqli_real_escape_string($conn, trim($_POST['complainant_address']?? ''));
    $complainant_contact  = mysqli_real_escape_string($conn, trim($_POST['complainant_contact']?? ''));
    $respondent_name      = mysqli_real_escape_string($conn, trim($_POST['respondent_name']   ?? ''));
    $respondent_address   = mysqli_real_escape_string($conn, trim($_POST['respondent_address']?? ''));
    $narrative            = mysqli_real_escape_string($conn, trim($_POST['narrative']         ?? ''));
    $status               = mysqli_real_escape_string($conn, trim($_POST['status']            ?? 'Pending'));
    $admin_id             = (int)$_SESSION['user_id'];

    // Validate required
    if (!$subject || !$original_filed_date || !$complainant_name || !$respondent_name || !$narrative) {
        echo json_encode(['error' => 'Please fill in all required fields.']); exit;
    }

    // Allowed statuses
    $allowed = ['Pending','Approved','In Process','Rescheduled','Cannot Be Handled','Completed'];
    if (!in_array($status, $allowed)) $status = 'Pending';

    // Handle scanned evidence upload (optional)
    $evidence_pic = '';
    if (!empty($_FILES['evidence']) && $_FILES['evidence']['error'][0] !== UPLOAD_ERR_NO_FILE) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $filenames  = [];
        foreach ($_FILES['evidence']['tmp_name'] as $i => $tmp) {
            if ($_FILES['evidence']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext      = strtolower(pathinfo($_FILES['evidence']['name'][$i], PATHINFO_EXTENSION));
            $newname  = 'paper_' . time() . '_' . $i . '.' . $ext;
            if (move_uploaded_file($tmp, $upload_dir . $newname)) {
                $filenames[] = $newname;
            }
        }
        $evidence_pic = implode(',', $filenames);
    }

    $ep = mysqli_real_escape_string($conn, $evidence_pic);

    $conn->query("
        INSERT INTO complaints
            (subject, date_filed, complainant_name, complainant_address,
             complainant_contact, respondent_name, respondent_address,
             narrative, evidence_pic, status,
             is_paper_based, encoded_by_admin_id, encoded_at, original_filed_date)
        VALUES
            ('$subject', '$original_filed_date', '$complainant_name', '$complainant_address',
             '$complainant_contact', '$respondent_name', '$respondent_address',
             '$narrative', '$ep', '$status',
             1, $admin_id, NOW(), '$original_filed_date')
    ");

    if ($conn->insert_id) {
        $cid  = $conn->insert_id;
        $msg  = mysqli_real_escape_string($conn, "Paper record encoded: $complainant_name vs $respondent_name ($subject)");
        $conn->query("INSERT INTO notifications (complaint_id, complainant_name, subject, message, type, is_read)
                      VALUES ($cid, '$complainant_name', '$subject', '$msg', 'paper_record', 0)");
        log_audit($conn, 'PAPER_CREATE', 'File Maintenance',
            "Encoded paper record for $complainant_name vs $respondent_name. Subject: $subject. Filed: $original_filed_date.",
            $cid, "BRGY-2026-0$cid — $complainant_name vs $respondent_name",
            null, "Subject: $subject | Status: $status | Filed: $original_filed_date");
        echo json_encode(['success' => true, 'complaint_id' => $cid]);
    } else {
        echo json_encode(['error' => 'Failed to save record. ' . $conn->error]);
    }
}

// ── UPDATE existing paper record ─────────────────────────────────────────────
if ($action === 'update') {
    $id      = (int)($_POST['id'] ?? 0);
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']          ?? ''));
    $ofd     = mysqli_real_escape_string($conn, trim($_POST['original_filed_date'] ?? ''));
    $cn      = mysqli_real_escape_string($conn, trim($_POST['complainant_name']  ?? ''));
    $ca      = mysqli_real_escape_string($conn, trim($_POST['complainant_address']?? ''));
    $cc      = mysqli_real_escape_string($conn, trim($_POST['complainant_contact']?? ''));
    $rn      = mysqli_real_escape_string($conn, trim($_POST['respondent_name']   ?? ''));
    $ra      = mysqli_real_escape_string($conn, trim($_POST['respondent_address']?? ''));
    $narr    = mysqli_real_escape_string($conn, trim($_POST['narrative']         ?? ''));
    $status  = mysqli_real_escape_string($conn, trim($_POST['status']            ?? 'Pending'));

    if (!$id) { echo json_encode(['error' => 'Invalid record ID.']); exit; }

    // Get old values before update
    $old = $conn->query("SELECT complainant_name, respondent_name, subject, status FROM complaints WHERE id=$id")->fetch_assoc();

    $conn->query("
        UPDATE complaints
        SET subject='$subject', date_filed='$ofd', original_filed_date='$ofd',
            complainant_name='$cn', complainant_address='$ca',
            complainant_contact='$cc', respondent_name='$rn',
            respondent_address='$ra', narrative='$narr', status='$status'
        WHERE id=$id AND is_paper_based=1
    ");

    log_audit($conn, 'PAPER_UPDATE', 'File Maintenance',
        "Updated paper record BRGY-2026-0$id ($cn vs $rn).",
        $id, "BRGY-2026-0$id — $cn vs $rn",
        "Subject: " . ($old['subject']??'') . " | Status: " . ($old['status']??''),
        "Subject: $subject | Status: $status");

    echo json_encode(['success' => true]);
}

// ── DELETE paper record ───────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['error' => 'Invalid ID.']); exit; }
    // Get record before deleting
    $old = $conn->query("SELECT complainant_name, respondent_name, subject FROM complaints WHERE id=$id")->fetch_assoc();
    $conn->query("DELETE FROM complaints WHERE id=$id AND is_paper_based=1");
    log_audit($conn, 'PAPER_DELETE', 'File Maintenance',
        "Deleted paper record BRGY-2026-0$id (" . ($old['complainant_name']??'') . " vs " . ($old['respondent_name']??'') . ").",
        $id, "BRGY-2026-0$id",
        "Subject: " . ($old['subject']??'') . " | Complainant: " . ($old['complainant_name']??''),
        null);
    echo json_encode(['success' => true]);
}

// ── FETCH single record for edit form ────────────────────────────────────────
if ($action === 'fetch') {
    $id  = (int)($_POST['id'] ?? 0);
    $row = $conn->query("SELECT * FROM complaints WHERE id=$id AND is_paper_based=1")->fetch_assoc();
    if ($row) echo json_encode(['success' => true, 'record' => $row]);
    else      echo json_encode(['error' => 'Record not found.']);
}
?>
