<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/ClientController.php';

// Only Clients Allowed
requireRole(['client']);

$clientCtrl = new ClientController();
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// Fetch Invoices
$invoices = $clientCtrl->getMyInvoices($search, $status);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">My Invoices</h4>
            <p class="text-muted small m-0">View and manage your billing history.</p>
        </div>
        
        <form method="GET" class="d-flex gap-2">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="unpaid" <?= $status == 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                <option value="partial" <?= $status == 'partial' ? 'selected' : '' ?>>Partial</option>
            </select>
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search Invoice #" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i></button>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">Number</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($invoices)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No invoices found matching your criteria.</td></tr>
                    <?php else: foreach($invoices as $inv): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary">#<?= htmlspecialchars($inv['invoice_number']) ?></td>
                        <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                        <td class="text-danger small fw-bold"><?= date('M d, Y', strtotime($inv['due_date'])) ?></td>
                        <td class="fw-bold"><?= $inv['symbol'] . number_format($inv['grand_total'], 2) ?></td>
                        <td><?= $inv['symbol'] . number_format($inv['outstanding_amount'], 2) ?></td>
                        <td>
                            <?php 
                                $badge = match($inv['status']) { 'paid'=>'success', 'unpaid'=>'danger', default=>'warning' };
                            ?>
                            <span class="badge bg-<?= $badge ?>"><?= strtoupper($inv['status']) ?></span>
                        </td>
                        <td class="text-end pe-4">
                            <div class="btn-group">
                                <a href="<?= BASE_URL ?>views/invoices/view.php?id=<?= $inv['invoice_id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View PDF">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <?php if($inv['status'] !== 'paid'): ?>
                                    <a href="<?= BASE_URL ?>views/portal/payments.php?pay_invoice=<?= $inv['invoice_id'] ?>" class="btn btn-sm btn-success fw-bold">
                                        Pay Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>