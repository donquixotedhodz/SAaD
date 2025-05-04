<?php
session_start();
require_once '../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header("Location: login.php");
    exit();
}

// Check if booking ID is provided
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
            r.room_type_id,
            rt.name as room_type,
            rt.price,
            rt.duration_hours
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE b.id = ? AND b.customer_id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$booking_id, $customer['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        $_SESSION['error'] = "Booking not found or cannot be edited.";
        header("Location: profile.php");
        exit();
    }

    // Get room types
    $stmt = $pdo->query("
        SELECT id, name, price, duration_hours 
        FROM room_types 
        ORDER BY price ASC
    ");
    $room_types = $stmt->fetchAll();

} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving booking details.";
    header("Location: profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .btn-toolbar {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Edit Booking #<?php echo htmlspecialchars($booking['id']); ?></h1>
                    <a href="profile.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Profile
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="card booking-form">
            <div class="card-body">
                <form action="process_edit_booking.php" method="POST">
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="room_type">Room Type</label>
                                <select class="form-control" id="room_type" name="room_type" required>
                                    <?php foreach ($room_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['id']); ?>"
                                                data-duration="<?php echo htmlspecialchars($type['duration_hours']); ?>"
                                                data-price="<?php echo htmlspecialchars($type['price']); ?>"
                                                <?php echo $type['id'] === $booking['room_type_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?> 
                                            (₱<?php echo number_format($type['price'], 2); ?> - 
                                            <?php echo $type['duration_hours']; ?> hours)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="check_in_date">Check-in Date</label>
                                <input type="date" class="form-control" id="check_in_date" name="check_in_date"
                                       value="<?php echo date('Y-m-d', strtotime($booking['check_in'])); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="check_in_time">Check-in Time</label>
                                <input type="time" class="form-control" id="check_in_time" name="check_in_time"
                                       value="<?php echo date('H:i', strtotime($booking['check_in'])); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="check_out_date">Check-out Date</label>
                                <input type="date" class="form-control" id="check_out_date" name="check_out_date"
                                       value="<?php echo date('Y-m-d', strtotime($booking['check_out'])); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="check_out_time">Check-out Time</label>
                                <input type="time" class="form-control" id="check_out_time" name="check_out_time"
                                       value="<?php echo date('H:i', strtotime($booking['check_out'])); ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="btn-toolbar">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="profile.php" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('room_type').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const duration = selectedOption.dataset.duration;
            
            // Update check-out time based on check-in time and duration
            document.getElementById('check_in_date').addEventListener('change', updateCheckOut);
            document.getElementById('check_in_time').addEventListener('change', updateCheckOut);
            
            function updateCheckOut() {
                const checkInDate = document.getElementById('check_in_date').value;
                const checkInTime = document.getElementById('check_in_time').value;
                
                if (checkInDate && checkInTime) {
                    const checkIn = new Date(checkInDate + 'T' + checkInTime);
                    const checkOut = new Date(checkIn.getTime() + duration * 60 * 60 * 1000);
                    
                    document.getElementById('check_out_date').value = checkOut.toISOString().split('T')[0];
                    document.getElementById('check_out_time').value = checkOut.toTimeString().slice(0, 5);
                }
            }
            
            updateCheckOut();
        });
    </script>
</body>
</html>
