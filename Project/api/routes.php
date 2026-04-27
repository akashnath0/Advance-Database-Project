<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);
if (isViewer() && $_SERVER['REQUEST_METHOD'] !== 'GET') jsonResponse(['error' => 'Forbidden: Viewer cannot modify data'], 403);
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$type   = sanitize($_GET['type'] ?? 'route');

if ($type === 'schedule') {
    switch ($method) {
        case 'POST':
            $dd=$input['departure_date']??''; $dt=$input['departure_time']??''; $at=$input['arrival_time']??'';
            if (!$dd) jsonResponse(['error'=>'Departure date required.'],400);
            do { $id='SC'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
                 $e=db()->fetchOne("SELECT 1 FROM Schedule WHERE schedule_id=:id",[':id'=>$id]); } while($e);
            $ok=db()->execute("INSERT INTO Schedule (schedule_id,departure_date,departure_time,arrival_time) VALUES (:id,:dd,:dt,:at)",
                [':id'=>$id,':dd'=>$dd,':dt'=>$dt,':at'=>$at]);
            jsonResponse($ok?['message'=>"Schedule $id created!",'schedule_id'=>$id]:['error'=>'Create failed.'],$ok?200:500);
        case 'PUT':
            $id=$input['schedule_id']??''; $dd=$input['departure_date']??''; $dt=$input['departure_time']??''; $at=$input['arrival_time']??'';
            if (!$id) jsonResponse(['error'=>'Schedule ID required.'],400);
            $ok=db()->execute("UPDATE Schedule SET departure_date=:dd,departure_time=:dt,arrival_time=:at WHERE schedule_id=:id",
                [':dd'=>$dd,':dt'=>$dt,':at'=>$at,':id'=>$id]);
            jsonResponse($ok?['message'=>"Schedule $id updated."]:['error'=>'Update failed.'],$ok?200:500);
        case 'DELETE':
            $id=$input['schedule_id']??'';
            if (!$id) jsonResponse(['error'=>'Schedule ID required.'],400);
            db()->execute("UPDATE Train SET schedule_id=NULL WHERE schedule_id=:id",[':id'=>$id]);
            $ok=db()->execute("DELETE FROM Schedule WHERE schedule_id=:id",[':id'=>$id]);
            jsonResponse($ok?['message'=>"Schedule $id deleted."]:['error'=>'Delete failed.'],$ok?200:500);
        case 'GET':
            jsonResponse(db()->fetchAll("SELECT * FROM Schedule ORDER BY departure_date"));
        default: jsonResponse(['error'=>'Method not allowed'],405);
    }
} else {
    switch ($method) {
        case 'POST':
            $o=$input['origin']??''; $d=$input['destination']??'';
            $di=floatval($input['distance_km']??0); $dur=$input['estimated_duration']??'';
            if (!$o||!$d||!$di) jsonResponse(['error'=>'Origin, destination and distance required.'],400);
            do { $id='RT'.strtoupper(substr(bin2hex(random_bytes(3)),0,6));
                 $e=db()->fetchOne("SELECT 1 FROM Route WHERE route_id=:id",[':id'=>$id]); } while($e);
            $ok=db()->execute("INSERT INTO Route (route_id,origin,destination,distance_km,estimated_duration) VALUES (:id,:o,:d,:di,:dur)",
                [':id'=>$id,':o'=>$o,':d'=>$d,':di'=>$di,':dur'=>$dur]);
            jsonResponse($ok?['message'=>"Route $id created!",'route_id'=>$id]:['error'=>'Create failed.'],$ok?200:500);
        case 'PUT':
            $id=$input['route_id']??''; $o=$input['origin']??''; $d=$input['destination']??'';
            $di=floatval($input['distance_km']??0); $dur=$input['estimated_duration']??'';
            if (!$id) jsonResponse(['error'=>'Route ID required.'],400);
            $ok=db()->execute("UPDATE Route SET origin=:o,destination=:d,distance_km=:di,estimated_duration=:dur WHERE route_id=:id",
                [':o'=>$o,':d'=>$d,':di'=>$di,':dur'=>$dur,':id'=>$id]);
            jsonResponse($ok?['message'=>"Route $id updated."]:['error'=>'Update failed.'],$ok?200:500);
        case 'DELETE':
            $id=$input['route_id']??'';
            if (!$id) jsonResponse(['error'=>'Route ID required.'],400);
            db()->execute("UPDATE Train SET route_id=NULL WHERE route_id=:id",[':id'=>$id]);
            $ok=db()->execute("DELETE FROM Route WHERE route_id=:id",[':id'=>$id]);
            jsonResponse($ok?['message'=>"Route $id deleted."]:['error'=>'Delete failed.'],$ok?200:500);
        case 'GET':
            jsonResponse(db()->fetchAll("SELECT * FROM Route ORDER BY route_id"));
        default: jsonResponse(['error'=>'Method not allowed'],405);
    }
}
