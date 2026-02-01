<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';

requireRole(['superadmin']);

$controller = new PaymentController();
$payments = $controller->getSubscriptionPayments();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root { --sa-bg: #0f172a; --sa-panel: #1e293b; --sa-border: #334155; }
    .main-content { margin-left: 250px; padding: 30px; background: var(--sa-bg); min-height: 100vh; color: white; }
    .table-dark-custom { background: var(--sa-panel); border-radius: 12px; overflow: hidden; }
    .table-dark-custom th { background: rgba(0,0,0,0.2); color: #94a3b8; font-size: 0.8rem; text-transform: uppercase; }
    .table-dark-custom td { border-bottom: 1px solid var(--sa-border); color: #e2e8f0; vertical-align: middle; }
</style>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">Subscription Revenue</h2>
        <div class="text-white opacity-75">
            Total Earned: <span class="text-success fw-bold">$<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></span>
        </div>
    </div>

    <div class="table-responsive table-dark-custom">
        <table class="table table-borderless mb-0">
            <thead>
                <tr>
                    <th class="ps-4">Transaction ID</th>
                    <th>Tenant Company</th>
                    <th>Plan</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th class="text-end pe-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($payments)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">No subscription payments found.</td></tr>
                <?php else: foreach($payments as $p): ?>
                    <tr>
                        <td class="ps-4 font-monospace small text-info"><?= htmlspecialchars($p['transaction_id']) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($p['company_name']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($p['plan_name']) ?></span></td>
                        <td class="text-success fw-bold">$<?= number_format($p['amount'], 2) ?></td>
                        <td class="text-white opacity-75 small"><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                        <td class="text-end pe-4">
                            <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> PAID</span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>