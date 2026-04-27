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
        $fn=$input['first_name']??''; $ln=$input['last_name']??'';
        $sp=$input['specialization']??''; $ex=intval($input['experience_years']??0); $con=$input['contact']??'';
        if (!$fn) jsonResponse(['error'=>'First name required.'],400);
        do { $id='TC'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
             $e=db()->fetchOne("SELECT 1 FROM Technician WHERE tech_id=:id",[':id'=>$id]); } while($e);
        $ok=db()->execute("INSERT INTO Technician (tech_id,first_name,last_name,specialization,experience_years,contact) VALUES (:id,:fn,:ln,:sp,:ex,:con)",
            [':id'=>$id,':fn'=>$fn,':ln'=>$ln,':sp'=>$sp,':ex'=>$ex,':con'=>$con]);
        jsonResponse($ok?['message'=>"Technician $id created!",'tech_id'=>$id]:['error'=>'Create failed.'],$ok?200:500);

    case 'PUT':
        $id=$input['tech_id']??''; $fn=$input['first_name']??''; $ln=$input['last_name']??'';
        $sp=$input['specialization']??''; $ex=intval($input['experience_years']??0); $con=$input['contact']??'';
        if (!$id) jsonResponse(['error'=>'Technician ID required.'],400);
        $ok=db()->execute("UPDATE Technician SET first_name=:fn,last_name=:ln,specialization=:sp,experience_years=:ex,contact=:con WHERE tech_id=:id",
            [':fn'=>$fn,':ln'=>$ln,':sp'=>$sp,':ex'=>$ex,':con'=>$con,':id'=>$id]);
        jsonResponse($ok?['message'=>"Technician $id updated."]:['error'=>'Update failed.'],$ok?200:500);

    case 'DELETE':
        $id=$input['tech_id']??'';
        if (!$id) jsonResponse(['error'=>'Technician ID required.'],400);
        db()->execute("DELETE FROM Maintenance WHERE tech_id=:id",[':id'=>$id]);
        $ok=db()->execute("DELETE FROM Technician WHERE tech_id=:id",[':id'=>$id]);
        jsonResponse($ok?['message'=>"Technician $id deleted."]:['error'=>'Delete failed.'],$ok?200:500);

    case 'GET':
        jsonResponse(db()->fetchAll("SELECT * FROM Technician ORDER BY tech_id"));

    default: jsonResponse(['error'=>'Method not allowed'],405);
}
