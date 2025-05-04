<?php
session_start();
require_once '../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: profile.php");
    exit();
}

try {
    $booking_id = $_POST['booking_id'];
    $room_type = $_POST['room_type'];
    $check_in_date = $_POST['check_in_date'];
    $check_in_time = $_POST['check_in_time'];
    $check_out_date = $_POST['check_out_date'];
    $check_out_time = $_POST['check_out_time'];
    
    // Validate check-in and check-out times
    $check_in = strtotime($check_in_date . ' ' . $check_in_time);
    $check_out = strtotime($check_out_date . ' ' . $check_out_time);

    if ($check_in < time()) {
        throw new Exception('Check-in time cannot be in the past');
    }

    // Get room duration and price
    $stmt = $pdo->prepare("SELECT duration_hours, price FROM room_types WHERE id = ?");
    $stmt->execute([$room_type]);
    $room_type_data = $stmt->fetch();
    
    if (!$room_type_data) {
        throw new Exception('Invalid room type selected');
    }
    
    $duration_hours = $room_type_data['duration_hours'];
    $room_price = $room_type_data['price'];

    // Calculate expected check-out time
    $expected_check_out = strtotime("+{$duration_hours} hours", $check_in);

    // Allow 5 minutes tolerance for time differences
    if (abs($check_out - $expected_check_out) > 300) {
        throw new Exception('Check-out time must be exactly ' . $duration_hours . ' hours after check-in time');
    }

    // Verify booking ownership
    $stmt = $pdo->prepare("
        SELECT b.*, r.room_type_id 
        FROM bookings b 
        JOIN rooms r ON b.room_id = r.id 
        WHERE b.id = ? AND b.customer_id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$booking_id, $_SESSION['customer_id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Invalid booking or not authorized to edit');
    }

    // Check if selected time slot is available
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        WHERE r.room_type_id = ?
        AND b.id != ?
        AND b.status != 'cancelled'
        AND (
            (b.check_in BETWEEN ? AND ?) OR
            (b.check_out BETWEEN ? AND ?) OR
            (b.check_in <= ? AND b.check_out >= ?)
        )
    ");
    
    $check_in_dt = date('Y-m-d H:i:s', $check_in);
    $check_out_dt = date('Y-m-d H:i:s', $check_out);
    
    $stmt->execute([
        $room_type,
        $booking_id,
        $check_in_dt,
        $check_out_dt,
        $check_in_dt,
        $check_out_dt,
        $check_in_dt,
        $check_out_dt
    ]);
    
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Selected time slot is not available');
    }

    // Get an available room
    $stmt = $pdo->prepare("
        SELECT r.id
        FROM rooms r
        LEFT JOIN bookings b ON r.id = b.room_id
        AND b.id != ?
        AND b.status != 'cancelled'
        AND (
            (b.check_in BETWEEN ? AND ?) OR
            (b.check_out BETWEEN ? AND ?) OR
            (b.check_in <= ? AND b.check_out >= ?)
        )
        WHERE r.room_type_id = ?
        AND r.status = 'available'
        AND b.id IS NULL
        LIMIT 1
    ");

    $stmt->execute([
        $booking_id,
        $check_in_dt,
        $check_out_dt,
        $check_in_dt,
        $check_out_dt,
        $check_in_dt,
        $check_out_dt,
        $room_type
    ]);
    
    $room = $stmt->fetch();
    
    if (!$room) {
        throw new Exception('No rooms available for the selected time slot');
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Update booking
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET room_id = ?, check_in = ?, check_out = ?, total_amount = ?
        WHERE id = ? AND customer_id = ?
    ");
    
    $stmt->execute([
        $room['id'],
        $check_in_dt,
        $check_out_dt,
        $room_price,
        $booking_id,
        $_SESSION['customer_id']
    ]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = "Booking updated successfully!";
    header("Location: profile.php");
    exit();

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = $e->getMessage();
    header("Location: edit_booking.php?id=" . $booking_id);
    exit();
}
