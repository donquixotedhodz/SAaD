<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_POST['room_id'], $_POST['check_in_date'], $_POST['check_in_time'])) {
        throw new Exception('Missing required fields');
    }

    $room_id = $_POST['room_id'];
    $check_in = date('Y-m-d H:i:s', strtotime($_POST['check_in_date'] . ' ' . $_POST['check_in_time']));
    $check_out = date('Y-m-d H:i:s', strtotime($_POST['check_out_date'] . ' ' . $_POST['check_out_time']));

    // Check if room exists and is available
    $stmt = $pdo->prepare("
        SELECT r.status, COUNT(b.id) as booking_count
        FROM rooms r
        LEFT JOIN bookings b ON r.id = b.room_id
        AND b.status != 'cancelled'
        AND (
            (b.check_in BETWEEN ? AND ?) OR
            (b.check_out BETWEEN ? AND ?) OR
            (b.check_in <= ? AND b.check_out >= ?)
        )
        WHERE r.id = ?
        GROUP BY r.id, r.status
    ");

    $stmt->execute([$check_in, $check_out, $check_in, $check_out, $check_in, $check_out, $room_id]);
    $result = $stmt->fetch();

    if (!$result) {
        throw new Exception('Room not found');
    }

    if ($result['status'] !== 'available') {
        throw new Exception('Room is currently ' . $result['status']);
    }

    if ($result['booking_count'] > 0) {
        throw new Exception('Room is already booked for the selected time period');
    }

    echo json_encode([
        'available' => true,
        'message' => 'Room is available'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'available' => false,
        'message' => $e->getMessage()
    ]);
}