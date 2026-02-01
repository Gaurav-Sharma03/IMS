<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/CurrencyController.php';

// Controller Logic
$controller = new CurrencyController();
$controller->handleRequest(); 

// Fetch Data
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$data = $controller->index($search, $page, $limit);

$currencies = $data['currencies'];
$totalItems = $data['total'];
$totalPages = ceil($totalItems / $limit);

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
    
    .code-badge { background: #e0e7ff; color: #4338ca; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 0.8rem; letter-spacing: 0.5px; }
    .btn-icon { border:none; background:transparent; color:#64748b; transition:0.2s; }
    .btn-icon:hover { color:#0f172a; } .btn-icon.delete:hover { color:#ef4444; }
</style>

<main class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">Currencies</h4>
            <p class="text-muted small m-0">Manage supported currencies and exchange rates</p>
        </div>
        <button class="btn btn-primary fw-bold px-4 shadow-sm" onclick="openAddModal()">
            <i class="fa-solid fa-plus me-2"></i> Add Currency
        </button>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success shadow-sm border-0 mb-4 d-flex align-items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> 
            <?= $_GET['success'] == 'added' ? 'Currency added successfully.' : 'Currency updated successfully.' ?>
        </div>
    <?php elseif(isset($_GET['error'])): ?>
        <div class="alert alert-danger shadow-sm border-0 mb-4 d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i> 
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="table-card overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <form method="GET" class="position-relative">
                <i class="fa-solid fa-search position-absolute text-muted" style="top:50%; left:12px; transform:translateY(-50%); font-size:0.85rem;"></i>
                <input type="text" name="search" class="form-control ps-5" placeholder="Search code or name..." value="<?= htmlspecialchars($search) ?>" style="width: 250px;">
            </form>
            <div class="text-muted small">Total: <strong><?= $totalItems ?></strong></div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Currency Name</th>
                        <th>Symbol</th>
                        <th>Exchange Rate</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($currencies) > 0): ?>
                        <?php foreach ($currencies as $c): ?>
                            <tr>
                                <td><span class="code-badge"><?= htmlspecialchars($c['code']) ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="fs-5"><?= htmlspecialchars($c['symbol']) ?></td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold">1.00 = <?= number_format($c['exchange_rate'], 4) ?></span>
                                        <span class="small text-muted">Base Rate</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <button class="btn-icon" onclick='openEditModal(<?= json_encode($c) ?>)'><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon delete" onclick="confirmDelete('<?= $c['currency_id'] ?>')"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No currencies found.</td></tr>
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

<div class="modal fade" id="currencyModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Add New Currency</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="currency_id" id="currency_id">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Code (e.g. USD) <span class="text-danger">*</span></label>
                        <input type="text" name="code" id="code" class="form-control text-uppercase" maxlength="5" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Symbol (e.g. $)</label>
                        <input type="text" name="symbol" id="symbol" class="form-control">
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label small fw-bold">Currency Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. United States Dollar" required>
                </div>

                <div class="mt-3">
                    <label class="form-label small fw-bold">Exchange Rate <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0">1.00 =</span>
                        <input type="number" step="0.0001" name="exchange_rate" id="exchange_rate" class="form-control border-start-0 ps-0" required>
                    </div>
                    <div class="form-text small">Exchange rate relative to your company's base currency.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Currency</button>
            </div>
        </form>
    </div>
</div>

<script>
    const currencyModal = new bootstrap.Modal(document.getElementById('currencyModal'));

    function openAddModal() {
        document.getElementById('modalTitle').innerText = "Add New Currency";
        document.getElementById('currency_id').value = "";
        document.getElementById('code').value = "";
        document.getElementById('name').value = "";
        document.getElementById('symbol').value = "";
        document.getElementById('exchange_rate').value = "1.0000";
        currencyModal.show();
    }

    function openEditModal(c) {
        document.getElementById('modalTitle').innerText = "Edit Currency";
        document.getElementById('currency_id').value = c.currency_id;
        document.getElementById('code').value = c.code;
        document.getElementById('name').value = c.name;
        document.getElementById('symbol').value = c.symbol;
        document.getElementById('exchange_rate').value = c.exchange_rate;
        currencyModal.show();
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'Delete Currency?',
            text: "This could affect products using this currency.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="currency_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        })
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>