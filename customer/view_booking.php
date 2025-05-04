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
        .booking-card {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .qr-code {
            max-width: 300px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
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
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Room Type:</strong> <?php echo htmlspecialchars($booking['room_type']); ?></p>
                                <p><strong>Room Number:</strong> <?php echo htmlspecialchars($booking['room_number']); ?></p>
                                <p><strong>Check-in:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['check_in'])); ?></p>
                                <p><strong>Check-out:</strong> <?php echo date('M d, Y h:i A', strtotime($booking['check_out'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Duration:</strong> <?php echo htmlspecialchars($booking['duration_hours']); ?> hours</p>
                                <p><strong>Amount:</strong> ₱<?php echo number_format($booking['total_amount'], 2); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php 
                                        echo match($booking['status']) {
                                            'pending' => 'warning',
                                            'confirmed' => 'success',
                                            'checked_in' => 'info',
                                            'checked_out' => 'secondary',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                    </span>
                                </p>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
