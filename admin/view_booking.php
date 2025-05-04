<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Check admin session
checkAdminSession();

// Check if booking ID is provided
if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$booking_id = $_GET['id'];

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            r.room_number,
            rt.name as room_type,
            rt.price,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            a.username as confirmed_by_username
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        JOIN customers c ON b.customer_id = c.id
        LEFT JOIN admin a ON b.confirmed_by = a.id
        WHERE b.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new Exception('Booking not found');
    }

    // Get confirming admin's name if booking is confirmed
    $admin_name = 'Not confirmed';
    if ($booking['status'] === 'confirmed' && !empty($booking['confirmed_by'])) {
        $admin_name = $booking['confirmed_by_username'] ?? 'Unknown';
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: dashboard.php");
    exit();
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'confirmed':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        case 'completed':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Booking - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-details {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .detail-row {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .btn-toolbar {
            margin-top: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
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
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Booking Details #<?php echo htmlspecialchars($booking['id']); ?></h1>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="booking-status">
                    <span class="badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                    <?php if ($booking['status'] === 'pending'): ?>
                        <div class="action-buttons d-inline-block ms-2">
                            <form action="approve_booking.php" method="POST" class="d-inline">
                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">
                                <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this booking?');">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                            </form>
                            <form action="cancel_booking.php" method="POST" class="d-inline ms-2">
                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">
                                <input type="hidden" name="admin_cancelled" value="1">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card booking-details">
            <div class="card-body">
                <div class="row detail-row">
                    <div class="col-md-6">
                        <div class="detail-label">Amount</div>
                        ₱<?php echo number_format($booking['total_amount'], 2); ?>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Customer Name</div>
                        <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                    </div>
                </div>

                <div class="row detail-row">
                    <div class="col-md-6">
                        <div class="detail-label">Contact Info</div>
                        <div><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($booking['email']); ?></div>
                        <div><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($booking['phone']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-label">Room Details</div>
                        <div>Room #<?php echo htmlspecialchars($booking['room_number']); ?></div>
                        <div><?php echo htmlspecialchars($booking['room_type']); ?></div>
                    </div>
                </div>

                <div class="row detail-row">
                    <div class="col-md-6">
                        <div class="detail-label">Booking Dates</div>
                        <div>
                            <i class="fas fa-calendar-check me-2"></i>
                            Check-in: <?php echo date('Y-m-d H:i', strtotime($booking['check_in'])); ?>
                        </div>
                        <div>
                            <i class="fas fa-calendar-times me-2"></i>
                            Check-out: <?php echo date('Y-m-d H:i', strtotime($booking['check_out'])); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php if ($booking['status'] === 'confirmed'): ?>
                            <div class="detail-label">Confirmation Details</div>
                            <div>
                                <i class="fas fa-user-check me-2"></i>
                                Confirmed by: <?php echo htmlspecialchars($admin_name); ?>
                            </div>
                            <div>
                                <i class="fas fa-clock me-2"></i>
                                Confirmed at: <?php echo !empty($booking['confirmed_at']) ? date('Y-m-d H:i', strtotime($booking['confirmed_at'])) : 'Not yet confirmed'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($booking['status'] === 'pending'): ?>
                    <div class="btn-toolbar justify-content-center">
                        <a href="confirm_booking.php?id=<?php echo $booking['id']; ?>" 
                           class="btn btn-success me-2"
                           onclick="return confirm('Are you sure you want to confirm this booking?')">
                            <i class="fas fa-check me-2"></i>Confirm Booking
                        </a>
                        <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to cancel this booking?')">
                            <i class="fas fa-times me-2"></i>Cancel Booking
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
