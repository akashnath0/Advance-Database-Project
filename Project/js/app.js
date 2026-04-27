// ============================================================
// Bangladesh Railway Management System — Main JS
// ============================================================

// ---- Toast Notifications ----
function showToast(message, type = 'success') {
  const icons = { success: '✅', error: '❌', info: 'ℹ️' };
  const container = document.getElementById('toast-container') || createToastContainer();
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span style="font-size:16px">${icons[type]||'🔔'}</span><span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 3500);
}

function createToastContainer() {
  const div = document.createElement('div');
  div.id = 'toast-container';
  div.className = 'toast-container';
  document.body.appendChild(div);
  return div;
}

// ---- Modal ----
function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}
// Close modal on backdrop click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-backdrop')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

// ---- Sidebar mobile toggle ----
let sidebar, menuBtn;
document.addEventListener('DOMContentLoaded', () => {
  sidebar = document.querySelector('.sidebar');
  menuBtn = document.getElementById('menu-toggle');
  if (menuBtn && sidebar) {
    menuBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
  }
});

// ---- Active nav ----
function setActiveNav() {
  const path = window.location.pathname.split('/').pop() || 'dashboard.php';
  document.querySelectorAll('.nav-item[data-page]').forEach(item => {
    const page = item.getAttribute('data-page');
    if (path.includes(page)) item.classList.add('active');
    else item.classList.remove('active');
  });
}
document.addEventListener('DOMContentLoaded', setActiveNav);

// ---- API Helper ----
async function apiRequest(url, method = 'GET', data = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  };
  if (data) opts.body = JSON.stringify(data);
  const res = await fetch(url, opts);
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || 'Request failed');
  return json;
}

// ---- Confirm Dialog ----
function confirmDelete(message, callback) {
  if (confirm(message || 'Are you sure you want to delete this record? This action cannot be undone.')) {
    callback();
  }
}

// ---- Table Search Filter ----
function setupTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;
  input.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ---- Format date ----
function formatDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

// ---- Highlight new rows ----
function highlightRow(rowEl) {
  rowEl.style.animation = 'none';
  rowEl.style.background = 'rgba(46,160,67,.12)';
  setTimeout(() => rowEl.style.background = '', 2000);
}

// ---- Generic CRUD form submit ----
async function submitForm(formEl, url, method, onSuccess) {
  const formData = new FormData(formEl);
  const data = Object.fromEntries(formData.entries());
  try {
    const res = await apiRequest(url, method, data);
    showToast(res.message || 'Operation successful!', 'success');
    if (onSuccess) onSuccess(res);
  } catch (err) {
    showToast(err.message, 'error');
  }
}

// ---- Number formatter ----
function formatCurrency(n) {
  return '৳ ' + parseFloat(n).toLocaleString('en-BD', { minimumFractionDigits: 2 });
}

// ---- Countdown for session ----
let sessionTimer;
function startSessionTimer(seconds) {
  clearInterval(sessionTimer);
  let remaining = seconds;
  sessionTimer = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
      clearInterval(sessionTimer);
      showToast('Session expired. Redirecting to login...', 'error');
      setTimeout(() => window.location.href = '/login.php', 2000);
    }
  }, 1000);
}

// ---- Animate counters on dashboard ----
function animateCounter(el, target, duration = 1200) {
  let start = 0;
  const step = (timestamp) => {
    if (!start) start = timestamp;
    const progress = Math.min((timestamp - start) / duration, 1);
    el.textContent = Math.floor(progress * target).toLocaleString();
    if (progress < 1) requestAnimationFrame(step);
  };
  requestAnimationFrame(step);
}
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.stat-value[data-count]').forEach(el => {
    animateCounter(el, parseInt(el.getAttribute('data-count')));
  });
});

// ---- Provenance: Highlight changed fields ----
function highlightChanges() {
  document.querySelectorAll('.change-row').forEach(row => {
    const old = row.querySelector('.old-val');
    const nw  = row.querySelector('.new-val');
    if (old && nw && old.textContent.trim() !== nw.textContent.trim()) {
      row.style.background = 'rgba(31,111,235,.08)';
      row.style.borderRadius = '4px';
    }
  });
}
document.addEventListener('DOMContentLoaded', highlightChanges);
