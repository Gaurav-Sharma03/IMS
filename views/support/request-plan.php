<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/TenantController.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';

// Ensure User is Company Admin
requireRole(['admin']); 

$tenantCtrl = new TenantController();
$payCtrl = new PaymentController();

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $res = $payCtrl->processSubscriptionPayment($_POST['plan_id'], $_POST['amount'], $_POST['transaction_id']);
    // Success State handled via JS Redirect usually, but PHP fallback here
    $alertType = ($res['status'] === 'success') ? 'success' : 'danger';
    $alertMsg = $res['message'];
}

$plans = $tenantCtrl->getAvailablePlans();
$currentSub = $tenantCtrl->getCurrentSubscription();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    /* Professional Dark Pricing UI */
    :root { 
        --p-bg: #0f172a;       /* Dark Background */
        --p-card: #1e293b;     /* Card Background */
        --p-border: #334155;   /* Border Color */
        --p-text: #ffffff;     /* White Text */
        --p-accent: #6366f1;   /* Indigo Accent */
        --p-muted: #cbd5e1;    /* Light Gray */
    }
    
    .main-content { margin-left: 250px; padding: 40px; background: var(--p-bg); min-height: 100vh; font-family: 'Inter', sans-serif; color: var(--p-text); }
    @media (max-width: 768px) { .main-content { margin-left: 0; padding: 20px; } }

    /* Header */
    .page-header { text-align: center; margin-bottom: 50px; }
    .page-title { font-size: 2.2rem; font-weight: 800; color: var(--p-text); letter-spacing: -0.5px; }
    .page-subtitle { color: var(--p-muted); font-size: 1.1rem; max-width: 600px; margin: 10px auto 0; }

    /* Pricing Grid */
    .pricing-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; max-width: 1200px; margin: 0 auto; }
    
    .plan-card {
        background: var(--p-card); border: 1px solid var(--p-border); border-radius: 16px; padding: 40px 30px;
        transition: transform 0.3s, box-shadow 0.3s; position: relative; overflow: hidden;
        display: flex; flex-direction: column;
    }
    .plan-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px -5px rgba(0,0,0,0.3); border-color: var(--p-accent); }
    
    /* Best Value Ribbon */
    .ribbon {
        position: absolute; top: 12px; right: -30px; transform: rotate(45deg);
        background: #10b981; color: white; font-size: 0.7rem; font-weight: 700;
        padding: 5px 30px; text-transform: uppercase; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }

    .plan-name { font-size: 1.25rem; font-weight: 700; color: var(--p-text); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; opacity: 0.9; }
    .plan-price { font-size: 3.5rem; font-weight: 800; color: var(--p-text); line-height: 1; margin-bottom: 10px; }
    .plan-price span { font-size: 1rem; color: var(--p-muted); font-weight: 500; }
    .plan-duration { font-size: 0.9rem; color: var(--p-muted); margin-bottom: 30px; font-weight: 500; }

    .feature-list { list-style: none; padding: 0; margin: 0 0 30px 0; flex-grow: 1; }
    .feature-list li { margin-bottom: 15px; color: #e2e8f0; font-size: 0.95rem; display: flex; align-items: start; gap: 10px; }
    .feature-list i { color: var(--p-accent); margin-top: 3px; }

    /* Buttons */
    .btn-plan {
        width: 100%; padding: 15px; border-radius: 12px; font-weight: 700; font-size: 1rem;
        background: transparent; border: 2px solid var(--p-accent); color: var(--p-text); transition: 0.2s; cursor: pointer;
    }
    .btn-plan:hover { background: var(--p-accent); color: white; box-shadow: 0 0 15px rgba(99, 102, 241, 0.4); }
    
    .btn-current { background: #10b981; border-color: #10b981; color: white; cursor: default; }
    .btn-pending { background: #f59e0b; border-color: #f59e0b; color: white; cursor: not-allowed; opacity: 0.8; }

    /* --- PAYMENT MODAL ADVANCED --- */
    .pay-modal { background: #f8fafc; border-radius: 20px; border: none; overflow: hidden; }
    .pay-header { background: linear-gradient(135deg, #6366f1, #4f46e5); padding: 30px; color: white; text-align: center; position: relative; }
    .pay-body { padding: 40px; }
    
    /* Credit Card Visual */
    .credit-card {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: white; padding: 25px; border-radius: 15px;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.3);
        margin-bottom: 30px; position: relative; overflow: hidden;
    }
    .credit-card::before {
        content: ''; position: absolute; top: -50px; right: -50px;
        width: 200px; height: 200px; background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    
    .cc-chip { width: 40px; height: 30px; background: #fbbf24; border-radius: 5px; margin-bottom: 20px; }
    .cc-number { font-family: 'Courier New', monospace; font-size: 1.4rem; letter-spacing: 2px; margin-bottom: 20px; text-shadow: 0 2px 2px rgba(0,0,0,0.3); }
    .cc-info { display: flex; justify-content: space-between; font-size: 0.8rem; text-transform: uppercase; color: #94a3b8; }
    .cc-val { color: white; font-size: 1rem; margin-top: 5px; }

    /* Inputs */
    .pay-label { font-size: 0.75rem; font-weight: 700; color: #64748b; margin-bottom: 8px; display: block; text-transform: uppercase; letter-spacing: 0.5px; }
    .pay-input { background: white; border: 1px solid #e2e8f0; padding: 12px 15px; border-radius: 8px; width: 100%; font-size: 1rem; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    .pay-input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1); }

    /* Success Modal Animation */
    .success-modal { text-align: center; padding: 40px; }
    .success-icon-box {
        width: 80px; height: 80px; background: #d1fae5; color: #10b981;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 40px; margin: 0 auto 20px;
        animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes popIn { 0% { transform: scale(0); opacity: 0; } 100% { transform: scale(1); opacity: 1; } }
</style>

<main class="main-content">
    
    <?php if(isset($alertMsg)): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fa-solid fa-bell me-2"></i> <?= htmlspecialchars($alertMsg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <h1 class="page-title">Choose Your Plan</h1>
        <p class="page-subtitle">Unlock the full potential of your dashboard with our flexible pricing tiers. Upgrade anytime as you grow.</p>
    </div>

    <?php if($currentSub && $currentSub['status'] === 'active'): ?>
        <div class="container mb-5" style="max-width: 1200px;">
            <div class="rounded-3 p-4 d-flex align-items-center justify-content-between shadow-sm" style="background: var(--p-card); border: 1px solid #10b981;">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold m-0 text-white">Active Subscription: <?= htmlspecialchars($currentSub['plan_name']) ?></h5>
                        <p class="text-white opacity-75 small m-0">
                            Valid until <strong><?= date('M d, Y', strtotime($currentSub['end_date'])) ?></strong>
                        </p>
                    </div>
                </div>
                <button class="btn btn-outline-danger btn-sm fw-bold">Cancel Renewal</button>
            </div>
        </div>
    <?php endif; ?>

    <div class="pricing-grid">
        <?php foreach($plans as $p): ?>
            <?php 
                $isCurrent = ($currentSub && $currentSub['plan_id'] == $p['plan_id'] && $currentSub['status'] == 'active');
                $isPending = ($currentSub && $currentSub['status'] == 'pending_approval');
                $features = array_map('trim', explode(',', $p['features']));
            ?>
            <div class="plan-card <?= $isCurrent ? 'border-success' : '' ?>" style="<?= $isCurrent ? 'border-color: #10b981;' : '' ?>">
                <?php if($p['name'] === 'Pro' || $p['price'] > 50): ?>
                    <div class="ribbon">Popular</div>
                <?php endif; ?>

                <div class="plan-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="plan-price">$<?= number_format($p['price'], 0) ?><span>/mo</span></div>
                <div class="plan-duration">Billed every <?= $p['duration'] ?> days</div>

                <ul class="feature-list">
                    <?php foreach($features as $f): ?>
                        <li><i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($f) ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="mt-auto">
                    <?php if($isCurrent): ?>
                        <button class="btn btn-plan btn-current">
                            <i class="fa-solid fa-circle-check me-2"></i> Current Plan
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-plan" onclick="openPaymentModal(<?= $p['plan_id'] ?>, '<?= htmlspecialchars($p['name']) ?>', <?= $p['price'] ?>)">
                            <?= ($currentSub && $currentSub['status'] == 'active') ? 'Switch Plan' : 'Pay & Activate' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</main>

<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pay-modal shadow-lg">
            
            <div class="pay-header">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
                <h4 class="fw-bold m-0"><i class="fa-solid fa-shield-halved me-2"></i> Secure Checkout</h4>
                <div class="mt-2 text-white-50 small">Encrypted 256-bit SSL Connection</div>
            </div>

            <div class="pay-body">
                
                <div class="credit-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="cc-chip"></div>
                        <i class="fa-brands fa-cc-visa fa-2x opacity-75"></i>
                    </div>
                    <div class="cc-number">4242 4242 4242 4242</div>
                    <div class="cc-info">
                        <div>
                            <span>Card Holder</span>
                            <div class="cc-val text-uppercase">Demo User</div>
                        </div>
                        <div>
                            <span>Expires</span>
                            <div class="cc-val">12/30</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-end mb-4">
                    <div>
                        <span class="text-muted small fw-bold">PLAN SELECTED</span>
                        <div class="fw-bold text-dark fs-5" id="payPlanName">--</div>
                    </div>
                    <div class="text-end">
                        <span class="text-muted small fw-bold">TOTAL AMOUNT</span>
                        <div class="fw-bold text-primary fs-4">$<span id="payAmount">0.00</span></div>
                    </div>
                </div>

                <form method="POST" id="realPayForm">
                    <input type="hidden" name="process_payment" value="1">
                    <input type="hidden" name="plan_id" id="formPlanId">
                    <input type="hidden" name="amount" id="formAmount">
                    <input type="hidden" name="transaction_id" id="formTxnId">
                    
                    <div class="mb-3">
                        <label class="pay-label">Card Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-credit-card text-muted"></i></span>
                            <input type="text" class="pay-input border-start-0 ps-0" value="4242 4242 4242 4242" readonly style="font-family:monospace;">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="pay-label">Expiry Date</label>
                            <input type="text" class="pay-input" value="12 / 30" readonly text-center">
                        </div>
                        <div class="col-6">
                            <label class="pay-label">CVC / CWW</label>
                            <input type="password" class="pay-input" value="123" readonly text-center">
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary w-100 fw-bold py-3 shadow-sm" onclick="processTxn()">
                        <i class="fa-solid fa-lock me-2"></i> Pay & Activate Plan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="success-modal bg-white">
                <div class="success-icon-box">
                    <i class="fa-solid fa-check"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Payment Successful!</h4>
                <p class="text-muted small mb-4">Your subscription has been activated successfully. Redirecting...</p>
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let paymentModal;
    let successModal;

    document.addEventListener('DOMContentLoaded', function() {
        paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        successModal = new bootstrap.Modal(document.getElementById('successModal'));
    });

    function openPaymentModal(id, name, price) {
        document.getElementById('payPlanName').innerText = name;
        document.getElementById('payAmount').innerText = parseFloat(price).toFixed(2);
        document.getElementById('formPlanId').value = id;
        document.getElementById('formAmount').value = price;
        paymentModal.show();
    }

    function processTxn() {
        const btn = document.querySelector('#realPayForm button');
        const originalText = btn.innerHTML;
        
        // 1. Show Loading State
        btn.disabled = true; 
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-2"></i> Processing Securely...';

        // 2. Simulate Payment Gateway Delay (2 seconds)
        setTimeout(() => {
            // Generate Fake Transaction ID
            const txn = 'txn_' + Math.random().toString(36).substr(2, 9).toUpperCase();
            document.getElementById('formTxnId').value = txn;
            
            // 3. Hide Payment Modal & Show Success Modal
            paymentModal.hide();
            successModal.show();

            // 4. Submit Form after showing success (1.5s delay for effect)
            setTimeout(() => {
                document.getElementById('realPayForm').submit();
            }, 1500);

        }, 2000);
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>