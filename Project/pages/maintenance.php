<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireStaff();

$pageTitle  = 'Maintenance Records';
$activePage = 'maintenance';

$records = db()->fetchAll("
  SELECT m.*, t.train_name,
         CONCAT(tc.first_name, ' ', tc.last_name) AS tech_name, tc.specialization
  FROM Maintenance m
  LEFT JOIN Train      t  ON m.train_id = t.train_id
  LEFT JOIN Technician tc ON m.tech_id  = tc.tech_id
  ORDER BY m.maintenance_date DESC
");
$trains      = db()->fetchAll("SELECT train_id, train_name FROM Train");
$technicians = db()->fetchAll("SELECT tech_id, CONCAT(first_name, ' ', last_name) AS name, specialization FROM Technician");

include '../includes/layout_head.php';
?>
<div class="card">
  <div class="card-header">
    <div class="card-title">🔧 Maintenance Records</div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Log Maintenance</button>
  </div>
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search maintenance...">
      </div>
      <select class="form-control" id="type-filter" style="max-width:160px">
        <option value="">All Types</option><option>Scheduled</option><option>Emergency</option><option>Preventive</option>
      </select>
    </div>
  </div>
  <div class="table-wrapper">
    <table id="tbl-maint">
      <thead>
        <tr><th>ID</th><th>Train</th><th>Technician</th><th>Type</th><th>Date</th><th>Remarks</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($records as $m): ?>
        <tr id="row-<?= $m['maintenance_id'] ?>">
          <td class="td-mono"><?= $m['maintenance_id'] ?></td>
          <td>🚂 <?= htmlspecialchars($m['train_name']??'—') ?></td>
          <td>
            🧰 <?= htmlspecialchars($m['tech_name']??'—') ?>
            <div class="text-muted text-sm"><?= htmlspecialchars($m['specialization']??'') ?></div>
          </td>
          <td><span class="badge <?= $m['maintenance_type']==='Emergency'?'badge-danger':'badge-secondary' ?>"><?= htmlspecialchars($m['maintenance_type']??'—') ?></span></td>
          <td><?= substr($m['maintenance_date'],0,11) ?></td>
          <td style="max-width:200px;font-size:12px;color:var(--text-secondary)"><?= htmlspecialchars($m['remarks']??'—') ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <button class="btn btn-ghost btn-sm" onclick="editMaint(<?= htmlspecialchars(json_encode($m)) ?>)">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="deleteMaint('<?= $m['maintenance_id'] ?>')">🗑️</button>
              <a href="audit.php?table=Maintenance&id=<?= $m['maintenance_id'] ?>" class="btn btn-ghost btn-sm">📋</a>
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
    <div class="modal-header"><div class="modal-title">➕ Log Maintenance</div><button class="btn-close" onclick="closeModal('modal-add')">✕</button></div>
    <form id="form-add" onsubmit="submitAdd(event)">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Train *</label>
            <select name="train_id" class="form-control" required>
              <option value="">— Select Train —</option>
              <?php foreach ($trains as $t): ?><option value="<?= $t['train_id'] ?>"><?= htmlspecialchars($t['train_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Technician *</label>
            <select name="tech_id" class="form-control" required>
              <option value="">— Select Technician —</option>
              <?php foreach ($technicians as $tc): ?><option value="<?= $tc['tech_id'] ?>"><?= htmlspecialchars($tc['name']) ?> (<?= $tc['specialization'] ?>)</option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Maintenance Type</label>
            <select name="maintenance_type" class="form-control">
              <option>Scheduled</option><option>Emergency</option><option>Preventive</option><option>Corrective</option>
            </select>
          </div>
          <div class="form-group"><label>Date *</label><input name="maintenance_date" type="date" class="form-control" required></div>
          <div class="form-group full"><label>Remarks</label><textarea name="remarks" class="form-control" placeholder="Describe the maintenance work..."></textarea></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Save Record</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">✏️ Edit Maintenance</div><button class="btn-close" onclick="closeModal('modal-edit')">✕</button></div>
    <form id="form-edit" onsubmit="submitEdit(event)">
      <input type="hidden" name="maintenance_id" id="edit-maintenance_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Maintenance Type</label>
            <select name="maintenance_type" id="edit-maintenance_type" class="form-control">
              <option>Scheduled</option><option>Emergency</option><option>Preventive</option><option>Corrective</option>
            </select>
          </div>
          <div class="form-group"><label>Date</label><input name="maintenance_date" id="edit-maintenance_date" type="date" class="form-control"></div>
          <div class="form-group full"><label>Remarks</label><textarea name="remarks" id="edit-remarks" class="form-control"></textarea></div>
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
setupTableSearch('tbl-search','tbl-maint');
document.getElementById('type-filter').addEventListener('change',function(){const val=this.value.toLowerCase();document.querySelectorAll('#tbl-maint tbody tr').forEach(r=>{r.style.display=(!val||r.textContent.toLowerCase().includes(val))?'':'none';});});
async function submitAdd(e){e.preventDefault();try{const r=await apiRequest('../api/maintenance.php','POST',Object.fromEntries(new FormData(document.getElementById('form-add'))));showToast(r.message,'success');closeModal('modal-add');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function editMaint(m){document.getElementById('edit-maintenance_id').value=m.maintenance_id;document.getElementById('edit-maintenance_type').value=m.maintenance_type||'Scheduled';document.getElementById('edit-maintenance_date').value=m.maintenance_date?m.maintenance_date.substring(0,10):'';document.getElementById('edit-remarks').value=m.remarks||'';openModal('modal-edit');}
async function submitEdit(e){e.preventDefault();try{const r=await apiRequest('../api/maintenance.php','PUT',Object.fromEntries(new FormData(document.getElementById('form-edit'))));showToast(r.message,'success');closeModal('modal-edit');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function deleteMaint(id){confirmDelete('Delete maintenance record '+id+'?',async()=>{try{const r=await apiRequest('../api/maintenance.php','DELETE',{maintenance_id:id});showToast(r.message,'success');document.getElementById('row-'+id)?.remove();}catch(err){showToast(err.message,'error');}});}
</script>
<?php include '../includes/layout_foot.php'; ?>
