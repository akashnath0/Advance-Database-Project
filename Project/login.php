<?php
// Updated login.php — calls updated auth library
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php'); exit;
}

require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$username || !$password) {
        $error = 'Please enter both username and password.';
    } elseif (login($username, $password)) {
        header('Location: pages/dashboard.php'); exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Bangladesh Railway Management System</title>
  <meta name="description" content="Secure login for the Bangladesh Railway Data Provenance Management System.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .login-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-main);
      padding: 24px;
    }
    .login-wrapper {
      display: flex;
      gap: 60px;
      width: 100%;
      max-width: 860px;
      align-items: center;
    }
    .login-brand { flex: 1; }
    .train-icon { font-size: 72px; line-height: 1; margin-bottom: 20px; display: block; animation: trainPulse 2s infinite; }
    @keyframes trainPulse { 0%,100%{transform:translateX(0)} 50%{transform:translateX(6px)} }
    .brand-name {
      font-size: 32px; font-weight: 800;
      background: linear-gradient(135deg, #fff 0%, var(--blue-light) 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
      margin-bottom: 8px; line-height: 1.2;
    }
    .brand-sub { font-size: 13px; color: var(--text-muted); line-height: 1.6; }
    .stat-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 24px; }
    .chip { padding: 6px 12px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: 20px; font-size: 12px; color: var(--text-secondary); }
    .login-card {
      flex: 1; max-width: 380px;
      background: var(--bg-surface); border: 1px solid var(--border); border-radius: 20px;
      padding: 36px 28px; box-shadow: 0 20px 60px rgba(0,0,0,.4);
    }
    .login-card h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
    .login-card .sub { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }
    .error-box { padding:10px 14px; background:rgba(218,54,51,.12); border:1px solid var(--red-light); border-radius:8px; font-size:13px; color:var(--red-light); margin-bottom:16px; }
    .input-group { position: relative; }
    .input-group .input-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:16px; }
    .input-group input { padding-left: 38px; }
    .corner-link { text-align:center; margin-top:16px; font-size:13px; color:var(--text-muted); }
    .corner-link a { color:var(--blue-light); font-weight:600; text-decoration:none; }
    @media(max-width:700px){ .login-brand{display:none} .login-card{max-width:100%} }
  </style>
</head>
<body>
<div class="login-page">
  <div class="login-wrapper">

    <div class="login-brand">
      <span class="train-icon">🚂</span>
      <div class="brand-name">Bangladesh Railway</div>
      <div class="brand-name" style="font-size:20px;margin-top:4px">Management System</div>
      <div class="stat-chips">
        <div class="chip">📋 Full Audit Trail</div>
        <div class="chip">🔍 Provenance Explorer</div>
        <div class="chip">🚂 Fleet Manager</div>
        <div class="chip">🎫 Booking Engine</div>
        <div class="chip">📊 Analytics</div>
        <div class="chip">🔐 Role-Based Access</div>
      </div>
    </div>

    <div class="login-card">
      <h2>🔑 Sign In</h2>
      <p class="sub">Enter your credentials to access the system</p>

      <?php if ($error): ?>
      <div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php">
        <div class="form-group">
          <label>Username</label>
          <div class="input-group">
            <span class="input-icon">👤</span>
            <input type="text" name="username" class="form-control" placeholder="username"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
          </div>
        </div>
        <div class="form-group" style="margin-top:14px">
          <label>Password</label>
          <div class="input-group" style="position:relative">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="pwd-field" class="form-control" placeholder="Enter password" required autocomplete="current-password">
            <button type="button" onclick="togglePwd()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:16px;color:var(--text-muted)">👁️</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:20px;padding:14px;font-size:15px;font-weight:700">
          🚀 Login
        </button>
      </form>

      <div class="corner-link">
        New user? <a href="register.php">Create account →</a>
      </div>
    </div>
  </div>
</div>
<script>
function togglePwd() {
  const f = document.getElementById('pwd-field');
  f.type = f.type === 'password' ? 'text' : 'password';
}
</script>
</body>
</html>
