<?php
/**
 * audit.php
 * Include this file in any admin PHP file to enable audit logging.
 * Usage: log_audit($conn, $action, $module, $description, $target_id, $target_label, $old_value, $new_value);
 *
 * Actions (use these exact strings for consistency):
 *   LOGIN            - admin logged in
 *   LOGOUT           - admin logged out
 *   STATUS_UPDATE    - complaint status changed
 *   LUPON_ASSIGN     - lupon panel assigned to complaint
 *   RESCHEDULE       - hearing rescheduled
 *   HOLIDAY_ADD      - holiday/blocked date added
 *   HOLIDAY_DELETE   - holiday/blocked date removed
 *   WINDOW_UPDATE    - booking window changed
 *   PAPER_CREATE     - paper-based record encoded
 *   PAPER_UPDATE     - paper-based record edited
 *   PAPER_DELETE     - paper-based record deleted
 *   LUPON_ADD        - lupon member added
 *   LUPON_EDIT       - lupon member edited
 *   LUPON_DEACTIVATE - lupon member deactivated
 *   COMPLAINT_VIEW   - admin viewed a complaint (optional, can be noisy)
 *
 * Modules:
 *   Complaints | Appointments | Calendar | File Maintenance
 *   Committee | Authentication | System
 */

function log_audit(
    $conn,
    string $action,
    string $module,
    string $description,
    ?int   $target_id    = null,
    ?string $target_label = null,
    ?string $old_value   = null,
    ?string $new_value   = null
): void {
    // Get admin info from session
    $admin_id       = isset($_SESSION['user_id'])   ? (int)$_SESSION['user_id']   : null;
    $admin_username = isset($_SESSION['user_name']) ? $_SESSION['user_name']       : 'System';

    // Get real IP — handle proxies
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_CLIENT_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
    // Take first IP if comma-separated
    $ip = trim(explode(',', $ip)[0]);

    // Escape everything
    $action_e   = mysqli_real_escape_string($conn, $action);
    $module_e   = mysqli_real_escape_string($conn, $module);
    $desc_e     = mysqli_real_escape_string($conn, $description);
    $label_e    = mysqli_real_escape_string($conn, $target_label ?? '');
    $old_e      = mysqli_real_escape_string($conn, $old_value    ?? '');
    $new_e      = mysqli_real_escape_string($conn, $new_value    ?? '');
    $user_e     = mysqli_real_escape_string($conn, $admin_username);
    $ip_e       = mysqli_real_escape_string($conn, $ip);
    $tid        = $target_id !== null ? (int)$target_id : 'NULL';
    $aid        = $admin_id  !== null ? (int)$admin_id  : 'NULL';

    $conn->query("
        INSERT INTO audit_trail
            (admin_id, admin_username, action, module,
             target_id, target_label, old_value, new_value,
             description, ip_address)
        VALUES
            ($aid, '$user_e', '$action_e', '$module_e',
             $tid, '$label_e', '$old_e', '$new_e',
             '$desc_e', '$ip_e')
    ");
}
?>
