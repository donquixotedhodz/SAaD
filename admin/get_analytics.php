<?php
// Add this new file: get_analytics.php
// filepath: c:\wamp64\www\richardshotelMS\admin\get_analytics.php
require_once '../includes/session.php';
require_once '../config/database.php';

try {
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 180;
    
    // Get dates and revenue data
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as booking_count,
            SUM(total_amount) as daily_revenue,
            SUM(CASE WHEN additional_services IS NOT NULL THEN JSON_EXTRACT(additional_services, '$.total') ELSE 0 END) as additional_revenue
        FROM bookings 
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$days]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get current occupancy
    $occupancy_stmt = $pdo->query("
        SELECT 
            SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
            SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
            SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
        FROM rooms
    ");
    $occupancy = $occupancy_stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare response data
    $response = [
        'dates' => array_column($results, 'date'),
        'bookings' => array_column($results, 'booking_count'),
        'revenue' => array_column($results, 'daily_revenue'),
        'roomRevenue' => array_map(function($row) {
            return $row['daily_revenue'] - $row['additional_revenue'];
        }, $results),
        'additionalRevenue' => array_column($results, 'additional_revenue'),
        'occupancy' => $occupancy
    ];

    header('Content-Type: application/json');
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}