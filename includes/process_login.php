<?php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/login.php');
    exit();
}

try {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Get admin by username
    $stmt = $pdo->prepare("SELECT id, password FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        throw new Exception('Invalid username or password');
    }

    // Set session variables
    $_SESSION['admin_id'] = $admin['id'];

    // Redirect to dashboard
    header('Location: ../admin/dashboard.php');
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../admin/login.php');
    exit();
}
