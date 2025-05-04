<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Get current date
    $currentDate = date('Y-m-d');

    // Update bookings to archived status where checkout date has passed
    $stmt = $pdo->prepare("
        UPDATE bookings 
        SET 
            status = 'archived',
            updated_at = NOW()
        WHERE 
            status = 'checked_out' 
            AND check_out < :currentDate
    ");
    
    $stmt->execute(['currentDate' => $currentDate]);
    
    // Count number of records archived
    $archivedCount = $stmt->rowCount();
    
    // Log the archive operation
    $logStmt = $pdo->prepare("
        INSERT INTO system_logs (
            action_type,
            action_description,
            affected_records,
            performed_by,
            performed_at
        ) VALUES (
            'ARCHIVE_CHECKOUTS',
            'Automated archiving of checked-out bookings',
            :affected_records,
            'SYSTEM',
            NOW()
        )
    ");
    
    $logStmt->execute(['affected_records' => $archivedCount]);
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully archived $archivedCount checked-out bookings",
        'archived_count' => $archivedCount
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Error: " . $e->getMessage()
    ]);
}