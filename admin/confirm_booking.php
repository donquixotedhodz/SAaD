<?php
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get booking details
        $stmt = $pdo->prepare("
            SELECT b.*, r.room_number, c.first_name, c.last_name, c.email, c.phone
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            JOIN customers c ON b.customer_id = c.id
            WHERE b.id = ? AND b.status = 'pending'
        ");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if (!$booking) {
            throw new Exception('Booking not found or cannot be confirmed');
        }

        // Update booking status
        $stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'confirmed', 
                confirmed_at = NOW(),
                confirmed_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['admin_id'], $booking_id]);

        // Send confirmation email to customer
        $to = $booking['email'];
        $subject = "Booking Confirmation - Room " . $booking['room_number'];
        
        $message = "Dear " . $booking['first_name'] . " " . $booking['last_name'] . ",\n\n";
        $message .= "Your booking has been confirmed.\n\n";
        $message .= "Booking Details:\n";
        $message .= "Room Number: " . $booking['room_number'] . "\n";
        $message .= "Check-in: " . date('Y-m-d H:i', strtotime($booking['check_in'])) . "\n";
        $message .= "Check-out: " . date('Y-m-d H:i', strtotime($booking['check_out'])) . "\n";
        $message .= "Amount: ₱" . number_format($booking['amount'], 2) . "\n\n";
        $message .= "Thank you for choosing our hotel!\n\n";
        $message .= "Best regards,\nHotel Management";

        $headers = "From: hotel@example.com";

        mail($to, $subject, $message, $headers);

        // Commit transaction
        $pdo->commit();

        $_SESSION['success'] = "Booking #$booking_id has been confirmed successfully.";
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        $_SESSION['error'] = $e->getMessage();
    }
}

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
