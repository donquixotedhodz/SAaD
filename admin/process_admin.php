<?php
require_once '../includes/session.php';
require_once '../config/database.php';

// Check if user is logged in as admin
checkAdminSession();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'change_password':
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id']]);
            $admin = $stmt->fetch();
            
            if (!password_verify($current_password, $admin['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Verify new passwords match
            if ($new_password !== $confirm_password) {
                throw new Exception('New passwords do not match');
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Password updated successfully',
                'title' => 'Success!',
                'icon' => 'success'
            ]);
            break;
            
        case 'create_admin':
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate passwords match
            if ($password !== $confirm_password) {
                throw new Exception('Passwords do not match');
            }
            
            // Check if username already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Username already exists');
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Email already exists');
            }
            
            // Create new admin
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admin (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$username, $email, $hashed_password]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Admin account created successfully',
                'title' => 'Success!',
                'icon' => 'success'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'title' => 'Error!',
        'icon' => 'error'
    ]);
}