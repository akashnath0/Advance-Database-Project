<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
if (isViewer()) jsonResponse(['error' => 'Forbidden: Viewer access restricted'], 403);
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'POST':
        $tid=$input['train_id']??''; $tcid=$input['tech_id']??'';
        $mt=$input['maintenance_type']??'Scheduled'; $md=$input['maintenance_date']??''; $rem=$input['remarks']??'';
        if (!$tid||!$tcid||!$md) jsonResponse(['error'=>'Train, technician and date required.'],400);
        do { $id='MT'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
             $e=db()->fetchOne("SELECT 1 FROM Maintenance WHERE maintenance_id=:id",[':id'=>$id]); } while($e);
        $ok=db()->execute("INSERT INTO Maintenance (maintenance_id,train_id,tech_id,maintenance_type,maintenance_date,remarks) VALUES (:id,:tid,:tcid,:mt,:md,:rem)",
            [':id'=>$id,':tid'=>$tid,':tcid'=>$tcid,':mt'=>$mt,':md'=>$md,':rem'=>$rem]);
        if ($ok) db()->execute("UPDATE Train SET status='MAINTENANCE' WHERE train_id=:id",[':id'=>$tid]);
        jsonResponse($ok?['message'=>"Maintenance $id logged. Train set to MAINTENANCE.",'maintenance_id'=>$id]:['error'=>'Create failed.'],$ok?200:500);

    case 'PUT':
        $id=$input['maintenance_id']??''; $mt=$input['maintenance_type']??''; $md=$input['maintenance_date']??''; $rem=$input['remarks']??'';
        if (!$id) jsonResponse(['error'=>'Maintenance ID required.'],400);
        $ok=db()->execute("UPDATE Maintenance SET maintenance_type=:mt,maintenance_date=:md,remarks=:rem WHERE maintenance_id=:id",
            [':mt'=>$mt,':md'=>$md,':rem'=>$rem,':id'=>$id]);
        jsonResponse($ok?['message'=>"Maintenance $id updated."]:['error'=>'Update failed.'],$ok?200:500);

    case 'DELETE':
        $id=$input['maintenance_id']??'';
        if (!$id) jsonResponse(['error'=>'Maintenance ID required.'],400);
        $ok=db()->execute("DELETE FROM Maintenance WHERE maintenance_id=:id",[':id'=>$id]);
        jsonResponse($ok?['message'=>"Maintenance $id deleted."]:['error'=>'Delete failed.'],$ok?200:500);

    case 'GET':
        jsonResponse(db()->fetchAll("SELECT m.*,t.train_name,CONCAT(tc.first_name,' ',tc.last_name) AS tech_name FROM Maintenance m LEFT JOIN Train t ON m.train_id=t.train_id LEFT JOIN Technician tc ON m.tech_id=tc.tech_id ORDER BY m.maintenance_date DESC"));

    default: jsonResponse(['error'=>'Method not allowed'],405);
}
