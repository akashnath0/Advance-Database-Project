<?php
// ============================================================
// setup.php — One-click setup for MySQL version
// Run ONCE then delete or restrict!
// ============================================================
require_once 'includes/db.php';
require_once 'includes/functions.php';

$results = [];
$errors  = [];

// Seed demo user passwords
$users = [
    ['admin',  'admin123',  'admin'],
    ['staff1', 'staff123',  'staff'],
    ['viewer', 'view123',   'viewer'],
];

foreach ($users as [$uname, $pass, $role]) {
    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $exists = db()->fetchOne("SELECT user_id FROM UserDetails WHERE username = :u", [':u' => $uname]);
    if ($exists) {
        db()->execute("UPDATE UserDetails SET password = :p, role = :r WHERE username = :u", [':p'=>$hash, ':r'=>$role, ':u'=>$uname]);
        $results[] = "✅ Updated user: <strong>$uname</strong>";
    } else {
        db()->execute("INSERT INTO UserDetails (username, password, role, user_activated) VALUES (:u,:p,:r,1)", [':u'=>$uname,':p'=>$hash,':r'=>$role]);
        $results[] = "✅ Created user: <strong>$uname</strong>";
    }
}

// Verify required tables
$required = ['UserDetails','Passenger','Payment','Schedule','Route','Employee','Train','Coach','Booking','Technician','Maintenance','Driver','Platform_Staff',
             'Passenger_Audit','Payment_Audit','Train_Audit','Booking_Audit','Employee_Audit','Maintenance_Audit',
             'Route_Audit','Schedule_Audit','Coach_Audit'];
$missing = [];
foreach ($required as $tbl) {
    try {
        db()->fetchOne("SELECT 1 FROM `$tbl` LIMIT 1");
    } catch (Exception $e) {
        $missing[] = $tbl;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>BRMS Setup</title>
  <link rel="stylesheet" href="css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>body{padding:40px;max-width:720px;margin:0 auto}</style>
</head>
<body>
<div class="card">
  <div class="card-header">
    <div class="card-title">🛠️ Bangladesh Railway — Database Setup</div>
    <span class="badge badge-info">MySQL / XAMPP</span>
  </div>
  <div class="card-body">

    <h3 style="margin-bottom:12px;color:var(--text-secondary)">Step 1 — Run Schema</h3>
    <div style="padding:14px;background:var(--bg-main);border-radius:8px;font-size:13px;color:var(--text-muted);margin-bottom:20px">
      Open <strong>phpMyAdmin → SQL</strong> and paste the contents of:<br>
      <code style="color:var(--blue-light)">db/schema.sql</code> then
      <code style="color:var(--blue-light)">db/triggers.sql</code>
    </div>

    <h3 style="margin-bottom:12px;color:var(--text-secondary)">Step 2 — User Passwords</h3>
    <?php foreach ($results as $r): ?>
    <div style="padding:8px 12px;margin-bottom:6px;background:var(--bg-surface);border-radius:6px;border-left:3px solid var(--green-light);font-size:13px"><?= $r ?></div>
    <?php endforeach; ?>

    <h3 style="margin-top:20px;margin-bottom:12px;color:var(--text-secondary)">Step 3 — Table Check</h3>
    <?php if ($missing): ?>
    <div style="padding:16px;background:rgba(218,54,51,.1);border:1px solid var(--red-light);border-radius:8px">
      <strong style="color:var(--red-light)">⚠️ Missing Tables (<?= count($missing) ?>)</strong>
      <p style="font-size:13px;color:var(--text-secondary);margin:8px 0">Run the SQL files in phpMyAdmin first!</p>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:8px">
        <?php foreach ($missing as $m): ?><span class="badge badge-danger"><?= $m ?></span><?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div style="padding:14px 16px;background:rgba(46,160,67,.1);border:1px solid var(--green-light);border-radius:8px">
      <strong style="color:var(--green-light)">✅ All <?= count($required) ?> tables found and ready!</strong>
    </div>
    <?php endif; ?>

    <div style="margin-top:24px;padding:16px;background:var(--bg-surface);border-radius:10px">
      <h4 style="margin-bottom:12px">🔐 Demo Login Credentials</h4>
      <table style="width:100%;font-size:13px;border-collapse:collapse">
        <tr style="border-bottom:1px solid var(--border)">
          <th style="text-align:left;padding:8px;color:var(--text-muted)">Username</th>
          <th style="text-align:left;padding:8px;color:var(--text-muted)">Password</th>
          <th style="text-align:left;padding:8px;color:var(--text-muted)">Role</th>
        </tr>
        <tr><td style="padding:8px"><code>admin</code></td><td style="padding:8px"><code>admin123</code></td><td style="padding:8px"><span class="badge badge-danger">Admin</span></td></tr>
        <tr><td style="padding:8px"><code>staff1</code></td><td style="padding:8px"><code>staff123</code></td><td style="padding:8px"><span class="badge badge-info">Staff</span></td></tr>
        <tr><td style="padding:8px"><code>viewer</code></td><td style="padding:8px"><code>view123</code></td><td style="padding:8px"><span class="badge badge-secondary">Viewer</span></td></tr>
      </table>
    </div>

    <div style="margin-top:16px;padding:12px 16px;background:rgba(218,54,51,.08);border:1px solid rgba(218,54,51,.2);border-radius:8px;font-size:13px">
      ⚠️ <strong style="color:var(--amber-light)">Security:</strong> Delete <code>setup.php</code> after setup is complete!
    </div>

    <div style="margin-top:24px;display:flex;gap:10px">
      <a href="login.php"    class="btn btn-primary">🔑 Go to Login</a>
      <a href="register.php" class="btn btn-ghost">✨ Register</a>
      <?php if (empty($missing)): ?>
      <a href="pages/dashboard.php" class="btn btn-ghost">📊 Dashboard</a>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
