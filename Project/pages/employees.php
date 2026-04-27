<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireStaff();

$pageTitle  = 'Employees';
$activePage = 'employees';

$employees = db()->fetchAll("
  SELECT e.*,
    (SELECT d.license_no FROM Driver d WHERE d.emp_id = e.emp_id LIMIT 1) AS license_no,
    (SELECT ps.badge_no  FROM Platform_Staff ps WHERE ps.emp_id = e.emp_id LIMIT 1) AS badge_no,
    (SELECT ps.shift     FROM Platform_Staff ps WHERE ps.emp_id = e.emp_id LIMIT 1) AS shift
  FROM Employee e ORDER BY e.emp_id
");

include '../includes/layout_head.php';
?>
<div class="card">
  <div class="card-header">
    <div class="card-title">👷 Employee Management</div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Add Employee</button>
  </div>
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper"><span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search employees...">
      </div>
      <select class="form-control" id="desg-filter" style="max-width:180px">
        <option value="">All Designations</option>
        <option>Driver</option><option>Platform Staff</option><option>Supervisor</option>
      </select>
    </div>
  </div>
  <div class="table-wrapper">
    <table id="tbl-employees">
      <thead><tr><th>ID</th><th>Name</th><th>Designation</th><th>Salary</th><th>Contact</th><th>Hire Date</th><th>Role Info</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($employees as $e): ?>
        <tr id="row-<?= $e['emp_id'] ?>">
          <td class="td-mono"><?= $e['emp_id'] ?></td>
          <td><strong><?= htmlspecialchars($e['first_name'].' '.$e['last_name']) ?></strong></td>
          <td><span class="badge badge-secondary"><?= htmlspecialchars($e['designation']) ?></span></td>
          <td>৳<?= number_format($e['salary'],0) ?></td>
          <td><?= htmlspecialchars($e['contact_number']) ?></td>
          <td><?= htmlspecialchars($e['hire_date']) ?></td>
          <td class="text-sm text-muted">
            <?php if ($e['license_no']): ?>🚙 <?= $e['license_no'] ?><?php endif; ?>
            <?php if ($e['badge_no']): ?>🪪 <?= $e['badge_no'] ?> (<?= $e['shift'] ?>)<?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <button class="btn btn-ghost btn-sm" onclick="editEmp(<?= htmlspecialchars(json_encode($e)) ?>)">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="deleteEmp('<?= $e['emp_id'] ?>')">🗑️</button>
              <a href="audit.php?table=Employee&id=<?= $e['emp_id'] ?>" class="btn btn-ghost btn-sm">📋</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal-backdrop" id="modal-add">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">➕ Add Employee</div><button class="btn-close" onclick="closeModal('modal-add')">✕</button></div>
    <form id="form-add" onsubmit="submitAdd(event)">
      <div class="modal-body"><div class="form-grid">
        <div class="form-group"><label>First Name *</label><input name="first_name" class="form-control" required></div>
        <div class="form-group"><label>Last Name</label><input name="last_name" class="form-control"></div>
        <div class="form-group"><label>Designation *</label>
          <select name="designation" class="form-control" required><option>Driver</option><option>Platform Staff</option><option>Supervisor</option><option>Technician</option><option>Manager</option></select>
        </div>
        <div class="form-group"><label>Salary (৳) *</label><input name="salary" type="number" class="form-control" required></div>
        <div class="form-group"><label>Contact *</label><input name="contact_number" class="form-control" required></div>
        <div class="form-group"><label>Address</label><input name="address" class="form-control"></div>
        <div class="form-group"><label>Hire Date *</label><input name="hire_date" type="date" class="form-control" required></div>
      </div></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="modal-edit">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">✏️ Edit Employee</div><button class="btn-close" onclick="closeModal('modal-edit')">✕</button></div>
    <form id="form-edit" onsubmit="submitEdit(event)">
      <input type="hidden" name="emp_id" id="edit-emp_id">
      <div class="modal-body"><div class="form-grid">
        <div class="form-group"><label>First Name *</label><input name="first_name" id="edit-first_name" class="form-control" required></div>
        <div class="form-group"><label>Last Name</label><input name="last_name" id="edit-last_name" class="form-control"></div>
        <div class="form-group"><label>Designation *</label>
          <select name="designation" id="edit-designation" class="form-control" required><option>Driver</option><option>Platform Staff</option><option>Supervisor</option><option>Technician</option><option>Manager</option></select>
        </div>
        <div class="form-group"><label>Salary (৳)*</label><input name="salary" id="edit-salary" type="number" class="form-control" required></div>
        <div class="form-group"><label>Contact *</label><input name="contact_number" id="edit-contact_number" class="form-control" required></div>
        <div class="form-group"><label>Address</label><input name="address" id="edit-address" class="form-control"></div>
      </div></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit')">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Update</button>
      </div>
    </form>
  </div>
</div>

<script>
setupTableSearch('tbl-search','tbl-employees');
document.getElementById('desg-filter').addEventListener('change',function(){
  const val=this.value.toLowerCase();
  document.querySelectorAll('#tbl-employees tbody tr').forEach(r=>{r.style.display=(!val||r.textContent.toLowerCase().includes(val))?'':'none';});
});
async function submitAdd(e){e.preventDefault();try{const r=await apiRequest('../api/employees.php','POST',Object.fromEntries(new FormData(document.getElementById('form-add'))));showToast(r.message,'success');closeModal('modal-add');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function editEmp(emp){['emp_id','first_name','last_name','designation','salary','contact_number','address'].forEach(k=>{const el=document.getElementById('edit-'+k);if(el)el.value=emp[k]||'';});openModal('modal-edit');}
async function submitEdit(e){e.preventDefault();try{const r=await apiRequest('../api/employees.php','PUT',Object.fromEntries(new FormData(document.getElementById('form-edit'))));showToast(r.message,'success');closeModal('modal-edit');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function deleteEmp(id){confirmDelete('Delete employee '+id+'?',async()=>{try{const r=await apiRequest('../api/employees.php','DELETE',{emp_id:id});showToast(r.message,'success');document.getElementById('row-'+id)?.remove();}catch(err){showToast(err.message,'error');}});}
</script>
<?php include '../includes/layout_foot.php'; ?>
