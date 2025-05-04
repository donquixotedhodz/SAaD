<?php
require_once '../includes/session.php';

// Redirect if already logged in
redirectIfLoggedIn();

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            throw new Exception('Please enter both username and password');
        }

        // Get admin by username
        $stmt = $pdo->prepare("SELECT id, username, password FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            throw new Exception('Invalid username or password');
        }

        // Set session variables
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['last_activity'] = time();

        // Redirect to dashboard
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

// Clear any old session messages
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-form {
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #0d6efd;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-form">
                    <div class="card-body">
                        <img src="../images/logo1.png" alt="Richard's Hotel Logo" class="img-fluid mb-3 d-block mx-auto" style="max-height: 80px;">
                        <h5 class="card-title text-center mb-4">Admin Login</h5>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                echo htmlspecialchars($_SESSION['error']);
                                unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
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