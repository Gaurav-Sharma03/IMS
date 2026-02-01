<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';

// Only Clients Allowed
requireRole(['client']);

$payCtrl = new PaymentController();
$payCtrl->handleRequest(); // Now this method exists!
$data = $payCtrl->getData(); // And this one too!

$pending = $data['pending'];
$history = $data['history'];

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>




<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap');

    :root {
        --primary: #4f46e5;
        --primary-hover: #4338ca;
        --dark-bg: #0f172a;
        --body-bg: #f8fafc;
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
        --radius-lg: 16px;
        --radius-md: 10px;
        --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
        --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--body-bg);
        color: var(--text-main);
    }

    /* --- Page Layout --- */
    .main-content {
        margin-left: 250px;
        padding: 40px;
        min-height: 100vh;
    }
    
    @media (max-width: 992px) { .main-content { margin-left: 0; padding: 20px; } }

    .page-title { font-size: 1.75rem; font-weight: 700; color: var(--dark-bg); letter-spacing: -0.025em; }
    .page-subtitle { color: var(--text-muted); font-size: 0.95rem; margin-top: 4px; }

    /* --- Grid Layout for Desktop --- */
    .payment-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 40px;
        margin-top: 30px;
    }
    
    @media (max-width: 1200px) { .payment-grid { grid-template-columns: 1fr; } }

    /* --- Left Column: Lists --- */
    .section-card {
        background: var(--card-bg);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        overflow: hidden;
    }

    .custom-tabs {
        display: flex;
        border-bottom: 1px solid var(--border-color);
        background: #fff;
        padding: 0 20px;
    }
    .custom-tabs button {
        background: none;
        border: none;
        padding: 20px 20px;
        font-weight: 600;
        color: var(--text-muted);
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
    }
    .custom-tabs button.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
    }
    .custom-tabs button:hover { color: var(--text-main); }

    /* --- Invoice List Items --- */
    .invoice-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 24px;
        border-bottom: 1px solid var(--border-color);
        transition: background 0.15s;
    }
    .invoice-item:last-child { border-bottom: none; }
    .invoice-item:hover { background-color: #f8fafc; }

    .inv-icon-box {
        width: 48px; height: 48px;
        background: #eff6ff;
        color: var(--primary);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        margin-right: 20px;
    }
    
    .inv-details h6 { margin: 0; font-weight: 600; font-size: 1rem; color: var(--text-main); }
    .inv-details span { font-size: 0.85rem; color: var(--text-muted); }
    .inv-ref { font-family: 'JetBrains Mono', monospace; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 0.75rem; color: #475569; }

    .inv-amount { text-align: right; margin-right: 20px; }
    .inv-amount .val { font-weight: 700; font-size: 1.1rem; color: var(--dark-bg); }
    .inv-amount .status { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    
    .btn-action {
        background: var(--dark-bg);
        color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .btn-action:hover { background: var(--primary); transform: translateY(-1px); box-shadow: var(--shadow-md); color: white; }

    /* --- Right Column: Summary Card --- */
    .summary-widget {
        background: linear-gradient(145deg, #1e293b, #0f172a);
        color: white;
        border-radius: var(--radius-lg);
        padding: 30px;
        position: relative;
        box-shadow: var(--shadow-lg);
    }
    .summary-widget::before {
        content: ''; position: absolute; top: 0; right: 0; width: 150px; height: 150px;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        border-top-right-radius: var(--radius-lg);
    }

    .balance-label { font-size: 0.85rem; opacity: 0.7; text-transform: uppercase; letter-spacing: 1px; }
    .balance-val { font-size: 2.5rem; font-weight: 700; margin: 10px 0; letter-spacing: -1px; }
    
    .mini-stat { display: flex; align-items: center; justify-content: space-between; margin-top: 25px; padding-top: 25px; border-top: 1px solid rgba(255,255,255,0.1); }
    .mini-stat div span { display: block; }
    .stat-head { font-size: 0.8rem; opacity: 0.6; }
    .stat-body { font-size: 1rem; font-weight: 600; margin-top: 2px; }

    /* --- Modal Upgrade --- */
    .modal-content { border-radius: 20px; overflow: hidden; }
    .checkout-header { background: #f8fafc; padding: 25px; border-bottom: 1px solid var(--border-color); text-align: center; }
    .checkout-body { padding: 30px; }
    
    /* Credit Card in Modal */
    .cc-visual {
        background: linear-gradient(135deg, #4f46e5, #818cf8);
        border-radius: 14px;
        padding: 25px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
        position: relative;
        overflow: hidden;
    }
    .cc-visual .chip { width: 40px; height: 30px; background: rgba(255,255,255,0.3); border-radius: 6px; margin-bottom: 20px; }
    .cc-num { font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; letter-spacing: 2px; margin-bottom: 20px; text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    
    .form-control-lg { font-size: 0.95rem; border-radius: 8px; border: 1px solid #cbd5e1; padding: 12px 15px; }
    .form-control-lg:focus { box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); border-color: var(--primary); }
</style>

<main class="main-content">

    <div class="d-flex justify-content-between align-items-end mb-2">
        <div>
            <h1 class="page-title">Billing & Payments</h1>
            <p class="page-subtitle">Manage your invoices and track your payment history.</p>
        </div>
        <div class="d-block d-xl-none bg-white px-3 py-2 rounded shadow-sm border">
            <span class="small text-muted fw-bold">DUE:</span>
            <span class="fw-bold text-danger">$<?= number_format(array_sum(array_column($pending, 'grand_total')), 2) ?></span>
        </div>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center mt-3 animate__animated animate__fadeIn">
            <i class="fa-solid fa-circle-check fs-4 me-3"></i>
            <div><strong>Success:</strong> Payment processed successfully. A receipt has been sent to your email.</div>
        </div>
    <?php endif; ?>

    <div class="payment-grid">
        
        <div class="left-col">
            <div class="section-card">
                <div class="custom-tabs nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-due-tab" data-bs-toggle="tab" data-bs-target="#nav-due" type="button">
                        Outstanding <span class="badge bg-danger bg-opacity-10 text-danger ms-2 rounded-pill"><?= count($pending) ?></span>
                    </button>
                    <button class="nav-link" id="nav-history-tab" data-bs-toggle="tab" data-bs-target="#nav-history" type="button">
                        Payment History
                    </button>
                </div>

                <div class="tab-content" id="nav-tabContent">
                    
                    <div class="tab-pane fade show active" id="nav-due">
                        <?php if(empty($pending)): ?>
                            <div class="text-center py-5">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                    <i class="fa-solid fa-check text-success fs-2"></i>
                                </div>
                                <h5 class="fw-bold text-dark">All settled up</h5>
                                <p class="text-muted">You have no outstanding invoices.</p>
                            </div>
                        <?php else: foreach($pending as $inv): ?>
                            <div class="invoice-item">
                                <div class="d-flex align-items-center">
                                    <div class="inv-icon-box">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </div>
                                    <div class="inv-details">
                                        <h6>Invoice <span class="inv-ref">#<?= htmlspecialchars($inv['invoice_number']) ?></span></h6>
                                        <span>Due <?= date('M d, Y', strtotime($inv['due_date'])) ?></span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="inv-amount d-none d-sm-block">
                                        <div class="val text-dark"><?= $inv['symbol'] . number_format($inv['grand_total'], 2) ?></div>
                                        <div class="status text-danger">Unpaid</div>
                                    </div>
                                    <button class="btn-action" onclick="openPaymentModal('<?= $inv['invoice_id'] ?>', '<?= $inv['invoice_number'] ?>', '<?= $inv['grand_total'] ?>', '<?= $inv['symbol'] ?>')">
                                        Pay
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                    <div class="tab-pane fade" id="nav-history">
                        <?php if(empty($history)): ?>
                            <div class="text-center py-5 text-muted">No payment history available.</div>
                        <?php else: foreach($history as $h): ?>
                            <div class="invoice-item">
                                <div class="d-flex align-items-center">
                                    <div class="inv-icon-box" style="background: #ecfdf5; color: #059669;">
                                        <i class="fa-solid fa-receipt"></i>
                                    </div>
                                    <div class="inv-details">
                                        <h6>Paid <span class="inv-ref">#<?= htmlspecialchars($h['invoice_number']) ?></span></h6>
                                        <span><?= date('M d, Y', strtotime($h['payment_date'])) ?> • via <?= htmlspecialchars($h['payment_method']) ?></span>
                                    </div>
                                </div>
                                <div class="inv-amount">
                                    <div class="val text-success">+ $<?= number_format($h['amount'], 2) ?></div>
                                    <div class="status text-success">Success</div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>

                </div>
            </div>
            
            <div class="mt-4 text-center">
                <small class="text-muted"><i class="fa-solid fa-lock me-1"></i> Payments are secured by 256-bit SSL encryption.</small>
            </div>
        </div>

        <div class="right-col d-none d-xl-block">
            <div class="summary-widget sticky-top" style="top: 30px; z-index: 1;">
                <div class="balance-label">Total Outstanding</div>
                <div class="balance-val">$<?= number_format(array_sum(array_column($pending, 'grand_total')), 2) ?></div>
                <div class="text-white-50 small mb-4">Make payments promptly to avoid service interruption.</div>
                
                <div class="mini-stat">
                    <div>
                        <span class="stat-head">Pending Invoices</span>
                        <span class="stat-body"><?= count($pending) ?></span>
                    </div>
                    <div class="text-end">
                        <span class="stat-head">Last Payment</span>
                        <span class="stat-body">
                            <?= !empty($history) ? date('M d', strtotime($history[0]['payment_date'])) : 'N/A' ?>
                        </span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fa-brands fa-cc-visa fa-2x opacity-50"></i>
                        <i class="fa-brands fa-cc-mastercard fa-2x opacity-50"></i>
                        <i class="fa-brands fa-cc-amex fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            
            <div class="checkout-header">
                <h5 class="fw-bold m-0">Secure Checkout</h5>
                <p class="text-muted small m-0 mt-1">Paying Invoice <span id="modal_invoice_display" class="fw-bold text-dark"></span></p>
                <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            </div>

            <div class="checkout-body">
                <div class="cc-visual">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="chip"></div>
                        <i class="fa-brands fa-cc-visa fs-1 opacity-75"></i>
                    </div>
                    <div class="cc-num">•••• •••• •••• 4242</div>
                    <div class="d-flex justify-content-between small opacity-90">
                        <span class="text-uppercase"><?= htmlspecialchars($_SESSION['name'] ?? 'CARD HOLDER') ?></span>
                        <span>12/29</span>
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="pay_now" value="1">
                    <input type="hidden" name="invoice_id" id="modal_invoice_id">
                    <input type="hidden" name="amount" id="modal_amount_input">

                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <span class="fw-bold text-muted">Total Amount</span>
                        <span class="fs-4 fw-bold text-dark" id="modal_display_amount">$0.00</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold text-muted text-uppercase">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-credit-card text-muted"></i></span>
                                <input type="text" class="form-control form-control-lg border-start-0 ps-0" value="4242 4242 4242 4242" readonly>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">Expiry</label>
                            <input type="text" class="form-control form-control-lg text-center" value="12 / 29" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted text-uppercase">CVC</label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg text-center" value="123" readonly>
                                <span class="input-group-text bg-white"><i class="fa-solid fa-circle-question text-muted"></i></span>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 py-3 mt-4 rounded-3 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2">
                        <span>Pay Securely</span> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openPaymentModal(id, number, amount, symbol) {
        document.getElementById('modal_invoice_id').value = id;
        document.getElementById('modal_invoice_display').innerText = '#' + number;
        document.getElementById('modal_amount_input').value = amount;
        document.getElementById('modal_display_amount').innerText = symbol + parseFloat(amount).toFixed(2);
        
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        const invoiceId = urlParams.get('pay_invoice');
        if (invoiceId) {
            const payBtn = document.querySelector(`button[onclick*="'${invoiceId}'"]`);
            if(payBtn) payBtn.click();
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>