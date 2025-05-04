<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $pdo->beginTransaction();

    // Update room types if form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_types'])) {
        foreach ($_POST['room_types'] as $type) {
            $stmt = $pdo->prepare("
                UPDATE room_types 
                SET name = ?, price = ?, duration_hours = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $type['name'],
                $type['price'],
                $type['duration_hours'],
                $type['id']
            ]);
        }
    }

    // Get all room types
    $stmt = $pdo->query("SELECT * FROM room_types ORDER BY name");
    $room_types = $stmt->fetchAll();

    // Get all rooms with their types
    $stmt = $pdo->query("
        SELECT r.*, rt.name as type_name 
        FROM rooms r 
        JOIN room_types rt ON r.room_type_id = rt.id 
        ORDER BY r.room_number
    ");
    $rooms = $stmt->fetchAll();

    // Get active bookings
    $stmt = $pdo->query("
        SELECT b.*, r.room_number, c.first_name, c.last_name 
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN customers c ON b.customer_id = c.id
        WHERE b.status IN ('confirmed', 'checked_in')
        ORDER BY b.check_in DESC
    ");
    $active_bookings = $stmt->fetchAll();

    // Add new room if form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_room'])) {
        $room_number = $_POST['room_number'];
        $type_id = $_POST['type_id'];

        // Check if room number already exists
        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ?");
        $stmt->execute([$room_number]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Room number already exists');
        }

        // Insert new room
        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type_id) VALUES (?, ?)");
        $stmt->execute([$room_number, $type_id]);
    }

    // Update room assignments if form submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['room_assignments'])) {
        foreach ($_POST['room_assignments'] as $assignment) {
            $room_id = $assignment['room_id'];
            $booking_id = $assignment['booking_id'];

            // Check if room is available
            $stmt = $pdo->prepare("
                SELECT b.id 
                FROM bookings b 
                WHERE b.room_id = ? 
                AND b.status IN ('confirmed', 'checked_in')
                AND b.id != ?
            ");
            $stmt->execute([$room_id, $booking_id]);

            if ($stmt->rowCount() > 0) {
                throw new Exception('Room is already assigned to another active booking');
            }

            // Update room assignment
            $stmt = $pdo->prepare("UPDATE bookings SET room_id = ? WHERE id = ?");
            $stmt->execute([$room_id, $booking_id]);
        }
    }

    $pdo->commit();
    $_SESSION['success'] = 'Room updates saved successfully';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Rooms - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .room-form {
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
<body class="bg-light">
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1>Room Management</h1>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo htmlspecialchars($_SESSION['success']); 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo htmlspecialchars($_SESSION['error']); 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Room Types -->
        <div class="card room-form mb-4">
            <div class="card-body">
                <h3 class="mb-4">Room Types</h3>
                <form action="" method="POST">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Duration (hours)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($room_types as $type): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="room_types[<?php echo $type['id']; ?>][id]" 
                                                   value="<?php echo htmlspecialchars($type['id']); ?>">
                                            <input type="text" class="form-control" 
                                                   name="room_types[<?php echo $type['id']; ?>][name]"
                                                   value="<?php echo htmlspecialchars($type['name']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" 
                                                   name="room_types[<?php echo $type['id']; ?>][price]"
                                                   value="<?php echo htmlspecialchars($type['price']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control" 
                                                   name="room_types[<?php echo $type['id']; ?>][duration_hours]"
                                                   value="<?php echo htmlspecialchars($type['duration_hours']); ?>" required>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Room Types
                    </button>
                </form>
            </div>
        </div>

        <!-- Add New Room -->
        <div class="card room-form mb-4">
            <div class="card-body">
                <h3 class="mb-4">Add New Room</h3>
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Room Number</label>
                                <input type="text" class="form-control" name="room_number" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Room Type</label>
                                <select class="form-control" name="type_id" required>
                                    <?php foreach ($room_types as $type): ?>
                                        <option value="<?php echo htmlspecialchars($type['id']); ?>">
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="new_room" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Add Room
                    </button>
                </form>
            </div>
        </div>

        <!-- Room List -->
        <div class="card room-form mb-4">
            <div class="card-body">
                <h3 class="mb-4">Room List</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                    <td><?php echo htmlspecialchars($room['type_name']); ?></td>
                                    <td>
                                        <?php
                                        $is_occupied = false;
                                        foreach ($active_bookings as $booking) {
                                            if ($booking['room_id'] === $room['id']) {
                                                echo '<span class="badge bg-danger">Occupied</span>';
                                                $is_occupied = true;
                                                break;
                                            }
                                        }
                                        if (!$is_occupied) {
                                            echo '<span class="badge bg-success">Available</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Active Bookings -->
        <?php if (!empty($active_bookings)): ?>
            <div class="card room-form">
                <div class="card-body">
                    <h3 class="mb-4">Active Bookings</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Guest</th>
                                    <th>Current Room</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Reassign Room</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_bookings as $booking): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($booking['room_number']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('Y-m-d H:i', strtotime($booking['check_in'])); ?>
                                        </td>
                                        <td>
                                            <?php echo date('Y-m-d H:i', strtotime($booking['check_out'])); ?>
                                        </td>
                                        <td>
                                            <form action="" method="POST" class="d-flex">
                                                <input type="hidden" 
                                                       name="room_assignments[<?php echo $booking['id']; ?>][booking_id]" 
                                                       value="<?php echo $booking['id']; ?>">
                                                <select class="form-control me-2" 
                                                        name="room_assignments[<?php echo $booking['id']; ?>][room_id]">
                                                    <?php foreach ($rooms as $room): ?>
                                                        <option value="<?php echo $room['id']; ?>"
                                                                <?php echo $room['id'] === $booking['room_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($room['room_number']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
