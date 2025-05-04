<?php
header('Content-Type: application/json');

try {
    require_once '../config/database.php';

    if (!$pdo || $pdo === null) {
        throw new Exception('Database connection failed');
    }

    $stmt = $pdo->prepare("SELECT id, name, description, price, duration_hours, capacity FROM room_types");
    $stmt->execute();
    $room_types = $stmt->fetchAll();

    // Convert numeric strings to their proper types
    foreach ($room_types as &$room) {
        $room['id'] = (int)$room['id'];
        $room['price'] = (float)$room['price'];
        $room['duration_hours'] = (int)$room['duration_hours'];
        $room['capacity'] = (int)$room['capacity'];
    }

    echo json_encode($room_types);
} catch (Exception $e) {
    error_log('Room types error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load room types', 'details' => $e->getMessage()]);
}
