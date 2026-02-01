<?php
// =========================================================
//  PASSWORD RECOVERY CONTROLLER
// =========================================================

// 1. Load Configurations (Absolute Paths using __DIR__)
// Go UP 2 levels from 'views/auth' to reach 'root'
require_once __DIR__ . "/../../config/session.php";     
require_once __DIR__ . "/../../config/database.php"; 
require_once __DIR__ . "/../../controllers/AuthController.php";

// 2. Initialize System
$database = new Database();
$conn = $database->connect();
$auth = new AuthController($conn);

$message = "";
$messageType = ""; // 'success' or 'error'

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $response = $auth->forgotPassword($email);

    if ($response['status'] === true) {
        $messageType = "success";
        $message = $response['message'];
        
        // FOR LOCALHOST TESTING ONLY: Show the link directly
        if(isset($response['debug_link'])) {
            $message .= "<br><br><strong>Dev Mode Link:</strong> <a href='{$response['debug_link']}'>Click here to reset</a>";
        }
    } else {
        $messageType = "error";
        $message = $response['message'];
    }
}

// 4. Include Header (Go UP 1 level from 'auth' to 'views', then into 'layouts')
include __DIR__ . "/../layouts/lending-header.php";
?>

<style>
    /* --- RECOVER PASSWORD SPECIFIC STYLES --- */
    
    /* Full Height Background (Matches Login Page) */
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

    /* Background Decoration */
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
        background-color: rgba(79, 70, 229, 0.1); /* Primary color light */
        color: var(--primary-color);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 25px;
    }

    /* Typography */
    .auth-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800;
        color: var(--secondary-color);
        font-size: 1.75rem;
        margin-bottom: 10px;
        letter-spacing: -0.5px;
    }

    .auth-subtitle {
        color: #64748b;
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 30px;
    }

    /* Form Fields */
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
        top: 42px; /* Adjusted based on label height */
        color: #94a3b8;
        font-size: 1.1rem;
        transition: color 0.3s;
    }

    .form-control-custom {
        width: 100%;
        padding: 14px 16px 14px 48px; /* Space for icon */
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

    /* Submit Button */
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
    }
    .btn-auth:hover {
        background-color: #4338ca;
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
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

    /* Alerts */
    .alert-box {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 0.9rem;
        margin-bottom: 25px;
        text-align: left;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        line-height: 1.5;
    }
    .alert-error { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .alert-success { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
</style>

<div class="auth-wrapper">
    <div class="auth-blob"></div>

    <div class="auth-card">
        
        <div class="auth-icon-circle">
            <i class="fa-solid fa-key"></i>
        </div>

        <h2 class="auth-title">Forgot Password?</h2>
        <p class="auth-subtitle">No worries! Enter your email address and we will send you secure reset instructions.</p>

        <?php if (!empty($message)): ?>
            <div class="alert-box alert-<?= $messageType ?>">
                <i class="fa-solid <?= $messageType == 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> mt-1"></i>
                <div><?= $message; ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <i class="fa-solid fa-envelope form-control-icon"></i>
                <input type="email" id="email" name="email" class="form-control-custom" 
                       placeholder="Enter your email" required autofocus>
            </div>

            <button type="submit" class="btn-auth">
                Send Reset Link
            </button>

        </form>

        <a href="login.php" class="auth-back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Log In
        </a>

    </div>
</div>

<?php 
// Include Footer (Go UP 1 level from 'auth' to 'views', then into 'layouts')
include __DIR__ . "/../layouts/lending-footer.php"; 
?>