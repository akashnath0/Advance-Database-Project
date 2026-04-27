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
        $d=$input['designation']??''; $s=floatval($input['salary']??0);
        $cn=$input['contact_number']??''; $ad=$input['address']??'';
        $hd=$input['hire_date']??'';
        if (!$fn||!$d||!$s||!$cn||!$hd) jsonResponse(['error'=>'Required fields missing.'],400);
        do { $id='EMP'.strtoupper(substr(bin2hex(random_bytes(3)),0,5));
             $e=db()->fetchOne("SELECT 1 FROM Employee WHERE emp_id=:id",[':id'=>$id]); } while ($e);
        $ok=db()->execute("INSERT INTO Employee (emp_id,first_name,last_name,designation,salary,contact_number,address,hire_date) VALUES (:id,:fn,:ln,:d,:s,:cn,:ad,:hd)",
            [':id'=>$id,':fn'=>$fn,':ln'=>$ln,':d'=>$d,':s'=>$s,':cn'=>$cn,':ad'=>$ad,':hd'=>$hd]);
        jsonResponse($ok?['message'=>"Employee $id created!",'emp_id'=>$id]:['error'=>'Create failed.'],$ok?200:500);

    case 'PUT':
        $id=$input['emp_id']??''; $fn=$input['first_name']??''; $ln=$input['last_name']??'';
        $d=$input['designation']??''; $s=floatval($input['salary']??0);
        $cn=$input['contact_number']??''; $ad=$input['address']??'';
        if (!$id) jsonResponse(['error'=>'Employee ID required.'],400);
        $ok=db()->execute("UPDATE Employee SET first_name=:fn,last_name=:ln,designation=:d,salary=:s,contact_number=:cn,address=:ad WHERE emp_id=:id",
            [':fn'=>$fn,':ln'=>$ln,':d'=>$d,':s'=>$s,':cn'=>$cn,':ad'=>$ad,':id'=>$id]);
        jsonResponse($ok?['message'=>"Employee $id updated."]:['error'=>'Update failed.'],$ok?200:500);

    case 'DELETE':
        $id=$input['emp_id']??'';
        if (!$id) jsonResponse(['error'=>'Employee ID required.'],400);
        db()->execute("UPDATE Train SET emp_id=NULL WHERE emp_id=:id",[':id'=>$id]);
        db()->execute("DELETE FROM Driver WHERE emp_id=:id",[':id'=>$id]);
        db()->execute("DELETE FROM Platform_Staff WHERE emp_id=:id",[':id'=>$id]);
        $ok=db()->execute("DELETE FROM Employee WHERE emp_id=:id",[':id'=>$id]);
        jsonResponse($ok?['message'=>"Employee $id deleted."]:['error'=>'Delete failed.'],$ok?200:500);

    case 'GET':
        jsonResponse(db()->fetchAll("SELECT * FROM Employee ORDER BY emp_id"));

    default: jsonResponse(['error'=>'Method not allowed'],405);
}
