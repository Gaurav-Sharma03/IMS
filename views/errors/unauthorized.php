<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';

// Determine dashboard link based on role
$dashboardLink = BASE_URL . 'views/dashboard.php'; // Default
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'superadmin') $dashboardLink = BASE_URL . 'views/dashboard/superadmin.php';
    elseif ($_SESSION['role'] === 'admin') $dashboardLink = BASE_URL . 'views/dashboard/admin.php';
    elseif ($_SESSION['role'] === 'staff') $dashboardLink = BASE_URL . 'views/dashboard/staff.php';
    elseif ($_SESSION['role'] === 'client') $dashboardLink = BASE_URL . 'views/dashboard/client.php';
 
} else {
    $dashboardLink = BASE_URL . 'index.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Unauthorized</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #0f172a;       /* Deep Slate */
            --card-bg: #1e293b;        /* Lighter Slate */
            --text-white: #ffffff;
            --text-muted: #94a3b8;
            --accent-red: #ef4444;     /* Red for Error */
            --accent-glow: rgba(239, 68, 68, 0.2);
            --border-color: #334155;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-color);
            color: var(--text-white);
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .error-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 60px 40px;
            border-radius: 24px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 10;
        }

        .icon-wrapper {
            width: 100px;
            height: 100px;
            background: var(--accent-glow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            border: 2px solid rgba(239, 68, 68, 0.3);
            animation: pulse 2s infinite;
        }

        .error-icon {
            font-size: 3rem;
            color: var(--accent-red);
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0 0 10px;
            letter-spacing: -1px;
        }

        p {
            color: var(--text-muted);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .btn-home {
            display: inline-block;
            background-color: var(--text-white);
            color: var(--bg-color);
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 12px;
            font-weight: 700;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
        }

        /* Background Effects */
        .bg-pattern {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: radial-gradient(#334155 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.1;
            z-index: 1;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 var(--accent-glow); }
            70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body>

    <div class="bg-pattern"></div>

    <div class="error-card">
        <div class="icon-wrapper">
            <i class="fa-solid fa-shield-halved error-icon"></i>
        </div>
        
        <h1>Access Denied</h1>
        <p>
            You do not have the necessary permissions to view this page. 
            If you believe this is a mistake, please contact your administrator.
        </p>

        <a href="<?= $dashboardLink ?>" class="btn-home">
            <i class="fa-solid fa-arrow-left-long" style="margin-right: 8px;"></i> Return to Dashboard
        </a>
    </div>

</body>
</html>