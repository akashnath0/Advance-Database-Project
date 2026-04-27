<?php
// ============================================================
// includes/functions.php — Utility helpers
// ============================================================

/**
 * Return a safe, HTML-escaped string
 */
function sanitize($value) {
    return is_string($value) ? trim(htmlspecialchars_decode(strip_tags(trim($value)))) : $value;
}

/**
 * Output JSON and exit
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Return CSS badge class for a payment/booking status
 */
function statusBadge($status) {
    return match (strtoupper($status ?? '')) {
        'PAID', 'ACTIVE'      => 'badge-success',
        'PENDING'             => 'badge-warning',
        'REFUNDED', 'CANCELLED' => 'badge-danger',
        'MAINTENANCE'         => 'badge-amber',
        default               => 'badge-secondary',
    };
}

/**
 * Format a date for display
 */
function fmtDate($date, $format = 'd M Y') {
    if (!$date) return '—';
    return date($format, strtotime($date));
}

/**
 * Get client IP
 */
function clientIp() {
    return $_SERVER['HTTP_CLIENT_IP']
        ?? $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '0.0.0.0';
}
