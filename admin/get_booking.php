<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Check admin session
checkAdminSession();

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit;
}

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            r.room_number,
            rt.name as room_type
        FROM bookings b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN rooms r ON b.room_id = r.id
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        WHERE b.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }

    // Get available rooms
    $stmt = $pdo->query("
        SELECT 
            r.id,
            r.room_number,
            rt.name as room_type
        FROM rooms r
        LEFT JOIN room_types rt ON r.room_type_id = rt.id
        ORDER BY r.room_number
    ");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'booking' => $booking,
        'rooms' => $rooms
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>