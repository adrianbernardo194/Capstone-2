<?php
// session_check_resident.php
// Include at the very top of every resident page.
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'resident') {
    header('Location: login.php');
    exit;
}
// Convenience variables available in every resident page:
$session_resident_id   = $_SESSION['user_id'];
$session_resident_name = $_SESSION['user_name'];

// ── ID verification status ─────────────────────────────────────────────
// Looked up fresh on every page load (not cached in the session) so that
// when an admin approves/rejects an ID, the resident sees the change
// immediately instead of needing to log out and back in.
//
// Expected values: 'unverified' (never submitted), 'pending' (submitted,
// awaiting admin review), 'verified' (approved — can file complaints),
// 'rejected' (admin rejected — resident should resubmit).
$session_id_verification_status = 'unverified';

require_once 'db.php';
if (isset($conn) && $conn) {
    if ($stmt = $conn->prepare("SELECT id_verification_status FROM residents WHERE id = ? LIMIT 1")) {
        $stmt->bind_param("i", $session_resident_id);
        $stmt->execute();
        $stmt->bind_result($status);
        if ($stmt->fetch() && $status !== null && $status !== '') {
            $session_id_verification_status = $status;
        }
        $stmt->close();
    }
    // If the prepare() fails (e.g. the id_verification_status column hasn't
    // been added yet), we silently keep the 'unverified' default above
    // rather than fatally erroring out every resident page.
}

$session_id_verified = ($session_id_verification_status === 'verified');
?>
