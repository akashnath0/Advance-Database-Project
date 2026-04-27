<?php
// ============================================================
// API: Bookings — CRUD (MySQL PDO Version)
// ============================================================
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (empty($_SESSION['user_id'])) jsonResponse(['error' => 'Unauthorized'], 401);

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($method) {
    case 'POST':
        $passenger_id = sanitize($input['passenger_id'] ?? '');
        $train_id     = sanitize($input['train_id']     ?? '');
        $coach_id     = sanitize($input['coach_id']     ?? '');
        $seat_no      = intval($input['seat_no']        ?? 0);
        $travel_date  = sanitize($input['travel_date']  ?? '');
        $amount       = floatval($input['amount']       ?? 0);
        $method_pay   = sanitize($input['method']       ?? 'Cash');

        if (!$passenger_id || !$train_id || !$coach_id || !$seat_no || !$travel_date || !$amount)
            jsonResponse(['error' => 'All fields are required.'], 400);

        if (!isStaff() && $passenger_id !== ($_SESSION['passenger_id'] ?? 'NONE'))
            jsonResponse(['error' => 'Forbidden: You can only book tickets for yourself.'], 403);

        // Check seat availability
        $seatTaken = db()->fetchOne(
            "SELECT 1 FROM Booking WHERE coach_id = :cid AND seat_no = :sno AND travel_date = :td",
            [':cid' => $coach_id, ':sno' => $seat_no, ':td' => $travel_date]
        );
        if ($seatTaken) jsonResponse(['error' => "Seat #$seat_no is already booked on that date!"], 409);

        // Generate unique IDs
        do { $pay_id = 'PY' . strtoupper(substr(bin2hex(random_bytes(3)),0,6));
             $e = db()->fetchOne("SELECT 1 FROM Payment WHERE payment_id=:id",[':id'=>$pay_id]); } while ($e);
        do { $bk_id = 'BK' . strtoupper(substr(bin2hex(random_bytes(3)),0,6));
             $e = db()->fetchOne("SELECT 1 FROM Booking WHERE booking_id=:id",[':id'=>$bk_id]); } while ($e);

        // Insert Payment (triggers Payment_Audit)
        db()->execute(
            "INSERT INTO Payment (payment_id, amount, method, status, payment_date) VALUES (:id, :am, :m, 'PAID', NOW())",
            [':id' => $pay_id, ':am' => $amount, ':m' => $method_pay]
        );

        // Insert Booking (triggers Booking_Audit)
        $ok = db()->execute(
            "INSERT INTO Booking (booking_id, passenger_id, train_id, coach_id, seat_no, payment_id, booking_time, travel_date)
             VALUES (:bid, :pid, :tid, :cid, :sno, :pyid, NOW(), :td)",
            [':bid'=>$bk_id, ':pid'=>$passenger_id, ':tid'=>$train_id, ':cid'=>$coach_id, ':sno'=>$seat_no, ':pyid'=>$pay_id, ':td'=>$travel_date]
        );

        jsonResponse($ok
            ? ['message' => "Booking $bk_id confirmed! Payment $pay_id processed.", 'booking_id' => $bk_id, 'payment_id' => $pay_id]
            : ['error' => 'Booking failed.'], $ok ? 200 : 500);

    case 'PUT':
        $booking_id  = sanitize($input['booking_id']  ?? '');
        $payment_id  = sanitize($input['payment_id']  ?? '');
        $seat_no     = intval($input['seat_no']        ?? 0);
        $travel_date = sanitize($input['travel_date']  ?? '');
        $pay_status  = sanitize($input['pay_status']   ?? 'PAID');

        if (!$booking_id) jsonResponse(['error' => 'Booking ID required.'], 400);
        if (!isStaff()) jsonResponse(['error' => 'Forbidden: Viewers cannot edit bookings.'], 403);

        db()->execute("UPDATE Booking SET seat_no=:sno, travel_date=:td WHERE booking_id=:id",
            [':sno'=>$seat_no, ':td'=>$travel_date, ':id'=>$booking_id]);
        if ($payment_id)
            db()->execute("UPDATE Payment SET status=:st WHERE payment_id=:id", [':st'=>$pay_status, ':id'=>$payment_id]);

        jsonResponse(['message' => "Booking $booking_id updated. All changes logged."]);

    case 'DELETE':
        $booking_id = sanitize($input['booking_id'] ?? '');
        if (!$booking_id) jsonResponse(['error' => 'Booking ID required.'], 400);

        $bk = db()->fetchOne("SELECT passenger_id, payment_id FROM Booking WHERE booking_id = :id", [':id' => $booking_id]);
        if (!$bk) jsonResponse(['error' => 'Booking not found.'], 404);
        if (!isStaff() && $bk['passenger_id'] !== ($_SESSION['passenger_id'] ?? 'NONE'))
            jsonResponse(['error' => 'Forbidden: You can only cancel your own bookings.'], 403);

        db()->execute("DELETE FROM Booking WHERE booking_id = :id", [':id' => $booking_id]);
        if ($bk && $bk['payment_id'])
            db()->execute("UPDATE Payment SET status='REFUNDED' WHERE payment_id = :id", [':id' => $bk['payment_id']]);

        jsonResponse(['message' => "Booking $booking_id cancelled and refunded."]);

    case 'GET':
        $sql = "
            SELECT b.*, CONCAT(p.first_name,' ',p.last_name) AS passenger_name,
                   t.train_name, py.amount, py.status AS pay_status
            FROM Booking b
            JOIN Passenger p ON b.passenger_id = p.passenger_id
            JOIN Train     t ON b.train_id     = t.train_id
            JOIN Payment  py ON b.payment_id   = py.payment_id
        ";
        $params = [];
        if (!isStaff()) {
            $sql .= " WHERE b.passenger_id = :pid ";
            $params[':pid'] = $_SESSION['passenger_id'] ?? 'NONE';
        }
        $sql .= " ORDER BY b.booking_time DESC";
        jsonResponse(db()->fetchAll($sql, $params));

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
