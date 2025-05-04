<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function checkAdminSession() {
    if (!isLoggedIn()) {
        header("Location: /richardshotelMS/admin/login.php");
        exit();
    }

    // Check session timeout (30 minutes)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        session_unset();
        session_destroy();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header("Location: /richardshotelMS/admin/login.php");
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        header("Location: /richardshotelMS/admin/dashboard.php");
        exit();
    }
}
?>
