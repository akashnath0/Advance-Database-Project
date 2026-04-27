<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

$totalPassengers  = db()->count('Passenger');
$totalTrains      = db()->count('Train');
$totalBookings    = db()->count('Booking');
$totalRevenue     = db()->fetchOne("SELECT IFNULL(SUM(amount),0) AS total FROM Payment WHERE status='PAID'");
$activeTrains     = db()->count('Train', "status='ACTIVE'");
$maintenanceCount = db()->count('Train', "status='MAINTENANCE'");

$auditCount = db()->fetchOne("SELECT
    (SELECT COUNT(*) FROM Passenger_Audit) +
    (SELECT COUNT(*) FROM Booking_Audit) +
    (SELECT COUNT(*) FROM Train_Audit) +
    (SELECT COUNT(*) FROM Payment_Audit) AS total");
$revenue    = $totalRevenue['total']  ?? 0;
$auditTotal = $auditCount['total']    ?? 0;

$recentBookingsSql = "
    SELECT b.booking_id, CONCAT(p.first_name,' ',p.last_name) AS passenger,
           t.train_name, b.travel_date, b.seat_no, py.amount, py.status
    FROM Booking b
    JOIN Passenger p  ON b.passenger_id = p.passenger_id
    JOIN Train     t  ON b.train_id     = t.train_id
    JOIN Payment  py  ON b.payment_id   = py.payment_id
";
$recentBookingsParams = [];
if (!isStaff()) {
    $pid = $_SESSION['passenger_id'] ?? 'NONE';
    $recentBookingsSql .= " WHERE b.passenger_id = :pid ";
    $recentBookingsParams = [':pid' => $pid];
}
$recentBookingsSql .= " ORDER BY b.booking_time DESC LIMIT 8";
$recentBookings = db()->fetchAll($recentBookingsSql, $recentBookingsParams);

$recentAudit = db()->fetchAll("
    SELECT 'Passenger' AS tbl, operation, passenger_id AS rec_id, performed_by, operation_time FROM Passenger_Audit
    UNION ALL
    SELECT 'Booking', operation, booking_id, performed_by, operation_time FROM Booking_Audit
    UNION ALL
    SELECT 'Train', operation, train_id, performed_by, operation_time FROM Train_Audit
    ORDER BY operation_time DESC
    LIMIT 10
");

include '../includes/layout_head.php';
?>
<div class="stats-grid">
  <div class="stat-card green">
    <div class="stat-icon">👥</div>
    <div class="stat-info">
      <div class="stat-label">Total Passengers</div>
      <div class="stat-value" data-count="<?= $totalPassengers ?>">0</div>
      <div class="stat-sub">Registered passengers</div>
    </div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">🚂</div>
    <div class="stat-info">
      <div class="stat-label">Total Trains</div>
      <div class="stat-value" data-count="<?= $totalTrains ?>">0</div>
      <div class="stat-sub"><?= $activeTrains ?> active / <?= $maintenanceCount ?> maintenance</div>
    </div>
  </div>
  <div class="stat-card amber">
    <div class="stat-icon">🎫</div>
    <div class="stat-info">
      <div class="stat-label">Total Bookings</div>
      <div class="stat-value" data-count="<?= $totalBookings ?>">0</div>
      <div class="stat-sub">All-time bookings</div>
    </div>
  </div>
  <div class="stat-card purple">
    <div class="stat-icon">💳</div>
    <div class="stat-info">
      <div class="stat-label">Total Revenue</div>
      <div class="stat-value" style="font-size:22px">৳<?= number_format($revenue,0) ?></div>
      <div class="stat-sub">From paid bookings</div>
    </div>
  </div>
  <div class="stat-card cyan">
    <div class="stat-icon">🔧</div>
    <div class="stat-info">
      <div class="stat-label">In Maintenance</div>
      <div class="stat-value" data-count="<?= $maintenanceCount ?>">0</div>
      <div class="stat-sub">Trains under service</div>
    </div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon">📋</div>
    <div class="stat-info">
      <div class="stat-label">Audit Events</div>
      <div class="stat-value" data-count="<?= $auditTotal ?>">0</div>
      <div class="stat-sub">Provenance records</div>
    </div>
  </div>
</div>

<div class="grid-2" style="gap:20px">
  <?php if (isStaff()): ?>
  <div class="card">
    <div class="card-header">
      <div class="card-title">🎫 Recent Bookings</div>
      <a href="bookings.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>ID</th><th>Passenger</th><th>Train</th><th>Seat</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($recentBookings)): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">💭</div><p>No bookings yet</p></div></td></tr>
          <?php else: foreach ($recentBookings as $b): ?>
          <tr>
            <td class="td-mono"><?= $b['booking_id'] ?></td>
            <td><?= htmlspecialchars($b['passenger']) ?></td>
            <td><?= htmlspecialchars($b['train_name']) ?></td>
            <td><?= $b['seat_no'] ?></td>
            <td>৳<?= number_format($b['amount'],0) ?></td>
            <td><span class="badge <?= statusBadge($b['status']) ?>"><?= $b['status'] ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title">📋 Live Audit Feed</div>
      <a href="audit.php" class="btn btn-ghost btn-sm">Explore</a>
    </div>
    <div style="padding:12px 8px;max-height:420px;overflow-y:auto">
      <?php if (empty($recentAudit)): ?>
      <div class="empty-state"><div class="empty-icon">🔍</div><p>No audit data yet. Make some changes!</p></div>
      <?php else: foreach ($recentAudit as $a): ?>
      <div style="display:flex;align-items:center;gap:10px;padding:8px 12px;border-bottom:1px solid var(--border)">
        <span class="badge badge-<?= strtolower($a['operation']) ?>"><?= $a['operation'] ?></span>
        <div style="flex:1">
          <div style="font-size:13px;font-weight:600"><?= $a['tbl'] ?> <span class="td-mono">#<?= $a['rec_id'] ?></span></div>
          <div class="text-muted text-sm">by <?= htmlspecialchars($a['performed_by']) ?></div>
        </div>
        <div class="text-muted text-sm"><?= substr($a['operation_time'],0,16) ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
  <div class="card" style="grid-column:1/-1">
    <div class="card-header">
      <div class="card-title">🎫 My Recent Bookings</div>
      <a href="bookings.php" class="btn btn-ghost btn-sm">View All</a>
    </div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>ID</th><th>Passenger</th><th>Train</th><th>Seat</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($recentBookings)): ?>
          <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">💭</div><p>You have no recent bookings</p></div></td></tr>
          <?php else: foreach ($recentBookings as $b): ?>
          <tr>
            <td class="td-mono"><?= $b['booking_id'] ?></td>
            <td><?= htmlspecialchars($b['passenger']) ?></td>
            <td><?= htmlspecialchars($b['train_name']) ?></td>
            <td><?= $b['seat_no'] ?></td>
            <td>৳<?= number_format($b['amount'],0) ?></td>
            <td><span class="badge <?= statusBadge($b['status']) ?>"><?= $b['status'] ?></span></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include '../includes/layout_foot.php'; ?>
