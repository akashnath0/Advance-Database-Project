<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle  = 'Coaches';
$activePage = 'coaches';

$coaches = db()->fetchAll("
  SELECT c.*, t.train_name,
    (SELECT COUNT(*) FROM Booking b WHERE b.coach_id = c.coach_id) AS bookings_count
  FROM Coach c
  LEFT JOIN Train t ON c.train_id = t.train_id
  ORDER BY c.train_id, c.coach_id
");
$trains = db()->fetchAll("SELECT train_id, train_name FROM Train");

include '../includes/layout_head.php';
?>
<div class="card">
  <div class="card-header">
    <div class="card-title">🚃 Coach Management</div>
    <?php if (isStaff()): ?>
    <button class="btn btn-primary" onclick="openModal('modal-add')">➕ Add Coach</button>
    <?php endif; ?>
  </div>
  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search coaches...">
      </div>
      <select class="form-control" id="type-filter" style="max-width:160px">
        <option value="">All Types</option><option>AC</option><option>Non-AC</option><option>Sleeper</option>
      </select>
    </div>
  </div>
  <div class="table-wrapper">
    <table id="tbl-coaches">
      <thead>
        <tr><th>Coach ID</th><th>Train</th><th>Type</th><th>Capacity</th><th>Bookings</th><th>Occupancy</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($coaches as $c):
          $pct = $c['capacity'] > 0 ? round(($c['bookings_count'] / $c['capacity']) * 100) : 0;
          $barColor = $pct > 80 ? 'var(--red-light)' : ($pct > 50 ? 'var(--amber-light)' : 'var(--green-light)');
        ?>
        <tr id="row-<?= $c['coach_id'] ?>">
          <td class="td-mono"><?= $c['coach_id'] ?></td>
          <td>🚂 <?= htmlspecialchars($c['train_name'] ?? '—') ?></td>
          <td><span class="badge badge-<?= $c['coach_type']==='AC'?'info':'secondary' ?>"><?= $c['coach_type'] ?></span></td>
          <td><?= $c['capacity'] ?> seats</td>
          <td><?= $c['bookings_count'] ?> booked</td>
          <td style="min-width:120px">
            <div style="background:var(--border);border-radius:4px;height:8px;overflow:hidden">
              <div style="width:<?= $pct ?>%;height:100%;background:<?= $barColor ?>;transition:width .5s"></div>
            </div>
            <span class="text-sm text-muted"><?= $pct ?>%</span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              <?php if (isStaff()): ?>
              <button class="btn btn-ghost btn-sm" onclick="editCoach(<?= htmlspecialchars(json_encode($c)) ?>)">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="deleteCoach('<?= $c['coach_id'] ?>')">🗑️</button>
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
    <div class="modal-header"><div class="modal-title">➕ Add Coach</div><button class="btn-close" onclick="closeModal('modal-add')">✕</button></div>
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
            <label>Coach Type *</label>
            <select name="coach_type" class="form-control" required>
              <option>AC</option><option>Non-AC</option><option>Sleeper</option><option>First Class</option><option>Second Class</option>
            </select>
          </div>
          <div class="form-group"><label>Capacity (seats) *</label><input name="capacity" type="number" class="form-control" required min="1" placeholder="e.g. 50"></div>
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
    <div class="modal-header"><div class="modal-title">✏️ Edit Coach</div><button class="btn-close" onclick="closeModal('modal-edit')">✕</button></div>
    <form id="form-edit" onsubmit="submitEdit(event)">
      <input type="hidden" name="coach_id" id="edit-coach_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group">
            <label>Train *</label>
            <select name="train_id" id="edit-train_id" class="form-control" required>
              <?php foreach ($trains as $t): ?><option value="<?= $t['train_id'] ?>"><?= htmlspecialchars($t['train_name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Coach Type *</label>
            <select name="coach_type" id="edit-coach_type" class="form-control" required>
              <option>AC</option><option>Non-AC</option><option>Sleeper</option><option>First Class</option><option>Second Class</option>
            </select>
          </div>
          <div class="form-group"><label>Capacity *</label><input name="capacity" id="edit-capacity" type="number" class="form-control" required></div>
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
setupTableSearch('tbl-search','tbl-coaches');
document.getElementById('type-filter').addEventListener('change',function(){
  const val=this.value.toLowerCase();
  document.querySelectorAll('#tbl-coaches tbody tr').forEach(r=>{r.style.display=(!val||r.textContent.toLowerCase().includes(val))?'':'none';});
});
async function submitAdd(e){e.preventDefault();try{const r=await apiRequest('../api/coaches.php','POST',Object.fromEntries(new FormData(document.getElementById('form-add'))));showToast(r.message,'success');closeModal('modal-add');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function editCoach(c){['coach_id','train_id','coach_type','capacity'].forEach(k=>{const el=document.getElementById('edit-'+k);if(el)el.value=c[k]||'';});openModal('modal-edit');}
async function submitEdit(e){e.preventDefault();try{const r=await apiRequest('../api/coaches.php','PUT',Object.fromEntries(new FormData(document.getElementById('form-edit'))));showToast(r.message,'success');closeModal('modal-edit');setTimeout(()=>location.reload(),800);}catch(err){showToast(err.message,'error');}}
function deleteCoach(id){confirmDelete('Delete coach '+id+'?',async()=>{try{const r=await apiRequest('../api/coaches.php','DELETE',{coach_id:id});showToast(r.message,'success');document.getElementById('row-'+id)?.remove();}catch(err){showToast(err.message,'error');}});}
</script>
<?php include '../includes/layout_foot.php'; ?>
