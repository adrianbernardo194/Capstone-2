<?php
// session_check_admin.php
// Include at the very top of every admin page.
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$session_admin_name = $_SESSION['user_name'];
?>
