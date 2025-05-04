<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$bookingId = $_POST['booking_id'] ?? '';

try {
    switch ($action) {
        case 'confirm':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', confirmed_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $bookingId]);
            echo json_encode(['success' => true, 'message' => 'Booking confirmed successfully']);
            break;

        case 'cancel':
            $reason = $_POST['reason'] ?? '';
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_by = ?, cancellation_reason = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $reason, $bookingId]);
            echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
            break;

        case 'check_in':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_in', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$bookingId]);
            echo json_encode(['success' => true, 'message' => 'Guest checked in successfully']);
            break;

        case 'check_out':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'checked_out', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$bookingId]);
            echo json_encode(['success' => true, 'message' => 'Guest checked out successfully']);
            break;

        case 'archive':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'archived', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$bookingId]);
            echo json_encode(['success' => true, 'message' => 'Booking archived successfully']);
            break;

        case 'get_booking':
            $bookingId = $_GET['id'] ?? 0;
            
            // Get booking details
            $stmt = $pdo->prepare("
                SELECT b.*, c.first_name, c.last_name, c.email, c.phone
                FROM bookings b
                LEFT JOIN customers c ON b.customer_id = c.id
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            // Get available rooms
            $stmt = $pdo->prepare("
                SELECT r.*, rt.name as room_type
                FROM rooms r
                LEFT JOIN room_types rt ON r.room_type_id = rt.id
                WHERE r.id = ? OR r.id NOT IN (
                    SELECT room_id FROM bookings 
                    WHERE status IN ('confirmed', 'checked_in')
                    AND check_out > NOW()
                )
                ORDER BY r.room_number
            ");
            $stmt->execute([$booking['room_id']]);
            $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'booking' => $booking,
                'rooms' => $rooms
            ]);
            break;
            
        case 'edit_booking':
            // Validate and update booking
            $bookingId = $_POST['booking_id'];
            $firstName = $_POST['first_name'];
            $lastName = $_POST['last_name'];
            $email = $_POST['email'];
            $phone = $_POST['phone'];
            $checkIn = $_POST['check_in'];
            $checkOut = $_POST['check_out'];
            $roomId = $_POST['room_id'];
            $status = $_POST['status'];
            $totalAmount = $_POST['total_amount'];
            $notes = $_POST['notes'];
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Update customer information
            $stmt = $pdo->prepare("
                UPDATE customers 
                SET first_name = ?, last_name = ?, email = ?, phone = ?
                WHERE id = (SELECT customer_id FROM bookings WHERE id = ?)
            ");
            $stmt->execute([$firstName, $lastName, $email, $phone, $bookingId]);
            
            // Update booking information
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET check_in = ?, check_out = ?, room_id = ?, 
                    status = ?, total_amount = ?, notes = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $checkIn, $checkOut, $roomId, 
                $status, $totalAmount, $notes, 
                $bookingId
            ]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking updated successfully'
            ]);
            break;
            
        case 'delete':
            try {
                // Check if booking exists
                $check_stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
                $check_stmt->execute([$booking_id]);
                $booking = $check_stmt->fetch();
        
                if (!$booking) {
                    echo json_encode(['success' => false, 'message' => 'Booking not found']);
                    exit;
                }
        
                // Delete the booking
                $delete_stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                $delete_stmt->execute([$booking_id]);
        
                // Return success response
                echo json_encode([
                    'success' => true,
                    'message' => 'Booking has been permanently deleted'
                ]);
            } catch (PDOException $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting booking: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>