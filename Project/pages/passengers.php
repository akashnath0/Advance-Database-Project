<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireStaff();

$pageTitle  = 'Passengers';
$activePage = 'passengers';

$passengers = db()->fetchAll("SELECT * FROM Passenger ORDER BY passenger_id");
$total = count($passengers);

include '../includes/layout_head.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">👥 Passenger Registry <span class="badge badge-info" style="margin-left:8px"><?= $total ?> Records</span></div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Add Passenger</button>
    </div>
  </div>

  <!-- Filters -->
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search passengers...">
      </div>
      <select class="form-control" id="gender-filter" style="max-width:140px">
        <option value="">All Genders</option>
        <option>Male</option><option>Female</option><option>Other</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper">
    <table id="tbl-passengers">
      <thead>
        <tr>
          <th>ID</th><th>Name</th><th>Gender</th><th>Contact</th><th>Date of Birth</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($passengers)): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">👤</div><p>No passengers found.</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($passengers as $p): ?>
        <tr id="row-<?= $p['passenger_id'] ?>">
          <td class="td-mono"><?= $p['passenger_id'] ?></td>
          <td><strong><?= htmlspecialchars($p['first_name'].' '.$p['last_name']) ?></strong></td>
          <td>
            <?php $g = $p['gender'];
              $icon = $g==='Male' ? '♂️' : ($g==='Female' ? '♀️' : '⚧');
              echo "<span>$icon $g</span>";
            ?>
          </td>
          <td><?= htmlspecialchars($p['contact_number']) ?></td>
          <td><?= htmlspecialchars($p['date_of_birth']) ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <button class="btn btn-ghost btn-sm" onclick="editPassenger(<?= htmlspecialchars(json_encode($p)) ?>)">✏️ Edit</button>
              <button class="btn btn-danger btn-sm" onclick="deletePassenger('<?= $p['passenger_id'] ?>')">🗑️</button>
              <a href="audit.php?table=Passenger&id=<?= $p['passenger_id'] ?>" class="btn btn-ghost btn-sm">📋 History</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div class="modal-backdrop" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">➕ Add New Passenger</div>
      <button class="btn-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <form id="form-add" onsubmit="submitAdd(event)">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>First Name *</label>
            <input name="first_name" class="form-control" required placeholder="First name">
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input name="last_name" class="form-control" placeholder="Last name">
          </div>
          <div class="form-group">
            <label>Gender *</label>
            <select name="gender" class="form-control" required>
              <option value="">Select gender</option>
              <option>Male</option><option>Female</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Contact Number *</label>
            <input name="contact_number" class="form-control" required placeholder="017XXXXXXXX">
          </div>
          <div class="form-group">
            <label>Date of Birth *</label>
            <input name="date_of_birth" type="date" class="form-control" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Save Passenger</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">✏️ Edit Passenger</div>
      <button class="btn-close" onclick="closeModal('modal-edit')">✕</button>
    </div>
    <form id="form-edit" onsubmit="submitEdit(event)">
      <input type="hidden" name="passenger_id" id="edit-passenger_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>First Name *</label>
            <input name="first_name" id="edit-first_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input name="last_name" id="edit-last_name" class="form-control">
          </div>
          <div class="form-group">
            <label>Gender *</label>
            <select name="gender" id="edit-gender" class="form-control" required>
              <option>Male</option><option>Female</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Contact Number *</label>
            <input name="contact_number" id="edit-contact_number" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Date of Birth *</label>
            <input name="date_of_birth" id="edit-date_of_birth" type="date" class="form-control" required>
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
setupTableSearch('tbl-search', 'tbl-passengers');

// Gender filter
document.getElementById('gender-filter').addEventListener('change', function() {
  const val = this.value.toLowerCase();
  document.querySelectorAll('#tbl-passengers tbody tr').forEach(row => {
    if (!val) { row.style.display = ''; return; }
    row.style.display = row.textContent.toLowerCase().includes(val) ? '' : 'none';
  });
});

async function submitAdd(e) {
  e.preventDefault();
  const form = document.getElementById('form-add');
  const data = Object.fromEntries(new FormData(form));
  try {
    const res = await apiRequest('../api/passengers.php', 'POST', data);
    showToast(res.message, 'success');
    closeModal('modal-add');
    setTimeout(() => location.reload(), 800);
  } catch(err) { showToast(err.message, 'error'); }
}

function editPassenger(p) {
  document.getElementById('edit-passenger_id').value = p.passenger_id;
  document.getElementById('edit-first_name').value = p.first_name;
  document.getElementById('edit-last_name').value = p.last_name || '';
  document.getElementById('edit-gender').value = p.gender;
  document.getElementById('edit-contact_number').value = p.contact_number;
  // Format date
  const dob = p.date_of_birth ? p.date_of_birth.substring(0, 10) : '';
  document.getElementById('edit-date_of_birth').value = dob;
  openModal('modal-edit');
}

async function submitEdit(e) {
  e.preventDefault();
  const form = document.getElementById('form-edit');
  const data = Object.fromEntries(new FormData(form));
  try {
    const res = await apiRequest('../api/passengers.php', 'PUT', data);
    showToast(res.message, 'success');
    closeModal('modal-edit');
    setTimeout(() => location.reload(), 800);
  } catch(err) { showToast(err.message, 'error'); }
}

function deletePassenger(id) {
  confirmDelete('Delete passenger ' + id + '? This will also remove their bookings!', async () => {
    try {
      const res = await apiRequest('../api/passengers.php', 'DELETE', { passenger_id: id });
      showToast(res.message, 'success');
      document.getElementById('row-' + id)?.remove();
    } catch(err) { showToast(err.message, 'error'); }
  });
}
</script>

<?php include '../includes/layout_foot.php'; ?>
