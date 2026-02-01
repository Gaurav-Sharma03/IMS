<?php
if (!defined('BASE_URL')) require_once __DIR__ . '/../../config/constants.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Fetching user details - using $userName for the display
$userName    = $_SESSION['name'] ?? 'Guest User';
$userEmail   = $_SESSION['email'] ?? 'Not Signed In';
$userRole    = $_SESSION['role'] ?? 'guest';
$userAvatar  = $_SESSION['avatar'] ?? null;

$currentPage = basename($_SERVER['PHP_SELF'], ".php");
$pageTitle   = ($currentPage == "Index") ? "Dashboard" : ucwords(str_replace("-", " ", $currentPage));

require_once __DIR__ . '/../../controllers/NotificationController.php';
$headerNotifCtrl = new NotificationController();
$headerNotifs    = $headerNotifCtrl->getHeaderNotifications(5); 
$headerNotifCount = $headerNotifCtrl->getUnreadCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> | <?= APP_NAME ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-color: #6366f1; /* Indigo */
            --header-bg: #0f172a;   /* Midnight Navy */
            --surface-card: #ffffff;
            --text-on-header: #f8fafc;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; }

        /* Professional Header Styling */
        .topbar {
            height: 70px;
            background: var(--header-bg);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; position: sticky; top: 0; z-index: 1000;
            color: var(--text-on-header);
        }

        /* Brand Logo Area */
        .brand-area {
            display: flex; align-items: center; gap: 12px;
            padding-right: 20px; border-right: 1px solid rgba(255,255,255,0.1);
            margin-right: 10px;
        }
        .brand-logo-box {
            width: 35px; height: 35px; background: linear-gradient(135deg, #818cf8 0%, #6366f1 100%);
            border-radius: 8px; display: flex; align-items: center; justify-content: center;
            font-weight: 800; color: white; font-size: 18px; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }
        .brand-name { font-weight: 700; font-size: 1.2rem; letter-spacing: -0.5px; color: #fff; margin: 0; }

        .icon-btn {
            width: 40px; height: 40px; border-radius: 10px; border: none;
            background: rgba(255,255,255,0.05); color: #94a3b8;
            transition: all 0.3s ease; position: relative;
        }
        .icon-btn:hover { background: rgba(255,255,255,0.15); color: #fff; transform: translateY(-1px); }

        .badge-count {
            position: absolute; top: -4px; right: -4px; width: 20px; height: 20px;
            background: #f43f5e; color: white; border: 2px solid var(--header-bg);
            font-size: 10px; border-radius: 50%; display: grid; place-items: center; font-weight: 700;
        }

        /* Profile Trigger Refinement */
        .profile-trigger {
            display: flex; align-items: center; gap: 10px; padding: 5px 12px;
            border-radius: 12px; transition: all 0.3s ease; cursor: pointer;
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);
        }
        .profile-trigger:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); }

        .profile-img-box {
            width: 38px; height: 38px; border-radius: 8px; 
            background: linear-gradient(to bottom right, #4f46e5, #3b82f6);
            color: white; display: flex; align-items: center; justify-content: center;
            font-weight: 700; border: 2px solid rgba(255,255,255,0.2);
        }

        /* Dropdown Customization */
        .custom-dropdown {
            position: absolute; top: calc(100% + 12px); right: 0; width: 300px;
            background: white; border-radius: 16px; border: 1px solid #e2e8f0;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);
            display: none; overflow: hidden; color: #1e293b;
        }
        .custom-dropdown.show { display: block; animation: slideIn 0.25s cubic-bezier(0.165, 0.84, 0.44, 1); }
        
        @keyframes slideIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        .dropdown-link {
            padding: 10px 16px; display: flex; align-items: center; gap: 12px;
            color: #475569; text-decoration: none; font-size: 14px; transition: 0.2s;
        }
        .dropdown-link:hover { background: #f8fafc; color: var(--brand-color); }
        .dropdown-link i { font-size: 16px; opacity: 0.6; width: 20px; text-align: center; }
    </style>
</head>
<body>

<header class="topbar">
    <div class="d-flex align-items-center">
        <div class="brand-area">
            <div class="brand-logo-box">I</div>
            <h1 class="brand-name">Invoice Management System (IMS)</h1>
        </div>

        <button class="icon-btn ms-2" id="sidebarToggle">
            <i class="fa-solid fa-bars-staggered"></i>
        </button>
        <h6 class="mb-0 fw-semibold text-white-50 ms-3 d-none d-md-block"><?= $pageTitle ?></h6>
    </div>

    <div class="d-flex align-items-center gap-3">
        

        <div class="position-relative">
            <button class="icon-btn" onclick="toggleDropdown('notifMenu')">
                <i class="fa-regular fa-bell"></i>
                <?php if($headerNotifCount > 0): ?>
                    <span class="badge-count"><?= $headerNotifCount ?></span>
                <?php endif; ?>
            </button>
            <div class="custom-dropdown" id="notifMenu">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                    <span class="fw-bold">Notifications</span>
                    <span class="badge bg-primary rounded-pill small"><?= $headerNotifCount ?> New</span>
                </div>
                <div class="overflow-auto" style="max-height: 350px;">
                    <?php if(empty($headerNotifs)): ?>
                        <div class="p-5 text-center text-muted small">
                            <i class="fa-regular fa-face-smile d-block mb-2 fs-3 opacity-25"></i>
                            Everything is up to date!
                        </div>
                    <?php else: foreach($headerNotifs as $n): ?>
                        <a href="<?= $n['link'] ?: '#' ?>" class="p-3 d-flex gap-3 text-decoration-none border-bottom hover-bg-light transition-all">
                            <div class="mt-1"><i class="fa-solid fa-circle-check text-success"></i></div>
                            <div>
                                <div class="text-dark fw-semibold small"><?= htmlspecialchars($n['title']) ?></div>
                                <div class="text-muted smaller text-truncate" style="max-width: 200px;"><?= htmlspecialchars($n['message']) ?></div>
                                <div class="smaller opacity-50 mt-1"><?= date('M d, H:i', strtotime($n['created_at'])) ?></div>
                            </div>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
                <div class="p-2 text-center border-top">
                    <a href="<?= BASE_URL ?>views/notification/notifications.php" class="text-primary fw-bold smaller text-decoration-none">See all alerts</a>
                </div>
            </div>
        </div>

        <div class="position-relative">
            <div class="profile-trigger" onclick="toggleDropdown('userMenu')">
                <?php if($userAvatar && file_exists(__DIR__ . '/../../assets/uploads/avatars/' . $userAvatar)): ?>
                    <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $userAvatar ?>" class="profile-img-box" alt="User">
                <?php else: ?>
                    <div class="profile-img-box"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                <?php endif; ?>
                
                <div class="d-none d-lg-block text-start">
                    <div class="small fw-bold text-white lh-1"><?= htmlspecialchars($userName) ?></div>
                    <div class="smaller opacity-50 text-capitalize" style="font-size: 11px;"><?= htmlspecialchars($userRole) ?></div>
                </div>
                <i class="fa-solid fa-chevron-down opacity-50" style="font-size: 10px;"></i>
            </div>

            <div class="custom-dropdown" id="userMenu">
                <div class="p-4 text-center bg-light border-bottom">
                    <div class="mb-2 d-inline-block">
                        <div class="profile-img-box mx-auto" style="width: 50px; height: 50px; font-size: 20px;">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                    </div>
                    <h6 class="mb-0 fw-bold"><?= htmlspecialchars($userName) ?></h6>
                    <p class="smaller text-muted mb-0"><?= htmlspecialchars($userEmail) ?></p>
                </div>
                <div class="p-2">
                    <a href="<?= BASE_URL ?>views/profile/edit.php" class="dropdown-link rounded-3">
                        <i class="fa-regular fa-id-badge"></i> My Profile
                    </a>
                    <a href="<?= BASE_URL ?>views/dashboard/settings.php" class="dropdown-link rounded-3">
                        <i class="fa-solid fa-sliders"></i> Preferences
                    </a>
                </div>
                <div class="p-2 border-top bg-light-subtle">
                    <a href="<?= BASE_URL ?>logout.php" class="dropdown-link text-danger fw-bold rounded-3">
                        <i class="fa-solid fa-power-off"></i> Secure Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>