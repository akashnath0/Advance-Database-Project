<?php
// ============================================================
// API: Passengers — CRUD (MySQL PDO Version)
// ============================================================
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
if (isViewer()) jsonResponse(['error' => 'Forbidden: Viewer access restricted'], 403);

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$ip     = $_SERVER['REMOTE_ADDR'] ?? null;

switch ($method) {

    case 'POST':
        $first_name     = sanitize($input['first_name']     ?? '');
        $last_name      = sanitize($input['last_name']      ?? '');
        $gender         = sanitize($input['gender']         ?? '');
        $contact_number = sanitize($input['contact_number'] ?? '');
        $date_of_birth  = sanitize($input['date_of_birth']  ?? '');

        if (!$first_name || !$contact_number || !$date_of_birth)
            jsonResponse(['error' => 'First name, contact, and date of birth are required.'], 400);

        do {
            $id = 'PS' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists = db()->fetchOne("SELECT 1 FROM Passenger WHERE passenger_id = :id", [':id' => $id]);
        } while ($exists);

        $ok = db()->execute(
            "INSERT INTO Passenger (passenger_id, first_name, last_name, gender, contact_number, date_of_birth)
             VALUES (:id, :fn, :ln, :g, :cn, :dob)",
            [':id'=>$id, ':fn'=>$first_name, ':ln'=>$last_name, ':g'=>$gender, ':cn'=>$contact_number, ':dob'=>$date_of_birth]
        );
        // Log IP to audit (update last inserted audit row)
        if ($ok && $ip) db()->execute("UPDATE Passenger_Audit SET ip_address=:ip ORDER BY audit_id DESC LIMIT 1", [':ip'=>$ip]);

        jsonResponse($ok
            ? ['message' => "Passenger $id created!", 'passenger_id' => $id]
            : ['error' => 'Failed to create passenger.'], $ok ? 200 : 500);

    case 'PUT':
        $passenger_id   = sanitize($input['passenger_id']   ?? '');
        $first_name     = sanitize($input['first_name']     ?? '');
        $last_name      = sanitize($input['last_name']      ?? '');
        $gender         = sanitize($input['gender']         ?? '');
        $contact_number = sanitize($input['contact_number'] ?? '');
        $date_of_birth  = sanitize($input['date_of_birth']  ?? '');

        if (!$passenger_id) jsonResponse(['error' => 'Passenger ID required.'], 400);

        $ok = db()->execute(
            "UPDATE Passenger SET first_name=:fn, last_name=:ln, gender=:g, contact_number=:cn, date_of_birth=:dob WHERE passenger_id=:id",
            [':fn'=>$first_name, ':ln'=>$last_name, ':g'=>$gender, ':cn'=>$contact_number, ':dob'=>$date_of_birth, ':id'=>$passenger_id]
        );
        if ($ok && $ip) db()->execute("UPDATE Passenger_Audit SET ip_address=:ip WHERE passenger_id=:id ORDER BY audit_id DESC LIMIT 1", [':ip'=>$ip, ':id'=>$passenger_id]);

        jsonResponse($ok ? ['message' => "Passenger $passenger_id updated. Audit trail recorded."] : ['error' => 'Update failed.'], $ok ? 200 : 500);

    case 'DELETE':
        $passenger_id = sanitize($input['passenger_id'] ?? '');
        if (!$passenger_id) jsonResponse(['error' => 'Passenger ID required.'], 400);

        db()->execute("DELETE FROM Booking WHERE passenger_id = :id", [':id' => $passenger_id]);
        $ok = db()->execute("DELETE FROM Passenger WHERE passenger_id = :id", [':id' => $passenger_id]);

        jsonResponse($ok ? ['message' => "Passenger $passenger_id deleted."] : ['error' => 'Delete failed.'], $ok ? 200 : 500);

    case 'GET':
        $id = sanitize($_GET['id'] ?? '');
        if ($id) {
            $row = db()->fetchOne("SELECT * FROM Passenger WHERE passenger_id = :id", [':id' => $id]);
            jsonResponse($row ?: ['error' => 'Not found'], $row ? 200 : 404);
        }
        jsonResponse(db()->fetchAll("SELECT * FROM Passenger ORDER BY passenger_id"));

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
