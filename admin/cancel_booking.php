<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Check admin session
checkAdminSession();

// Check if booking_id is provided
if (!isset($_POST['booking_id'])) {
    $_SESSION['error'] = 'Booking ID is required';
    header('Location: dashboard.php');
    exit;
}

$booking_id = $_POST['booking_id'];
$admin_cancelled = isset($_POST['admin_cancelled']) ? 1 : 0;

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get booking details first to verify it exists
    $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    if ($booking['status'] === 'cancelled') {
        throw new Exception('Booking is already cancelled');
    }

    if ($booking['status'] === 'completed') {
        throw new Exception('Cannot cancel a completed booking');
    }

    // Update booking status to cancelled
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'cancelled',
            cancelled_by = ?,
            cancelled_at = NOW(),
            admin_cancelled = ?
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['admin_id'], $admin_cancelled, $booking_id]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = 'Booking has been cancelled successfully';
    header('Location: view_booking.php?id=' . $booking_id);
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    $_SESSION['error'] = 'Error cancelling booking: ' . $e->getMessage();
    header('Location: view_booking.php?id=' . $booking_id);
    exit;
}
