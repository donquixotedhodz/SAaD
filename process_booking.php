<?php
require_once '../includes/session.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check admin session
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $action = $_POST['action'] ?? '';
    $bookingId = $_POST['booking_id'] ?? '';

    if (!$action || !$bookingId) {
        throw new Exception('Missing required parameters');
    }

    $pdo->beginTransaction();

    switch ($action) {
        case 'confirm':
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'confirmed',
                    confirmed_by = ?,
                    updated_at = NOW() 
                WHERE id = ? 
                AND status = 'pending'
            ");
            $result = $stmt->execute([$_SESSION['admin_id'], $bookingId]);
            break;

        case 'cancel':
            $reason = $_POST['reason'] ?? '';
            if (!$reason) {
                throw new Exception('Cancellation reason is required');
            }

            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'cancelled',
                    cancelled_by = ?,
                    cancellation_reason = ?,
                    updated_at = NOW() 
                WHERE id = ? 
                AND status IN ('pending', 'confirmed')
            ");
            $result = $stmt->execute([$_SESSION['admin_id'], $reason, $bookingId]);
            break;

        default:
            throw new Exception('Invalid action');
    }

    if ($stmt->rowCount() === 0) {
        throw new Exception('No booking was updated. It may have already been processed.');
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => ucfirst($action) . ' completed successfully'
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}