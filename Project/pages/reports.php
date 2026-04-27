<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireStaff();

$pageTitle  = 'Reports & Analytics';
$activePage = 'reports';

$revenueByMethod = db()->fetchAll("
  SELECT method, SUM(amount) AS total, COUNT(*) AS txn_count
  FROM Payment WHERE status='PAID'
  GROUP BY method ORDER BY total DESC
");

$topTrains = db()->fetchAll("
  SELECT t.train_name, t.type, COUNT(b.booking_id) AS booking_count,
         IFNULL(SUM(py.amount),0) AS revenue
  FROM Train t
  LEFT JOIN Booking b  ON b.train_id    = t.train_id
  LEFT JOIN Payment py ON py.payment_id = b.payment_id AND py.status='PAID'
  GROUP BY t.train_id, t.train_name, t.type
  ORDER BY booking_count DESC
  LIMIT 10
");

$bookingsByMonth = db()->fetchAll("
  SELECT DATE_FORMAT(travel_date,'%b %Y') AS month_label,
         DATE_FORMAT(travel_date,'%Y-%m')  AS month_sort,
         COUNT(*) AS cnt
  FROM Booking
  GROUP BY DATE_FORMAT(travel_date,'%b %Y'), DATE_FORMAT(travel_date,'%Y-%m')
  ORDER BY month_sort DESC
  LIMIT 12
");

$maintSummary = db()->fetchAll("
  SELECT maintenance_type, COUNT(*) AS cnt
  FROM Maintenance
  GROUP BY maintenance_type ORDER BY cnt DESC
");

$topPassengers = db()->fetchAll("
  SELECT CONCAT(p.first_name,' ',p.last_name) AS name,
         COUNT(b.booking_id) AS trips,
         IFNULL(SUM(py.amount),0) AS spent
  FROM Passenger p
  LEFT JOIN Booking b  ON b.passenger_id = p.passenger_id
  LEFT JOIN Payment py ON py.payment_id  = b.payment_id AND py.status='PAID'
  GROUP BY p.passenger_id, p.first_name, p.last_name
  ORDER BY trips DESC
  LIMIT 10
");

$auditSummary = db()->fetchAll("
  SELECT 'Passenger' AS tbl, COUNT(*) AS total,
    SUM(operation='INSERT') AS inserts, SUM(operation='UPDATE') AS updates, SUM(operation='DELETE') AS deletes
  FROM Passenger_Audit
  UNION ALL SELECT 'Booking', COUNT(*), SUM(operation='INSERT'), SUM(operation='UPDATE'), SUM(operation='DELETE') FROM Booking_Audit
  UNION ALL SELECT 'Train',   COUNT(*), SUM(operation='INSERT'), SUM(operation='UPDATE'), SUM(operation='DELETE') FROM Train_Audit
  UNION ALL SELECT 'Payment', COUNT(*), SUM(operation='INSERT'), SUM(operation='UPDATE'), SUM(operation='DELETE') FROM Payment_Audit
  UNION ALL SELECT 'Employee',COUNT(*), SUM(operation='INSERT'), SUM(operation='UPDATE'), SUM(operation='DELETE') FROM Employee_Audit
  UNION ALL SELECT 'Maintenance',COUNT(*),SUM(operation='INSERT'),SUM(operation='UPDATE'),SUM(operation='DELETE') FROM Maintenance_Audit
  ORDER BY total DESC
");

$maxBookings  = max(array_column($topTrains,     'booking_count') ?: [1]);
$maxRevMethod = max(array_column($revenueByMethod,'total')        ?: [1]);

include '../includes/layout_head.php';
?>
<div class="grid-2" style="gap:20px">

  <!-- Revenue by Method -->
  <div class="card">
    <div class="card-header"><div class="card-title">💳 Revenue by Payment Method</div></div>
    <div class="card-body">
      <?php if (empty($revenueByMethod)): ?><div class="empty-state"><p>No payment data</p></div><?php else:
      foreach ($revenueByMethod as $r):
        $pct = round(($r['total'] / $maxRevMethod) * 100);
        $colors=['bKash'=>'var(--green-light)','Nagad'=>'var(--amber-light)','Cash'=>'var(--text-secondary)','Card'=>'var(--blue-light)','Rocket'=>'var(--purple)'];
        $color=$colors[$r['method']]??'var(--cyan)'; ?>
      <div style="margin-bottom:16px">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px">
          <span style="font-size:14px;font-weight:600"><?= htmlspecialchars($r['method']) ?></span>
          <span class="text-muted text-sm"><?= $r['txn_count'] ?> txn • ৳<?= number_format($r['total'],0) ?></span>
        </div>
        <div style="background:var(--border);border-radius:6px;height:10px;overflow:hidden">
          <div style="width:<?= $pct ?>%;height:100%;background:<?= $color ?>;border-radius:6px;transition:width .8s ease"></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Top Trains -->
  <div class="card">
    <div class="card-header"><div class="card-title">🚂 Top Trains by Bookings</div></div>
    <div class="card-body">
      <?php if (empty($topTrains)): ?><div class="empty-state"><p>No train data</p></div><?php else:
      foreach ($topTrains as $i => $t):
        $pct = $maxBookings > 0 ? round(($t['booking_count'] / $maxBookings) * 100) : 0; ?>
      <div style="margin-bottom:14px;display:flex;align-items:center;gap:12px">
        <div style="width:24px;height:24px;border-radius:50%;background:var(--bg-hover);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--text-muted);flex-shrink:0"><?= $i+1 ?></div>
        <div style="flex:1">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px">
            <span style="font-size:13px;font-weight:600"><?= htmlspecialchars($t['train_name']) ?></span>
            <span class="text-muted text-sm"><?= $t['booking_count'] ?> bookings</span>
          </div>
          <div style="background:var(--border);border-radius:4px;height:6px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--blue),var(--blue-light));border-radius:4px"></div>
          </div>
        </div>
        <div style="font-size:12px;color:var(--green-light);font-weight:600;min-width:80px;text-align:right">৳<?= number_format($t['revenue'],0) ?></div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- Bookings by Month -->
  <div class="card">
    <div class="card-header"><div class="card-title">📅 Bookings by Month</div></div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Month</th><th>Bookings</th><th>Volume</th></tr></thead>
        <tbody>
          <?php if (empty($bookingsByMonth)): ?>
          <tr><td colspan="3"><div class="empty-state"><p>No booking data</p></div></td></tr>
          <?php else:
          $maxBkMonth = max(array_column($bookingsByMonth,'cnt') ?: [1]);
          foreach ($bookingsByMonth as $bm):
            $pct = round(($bm['cnt'] / $maxBkMonth) * 100); ?>
          <tr>
            <td><strong><?= $bm['month_label'] ?></strong></td>
            <td><?= $bm['cnt'] ?></td>
            <td style="width:200px">
              <div style="background:var(--border);border-radius:4px;height:8px;overflow:hidden">
                <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--green),var(--green-light));border-radius:4px"></div>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Maintenance Summary -->
  <div class="card">
    <div class="card-header"><div class="card-title">🔧 Maintenance Summary</div></div>
    <div class="card-body">
      <?php if (empty($maintSummary)): ?><div class="empty-state"><p>No maintenance data</p></div><?php else:
      $maxM = max(array_column($maintSummary,'cnt') ?: [1]);
      $mColors=['Scheduled'=>'var(--green-light)','Emergency'=>'var(--red-light)','Preventive'=>'var(--blue-light)','Corrective'=>'var(--amber-light)'];
      foreach ($maintSummary as $m):
        $pct=round(($m['cnt']/$maxM)*100); $col=$mColors[$m['maintenance_type']]??'var(--text-secondary)'; ?>
      <div style="margin-bottom:14px">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px">
          <span style="font-size:13px;font-weight:600;color:<?= $col ?>"><?= htmlspecialchars($m['maintenance_type']) ?></span>
          <span class="text-muted text-sm"><?= $m['cnt'] ?> records</span>
        </div>
        <div style="background:var(--border);border-radius:4px;height:8px">
          <div style="width:<?= $pct ?>%;height:100%;background:<?= $col ?>;border-radius:4px"></div>
        </div>
      </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Provenance Coverage -->
<div class="card" style="margin-top:20px">
  <div class="card-header">
    <div class="card-title">📋 Data Provenance Coverage Report</div>
    <span class="text-muted text-sm">Audit events per table</span>
  </div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Table</th><th>Total Events</th><th>INSERT</th><th>UPDATE</th><th>DELETE</th><th>Coverage</th></tr></thead>
      <tbody>
        <?php if (empty($auditSummary)): ?>
        <tr><td colspan="6"><div class="empty-state"><p>No audit data yet. Perform some operations!</p></div></td></tr>
        <?php else:
        $maxAT = max(array_column($auditSummary,'total') ?: [1]);
        foreach ($auditSummary as $a):
          $pct = $maxAT > 0 ? round(($a['total']/$maxAT)*100) : 0; ?>
        <tr>
          <td><strong><?= $a['tbl'] ?></strong></td>
          <td><?= $a['total'] ?></td>
          <td><span class="badge badge-insert"><?= $a['inserts'] ?></span></td>
          <td><span class="badge badge-update"><?= $a['updates'] ?></span></td>
          <td><span class="badge badge-delete"><?= $a['deletes'] ?></span></td>
          <td style="width:160px">
            <div style="background:var(--border);border-radius:4px;height:8px;overflow:hidden">
              <div style="width:<?= $pct ?>%;height:100%;background:linear-gradient(90deg,var(--purple),var(--blue-light));border-radius:4px"></div>
            </div>
          </td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Top Passengers -->
<div class="card" style="margin-top:20px">
  <div class="card-header"><div class="card-title">👥 Top Passengers by Travel</div></div>
  <div class="table-wrapper">
    <table>
      <thead><tr><th>Rank</th><th>Passenger</th><th>Trips</th><th>Total Spent</th></tr></thead>
      <tbody>
        <?php if (empty($topPassengers)): ?>
        <tr><td colspan="4"><div class="empty-state"><p>No passenger data</p></div></td></tr>
        <?php else: foreach ($topPassengers as $i => $p): ?>
        <tr>
          <td><?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':'#'.($i+1))) ?></td>
          <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
          <td><?= $p['trips'] ?> trips</td>
          <td style="color:var(--green-light);font-weight:600">৳<?= number_format($p['spent'],0) ?></td>
        </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include '../includes/layout_foot.php'; ?>
