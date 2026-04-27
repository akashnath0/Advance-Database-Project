<?php
// ============================================================
// includes/auth.php — Session auth for MySQL version
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();

function login($username, $password) {
    require_once __DIR__ . '/db.php';
    $user = db()->fetchOne(
        "SELECT * FROM UserDetails WHERE username = :u AND user_activated = 1",
        [':u' => $username]
    );
    if (!$user) return false;

    // Verify bcrypt hash
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id']      = $user['user_id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['passenger_id'] = $user['passenger_id'] ?? null;
        session_regenerate_id(true);
        return true;
    }
    // Fallback: plain-text check (for demo seeded data before setup.php is run)
    if ($password === $user['password']) {
        $_SESSION['user_id']      = $user['user_id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['role']         = $user['role'];
        $_SESSION['passenger_id'] = $user['passenger_id'] ?? null;
        session_regenerate_id(true);
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /Project/login.php');
    exit;
}

function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /Project/login.php');
        exit;
    }
}

function currentUser() {
    return [
        'id'           => $_SESSION['user_id']      ?? null,
        'username'     => $_SESSION['username']     ?? 'Guest',
        'role'         => $_SESSION['role']         ?? 'viewer',
        'passenger_id' => $_SESSION['passenger_id'] ?? null,
    ];
}

function isAdmin() {
    return ($_SESSION['role'] ?? '') === 'admin';
}

function isStaff() {
    $role = $_SESSION['role'] ?? '';
    return $role === 'admin' || $role === 'staff';
}

function isViewer() {
    return ($_SESSION['role'] ?? '') === 'viewer';
}

/**
 * Redirect viewers to dashboard; only admin/staff may proceed.
 */
function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        header('Location: /Project/pages/dashboard.php?access=denied');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /Project/pages/dashboard.php?access=denied');
        exit;
    }
}
