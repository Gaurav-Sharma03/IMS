<?php
// Initialize Controllers
require_once __DIR__ . '/../../controllers/ReportController.php';
require_once __DIR__ . '/../../controllers/ExpenseController.php';

$reportCtrl = new ReportController();
$expenseCtrl = new ExpenseController();

// Process POST requests
$expenseCtrl->handleRequest();

// Fetch Data (Controller returns: summary, ledger, chartData, start, end, page, totalPages, totalRecords)
$data = $reportCtrl->index();
extract($data); 

$categories = $expenseCtrl->getCategories();

// --- 1. Pagination Calculation (Must be done before HTML) ---
$limit = 10; // Must match the limit set in ReportController
$startRec = ($totalRecords > 0) ? ($page - 1) * $limit + 1 : 0;
$endRec = min($startRec + $limit - 1, $totalRecords);

// --- 2. Build Query Params (Preserve filters in pagination links) ---
$queryParams = http_build_query([
    'start_date' => $start,
    'end_date' => $end
]);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .main-content {
        margin-left: 250px;
        padding: 30px;
        background: #f1f5f9;
        min-height: 100vh;
    }

    @media (max-width: 768px) {
        .main-content {
            margin-left: 0;
        }
       
    }

    /* --- Professional Filter Bar --- */
    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 25px;
    }

    .filter-label {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 6px;
        display: block;
    }

    .btn-preset {
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid #e2e8f0;
        color: #475569;
        background: white;
        padding: 6px 12px;
        transition: 0.2s;
    }

    .btn-preset:hover,
    .btn-preset.active {
        background: #eff6ff;
        color: #2563eb;
        border-color: #bfdbfe;
    }

    /* --- Modern Stats Cards --- */
    .stat-card {
        border: none;
        border-radius: 16px;
        padding: 24px;
        background: white;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05);
    }

    .stat-icon-circle {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 16px;
    }

    /* Color Palettes */
    .theme-indigo {
        background: #e0e7ff;
        color: #4338ca;
    }

    /* Revenue */
    .theme-rose {
        background: #ffe4e6;
        color: #e11d48;
    }

    /* Expense */
    .theme-amber {
        background: #fef3c7;
        color: #d97706;
    }

    /* Tax */
    .theme-emerald {
        background: #d1fae5;
        color: #059669;
    }

    /* Profit */

    /* --- Action Buttons --- */
    .btn-manage {
        background: #334155;
        color: white;
        border: none;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(51, 65, 85, 0.2);
        transition: 0.2s;
    }

    .btn-manage:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }

    .btn-add {
        background: #2563eb;
        color: white;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
        border: none;
    }

    .btn-add:hover {
        background: #1d4ed8;
        color: white;
        transform: translateY(-1px);
    }

    .btn-export {
        background: white;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-weight: 600;
        padding: 10px 20px;
        border-radius: 8px;
    }

    .btn-export:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #334155;
    }

    /* --- Table Styles --- */
    .ledger-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: 0.75rem;
        text-transform: uppercase;
        font-weight: 700;
        padding: 16px;
        border-bottom: 2px solid #e2e8f0;
    }

    .ledger-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #334155;
    }

    .badge-soft {
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.3px;
    }
</style>

