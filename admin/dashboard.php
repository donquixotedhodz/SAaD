<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper functions
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending': return 'bg-warning text-dark';
        case 'confirmed': return 'bg-success text-white';
        case 'cancelled': return 'bg-danger text-white';
        case 'checked_in': return 'bg-info text-white';
        case 'checked_out': return 'bg-secondary text-white';
        case 'archived': return 'bg-secondary text-white';
        case 'awaiting_approval': return 'bg-primary text-white'; // Add this line
        default: return 'bg-secondary text-white';
    }
}

// Check admin session
checkAdminSession();

try {
    // Get today's check-ins count
    $today_stmt = $pdo->prepare("SELECT 
    COUNT(*) as today_checkins
FROM bookings 
WHERE DATE(check_in) = CURRENT_DATE()
AND status IN ('checked_in')");  // Change this line to only count 'checked_in' status
$today_stmt->execute();
$today_stats = $today_stmt->fetch(PDO::FETCH_ASSOC);

    // Get active bookings count (confirmed or checked in)
    $active_stmt = $pdo->prepare("SELECT 
        COUNT(*) as active_bookings
    FROM bookings 
    WHERE status IN ('confirmed', 'checked_in')
    AND check_out >= CURRENT_DATE()");
    $active_stmt->execute();
    $active_stats = $active_stmt->fetch(PDO::FETCH_ASSOC);

    // Get current month revenue
    $revenue_stmt = $pdo->prepare("SELECT 
        COALESCE(SUM(total_amount), 0) as current_month_revenue
    FROM bookings 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    AND status != 'cancelled'");
    $revenue_stmt->execute();
    $current_month_revenue = $revenue_stmt->fetch(PDO::FETCH_ASSOC);

    // Get room availability stats
    $room_stmt = $pdo->prepare("WITH RoomStatus AS (
        SELECT r.id,
               CASE WHEN EXISTS (
                   SELECT 1 FROM bookings b 
                   WHERE b.room_id = r.id 
                   AND b.status IN ('confirmed', 'checked_in')
                   AND CURRENT_DATE() BETWEEN DATE(b.check_in) AND DATE(b.check_out)
               ) THEN 'occupied' ELSE 'available' END as current_status
        FROM rooms r
    )
    SELECT 
        (SELECT COUNT(*) FROM rooms) as total_rooms,
        (SELECT COUNT(*) FROM RoomStatus WHERE current_status = 'available') as available_rooms,
        (SELECT COUNT(*) FROM RoomStatus WHERE current_status = 'occupied') as occupied_rooms
    FROM dual");
    $room_stmt->execute();
    $room_stats = $room_stmt->fetch(PDO::FETCH_ASSOC);

    // Add after the query for debugging
    echo "<!-- Debug Room Stats:
    Total: {$room_stats['total_rooms']}
    Available: {$room_stats['available_rooms']}
    Occupied: {$room_stats['occupied_rooms']}
    Sum Check: " . ($room_stats['available_rooms'] + $room_stats['occupied_rooms']) . "
    -->";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

try {
    // Auto-archive completed bookings
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'archived' WHERE status = 'checked_out' AND check_out < CURRENT_DATE()");
    $stmt->execute();

    // Update the archived bookings query - add this after the try statement
    try {
        // Auto-archive completed bookings that are checked out
        $archive_stmt = $pdo->prepare("
            UPDATE bookings 
            SET status = 'archived' 
            WHERE status = 'checked_out' 
            AND check_out < CURRENT_DATE()
        ");
        $archive_stmt->execute();

        // Get archived bookings with more detailed conditions
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                c.first_name,
                c.last_name,
                c.email,
                r.room_number,
                rt.name as room_type,
                DATEDIFF(b.check_out, b.check_in) as nights_stayed,
                b.total_amount as revenue
            FROM bookings b
            LEFT JOIN customers c ON b.customer_id = c.id
            LEFT JOIN rooms r ON b.room_id = r.id
            LEFT JOIN room_types rt ON r.room_type_id = rt.id
            WHERE b.status = 'archived' 
            OR (b.status = 'checked_out' AND b.check_out < CURRENT_DATE())
            ORDER BY b.check_out DESC
            LIMIT 10
        ");
        $stmt->execute();
        $archived_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }

    // // Get monthly revenue statistics
    // $stmt = $pdo->prepare("SELECT 
    //                         DATE_FORMAT(check_out, '%Y-%m') as month_year,
    //                         DATE_FORMAT(check_out, '%b %Y') as month_label,
    //                         COUNT(*) as total_bookings,
    //                         COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
    //                         SUM(CASE WHEN status IN ('checked_out', 'completed', 'archived') THEN total_amount ELSE 0 END) as total_revenue,
    //                         AVG(CASE WHEN status IN ('checked_out', 'completed', 'archived') THEN total_amount ELSE NULL END) as avg_booking_value
    //                     FROM bookings 
    //                     WHERE check_out >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    //                     GROUP BY DATE_FORMAT(check_out, '%Y-%m'), DATE_FORMAT(check_out, '%b %Y')
    //                     ORDER BY month_year ASC");
    // $stmt->execute();
    // $monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get archived bookings
    $stmt = $pdo->prepare("SELECT 
                            b.*,
                            c.first_name,
                            c.last_name,
                            c.email,
                            r.room_number,
                            rt.name as room_type,
                            DATEDIFF(b.check_out, b.check_in) as nights_stayed,
                            b.total_amount as revenue
                        FROM bookings b
                        LEFT JOIN customers c ON b.customer_id = c.id
                        LEFT JOIN rooms r ON b.room_id = r.id
                        LEFT JOIN room_types rt ON r.room_type_id = rt.id
                        WHERE b.status IN ('checked_out', 'completed', 'archived')
                        ORDER BY b.check_out DESC
                        LIMIT 10");
    $stmt->execute();
    $archived_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get booking statistics for the last 6 months
    $stmt = $pdo->prepare("SELECT 
                            DATE_FORMAT(check_in, '%Y-%m') as month_year,
                            DATE_FORMAT(check_in, '%b %Y') as month_label,
                            COUNT(*) as total
                        FROM bookings 
                        WHERE check_in >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                        AND check_in <= CURRENT_DATE()
                        GROUP BY DATE_FORMAT(check_in, '%Y-%m'), DATE_FORMAT(check_in, '%b %Y')
                        ORDER BY month_year ASC");
    $stmt->execute();
    $bookings_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get room occupancy
    $stmt = $pdo->query("SELECT 
                            rt.name,
                            COUNT(DISTINCT r.id) as total_rooms,
                            COUNT(DISTINCT CASE 
                                WHEN b.status IN ('confirmed', 'checked_in') 
                                AND CURRENT_DATE() BETWEEN DATE(b.check_in) AND DATE(b.check_out)
                                THEN r.id 
                            END) as occupied_rooms
                        FROM room_types rt
                        LEFT JOIN rooms r ON rt.id = r.room_type_id
                        LEFT JOIN bookings b ON r.id = b.room_id
                        GROUP BY rt.id, rt.name
                        ORDER BY rt.id");
    $room_occupancy = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get last 7 days revenue
    $stmt = $pdo->prepare("SELECT 
                            DATE(created_at) as date,
                            DATE_FORMAT(created_at, '%a, %b %d') as date_label,
                            COALESCE(SUM(total_amount), 0) as daily_total
                        FROM bookings 
                        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
                        AND created_at <= CURRENT_DATE()
                        AND status != 'cancelled'
                        GROUP BY DATE(created_at), DATE_FORMAT(created_at, '%a, %b %d')
                        ORDER BY date ASC");
    $stmt->execute();
    $weekly_revenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent bookings with customer and room details
   // Replace the existing bookings query in dashboard.php
$bookingsQuery = "
SELECT 
    b.*,
    c.first_name,
    c.last_name,
    c.email,
    r.room_number,
    rt.name as room_type,
    rt.rate as room_rate,
    DATEDIFF(b.check_out, b.check_in) as nights_stayed,
    COALESCE(NULLIF(b.total_amount, 0), price) as total_amount,
    a1.username as confirmed_by_name,
    a2.username as cancelled_by_name
FROM bookings b
LEFT JOIN customers c ON b.customer_id = c.id
LEFT JOIN rooms r ON b.room_id = r.id
LEFT JOIN room_types rt ON r.room_type_id = rt.id
LEFT JOIN admin a1 ON b.confirmed_by = a1.id
LEFT JOIN admin a2 ON b.cancelled_by = a2.id
WHERE b.status NOT IN ('archived')
ORDER BY 
    CASE 
        WHEN b.status = 'pending' THEN 1
        WHEN b.status = 'confirmed' THEN 2
        WHEN b.status = 'checked_in' THEN 3
        WHEN b.status = 'checked_out' THEN 4
        WHEN b.status = 'cancelled' THEN 5
    END,
    b.created_at ASC
LIMIT 10
";
    $stmt = $pdo->prepare($bookingsQuery);
    $stmt->execute();
    $recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add debug output
    foreach ($recent_bookings as $booking) {
        echo "<!-- Debug Booking ID {$booking['id']}:
        Room Rate: {$booking['room_rate']}
        Nights: {$booking['nights_stayed']}
        Total Amount: {$booking['total_amount']}
        -->";
    }

    // Add debug output
    foreach ($recent_bookings as $booking) {
        echo "<!-- Debug Booking {$booking['id']}: 
        Room Rate: {$booking['room_rate']}
        Nights Stayed: {$booking['nights_stayed']}
        Total Amount Raw: {$booking['total_amount']}
        Calculated Amount: " . ($booking['room_rate'] * $booking['nights_stayed']) . "
        -->";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

try {
    // Get total sales statistics
    $sales_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT id) as total_bookings,
            COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN status != 'cancelled' THEN total_amount END), 0) as average_booking_value,
            COUNT(DISTINCT customer_id) as unique_customers
        FROM bookings
        WHERE status != 'pending'
    ");
    
    $sales_stmt->execute();
    $sales_stats = $sales_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Update the chart data query to fetch all necessary data
try {
    $chart_stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            DATE_FORMAT(created_at, '%b %Y') as month_label,
            COUNT(DISTINCT customer_id) as customer_count,
            SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as revenue
        FROM bookings
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m'), DATE_FORMAT(created_at, '%b %Y')
        ORDER BY month ASC
    ");
    $chart_stmt->execute();
    $chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Add debug output
echo "<!-- Debug Info:
Total Rooms: " . $room_stats['total_rooms'] . "
Occupied Rooms: " . $room_stats['occupied_rooms'] . "
Available Rooms: " . $room_stats['available_rooms'] . "
Active Bookings: " . $active_stats['active_bookings'] . "
Current Date: " . date('Y-m-d') . "
-->";

// Auto-archive checked out bookings
try {
    $archive_stmt = $pdo->prepare("
        UPDATE bookings 
        SET 
            status = 'archived',
            updated_at = NOW()
        WHERE 
            status = 'checked_out' 
            AND check_out < CURRENT_DATE()
    ");
    
    $archive_stmt->execute();
    $archived_count = $archive_stmt->rowCount();
    
    if ($archived_count > 0) {
        $_SESSION['success'] = "$archived_count booking(s) have been archived";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Error archiving bookings: " . $e->getMessage();
}

// Replace the existing archived bookings query with this updated version
$archived_bookings_query = "
    SELECT 
        b.*,
        c.first_name,
        c.last_name,
        c.email,
        r.room_number,
        rt.name as room_type,
        DATEDIFF(b.check_out, b.check_in) as nights_stayed,
        b.total_amount as revenue
    FROM bookings b
    LEFT JOIN customers c ON b.customer_id = c.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN room_types rt ON r.room_type_id = rt.id
    WHERE DATE(b.check_out) < CURRENT_DATE()
    ORDER BY b.check_out DESC
    LIMIT 100"; // Increased limit to show more historical data

try {
    $stmt = $pdo->query($archived_bookings_query);
    $archived_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error fetching archived bookings: " . $e->getMessage();
}

// Get admin name
$admin_name = htmlspecialchars($_SESSION['admin_username']);

// Execute the provided SQL queries
try {
    $stmt = $pdo->query("SELECT * FROM room_types");
    $room_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If needed, update rates
    $stmt = $pdo->prepare("UPDATE room_types SET rate = 1000 WHERE id = 1"); // Adjust values as needed
    $stmt->execute();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Get customer count per month for the last 6 months
$customer_stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(check_out, '%Y-%m') as month,
        DATE_FORMAT(check_out, '%b %Y') as month_label,
        COUNT(DISTINCT id) as customer_count
    FROM bookings 
    WHERE check_out >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        AND status IN ('checked_out', 'archived')  -- Only count completed stays
    GROUP BY DATE_FORMAT(check_out, '%Y-%m'), DATE_FORMAT(check_out, '%b %Y')
    ORDER BY month ASC
");
$customer_stmt->execute();
$customer_data = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <!-- Sidebar Navigation -->
    <nav class="sidebar">
    <div class="text-center mb-2">
        <!-- Update the image path by adding one more ../ to go up one more directory level -->
        <img src="../images/logo1.png" class="mb-2" style="width: 80px; height: auto;" alt="Hotel Logo">
        <h5 class="mb-1">Richard's Hotel</h5>
        <small>Admin Dashboard</small>
    </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="customer_report.php">
                    <i class="fas fa-users"></i> Customers
                </a>
            </li>
            <!-- Add this new settings section -->
            <li class="nav-item">
                <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#settingsModal">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
           
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
                
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Display Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Admin Header -->
        <div class="admin-header mb-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Welcome, <?php echo $admin_name; ?>!</h5>
                    <small class="text-muted">Hotel status today</small>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#pendingApprovalsModal">
                        <i class=""></i>   -->
                        <?php
                        // Get count of pending online bookings
                        $pending_stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM bookings WHERE status = 'awaiting_approval'");
                        $pending_stmt->execute();
                        $pending_count = $pending_stmt->fetch(PDO::FETCH_ASSOC)['pending_count'];
                        if ($pending_count > 0) {
                            echo "<span class='badge bg-danger'>$pending_count</span>";
                        }
                        ?>


                    <span class="text-muted"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-2 mb-2">
    <div class="col-md-3">
        <div class="stat-card checkins p-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted mb-1 d-block">Today's Check-ins</span>
                    <h5 class="mb-0"><?php echo $today_stats['today_checkins']; ?></h5>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-calendar-day fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bookings p-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted mb-1 d-block">Active Bookings</span>
                    <h5 class="mb-0"><?php echo $active_stats['active_bookings']; ?></h5>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-book fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card rooms p-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted mb-1 d-block">Available Rooms</span>
                    <h5 class="mb-0"><?php echo $room_stats['available_rooms']; ?> / <?php echo $room_stats['total_rooms']; ?></h5>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-door-open fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card revenue p-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted mb-1 d-block">Annual Revenue</span>
                    <h5 class="mb-0">₱<?php echo number_format($current_month_revenue['current_month_revenue'], 2); ?></h5>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-peso-sign fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>


    <!-- Charts Section -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent py-2">
                <h6 class="card-title mb-0">Monthly Customer Statistics</h6>
            </div>
            <div class="card-body">
                <canvas id="customerChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

        <!-- Sales Overview -->
        

    

<!-- Replace or update the Charts Section in your HTML -->


        <!-- Recent Bookings -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0">Recent Bookings</h6>
                            <div class="btn-group btn-group-sm">
                                
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Customer</th>
                                        <th>Room</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                    <tr>
                                        <td>#<?= $booking['id'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?>
                                            <div class="text-muted small"><?= htmlspecialchars($booking['email']) ?></div>
                                        </td>
                                        <td>
                                            Room <?= htmlspecialchars($booking['room_number']) ?>
                                            <div class="text-muted small"><?= htmlspecialchars($booking['room_type']) ?></div>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($booking['check_in'])) ?>
                                            <div class="text-muted small"><?= date('h:i A', strtotime($booking['check_in'])) ?></div>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', strtotime($booking['check_out'])) ?>
                                            <div class="text-muted small"><?= date('h:i A', strtotime($booking['check_out'])) ?></div>
                                        </td>
                                        <td>₱<?= number_format($booking['total_amount'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= getStatusBadgeClass($booking['status']) ?>">
                                                <?= ucfirst($booking['status']) ?>
                                            </span>
                                        </td>
                                        <!-- Replace the existing buttons section in the table -->
<td>
    <div class="btn-group btn-group-sm">
        <?php if($booking['status'] == 'pending'): ?>
            <button type="button" class="btn btn-success btn-sm" onclick="handleBooking('confirm', <?= $booking['id'] ?>)">
                <i class="fas fa-check"></i>
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="handleBooking('cancel', <?= $booking['id'] ?>)">
                <i class="fas fa-times"></i>
            </button>
        <?php elseif($booking['status'] == 'confirmed'): ?>
            <button type="button" class="btn btn-primary btn-sm" onclick="handleBooking('check_in', <?= $booking['id'] ?>)">
                <i class="fas fa-sign-in-alt"></i>
            </button>
        <?php elseif($booking['status'] == 'checked_in'): ?>
            <button type="button" class="btn btn-info btn-sm" onclick="handleBooking('check_out', <?= $booking['id'] ?>)">
                <i class="fas fa-sign-out-alt"></i>
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-warning btn-sm" onclick="handleBooking('edit', <?= $booking['id'] ?>)">
            <i class="fas fa-edit"></i>
        </button>
        <?php if($booking['status'] == 'checked_out'): ?>
            <button type="button" class="btn btn-secondary btn-sm" onclick="handleBooking('archive', <?= $booking['id'] ?>)">
                <i class="fas fa-archive"></i>
            </button>
        <?php endif; ?>
    </div>
</td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($recent_bookings)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-3">
                                            <i class="fas fa-info-circle text-info me-2"></i>
                                            No recent bookings found
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
    

    <!-- Archive Modal -->
    <div class="modal fade" id="archiveModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title mb-0"><i class="fas fa-archive"></i> Archived Bookings</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Room</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Completed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($archived_bookings as $booking): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) ?>
                                        <div class="text-muted"><?= htmlspecialchars($booking['email']) ?></div>
                                    </td>
                                    <td>
                                        Room <?= htmlspecialchars($booking['room_number']) ?>
                                        <div class="text-muted"><?= htmlspecialchars($booking['room_type']) ?></div>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($booking['check_in'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($booking['check_out'])) ?></td>
                                    <td>₱<?= number_format($booking['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= getStatusBadgeClass($booking['status']) ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($booking['updated_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer py-1">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-primary" onclick="printArchiveReport()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="fas fa-cog"></i> Admin Settings</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#changePassword">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#createAdmin">
                            <i class="fas fa-user-plus"></i> Create Admin
                        </a>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Change Password Tab -->
                    <div class="tab-pane fade show active" id="changePassword">
                        <form id="changePasswordForm" action="process_admin.php" method="POST">
                            <input type="hidden" name="action" value="change_password">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>

                    <!-- Create Admin Tab -->
                    <div class="tab-pane fade" id="createAdmin">
                        <form id="createAdminForm" action="process_admin.php" method="POST">
                            <input type="hidden" name="action" value="create_admin">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Create Admin</button>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Edit Booking Modal -->
<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editBookingForm">
                    <input type="hidden" name="action" value="edit_booking">
                    <input type="hidden" name="booking_id" id="editBookingId">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="editFirstName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="editLastName" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="editEmail" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone" id="editPhone" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Check-in Date</label>
                            <input type="datetime-local" class="form-control" name="check_in" id="editCheckIn" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Check-out Date</label>
                            <input type="datetime-local" class="form-control" name="check_out" id="editCheckOut" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Room Number</label>
                            <select class="form-select" name="room_id" id="editRoomId" required>
                                <!-- Rooms will be populated dynamically -->
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" id="editStatus" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="checked_in">Checked In</option>
                                <option value="checked_out">Checked Out</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Total Amount</label>
                        <input type="number" class="form-control" name="total_amount" id="editTotalAmount" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" id="editNotes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveBookingChanges()">Save Changes</button>
            </div>
        </div>
    </div>
</div>



    <!-- Bootstrap JS and Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div id="notification" class="notification"></div>
    <script>
        // Replace the existing form handlers section with this updated code

document.addEventListener('DOMContentLoaded', function() {
    // Handle Change Password Form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            try {
                const formData = new FormData(this);
                const response = await fetch('process_admin.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        title: result.title,
                        text: result.message,
                        icon: result.icon,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then((swalResult) => {
                        if (swalResult.isConfirmed) {
                            this.reset();
                            bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.message || 'Something went wrong',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while changing password',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    }

    // Handle Create Admin Form
    const createAdminForm = document.getElementById('createAdminForm');
    if (createAdminForm) {
        createAdminForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            try {
                const formData = new FormData(this);
                const response = await fetch('process_admin.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        title: result.title,
                        text: result.message,
                        icon: result.icon,
                        confirmButtonColor: '#3085d6',
                        confirmButtonText: 'OK'
                    }).then((swalResult) => {
                        if (swalResult.isConfirmed) {
                            this.reset();
                            bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.message || 'Something went wrong',
                        icon: 'error',
                        confirmButtonColor: '#3085d6'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while creating admin',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    }
});
</script>

<script>
function handleBooking(action, bookingId) {
    // For edit action
    if (action === 'edit') {
        fetchBookingDetails(bookingId);
        return;
    }

    let confirmationMessage = '';
    let promptReason = false;

    switch (action) {
        case 'confirm':
            confirmationMessage = 'Are you sure you want to confirm this booking?';
            break;
        case 'cancel':
            confirmationMessage = 'Please enter the reason for cancellation:';
            promptReason = true;
            break;
        case 'check_in':
            confirmationMessage = 'Are you sure you want to check in this guest?';
            break;
        case 'check_out':
            confirmationMessage = 'Are you sure you want to check out this guest?';
            break;
        case 'archive':
            confirmationMessage = 'Are you sure you want to archive this booking?';
            break;
        default:
            console.error('Invalid action:', action);
            return;
    }

    if (promptReason) {
        Swal.fire({
            title: 'Cancellation Reason',
            input: 'text',
            inputLabel: 'Please enter the reason for cancellation:',
            showCancelButton: true,
            inputValidator: (value) => {
                if (!value) {
                    return 'You need to provide a reason!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                sendBookingRequest(action, bookingId, result.value);
            }
        });
    } else {
        Swal.fire({
            title: 'Confirm Action',
            text: confirmationMessage,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed) {
                sendBookingRequest(action, bookingId);
            }
        });
    }
}

async function fetchBookingDetails(bookingId) {
    try {
        const response = await fetch(`get_booking.php?id=${bookingId}`);
        const data = await response.json();
        
        if (data.success) {
            // Populate form fields
            document.getElementById('editBookingId').value = data.booking.id;
            document.getElementById('editFirstName').value = data.booking.first_name;
            document.getElementById('editLastName').value = data.booking.last_name;
            document.getElementById('editEmail').value = data.booking.email;
            document.getElementById('editPhone').value = data.booking.phone;
            document.getElementById('editCheckIn').value = data.booking.check_in.slice(0, 16);
            document.getElementById('editCheckOut').value = data.booking.check_out.slice(0, 16);
            document.getElementById('editRoomId').value = data.booking.room_id;
            document.getElementById('editStatus').value = data.booking.status;
            document.getElementById('editTotalAmount').value = data.booking.total_amount;
            
            // Show modal
            const editModal = new bootstrap.Modal(document.getElementById('editBookingModal'));
            editModal.show();
        } else {
            Swal.fire('Error', data.message || 'Failed to fetch booking details', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'Failed to fetch booking details', 'error');
    }
}

async function sendBookingRequest(action, bookingId, reason = '') {
    try {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('booking_id', bookingId);
        if (reason) formData.append('reason', reason);

        const response = await fetch('process_booking.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.message || 'Action completed successfully',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire('Error', result.message || 'Failed to process request', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire('Error', 'An unexpected error occurred', 'error');
    }
}
</script>

<script>
// Replace the existing chart JavaScript code

document.addEventListener('DOMContentLoaded', function() {
    // Get customer data from PHP
    const customerData = <?php echo json_encode($customer_data, JSON_NUMERIC_CHECK); ?>;
    
    // Function to get months in order
    function getMonthsInOrder() {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    }

    // Initialize data for all months with zero values
    const allMonths = getMonthsInOrder();
    const defaultData = allMonths.map(month => ({
        month_label: month,
        customer_count: 0
    }));

    // Update counts for months that have data
    customerData.forEach(data => {
        const monthIndex = allMonths.indexOf(data.month_label.split(' ')[0]);
        if (monthIndex !== -1) {
            defaultData[monthIndex].customer_count = data.customer_count;
        }
    });

    // Create the chart
    const ctx = document.getElementById('customerChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: defaultData.map(data => data.month_label),
            datasets: [
                {
                    label: 'Monthly',
                    data: defaultData.map(data => data.customer_count),
                    backgroundColor: 'rgb(0, 123, 255)',
                    barPercentage: 0.6,
                    categoryPercentage: 0.7
                },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'center',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        stepSize: 5,
                        font: {
                            size: 11
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
});
</script>




    <script>
        // Helper function to format currency
        function formatCurrency(value) {
            return new Intl.NumberFormat('en-PH', {
                style: 'currency',
                currency: 'PHP',
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(value);
        }

        // Function to export monthly stats to CSV
        function exportMonthlyStats() {
            const table = document.querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (const row of rows) {
                const cols = row.querySelectorAll('td,th');
                const rowData = Array.from(cols).map(col => {
                    let text = col.textContent.trim();
                    // Remove currency symbols and commas from numbers
                    text = text.replace(/[$,₱]/g, '');
                    // Wrap text in quotes if it contains commas
                    return text.includes(',') ? `"${text}"` : text;
                });
                csv.push(rowData.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.setAttribute('download', 'monthly_revenue_stats.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Function to print archive report
        function printArchiveReport() {
            const modalContent = document.querySelector('#archiveModal .modal-body').innerHTML;
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Archived Bookings Report</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        @media print {
                            body {
                                padding: 20px;
                                font-size: 10pt;
                            }
                            .table {
                                font-size: 9pt;
                            }
                            @page {
                                size: A4;
                                margin: 1cm;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="text-center mb-3">
                        <h3>Richard's Hotel</h3>
                        <h5>Archived Bookings Report</h5>
                        <small>Generated on: ${new Date().toLocaleString()}</small>
                    </div>
                    ${modalContent}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        // Replace the existing exportSalesStats function with this new printSalesStats function
        function printSalesStats() {
            const salesTable = document.querySelector('.card-body table').cloneNode(true);
            const printWindow = window.open('', '', 'width=800,height=600');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Sales Statistics Report</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <style>
                        @media print {
                            body {
                                padding: 20px;
                                font-size: 12pt;
                            }
                            .table {
                                width: 100%;
                                margin-bottom: 1rem;
                                border-collapse: collapse;
                            }
                            .table th,
                            .table td {
                                padding: 0.75rem;
                                border: 1px solid #dee2e6;
                            }
                            @page {
                                size: A4;
                                margin: 2cm;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="text-center mb-4">
                            <h3>Richard's Hotel</h3>
                            <h4>Sales Statistics Report</h4>
                            <p>Generated on: ${new Date().toLocaleString()}</p>
                        </div>
                        <div class="table-responsive">
                            ${salesTable.outerHTML}
                        </div>
                    </div>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        }

        // Prepare chart data
       // PHP-injected data
const chartData = <?php echo json_encode($chart_data); ?>;

// Sort chartData by month order if necessary
const monthOrder = ['January', 'February', 'March', 'April', 'May', 'June', 
                    'July', 'August', 'September', 'October', 'November', 'December'];

chartData.sort((a, b) => {
    return monthOrder.indexOf(a.month_label) - monthOrder.indexOf(b.month_label);
});
</script>

    <script>
// Add this code to replace the existing admin settings event listeners in dashboard.php

// Replace the existing admin form handlers with this updated version
document.addEventListener('DOMContentLoaded', function() {
    // Handle Change Password Form
    document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('process_admin.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Password changed successfully!');
                this.reset();
                bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
            } else {
                alert('Failed to change password: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while changing password');
        }
    });

    // Handle Create Admin Form 
    document.getElementById('createAdminForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        try {
            const formData = new FormData(this);
            const response = await fetch('process_admin.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                alert('Admin created successfully!');
                this.reset();
                bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
            } else {
                alert('Failed to create admin: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while creating admin');
        }
    });
});
</script>
</body>
</html>