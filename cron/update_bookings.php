<?php
require_once '../config/database.php';

try {
    $pdo->beginTransaction();

    // Update completed bookings to checked-out
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'checked_out' 
        WHERE status IN ('confirmed', 'checked_in')
        AND check_out < NOW()
    ");
    $stmt->execute();

    // Mark expired pending bookings as cancelled
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET status = 'cancelled' 
        WHERE status = 'pending' 
        AND check_in < NOW()
    ");
    $stmt->execute();

    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error updating bookings: " . $e->getMessage());
}
