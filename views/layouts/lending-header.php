<?php
// 1. Session Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Load Constants (Adjust path: goes up 2 levels from views/layouts/ to config/)
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/constants.php';
}

// 3. Get Current Page Name for Active Menu Highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('APP_NAME') ? APP_NAME : 'IMS' ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4f46e5; /* Electric Indigo */
            --secondary-color: #0f172a; /* Deep Navy */
            --accent-color: #818cf8; /* Soft Indigo */
            --bg-light: #f8fafc;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* --- NAVBAR STYLING --- */
        .navbar {
            padding: 1rem 0;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--secondary-color);
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            font-weight: 600;
            color: var(--text-muted);
            font-size: 0.95rem;
            margin: 0 0.8rem;
            transition: color 0.2s;
            position: relative;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        /* Active Link Indicator */
        .nav-link.active {
            color: var(--primary-color);
        }
       

        /* Buttons */
        .btn-login {
            font-weight: 700;
            color: var(--secondary-color);
            padding: 0.6rem 1.5rem;
            transition: color 0.2s;
        }
        .btn-login:hover { color: var(--primary-color); }

        .btn-signup {
            background-color: var(--primary-color);
            color: white;
            font-weight: 700;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
            border: none;
            transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
        }

        .btn-signup:hover {
            background-color: #4338ca;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
        }

        /* Responsive Navbar Fix */
        @media (max-width: 991px) {
            .navbar-collapse {
                background: white;
                padding: 1.5rem;
                border-radius: 16px;
                box-shadow: 0 15px 30px -10px rgba(0,0,0,0.1);
                margin-top: 15px;
                border: 1px solid rgba(0,0,0,0.05);
            }
            .d-flex.justify-content-lg-start { justify-content: center !important; }
            .nav-link.active::after { bottom: 0; left: 0; transform: none; top: 50%; width: 4px; height: 100%; border-radius: 0 4px 4px 0; background: var(--primary-color); opacity: 0.2; }
        }
        
        /* Blobs for backgrounds */
        .blob {
            position: absolute;
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, #c7d2fe 0%, #a5b4fc 100%);
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.5;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?= BASE_URL ?>index.php">
                <i class="fa-solid fa-layer-group text-primary"></i>
                Invoice Management System <span class="text-primary">(IMS)</span>
            </a>
            
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fa-solid fa-bars-staggered fa-lg"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'features.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>features.php">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'pricing.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>pricing.php">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currentPage == 'contact.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>contact.php">Contact</a>
                    </li>
                   
                </ul>
                
                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                        <?php 
                            // Determine Dashboard Link Dynamically based on Role
                            $role = $_SESSION['role'] ?? 'user';
                            // Example: views/dashboard/superadmin.php
                            $dashLink = BASE_URL . 'views/dashboard/' . ($role === 'user' ? 'setup-company.php' : $role . '.php');
                        ?>
                        <a href="<?= $dashLink ?>" class="btn btn-signup text-decoration-none">
                            <i class="fa-solid fa-gauge-high me-2"></i> Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>views/auth/login.php" class="btn btn-login text-decoration-none">Log In</a>
                        <a href="<?= BASE_URL ?>views/auth/register.php" class="btn btn-signup text-decoration-none">
                            Get Started <i class="fa-solid fa-arrow-right ms-2"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>