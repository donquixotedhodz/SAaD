<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$email = trim($_POST['email']);
$phone = trim($_POST['phone']);

try {
    // Get customer with provided email and phone
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone 
        FROM customers 
        WHERE email = ? AND phone = ?
    ");
    $stmt->execute([$email, $phone]);
    $customer = $stmt->fetch();

    // Verify customer exists
    if ($customer) {
        // Set session variables
        $_SESSION['customer'] = $customer;

        // Redirect to profile page
        header('Location: profile.php');
        exit();
    } else {
        throw new Exception('Invalid email or phone number');
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: login.php');
    exit();
}
