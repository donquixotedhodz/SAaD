<?php
session_start();
require_once '../config/database.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate password match
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match');
        }

        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Username already exists');
        }

        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            throw new Exception('Email already exists');
        }

        // Hash password and insert new admin
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashed_password]);

        $_SESSION['success'] = "Registration successful! You can now login.";
        header("Location: login.php");
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
    <title>Admin Registration - Hotel Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .register-form {
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
        .hotel-logo {
            max-width: 150px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card register-form">
                    <div class="card-body text-center">
                        <img src="../assets/images/logo.png" alt="Hotel Logo" class="hotel-logo">
                        <h2 class="mb-4">Admin Registration</h2>

                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                    echo htmlspecialchars($_SESSION['error']); 
                                    unset($_SESSION['error']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user"></i>
                                            </span>
                                            <input type="text" class="form-control" name="username" placeholder="Username" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control" name="email" placeholder="Email" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" name="password" placeholder="Password" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="btn-toolbar justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i>Register
                                </button>
                                <a href="login.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Back to Login
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