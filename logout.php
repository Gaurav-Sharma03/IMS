<?php
require_once __DIR__ . '/models/AuditLogger.php';
// 1. Initialize the session
session_start();

// 2. Load constants to get BASE_URL for redirection
require_once __DIR__ . '/config/constants.php';

// 3. Unset all session variables
$_SESSION = array();

// 4. Destroy the session cookie (Important for security)
// This ensures the session ID cannot be reused
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
AuditLogger::log('LOGOUT_PROFILE', 'User Logout');
// 5. Destroy the session
session_destroy();

// 6. Redirect to Login Page
header("Location: " . BASE_URL . "views/auth/login.php");
exit;
?>