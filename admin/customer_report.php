<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Check admin session
checkAdminSession();

// Default to all-time if no filter is set
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Set SQL date filters based on selection
$date_filter_sql = "";
$filter_description = "All Time";

switch ($filter) {
    case 'daily':
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $date_filter_sql = "AND DATE(b.check_in) = '$date'"; // Changed from created_at
        $filter_description = "Daily Report: " . date('F d, Y', strtotime($date));
        break;
    case 'monthly':
        $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
        $date_filter_sql = "AND DATE_FORMAT(b.check_in, '%Y-%m') = '$month'"; // Changed from created_at
        $filter_description = "Monthly Report: " . date('F Y', strtotime($month . "-01"));
        break;
    case 'yearly':
        $year = isset($_GET['year']) ? $_GET['year'] : date('Y');
        $date_filter_sql = "AND YEAR(b.check_in) = '$year'"; // Changed from created_at
        $filter_description = "Annual Report: " . $year;
        break;
    case 'custom':
        if ($custom_start && $custom_end) {
            $date_filter_sql = "AND DATE(b.check_in) BETWEEN '$custom_start' AND '$custom_end'";
            $filter_description = "Custom: " . date('M d, Y', strtotime($custom_start)) . " to " . date('M d, Y', strtotime($custom_end));
        }
        break;
    case 'weekly':
        $end_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-7 days', strtotime($end_date)));
        $date_filter_sql = "AND DATE(b.check_in) BETWEEN '$start_date' AND '$end_date'";
        $filter_description = "Weekly Report: " . date('M d', strtotime($start_date)) . " - " . date('M d, Y', strtotime($end_date));
        break;
}

