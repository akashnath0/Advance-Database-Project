<?php
// Shared layout — include at top of every page
// Usage: include __DIR__.'/../includes/layout_head.php';
// Set $pageTitle and $activePage before including

$user = currentUser();
$uname = htmlspecialchars($user['username'] ?? 'User');
$role  = htmlspecialchars($user['role'] ?? 'viewer');
$avatar= strtoupper(substr($uname, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Railway Management') ?> — BRMS</title>
  <meta name="description" content="Bangladesh Railway Management System — <?= htmlspecialchars($pageTitle ?? '') ?>">
  <link rel="stylesheet" href="../css/style.css">
  <script src="../js/app.js"></script>
</head>
<body>
<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon">🚂</div>
      <div class="brand-text">
        <h1>Railway MgmtSys</h1>
        <span>Bangladesh Railway</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-label">Overview</div>
      <a href="dashboard.php" class="nav-item<?= ($activePage==='dashboard')?' active':'' ?>" data-page="dashboard">
        <span class="nav-icon">📊</span> Dashboard
      </a>

      <div class="nav-section-label">Operations</div>
      <a href="trains.php" class="nav-item<?= ($activePage==='trains')?' active':'' ?>" data-page="trains">
        <span class="nav-icon">🚂</span> Trains
      </a>
      <a href="routes.php" class="nav-item<?= ($activePage==='routes')?' active':'' ?>" data-page="routes">
        <span class="nav-icon">🗺️</span> Routes &amp; Schedules
      </a>
      <?php if (isStaff()): ?>
      <a href="coaches.php" class="nav-item<?= ($activePage==='coaches')?' active':'' ?>" data-page="coaches">
        <span class="nav-icon">🚃</span> Coaches
      </a>
      <?php endif; ?>

      <div class="nav-section-label">People</div>
      <?php if (isStaff()): ?>
      <a href="passengers.php" class="nav-item<?= ($activePage==='passengers')?' active':'' ?>" data-page="passengers">
        <span class="nav-icon">👥</span> Passengers
      </a>
      <a href="employees.php" class="nav-item<?= ($activePage==='employees')?' active':'' ?>" data-page="employees">
        <span class="nav-icon">👷</span> Employees
      </a>
      <?php endif; ?>
      <?php if (isAdmin()): ?>
      <a href="users.php" class="nav-item<?= ($activePage==='users')?' active':'' ?>" data-page="users">
        <span class="nav-icon">🔐</span> Users
      </a>
      <?php endif; ?>

      <div class="nav-section-label">Ticketing</div>
      <a href="bookings.php" class="nav-item<?= ($activePage==='bookings')?' active':'' ?>" data-page="bookings">
        <span class="nav-icon">🎫</span> Bookings
      </a>
      <a href="payments.php" class="nav-item<?= ($activePage==='payments')?' active':'' ?>" data-page="payments">
        <span class="nav-icon">💳</span> Payments
      </a>

      <div class="nav-section-label">Maintenance</div>
      <?php if (isStaff()): ?>
      <a href="maintenance.php" class="nav-item<?= ($activePage==='maintenance')?' active':'' ?>" data-page="maintenance">
        <span class="nav-icon">🔧</span> Maintenance
      </a>
      <a href="technicians.php" class="nav-item<?= ($activePage==='technicians')?' active':'' ?>" data-page="technicians">
        <span class="nav-icon">🧰</span> Technicians
      </a>
      <?php endif; ?>

      <div class="nav-section-label">Provenance</div>
      <?php if (isStaff()): ?>
      <a href="audit.php" class="nav-item<?= ($activePage==='audit')?' active':'' ?>" data-page="audit">
        <span class="nav-icon">📋</span> Audit Trail
      </a>
      <a href="reports.php" class="nav-item<?= ($activePage==='reports')?' active':'' ?>" data-page="reports">
        <span class="nav-icon">📈</span> Reports
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-card">
        <div class="user-avatar"><?= $avatar ?></div>
        <div class="user-info">
          <div class="uname"><?= $uname ?></div>
          <div class="urole"><?= $role ?></div>
        </div>
        <a href="../logout.php" class="btn-logout" title="Logout">⏻</a>
      </div>
    </div>
  </aside>

  <!-- Main area -->
  <div class="main-area">
    <!-- Topbar -->
    <header class="topbar">
      <button id="menu-toggle" style="background:none;border:none;color:var(--text-secondary);font-size:20px;cursor:pointer;display:none">☰</button>
      <h2 class="topbar-title"><?= htmlspecialchars($pageTitle ?? '') ?></h2>
      <div class="topbar-search">
        <span class="topbar-icon">🔍</span>
        <input type="text" id="global-search" placeholder="Quick search..." autocomplete="off">
      </div>
    </header>

    <!-- Page Content -->
    <main class="page-content fade-in">
    <?php if (isset($_GET['access']) && $_GET['access']==='denied'): ?>
    <div style="display:flex;align-items:center;gap:12px;background:rgba(220,53,69,.12);border:1px solid rgba(220,53,69,.35);border-radius:10px;padding:14px 20px;margin-bottom:20px;color:#ff6b6b;font-size:14px">
      <span style="font-size:20px">🔒</span>
      <div><strong>Access Restricted.</strong> Your <em>Viewer</em> role does not have permission to access that section.</div>
    </div>
    <?php endif; ?>
