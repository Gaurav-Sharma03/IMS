<?php
// Ensure role is set
$role = $_SESSION['role'] ?? 'guest';

// Helper to get current page name
$currentPage = basename($_SERVER['PHP_SELF']);

// Helper function to set active class
function isActive($pageName) {
    global $currentPage;
    if ($currentPage === $pageName) {
        return 'active';
    }
    return '';
}
?>

<style>
    /* Sidebar Styling */
    .sidebar {
        width: 250px;
        background: #ffffff;
        border-right: 1px solid #e5e7eb;
        height: calc(100vh - 70px);
        position: fixed;
        top: 70px;
        left: 0;
        overflow-y: auto;
        transition: transform 0.3s ease;
        z-index: 900;
        padding-top: 20px;
        padding-bottom: 40px;
    }

    /* Scrollbar */
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-track { background: #f1f1f1; }
    .sidebar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

    /* Mobile */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.show { transform: translateX(0); }
    }

    .nav-category {
        font-size: 0.75rem;
        text-transform: uppercase;
        color: #9ca3af;
        letter-spacing: 0.05em;
        font-weight: 700;
        padding: 16px 25px 8px;
        margin-top: 5px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 25px;
        color: #4b5563;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        border-left: 3px solid transparent;
        transition: all 0.2s ease-in-out;
    }

    .nav-link:hover { background: #f9fafb; color: #1f2937; }
    
    .nav-link.active {
        background: #eff6ff;
        color: #4f46e5;
        border-left-color: #4f46e5;
        font-weight: 600;
    }

    /* Superadmin specific active state color */
    .superadmin-mode .nav-link.active {
        background: #f5f3ff; /* Light Purple */
        color: #7c3aed;
        border-left-color: #7c3aed;
    }

    .nav-link i { width: 20px; text-align: center; font-size: 1.1em; opacity: 0.8; }
    .nav-link.active i { opacity: 1; }
</style>

<aside class="sidebar <?= ($role === 'superadmin') ? 'superadmin-mode' : '' ?>" id="sidebar">

    <?php if ($role === 'superadmin'): ?>
        <div class="nav-category text-primary">Command Center</div>
        
        <a href="<?= BASE_URL ?>views/dashboard/superadmin.php" class="nav-link <?= isActive('superadmin.php') ?>">
            <i class="fa-solid fa-server"></i> System Overview
        </a>

        <div class="nav-category">Tenant Management</div>
        
        <a href="<?= BASE_URL ?>views/superadmin/companies.php" class="nav-link <?= isActive('companies.php') ?>">
            <i class="fa-solid fa-building-user"></i> Companies
        </a>
        
        <a href="<?= BASE_URL ?>views/superadmin/subscriptions.php" class="nav-link <?= isActive('subscriptions.php') ?>">
            <i class="fa-solid fa-file-contract"></i> Subscriptions
        </a>

        <div class="nav-category">Global Oversight</div>

        <a href="<?= BASE_URL ?>views/superadmin/users.php" class="nav-link <?= isActive('users.php') ?>">
            <i class="fa-solid fa-users-gear"></i> All Users
        </a>

        <a href="<?= BASE_URL ?>views/support/global-tickets.php" class="nav-link <?= isActive('global-tickets.php') ?>">
            <i class="fa-solid fa-life-ring"></i> Global Support
        </a>

        <div class="nav-category">System Health</div>

        <a href="<?= BASE_URL ?>views/superadmin/logs.php" class="nav-link <?= isActive('logs.php') ?>">
            <i class="fa-solid fa-terminal"></i> Audit Logs
        </a>

        <a href="<?= BASE_URL ?>views/superadmin/settings.php" class="nav-link <?= isActive('settings.php') ?>">
            <i class="fa-solid fa-sliders"></i> Global Settings
        </a>
        
        <a href="<?= BASE_URL ?>views/notes/manage.php" class="nav-link <?= isActive('manage.php') ?>">
            <i class="fa-solid fa-note-sticky"></i> Private Notes
        </a>
    <?php endif; ?>


    <?php if ($role === 'admin'): ?>
        <div class="nav-category">Overview</div>
        
        <a href="<?= BASE_URL ?>views/dashboard/admin.php" class="nav-link <?= isActive('admin.php') ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
        </a>
        
        <a href="<?= BASE_URL ?>views/invoices/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'invoices') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i> Invoices
        </a>
        
        <a href="<?= BASE_URL ?>views/reports/report.php" class="nav-link <?= isActive('report.php') ?>">
            <i class="fa-solid fa-chart-line"></i> Reports
        </a>
        
        <a href="<?= BASE_URL ?>views/support/request-plan.php" class="nav-link <?= isActive('request-plan.php') ?>">
            <i class="fa-solid fa-life-ring"></i> Subscription Planes
        </a>


        <div class="nav-category">Operations</div>

        <a href="<?= BASE_URL ?>views/tasks/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'tasks') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i> Tasks
        </a>

        <a href="<?= BASE_URL ?>views/products/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'products') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-box-open"></i> Products
        </a>

        <div class="nav-category">People</div>
        
        <a href="<?= BASE_URL ?>views/clients/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'clients') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Clients
        </a>
        
        <a href="<?= BASE_URL ?>views/staff/manage-staff.php" class="nav-link <?= isActive('manage-staff.php') ?>">
            <i class="fa-solid fa-id-card"></i> Staff
        </a>

        <div class="nav-category">Configuration</div>

        <a href="<?= BASE_URL ?>views/taxes/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'taxes') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-percent"></i> Taxes
        </a>

        <a href="<?= BASE_URL ?>views/currencies/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'currencies') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-money-bill-1-wave"></i> Currencies
        </a>

        <a href="<?= BASE_URL ?>views/dashboard/settings.php" class="nav-link <?= isActive('settings.php') ?>">
            <i class="fa-solid fa-gear"></i> Company Settings
        </a>
        
        <a href="<?= BASE_URL ?>views/support/manage.php" class="nav-link <?= isActive('manage.php') ?>">
            <i class="fa-solid fa-life-ring"></i> Ticket Support
        </a>


        <a href="<?= BASE_URL ?>views/notes/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'notes') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-note-sticky"></i> My Notes
        </a>
    <?php endif; ?>


    <?php if ($role === 'staff'): ?>
        <div class="nav-category">Workspace</div>
        
        <a href="<?= BASE_URL ?>views/dashboard/staff.php" class="nav-link <?= isActive('staff.php') ?>">
            <i class="fa-solid fa-briefcase"></i> My Dashboard
        </a>
        
        <a href="<?= BASE_URL ?>views/tasks/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'tasks') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-list-check"></i> My Tasks
        </a>

        <a href="<?= BASE_URL ?>views/support/manage.php" class="nav-link <?= isActive('manage.php') ?>">
            <i class="fa-solid fa-life-ring"></i> Ticket Support
        </a>
        
        <a href="<?= BASE_URL ?>views/notes/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'notes') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-note-sticky"></i> My Notes
        </a>

        <div class="nav-category">Operations</div>

        <a href="<?= BASE_URL ?>views/invoices/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'invoices') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-file-invoice-dollar"></i> Invoices
        </a>

        <a href="<?= BASE_URL ?>views/clients/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'clients') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Client List
        </a>
    <?php endif; ?>


    <?php if ($role === 'client'): ?>
        <div class="nav-category">My Account</div>
        
        <a href="<?= BASE_URL ?>views/dashboard/client.php" class="nav-link <?= isActive('client.php') ?>">
            <i class="fa-solid fa-house-user"></i> Overview
        </a>
        
        <a href="<?= BASE_URL ?>views/portal/my-invoices.php" class="nav-link <?= isActive('my-invoices.php') ?>">
            <i class="fa-solid fa-file-invoice"></i> My Invoices
        </a>
        
        <a href="<?= BASE_URL ?>views/portal/payments.php" class="nav-link <?= isActive('payments.php') ?>">
            <i class="fa-solid fa-credit-card"></i> Payments
        </a>
        
        <a href="<?= BASE_URL ?>views/portal/support.php" class="nav-link <?= isActive('support.php') ?>">
            <i class="fa-solid fa-headset"></i> Support Tickets
        </a>

        <a href="<?= BASE_URL ?>views/notes/manage.php" class="nav-link <?= isActive('manage.php') && strpos($_SERVER['REQUEST_URI'], 'notes') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-note-sticky"></i> My Notes
        </a>

        <div class="nav-category">Profile</div>
        
        <a href="<?= BASE_URL ?>views/dashboard/settings.php" class="nav-link <?= isActive('settings.php') ?>">
            <i class="fa-solid fa-user-gear"></i> Account Settings
        </a>
    <?php endif; ?>

</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.querySelector('.navbar-toggler'); 
        const sidebar = document.getElementById('sidebar');
        
        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
        }
    });
</script>