<main class="main-content">

    <div class="d-flex justify-content-between align-items-end mb-4">
        <div>
            <h3 class="fw-bold text-dark m-0 ls-tight">Financial Reports</h3>
            <p class="text-muted small mb-0 mt-1">Track your income, expenses, and net profit efficiently.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-manage" data-bs-toggle="modal" data-bs-target="#manageCategoryModal">
                <i class="fa-solid fa-layer-group me-2"></i> Categories
            </button>
            <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fa-solid fa-plus me-2"></i> Record Expense
            </button>
        </div>
    </div>

    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">

            <div class="col-lg-5">
                <span class="filter-label">Quick Period</span>
                <div class="btn-group w-100 shadow-sm" role="group">
                    <a href="?start_date=<?= date('Y-m-d') ?>&end_date=<?= date('Y-m-d') ?>"
                        class="btn btn-preset">Today</a>
                    <a href="?start_date=<?= date('Y-m-d', strtotime('monday this week')) ?>&end_date=<?= date('Y-m-d', strtotime('sunday this week')) ?>"
                        class="btn btn-preset">Week</a>
                    <a href="?start_date=<?= date('Y-m-01') ?>&end_date=<?= date('Y-m-t') ?>"
                        class="btn btn-preset <?= ($start == date('Y-m-01')) ? 'active' : '' ?>">Month</a>
                    <a href="?start_date=<?= date('Y-01-01') ?>&end_date=<?= date('Y-12-31') ?>"
                        class="btn btn-preset">Year</a>
                </div>
            </div>

            <div class="col-lg-3">
                <span class="filter-label">Start Date</span>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $start ?>">
            </div>
            <div class="col-lg-3">
                <span class="filter-label">End Date</span>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $end ?>">
            </div>

            <div class="col-lg-1 d-grid">
                <button type="submit" class="btn btn-primary btn-sm fw-bold" style="height: 38px;"><i
                        class="fa-solid fa-filter"></i></button>
            </div>
        </form>
    </div>

  <div class="row g-4 mb-4">
        
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-icon-circle theme-indigo"><i class="fa-solid fa-wallet"></i></div>
                        <h6 class="text-uppercase text-muted fw-bold small">Total Revenue</h6>
                        <h3 class="fw-bold text-dark mb-0"><?= number_format($summary['income'], 2) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon-circle theme-rose"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <h6 class="text-uppercase text-muted fw-bold small">Total Expenses</h6>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($summary['expense'], 2) ?></h3>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon-circle theme-indigo"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                <h6 class="text-uppercase text-muted fw-bold small">Tax Liability</h6>
                <h3 class="fw-bold text-dark mb-0"><?= number_format($summary['tax'], 2) ?></h3>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon-circle theme-rose"><i class="fa-solid fa-chart-line"></i></div>
                <h6 class="text-uppercase text-muted fw-bold small">Net Profit</h6>
                <h3 class="fw-bold <?= $summary['profit'] >= 0 ? 'text-success' : 'text-danger' ?> mb-0">
                    <?= number_format($summary['profit'], 2) ?>
                </h3>
            </div>
        </div>

    </div>

   <div class="row g-4">
        
        <div class="col-lg-8 order-2 order-lg-1">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="fw-bold m-0 text-dark">Transaction Ledger</h6>
                        <?php if($totalRecords > 0): ?>
                            <small class="text-muted" style="font-size: 0.75rem;">Showing <?= $startRec ?>-<?= $endRec ?> of <?= $totalRecords ?></small>
                        <?php endif; ?>
                    </div>
                    <a href="?start_date=<?= $start ?>&end_date=<?= $end ?>&export=csv" class="btn btn-export">
                        <i class="fa-solid fa-download me-1"></i> CSV
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table class="table ledger-table mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4">Date</th>
                                <th>Description</th>
                                <th>Type</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ledger)): ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No records found for this period.</td></tr>
                            <?php else: foreach ($ledger as $row): ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-bold" style="font-size: 0.85rem;">
                                        <?= date('M d, Y', strtotime($row['date'])) ?>
                                    </td>
                                    <td><span class="d-block text-dark fw-semibold text-truncate" style="max-width: 200px;"><?= htmlspecialchars($row['description']) ?></span></td>
                                    <td>
                                        <?php if ($row['type'] == 'Income'): ?>
                                            <span class="badge-soft theme-emerald">INCOME</span>
                                        <?php else: ?>
                                            <span class="badge-soft theme-rose">EXPENSE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold text-dark"><?= number_format($row['amount'], 2) ?></td>
                                    <td class="text-end pe-4">
                                        <?php if ($row['type'] == 'Expense'): ?>
                                            <div class="d-flex justify-content-end gap-1">
                                                <button class="btn btn-light btn-sm text-primary border" onclick="openEditModal('<?= $row['id'] ?>', '<?= htmlspecialchars($row['description'], ENT_QUOTES) ?>', '<?= $row['amount'] ?>', '<?= $row['date'] ?>', '<?= $row['category_id'] ?? '' ?>')" title="Edit"><i class="fa-solid fa-pen"></i></button>
                                                <form method="POST" onsubmit="return confirm('Permanently delete this expense?');">
                                                    <input type="hidden" name="delete_expense" value="1">
                                                    <input type="hidden" name="expense_id" value="<?= $row['id'] ?>">
                                                    <button class="btn btn-light btn-sm text-danger border" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small"><i class="fa-solid fa-lock"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="card-footer bg-white border-top py-3">
                    <nav>
                        <ul class="pagination justify-content-center justify-content-lg-end mb-0">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&<?= $queryParams ?>"><i class="fa-solid fa-chevron-left small"></i></a>
                            </li>
                            <?php
                            $range = 1; 
                            $pStart = max(1, $page - $range);
                            $pEnd = min($totalPages, $page + $range);

                            if($pStart > 1) { 
                                echo '<li class="page-item"><a class="page-link" href="?page=1&'.$queryParams.'">1</a></li>';
                                if($pStart > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }

                            for ($i = $pStart; $i <= $pEnd; $i++): ?>
                                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= $queryParams ?>"><?= $i ?></a>
                                </li>
                            <?php endfor;

                            if($pEnd < $totalPages) {
                                if($pEnd < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                echo '<li class="page-item"><a class="page-link" href="?page='.$totalPages.'&'.$queryParams.'">'.$totalPages.'</a></li>';
                            }
                            ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&<?= $queryParams ?>"><i class="fa-solid fa-chevron-right small"></i></a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>

       <div class="col-lg-4 order-1 order-lg-2">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-dark">Expense Distribution</h6>
                </div>
                <div class="card-body d-flex flex-column align-items-center justify-content-center">
                    <?php if (empty($chartData)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fa-solid fa-chart-pie fa-3x mb-3 text-light"></i>
                            <p class="small">No expense data available for this period.</p>
                        </div>
                    <?php else: ?>
                        <div style="width: 100%; height: 240px; position: relative;">
                            <canvas id="expenseChart"></canvas>
                        </div>
                        <div class="mt-4 w-100">
                            <h6 class="text-uppercase small fw-bold text-muted mb-3">Top Categories</h6>
                            <ul class="list-group list-group-flush small">
                                <?php foreach (array_slice($chartData, 0, 5) as $cd): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-light">
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($cd['name']) ?></span>
                                        <span class="badge bg-light text-dark border"><?= number_format($cd['total'], 2) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

   
    </div>
</main>

<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title fw-bold"><i class="fa-solid fa-receipt me-2"></i>Record New Expense</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="add_expense" value="1">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-uppercase">Category</label>
                        <div class="input-group">
                            <select name="category_id" class="form-select" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-light border" type="button" data-bs-toggle="modal"
                                data-bs-target="#addCategoryModal">
                                <i class="fa-solid fa-plus text-primary"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-uppercase">Date</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>"
                                required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-uppercase">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="0.00"
                                    required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-uppercase">Description</label>
                        <input type="text" name="description" class="form-control"
                            placeholder="e.g. Office Rent, Server Hosting..." required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2">Save Record</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editExpenseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h6 class="modal-title fw-bold">Edit Expense</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="edit_expense" value="1">
                    <input type="hidden" name="expense_id" id="edit_id">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Category</label>
                        <select name="category_id" id="edit_category" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold">Date</label>
                            <input type="date" name="expense_date" id="edit_date" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Amount</label>
                            <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control"
                                required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Description</label>
                        <input type="text" name="description" id="edit_desc" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 fw-bold">Update Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="manageCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold">Manage Categories</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($categories as $cat): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-3 py-2">
                            <span class="small fw-semibold"><?= htmlspecialchars($cat['name']) ?></span>
                            <form method="POST" onsubmit="return confirm('Delete this category?');" class="m-0">
                                <input type="hidden" name="delete_category" value="1">
                                <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                <button class="btn btn-link text-danger p-0" style="font-size: 0.8rem;"><i
                                        class="fa-solid fa-xmark"></i></button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="p-3 bg-light border-top">
                    <form method="POST">
                        <input type="hidden" name="add_category" value="1">
                        <label class="form-label small fw-bold text-muted">Add New</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="category_name" class="form-control" placeholder="Name..." required>
                            <button class="btn btn-dark">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold">New Category</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" data-bs-toggle="modal"
                    data-bs-target="#addExpenseModal"></button>
            </div>
            <div class="modal-body pt-2">
                <form method="POST">
                    <input type="hidden" name="add_category" value="1">
                    <div class="mb-2"><input type="text" name="category_name" class="form-control" placeholder="Name..."
                            required></div>
                    <button type="submit" class="btn btn-dark w-100 btn-sm fw-bold">Create</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditModal(id, desc, amount, date, catId) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_desc').value = desc;
        document.getElementById('edit_amount').value = amount;
        document.getElementById('edit_date').value = date;
        document.getElementById('edit_category').value = catId;
        var editModal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
        editModal.show();
    }

    <?php if (!empty($chartData)): ?>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($chartData, 'name')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($chartData, 'total')) ?>,
                    backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '65%' }
        });
    <?php endif; ?>
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>