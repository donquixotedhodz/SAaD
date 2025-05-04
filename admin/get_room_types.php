<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', 'room_types_error.log');

// Check admin session
checkAdminSession();

// Set proper headers
header('Content-Type: application/json');

try {
    // Verify database connection
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Debug: Log the query we're about to execute
    error_log("Executing room types query...");
    
    // Get room types with proper column names
    $stmt = $pdo->prepare("
        SELECT 
            id,
            room_type_name as name,
            room_rate as rate,
            room_description as description
        FROM room_types
        ORDER BY room_type_name ASC
    ");
    
    $stmt->execute();
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the result count
    error_log("Found " . count($room_types) . " room types");
    
    if (empty($room_types)) {
        throw new Exception('No room types found in the database');
    }
    
    // Return JSON response
    echo json_encode($room_types);
    
} catch (PDOException $e) {
    // Log the error
    error_log("Database error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Log the error
    error_log("General error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}