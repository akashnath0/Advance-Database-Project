<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
requireAdmin();

$pageTitle  = 'User Management';
$activePage = 'users';

include '../includes/layout_head.php';
?>

<div class="card">
  <div class="card-header">
    <div class="card-title">🔐 User Management</div>
  </div>

  <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
    <div class="filters-bar">
      <div class="search-input-wrapper">
        <span class="search-icon">🔍</span>
        <input type="text" id="tbl-search" class="form-control" placeholder="Search users...">
      </div>
      <select class="form-control" id="role-filter" style="max-width:150px">
        <option value="">All Roles</option>
        <option value="admin">Admin</option>
        <option value="staff">Staff</option>
        <option value="viewer">Viewer</option>
      </select>
    </div>
  </div>

  <div class="table-wrapper">
    <table id="tbl-users">
      <thead>
        <tr>
          <th>User ID</th>
          <th>Username</th>
          <th>Role</th>
          <th>Passenger ID</th>
          <th>Registered On</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="users-tbody">
        <!-- Loaded via JS -->
      </tbody>
    </table>
  </div>
</div>

<script>
setupTableSearch('tbl-search', 'tbl-users');
document.getElementById('role-filter').addEventListener('change', function() {
  const val = this.value.toLowerCase();
  document.querySelectorAll('#tbl-users tbody tr').forEach(row => {
    const roleCell = row.querySelector('.role-cell')?.textContent.toLowerCase() || '';
    row.style.display = (!val || roleCell.includes(val)) ? '' : 'none';
  });
});

async function loadUsers() {
  const tbody = document.getElementById('users-tbody');
  tbody.innerHTML = '<tr><td colspan="6" style="text-align:center">Loading...</td></tr>';
  try {
    const res = await apiRequest('../api/users.php', 'GET');
    tbody.innerHTML = '';
    if (res.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6"><div class="empty-state">No users found</div></td></tr>';
      return;
    }
    
    res.forEach(u => {
      const tr = document.createElement('tr');
      tr.id = `row-${u.user_id}`;
      
      let badgeClass = 'badge-secondary';
      if (u.role === 'admin') badgeClass = 'badge-danger';
      else if (u.role === 'staff') badgeClass = 'badge-info';
      
      let actionBtn = '';
      if (u.role !== 'admin') {
        actionBtn = `<button class="btn btn-danger btn-sm" onclick="deleteUser(${u.user_id}, '${u.username}')">🗑️ Remove User</button>`;
      } else {
        actionBtn = `<span class="text-muted text-sm">Protected</span>`;
      }
      
      tr.innerHTML = `
        <td class="td-mono">${u.user_id}</td>
        <td><strong>${u.username}</strong></td>
        <td class="role-cell"><span class="badge ${badgeClass}">${u.role}</span></td>
        <td class="td-mono">${u.passenger_id ? u.passenger_id : '—'}</td>
        <td style="font-size:13px">${u.created_at}</td>
        <td>${actionBtn}</td>
      `;
      tbody.appendChild(tr);
    });
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="6" style="color:red;text-align:center">${err.message}</td></tr>`;
  }
}

function deleteUser(id, username) {
  confirmDelete(`Are you sure you want to completely remove user '${username}'?\n\nWARNING: This will permanently delete their account, their passenger profile, and ALL their bookings and payments!`, async () => {
    try {
      const res = await apiRequest('../api/users.php', 'DELETE', { user_id: id });
      showToast(res.message, 'success');
      document.getElementById('row-' + id)?.remove();
    } catch(err) { 
      showToast(err.message, 'error'); 
    }
  });
}

document.addEventListener('DOMContentLoaded', loadUsers);
</script>

<?php include '../includes/layout_foot.php'; ?>
