<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

try {
    // Get room types
    $stmt = $pdo->prepare("SELECT * FROM room_types ORDER BY price ASC");
    $stmt->execute();
    $room_types = $stmt->fetchAll();

    // Get available rooms
    $stmt = $pdo->prepare("
        SELECT r.*, rt.name as room_type_name, rt.price
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE r.status = 'available'
        ORDER BY rt.price ASC, r.room_number ASC
    ");
    $stmt->execute();
    $available_rooms = $stmt->fetchAll();

    // Group rooms by type
    $rooms_by_type = [];
    foreach ($available_rooms as $room) {
        if (!isset($rooms_by_type[$room['room_type_id']])) {
            $rooms_by_type[$room['room_type_id']] = [];
        }
        $rooms_by_type[$room['room_type_id']][] = $room;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: error.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Room - Hotel Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .room-card {
            transition: transform 0.2s;
        }
        .room-card:hover {
            transform: translateY(-5px);
        }
        .room-image {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Richard's Hotel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">My Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="book.php">Book a Room</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <!-- Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="card-title mb-4">Book a Room</h3>
                
                <form action="../includes/process_booking.php" method="POST" class="needs-validation" novalidate>
                    <!-- Date Selection -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Check-in</h5>
                                    <div class="mb-3">
                                        <label for="check_in_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="check_in_date" name="check_in_date" 
                                               min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="check_in_time" class="form-label">Time</label>
                                        <input type="time" class="form-control" id="check_in_time" name="check_in_time" 
                                               value="14:00" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Check-out</h5>
                                    <div class="mb-3">
                                        <label for="check_out_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="check_out_date" name="check_out_date" 
                                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="check_out_time" class="form-label">Time</label>
                                        <input type="time" class="form-control" id="check_out_time" name="check_out_time" 
                                               value="12:00" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Room Selection -->
                    <h5 class="mb-3">Select a Room</h5>
                    <div class="row g-4">
                        <?php foreach ($room_types as $type): ?>
                            <?php if (isset($rooms_by_type[$type['id']])): ?>
                                <?php foreach ($rooms_by_type[$type['id']] as $room): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 room-card">
                                            <img src="../images/rooms/<?php echo $type['image'] ?? 'default.jpg'; ?>" 
                                                 class="card-img-top room-image" 
                                                 alt="<?php echo htmlspecialchars($type['name']); ?>">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           name="room_id" 
                                                           id="room_<?php echo $room['id']; ?>" 
                                                           value="<?php echo $room['id']; ?>" required>
                                                    <label class="form-check-label" for="room_<?php echo $room['id']; ?>">
                                                        <h5 class="card-title mb-1">
                                                            Room #<?php echo htmlspecialchars($room['room_number']); ?>
                                                        </h5>
                                                        <h6 class="card-subtitle text-muted mb-2">
                                                            <?php echo htmlspecialchars($type['name']); ?>
                                                        </h6>
                                                    </label>
                                                </div>
                                                <p class="card-text">
                                                    <strong class="text-primary">₱<?php echo number_format($type['price'], 2); ?></strong> per night
                                                </p>
                                                <input type="hidden" name="room_type" value="<?php echo $type['id']; ?>">
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <small class="text-muted">
                                                    <i class="fas fa-bed"></i> <?php echo htmlspecialchars($type['capacity']); ?> persons
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-check"></i> Request Booking
                        </button>
                        <a href="profile.php" class="btn btn-link">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()

        // Date validation
        document.getElementById('check_in_date').addEventListener('change', function() {
            var checkIn = new Date(this.value);
            var checkOut = document.getElementById('check_out_date');
            checkOut.min = new Date(checkIn.getTime() + (24 * 60 * 60 * 1000)).toISOString().split('T')[0];
            if (checkOut.value && new Date(checkOut.value) <= checkIn) {
                checkOut.value = checkOut.min;
            }
        });
    </script>
</body>
</html>
