<?php
// 1. Load Dependencies
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';

// 2. Security: User must be in "Pending" state (indicated by temp_user_id session)
// If they try to access this page directly without logging in, kick them out.
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();
$userModel = new User($conn);

$error = "";

// 3. Handle Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Hash and Update
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $userId = $_SESSION['temp_user_id'];

        // 'updatePassword' method automatically sets status = 'active'
        if ($userModel->updatePassword($userId, $hashed)) {
            // Success: Clean up and Redirect to Login
            unset($_SESSION['temp_user_id']);
            header("Location: login.php?success=2"); // Code 2 = "Account Activated"
            exit;
        } else {
            $error = "System error. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Account</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #0f172a;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--secondary-color);
            margin: 0;
            padding: 0;
        }

        /* Full Height Background */
        .auth-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            background-color: #dcfce7; /* Green tint for Activation */
            color: #16a34a;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 25px;
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

        /* Buttons */
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
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="auth-blob"></div>

    <div class="auth-card">
        
        <div class="auth-icon-circle">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <h3 class="auth-title">Setup Account</h3>
        <p class="auth-subtitle">
            Welcome aboard! Please set a secure password to activate your staff profile.
        </p>

        <?php if (!empty($error)): ?>
            <div class="alert-box">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            
            <div class="form-group">
                <label class="form-label">New Password</label>
                <i class="fa-solid fa-lock form-control-icon"></i>
                <input type="password" name="password" class="form-control-custom" 
                       placeholder="••••••••" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <i class="fa-solid fa-check-double form-control-icon"></i>
                <input type="password" name="confirm_password" class="form-control-custom" 
                       placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-auth">
                Activate Account <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
            
        </form>
    </div>
</div>

</body>
</html>