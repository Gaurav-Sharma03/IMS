<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/InvoiceController.php';

// 1. Init Controller
$controller = new InvoiceController();
$controller->handleRequest();

// 2. Pagination & Search Logic
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Default to page 1
$limit = 10; // Records per page

// Fetch data (Ensure your controller returns 'total_records' and 'total_pages' now)
$data = $controller->index($search, $page, $limit);

// Handle response structure
if (isset($data['invoices'])) {
    $invoices = $data['invoices'];
    $totalRecords = $data['total_records'] ?? count($invoices);
    $totalPages = $data['total_pages'] ?? 1;
} else {
    // Fallback if controller just returns array
    $invoices = $data;
    $totalRecords = count($invoices);
    $totalPages = 1;
}

// Calculate Display Range (e.g. "Showing 1-10 of 50")
$startRecord = ($totalRecords > 0) ? ($page - 1) * $limit + 1 : 0;
$endRecord = min($startRecord + $limit - 1, $totalRecords);

// 3. Session Check for New Invoice Popup
$newInvoice = null;
if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['new_invoice'])) {
    $newInvoice = $_SESSION['new_invoice'];
    unset($_SESSION['new_invoice']); 
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<?php if (isset($_GET['success'])): ?>
    <script>
        const status = '<?= htmlspecialchars($_GET['success']) ?>';
        let title = 'Success!';
        let msg = 'Operation completed.';
        let icon = 'success';

        if(status === 'sent_both') {
            title = 'Sent Successfully!';
            msg = 'Invoice sent to Client Dashboard AND Email.';
        } else if(status === 'sent_dashboard') {
            title = 'Sent to Dashboard';
            msg = 'Notification sent to Client Dashboard. (Email delivery skipped/failed).';
            icon = 'info'; 
        } else if(status === 'failed') {
            title = 'Sending Failed';
            msg = 'Could not send notification or email.';
            icon = 'error';
        } else if(status === 'payment_updated') {
            title = 'Payment Recorded';
            msg = 'Invoice balance updated successfully.';
        }

        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: icon, 
                title: title, 
                text: msg, 
                timer: 4000, 
                showConfirmButton: true,
                confirmButtonColor: '#334155'
            });
        });
    </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    
    .table-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
    
    /* Badges */
    .status-badge { padding: 5px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .status-paid { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .status-partial { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
    .status-unpaid { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .status-draft { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    /* Buttons */
    .btn-icon { border: 1px solid transparent; background: transparent; color: #64748b; transition: 0.2s; padding: 6px; border-radius: 6px; }
    .btn-icon:hover { background: #f1f5f9; color: #0f172a; }
    .btn-icon.text-danger:hover { background: #fee2e2; color: #991b1b; }
    .btn-disabled { opacity: 0.4; cursor: not-allowed !important; color: #cbd5e1 !important; }

    /* Pagination Customization */
    .pagination .page-link { color: #64748b; border: 1px solid #e2e8f0; margin: 0 3px; border-radius: 6px; font-size: 0.9rem; }
    .pagination .page-link:hover { background: #f1f5f9; color: #0f172a; }
    .pagination .active .page-link { background-color: #0f172a; border-color: #0f172a; color: white; }
    .pagination .disabled .page-link { background-color: #f8fafc; color: #cbd5e1; border-color: #f1f5f9; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">Invoice Manager</h4>
            <p class="text-muted small m-0">Track payments and manage client billing</p>
        </div>
        <a href="create.php" class="btn btn-primary fw-bold px-4 shadow-sm">
            <i class="fa-solid fa-plus me-2"></i> Create Invoice
        </a>
    </div>

    <?php if ($newInvoice): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Invoice Created!',
                html: `<div class="text-center"><p class="fs-5 text-secondary">Invoice <strong><?= htmlspecialchars($newInvoice['number']) ?></strong> has been generated successfully.</p></div>`,
                confirmButtonText: 'OK',
                confirmButtonColor: '#10b981',
                allowOutsideClick: false
            });
        });
    </script>
    <?php endif; ?>

    <div class="table-card overflow-hidden">
        <div class="p-3 border-bottom bg-white d-flex justify-content-between align-items-center flex-wrap gap-3">
            <form method="GET" class="position-relative" style="width: 300px;">
                <i class="fa-solid fa-search position-absolute text-muted" style="top:50%; left:12px; transform:translateY(-50%); font-size:0.85rem;"></i>
                <input type="text" name="search" class="form-control ps-5" placeholder="Search client, number..." value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="page" value="1">
            </form>
            <div class="text-muted small fw-bold">
                Showing <?= $startRecord ?>-<?= $endRecord ?> of <?= $totalRecords ?> records
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light text-uppercase text-muted small">
                    <tr>
                        <th class="ps-4">Invoice #</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th class="text-end pe-4" width="180px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($invoices)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No invoices found.</td></tr>
                    <?php else: foreach($invoices as $inv): ?>
                    <?php $isPaid = ($inv['status'] === 'paid'); ?>
                    <tr>
                        <td class="ps-4 fw-bold text-primary">
                            <a href="view.php?id=<?= $inv['invoice_id'] ?>" target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars($inv['invoice_number']) ?>
                            </a>
                        </td>
                        <td><div class="fw-bold text-dark"><?= htmlspecialchars($inv['client_name']) ?></div></td>
                        <td>
                            <div><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></div>
                            <div class="small text-danger" style="font-size: 0.75rem;">Due: <?= date('M d', strtotime($inv['due_date'])) ?></div>
                        </td>
                        <td class="fw-bold"><?= $inv['symbol'] ?> <?= number_format($inv['grand_total'], 2) ?></td>
                        <td>
                            <?php if($inv['outstanding_amount'] > 0): ?>
                                <span class="text-danger fw-bold"><?= $inv['symbol'] ?> <?= number_format($inv['outstanding_amount'], 2) ?></span>
                            <?php else: ?>
                                <span class="text-success small"><i class="fa-solid fa-check-circle"></i> Cleared</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge status-<?= $inv['status'] ?>"><?= $inv['status'] ?></span></td>
                        <td class="text-end pe-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="view.php?id=<?= $inv['invoice_id'] ?>" target="_blank" class="btn-icon" title="View PDF"><i class="fa-regular fa-file-pdf"></i></a>
                                <?php if (!$isPaid): ?>
                                    <button class="btn-icon" onclick='openPaymentModal(<?= json_encode($inv) ?>)' title="Record Payment"><i class="fa-solid fa-hand-holding-dollar text-success"></i></button>
                                <?php endif; ?>
                                <button class="btn-icon" onclick='openSendModal(<?= json_encode($inv) ?>)' title="Send"><i class="fa-regular fa-paper-plane text-primary"></i></button>
                                <?php if ($isPaid): ?>
                                    <button class="btn-icon btn-disabled" disabled><i class="fa-regular fa-trash-can"></i></button>
                                <?php else: ?>
                                    <button class="btn-icon text-danger" onclick="confirmDelete('<?= $inv['invoice_id'] ?>')"><i class="fa-regular fa-trash-can"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top bg-white d-flex justify-content-end">
            <nav aria-label="Invoice navigation">
                <ul class="pagination mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                            <i class="fa-solid fa-chevron-left small"></i>
                        </a>
                    </li>

                    <?php
                    // Display Window: Current Page +/- 1.  (e.g., 1 ... 4 5 6 ... 10)
                    $range = 1; 
                    $start = max(1, $page - $range);
                    $end = min($totalPages, $page + $range);

                    if($start > 1) { 
                        echo '<li class="page-item"><a class="page-link" href="?page=1&search='.urlencode($search).'">1</a></li>';
                        if($start > 2) echo '<li class="page-item disabled"><span class="page-link px-2">...</span></li>';
                    }

                    for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor;

                    if($end < $totalPages) {
                        if($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link px-2">...</span></li>';
                        echo '<li class="page-item"><a class="page-link" href="?page='.$totalPages.'&search='.urlencode($search).'">'.$totalPages.'</a></li>';
                    }
                    ?>

                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                            <i class="fa-solid fa-chevron-right small"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Record Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update_payment">
                <input type="hidden" name="invoice_id" id="pay_id">
                <div class="card bg-light border-0 mb-3"><div class="card-body p-3 d-flex justify-content-between"><div><small class="text-muted d-block">Total</small><span class="fw-bold" id="pay_total"></span></div><div class="text-end"><small class="text-danger d-block">Due</small><span class="fw-bold text-danger" id="pay_due"></span></div></div></div>
                <div class="mb-3"><label class="form-label small fw-bold">Amount Paid (Cumulative)</label><input type="number" name="paid_amount" id="pay_amount" class="form-control" step="0.01" required></div>
                <div class="mb-3"><label class="form-label small fw-bold">Method</label><select name="payment_method" class="form-select"><option value="Bank">Bank</option><option value="Cash">Cash</option><option value="Online">Online</option></select></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success fw-bold px-4">Update</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="sendModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Send Invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="action" value="send"><input type="hidden" name="invoice_id" id="send_id">
                <div class="mb-3"><label class="form-label small fw-bold">Client Email</label><input type="email" name="client_email" id="send_email" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary fw-bold px-4">Send</button></div>
        </form>
    </div>
</div>

<script>
    function openPaymentModal(inv) {
        document.getElementById('pay_id').value = inv.invoice_id;
        document.getElementById('pay_total').innerText = inv.symbol + ' ' + parseFloat(inv.grand_total).toFixed(2);
        document.getElementById('pay_due').innerText = inv.symbol + ' ' + parseFloat(inv.outstanding_amount).toFixed(2);
        document.getElementById('pay_amount').value = parseFloat(inv.paid_amount).toFixed(2);
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    }
    function openSendModal(inv) {
        document.getElementById('send_id').value = inv.invoice_id;
        document.getElementById('send_email').value = inv.client_email || '';
        new bootstrap.Modal(document.getElementById('sendModal')).show();
    }
    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete?', text: "Permanent action.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#ef4444', confirmButtonText: 'Delete'
        }).then((r) => {
            if (r.isConfirmed) {
                let f = document.createElement('form'); f.method = 'POST';
                f.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="invoice_id" value="${id}">`;
                document.body.appendChild(f); f.submit();
            }
        })
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>