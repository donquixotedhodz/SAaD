<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin session
checkAdminSession();

// Debug - Log request data
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

// Check if booking_id is provided
if (!isset($_POST['booking_id'])) {
    $_SESSION['error'] = 'Booking ID is required';
    header('Location: dashboard.php');
    exit;
}

$booking_id = $_POST['booking_id'];

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get booking details first to verify it exists and is pending
    $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    // Debug - Log booking data
    error_log("Booking data: " . print_r($booking, true));

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    if ($booking['status'] !== 'pending') {
        throw new Exception('Only pending bookings can be approved');
    }

    // Debug - Log the update attempt
    error_log("Attempting to update booking {$booking_id} to confirmed status");

    // Update booking status to confirmed
    $updateQuery = "
        UPDATE bookings 
        SET status = 'confirmed',
            confirmed_by = ?,
            confirmed_at = NOW()
        WHERE id = ?
    ";

    // Debug - Log the query and parameters
    error_log("Update query: " . $updateQuery);
    error_log("Parameters: admin_id = " . $_SESSION['admin_id'] . ", booking_id = " . $booking_id);

    $stmt = $pdo->prepare($updateQuery);
    $result = $stmt->execute([$_SESSION['admin_id'], $booking_id]);

    // Debug - Log the result and any potential errors
    error_log("Update result: " . ($result ? "success" : "failed"));
    if (!$result) {
        error_log("PDO error info: " . print_r($stmt->errorInfo(), true));
    }

    // Verify the update
    $stmt = $pdo->prepare("SELECT status, confirmed_by, confirmed_at FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $updatedBooking = $stmt->fetch();
    error_log("Updated booking data: " . print_r($updatedBooking, true));

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = 'Booking has been approved successfully';
    header('Location: view_booking.php?id=' . $booking_id);
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    
    // Debug - Log the error
    error_log("Error in approve_booking.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    $_SESSION['error'] = 'Error approving booking: ' . $e->getMessage();
    header('Location: view_booking.php?id=' . $booking_id);
    exit;
}
