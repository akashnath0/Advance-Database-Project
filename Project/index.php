<?php
// Redirect root to login
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    header('Location: pages/dashboard.php'); exit;
}
header('Location: login.php'); exit;
