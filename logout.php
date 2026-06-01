<?php
session_start();

// Log before destroying session
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    include 'db.php';
    include 'audit.php';
    log_audit($conn, 'LOGOUT', 'Authentication',
        "Admin '{$_SESSION['user_name']}' logged out.",
        $_SESSION['user_id'] ?? null,
        $_SESSION['user_name'] ?? 'Admin',
        null, null);
}

session_destroy();
header('Location: login.php');
exit;
?>
