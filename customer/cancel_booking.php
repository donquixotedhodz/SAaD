<?php
session_start();
require_once '../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header("Location: login.php");
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$booking_id = $_GET['id'];
$customer = $_SESSION['customer'];

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if booking exists and belongs to customer
    $stmt = $pdo->prepare("
        SELECT status 
        FROM bookings 
        WHERE id = ? AND customer_id = ? AND status = 'pending'
    ");
    $stmt->execute([$booking_id, $customer['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking not found or cannot be cancelled');
    }

    // Update booking status
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'cancelled' 
        WHERE id = ? AND customer_id = ?
    ");
    $stmt->execute([$booking_id, $customer['id']]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = "Booking cancelled successfully";
    header("Location: profile.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = $e->getMessage();
    header("Location: profile.php");
    exit();
}