try {
   // Replace the existing SQL query (around line 41) with:
$stmt = $pdo->prepare("
SELECT 
    c.*,
    COUNT(DISTINCT b.id) as total_bookings,
    SUM(CASE WHEN b.status != 'cancelled' THEN b.total_amount ELSE 0 END) as total_spent,
    MAX(b.created_at) as last_booking_date,
    GROUP_CONCAT(DISTINCT rt.name) as room_types_booked,
    DATE_FORMAT(MAX(b.check_in), '%Y-%m-%d %H:%i') as latest_check_in,
    DATE_FORMAT(MAX(b.check_out), '%Y-%m-%d %H:%i') as latest_check_out
FROM customers c
LEFT JOIN bookings b ON c.id = b.customer_id
LEFT JOIN rooms r ON b.room_id = r.id
LEFT JOIN room_types rt ON r.room_type_id = rt.id
WHERE 1=1 $date_filter_sql
GROUP BY c.id
ORDER BY c.created_at DESC
");
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get available years for the filter
    $years_stmt = $pdo->query("
        SELECT DISTINCT YEAR(created_at) as year 
        FROM bookings 
        ORDER BY year DESC
    ");
    $available_years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get available months for the current year
    $current_year = date('Y');
    $months_stmt = $pdo->prepare("
        SELECT DISTINCT DATE_FORMAT(created_at, '%Y-%m') as month 
        FROM bookings 
        WHERE YEAR(created_at) = ? 
        ORDER BY month DESC
    ");
    $months_stmt->execute([$current_year]);
    $available_months = $months_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

try {
    // Get total sales statistics with filter
    $sales_stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT b.id) as total_bookings,
            COALESCE(SUM(CASE WHEN b.status != 'cancelled' THEN b.total_amount ELSE 0 END), 0) as total_revenue,
            COALESCE(AVG(CASE WHEN b.status != 'cancelled' THEN b.total_amount END), 0) as average_booking_value,
            COUNT(DISTINCT b.customer_id) as unique_customers
        FROM bookings b
        WHERE 1=1
        " . ($date_filter_sql ? $date_filter_sql : ""));
    
    $sales_stmt->execute();
    $sales_stats = $sales_stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Report - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DatePicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --sidebar-width: 200px;
        }
        
        body {
            font-size: 0.875rem;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: #2c3e50;
            color: white;
            padding-top: 10px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 8px 12px;
            margin: 2px 8px;
            border-radius: 4px;
            transition: all 0.3s;
            font-size: 0.85rem;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 6px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 15px;
        }

        .filter-card {
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            background: white;
            margin-bottom: 15px;
            padding: 12px;
        }
        
        .card {
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .form-label {
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .form-control, .form-select {
            padding: 0.25rem 0.5rem;
            font-size: 0.85rem;
            min-height: auto;
        }
        
        .table {
            font-size: 0.8rem;
        }
        
        .table > :not(caption) > * > * {
            padding: 0.4rem 0.6rem;
        }
        
        h2 {
            font-size: 1.5rem;
        }
        
        h5 {
            font-size: 1.25rem;
        }
        
        .actions-column {
            width: 100px;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                margin: 0;
                padding: 0;
                font-size: 10pt;
            }
            
            .sidebar, .no-print {
                display: none !important;
            }

            .main-content {
                margin: 0 !important;
                padding: 0 !important;
                width: 210mm; /* A4 width */
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .card-body {
                padding: 0 !important;
            }

            /* Coupon style header */
            .print-header {
                text-align: center;
                border-bottom: 1px dashed #000;
                margin-bottom: 15px;
                padding: 15px;
            }

            .print-header h2 {
                margin: 0;
                font-size: 16pt;
            }

            .print-header p {
                margin: 3px 0;
                font-size: 10pt;
            }

            /* Table styles for print */
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 9pt;
                margin: 15px 0;
            }

            th, td {
                padding: 4px 6px;
                border: 1px solid #ddd;
                text-align: left;
            }

            th {
                background-color: #f8f9fa;
            }

            /* Footer style */
            .print-footer {
                text-align: center;
                border-top: 1px dashed #000;
                margin-top: 15px;
                padding: 10px;
                font-size: 9pt;
                position: fixed;
                bottom: 0;
                width: 100%;
            }

            /* Page break settings */
            .table-responsive {
                page-break-inside: auto;
                overflow: visible !important;
            }

            .sales-summary {
                border: 1px solid #ddd;
                padding: 10px;
                margin: 15px 0;
            }
            
            .sales-summary h4 {
                font-size: 14pt;
                margin-bottom: 10px;
            }
            
            .sales-summary table {
                width: 100%;
                font-size: 10pt;
            }
            
            .sales-summary td {
                padding: 4px;
                border: none;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Sidebar Navigation -->
    <nav class="sidebar">
    <div class="text-center mb-2">
        <!-- Fixed image path -->
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
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="fas fa-users"></i> Customer Report</h2>
                <div class="no-print">
                    <button onclick="window.print()" class="btn btn-sm btn-primary me-1">
                        <i class="fas fa-print"></i> Print
                    </button>
                    <button onclick="exportToCSV()" class="btn btn-sm btn-success">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>

            <!-- Filter Options - More Compact -->
            <div class="filter-card no-print">
                <form id="filter-form" method="GET" class="row g-2">
                    <div class="col-auto">
                        <label class="form-label visually-hidden">Filter</label>
                        <select name="filter" id="filter-select" class="form-select form-select-sm">
                            <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Time</option>
                            <option value="weekly" <?= $filter == 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="daily" <?= $filter == 'daily' ? 'selected' : '' ?>>Daily</option>
                            <option value="monthly" <?= $filter == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= $filter == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                            <option value="custom" <?= $filter == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                        </select>
                    </div>
                    
                    <!-- Daily Filter -->
                    <div class="col-auto filter-option" id="daily-filter" style="display: <?= $filter == 'daily' ? 'inline-block' : 'none' ?>">
                        <input type="date" name="date" class="form-control form-control-sm datepicker" value="<?= isset($_GET['date']) ? $_GET['date'] : date('Y-m-d') ?>" placeholder="Select Date">
                    </div>
                    
                    <!-- Weekly Filter -->
                    <div class="col-auto filter-option" id="weekly-filter" style="display: <?= $filter == 'weekly' ? 'inline-block' : 'none' ?>">
                        <input type="date" name="date" class="form-control form-control-sm datepicker" 
                               value="<?= isset($_GET['date']) ? $_GET['date'] : date('Y-m-d') ?>" 
                               placeholder="Select End Date">
                    </div>
                    
                    <!-- Monthly Filter -->
                    <div class="col-auto filter-option" id="monthly-filter" style="display: <?= $filter == 'monthly' ? 'inline-block' : 'none' ?>">
                        <input type="month" name="month" class="form-control form-control-sm" value="<?= isset($_GET['month']) ? $_GET['month'] : date('Y-m') ?>">
                    </div>
                    
                    <!-- Yearly Filter -->
                    <div class="col-auto filter-option" id="yearly-filter" style="display: <?= $filter == 'yearly' ? 'inline-block' : 'none' ?>">
                        <select name="year" class="form-select form-select-sm">
                            <?php foreach ($available_years as $year): ?>
                                <option value="<?= $year ?>" <?= (isset($_GET['year']) && $_GET['year'] == $year) ? 'selected' : '' ?>>
                                    <?= $year ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Custom Range Filter -->
                    <div class="filter-option" id="custom-filter" style="display: <?= $filter == 'custom' ? 'flex' : 'none' ?>">
                        <div class="input-group input-group-sm">
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $custom_start ?>" placeholder="Start">
                            <span class="input-group-text">to</span>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $custom_end ?>" placeholder="End">
                        </div>
                    </div>
                    
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-filter"></i> Apply
                        </button>
                    </div>
                    
                    <div class="col ms-auto">
                        <span class="badge bg-info text-dark">
                            <i class="fas fa-info-circle"></i> <?= $filter_description ?>
                        </span>
                    </div>
                </form>
            </div>

            <div class="card">
                <div class="card-body p-2">
                    <!-- Print Header - Only visible when printing -->
                    <div class="print-header d-none d-print-block">
    <h2>Richard's Hotel</h2>
    <p>Customer Report</p>
    <p id="generated-on-text">Generated on: </p>
    <p><?= $filter_description ?></p>
    
    <!-- Add sales summary section -->
    <div class="sales-summary mt-3 mb-3">
        <h4>Sales Summary</h4>
        <table class="table table-sm">
            <tr>
                <td>Total Bookings:</td>
                <td><?= number_format($sales_stats['total_bookings']) ?></td>
                <td>Total Revenue:</td>
                <td>₱<?= number_format($sales_stats['total_revenue'], 2) ?></td>
            </tr>
            <tr>
                <td>Average Booking Value:</td>
                <td>₱<?= number_format($sales_stats['average_booking_value'], 2) ?></td>
                <td>Unique Customers:</td>
                <td><?= number_format($sales_stats['unique_customers']) ?></td>
            </tr>
        </table>
    </div>
</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                
                    <th class="text-end">Bill</th>
                   
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Room Types</th>
    
                  </tr>
</thead>
<tbody>
    <?php if(count($customers) > 0): ?>
        <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></td>
            <td><?= htmlspecialchars($customer['email']) ?></td>
            <td><?= htmlspecialchars($customer['phone']) ?></td>
           
            <td class="text-end">₱<?= number_format($customer['total_spent'], 2) ?></td>
            
            <td><?= $customer['latest_check_in'] ? date('M d, Y H:i', strtotime($customer['latest_check_in'])) : 'N/A' ?></td>
            <td><?= $customer['latest_check_out'] ? date('M d, Y H:i', strtotime($customer['latest_check_out'])) : 'N/A' ?></td>
            <td><?= htmlspecialchars($customer['room_types_booked'] ?: 'None') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="10" class="text-center">No customer data found for the selected time period.</td>
        </tr>
    <?php endif; ?>
</tbody>
                        </table>
                    </div>

                    <!-- Print Footer - Only visible when printing -->
                    <div class="print-footer d-none d-print-block">
                        <p>Richard's Hotel Management System</p>
                        <p>Page 1</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-2 text-end no-print">
                <small class="text-muted">Total Records: <?= count($customers) ?></small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        // Initialize date pickers
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr(".datepicker", {
                dateFormat: "Y-m-d"
            });
            
            // Show/hide filter options based on selection
            document.getElementById('filter-select').addEventListener('change', function() {
                // Hide all filter options first
                const filterOptions = document.querySelectorAll('.filter-option');
                filterOptions.forEach(option => {
                    option.style.display = 'none';
                });
                
                // Show selected filter option
                const selected = this.value;
                if (selected !== 'all') {
                    document.getElementById(selected + '-filter').style.display = 
                        selected === 'custom' ? 'flex' : 'inline-block';
                }
            });
        });

        function exportToCSV() {
            const table = document.querySelector('table');
            let csv = [];
            const rows = table.querySelectorAll('tr');
            
            for (const row of rows) {
                const cols = row.querySelectorAll('td,th');
                const rowData = Array.from(cols).map(col => {
                    let text = col.textContent.trim();
                    // Remove currency symbols and commas from numbers
                    text = text.replace(/[₱,]/g, '');
                    // Wrap text in quotes if it contains commas
                    return text.includes(',') ? `"${text}"` : text;
                });
                csv.push(rowData.join(','));
            }
            
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            
            // Add the filter type to the filename
            const filterType = document.getElementById('filter-select').value;
            let filename = 'customer_report';
            
            switch(filterType) {
                case 'daily':
                    const date = document.querySelector('input[name="date"]').value;
                    filename += '_daily_' + date;
                    break;
                case 'monthly':
                    const month = document.querySelector('input[name="month"]').value;
                    filename += '_monthly_' + month;
                    break;
                case 'yearly':
                    const year = document.querySelector('select[name="year"]').value;
                    filename += '_yearly_' + year;
                    break;
                case 'custom':
                    const startDate = document.querySelector('input[name="start_date"]').value;
                    const endDate = document.querySelector('input[name="end_date"]').value;
                    filename += '_custom_' + startDate + '_to_' + endDate;
                    break;
                case 'weekly':
                    const weekEndDate = document.querySelector('input[name="date"]').value;
                    const weekStartDate = new Date(weekEndDate);
                    weekStartDate.setDate(weekStartDate.getDate() - 7);
                    filename += '_weekly_' + weekStartDate.toISOString().split('T')[0] + '_to_' + weekEndDate;
                    break;
            }
            
            link.setAttribute('download', filename + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        // Set generated date when print is triggered
window.addEventListener('beforeprint', function () {
    const now = new Date();
    const options = { 
        year: 'numeric', 
        month: 'long', 
        day: '2-digit', 
        hour: '2-digit', 
        minute: '2-digit', 
        hour12: true 
    };
    const formattedDate = now.toLocaleString('en-US', options);
    document.getElementById('generated-on-text').textContent = "Generated on: " + formattedDate;
});
function showNotification(message, type = 'success') {
    const notification = document.getElementById('notification');
    notification.textContent = message;
    notification.className = `notification ${type}`;
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Hide notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
    }, 3000);
}
    </script>
</body>
</html>