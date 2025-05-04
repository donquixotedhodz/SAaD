<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $room_type = $_GET['room_type'] ?? '';
    
    // Map room type to room_type_id
    $room_type_id = '';
    switch($room_type) {
        case '3hours':
        case '12hours':
        case '24hours':
            $room_type_id = 1; // Standard room ID
            break;
        case 'family':
            $room_type_id = 2; // Family room ID
            break;
    }
    
    if (!$room_type_id) {
        echo json_encode([]);
        exit;
    }
    
    // Get available rooms
    $stmt = $pdo->prepare("
        SELECT r.id, r.room_number
        FROM rooms r
        WHERE r.room_type_id = :room_type_id
        AND r.id NOT IN (
            SELECT b.room_id 
            FROM bookings b 
            WHERE b.status IN ('confirmed', 'checked_in')
            AND CURRENT_TIMESTAMP BETWEEN b.check_in AND b.check_out
        )
        ORDER BY r.room_number
    ");
    
    $stmt->execute(['room_type_id' => $room_type_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($rooms);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}