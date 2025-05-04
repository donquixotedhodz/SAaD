<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Validate input data
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'room_id', 'room_type', 
                       'check_in_date', 'check_in_time', 'check_out_date', 'check_out_time'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Create datetime objects for check-in and check-out
    $checkIn = date('Y-m-d H:i:s', strtotime($_POST['check_in_date'] . ' ' . $_POST['check_in_time']));
    $checkOut = date('Y-m-d H:i:s', strtotime($_POST['check_out_date'] . ' ' . $_POST['check_out_time']));

    // Check if room is available for the selected dates
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings 
        WHERE room_id = ? 
        AND status NOT IN ('cancelled', 'checked_out')
        AND (
            (check_in <= ? AND check_out >= ?) OR
            (check_in <= ? AND check_out >= ?) OR
            (check_in >= ? AND check_out <= ?)
        )
    ");
    $stmt->execute([
        $_POST['room_id'],
        $checkIn, $checkIn,
        $checkOut, $checkOut,
        $checkIn, $checkOut
    ]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Room is not available for the selected dates");
    }

    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND phone = ?");
    $stmt->execute([$_POST['email'], $_POST['phone']]);
    $existingCustomer = $stmt->fetch();
    
    if ($existingCustomer) {
        $customerId = $existingCustomer['id'];
        
        // Update customer information
        $stmt = $pdo->prepare("UPDATE customers SET first_name = ?, last_name = ? WHERE id = ?");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $customerId
        ]);
    } else {
        // Insert new customer
        $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone) 
                              VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone']
        ]);
        $customerId = $pdo->lastInsertId();
    }

    // Get room price
    $stmt = $pdo->prepare("SELECT price FROM room_types WHERE id = ?");
    $stmt->execute([$_POST['room_type']]);
    $roomPrice = $stmt->fetchColumn();

    // Calculate number of days
    $days = ceil((strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24));
    $totalAmount = $roomPrice * $days;

    // Insert booking with pending status
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            customer_id, 
            room_id, 
            check_in, 
            check_out, 
            total_amount, 
            status, 
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 'pending', NOW()
        )
    ");
    $stmt->execute([
        $customerId,
        $_POST['room_id'],
        $checkIn,
        $checkOut,
        $totalAmount
    ]);
    $bookingId = $pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'check_in' => $checkIn,
        'check_out' => $checkOut,
        'total_amount' => $totalAmount,
        'message' => 'Booking request submitted successfully! Please wait for admin approval.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}