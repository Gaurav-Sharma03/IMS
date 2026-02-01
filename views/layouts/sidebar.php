<?php
$role = $_SESSION['role'] ?? 'guest';
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($pageName, $folder = '') {
    global $currentPage;
    $uri = $_SERVER['REQUEST_URI'];
    $active = ($currentPage === $pageName);
    if ($folder && strpos($uri, $folder) === false) $active = false;
    return $active ? 'active' : '';
}
?>
<style>
    :root {
        --primary: #4f46e5;
        --primary-soft: #eef2ff;
        --sidebar-width: 260px;   /* <--- Defined here */
        --topbar-height: 70px;    /* <--- Defined here */
        --bg-body: #f9fafb;
        --text-main: #111827;
        --text-muted: #6b7280;
    }
    .sidebar {
        width: var(--sidebar-width);
        background: #0f172a;
        border-right: 1px solid #e5e7eb;
        height: calc(100vh - var(--topbar-height));
        position: fixed; top: var(--topbar-height); left: 0;
        z-index: 999; overflow-y: auto; transition: 0.3s;
    }
    .nav-group-title {
        font-size: 11px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: 1px;
        padding: 24px 24px 8px;
    }
    .sidebar-link {
        display: flex; align-items: center; gap: 12px;
        padding: 12px 24px; color: #4b5563; text-decoration: none;
        font-size: 14px; font-weight: 500; transition: 0.2s;
        border-right: 3px solid transparent;
    }
    .sidebar-link i { width: 20px; font-size: 16px; opacity: 0.7; }
    .sidebar-link:hover { background: var(--primary-soft); color: var(--primary); }
    .sidebar-link.active {
        background: var(--primary-soft); color: var(--primary);
        border-right: 3px solid var(--primary); font-weight: 600;
    }
    .sidebar-link.active i { opacity: 1; }
    
    /* Superadmin Brand */
    .superadmin-mode .sidebar-link.active {
        background: #f5f3ff; color: #7c3aed; border-right-color: #7c3aed;
    }

    @media (max-width: 992px) {
        .sidebar { transform: translateX(-100%); }
        .sidebar.open { transform: translateX(0); box-shadow: 20px 0 50px rgba(0,0,0,0.1); }
    }
</style>

<aside class="sidebar <?= ($role === 'superadmin') ? 'superadmin-mode' : '' ?>" id="sidebarMain">
    <?php if ($role === 'superadmin'): ?>
        <div class="nav-group-title">Command Center</div>
        <a href="<?= BASE_URL ?>views/dashboard/superadmin.php" class="sidebar-link <?= isActive('superadmin.php') ?>">
            <i class="fa-solid fa-chart-line"></i> System Overview</a>

        <div class="nav-group-title">Management</div>
        <a href="<?= BASE_URL ?>views/superadmin/companies.php" class="sidebar-link <?= isActive('companies.php') ?>">
            <i class="fa-solid fa-building"></i> Companies</a>
        <a href="<?= BASE_URL ?>views/superadmin/subscriptions.php" class="sidebar-link <?= isActive('subscriptions.php') ?>">
            <i class="fa-solid fa-credit-card"></i> Subscriptions</a>
        <a href="<?= BASE_URL ?>views/superadmin/users.php" class="sidebar-link <?= isActive('users.php') ?>">
            <i class="fa-solid fa-user-shield"></i> All Users</a>

        <div class="nav-group-title">System</div>
        <a href="<?= BASE_URL ?>views/support/global-tickets.php" class="sidebar-link <?= isActive('global-tickets.php') ?>">
            <i class="fa-solid fa-headset"></i> Global Support</a>
        <a href="<?= BASE_URL ?>views/superadmin/logs.php" class="sidebar-link <?= isActive('logs.php') ?>">
            <i class="fa-solid fa-database"></i> Audit Logs</a>
        <a href="<?= BASE_URL ?>views/superadmin/settings.php" class="sidebar-link <?= isActive('settings.php') ?>">
            <i class="fa-solid fa-gears"></i> Global Settings</a>
    <?php endif; ?>

   <?php if ($role === 'admin'): ?>
    <div class="nav-group-title">Main</div>
    
    <a href="<?= BASE_URL ?>views/dashboard/admin.php" class="sidebar-link <?= isActive('admin.php') ?>">
        <i class="fa-solid fa-house"></i> Dashboard
    </a>
    
    <a href="<?= BASE_URL ?>views/invoices/manage.php" class="sidebar-link <?= isActive('manage.php', 'invoices') ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i> Invoices
    </a>
    
    <a href="<?= BASE_URL ?>views/reports/report.php" class="sidebar-link <?= isActive('report.php') ?>">
        <i class="fa-solid fa-chart-pie"></i> Reports
    </a>
    
    <a href="<?= BASE_URL ?>views/support/request-plan.php" class="sidebar-link <?= isActive('request-plan.php') ?>">
        <i class="fa-solid fa-layer-group"></i> Subscription Plans
    </a>

    <div class="nav-group-title">Operations</div>
    
    <a href="<?= BASE_URL ?>views/tasks/manage.php" class="sidebar-link <?= isActive('manage.php', 'tasks') ?>">
        <i class="fa-solid fa-check-to-slot"></i> Tasks
    </a>
    
    <a href="<?= BASE_URL ?>views/products/manage.php" class="sidebar-link <?= isActive('manage.php', 'products') ?>">
        <i class="fa-solid fa-box"></i> Products
    </a>

    <div class="nav-group-title">Network</div>
    
    <a href="<?= BASE_URL ?>views/clients/manage.php" class="sidebar-link <?= isActive('manage.php', 'clients') ?>">
        <i class="fa-solid fa-users"></i> Clients
    </a>
    
    <a href="<?= BASE_URL ?>views/staff/manage-staff.php" class="sidebar-link <?= isActive('manage-staff.php') ?>">
        <i class="fa-solid fa-user-group"></i> Staff Members
    </a>
 
    <div class="nav-group-title">Support</div>
    
    <a href="<?= BASE_URL ?>views/support/manage.php" class="sidebar-link <?= isActive('manage.php', 'support') ?>">
        <i class="fa-solid fa-headset"></i> Help Desk
    </a>

    <div class="nav-group-title">Configuration</div>
    
    <a href="<?= BASE_URL ?>views/taxes/manage.php" class="sidebar-link <?= isActive('manage.php', 'taxes') ?>">
        <i class="fa-solid fa-percent"></i> Taxes
    </a>
    
    <a href="<?= BASE_URL ?>views/currencies/manage.php" class="sidebar-link <?= isActive('manage.php', 'currencies') ?>">
        <i class="fa-solid fa-coins"></i> Currencies
    </a>
    
    <a href="<?= BASE_URL ?>views/dashboard/settings.php" class="sidebar-link <?= isActive('settings.php') ?>">
        <i class="fa-solid fa-sliders"></i> Company Settings
    </a>

   
