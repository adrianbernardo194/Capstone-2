<?php
/**
 * common_complaint_stats.php
 *
 * Computes the most common AI-categorized complaint type(s).
 * Include this file or call it via fetch() to display stats
 * on the admin dashboard (e.g. "Most common complaint this month: Pagnanakaw (Art. 309) - 12 cases").
 */

require_once 'session_check_admin.php';
include 'db.php';
header('Content-Type: application/json');

// Optional date range filter: ?from=YYYY-MM-DD&to=YYYY-MM-DD
$from = $_GET['from'] ?? null;
$to   = $_GET['to']   ?? null;

$where = "ai_processed = 1 AND ai_category IS NOT NULL AND ai_category != ''";
if ($from) $where .= " AND date_filed >= '" . $conn->real_escape_string($from) . "'";
if ($to)   $where .= " AND date_filed <= '" . $conn->real_escape_string($to)   . "'";

$sql = "
    SELECT ai_category, COUNT(*) as total
    FROM complaints
    WHERE $where
    GROUP BY ai_category
    ORDER BY total DESC
";

$result = $conn->query($sql);
$stats  = [];
$grand_total = 0;

while ($row = $result->fetch_assoc()) {
    $stats[] = [
        'category' => $row['ai_category'],
        'count'    => (int)$row['total'],
    ];
    $grand_total += (int)$row['total'];
}

// Top category
$top = $stats[0] ?? null;

echo json_encode([
    'total_analyzed'  => $grand_total,
    'top_category'    => $top,
    'breakdown'       => $stats,
]);
?>
