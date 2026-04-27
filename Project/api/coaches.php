<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
if (isViewer() && $_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Forbidden: Viewer cannot modify data'], 403);
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'POST':
        $tid=$input['train_id']??''; $ct=$input['coach_type']??''; $cap=intval($input['capacity']??0);
        if (!$tid||!$ct||!$cap) jsonResponse(['error'=>'All coach fields required.'],400);
        do { $id='CO'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
             $e=db()->fetchOne("SELECT 1 FROM Coach WHERE coach_id=:id",[':id'=>$id]); } while($e);
        $ok=db()->execute("INSERT INTO Coach (coach_id,train_id,coach_type,capacity) VALUES (:id,:tid,:ct,:cap)",
            [':id'=>$id,':tid'=>$tid,':ct'=>$ct,':cap'=>$cap]);
        jsonResponse($ok?['message'=>"Coach $id added!",'coach_id'=>$id]:['error'=>'Create failed.'],$ok?200:500);

    case 'PUT':
        $id=$input['coach_id']??''; $tid=$input['train_id']??''; $ct=$input['coach_type']??''; $cap=intval($input['capacity']??0);
        if (!$id) jsonResponse(['error'=>'Coach ID required.'],400);
        $ok=db()->execute("UPDATE Coach SET train_id=:tid,coach_type=:ct,capacity=:cap WHERE coach_id=:id",
            [':tid'=>$tid,':ct'=>$ct,':cap'=>$cap,':id'=>$id]);
        jsonResponse($ok?['message'=>"Coach $id updated."]:['error'=>'Update failed.'],$ok?200:500);

    case 'DELETE':
        $id=$input['coach_id']??'';
        if (!$id) jsonResponse(['error'=>'Coach ID required.'],400);
        db()->execute("DELETE FROM Booking WHERE coach_id=:id",[':id'=>$id]);
        $ok=db()->execute("DELETE FROM Coach WHERE coach_id=:id",[':id'=>$id]);
        jsonResponse($ok?['message'=>"Coach $id deleted."]:['error'=>'Delete failed.'],$ok?200:500);

    case 'GET':
        $tid=sanitize($_GET['train_id']??'');
        if ($tid) jsonResponse(db()->fetchAll("SELECT * FROM Coach WHERE train_id=:id ORDER BY coach_id",[':id'=>$tid]));
        jsonResponse(db()->fetchAll("SELECT c.*,t.train_name FROM Coach c LEFT JOIN Train t ON c.train_id=t.train_id ORDER BY c.coach_id"));

    default: jsonResponse(['error'=>'Method not allowed'],405);
}
