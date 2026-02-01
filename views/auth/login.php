<?php
// =========================================================
//  LOGIN PAGE CONTROLLER
// =========================================================

// 1. Load Configurations
require_once __DIR__ . "/../../config/session.php";     
require_once __DIR__ . "/../../config/database.php"; 
require_once __DIR__ . "/../../controllers/AuthController.php";

// 2. Redirect if already logged in
if (function_exists('isLoggedIn') && isLoggedIn()) {
    $redirect = match($_SESSION['role'] ?? 'user') {
        'superadmin' => '../dashboard/superadmin.php',
        'admin'      => '../dashboard/admin.php',
        'staff'      => '../dashboard/staff.php',
        'client'     => '../dashboard/client.php',
        default      => '../dashboard/setup-company.php'
    };
    header("Location: " . $redirect);
    exit;
}

// 3. Initialize System
$database = new Database();
$conn = $database->connect();
$auth = new AuthController($conn);

$error = "";

// 4. Handle Login Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $response = $auth->login([
        'email'    => $email,
        'password' => $password
    ]);

    if ($response['status'] === true) {
        header("Location: " . $response['redirect']);
        exit;
    } else {
        $error = $response['message']; 
    }
}

// 5. Include Header
include("../layouts/lending-header.php");
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* ... (KEEP YOUR EXISTING CSS EXACTLY AS IS) ... */
    
    /* Full Height Background */
    .auth-wrapper {
        min-height: calc(100vh - 76px);
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--secondary-color);
        padding: 40px 20px;
        margin-top:76px;
        position: relative;
        overflow: hidden;
    }

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

    .auth-card {
        width: 100%;
        max-width: 420px;
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        padding: 40px;
        position: relative;
        z-index: 10;
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .auth-header {
        text-align: center;
        margin-bottom: 35px;
    }
    .auth-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800;
        color: var(--secondary-color);
        font-size: 1.75rem;
        margin-bottom: 0.5rem;
        letter-spacing: -0.5px;
    }
    .auth-subtitle {
        color: var(--text-muted);
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }
    
    .form-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-dark);
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
        color: var(--text-dark);
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

    .form-group:focus-within .form-control-icon {
        color: var(--primary-color);
    }

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

    .forgot-link {
        font-size: 0.85rem;
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 600;
        float: right;
        margin-top: -5px;
    }
    .forgot-link:hover { color: var(--primary-color); }

    .auth-footer {
        text-align: center;
        margin-top: 30px;
        font-size: 0.9rem;
        color: var(--text-muted);
    }
    .auth-footer a {
        color: var(--primary-color);
        font-weight: 700;
        text-decoration: none;
    }
    .auth-footer a:hover { text-decoration: underline; }

    .alert-box {
        background-color: #fee2e2;
        color: #b91c1c;
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 0.9rem;
        margin-bottom: 25px;
        border: 1px solid #fecaca;
        display: flex;
        align-items: center;
        gap: 10px;
    }
</style>

<div class="auth-wrapper">
    <div class="auth-blob"></div>

    <div class="auth-card">
        
        <div class="auth-header">
            <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                <i class="fa-solid fa-right-to-bracket fa-xl"></i>
            </div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Enter your credentials to access your account.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-box">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <i class="fa-solid fa-envelope form-control-icon"></i>
                <input type="email" id="email" name="email" class="form-control-custom" 
                       placeholder="name@company.com" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                       required autofocus>
            </div>

            <div class="form-group">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0" for="password">Password</label>
                    <a href="recover.php" class="forgot-link">Forgot Password?</a>
                </div>
                <i class="fa-solid fa-lock form-control-icon" style="top: 40px;"></i>
                <input type="password" id="password" name="password" class="form-control-custom" 
                       placeholder="Enter your password" 
                       required>
            </div>

            <button type="submit" class="btn-auth">
                Sign In <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>

        </form>

        <div class="auth-footer">
            Don't have an account? 
            <a href="register.php">Start Free Trial</a>
        </div>

    </div>
</div>

<?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Password Updated!',
            text: 'Your password has been successfully reset. Please log in with your new password.',
            icon: 'success',
            confirmButtonColor: '#4f46e5', // Matches your --primary-color
            confirmButtonText: 'Great, let\'s login!',
            width: 400,
            padding: '2em',
            backdrop: `rgba(15, 23, 42, 0.4)` // Matches your theme
        });
    });
</script>
<?php endif; ?>

<?php if(isset($_GET['success']) && $_GET['success'] == 2): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: 'Account Activated!',
            text: 'Your profile is now active. Please log in to continue.',
            icon: 'success',
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Continue to Login'
        });
    });
</script>
<?php endif; ?>

<?php include("../layouts/lending-footer.php"); ?>