<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../config/database.php';

// Fetch Options
$db = new Database();
$conn = $db->connect();
$currencies = $conn->query("SELECT currency_id, code, symbol FROM currencies ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);
$taxes = $conn->query("SELECT tax_id, name, rate FROM taxes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Handle Submit
$controller = new ProductController();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->store(); 
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    .form-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; max-width: 900px; margin: 0 auto; }
    .section-title { font-size: 0.75rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 20px; }
</style>

<main class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 max-w-900 mx-auto" style="max-width: 900px;">
        <h4 class="fw-bold text-dark m-0">Add Product</h4>
        <a href="<?= BASE_URL ?>views/products/manage.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-2"></i> Cancel
        </a>
    </div>

    <div class="form-card p-4">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger mb-4"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="add">

            <div class="section-title mt-0">General Info</div>
            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <label class="form-label small fw-bold">Product Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">SKU</label>
                    <input type="text" name="sku" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Category</label>
                    <input type="text" name="category" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Type</label>
                    <select name="product_type" class="form-select">
                        <option value="product">Physical Product</option>
                        <option value="service">Service</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Unit</label>
                    <input type="text" name="unit" class="form-control" value="pcs">
                </div>
            </div>

            <div class="section-title">Pricing</div>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Price *</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Currency *</label>
                    <select name="currency_id" class="form-select" required>
                        <option value="" disabled selected>Select</option>
                        <?php foreach($currencies as $c): ?><option value="<?= $c['currency_id'] ?>"><?= $c['code'] ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Tax</label>
                    <select name="tax_id" class="form-select">
                        <option value="">None</option>
                        <?php foreach($taxes as $t): ?><option value="<?= $t['tax_id'] ?>"><?= $t['name'] ?> (<?= $t['rate'] ?>%)</option><?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="section-title">Inventory & Details</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Stock Quantity</label>
                    <input type="number" name="stock_quantity" class="form-control" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="text-end mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-primary fw-bold px-4">Save Product</button>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>