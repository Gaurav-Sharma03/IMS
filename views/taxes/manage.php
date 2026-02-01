<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/TaxController.php';
require_once __DIR__ . '/../../config/database.php';

// 1. Fetch Currencies for the Modal Dropdown
$db = new Database();
$conn = $db->connect();
$currencies = $conn->query("SELECT currency_id, code, symbol FROM currencies ORDER BY code ASC")->fetchAll(PDO::FETCH_ASSOC);

// 2. Controller Logic
$controller = new TaxController();
$controller->handleRequest(); 

// 3. Fetch Data
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$data = $controller->index($search, $page, $limit);

$taxes = $data['taxes'];
$totalTaxes = $data['total'];
$totalPages = ceil($totalTaxes / $limit);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    
    .table-card { border-radius: 12px; border: 1px solid #e2e8f0; background: white; }
    .custom-table th { background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 16px; font-weight: 700; }
    .custom-table td { padding: 16px; vertical-align: middle; color: #334155; }
    
    .rate-badge { font-weight: 700; padding: 5px 10px; border-radius: 6px; font-size: 0.85rem; }
    .rate-low { background: #dcfce7; color: #166534; }
    .rate-mid { background: #fef9c3; color: #854d0e; }
    .rate-high { background: #fee2e2; color: #991b1b; }

    .btn-icon { border:none; background:transparent; color:#64748b; transition:0.2s; }
    .btn-icon:hover { color:#0f172a; } .btn-icon.delete:hover { color:#ef4444; }
</style>

<main class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">Tax Rules</h4>
            <p class="text-muted small m-0">Manage GST, VAT, and other tax rates</p>
        </div>
        <button class="btn btn-primary fw-bold px-4 shadow-sm" onclick="openAddModal()">
            <i class="fa-solid fa-plus me-2"></i> Add Tax
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success shadow-sm border-0 mb-4 d-flex align-items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> 
            <?= $_GET['success'] == 'added' ? 'Tax rule added successfully.' : 'Tax rule updated.' ?>
        </div>
    <?php endif; ?>

    <div class="table-card overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <form method="GET" class="position-relative">
                <i class="fa-solid fa-search position-absolute text-muted" style="top:50%; left:12px; transform:translateY(-50%); font-size:0.85rem;"></i>
                <input type="text" name="search" class="form-control ps-5" placeholder="Search taxes..." value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
            </form>
            <div class="text-muted small">Total: <strong><?= $totalTaxes ?></strong></div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table mb-0">
                <thead>
                    <tr>
                        <th width="30%">Tax Name</th>
                        <th>Rate (%)</th>
                        <th>Region / Country</th>
                        <th>Currency</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($taxes) > 0): ?>
                        <?php foreach ($taxes as $t): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['name']) ?></div>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;">
                                        <?= htmlspecialchars($t['description'] ?: 'No description') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $rateClass = 'rate-mid';
                                        if($t['rate'] < 10) $rateClass = 'rate-low';
                                        if($t['rate'] > 20) $rateClass = 'rate-high';
                                    ?>
                                    <span class="rate-badge <?= $rateClass ?>">
                                        <?= number_format($t['rate'], 2) ?>%
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="fa-solid fa-globe me-1 text-muted"></i> <?= htmlspecialchars($t['country']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($t['currency_code'] ?? 'All') ?></td>
                                <td class="text-end">
                                    <button class="btn-icon" onclick='openEditModal(<?= json_encode($t) ?>)'><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon delete" onclick="confirmDelete('<?= $t['tax_id'] ?>')"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No tax rules found.</td></tr>
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

<div class="modal fade" id="taxModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Add New Tax Rule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add"> <input type="hidden" name="tax_id" id="tax_id">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Tax Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. VAT, GST" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="rate" id="rate" class="form-control" placeholder="18.00" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Country</label>
                        <select name="country" id="country" class="form-select">
                            <option value="Global">Global / All</option>
                            <option value="India">India</option>
                            <option value="USA">USA</option>
                            <option value="UK">UK</option>
                            <option value="Canada">Canada</option>
                            <option value="Australia">Australia</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Currency</label>
                    <select name="currency_id" id="currency_id" class="form-select" required>
                        <?php foreach($currencies as $c): ?>
                            <option value="<?= $c['currency_id'] ?>"><?= $c['code'] ?> (<?= $c['symbol'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="2" placeholder="Brief description..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Tax Rule</button>
            </div>
        </form>
    </div>
</div>

<script>
    const taxModal = new bootstrap.Modal(document.getElementById('taxModal'));

    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Add New Tax Rule";
        document.getElementById('tax_id').value = ""; // Clear ID implies Add
        document.getElementById('name').value = "";
        document.getElementById('rate').value = "";
        document.getElementById('country').value = "Global";
        document.getElementById('description').value = "";
        taxModal.show();
    }

    function openEditModal(tax) {
        document.getElementById('modalTitle').innerText = "Edit Tax Rule";
        document.getElementById('tax_id').value = tax.tax_id; // Set ID implies Update
        document.getElementById('name').value = tax.name;
        document.getElementById('rate').value = tax.rate;
        document.getElementById('country').value = tax.country;
        document.getElementById('currency_id').value = tax.currency_id;
        document.getElementById('description').value = tax.description;
        taxModal.show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Tax Rule?',
            text: "This cannot be undone. Invoices using this tax will remain unchanged.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="tax_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        })
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>