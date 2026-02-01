<?php
// =========================================================
//  RESET PASSWORD CONTROLLER
// =========================================================

// 1. Load Configurations (Absolute Paths using __DIR__)
// Go UP 2 levels from 'views/auth' to reach 'root'
require_once __DIR__ . "/../../config/session.php";     
require_once __DIR__ . "/../../config/database.php"; 
require_once __DIR__ . "/../../models/User.php"; // Need User model to verify token

// 2. Initialize System
$database = new Database();
$conn = $database->connect();
$userModel = new User($conn);

$error = "";
$success = "";
$showForm = true;

// 3. Get and Verify Token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = "Invalid request. No token provided.";
    $showForm = false;
} else {
    // Check if token exists and hasn't expired
    $user = $userModel->findByResetToken($token);
    
    if (!$user) {
        $error = "This password reset link is invalid or has expired.";
        $showForm = false;
    }
}

// 4. Handle Password Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $showForm) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $token    = $_POST['token'] ?? ''; // hidden input

    if (empty($password) || empty($confirm)) {
        $error = "Please fill in both password fields.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Validation Passed - Update Password
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        if ($userModel->updatePassword($user['id'], $hashed)) {
            // Success! Redirect to login with message
            header("Location: login.php?success=1"); 
            exit;
        } else {
            $error = "System error. Please try again.";
        }
    }
}

// 5. Include Header (Go UP 1 level from 'auth' to 'views', then into 'layouts')
include __DIR__ . "/../layouts/lending-header.php";
?>

<style>
    /* --- RESET PASSWORD SPECIFIC STYLES --- */
    
    /* Full Height Background */
    .auth-wrapper {
        min-height: calc(100vh - 76px);
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--secondary-color); /* Deep Navy */
        padding: 40px 20px;
        margin-top:76px;
        position: relative;
        overflow: hidden;
    }

    /* Ambient Glow */
    .auth-blob {
        position: absolute;
        width: 600px;
        height: 600px;
        background: radial-gradient(circle, rgba(79, 70, 229, 0.15) 0%, rgba(0,0,0,0) 70%);
        border-radius: 50%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
    }

    /* Card Design */
    .auth-card {
        width: 100%;
        max-width: 420px;
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 40px;
        position: relative;
        z-index: 10;
        text-align: center;
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Icon Circle */
    .auth-icon-circle {
        width: 70px;
        height: 70px;
        background-color: rgba(79, 70, 229, 0.1);
        color: var(--primary-color);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 25px;
    }
    
    /* Error State Icon */
    .auth-icon-circle.error {
        background-color: #fee2e2;
        color: #ef4444;
    }

    /* Typography */
    .auth-title {
        font-weight: 800;
        color: var(--secondary-color);
        font-size: 1.75rem;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }

    .auth-subtitle {
        color: var(--text-muted);
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    /* Form Inputs */
    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
        text-align: left;
    }

    .form-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control-icon {
        position: absolute;
        left: 16px;
        top: 42px;
        color: #94a3b8;
        font-size: 1.1rem;
        transition: color 0.3s;
    }

    .form-control-custom {
        width: 100%;
        padding: 14px 16px 14px 48px;
        font-size: 0.95rem;
        color: var(--secondary-color);
        background-color: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .form-control-custom:focus {
        background-color: #fff;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    .form-control-custom:focus + .form-control-icon,
    .form-group:focus-within .form-control-icon {
        color: var(--primary-color);
    }

    /* Button */
    .btn-auth {
        width: 100%;
        background: var(--primary-color);
        color: white;
        padding: 14px;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        text-decoration: none;
        display: inline-block;
    }

    .btn-auth:hover {
        background-color: #4338ca;
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
        color: white;
    }

    /* Alerts */
    .alert-box {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 0.9rem;
        margin-bottom: 25px;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 12px;
        background-color: #fee2e2; 
        color: #b91c1c; 
        border: 1px solid #fecaca;
    }

    /* Back Link */
    .auth-back-link {
        display: inline-flex;
        align-items: center;
        margin-top: 30px;
        color: #64748b;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        transition: color 0.2s;
    }
    .auth-back-link i { margin-right: 8px; font-size: 0.8rem; transition: transform 0.2s; }
    .auth-back-link:hover { color: var(--primary-color); }
    .auth-back-link:hover i { transform: translateX(-3px); }

</style>

<div class="auth-wrapper">
    <div class="auth-blob"></div>

    <div class="auth-card">
        
        <?php if ($showForm): ?>
            <div class="auth-icon-circle">
                <i class="fa-solid fa-lock-open"></i>
            </div>

            <h2 class="auth-title">Reset Password</h2>
            <p class="auth-subtitle">Create a new, strong password to secure your account.</p>

            <?php if (!empty($error)): ?>
                <div class="alert-box">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?= htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <i class="fa-solid fa-key form-control-icon"></i>
                    <input type="password" name="password" class="form-control-custom" 
                           placeholder="Enter new password" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <i class="fa-solid fa-check-double form-control-icon"></i>
                    <input type="password" name="confirm_password" class="form-control-custom" 
                           placeholder="Confirm new password" required>
                </div>

                <button type="submit" class="btn-auth">
                    Update Password <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>
            </form>

        <?php else: ?>
            <div class="auth-icon-circle error">
                <i class="fa-solid fa-link-slash"></i>
            </div>
            
            <h2 class="auth-title">Link Expired</h2>
            <p class="auth-subtitle">
                <?= htmlspecialchars($error) ?><br>
                For security reasons, password reset links expire. Please request a new one.
            </p>

            <a href="recover.php" class="btn-auth">
                Request New Link
            </a>
        <?php endif; ?>

        <a href="login.php" class="auth-back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Log In
        </a>

    </div>
</div>

<?php 
// Include Footer (Go UP 1 level from 'auth' to 'views', then into 'layouts')
include __DIR__ . "/../layouts/lending-footer.php"; 
?>