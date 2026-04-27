<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle  = 'Payments';
$activePage = 'payments';

$paymentsSql = "
  SELECT py.*,
    (SELECT b.booking_id FROM Booking b WHERE b.payment_id = py.payment_id LIMIT 1) AS booking_id,
    (SELECT CONCAT(p.first_name,' ',p.last_name)
       FROM Booking b JOIN Passenger p ON b.passenger_id = p.passenger_id
       WHERE b.payment_id = py.payment_id LIMIT 1) AS passenger_name
  FROM Payment py
";
$paymentsParams = [];
if (!isStaff()) {
    $paymentsSql .= " JOIN Booking b2 ON py.payment_id = b2.payment_id WHERE b2.passenger_id = :pid ";
    $paymentsParams[':pid'] = $_SESSION['passenger_id'] ?? 'NONE';
}
$paymentsSql .= " ORDER BY py.payment_date DESC ";
$payments = db()->fetchAll($paymentsSql, $paymentsParams);

$totalRev   = 0; $pendingAmt = 0;
foreach ($payments as $p) {
    if ($p['status']==='PAID')    $totalRev   += $p['amount'];
    if ($p['status']==='PENDING') $pendingAmt += $p['amount'];
}

include '../includes/layout_head.php';
?>
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:20px">
  <div class="stat-card green">
    <div class="stat-icon">✅</div>
    <div class="stat-info"><div class="stat-label">Revenue Collected</div><div class="stat-value" style="font-size:20px">৳<?= number_format($totalRev,0) ?></div></div>
  </div>
  <div class="stat-card amber">
    <div class="stat-icon">⏳</div>
    <div class="stat-info"><div class="stat-label">Pending Amount</div><div class="stat-value" style="font-size:20px">৳<?= number_format($pendingAmt,0) ?></div></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon">💳</div>
    <div class="stat-info"><div class="stat-label">Total Transactions</div><div class="stat-value"><?= count($payments) ?></div></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title">💳 Payment Records</div></div>
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper"><span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search payments...">
      </div>
      <select class="form-control" id="method-filter" style="max-width:150px">
        <option value="">All Methods</option>
        <option>bKash</option><option>Nagad</option><option>Cash</option><option>Card</option><option>Rocket</option>
      </select>
      <select class="form-control" id="status-filter" style="max-width:140px">
        <option value="">All Statuses</option><option>PAID</option><option>PENDING</option><option>REFUNDED</option>
      </select>
    </div>
  </div>
  <div class="table-wrapper">
    <table id="tbl-payments">
      <thead><tr><th>Payment ID</th><th>Booking</th><th>Passenger</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td class="td-mono"><?= $p['payment_id'] ?></td>
          <td class="td-mono"><?= $p['booking_id'] ?? '—' ?></td>
          <td><?= htmlspecialchars($p['passenger_name'] ?? '—') ?></td>
          <td><strong>৳<?= number_format($p['amount'],2) ?></strong></td>
          <td><span class="badge badge-secondary"><?= $p['method'] ?></span></td>
          <td><span class="badge <?= statusBadge($p['status']) ?>"><?= $p['status'] ?></span></td>
          <td style="font-size:12px"><?= substr($p['payment_date']??'',0,16) ?></td>
          <td>
            <?php if (isStaff()): ?>
            <a href="audit.php?table=Payment&amp;id=<?= $p['payment_id'] ?>" class="btn btn-ghost btn-sm">📋</a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
setupTableSearch('tbl-search','tbl-payments');
['method-filter','status-filter'].forEach(id=>{
  document.getElementById(id).addEventListener('change',function(){
    const mVal=document.getElementById('method-filter').value.toLowerCase();
    const sVal=document.getElementById('status-filter').value.toLowerCase();
    document.querySelectorAll('#tbl-payments tbody tr').forEach(row=>{
      const txt=row.textContent.toLowerCase();
      row.style.display=((!mVal||txt.includes(mVal))&&(!sVal||txt.includes(sVal)))?'':'none';
    });
  });
});
</script>
<?php include '../includes/layout_foot.php'; ?>
