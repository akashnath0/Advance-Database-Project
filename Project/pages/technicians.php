<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireStaff();

$pageTitle  = 'Technicians';
$activePage = 'technicians';

$technicians = db()->fetchAll("SELECT * FROM Technician ORDER BY tech_id");
include '../includes/layout_head.php';
?>
<div class="card">
  <div class="card-header">
    <div class="card-title">🧰 Technicians</div>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Add Technician</button>
  </div>
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <input type="text" id="tbl-search" class="form-control" placeholder="Search technicians...">
  </div>
  <div class="table-wrapper">
    <table id="tbl-tech">
      <thead><tr><th>ID</th><th>Name</th><th>Specialization</th><th>Experience</th><th>Contact</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($technicians as $tc): ?>
        <tr id="row-<?= $tc['tech_id'] ?>">
          <td class="td-mono"><?= $tc['tech_id'] ?></td>
          <td><strong><?= htmlspecialchars($tc['first_name'].' '.$tc['last_name']) ?></strong></td>
          <td><span class="badge badge-info"><?= htmlspecialchars($tc['specialization']??'—') ?></span></td>
          <td><?= $tc['experience_years'] ?> yrs</td>
          <td><?= htmlspecialchars($tc['contact']??'—') ?></td>
          <td>
            <div style="display:flex;gap:6px">
              <button class="btn btn-ghost btn-sm" onclick="editTech(<?= htmlspecialchars(json_encode($tc)) ?>)">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="deleteTech('<?= $tc['tech_id'] ?>')">🗑️</button>
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
    <div class="modal-header"><div class="modal-title">➕ Add Technician</div><button class="btn-close" onclick="closeModal('modal-add')">✕</button></div>
    <form id="form-add" onsubmit="submitAdd(event)">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label>First Name *</label><input name="first_name" class="form-control" required></div>
          <div class="form-group"><label>Last Name</label><input name="last_name" class="form-control"></div>
          <div class="form-group">
            <label>Specialization</label>
            <select name="specialization" class="form-control">
              <option>Electrical</option><option>Mechanical</option><option>Hydraulics</option><option>Electronics</option><option>General</option>
            </select>
          </div>
          <div class="form-group"><label>Experience (years)</label><input name="experience_years" type="number" class="form-control" min="0"></div>
          <div class="form-group"><label>Contact</label><input name="contact" class="form-control" placeholder="017XXXXXXXX"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">✏️ Edit Technician</div><button class="btn-close" onclick="closeModal('modal-edit')">✕</button></div>
    <form id="form-edit" onsubmit="submitEdit(event)">
      <input type="hidden" name="tech_id" id="edit-tech_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label>First Name *</label><input name="first_name" id="edit-first_name" class="form-control" required></div>
          <div class="form-group"><label>Last Name</label><input name="last_name" id="edit-last_name" class="form-control"></div>
          <div class="form-group"><label>Specialization</label>
            <select name="specialization" id="edit-specialization" class="form-control">
              <option>Electrical</option><option>Mechanical</option><option>Hydraulics</option><option>Electronics</option><option>General</option>
            </select>
          </div>
          <div class="form-group"><label>Experience (years)</label><input name="experience_years" id="edit-experience_years" type="number" class="form-control"></div>
          <div class="form-group"><label>Contact</label><input name="contact" id="edit-contact" class="form-control"></div>
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
setupTableSearch('tbl-search','tbl-tech');
async function submitAdd(e){e.preventDefault();try{const r=await apiRequest('../api/technicians.php','POST',Object.fromEntries(new FormData(document.getElementById('form-add'))));showToast(r.message,'success');closeModal('modal-add');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function editTech(tc){['tech_id','first_name','last_name','specialization','experience_years','contact'].forEach(k=>{const el=document.getElementById('edit-'+k);if(el)el.value=tc[k]||'';});openModal('modal-edit');}
async function submitEdit(e){e.preventDefault();try{const r=await apiRequest('../api/technicians.php','PUT',Object.fromEntries(new FormData(document.getElementById('form-edit'))));showToast(r.message,'success');closeModal('modal-edit');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function deleteTech(id){confirmDelete('Delete technician '+id+'?',async()=>{try{const r=await apiRequest('../api/technicians.php','DELETE',{tech_id:id});showToast(r.message,'success');document.getElementById('row-'+id)?.remove();}catch(err){showToast(err.message,'error');}});}
</script>
<?php include '../includes/layout_foot.php'; ?>
