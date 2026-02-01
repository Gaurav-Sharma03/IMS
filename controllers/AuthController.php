<?php
// Ensure this path matches your folder structure:
// /invoice-system-root/controllers/AuthController.php
require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/../models/User.php";
require_once __DIR__ . "/../config/session.php";

// FIX: Point to 'models', NOT 'helpers'
require_once __DIR__ . '/../models/AuditLogger.php'; 

class AuthController {
    private $userModel;
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    /* =====================================================
       SECURE LOGIN
    ===================================================== */
    public function login($data) {
        // 1. Sanitize Input
        $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = trim($data['password'] ?? '');

        // 2. Validate Input
        if (empty($email) || empty($password)) {
            return ['status' => false, 'message' => "Email and password are required."];
        }

        // 3. Fetch User
        $user = $this->userModel->findByEmail($email);
        
        if (!$user) {
            // Log failed attempt
            AuditLogger::log('LOGIN_FAILED', "Failed login attempt for email: $email");
            return ['status' => false, 'message' => "Invalid credentials."];
        }

        // 4. Verify Password
        if (!password_verify($password, $user['password'])) {
            AuditLogger::log('LOGIN_FAILED', "Invalid password for user: {$user['name']} ($email)");
            return ['status' => false, 'message' => "Invalid credentials."];
        }

        // 5. Handle Pending Users
        if ($user['status'] === 'pending') {
            $_SESSION['temp_user_id'] = $user['id'];
            $base = defined('BASE_URL') ? BASE_URL : '';
            return [
                'status' => true, 
                'message' => "First time login: Please set a new password.", 
                'redirect' => $base . 'views/auth/set-password.php' 
            ];
        }

        // 6. Check Status
        if ($user['status'] !== 'active') {
            return ['status' => false, 'message' => "Your account is currently {$user['status']}."];
        }

        // 7. Success: Set Session
        session_regenerate_id(true);
        setUserSession($user); // This function is in config/session.php
        
        // Log Success
        AuditLogger::log('LOGIN_SUCCESS', "User logged in: {$user['name']} ({$user['role']})");
        
        // 8. Redirect based on Role
        $redirect = $this->getRedirectByRole($user['role']);

        return [
            'status' => true,
            'message' => "Login successful.",
            'redirect' => $redirect
        ];
    }

    /* =====================================================
       REGISTER (Initial Sign Up as USER)
    ===================================================== */
    public function register($data) {
        // 1. Basic Validation
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return ['status' => false, 'message' => "Please fill in all required fields."];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['status' => false, 'message' => "Invalid email format."];
        }

        if (strlen($data['password']) < 6) {
            return ['status' => false, 'message' => "Password must be at least 6 characters."];
        }

        // 2. Check Duplicates
        if ($this->userModel->emailExists($data['email'])) {
            return ['status' => false, 'message' => "This email is already registered."];
        }

        try {
            // 3. Hash Password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // 4. Prepare Data
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'role' => 'user',     
                'status' => 'active', 
                'company_id' => null  
            ];

            // 5. Register User
            $userId = $this->userModel->register($userData);

            if ($userId) {
                // Log Registration
                AuditLogger::log('REGISTER_NEW_USER', "New account created for: {$data['email']}");
                
                return ['status' => true, 'message' => "Account created! Please login to set up your company."];
            }

        } catch (Exception $e) {
            return ['status' => false, 'message' => "System Error: " . $e->getMessage()];
        }

        return ['status' => false, 'message' => "Registration failed. Please try again."];
    }

    /* =====================================================
       FORGOT PASSWORD
    ===================================================== */
    public function forgotPassword($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        // 1. Check if email exists
        if (!$this->userModel->emailExists($email)) {
            return ['status' => true, 'message' => "If this email exists, a reset link has been sent."];
        }

        // 2. Generate Token
        $token = bin2hex(random_bytes(32));
        
        // 3. Save Token to DB
        if ($this->userModel->saveResetToken($email, $token)) {
            
            $resetLink = BASE_URL . "views/auth/reset-password.php?token=" . $token;
            
            // Log Request
            AuditLogger::log('PASSWORD_RESET_REQ', "Password reset requested for: $email");

            return [
                'status' => true, 
                'message' => "Reset link generated.", 
                'debug_link' => $resetLink 
            ];
        }

        return ['status' => false, 'message' => "System error. Please try again."];
    }

    /* =====================================================
       HELPER: Role Redirects
    ===================================================== */
    private function getRedirectByRole($role) {
        $base = defined('BASE_URL') ? BASE_URL : '';

        switch ($role) {
            case 'superadmin':  
                return $base . 'views/dashboard/superadmin.php';
            case 'admin':       
                return $base . 'views/dashboard/admin.php';
            case 'staff':       
                return $base . 'views/dashboard/staff.php';
            case 'client':      
                return $base . 'views/dashboard/client.php';
            case 'user':        
                return $base . 'views/dashboard/setup-company.php'; 
            default:            
                return $base . 'views/auth/login.php';
        }
    }
}
?>