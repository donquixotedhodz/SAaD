<?php
// Add this new file: export_analytics.php
// filepath: c:\wamp64\www\richardshotelMS\admin\export_analytics.php
require_once '../includes/session.php';
require_once '../config/database.php';

try {
    $period = isset($_GET['period']) ? (int)$_GET['period'] : 180;
    
    // Get analytics data
    $stmt = $pdo->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as bookings,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN additional_services IS NOT NULL THEN JSON_EXTRACT(additional_services, '$.total') ELSE 0 END) as additional_revenue
        FROM bookings 
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$period]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare CSV content
    $csv = ["Date,Bookings,Room Revenue,Additional Revenue,Total Revenue\n"];
    foreach ($results as $row) {
        $roomRevenue = $row['total_revenue'] - $row['additional_revenue'];
        $csv[] = sprintf(
            "%s,%d,%.2f,%.2f,%.2f\n",
            $row['date'],
            $row['bookings'],
            $roomRevenue,
            $row['additional_revenue'],
            $row['total_revenue']
        );
    }

    // Output CSV file
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="analytics_' . date('Y-m-d') . '.csv"');
    foreach ($csv as $line) {
        echo $line;
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}