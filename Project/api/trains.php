<?php
// ============================================================
// API: Trains — CRUD (MySQL PDO Version)
// ============================================================
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
if (isViewer() && $_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Forbidden: Viewer cannot modify data'], 403);

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'POST':
        $train_name  = sanitize($input['train_name']  ?? '');
        $type        = sanitize($input['type']        ?? 'Express');
        $route_id    = sanitize($input['route_id']    ?? '') ?: null;
        $schedule_id = sanitize($input['schedule_id'] ?? '') ?: null;
        $emp_id      = sanitize($input['emp_id']      ?? '') ?: null;
        $status      = sanitize($input['status']      ?? 'ACTIVE');

        if (!$train_name) jsonResponse(['error' => 'Train name required.'], 400);

        do {
            $id = 'TR' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $exists = db()->fetchOne("SELECT 1 FROM Train WHERE train_id = :id", [':id' => $id]);
        } while ($exists);

        $ok = db()->execute(
            "INSERT INTO Train (train_id, train_name, type, route_id, schedule_id, emp_id, status)
             VALUES (:id, :tn, :tp, :rid, :sid, :eid, :st)",
            [':id'=>$id, ':tn'=>$train_name, ':tp'=>$type, ':rid'=>$route_id, ':sid'=>$schedule_id, ':eid'=>$emp_id, ':st'=>$status]
        );
        jsonResponse($ok ? ['message' => "Train $id created!", 'train_id' => $id] : ['error' => 'Create failed.'], $ok ? 200 : 500);

    case 'PUT':
        $train_id    = sanitize($input['train_id']    ?? '');
        $train_name  = sanitize($input['train_name']  ?? '');
        $type        = sanitize($input['type']        ?? '');
        $route_id    = sanitize($input['route_id']    ?? '') ?: null;
        $schedule_id = sanitize($input['schedule_id'] ?? '') ?: null;
        $emp_id      = sanitize($input['emp_id']      ?? '') ?: null;
        $status      = sanitize($input['status']      ?? 'ACTIVE');

        if (!$train_id) jsonResponse(['error' => 'Train ID required.'], 400);

        $ok = db()->execute(
            "UPDATE Train SET train_name=:tn, type=:tp, route_id=:rid, schedule_id=:sid, emp_id=:eid, status=:st WHERE train_id=:id",
            [':tn'=>$train_name, ':tp'=>$type, ':rid'=>$route_id, ':sid'=>$schedule_id, ':eid'=>$emp_id, ':st'=>$status, ':id'=>$train_id]
        );
        jsonResponse($ok ? ['message' => "Train $train_id updated."] : ['error' => 'Update failed.'], $ok ? 200 : 500);

    case 'DELETE':
        $train_id = sanitize($input['train_id'] ?? '');
        if (!$train_id) jsonResponse(['error' => 'Train ID required.'], 400);

        db()->execute("DELETE FROM Booking WHERE coach_id IN (SELECT coach_id FROM Coach WHERE train_id = :id)", [':id' => $train_id]);
        db()->execute("DELETE FROM Coach WHERE train_id = :id", [':id' => $train_id]);
        db()->execute("DELETE FROM Maintenance WHERE train_id = :id", [':id' => $train_id]);
        $ok = db()->execute("DELETE FROM Train WHERE train_id = :id", [':id' => $train_id]);
        jsonResponse($ok ? ['message' => "Train $train_id deleted."] : ['error' => 'Delete failed.'], $ok ? 200 : 500);

    case 'GET':
        jsonResponse(db()->fetchAll("SELECT t.*, r.origin, r.destination FROM Train t LEFT JOIN Route r ON t.route_id = r.route_id ORDER BY t.train_id"));

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
