<?php
require_once '../config/database.php';

try {
    // First, get a valid customer_id and room_id
    $stmt = $pdo->query("SELECT id FROM customers LIMIT 1");
    $customer = $stmt->fetch();
    $customer_id = $customer['id'];

    $stmt = $pdo->query("SELECT id FROM rooms LIMIT 1");
    $room = $stmt->fetch();
    $room_id = $room['id'];

    // Create a test booking with pending status
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            customer_id, 
            room_id, 
            check_in, 
            check_out, 
            status, 
            total_amount
        ) VALUES (
            ?, 
            ?, 
            DATE_ADD(NOW(), INTERVAL 1 DAY),
            DATE_ADD(NOW(), INTERVAL 3 DAY),
            'pending',
            500.00
        )
    ");
    
    $stmt->execute([$customer_id, $room_id]);
    echo "Test booking created successfully with ID: " . $pdo->lastInsertId();
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
