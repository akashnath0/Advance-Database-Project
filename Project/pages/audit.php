<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireStaff();

$pageTitle  = 'Audit Trail — Data Provenance';
$activePage = 'audit';

$filterTable = sanitize($_GET['table'] ?? '');
$filterId    = sanitize($_GET['id']    ?? '');
$filterOp    = sanitize($_GET['op']    ?? '');
$filterUser  = sanitize($_GET['user']  ?? '');

$auditMeta = [
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

// Detailed timeline for a specific record
$detailedRows = [];
if ($filterTable && $filterId && isset($auditMeta[$filterTable])) {
    $meta = $auditMeta[$filterTable];
    $detailedRows = db()->fetchAll(
        "SELECT * FROM `{$meta['table']}` WHERE `{$meta['id_col']}` = :id ORDER BY operation_time ASC",
        [':id' => $filterId]
    );
}

// Build combined UNION
$unionParts = [];
foreach ($auditMeta as $label => $meta) {
    $unionParts[] = "SELECT '$label' AS source_table, audit_id, operation, `{$meta['id_col']}` AS record_id, performed_by, ip_address, operation_time FROM `{$meta['table']}`";
}
$unionSql = implode(" UNION ALL ", $unionParts);

$whereParts = []; $params = [];
if ($filterTable && isset($auditMeta[$filterTable])) { $whereParts[] = "source_table = :tbl"; $params[':tbl'] = $filterTable; }
if ($filterId)   { $whereParts[] = "record_id = :rid";                                          $params[':rid'] = $filterId; }
if ($filterOp)   { $whereParts[] = "operation = :op";                                           $params[':op']  = strtoupper($filterOp); }
if ($filterUser) { $whereParts[] = "performed_by LIKE :usr";                                    $params[':usr'] = "%$filterUser%"; }
$whereStr = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

$allAudit = db()->fetchAll("SELECT * FROM ($unionSql) AS combined $whereStr ORDER BY operation_time DESC LIMIT 100", $params);

$insertCount = count(array_filter($allAudit, fn($r)=>$r['operation']==='INSERT'));
$updateCount = count(array_filter($allAudit, fn($r)=>$r['operation']==='UPDATE'));
$deleteCount = count(array_filter($allAudit, fn($r)=>$r['operation']==='DELETE'));

include '../includes/layout_head.php';
?>
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card green"><div class="stat-icon">📋</div><div class="stat-info"><div class="stat-label">Total Events</div><div class="stat-value"><?= count($allAudit) ?></div></div></div>
  <div class="stat-card green"><div class="stat-icon">➕</div><div class="stat-info"><div class="stat-label">Inserts</div><div class="stat-value"><?= $insertCount ?></div></div></div>
  <div class="stat-card blue"><div class="stat-icon">✏️</div><div class="stat-info"><div class="stat-label">Updates</div><div class="stat-value"><?= $updateCount ?></div></div></div>
  <div class="stat-card red"><div class="stat-icon">🗑️</div><div class="stat-info"><div class="stat-label">Deletes</div><div class="stat-value"><?= $deleteCount ?></div></div></div>
</div>

<?php if (!empty($detailedRows)): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <div class="card-title">🔍 Provenance Timeline — <?= htmlspecialchars($filterTable) ?> #<?= htmlspecialchars($filterId) ?>
      <span class="badge badge-info" style="margin-left:8px"><?= count($detailedRows) ?> Events</span>
    </div>
    <a href="audit.php" class="btn btn-ghost btn-sm">← Clear Filter</a>
  </div>
  <div class="card-body">
    <div class="provenance-timeline">
      <?php foreach ($detailedRows as $row):
        $opClass = strtolower($row['operation']); ?>
      <div class="timeline-item <?= $opClass ?>">
        <div class="timeline-dot"></div>
        <div class="timeline-card">
          <div class="tc-header">
            <span class="badge badge-<?= $opClass ?>"><?= $row['operation'] ?></span>
            <span style="font-size:12px;color:var(--text-muted)">
              👤 <?= htmlspecialchars($row['performed_by']??'—') ?>
              &nbsp;•&nbsp; 🌐 <?= htmlspecialchars($row['ip_address']??'N/A') ?>
              &nbsp;•&nbsp; 🕐 <?= substr($row['operation_time'],0,19) ?>
            </span>
          </div>
          <?php if ($row['operation']==='UPDATE'):
            $cols = array_keys($row);
            $oldCols = array_filter($cols, fn($c)=>str_starts_with($c,'old_'));
            foreach ($oldCols as $oldCol):
              $suffix = substr($oldCol,4);
              $newCol = 'new_'.$suffix;
              $oldVal = $row[$oldCol]??null; $newVal = $row[$newCol]??null;
              if ($oldVal==$newVal) continue; ?>
          <div class="change-row">
            <span class="field-name"><?= str_replace('_',' ',strtoupper($suffix)) ?></span>
            <span class="old-val"><?= $oldVal!==null?htmlspecialchars($oldVal):'NULL' ?></span>
            <span class="arrow">→</span>
            <span class="new-val"><?= $newVal!==null?htmlspecialchars($newVal):'NULL' ?></span>
          </div>
          <?php endforeach;
          elseif ($row['operation']==='INSERT'): ?>
          <div style="font-size:12px;color:var(--green-light)">✅ New record created</div>
          <?php else: ?>
          <div style="font-size:12px;color:var(--red-light)">🗑️ Record permanently deleted</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div class="card-title">📋 All Audit Events</div><a href="audit.php" class="btn btn-ghost btn-sm">🔄 Reset</a></div>
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <form method="GET" action="audit.php">
      <div class="filters-bar">
        <select name="table" class="form-control" style="max-width:160px">
          <option value="">All Tables</option>
          <?php foreach (array_keys($auditMeta) as $tbl): ?>
          <option value="<?= $tbl ?>" <?= $filterTable===$tbl?'selected':'' ?>><?= $tbl ?></option>
          <?php endforeach; ?>
        </select>
        <select name="op" class="form-control" style="max-width:140px">
          <option value="">All Operations</option>
          <option value="INSERT" <?= $filterOp==='INSERT'?'selected':'' ?>>INSERT</option>
          <option value="UPDATE" <?= $filterOp==='UPDATE'?'selected':'' ?>>UPDATE</option>
          <option value="DELETE" <?= $filterOp==='DELETE'?'selected':'' ?>>DELETE</option>
        </select>
        <input name="id"   type="text" class="form-control" placeholder="Record ID..."   style="max-width:140px" value="<?= htmlspecialchars($filterId) ?>">
        <input name="user" type="text" class="form-control" placeholder="Performed by..." style="max-width:160px" value="<?= htmlspecialchars($filterUser) ?>">
        <button type="submit" class="btn btn-blue">🔍 Filter</button>
      </div>
    </form>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>#</th><th>Table</th><th>Operation</th><th>Record ID</th><th>Performed By</th><th>IP Address</th><th>Timestamp</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($allAudit)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">🔍</div><p>No audit events matching your filters.</p></div></td></tr>
        <?php else: foreach ($allAudit as $row): ?>
        <tr>
          <td class="td-mono"><?= $row['audit_id'] ?></td>
          <td><strong><?= $row['source_table'] ?></strong></td>
          <td><span class="badge badge-<?= strtolower($row['operation']) ?>"><?= $row['operation'] ?></span></td>
          <td class="td-mono"><?= htmlspecialchars($row['record_id']) ?></td>
          <td><?= htmlspecialchars($row['performed_by']??'—') ?></td>
          <td class="td-mono"><?= htmlspecialchars($row['ip_address']??'—') ?></td>
          <td style="font-size:12px"><?= substr($row['operation_time'],0,19) ?></td>
          <td><a href="audit.php?table=<?= $row['source_table'] ?>&id=<?= $row['record_id'] ?>" class="btn btn-ghost btn-sm">🔎 Trace</a></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/layout_foot.php'; ?>
