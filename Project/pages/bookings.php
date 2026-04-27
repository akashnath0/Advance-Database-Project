<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle  = 'Bookings';
$activePage = 'bookings';

$bookingsSql = "
  SELECT b.*,
         CONCAT(p.first_name, ' ', p.last_name) AS passenger_name,
         t.train_name,
         c.coach_type,
         py.amount, py.method, py.status AS pay_status
  FROM Booking b
  JOIN Passenger p  ON b.passenger_id = p.passenger_id
  JOIN Train     t  ON b.train_id     = t.train_id
  JOIN Coach     c  ON b.coach_id     = c.coach_id
  JOIN Payment  py  ON b.payment_id   = py.payment_id
";
$bookingsParams = [];
if (!isStaff()) {
    $pid = $_SESSION['passenger_id'] ?? 'NONE';
    $bookingsSql .= " WHERE b.passenger_id = :pid ";
    $bookingsParams = [':pid' => $pid];
}
$bookingsSql .= " ORDER BY b.booking_time DESC";
$bookings = db()->fetchAll($bookingsSql, $bookingsParams);

$passengers = db()->fetchAll("SELECT passenger_id, CONCAT(first_name, ' ', last_name) AS name FROM Passenger");
$trains = db()->fetchAll("
    SELECT t.train_id, t.train_name, r.distance_km 
    FROM Train t 
    LEFT JOIN Route r ON t.route_id = r.route_id 
    WHERE t.status='ACTIVE'
");
$coaches    = db()->fetchAll("SELECT c.coach_id, c.train_id, c.coach_type, c.capacity FROM Coach c");

include '../includes/layout_head.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">🎫 Booking Management</div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ New Booking</button>
  </div>

  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search bookings...">
      </div>
      <select class="form-control" id="status-filter" style="max-width:150px">
        <option value="">All Status</option>
        <option>PAID</option><option>PENDING</option><option>REFUNDED</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper">
    <table id="tbl-bookings">
      <thead>
        <tr>
          <th>Booking ID</th><th>Passenger</th><th>Train</th><th>Coach</th>
          <th>Seat</th><th>Travel Date</th><th>Amount</th><th>Payment</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
        <tr id="row-<?= $b['booking_id'] ?>">
          <td class="td-mono"><?= $b['booking_id'] ?></td>
          <td><?= htmlspecialchars($b['passenger_name']) ?></td>
          <td>🚂 <?= htmlspecialchars($b['train_name']) ?></td>
          <td><span class="badge badge-secondary"><?= $b['coach_type'] ?></span></td>
          <td><strong>#<?= $b['seat_no'] ?></strong></td>
          <td><?= htmlspecialchars(substr($b['travel_date'],0,11)) ?></td>
          <td>৳<?= number_format($b['amount'],0) ?> <span class="text-muted text-sm"><?= $b['method'] ?></span></td>
          <td><span class="badge <?= statusBadge($b['pay_status']) ?>"><?= $b['pay_status'] ?></span></td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if (isStaff()): ?>
              <button class="btn btn-ghost btn-sm" onclick="editBooking(<?= htmlspecialchars(json_encode($b)) ?>)">✏️</button>
              <a href="audit.php?table=Booking&amp;id=<?= $b['booking_id'] ?>" class="btn btn-ghost btn-sm">📋</a>
              <?php endif; ?>
              <button class="btn btn-danger btn-sm" onclick="deleteBooking('<?= $b['booking_id'] ?>')">🗑️ Cancel</button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Booking Modal -->
<div class="modal-backdrop" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">➕ New Booking</div>
      <button class="btn-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <form id="form-add" onsubmit="submitAdd(event)">
      <div class="modal-body">
        <div class="form-grid">
          <?php if (isStaff()): ?>
          <div class="form-group">
            <label>Passenger *</label>
            <select name="passenger_id" class="form-control" required>
              <option value="">— Select Passenger —</option>
              <?php foreach ($passengers as $p): ?>
              <option value="<?= $p['passenger_id'] ?>"><?= htmlspecialchars($p['name']) ?> (<?= $p['passenger_id'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php else: ?>
          <input type="hidden" name="passenger_id" value="<?= htmlspecialchars($_SESSION['passenger_id'] ?? '') ?>">
          <?php endif; ?>
          <div class="form-group">
            <label>Train *</label>
            <select name="train_id" class="form-control" required id="sel-train" onchange="filterCoaches(this.value)">
              <option value="">— Select Train —</option>
              <?php foreach ($trains as $t): ?>
              <option value="<?= $t['train_id'] ?>"><?= htmlspecialchars($t['train_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Coach *</label>
            <select name="coach_id" class="form-control" required id="sel-coach" onchange="calculateAmount()">
              <option value="">— Select Train First —</option>
            </select>
          </div>
          <div class="form-group">
            <label>Seat Number *</label>
            <input name="seat_no" id="inp-seat" type="number" class="form-control" required min="1" placeholder="e.g. 12" oninput="calculateAmount()">
          </div>
          <div class="form-group">
            <label>Travel Date *</label>
            <input name="travel_date" type="date" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Payment Amount (৳) *</label>
            <input name="amount" id="inp-amount" type="number" step="0.01" class="form-control" required placeholder="Auto-calculated">
          </div>
          <div class="form-group">
            <label>Payment Method *</label>
            <select name="method" class="form-control" required>
              <option>bKash</option><option>Nagad</option><option>Cash</option><option>Card</option><option>Rocket</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Confirm Booking</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Booking Modal -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">✏️ Edit Booking</div>
      <button class="btn-close" onclick="closeModal('modal-edit')">✕</button>
    </div>
    <form id="form-edit" onsubmit="submitEdit(event)">
      <input type="hidden" name="booking_id" id="edit-booking_id">
      <input type="hidden" name="payment_id" id="edit-payment_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Seat Number</label>
            <input name="seat_no" id="edit-seat_no" type="number" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Travel Date</label>
            <input name="travel_date" id="edit-travel_date" type="date" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Payment Status</label>
            <select name="pay_status" id="edit-pay_status" class="form-control">
              <option>PAID</option><option>PENDING</option><option>REFUNDED</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit')">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Update</button>
      </div>
    </form>
  </div>
</div>

<script>
// Coach data from PHP
const allTrains = <?= json_encode($trains) ?>;
const allCoaches = <?= json_encode($coaches) ?>;

const rates = {
  'AC': 3,
  'Non-AC': 1.5,
  'Sleeper': 4.5,
  'First Class': 3.5,
  'Second Class': 2.5
};

function calculateAmount() {
  const trainId = document.getElementById('sel-train').value;
  const coachId = document.getElementById('sel-coach').value;
  const seatNo = document.getElementById('inp-seat').value;
  const amtInput = document.getElementById('inp-amount');
  
  if (!trainId || !coachId || !seatNo || parseInt(seatNo) <= 0) {
    amtInput.value = '0';
    return;
  }
  
  const train = allTrains.find(t => t.train_id === trainId);
  const coach = allCoaches.find(c => c.coach_id === coachId);
  
  if (train && coach && train.distance_km) {
    const rate = rates[coach.coach_type] || 1;
    const distance = parseFloat(train.distance_km) || 0;
    const seats = parseInt(seatNo) || 0;
    // Calculate seats * total distance * rate
    amtInput.value = Math.round(distance * rate * seats);
  } else {
    amtInput.value = '0';
  }
}

function filterCoaches(trainId) {
  const sel = document.getElementById('sel-coach');
  sel.innerHTML = '<option value="">— Select Coach —</option>';
  allCoaches.filter(c => c.train_id === trainId).forEach(c => {
    const opt = document.createElement('option');
    opt.value = c.coach_id;
    opt.textContent = c.coach_id + ' — ' + c.coach_type + ' (Cap: ' + c.capacity + ')';
    sel.appendChild(opt);
  });
  calculateAmount();
}

setupTableSearch('tbl-search', 'tbl-bookings');
document.getElementById('status-filter').addEventListener('change', function() {
  const val = this.value.toLowerCase();
  document.querySelectorAll('#tbl-bookings tbody tr').forEach(row => {
    row.style.display = (!val || row.textContent.toLowerCase().includes(val)) ? '' : 'none';
  });
});

async function submitAdd(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(document.getElementById('form-add')));
  try {
    const res = await apiRequest('../api/bookings.php', 'POST', data);
    showToast(res.message, 'success');
    closeModal('modal-add');
    setTimeout(() => location.reload(), 800);
  } catch(err) { showToast(err.message, 'error'); }
}

function editBooking(b) {
  document.getElementById('edit-booking_id').value = b.booking_id;
  document.getElementById('edit-payment_id').value = b.payment_id;
  document.getElementById('edit-seat_no').value = b.seat_no;
  document.getElementById('edit-travel_date').value = b.travel_date ? b.travel_date.substring(0,10) : '';
  document.getElementById('edit-pay_status').value = b.pay_status;
  openModal('modal-edit');
}

async function submitEdit(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(document.getElementById('form-edit')));
  try {
    const res = await apiRequest('../api/bookings.php', 'PUT', data);
    showToast(res.message, 'success');
    closeModal('modal-edit');
    setTimeout(() => location.reload(), 800);
  } catch(err) { showToast(err.message, 'error'); }
}

function deleteBooking(id) {
  confirmDelete('Cancel booking ' + id + '?', async () => {
    try {
      const res = await apiRequest('../api/bookings.php', 'DELETE', { booking_id: id });
      showToast(res.message, 'success');
      document.getElementById('row-' + id)?.remove();
    } catch(err) { showToast(err.message, 'error'); }
  });
}
</script>
<?php include '../includes/layout_foot.php'; ?>
