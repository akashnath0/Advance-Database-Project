<?php
// Username availability check for registration
require_once '../includes/db.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (isset($_GET['check_username'])) {
    $un    = sanitize($_GET['check_username']);
    $taken = db()->fetchOne("SELECT 1 FROM UserDetails WHERE username = :u", [':u' => $un]);
    echo json_encode(['available' => !$taken]);
    exit;
}

jsonResponse(['error' => 'Invalid request'], 400);
