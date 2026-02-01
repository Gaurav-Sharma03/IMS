<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SubscriptionController.php';
require_once __DIR__ . '/../../controllers/PaymentController.php'; // Include Payment Controller

requireRole(['superadmin']);

$subController = new SubscriptionController();
$payController = new PaymentController(); // Initialize Payment Controller

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['assign_plan'])) {
        $subController->assignSubscription($_POST);
        header("Location: subscriptions.php?success=assigned"); exit;
    }
    if (isset($_POST['approve_sub'])) {
        $subController->approveSubscription($_POST['sub_id']);
        header("Location: subscriptions.php?success=approved"); exit;
    }
    if (isset($_POST['cancel_sub'])) {
        $subController->cancelSubscription($_POST['sub_id']);
        header("Location: subscriptions.php?success=cancelled"); exit;
    }
    if (isset($_POST['delete_plan'])) {
        $subController->deletePlan($_POST['plan_id']);
        header("Location: subscriptions.php?tab=plans&success=deleted"); exit;
    }
    if (isset($_POST['save_plan'])) {
        $subController->savePlan($_POST);
        header("Location: subscriptions.php?tab=plans&success=saved"); exit;
    }
}

// Fetch Data
$stats = $subController->getStats();
$plans = $subController->getPlans();
$companies = $subController->getCompanies();
$subs = $subController->getAllSubscriptions($_GET['search'] ?? '', $_GET['status'] ?? '');
$payments = $payController->getSubscriptionPayments(); // Fetch Payments
$activeTab = $_GET['tab'] ?? 'overview';

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root { --sa-bg: #0f172a; --sa-panel: #1e293b; --sa-border: #334155; --text-white: #ffffff; --accent: #6366f1; }
    
    .main-content { margin-left: 250px; padding: 30px; background: var(--sa-bg); min-height: 100vh; color: white; font-family: 'Inter', sans-serif; }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* KPI Cards */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .kpi-card { background: var(--sa-panel); border: 1px solid var(--sa-border); padding: 20px; border-radius: 12px; display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    .kpi-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; background: rgba(255,255,255,0.05); }
    .kpi-val { font-size: 1.8rem; font-weight: 800; line-height: 1; margin-bottom: 5px; color: white; }
    .kpi-lbl { font-size: 0.75rem; color: #cbd5e1; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }

    /* Tabs */
    .nav-tabs { border-bottom: 1px solid var(--sa-border); margin-bottom: 25px; }
    .nav-link {  font-weight: 600; padding: 12px 20px; border: none; background: transparent; transition: 0.2s; }
 
    

    /* Tables */
    .data-card { background: var(--sa-panel); border: 1px solid var(--sa-border); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .sa-table { width: 100%; border-collapse: collapse; }
    .sa-table th { text-align: left; padding: 15px 20px; color: #cbd5e1; font-size: 0.75rem; text-transform: uppercase; font-weight: 700; border-bottom: 1px solid var(--sa-border); background: rgba(0,0,0,0.2); }
    .sa-table td { padding: 18px 20px; border-bottom: 1px solid var(--sa-border); color: #e2e8f0; vertical-align: middle; font-size: 0.9rem; }
    .sa-table tr:hover td { background: rgba(255,255,255,0.03); }

    /* Status Badges */
    .badge-status { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .st-active { background: rgba(52, 211, 153, 0.15); color: #34d399; }
    .st-pending { background: rgba(250, 204, 21, 0.15); color: #facc15; }
    .st-expired { background: rgba(248, 113, 113, 0.15); color: #f87171; }

    /* Modal Form */
    .modal-content { background-color: #1e293b; color: white; border: 1px solid #334155; }
    .modal-header { border-bottom: 1px solid #334155; }
    .form-label { color: #cbd5e1; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; }
    .form-control, .form-select { background: #020617; border: 1px solid var(--sa-border); color: white; }
    .form-control:focus, .form-select:focus { background: #020617; border-color: var(--accent); color: white; box-shadow: none; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0 text-white">Subscription & Revenue</h2>
            <p class="text-white-50 small m-0">Manage plans, tenant subscriptions, and billing history.</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary fw-bold shadow-sm" onclick="openAssignModal()">
                <i class="fa-solid fa-plus me-2"></i> Assign Plan
            </button>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-icon" style="color:#34d399;"><i class="fa-solid fa-money-bill-trend-up"></i></div>
            <div>
                <div class="kpi-val">$<?= number_format($stats['active_mrr'], 0) ?></div>
                <div class="kpi-lbl">Current MRR</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="color:#facc15;"><i class="fa-solid fa-bell"></i></div>
            <div>
                <div class="kpi-val"><?= $stats['pending_reqs'] ?></div>
                <div class="kpi-lbl">Pending Requests</div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="color:#60a5fa;"><i class="fa-solid fa-users"></i></div>
            <div>
                <div class="kpi-val"><?= $stats['active_count'] ?></div>
                <div class="kpi-lbl">Active Subscribers</div>
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'overview' ? 'active' : '' ?>" href="?tab=overview">
                <i class="fa-solid fa-list me-2"></i>Subscriptions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'payments' ? 'active' : '' ?>" href="?tab=payments">
                <i class="fa-solid fa-receipt me-2"></i>Payment History
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $activeTab === 'plans' ? 'active' : '' ?>" href="?tab=plans">
                <i class="fa-solid fa-gear me-2"></i>Plan Settings
            </a>
        </li>
    </ul>

    <?php if($activeTab === 'overview'): ?>
    <div class="data-card">
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Cycle</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($subs)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-white-50">No subscriptions found.</td></tr>
                    <?php else: foreach($subs as $sub): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-white"><?= htmlspecialchars($sub['company_name']) ?></div>
                                <div class="small text-white-50"><?= htmlspecialchars($sub['company_email']) ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($sub['plan_name']) ?></span></td>
                            <td class="text-success fw-bold">$<?= number_format($sub['price'], 2) ?></td>
                            <td>
                                <?php 
                                    $sClass = match($sub['status']) { 'active'=>'st-active', 'pending_approval'=>'st-pending', default=>'st-expired' };
                                    $sLabel = $sub['status'] == 'pending_approval' ? 'Needs Approval' : $sub['status'];
                                ?>
                                <span class="badge-status <?= $sClass ?>"><?= $sLabel ?></span>
                            </td>
                            <td class="small text-white-50">
                                <?= date('M d, Y', strtotime($sub['start_date'])) ?> &rarr; <?= date('M d, Y', strtotime($sub['end_date'])) ?>
                            </td>
                            <td class="text-end">
                                <?php if($sub['status'] === 'pending_approval'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="approve_sub" value="1">
                                        <input type="hidden" name="sub_id" value="<?= $sub['sub_id'] ?>">
                                        <button class="btn btn-sm btn-success fw-bold me-1 shadow-sm"><i class="fa-solid fa-check"></i> Approve</button>
                                    </form>
                                <?php endif; ?>
                                
                                <form method="POST" onsubmit="return confirm('Cancel subscription?');" class="d-inline">
                                    <input type="hidden" name="cancel_sub" value="1">
                                    <input type="hidden" name="sub_id" value="<?= $sub['sub_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-ban"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($activeTab === 'payments'): ?>
    <div class="data-card">
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th class="ps-4">Transaction ID</th>
                        <th>Company</th>
                        <th>Plan Purchased</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th class="text-end pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($payments)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-white-50">No payments recorded.</td></tr>
                    <?php else: foreach($payments as $p): ?>
                        <tr>
                            <td class="ps-4 font-monospace small text-info"><?= htmlspecialchars($p['transaction_id']) ?></td>
                            <td class="fw-bold text-white"><?= htmlspecialchars($p['company_name']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['plan_name']) ?></span></td>
                            <td class="text-success fw-bold">$<?= number_format($p['amount'], 2) ?></td>
                            <td class="text-white-50 small"><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                            <td class="text-end pe-4">
                                <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i> PAID</span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <?php if($activeTab === 'plans'): ?>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-outline-light btn-sm" onclick="openPlanModal()"><i class="fa-solid fa-plus me-2"></i> Create New Plan</button>
    </div>
    <div class="data-card">
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th>Plan Name</th>
                        <th>Price</th>
                        <th>Duration</th>
                        <th>Features</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($plans as $p): ?>
                        <tr>
                            <td class="fw-bold text-white"><?= htmlspecialchars($p['name']) ?></td>
                            <td class="text-success fw-bold">$<?= number_format($p['price'], 2) ?></td>
                            <td class="text-white-50"><?= $p['duration'] ?> Days</td>
                            <td class="text-white-50 small"><?= htmlspecialchars($p['features']) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-light border-0 me-1" 
                                    onclick="editPlan(<?= htmlspecialchars(json_encode($p)) ?>)">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form method="POST" onsubmit="return confirm('Delete this plan?');" class="d-inline">
                                    <input type="hidden" name="delete_plan" value="1">
                                    <input type="hidden" name="plan_id" value="<?= $p['plan_id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>

<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white">Manual Assignment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="assign_plan" value="1">
                    <div class="mb-3">
                        <label class="form-label">TENANT</label>
                        <select name="company_id" class="form-select" required>
                            <option value="">Select Company...</option>
                            <?php foreach($companies as $c): ?>
                                <option value="<?= $c['company_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">PLAN</label>
                        <select name="plan_id" class="form-select" required>
                            <?php foreach($plans as $p): ?>
                                <option value="<?= $p['plan_id'] ?>"><?= htmlspecialchars($p['name']) ?> ($<?= $p['price'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-primary fw-bold py-2">Assign & Activate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="planModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white" id="planModalTitle">Configure Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="save_plan" value="1">
                    <input type="hidden" name="plan_id" id="pId">
                    
                    <div class="mb-3">
                        <label class="form-label">PLAN NAME</label>
                        <input type="text" name="name" id="pName" class="form-control" required placeholder="e.g. Gold Tier">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">PRICE ($)</label>
                            <input type="number" step="0.01" name="price" id="pPrice" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">DURATION (DAYS)</label>
                            <input type="number" name="duration" id="pDur" class="form-control" value="30" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">FEATURES (Comma Separated)</label>
                        <textarea name="features" id="pFeat" class="form-control" rows="3" placeholder="5 Users, API Access, etc."></textarea>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-primary fw-bold py-2">Save Plan Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function openAssignModal() {
        new bootstrap.Modal(document.getElementById('assignModal')).show();
    }

    function openPlanModal() {
        document.getElementById('planModalTitle').innerText = 'Create New Plan';
        document.getElementById('pId').value = '';
        document.getElementById('pName').value = '';
        document.getElementById('pPrice').value = '';
        document.getElementById('pDur').value = '30';
        document.getElementById('pFeat').value = '';
        new bootstrap.Modal(document.getElementById('planModal')).show();
    }

    function editPlan(plan) {
        document.getElementById('planModalTitle').innerText = 'Edit Plan';
        document.getElementById('pId').value = plan.plan_id;
        document.getElementById('pName').value = plan.name;
        document.getElementById('pPrice').value = plan.price;
        document.getElementById('pDur').value = plan.duration;
        document.getElementById('pFeat').value = plan.features;
        new bootstrap.Modal(document.getElementById('planModal')).show();
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>