<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Create customer if not exists
    $stmt = $pdo->prepare("
        INSERT INTO customers (first_name, last_name, email, phone)
        VALUES (:first_name, :last_name, :email, :phone)
        ON DUPLICATE KEY UPDATE
        id = LAST_INSERT_ID(id)
    ");

    $stmt->execute([
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'email' => $_POST['email'],
        'phone' => $_POST['phone']
    ]);

    $customer_id = $pdo->lastInsertId();

    // Create booking
    $check_in = $_POST['check_in_date'] . ' ' . $_POST['check_in_time'];
    $check_out = date('Y-m-d H:i:s', strtotime($check_in . ' + ' . $_POST['duration'] . ' hours'));

    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            customer_id, room_id, check_in, check_out,
            total_amount, status, booking_type, payment_method,
            special_requests, created_by
        )
        VALUES (
            :customer_id, :room_id, :check_in, :check_out,
            :total_amount, 'confirmed', 'walk_in', :payment_method,
            :special_requests, :created_by
        )
    ");

    $stmt->execute([
        'customer_id' => $customer_id,
        'room_id' => $_POST['room_id'],
        'check_in' => $check_in,
        'check_out' => $check_out,
        'total_amount' => $_POST['total_amount'],
        'payment_method' => $_POST['payment_method'],
        'special_requests' => $_POST['special_requests'] ?? null,
        'created_by' => $_SESSION['admin_id']
    ]);

    $booking_id = $pdo->lastInsertId();

    // Commit transaction
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Walk-in booking completed successfully',
        'booking_id' => $booking_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing walk-in booking: ' . $e->getMessage()
    ]);
}