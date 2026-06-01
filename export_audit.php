<?php
require_once 'session_check_admin.php';
include 'db.php';

// Build same filters as the main page
$filter_module = $_GET['module']    ?? '';
$filter_action = $_GET['action']    ?? '';
$filter_admin  = $_GET['admin']     ?? '';
$filter_date   = $_GET['date']      ?? '';
$filter_search = $_GET['search']    ?? '';

$where = ['1=1'];
if ($filter_module) $where[] = "module = '" . mysqli_real_escape_string($conn, $filter_module) . "'";
if ($filter_action) $where[] = "action = '" . mysqli_real_escape_string($conn, $filter_action) . "'";
if ($filter_admin)  $where[] = "admin_username LIKE '%" . mysqli_real_escape_string($conn, $filter_admin) . "%'";
if ($filter_date)   $where[] = "DATE(created_at) = '" . mysqli_real_escape_string($conn, $filter_date) . "'";
if ($filter_search) $where[] = "(description LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%'
                                  OR target_label LIKE '%" . mysqli_real_escape_string($conn, $filter_search) . "%')";
$where_sql = implode(' AND ', $where);

$logs_res = $conn->query("SELECT * FROM audit_trail WHERE $where_sql ORDER BY created_at DESC LIMIT 5000");

// Output CSV headers
$filename = 'audit_trail_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

$out = fopen('php://output', 'w');

// CSV column headers
fputcsv($out, [
    'ID', 'Timestamp', 'Admin', 'Action', 'Module',
    'Target', 'Description', 'Old Value', 'New Value', 'IP Address'
]);

while ($row = $logs_res->fetch_assoc()) {
    fputcsv($out, [
        $row['id'],
        $row['created_at'],
        $row['admin_username'],
        $row['action'],
        $row['module'],
        $row['target_label'] ?? '',
        $row['description'],
        $row['old_value'] ?? '',
        $row['new_value'] ?? '',
        $row['ip_address'] ?? '',
    ]);
}

fclose($out);
exit;
?>
