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
?>
