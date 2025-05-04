<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate new password match
        if ($new_password !== $confirm_password) {
            throw new Exception('New passwords do not match');
        }

        // Get admin's current password
        $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($current_password, $admin['password'])) {
            throw new Exception('Current password is incorrect');
        }

        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $_SESSION['admin_id']]);

        $_SESSION['success'] = 'Password changed successfully';
        header("Location: dashboard.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .password-form {
            max-width: 500px;
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
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card password-form">
                    <div class="card-body">
                        <h2 class="text-center mb-4">Change Password</h2>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                    echo htmlspecialchars($_SESSION['error']); 
                                    unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" name="current_password" 
                                           placeholder="Current Password" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" class="form-control" name="new_password" 
                                           placeholder="New Password" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           placeholder="Confirm New Password" required>
                                </div>
                            </div>

                            <div class="btn-toolbar justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Change Password
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
