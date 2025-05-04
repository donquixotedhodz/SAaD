<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    if (!isset($_GET['room_type_id'])) {
        throw new Exception('Room type ID is required');
    }

    $room_type_id = (int)$_GET['room_type_id'];

    // Get an available room of the specified type
    $stmt = $pdo->prepare("
        SELECT r.id
        FROM rooms r
        LEFT JOIN bookings b ON r.id = b.room_id
        AND b.status != 'cancelled'
        AND (
            NOW() BETWEEN b.check_in AND b.check_out
            OR NOW() + INTERVAL 1 HOUR BETWEEN b.check_in AND b.check_out
        )
        WHERE r.room_type_id = ?
        AND r.status = 'available'
        AND b.id IS NULL
        LIMIT 1
    ");

    $stmt->execute([$room_type_id]);
    $room = $stmt->fetch();

    if (!$room) {
        throw new Exception('No rooms available for this type');
    }

    echo json_encode([
        'success' => true,
        'room_id' => $room['id']
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
