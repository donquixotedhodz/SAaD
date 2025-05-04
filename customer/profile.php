<?php
session_start();
require_once '../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: login.php');
    exit();
}

$customer = $_SESSION['customer'];

try {
    // Get customer's active bookings
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            rt.name as room_type,
            r.room_number,
            a1.username as confirmed_by_name,
            a2.username as cancelled_by_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        LEFT JOIN admin a1 ON b.confirmed_by = a1.id
        LEFT JOIN admin a2 ON b.cancelled_by = a2.id
        WHERE b.customer_id = ? 
        AND b.status IN ('pending', 'confirmed', 'checked_in')
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$customer['id']]);
    $active_bookings = $stmt->fetchAll();

    // Get customer's past bookings
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            rt.name as room_type,
            r.room_number,
            a1.username as confirmed_by_name,
            a2.username as cancelled_by_name
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        LEFT JOIN admin a1 ON b.confirmed_by = a1.id
        LEFT JOIN admin a2 ON b.cancelled_by = a2.id
        WHERE b.customer_id = ? 
        AND b.status IN ('checked_out', 'cancelled')
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$customer['id']]);
    $past_bookings = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: error.php");
    exit();
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'confirmed':
            return 'bg-success text-white';
        case 'checked_in':
            return 'bg-info text-white';
        case 'checked_out':
            return 'bg-secondary text-white';
        case 'cancelled':
            return 'bg-danger text-white';
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
    <title>My Profile - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .nav-link {
            color: #333;
        }
        .nav-link:hover {
            color: #0d6efd;
        }
        .active-booking {
            background-color: #e8f4ff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <img src="../images/logo.png" alt="Hotel Logo" class="img-fluid mb-3" style="max-height: 60px;">
                        <h5><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user-circle me-2"></i>My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../index.php">
                                <i class="fas fa-home me-2"></i>Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Profile</h1>
                </div>

                <!-- Profile Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        Personal Information
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($customer['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($customer['phone']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bookings -->
                <div class="card">
                    <div class="card-header">
                        My Bookings
                    </div>
                    <div class="card-body">
                        <!-- Add tabs for active and archived bookings -->
                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#active">Active Bookings</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#archived">Archived Bookings</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="active">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Room Type</th>
                                                <th>Room Number</th>
                                                <th>Check-in</th>
                                                <th>Check-out</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($active_bookings)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No active bookings found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($active_bookings as $booking): ?>
                                                    <tr>
                                                        <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                                        <td><?php echo date('M d, Y h:i A', strtotime($booking['check_in'])); ?></td>
                                                        <td><?php echo date('M d, Y h:i A', strtotime($booking['check_out'])); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                                                                <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                        <td>
                                                            <a href="view_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <?php if ($booking['status'] === 'pending'): ?>
                                                                <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                                    <i class="fas fa-times"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="archived">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Booking ID</th>
                                                <th>Room Type</th>
                                                <th>Room Number</th>
                                                <th>Check-in</th>
                                                <th>Check-out</th>
                                                <th>Status</th>
                                                <th>Amount</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($past_bookings)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No archived bookings found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($past_bookings as $booking): ?>
                                                    <tr>
                                                        <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['room_type']); ?></td>
                                                        <td><?php echo htmlspecialchars($booking['room_number']); ?></td>
                                                        <td><?php echo date('M d, Y h:i A', strtotime($booking['check_in'])); ?></td>
                                                        <td><?php echo date('M d, Y h:i A', strtotime($booking['check_out'])); ?></td>
                                                        <td>
                                                            <span class="badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                                                                <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                                            </span>
                                                        </td>
                                                        <td>₱<?php echo number_format($booking['total_amount'], 2); ?></td>
                                                        <td>
                                                            <a href="view_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
