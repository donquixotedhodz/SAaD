<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';

// Get database variables from database.php
global $host, $username, $password, $database;

// Initialize database connection
try {
    $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Auto-archive completed bookings
$stmt = $pdo->prepare("
    UPDATE bookings 
    SET status = 'archived'
    WHERE status = 'checked_out' 
    AND check_out < CURRENT_DATE()
");
$stmt->execute();

// Get archived bookings with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 15;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$stmt = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'archived'");
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get archived bookings for current page
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        c.first_name,
        c.last_name,
        c.email,
        c.phone,
        r.room_number,
        rt.name as room_type,
        DATEDIFF(b.check_out, b.check_in) as nights_stayed,
        b.total_amount as revenue
    FROM bookings b
    LEFT JOIN customers c ON b.customer_id = c.id
    LEFT JOIN rooms r ON b.room_id = r.id
    LEFT JOIN room_types rt ON r.room_type_id = rt.id
    WHERE b.status = 'archived'
    ORDER BY b.check_out DESC
    LIMIT :limit OFFSET :offset
");

$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$archived_bookings = $stmt->fetchAll();

// Calculate statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total_bookings,
        COALESCE(SUM(total_amount), 0) as total_revenue,
        COALESCE(AVG(NULLIF(total_amount, 0)), 0) as avg_revenue,
        COALESCE(AVG(NULLIF(DATEDIFF(check_out, check_in), 0)), 0) as avg_stay
    FROM bookings 
    WHERE status = 'archived'
");
$stats = $stmt->fetch();

// Format the statistics with default values if null
$stats['total_bookings'] = (int)$stats['total_bookings'];
$stats['total_revenue'] = (float)$stats['total_revenue'];
$stats['avg_revenue'] = (float)$stats['avg_revenue'];
$stats['avg_stay'] = (float)$stats['avg_stay'];

// Helper function for status badge
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'archived':
            return 'bg-success text-white';
        default:
            return 'bg-secondary text-white';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived Bookings - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <h2 class="h4 mb-4">Archived Bookings</h2>
                
                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Total Bookings</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['total_bookings']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Total Revenue</h6>
                                <h3 class="mb-0">₱<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Average Revenue</h6>
                                <h3 class="mb-0">₱<?php echo number_format($stats['avg_revenue'], 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted">Average Stay</h6>
                                <h3 class="mb-0"><?php echo number_format($stats['avg_stay'], 1); ?> nights</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Completed Bookings History</h5>
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportToCSV()">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="bookingsTable">
                                <thead>
                                    <tr>
                                        <th>Booking ID</th>
                                        <th>Guest</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Nights</th>
                                        <th>Revenue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($archived_bookings)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No archived bookings found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($archived_bookings as $booking): ?>
                                            <tr>
                                                <td><?php echo $booking['id']; ?></td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($booking['room_number']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['room_type']); ?></small>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($booking['check_in'])); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($booking['check_out'])); ?></td>
                                                <td><?php echo $booking['nights_stayed']; ?></td>
                                                <td><?php echo '₱' . number_format($booking['revenue'], 2); ?></td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function exportToCSV() {
            const table = document.getElementById('bookingsTable');
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
            link.setAttribute('download', 'archived_bookings.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
