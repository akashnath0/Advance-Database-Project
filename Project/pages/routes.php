<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireLogin();

$pageTitle  = 'Routes & Schedules';
$activePage = 'routes';

$routes    = db()->fetchAll("SELECT * FROM Route ORDER BY route_id");
$schedules = db()->fetchAll("SELECT * FROM Schedule ORDER BY schedule_id");

include '../includes/layout_head.php';
?>

<div class="grid-2" style="gap:20px;align-items:start">

<!-- Routes -->
<div class="card">
  <div class="card-header">
    <div class="card-title">🗺️ Routes</div>
    <?php if (isStaff()): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-route')">➕ Add Route</button>
    <?php endif; ?>
  </div>
  <div style="padding:10px 16px;border-bottom:1px solid var(--border)">
    <input type="text" id="route-search" class="form-control" placeholder="Search routes...">
  </div>
  <div class="table-wrapper">
    <table id="tbl-routes">
      <thead>
        <tr><th>ID</th><th>Origin → Destination</th><th>Distance</th><th>Duration</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($routes as $r): ?>
        <tr id="rrow-<?= $r['route_id'] ?>">
          <td class="td-mono"><?= $r['route_id'] ?></td>
          <td>📍 <?= htmlspecialchars($r['origin']) ?> → 🏁 <?= htmlspecialchars($r['destination']) ?></td>
          <td><?= $r['distance_km'] ?> km</td>
          <td><?= htmlspecialchars($r['estimated_duration']??'—') ?></td>
          <td>
            <div style="display:flex;gap:4px">
              <?php if (isStaff()): ?>
              <button class="btn btn-ghost btn-sm" onclick="editRoute(<?= htmlspecialchars(json_encode($r)) ?>)">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="deleteRoute('<?= $r['route_id'] ?>')">🗑️</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Schedules -->
<div class="card">
  <div class="card-header">
    <div class="card-title">📅 Schedules</div>
    <?php if (isStaff()): ?>
    <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-sched')">➕ Add Schedule</button>
    <?php endif; ?>
  </div>
  <div style="padding:10px 16px;border-bottom:1px solid var(--border)">
    <input type="text" id="sched-search" class="form-control" placeholder="Search schedules...">
  </div>
  <div class="table-wrapper">
    <table id="tbl-schedules">
      <thead>
        <tr><th>ID</th><th>Departure Date</th><th>Departure</th><th>Arrival</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($schedules as $s): ?>
        <tr id="srow-<?= $s['schedule_id'] ?>">
          <td class="td-mono"><?= $s['schedule_id'] ?></td>
          <td>📅 <?= substr($s['departure_date'],0,11) ?></td>
          <td>🕐 <?= $s['departure_time'] ?></td>
          <td>🕐 <?= $s['arrival_time'] ?></td>
          <td>
            <div style="display:flex;gap:4px">
              <?php if (isStaff()): ?>
              <button class="btn btn-ghost btn-sm" onclick="editSched(<?= htmlspecialchars(json_encode($s)) ?>)">✏️</button>
              <button class="btn btn-danger btn-sm" onclick="deleteSched('<?= $s['schedule_id'] ?>')">🗑️</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<!-- Add Route Modal -->
<div class="modal-backdrop" id="modal-add-route">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">➕ Add Route</div><button class="btn-close" onclick="closeModal('modal-add-route')">✕</button></div>
    <form id="form-add-route" onsubmit="submitAddRoute(event)">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label>Origin *</label><input name="origin" class="form-control" required placeholder="e.g. Dhaka"></div>
          <div class="form-group"><label>Destination *</label><input name="destination" class="form-control" required placeholder="e.g. Chittagong"></div>
          <div class="form-group"><label>Distance (km) *</label><input name="distance_km" type="number" class="form-control" required></div>
          <div class="form-group"><label>Est. Duration</label><input name="estimated_duration" class="form-control" placeholder="e.g. 5h 30m"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add-route')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Save Route</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Route Modal -->
<div class="modal-backdrop" id="modal-edit-route">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">✏️ Edit Route</div><button class="btn-close" onclick="closeModal('modal-edit-route')">✕</button></div>
    <form id="form-edit-route" onsubmit="submitEditRoute(event)">
      <input type="hidden" name="route_id" id="edit-route_id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label>Origin *</label><input name="origin" id="edit-route-origin" class="form-control" required></div>
          <div class="form-group"><label>Destination *</label><input name="destination" id="edit-route-dest" class="form-control" required></div>
          <div class="form-group"><label>Distance (km) *</label><input name="distance_km" id="edit-route-dist" type="number" class="form-control" required></div>
          <div class="form-group"><label>Est. Duration</label><input name="estimated_duration" id="edit-route-dur" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-route')">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Schedule Modal -->
