<?php
session_start();
require_once '../config/database.php';
require_once '../vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$booking_id = $_GET['id'];
$customer = $_SESSION['customer'];

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            r.room_number,
            rt.name as room_type,
            rt.price,
            rt.duration_hours
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE b.id = ? AND b.customer_id = ?
    ");
    $stmt->execute([$booking_id, $customer['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $_SESSION['error'] = "Booking not found.";
        header("Location: profile.php");
        exit();
    }

    // Generate QR code
    $qr_data = json_encode([
        'booking_id' => $booking['id'],
        'room_number' => $booking['room_number'],
        'check_in' => $booking['check_in'],
        'check_out' => $booking['check_out']
    ]);

    $qr = QrCode::create($qr_data)
        ->setSize(300)
        ->setMargin(10);

    $writer = new PngWriter();
    $result = $writer->write($qr);

    // Get QR code as data URI
    $qr_image = $result->getDataUri();
} catch (PDOException $e) {
    error_log("Error retrieving booking: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving the booking details.";
    header("Location: profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Sidebar Styles */
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
        }
        .nav-link {
            color:  rgba(255,255,255,0.8);
        }
        .nav-link:hover {
            color: #0d6efd;
        }
        .active-booking {
            background-color: #e8f4ff;
        }
        h5 {
            color: #fff;
        }

        /* Card Styles */
        .booking-card {
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            border: none;
            transition: transform 0.2s ease;
        }

        .booking-card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            padding: 1.2rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Status Badge */
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* QR Code Container */
        .qr-code {
            max-width: 250px;
            margin: 0 auto;
            padding: 1rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
        }

        .qr-code img {
            border-radius: 8px;
        }

        /* Info Labels */
        .info-label {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-size: 1rem;
            color: #2c3e50;
            font-weight: 500;
        }

        /* Action Buttons */
        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        /* Page Header */
        .page-header {
            background: #f8f9fa;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
                        <img src="../images/logo1.png" alt="Hotel Logo" class="img-fluid mb-3" style="max-height: 60px;">
                        <h5><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h5>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
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
                <div class="container py-5">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h1>Booking Details #<?php echo htmlspecialchars($booking['id']); ?></h1>
                                <a href="profile.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Profile
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card booking-card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">Booking Information</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="info-label"><i class="fas fa-bed me-2"></i>Room Type</div>
                                                <div class="info-value"><?php echo htmlspecialchars($booking['room_type']); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="info-label"><i class="fas fa-door-closed me-2"></i>Room Number</div>
                                                <div class="info-value"><?php echo htmlspecialchars($booking['room_number']); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="info-label"><i class="fas fa-clock me-2"></i>Check-in</div>
                                                <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($booking['check_in'])); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="info-label"><i class="fas fa-clock me-2"></i>Check-out</div>
                                                <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($booking['check_out'])); ?></div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <div class="info-label"><i class="fas fa-hourglass me-2"></i>Duration</div>
                                                <div class="info-value"><?php echo htmlspecialchars($booking['duration_hours']); ?> hours</div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="info-label"><i class="fas fa-money-bill-wave me-2"></i>Amount</div>
                                                <div class="info-value">₱<?php echo number_format($booking['total_amount'], 2); ?></div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="info-label"><i class="fas fa-info-circle me-2"></i>Status</div>
                                                <span class="badge bg-<?php 
                                                    echo match($booking['status']) {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'success',
                                                        'checked_in' => 'info',
                                                        'checked_out' => 'secondary',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?> status-badge">
                                                    <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($booking['status'] === 'pending'): ?>
                            <div class="card booking-card mb-4">
                                <div class="card-header bg-warning">
                                    <h4 class="mb-0">Actions</h4>
                                </div>
                                <div class="card-body">
                                    <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary me-2">
                                        <i class="fas fa-edit me-2"></i>Edit Booking
                                    </a>
                                    <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" 
                                       class="btn btn-danger"
                                       onclick="return confirm('Are you sure you want to cancel this booking?')">
                                        <i class="fas fa-times me-2"></i>Cancel Booking
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <div class="card booking-card">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">QR Code</h4>
                                </div>
                                <div class="card-body text-center">
                                    <div class="qr-code mb-3">
                                        <img src="<?php echo $qr_image; ?>" alt="Booking QR Code" class="img-fluid">
                                    </div>
                                    <p class="text-muted mb-0">Show this QR code at check-in</p>
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
