<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ClientController.php';

$clientCtrl = new ClientController();
$invoice_id = $_GET['id'] ?? null;

// Secure fetch: ensures invoice belongs to this logged-in client
$inv = $clientCtrl->getInvoiceForPayment($invoice_id);

if (!$inv) {
    echo "Invalid Request or Invoice ID.";
    exit;
}

include __DIR__ . '/../layouts/header.php';
// No sidebar here for a "checkout" focus
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <div class="mb-3">
                <a href="<?= BASE_URL ?>views/portal/my-invoices.php" class="text-decoration-none text-muted small">
                    <i class="fa-solid fa-arrow-left me-1"></i> Cancel and Return
                </a>
            </div>

            <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white py-4 text-center">
                    <h5 class="mb-1 fw-bold">Secure Payment</h5>
                    <small class="opacity-75">Completing payment for Invoice #<?= htmlspecialchars($inv['invoice_number']) ?></small>
                </div>
                
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <p class="text-muted mb-0 small text-uppercase fw-bold">Total Amount Due</p>
                        <h1 class="text-primary fw-bold display-5 my-2">
                            <?= $inv['symbol'] ?><?= number_format($inv['outstanding_amount'], 2) ?>
                        </h1>
                        <span class="badge bg-light text-dark border">To: <?= htmlspecialchars($inv['company_name']) ?></span>
                    </div>

                    <form action="#" method="POST"> 
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">Select Payment Method</label>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary flex-fill active"><i class="fa-brands fa-cc-visa fa-lg me-1"></i> Card</button>
                                <button type="button" class="btn btn-outline-secondary flex-fill"><i class="fa-brands fa-paypal fa-lg me-1"></i> PayPal</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Card Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-regular fa-credit-card"></i></span>
                                <input type="text" class="form-control" placeholder="0000 0000 0000 0000">
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Expiry Date</label>
                                <input type="text" class="form-control" placeholder="MM / YY">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">CVC / CVV</label>
                                <input type="password" class="form-control" placeholder="123">
                            </div>
                        </div>

                        <button type="button" class="btn btn-success w-100 fw-bold py-3 rounded-3 shadow-sm">
                            Confirm Payment &nbsp; <i class="fa-solid fa-lock"></i>
                        </button>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted" style="font-size: 11px;">
                                <i class="fa-solid fa-shield-halved me-1"></i> 
                                Payment processed securely via Stripe/PayPal
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>