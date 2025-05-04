<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../customer/register.php');
    exit();
}

$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);

try {
    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? AND phone = ?");
    $stmt->execute([$email, $phone]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['customer'] = $stmt->fetch();
        header('Location: ../customer/profile.php');
        exit();
    }

    // Insert new customer
    $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone) VALUES (?, ?, ?, ?)");
    $stmt->execute([$first_name, $last_name, $email, $phone]);
    
    $_SESSION['customer'] = [
        'id' => $pdo->lastInsertId(),
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'phone' => $phone
    ];

    header('Location: ../customer/profile.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../customer/register.php');
    exit();
}