<?php endif; ?>


<?php if ($role === 'staff'): ?>
    <div class="nav-group-title">Workspace</div>
    
    <a href="<?= BASE_URL ?>views/dashboard/staff.php" class="sidebar-link <?= isActive('staff.php') ?>">
        <i class="fa-solid fa-briefcase"></i> My Dashboard
    </a>
    
    <a href="<?= BASE_URL ?>views/tasks/manage.php" class="sidebar-link <?= isActive('manage.php', 'tasks') ?>">
        <i class="fa-solid fa-check-to-slot"></i> My Tasks
    </a>

    <div class="nav-group-title">Operations</div>

    <a href="<?= BASE_URL ?>views/invoices/manage.php" class="sidebar-link <?= isActive('manage.php', 'invoices') ?>">
        <i class="fa-solid fa-file-invoice-dollar"></i> Invoices
    </a>
    
    <a href="<?= BASE_URL ?>views/clients/manage.php" class="sidebar-link <?= isActive('manage.php', 'clients') ?>">
        <i class="fa-solid fa-users"></i> Client List
    </a>

    <div class="nav-group-title">Support</div>
    
    <a href="<?= BASE_URL ?>views/support/manage.php" class="sidebar-link <?= isActive('manage.php', 'support') ?>">
        <i class="fa-solid fa-headset"></i> Ticket Support
    </a>
<?php endif; ?>


<?php if ($role === 'client'): ?>
    <div class="nav-group-title">My Account</div>
    
    <a href="<?= BASE_URL ?>views/dashboard/client.php" class="sidebar-link <?= isActive('client.php') ?>">
        <i class="fa-solid fa-house-user"></i> Overview
    </a>
    
    <a href="<?= BASE_URL ?>views/portal/my-invoices.php" class="sidebar-link <?= isActive('my-invoices.php') ?>">
        <i class="fa-solid fa-file-invoice"></i> My Invoices
    </a>
    
    <a href="<?= BASE_URL ?>views/portal/payments.php" class="sidebar-link <?= isActive('payments.php') ?>">
        <i class="fa-solid fa-credit-card"></i> Payments
    </a>

    <div class="nav-group-title">Help & Profile</div>
    
    <a href="<?= BASE_URL ?>views/portal/support.php" class="sidebar-link <?= isActive('support.php') ?>">
        <i class="fa-solid fa-headset"></i> Support Tickets
    </a>
    
    <a href="<?= BASE_URL ?>views/dashboard/settings.php" class="sidebar-link <?= isActive('settings.php') ?>">
        <i class="fa-solid fa-user-gear"></i> Account Settings
    </a>
<?php endif; ?>

<div class="nav-group-title">Personal</div>
<a href="<?= BASE_URL ?>views/notes/manage.php" class="sidebar-link <?= isActive('manage.php', 'notes') ?>">
    <i class="fa-solid fa-note-sticky"></i> My Notes
</a>
</aside>