<div class="modal-backdrop" id="modal-add-sched">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">➕ Add Schedule</div><button class="btn-close" onclick="closeModal('modal-add-sched')">✕</button></div>
    <form id="form-add-sched" onsubmit="submitAddSched(event)">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label>Departure Date *</label><input name="departure_date" type="date" class="form-control" required></div>
          <div class="form-group"><label>Departure Time *</label><input name="departure_time" type="time" class="form-control" required></div>
          <div class="form-group"><label>Arrival Time *</label><input name="arrival_time" type="time" class="form-control" required></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-add-sched')">Cancel</button>
        <button type="submit" class="btn btn-primary">✅ Save Schedule</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Schedule Modal -->
<div class="modal-backdrop" id="modal-edit-sched">
  <div class="modal">
    <div class="modal-header"><div class="modal-title">✏️ Edit Schedule</div><button class="btn-close" onclick="closeModal('modal-edit-sched')">✕</button></div>
    <form id="form-edit-sched" onsubmit="submitEditSched(event)">
      <input type="hidden" name="schedule_id" id="edit-sched-id">
      <div class="modal-body">
        <div class="form-grid">
          <div class="form-group"><label>Departure Date *</label><input name="departure_date" id="edit-sched-date" type="date" class="form-control" required></div>
          <div class="form-group"><label>Departure Time *</label><input name="departure_time" id="edit-sched-dep" type="time" class="form-control" required></div>
          <div class="form-group"><label>Arrival Time *</label><input name="arrival_time" id="edit-sched-arr" type="time" class="form-control" required></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit-sched')">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Update</button>
      </div>
    </form>
  </div>
</div>

<script>
setupTableSearch('route-search','tbl-routes');
setupTableSearch('sched-search','tbl-schedules');

async function submitAddRoute(e) {
  e.preventDefault();
  try { const r=await apiRequest('../api/routes.php','POST',Object.fromEntries(new FormData(document.getElementById('form-add-route')))); showToast(r.message,'success'); closeModal('modal-add-route'); setTimeout(()=>location.reload(),800); } catch(err){showToast(err.message,'error');}
}
function editRoute(r) {
  document.getElementById('edit-route_id').value=r.route_id;
  document.getElementById('edit-route-origin').value=r.origin;
  document.getElementById('edit-route-dest').value=r.destination;
  document.getElementById('edit-route-dist').value=r.distance_km;
  document.getElementById('edit-route-dur').value=r.estimated_duration||'';
  openModal('modal-edit-route');
}
async function submitEditRoute(e) {
  e.preventDefault();
  try { const r=await apiRequest('../api/routes.php','PUT',Object.fromEntries(new FormData(document.getElementById('form-edit-route')))); showToast(r.message,'success'); closeModal('modal-edit-route'); setTimeout(()=>location.reload(),800); } catch(err){showToast(err.message,'error');}
}
function deleteRoute(id) {
  confirmDelete('Delete route '+id+'?', async()=>{ try { const r=await apiRequest('../api/routes.php','DELETE',{route_id:id}); showToast(r.message,'success'); document.getElementById('rrow-'+id)?.remove(); } catch(err){showToast(err.message,'error');} });
}
async function submitAddSched(e) {
  e.preventDefault();
  try { const r=await apiRequest('../api/routes.php?type=schedule','POST',Object.fromEntries(new FormData(document.getElementById('form-add-sched')))); showToast(r.message,'success'); closeModal('modal-add-sched'); setTimeout(()=>location.reload(),800); } catch(err){showToast(err.message,'error');}
}
function editSched(s) {
  document.getElementById('edit-sched-id').value=s.schedule_id;
  document.getElementById('edit-sched-date').value=s.departure_date?s.departure_date.substring(0,10):'';
  document.getElementById('edit-sched-dep').value=s.departure_time||'';
  document.getElementById('edit-sched-arr').value=s.arrival_time||'';
  openModal('modal-edit-sched');
}
async function submitEditSched(e) {
  e.preventDefault();
  try { const r=await apiRequest('../api/routes.php?type=schedule','PUT',Object.fromEntries(new FormData(document.getElementById('form-edit-sched')))); showToast(r.message,'success'); closeModal('modal-edit-sched'); setTimeout(()=>location.reload(),800); } catch(err){showToast(err.message,'error');}
}
function deleteSched(id) {
  confirmDelete('Delete schedule '+id+'?', async()=>{ try { const r=await apiRequest('../api/routes.php?type=schedule','DELETE',{schedule_id:id}); showToast(r.message,'success'); document.getElementById('srow-'+id)?.remove(); } catch(err){showToast(err.message,'error');} });
}
</script>
<?php include '../includes/layout_foot.php'; ?>
