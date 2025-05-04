<?php
require_once '../config/database.php';

// Get all rooms with their types
$query = "
    SELECT 
        r.id,
        r.room_number,
        rt.name as room_type,
        rt.price,
        r.status,
        (
            SELECT COUNT(*) 
            FROM bookings b 
            WHERE b.room_id = r.id 
            AND b.status IN ('pending', 'confirmed', 'checked_in')
            AND NOW() BETWEEN b.check_in AND b.check_out
        ) as is_occupied
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    ORDER BY r.room_number
";

$result = $conn->query($query);

echo "<h2>Current Room Status</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Room ID</th><th>Room Number</th><th>Type</th><th>Price</th><th>Status</th><th>Currently Occupied</th></tr>";

while ($room = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $room['id'] . "</td>";
    echo "<td>" . htmlspecialchars($room['room_number']) . "</td>";
    echo "<td>" . htmlspecialchars($room['room_type']) . "</td>";
    echo "<td>₱" . number_format($room['price'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($room['status']) . "</td>";
    echo "<td>" . ($room['is_occupied'] ? 'Yes' : 'No') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Show room type summary
$query = "
    SELECT 
        rt.name as room_type,
        COUNT(r.id) as total_rooms,
        SUM(
            CASE WHEN (
                SELECT COUNT(*) 
                FROM bookings b 
                WHERE b.room_id = r.id 
                AND b.status IN ('pending', 'confirmed', 'checked_in')
                AND NOW() BETWEEN b.check_in AND b.check_out
            ) > 0 THEN 1 ELSE 0 END
        ) as occupied_rooms
    FROM room_types rt
    LEFT JOIN rooms r ON rt.id = r.room_type_id
    GROUP BY rt.id, rt.name
";

$result = $conn->query($query);

echo "<h2>Room Type Summary</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Room Type</th><th>Total Rooms</th><th>Occupied</th><th>Available</th></tr>";

while ($type = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($type['room_type']) . "</td>";
    echo "<td>" . $type['total_rooms'] . "</td>";
    echo "<td>" . $type['occupied_rooms'] . "</td>";
    echo "<td>" . ($type['total_rooms'] - $type['occupied_rooms']) . "</td>";
    echo "</tr>";
}

echo "</table>";
?>
