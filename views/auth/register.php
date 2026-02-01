<?php
// =========================================================
//  REGISTRATION PAGE CONTROLLER
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
$success = "";

// 4. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Basic Password Match Check
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Prepare Data
        $data = [
            'name'     => trim($_POST['name']),
            'email'    => trim($_POST['email']),
            'phone'    => trim($_POST['phone']),
            'address'  => trim($_POST['address']),
            'password' => $password
        ];

        // Call Register Method
        $response = $auth->register($data);

        if ($response['status'] === true) {
            $success = $response['message'];
            // Redirect logic is handled via JS/Link in Success Alert below
        } else {
            $error = $response['message'];
        }
    }
}

// 5. Include Header (Ensures CSS variables are loaded)
include __DIR__ . "/../layouts/lending-header.php";
?>

<style>
    /* --- REGISTER PAGE SPECIFIC STYLES --- */
    
    /* Full Height Background */
    .auth-wrapper {
        min-height: calc(100vh - 76px);
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--secondary-color); /* Deep Navy */
        padding: 60px 20px;
        margin-top:76px;
        position: relative;
        overflow: hidden;
    }

    /* Ambient Glow */
    .auth-blob {
        position: absolute;
        width: 800px;
        height: 800px;
        background: radial-gradient(circle, rgba(79, 70, 229, 0.12) 0%, rgba(0,0,0,0) 70%);
        border-radius: 50%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
    }

    /* Card Design - Wider for Register Form */
    .auth-card {
        width: 100%;
        max-width: 550px;
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

    /* Header Section */
    .auth-header {
        text-align: center;
        margin-bottom: 35px;
    }
    
    .auth-icon-circle {
        width: 60px;
        height: 60px;
        background-color: rgba(79, 70, 229, 0.1);
        color: var(--primary-color);
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 20px;
    }

    .auth-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800;
        color: var(--secondary-color);
        font-size: 1.75rem;
        margin-bottom: 5px;
        letter-spacing: -0.5px;
    }

    .auth-subtitle {
        color: var(--text-muted);
        font-size: 0.95rem;
    }

    /* Form Grid Layout */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    @media (max-width: 576px) {
        .form-grid { grid-template-columns: 1fr; gap: 0; }
    }

    /* Form Fields */
    .form-group {
        margin-bottom: 1.25rem;
        position: relative;
    }
    
    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--secondary-color);
        margin-bottom: 0.4rem;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .form-control-icon {
        position: absolute;
        left: 16px;
        top: 40px; /* Adjusted for label height */
        color: #94a3b8;
        font-size: 1rem;
        transition: color 0.3s;
    }

    .form-control-custom {
        width: 100%;
        padding: 12px 16px 12px 42px; /* Space for icon */
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
        margin-top: 10px;
    }

    .btn-auth:hover {
        background-color: #4338ca;
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.4);
    }

    /* Footer Link */
    .auth-footer {
        text-align: center;
        margin-top: 25px;
        font-size: 0.9rem;
        color: var(--text-muted);
        border-top: 1px solid #f1f5f9;
        padding-top: 20px;
    }
    
    .auth-link {
        color: var(--primary-color);
        font-weight: 700;
        text-decoration: none;
    }
    .auth-link:hover { text-decoration: underline; }

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
    }
    .alert-error { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .alert-success { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
</style>

<div class="auth-wrapper">
    <div class="auth-blob"></div>

    <div class="auth-card">
        
        <div class="auth-header">
            <div class="auth-icon-circle">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join thousands of businesses managing their finances with IMS.</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert-box alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <span>
                    <?= htmlspecialchars($success); ?> 
                    <a href="login.php" style="color:inherit; font-weight:800; margin-left:5px; text-decoration:underline;">Login Now</a>
                </span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-box alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            
            <div class="form-grid">
                
                <div class="form-group full-width">
                    <label class="form-label">Full Name</label>
                    <i class="fa-solid fa-user form-control-icon"></i>
                    <input type="text" name="name" class="form-control-custom" 
                           placeholder="Enter your full name" 
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <i class="fa-solid fa-envelope form-control-icon"></i>
                    <input type="email" name="email" class="form-control-custom" 
                           placeholder="name@company.com" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <i class="fa-solid fa-phone form-control-icon"></i>
                    <input type="text" name="phone" class="form-control-custom" 
                           placeholder="+1 (555) 000-0000"
                           value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                </div>

                <div class="form-group full-width">
                    <label class="form-label">Business Address</label>
                    <i class="fa-solid fa-location-dot form-control-icon"></i>
                    <input type="text" name="address" class="form-control-custom" 
                           placeholder="123 Business St, City, Country"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <i class="fa-solid fa-lock form-control-icon"></i>
                    <input type="password" name="password" class="form-control-custom" 
                           placeholder="Create password" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <i class="fa-solid fa-check-double form-control-icon"></i>
                    <input type="password" name="confirm_password" class="form-control-custom" 
                           placeholder="Confirm password" required>
                </div>

            </div>

            <button type="submit" class="btn-auth">
                Create Account <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>

        </form>

        <div class="auth-footer">
            Already have an account? 
            <a href="login.php" class="auth-link">Log In</a>
        </div>

    </div>
</div>

<?php 
// Include Footer
include __DIR__ . "/../layouts/lending-footer.php"; 
?>