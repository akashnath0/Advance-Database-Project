<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle  = 'Trains';
$activePage = 'trains';

$trains = db()->fetchAll("
  SELECT t.*, r.origin, r.destination, s.departure_date, s.departure_time, s.arrival_time,
         CONCAT(e.first_name, ' ', e.last_name) AS driver_name
  FROM Train t
  LEFT JOIN Route    r ON t.route_id    = r.route_id
  LEFT JOIN Schedule s ON t.schedule_id = s.schedule_id
  LEFT JOIN Employee e ON t.emp_id      = e.emp_id
  ORDER BY t.train_id
");
$routes    = db()->fetchAll("SELECT route_id, CONCAT(origin, ' → ', destination) AS label FROM Route");
$schedules = db()->fetchAll("SELECT schedule_id, CONCAT(DATE_FORMAT(departure_date,'%d-%b-%Y'), ' ', departure_time) AS label FROM Schedule");
$employees = db()->fetchAll("SELECT emp_id, CONCAT(first_name, ' ', last_name) AS name FROM Employee WHERE designation='Driver'");

include '../includes/layout_head.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">🚂 Train Fleet Management</div>
    <?php if (isStaff()): ?>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Add Train</button>
    <?php endif; ?>
  </div>

  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search trains...">
      </div>
      <select class="form-control" id="status-filter" style="max-width:160px">
        <option value="">All Statuses</option>
        <option>ACTIVE</option><option>MAINTENANCE</option><option>INACTIVE</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper">
    <table id="tbl-trains">
      <thead>
        <tr>
          <th>ID</th><th>Train Name</th><th>Type</th><th>Route</th>
          <th>Schedule</th><th>Driver</th><th>Status</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($trains as $t): ?>
        <tr id="row-<?= $t['train_id'] ?>">
          <td class="td-mono"><?= $t['train_id'] ?></td>
          <td><strong>🚂 <?= htmlspecialchars($t['train_name']) ?></strong></td>
          <td><span class="badge badge-secondary"><?= htmlspecialchars($t['type']??'—') ?></span></td>
          <td>
            <?php if ($t['origin']): ?>
            <div style="font-size:12px">
              📍 <?= htmlspecialchars($t['origin']) ?><br>
              🏁 <?= htmlspecialchars($t['destination']) ?>
            </div>
            <?php else: echo '—'; endif; ?>
          </td>
          <td>
            <?php if ($t['departure_date']): ?>
            <div style="font-size:12px">
              📅 <?= substr($t['departure_date'],0,11) ?><br>
              🕐 <?= $t['departure_time'] ?> → <?= $t['arrival_time'] ?>
            </div>
            <?php else: echo '—'; endif; ?>
          </td>
          <td><?= htmlspecialchars($t['driver_name'] ?? '—') ?></td>
          <td><span class="badge <?= statusBadge($t['status']) ?>"><?= $t['status'] ?></span></td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if (isStaff()): ?>
              <button class="btn btn-ghost btn-sm" onclick="editTrain(<?= htmlspecialchars(json_encode($t)) ?>)">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="deleteTrain('<?= $t['train_id'] ?>')">🗑️</button>
              <a href="audit.php?table=Train&id=<?= $t['train_id'] ?>" class="btn btn-ghost btn-sm">📋</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-backdrop" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">➕ Add New Train</div>
      <button class="btn-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <form id="form-add" onsubmit="submitAdd(event)">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Train Name *</label>
            <input name="train_name" class="form-control" required placeholder="e.g. Sundarban Express">
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="type" class="form-control">
              <option>Express</option><option>Intercity</option><option>Local</option><option>Mail</option>
            </select>
          </div>
          <div class="form-group">
            <label>Route</label>
            <select name="route_id" class="form-control">
              <option value="">— Select Route —</option>
              <?php foreach ($routes as $r): ?>
              <option value="<?= $r['route_id'] ?>"><?= htmlspecialchars($r['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Schedule</label>
            <select name="schedule_id" class="form-control">
              <option value="">— Select Schedule —</option>
              <?php foreach ($schedules as $s): ?>
              <option value="<?= $s['schedule_id'] ?>"><?= htmlspecialchars($s['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Driver (Employee)</label>
            <select name="emp_id" class="form-control">
              <option value="">— Select Driver —</option>
              <?php foreach ($employees as $em): ?>
              <option value="<?= $em['emp_id'] ?>"><?= htmlspecialchars($em['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" class="form-control">
              <option>ACTIVE</option><option>MAINTENANCE</option><option>INACTIVE</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Save Train</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">✏️ Edit Train</div>
      <button class="btn-close" onclick="closeModal('modal-edit')">✕</button>
    </div>
    <form id="form-edit" onsubmit="submitEdit(event)">
      <input type="hidden" name="train_id" id="edit-train_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Train Name *</label>
            <input name="train_name" id="edit-train_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Type</label>
            <select name="type" id="edit-type" class="form-control">
              <option>Express</option><option>Intercity</option><option>Local</option><option>Mail</option>
            </select>
          </div>
          <div class="form-group">
            <label>Route</label>
            <select name="route_id" id="edit-route_id" class="form-control">
              <option value="">— Select Route —</option>
              <?php foreach ($routes as $r): ?>
              <option value="<?= $r['route_id'] ?>"><?= htmlspecialchars($r['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Schedule</label>
            <select name="schedule_id" id="edit-schedule_id" class="form-control">
              <option value="">— Select Schedule —</option>
              <?php foreach ($schedules as $s): ?>
              <option value="<?= $s['schedule_id'] ?>"><?= htmlspecialchars($s['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Driver</label>
            <select name="emp_id" id="edit-emp_id" class="form-control">
              <option value="">— Select Driver —</option>
              <?php foreach ($employees as $em): ?>
              <option value="<?= $em['emp_id'] ?>"><?= htmlspecialchars($em['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" id="edit-status" class="form-control">
              <option>ACTIVE</option><option>MAINTENANCE</option><option>INACTIVE</option>
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
setupTableSearch('tbl-search', 'tbl-trains');
document.getElementById('status-filter').addEventListener('change', function() {
  const val = this.value.toLowerCase();
  document.querySelectorAll('#tbl-trains tbody tr').forEach(row => {
    row.style.display = (!val || row.textContent.toLowerCase().includes(val)) ? '' : 'none';
  });
});
async function submitAdd(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(document.getElementById('form-add')));
  try {
    const res = await apiRequest('../api/trains.php', 'POST', data);
    showToast(res.message, 'success');
    closeModal('modal-add');
    setTimeout(() => location.reload(), 800);
  } catch(err) { showToast(err.message, 'error'); }
}
function editTrain(t) {
  ['train_id','train_name','type','route_id','schedule_id','emp_id','status'].forEach(k => {
    const el = document.getElementById('edit-' + k);
    if (el) el.value = t[k] || '';
  });
  openModal('modal-edit');
}
async function submitEdit(e) {
  e.preventDefault();
  const data = Object.fromEntries(new FormData(document.getElementById('form-edit')));
  try {
    const res = await apiRequest('../api/trains.php', 'PUT', data);
    showToast(res.message, 'success');
    closeModal('modal-edit');
    setTimeout(() => location.reload(), 800);
  } catch(err) { showToast(err.message, 'error'); }
}
function deleteTrain(id) {
  confirmDelete('Delete train ' + id + '?', async () => {
    try {
      const res = await apiRequest('../api/trains.php', 'DELETE', { train_id: id });
      showToast(res.message, 'success');
      document.getElementById('row-' + id)?.remove();
    } catch(err) { showToast(err.message, 'error'); }
  });
}
</script>
<?php include '../includes/layout_foot.php'; ?>
