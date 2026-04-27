<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Bangladesh Railway</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .auth-page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--bg-main);
      padding: 24px;
    }
    .auth-wrapper {
      display: flex;
      gap: 40px;
      width: 100%;
      max-width: 960px;
      align-items: flex-start;
    }
    .auth-brand {
      flex: 1;
      padding-top: 32px;
    }
    .auth-brand .brand-logo {
      font-size: 48px;
      margin-bottom: 16px;
    }
    .auth-brand h1 {
      font-size: 28px;
      font-weight: 800;
      background: linear-gradient(135deg, var(--green-light), var(--cyan));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 12px;
    }
    .auth-brand p {
      color: var(--text-muted);
      font-size: 14px;
      line-height: 1.6;
    }
    .auth-features {
      margin-top: 28px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .auth-feature {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      background: var(--bg-surface);
      border-radius: 10px;
      border: 1px solid var(--border);
    }
    .auth-feature span:first-child { font-size: 20px; }
    .auth-feature .feat-text { font-size: 13px; color: var(--text-secondary); }

    .register-card {
      flex: 1.2;
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 36px 32px;
      backdrop-filter: blur(20px);
      box-shadow: 0 20px 60px rgba(0,0,0,.4);
    }
    .register-card h2 {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 4px;
    }
    .register-card .subtitle {
      font-size: 13px;
      color: var(--text-muted);
      margin-bottom: 24px;
    }
    .section-divider {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--text-muted);
      padding: 12px 0 8px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 16px;
    }
    .alert-msg {
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 13px;
      margin-bottom: 16px;
      display: none;
    }
    .alert-msg.error   { background: rgba(218,54,51,.15); border: 1px solid var(--red-light);   color: var(--red-light); }
    .alert-msg.success { background: rgba(46,160,67,.15);  border: 1px solid var(--green-light); color: var(--green-light); }
    .alert-msg.show    { display: block; }
    .step-progress {
      display: flex;
      gap: 0;
      margin-bottom: 24px;
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid var(--border);
    }
    .step {
      flex: 1;
      text-align: center;
      padding: 10px;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-muted);
      background: var(--bg-main);
      cursor: pointer;
      transition: all .2s;
    }
    .step.active { background: var(--blue); color: #fff; }
    .step.done   { background: var(--green); color: #fff; }
    .form-step   { display: none; }
    .form-step.active { display: block; }
    .password-strength {
      height: 4px;
      border-radius: 2px;
      background: var(--border);
      margin-top: 6px;
      overflow: hidden;
    }
    .ps-bar { height: 100%; border-radius: 2px; transition: width .3s, background .3s; width: 0; }

    @media (max-width: 768px) {
      .auth-wrapper { flex-direction: column; }
      .auth-brand { display: none; }
    }
  </style>
</head>
<body>
<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php'); exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- Account Info
    $username        = sanitize($_POST['username']        ?? '');
    $password        = sanitize($_POST['password']        ?? '');
    $confirm_pass    = sanitize($_POST['confirm_password']?? '');
    $role            = sanitize($_POST['role'] ?? 'viewer');
    if (!in_array($role, ['admin', 'staff', 'viewer'])) $role = 'viewer';

    // ---- Passenger Info
    $first_name      = sanitize($_POST['first_name']      ?? '');
    $last_name       = sanitize($_POST['last_name']       ?? '');
    $gender          = sanitize($_POST['gender']          ?? '');
    $contact_number  = sanitize($_POST['contact_number']  ?? '');
    $date_of_birth   = sanitize($_POST['date_of_birth']   ?? '');

    // ---- Validations
    if (!$username || !$password || !$first_name || !$contact_number || !$date_of_birth) {
        $error = 'All required fields must be filled.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Passwords do not match.';
    } elseif (db()->fetchOne("SELECT 1 FROM UserDetails WHERE username = :u", [':u' => $username])) {
        $error = "Username '$username' is already taken. Please choose another.";
    } elseif (!preg_match('/^[0-9]{11}$/', $contact_number)) {
        $error = 'Contact number must be exactly 11 digits.';
    } else {
        // Generate Passenger ID
        do {
            $pass_id = 'PS' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists  = db()->fetchOne("SELECT 1 FROM Passenger WHERE passenger_id = :id", [':id' => $pass_id]);
        } while ($exists);

        // Create Passenger record (triggers Passenger_Audit INSERT)
        $ok1 = db()->execute(
            "INSERT INTO Passenger (passenger_id, first_name, last_name, gender, contact_number, date_of_birth)
             VALUES (:id, :fn, :ln, :g, :cn, :dob)",
            [':id'=>$pass_id, ':fn'=>$first_name, ':ln'=>$last_name, ':g'=>$gender, ':cn'=>$contact_number, ':dob'=>$date_of_birth]
        );

        // Create UserDetails with hashed password
        $ok2 = db()->execute(
            "INSERT INTO UserDetails (username, password, role, user_activated, passenger_id) VALUES (:u, :p, :r, 1, :pid)",
            [':u' => $username, ':p' => password_hash($password, PASSWORD_DEFAULT), ':r' => $role, ':pid' => $pass_id]
        );

        if ($ok1 && $ok2) {
            $success = "Registration successful! Your Passenger ID is <strong>$pass_id</strong>. You can now <a href='login.php' style='color:var(--green-light);font-weight:700'>login</a>.";
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<div class="auth-page">
  <div class="auth-wrapper">

    <!-- Brand Side -->
    <div class="auth-brand">
      <div class="brand-logo">🚂</div>
      <h1>Bangladesh Railway</h1>
      <p>Create your account to access the complete Railway Management platform with full audit trail tracking and data provenance.</p>
      <div class="auth-features">
        <div class="auth-feature"><span>🎫</span><span class="feat-text">Book train tickets with seat selection</span></div>
        <div class="auth-feature"><span>📋</span><span class="feat-text">Full data provenance — every action tracked</span></div>
        <div class="auth-feature"><span>📊</span><span class="feat-text">View travel history and analytics</span></div>
        <div class="auth-feature"><span>🔐</span><span class="feat-text">Secure bcrypt password hashing</span></div>
      </div>
    </div>

    <!-- Registration Form -->
    <div class="register-card">
      <h2>✨ Create Account</h2>
      <p class="subtitle">Fill in your details below to register as a passenger</p>

      <?php if ($error): ?>
      <div class="alert-msg error show">⚠️ <?= $error ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
      <div class="alert-msg success show">✅ <?= $success ?></div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" action="register.php" id="reg-form" onsubmit="return validateForm()">

        <div class="section-divider">👤 Account Information</div>
        <div class="form-grid">
          <div class="form-group full">
            <label>Username *</label>
            <input type="text" name="username" class="form-control" required minlength="3"
                   placeholder="Choose a username" value="<?= htmlspecialchars($_POST['username']??'') ?>"
                   oninput="checkUsername(this.value)">
            <div id="username-feedback" style="font-size:12px;margin-top:4px"></div>
          </div>
          <div class="form-group">
            <label>Password *</label>
            <div style="position:relative">
              <input type="password" name="password" id="password" class="form-control" required minlength="6"
                     placeholder="Min 6 characters" oninput="checkStrength(this.value)">
              <button type="button" onclick="togglePwd('password',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted)">👁️</button>
            </div>
            <div class="password-strength"><div class="ps-bar" id="ps-bar"></div></div>
            <div id="ps-label" style="font-size:11px;margin-top:2px;color:var(--text-muted)"></div>
          </div>
          <div class="form-group">
            <label>Confirm Password *</label>
            <div style="position:relative">
              <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Repeat password">
              <button type="button" onclick="togglePwd('confirm_password',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted)">👁️</button>
            </div>
          </div>
          <div class="form-group full">
            <label>Account Role *</label>
            <select name="role" class="form-control" required>
              <option value="viewer" <?= ($_POST['role']??'')==='viewer'?'selected':'' ?>>Viewer (Passenger)</option>
              <option value="staff"  <?= ($_POST['role']??'')==='staff' ?'selected':'' ?>>Staff</option>
              <option value="admin"  <?= ($_POST['role']??'')==='admin' ?'selected':'' ?>>Admin</option>
            </select>
          </div>
        </div>

        <div class="section-divider" style="margin-top:8px">🧍 Personal Information</div>
        <div class="form-grid">
          <div class="form-group">
            <label>First Name *</label>
            <input type="text" name="first_name" class="form-control" required placeholder="e.g. Abdullah"
                   value="<?= htmlspecialchars($_POST['first_name']??'') ?>">
          </div>
          <div class="form-group">
            <label>Last Name</label>
            <input type="text" name="last_name" class="form-control" placeholder="e.g. Al Mamun"
                   value="<?= htmlspecialchars($_POST['last_name']??'') ?>">
          </div>
          <div class="form-group">
            <label>Gender</label>
            <select name="gender" class="form-control">
              <option value="">— Select —</option>
              <option value="Male"   <?= ($_POST['gender']??'')==='Male'  ?'selected':'' ?>>Male</option>
              <option value="Female" <?= ($_POST['gender']??'')==='Female'?'selected':'' ?>>Female</option>
              <option value="Other"  <?= ($_POST['gender']??'')==='Other' ?'selected':'' ?>>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label>Contact Number * <span class="text-muted text-sm">(11 digits)</span></label>
            <input type="tel" name="contact_number" class="form-control" required maxlength="11" minlength="11"
                   placeholder="017XXXXXXXX" value="<?= htmlspecialchars($_POST['contact_number']??'') ?>">
          </div>
          <div class="form-group full">
            <label>Date of Birth *</label>
            <input type="date" name="date_of_birth" class="form-control" required
                   max="<?= date('Y-m-d', strtotime('-5 years')) ?>"
                   value="<?= htmlspecialchars($_POST['date_of_birth']??'') ?>">
          </div>
        </div>

        <div style="margin-top:8px;padding:12px 14px;background:rgba(46,160,67,.08);border:1px solid rgba(46,160,67,.2);border-radius:8px;font-size:12px;color:var(--text-muted)">
          🔒 Your data is stored securely. A <strong>Passenger ID</strong> will be automatically assigned to your account.
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;align-items:center">
          <button type="submit" class="btn btn-primary" style="flex:1;padding:14px;font-size:15px">
            🚀 Create Account
          </button>
        </div>
        <p style="text-align:center;font-size:13px;color:var(--text-muted);margin-top:16px">
          Already have an account? <a href="login.php" style="color:var(--blue-light);font-weight:600">Sign In →</a>
        </p>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
let usernameTimer;
function checkUsername(val) {
  const el = document.getElementById('username-feedback');
  clearTimeout(usernameTimer);
  if (val.length < 3) { el.textContent = ''; return; }
  usernameTimer = setTimeout(async () => {
    const res = await fetch('api/register.php?check_username=' + encodeURIComponent(val));
    const data = await res.json();
    el.textContent = data.available ? '✅ Username is available' : '❌ Username is taken';
    el.style.color  = data.available ? 'var(--green-light)' : 'var(--red-light)';
  }, 400);
}

function checkStrength(val) {
  const bar   = document.getElementById('ps-bar');
  const label = document.getElementById('ps-label');
  let score   = 0;
  if (val.length >= 6)              score++;
  if (val.length >= 10)             score++;
  if (/[A-Z]/.test(val))           score++;
  if (/[0-9]/.test(val))           score++;
  if (/[^A-Za-z0-9]/.test(val))   score++;
  const levels = [
    {w:'0',  bg:'transparent', txt:''},
    {w:'20%', bg:'var(--red-light)',   txt:'Weak'},
    {w:'40%', bg:'var(--amber-light)', txt:'Fair'},
    {w:'60%', bg:'var(--amber-light)', txt:'Good'},
    {w:'80%', bg:'var(--green-light)', txt:'Strong'},
    {w:'100%',bg:'var(--green-light)', txt:'Very Strong'},
  ];
  const l = levels[Math.min(score, 5)];
  bar.style.width      = l.w;
  bar.style.background = l.bg;
  label.textContent    = l.txt;
  label.style.color    = l.bg;
}

function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  inp.type  = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
}

function validateForm() {
  const pw  = document.getElementById('password').value;
  const cpw = document.getElementById('confirm_password').value;
  if (pw !== cpw) {
    alert('Passwords do not match!');
    return false;
  }
  return true;
}
</script>
</body>
</html>
