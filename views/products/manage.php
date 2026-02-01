<?php
// 1. Load Config & Controller
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ProductController.php';
require_once __DIR__ . '/../../config/database.php';

// 2. Fetch Options for Modal (Currencies/Taxes)
$db = new Database();
$conn = $db->connect();
$currencies = $conn->query("SELECT currency_id, code FROM currencies")->fetchAll(PDO::FETCH_ASSOC);
$taxes = $conn->query("SELECT tax_id, name, rate FROM taxes")->fetchAll(PDO::FETCH_ASSOC);

// 3. Controller Logic
$controller = new ProductController();
$controller->handleRequest(); 

// 4. Data Fetching
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$data = $controller->index($search, $page, $limit);

$products = $data['products'];
$totalProducts = $data['total'];
$totalPages = ceil($totalProducts / $limit);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    .table-card { border-radius: 12px; border: 1px solid #e2e8f0; background: white; }
    .custom-table th { background: #f8fafc; font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 16px; }
    .custom-table td { padding: 16px; vertical-align: middle; color: #334155; }
    .btn-icon { border:none; background:transparent; color:#64748b; transition:0.2s; }
    .btn-icon:hover { color:#0f172a; } .btn-icon.delete:hover { color:#ef4444; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0 text-dark">Inventory</h4>
        <a href="<?= BASE_URL ?>views/products/add.php" class="btn btn-primary fw-bold px-4">
            <i class="fa-solid fa-plus me-2"></i> Add Item
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success shadow-sm border-0 mb-4"><i class="fa-solid fa-check-circle me-2"></i> Action completed successfully.</div>
    <?php endif; ?>

    <div class="table-card overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <form method="GET" class="position-relative">
                <i class="fa-solid fa-search position-absolute text-muted" style="top:50%; left:12px; transform:translateY(-50%); font-size:0.85rem;"></i>
                <input type="text" name="search" class="form-control ps-5" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
            </form>
            <div class="text-muted small">Total: <strong><?= $totalProducts ?></strong></div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table mb-0">
                <thead>
                    <tr>
                        <th width="35%">Item</th>
                        <th>Price</th>
                        <th>Stock / Type</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="small text-muted">SKU: <?= htmlspecialchars($p['sku'] ?: '-') ?></div>
                                </td>
                                <td>
                                    <span class="fw-bold">$<?= number_format($p['price'], 2) ?></span>
                                    <div class="small text-muted">/ <?= $p['unit'] ?></div>
                                </td>
                                <td>
                                    <?php if($p['product_type'] == 'service'): ?>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">Service</span>
                                    <?php else: ?>
                                        <div class="<?= $p['stock_quantity'] > 0 ? 'text-success' : 'text-danger' ?> small fw-bold">
                                            <?= $p['stock_quantity'] ?> in stock
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($p['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn-icon" onclick='openEditModal(<?= json_encode($p) ?>)'><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon delete" onclick="confirmDelete('<?= $p['product_id'] ?>')"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No items found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-end gap-2 bg-light">
            <a href="?page=<?= max(1, $page-1) ?>" class="btn btn-sm btn-white border <?= $page<=1?'disabled':'' ?>">Previous</a>
            <a href="?page=<?= min($totalPages, $page+1) ?>" class="btn btn-sm btn-white border <?= $page>=$totalPages?'disabled':'' ?>">Next</a>
        </div>
        <?php endif; ?>
    </div>
</main>

<div class="modal fade" id="editProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add"> <input type="hidden" name="product_id" id="edit_id">
                
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">SKU</label>
                        <input type="text" name="sku" id="edit_sku" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Type</label>
                        <select name="product_type" id="edit_type" class="form-select">
                            <option value="product">Product</option>
                            <option value="service">Service</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Price</label>
                        <input type="number" step="0.01" name="price" id="edit_price" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Currency</label>
                        <select name="currency_id" id="edit_currency" class="form-select">
                            <?php foreach($currencies as $c): ?><option value="<?= $c['currency_id'] ?>"><?= $c['code'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Tax</label>
                        <select name="tax_id" id="edit_tax" class="form-select">
                            <option value="">None</option>
                            <?php foreach($taxes as $t): ?><option value="<?= $t['tax_id'] ?>"><?= $t['name'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Stock</label>
                        <input type="number" name="stock_quantity" id="edit_stock" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Unit</label>
                        <input type="text" name="unit" id="edit_unit" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(p) {
    document.getElementById('edit_id').value = p.product_id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_sku').value = p.sku;
    document.getElementById('edit_type').value = p.product_type;
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_currency').value = p.currency_id;
    document.getElementById('edit_tax').value = p.tax_id;
    document.getElementById('edit_stock').value = p.stock_quantity;
    document.getElementById('edit_unit').value = p.unit;
    document.getElementById('edit_desc').value = p.description;
    document.getElementById('edit_status').value = p.status;
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function confirmDelete(id) {
    if(confirm('Are you sure you want to delete this product?')) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="product_id" value="${id}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>