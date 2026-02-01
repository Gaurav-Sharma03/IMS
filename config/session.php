<?php
/* =================================================================================
 * SYSTEM SESSION MANAGER
 * =================================================================================
 * Handles secure session initiation, user context setting, role-based access control (RBAC),
 * and centralized security auditing.
 * ================================================================================= */

// Include AuditLogger to ensure we can track actions defined in this file
// Adjust the path below to match your folder structure (e.g., ../helpers/ or ../models/)
require_once __DIR__ . '/../models/AuditLogger.php'; 

/* ---------------------------------------------------------------------------------
   1. SECURE SESSION START
   --------------------------------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    // Security Settings
    ini_set('session.cookie_httponly', 1);  // Prevent XSS stealing cookies
    ini_set('session.use_only_cookies', 1); // Prevent Session Fixation
    ini_set('session.gc_maxlifetime', 3600); // Server-side session life (1 hour)
    
    // Uncomment for Production (HTTPS)
    // ini_set('session.cookie_secure', 1); 

    session_start();
}

/* ---------------------------------------------------------------------------------
   2. SET USER SESSION (Login Handler)
   --------------------------------------------------------------------------------- */
function setUserSession($user) {
    // Regenerate ID to prevent session fixation attacks
    session_regenerate_id(true);

    // A. Basic Identity
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['name'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role']; // e.g., 'superadmin', 'admin', 'staff', 'client'
    $_SESSION['profile_img'] = $user['profile_image'] ?? 'default.png';

    // B. Context Isolation (Multi-Tenancy)
    $_SESSION['company_id'] = $user['company_id'] ?? null;
    
    // Role specific IDs
    if (!empty($user['client_id'])) $_SESSION['client_id'] = $user['client_id'];
    if (!empty($user['staff_id']))  $_SESSION['staff_id']  = $user['staff_id'];

    // C. Session State
    $_SESSION['logged_in']   = true;
    $_SESSION['last_activity'] = time();

    // D. AUDIT LOG: LOGIN
    // Logs: Action Name | Person Profile (Name - Role)
    AuditLogger::log(
        'LOGIN_SUCCESS', 
        "User signed in: {$_SESSION['name']} [Role: {$_SESSION['role']}]"
    );
}

/* ---------------------------------------------------------------------------------
   3. CHECK LOGIN STATUS
   --------------------------------------------------------------------------------- */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/* ---------------------------------------------------------------------------------
   4. ROLE ACCESS CONTROL (Middleware)
   --------------------------------------------------------------------------------- */
function requireRole($allowed_roles) {
    
    // Normalize input to array
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }

    // A. Check if User is Logged In
    if (!isset($_SESSION['user_id'])) {
        // Log attempt if necessary (optional, usually noise)
        header("Location: " . BASE_URL . "login.php");
        exit;
    }

    // B. Check Session Timeout (30 mins = 1800 seconds)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        
        // AUDIT LOG: TIMEOUT
        $userName = $_SESSION['name'] ?? 'Unknown';
        AuditLogger::log(
            'SESSION_TIMEOUT', 
            "Session expired due to inactivity for: {$userName}"
        );

        session_unset();
        session_destroy();
        header("Location: " . BASE_URL . "login.php?timeout=1");
        exit;
    }
    
    // Refresh Activity Timer
    $_SESSION['last_activity'] = time(); 

    // C. Check Role Authorization
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        
        // AUDIT LOG: UNAUTHORIZED ACCESS
        // Capture who tried to access a restricted area
        AuditLogger::log(
            'ACCESS_DENIED', 
            "Unauthorized access attempt by {$_SESSION['name']} ({$_SESSION['role']}). Target required: " . implode(',', $allowed_roles)
        );

        header("Location: " . BASE_URL . "views/errors/unauthorized.php");
        exit;
    }
}

/* ---------------------------------------------------------------------------------
   5. LOGOUT HANDLER
   --------------------------------------------------------------------------------- */
function logout() {
    // Capture user details before destroying session for the log
    if (isset($_SESSION['user_id'])) {
        $name = $_SESSION['name'] ?? 'Unknown';
        $role = $_SESSION['role'] ?? 'Unknown';

        // AUDIT LOG: LOGOUT
        AuditLogger::log(
            'LOGOUT_SUCCESS', 
            "User signed out manually: {$name} [Role: {$role}]"
        );
    }

    // Destroy Session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}
?>