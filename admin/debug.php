<?php
require_once '../config/database.php';

try {
    // Check if the bookings table exists
    $tables = $pdo->query("SHOW TABLES LIKE 'bookings'")->fetchAll();
    echo "Tables found: " . print_r($tables, true) . "\n";

    // Get all bookings
    $stmt = $pdo->query("SELECT * FROM bookings");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Number of bookings found: " . count($bookings) . "\n";
    
    if (!empty($bookings)) {
        foreach ($bookings as $booking) {
            echo "Booking ID: {$booking['id']}, Status: {$booking['status']}\n";
        }
    } else {
        echo "No bookings found in the database\n";
    }
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE bookings");
    echo "\nTable structure:\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
