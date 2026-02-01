<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ClientController.php';

// Initialize Controller & Get Data
$clientCtrl = new ClientController();
$data = $clientCtrl->getDashboardData(); 

$stats = $data['stats'];
$recentInvoices = $data['invoices'];
$notifications = $data['notifications'];

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root {
        --body-bg: #f3f4f6;
        --card-bg: #ffffff;
        --text-primary: #111827;
        --text-secondary: #6b7280;
        --accent-blue: #2563eb;
        --accent-green: #10b981;
        --accent-red: #ef4444;
    }

    body { background-color: var(--body-bg); font-family: 'Inter', sans-serif; }

    /* --- Main Layout --- */
    .main-content {
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }
    @media (max-width: 991px) {
        .main-content { margin-left: 0; padding: 20px; }
    }

    /* --- Welcome Banner --- */
    .welcome-card {
        background: #223b2c;
       
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 30px;
        border: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    .welcome-text h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0; }
    .welcome-text p { color: var(--text-secondary); margin: 5px 0 0; }
    
    .btn-action {
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-primary-soft { background: #eff6ff; color: var(--accent-blue); border: none; }
    .btn-primary-soft:hover { background: #dbeafe; color: #1d4ed8; transform: translateY(-2px); }

    /* --- Stats Cards --- */
    .stat-card {
        background: var(--card-bg);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        height: 100%;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05); }
    
    .stat-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; margin-bottom: 16px;
    }
    .icon-red { background: #fef2f2; color: var(--accent-red); }
    .icon-green { background: #ecfdf5; color: var(--accent-green); }

    .stat-label { font-size: 0.85rem; font-weight: 600; text-transform: uppercase; color: var(--text-secondary); letter-spacing: 0.5px; }
    .stat-value { font-size: 2rem; font-weight: 800; color: var(--text-primary); line-height: 1.2; margin-top: 5px; }

    /* --- Content Grid --- */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    @media (max-width: 991px) { .dashboard-grid { grid-template-columns: 1fr; } }

    /* --- Tables --- */
    .content-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        overflow: hidden;
        height: 100%;
        display: flex; flex-direction: column;
    }
    .card-header-custom {
        padding: 20px 24px;
        border-bottom: 1px solid #f3f4f6;
        display: flex; justify-content: space-between; align-items: center;
    }
    .card-title { font-weight: 700; font-size: 1.1rem; color: var(--text-primary); margin: 0; }

    .table-custom th { 
        background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; 
        color: var(--text-secondary); padding: 16px 24px; border-bottom: 1px solid #e5e7eb; font-weight: 700;
    }
    .table-custom td { padding: 16px 24px; vertical-align: middle; color: #374151; font-size: 0.95rem; border-bottom: 1px solid #f3f4f6; }
    .table-custom tr:last-child td { border-bottom: none; }
    
    /* --- Notifications --- */
    .notif-container { padding: 0; overflow-y: auto; max-height: 400px; }
    .notif-item {
        padding: 16px 24px;
        border-bottom: 1px solid #f3f4f6;
        display: flex; gap: 15px; align-items: start;
        transition: background 0.2s;
    }
    .notif-item:hover { background: #f9fafb; }
    .notif-item:last-child { border-bottom: none; }
    
    .notif-icon {
        flex-shrink: 0; width: 10px; height: 10px;
        border-radius: 50%; margin-top: 6px;
    }
    .notif-unread { background-color: var(--accent-red); box-shadow: 0 0 0 4px #fef2f2; }
    .notif-read { background-color: #d1d5db; }
    
    /* --- Badges --- */
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    .bg-paid { background: #ecfdf5; color: #065f46; }
    .bg-unpaid { background: #fef2f2; color: #991b1b; }
</style>

<main class="main-content">

    <div class="welcome-card">
        <div class="welcome-text">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Valued Client') ?></h2>
            <p>Access your billing history and manage your services.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>views/portal/support.php" class="btn-action btn-light border">
                <i class="fa-solid fa-headset text-secondary"></i> Support
            </a>
            <a href="<?= BASE_URL ?>views/portal/my-invoices.php?status=unpaid" class="btn-action btn-primary-soft">
                <i class="fa-solid fa-credit-card"></i> Make Payment
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Outstanding Balance</div>
                        <div class="stat-value text-danger">$<?= number_format($stats['outstanding'], 2) ?></div>
                        <p class="text-muted small mt-2 mb-0">Due immediately</p>
                    </div>
                    <div class="stat-icon icon-red">
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-label">Total Paid (Lifetime)</div>
                        <div class="stat-value text-success">$<?= number_format($stats['paid'], 2) ?></div>
                        <p class="text-muted small mt-2 mb-0">Thank you for your business</p>
                    </div>
                    <div class="stat-icon icon-green">
                        <i class="fa-solid fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <div class="content-card">
            <div class="card-header-custom">
                <h5 class="card-title">Recent Invoices</h5>
                <a href="<?= BASE_URL ?>views/portal/my-invoices.php" class="text-decoration-none small fw-bold text-primary">View All</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recentInvoices)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No invoices found.</td></tr>
                        <?php else: foreach($recentInvoices as $inv): ?>
                        <tr>
                            <td class="fw-bold text-primary">#<?= htmlspecialchars($inv['invoice_number']) ?></td>
                            <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                            <td class="fw-bold"><?= $inv['symbol'] . number_format($inv['grand_total'], 2) ?></td>
                            <td>
                                <?php $badgeClass = match($inv['status']) { 'paid'=>'bg-paid', 'unpaid'=>'bg-unpaid', default=>'bg-light text-dark' }; ?>
                                <span class="status-badge <?= $badgeClass ?>"><?= strtoupper($inv['status']) ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>views/invoices/view.php?id=<?= $inv['invoice_id'] ?>" target="_blank" class="btn btn-sm btn-light border">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header-custom">
                <h5 class="card-title">Notifications</h5>
                <span class="badge bg-danger rounded-pill"><?= count($notifications) ?></span>
            </div>
            <div class="notif-container">
                <?php if(empty($notifications)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa-regular fa-bell-slash fa-2x mb-2 opacity-25"></i>
                        <p class="m-0 small">No new alerts.</p>
                    </div>
                <?php else: foreach($notifications as $n): ?>
                    <div class="notif-item">
                        <div class="notif-icon <?= $n['status'] === 'unread' ? 'notif-unread' : 'notif-read' ?>"></div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small text-dark d-block"><?= htmlspecialchars($n['title']) ?></strong>
                                <small class="text-muted ms-2" style="font-size: 0.7rem;"><?= date('M d', strtotime($n['created_at'])) ?></small>
                            </div>
                            <p class="m-0 text-secondary small lh-sm"><?= htmlspecialchars($n['message']) ?></p>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="p-3 bg-light text-center border-top">
                <a href="<?= BASE_URL ?>views/portal/notifications.php" class="text-decoration-none small fw-bold text-muted">View History</a>
            </div>
        </div>

    </div>

</main>
<?php include __DIR__ . '/../layouts/footer.php'; ?>