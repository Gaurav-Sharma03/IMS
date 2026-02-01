<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ClientController.php';
require_once __DIR__ . '/../../config/database.php';

// 1. Fetch Options (Currencies)
$db = new Database();
$conn = $db->connect();
$currencies = $conn->query("SELECT currency_id, code, symbol FROM currencies ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Handle Submit
$controller = new ClientController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->store(); 
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    
    .form-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; max-width: 950px; margin: 0 auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .card-body { padding: 35px; }
    
    /* Typography */
    .form-label { font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 6px; }
    .form-control, .form-select { padding: 11px 15px; font-size: 0.9rem; border-radius: 8px; border-color: #cbd5e1; }
    .form-control:focus, .form-select:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    
    /* Section Dividers */
    .section-title { 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        color: #94a3b8; 
        font-weight: 700; 
        border-bottom: 1px solid #f1f5f9; 
        padding-bottom: 10px; 
        margin-bottom: 20px; 
        margin-top: 10px;
    }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4 max-w-950 mx-auto" style="max-width: 950px;">
        <h4 class="fw-bold text-dark m-0">Add New Client</h4>
        <a href="<?= BASE_URL ?>views/clients/manage.php" class="btn btn-outline-secondary btn-sm fw-semibold">
            <i class="fa-solid fa-arrow-left me-2"></i> Cancel
        </a>
    </div>

    <div class="alert alert-light border border-info d-flex align-items-center gap-3 text-info small mb-4 mx-auto shadow-sm" style="max-width: 950px; background: #f0f9ff;">
        <i class="fa-solid fa-circle-info fs-5"></i>
        <div>
            <strong>Note:</strong> A default password (e.g., <span class="font-monospace bg-white px-1 border rounded">Client@<?= date('Y') ?></span>) will be generated automatically. 
            The client will be required to change it upon their first login.
        </div>
    </div>

    <div class="form-card">
        <div class="card-body">
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger mb-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="section-title mt-0">Business Identity</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Client / Company Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0"><i class="fa-regular fa-building"></i></span>
                            <input type="text" name="name" class="form-control border-start-0 ps-0" placeholder="e.g. Acme Corp" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Primary Contact Person</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0"><i class="fa-regular fa-user"></i></span>
                            <input type="text" name="contact_person" class="form-control border-start-0 ps-0" placeholder="e.g. John Doe">
                        </div>
                    </div>
                </div>

                <div class="section-title">Communication Details</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="client@example.com" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
                    </div>
                </div>

                <div class="section-title">Financial Settings</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Currency <span class="text-danger">*</span></label>
                        <select name="currency_id" class="form-select" required>
                            <option value="" disabled selected>Select Currency</option>
                            <?php foreach ($currencies as $curr): ?>
                                <option value="<?= $curr['currency_id'] ?>"><?= $curr['code'] ?> - <?= $curr['symbol'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tax ID (GST / VAT)</label>
                        <input type="text" name="gst_vat" class="form-control" placeholder="Optional">
                    </div>
                </div>

                <div class="section-title">Billing Address</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Street Address / P.O. Box</label>
                        <input type="text" name="address" class="form-control" placeholder="123 Main St, Suite 400">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">State / Province</label>
                        <input type="text" name="state" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Postal/ZIP Code</label>
                        <input type="text" name="postal_code" class="form-control">
                    </div>
                    
                    <input type="hidden" name="street" value=""> 
                </div>

                <div class="text-end mt-5 pt-3 border-top">
                    <button type="submit" class="btn btn-primary fw-bold px-5 py-2">
                        <i class="fa-solid fa-check me-2"></i> Register Client
                    </button>
                </div>

            </form>
        </div>
    </div>
</main>

<script>
    // Optional: Copy address to street hidden field if needed logic demands it
    document.querySelector('input[name="address"]').addEventListener('input', function() {
        document.querySelector('input[name="street"]').value = this.value;
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>