<?php
// ============================================================
// API: Users — Manage users (Admins only)
// ============================================================
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
if (!isAdmin()) jsonResponse(['error' => 'Forbidden: Admins only'], 403);

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'GET':
        $users = db()->fetchAll("
            SELECT user_id, username, role, user_activated, created_at, passenger_id
            FROM UserDetails
            WHERE user_id != :uid
            ORDER BY created_at DESC
        ", [':uid' => $_SESSION['user_id']]);
        jsonResponse($users);

    case 'DELETE':
        $user_id = sanitize($input['user_id'] ?? '');
        if (!$user_id) jsonResponse(['error' => 'User ID required.'], 400);

        // Prevent deleting oneself just in case
        if ($user_id == $_SESSION['user_id']) {
            jsonResponse(['error' => 'Cannot delete your own account.'], 403);
        }

        // Fetch user info to verify role and passenger_id
        $user = db()->fetchOne("SELECT role, passenger_id FROM UserDetails WHERE user_id = :id", [':id' => $user_id]);
        if (!$user) jsonResponse(['error' => 'User not found.'], 404);

        if ($user['role'] === 'admin') {
            jsonResponse(['error' => 'Cannot delete another admin.'], 403);
        }

        // Delete underlying data if requested by the user
        if ($user['passenger_id']) {
            $pid = $user['passenger_id'];
            // Get all payment IDs associated with this passenger's bookings to clean them up
            $bookings = db()->fetchAll("SELECT payment_id FROM Booking WHERE passenger_id = :pid", [':pid' => $pid]);
            $payment_ids = array_filter(array_column($bookings, 'payment_id'));

            // Delete passenger (this cascades to Bookings automatically due to ON DELETE CASCADE)
            db()->execute("DELETE FROM Passenger WHERE passenger_id = :pid", [':pid' => $pid]);

            // Delete orphaned payments
            foreach ($payment_ids as $pay_id) {
                db()->execute("DELETE FROM Payment WHERE payment_id = :pid", [':pid' => $pay_id]);
            }
        }

        // Finally, delete the UserDetails record
        db()->execute("DELETE FROM UserDetails WHERE user_id = :id", [':id' => $user_id]);

        jsonResponse(['message' => "User and all associated data deleted successfully."]);

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
