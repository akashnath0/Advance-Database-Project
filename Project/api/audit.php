<?php
// ============================================================
// API: Audit — read-only provenance (MySQL version)
// ============================================================
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
if (isViewer()) jsonResponse(['error' => 'Forbidden: Viewer access restricted'], 403);

$table  = sanitize($_GET['table']  ?? '');
$record = sanitize($_GET['record'] ?? '');
$op     = sanitize($_GET['op']     ?? '');
$limit  = min(intval($_GET['limit'] ?? 50), 200);

$auditMap = [
    'Passenger'   => ['table'=>'Passenger_Audit',   'id_col'=>'passenger_id'],
    'Payment'     => ['table'=>'Payment_Audit',      'id_col'=>'payment_id'],
    'Route'       => ['table'=>'Route_Audit',        'id_col'=>'route_id'],
    'Schedule'    => ['table'=>'Schedule_Audit',     'id_col'=>'schedule_id'],
    'Employee'    => ['table'=>'Employee_Audit',     'id_col'=>'emp_id'],
    'Train'       => ['table'=>'Train_Audit',        'id_col'=>'train_id'],
    'Coach'       => ['table'=>'Coach_Audit',        'id_col'=>'coach_id'],
    'Booking'     => ['table'=>'Booking_Audit',      'id_col'=>'booking_id'],
    'Maintenance' => ['table'=>'Maintenance_Audit',  'id_col'=>'maintenance_id'],
];

if ($table && isset($auditMap[$table])) {
    $meta = $auditMap[$table];
    $where = []; $params = [];
    if ($record) { $where[] = "`{$meta['id_col']}` = :rec"; $params[':rec'] = $record; }
    if ($op)     { $where[] = "operation = :op";             $params[':op']  = strtoupper($op); }
    $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $rows = db()->fetchAll("SELECT * FROM `{$meta['table']}` $whereStr ORDER BY operation_time DESC LIMIT $limit", $params);
    jsonResponse(['table' => $table, 'count' => count($rows), 'rows' => $rows]);
    exit;
}

// Summary across all tables
$parts = [];
foreach ($auditMap as $label => $meta) {
    $parts[] = "SELECT '$label' AS source_table, COUNT(*) AS total,
        SUM(operation='INSERT') AS inserts,
        SUM(operation='UPDATE') AS updates,
        SUM(operation='DELETE') AS deletes
        FROM `{$meta['table']}`";
}
$rows = db()->fetchAll("SELECT * FROM (" . implode(" UNION ALL ", $parts) . ") AS audit_summary ORDER BY total DESC");
jsonResponse(['summary' => $rows]);
