<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hotel_ms';

// Create PDO connection
try {
    // First try to connect to the database
    try {
        $dsn = "mysql:host=$host;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        // If database doesn't exist, create it
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database`");
        $pdo->exec("USE `$database`");
    }

    // Create admin table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS admin (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Check if admin table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM admin")->fetchColumn();

    if ($count == 0) {
        // Insert default admin account
        $default_username = 'admin';
        $default_password = password_hash('admin123', PASSWORD_DEFAULT);
        $default_email = 'admin@hotel.com';
        
        $stmt = $pdo->prepare("INSERT INTO admin (username, password, email) VALUES (?, ?, ?)");
        $stmt->execute([$default_username, $default_password, $default_email]);
    }

    // Create room types table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS room_types (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL,
        duration_hours INT NOT NULL,
        capacity INT NOT NULL DEFAULT 2,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Check if room types table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM room_types")->fetchColumn();

    if ($count == 0) {
        // Insert room types
        $room_types = [
            [
                'name' => 'Standard Room (3 Hours)',
                'description' => 'Comfortable room with essential amenities perfect for short stays.',
                'price' => 500,
                'duration_hours' => 3,
                'capacity' => 2
            ],
            [
                'name' => 'Standard Room (12 Hours)',
                'description' => 'Well-appointed room ideal for overnight stays.',
                'price' => 1200,
                'duration_hours' => 12,
                'capacity' => 2
            ],
            [
                'name' => 'Standard Room (1 Day)',
                'description' => 'Spacious room with all amenities for a full day stay.',
                'price' => 1000,
                'duration_hours' => 24,
                'capacity' => 2
            ],
            [
                'name' => 'Family Room',
                'description' => 'Large room perfect for families, featuring multiple beds and extra space.',
                'price' => 2000,
                'duration_hours' => 24,
                'capacity' => 4
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO room_types (name, description, price, duration_hours, capacity) VALUES (?, ?, ?, ?, ?)");
        foreach ($room_types as $room) {
            $stmt->execute([
                $room['name'],
                $room['description'],
                $room['price'],
                $room['duration_hours'],
                $room['capacity']
            ]);
        }
    }

    // Create rooms table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS rooms (
        id INT PRIMARY KEY AUTO_INCREMENT,
        room_type_id INT NOT NULL,
        room_number VARCHAR(10) NOT NULL UNIQUE,
        status ENUM('available', 'occupied', 'maintenance') NOT NULL DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_type_id) REFERENCES room_types(id)
    )";
    $pdo->exec($sql);

    // Check if rooms table is empty
    $count = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

    if ($count == 0) {
        // Get room types
        $room_types = $pdo->query("SELECT id FROM room_types")->fetchAll();
        
        // Insert sample rooms for each room type
        $stmt = $pdo->prepare("INSERT INTO rooms (room_type_id, room_number, status) VALUES (?, ?, 'available')");
        
        foreach ($room_types as $room_type) {
            // Create 5 rooms for each room type
            for ($i = 1; $i <= 5; $i++) {
                $room_number = sprintf("%03d", ($room_type['id'] * 100) + $i); // Creates room numbers like 101, 102, 201, 202, etc.
                $stmt->execute([$room_type['id'], $room_number]);
            }
        }
    }

    // Create bookings table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        customer_id INT NOT NULL,
        room_id INT NOT NULL,
        check_in DATETIME NOT NULL,
        check_out DATETIME NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (room_id) REFERENCES rooms(id)
    )";
    $pdo->exec($sql);

    // Create customers table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS customers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Create payments table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        booking_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('cash', 'card', 'online', 'pending') NOT NULL,
        payment_status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
        payment_date TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
    )";
    $pdo->exec($sql);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
