<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SuperAdminController.php';

// Strict Role Check
requireRole(['superadmin']);

$controller = new SuperAdminController();

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_company'])) {
        $controller->createCompany($_POST);
        header("Location: companies.php?success=created"); exit;
    }
    if (isset($_POST['toggle_status'])) {
        $controller->updateCompanyStatus($_POST['company_id'], $_POST['status']);
        header("Location: companies.php?success=updated"); exit;
    }
}

// Fetch Data
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$companies = $controller->getAllCompanies($search, $statusFilter);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root {
        --sa-bg: #0f172a;       /* Deep Blue-Black Background */
        --sa-panel: #1e293b;    /* Lighter Panel Background */
        --sa-border: #334155;   /* Border */
        --sa-accent: #6366f1;   /* Indigo Accent */
        
        /* High Contrast Text Variables */
        --text-primary: #ffffff;
        --text-secondary: #e2e8f0;
        --text-muted: #cbd5e1; /* Lighter grey for readability */
    }

    /* GLOBAL TEXT OVERRIDES */
    .main-content { 
        margin-left: 250px; 
        padding: 30px; 
        background: var(--sa-bg); 
        min-height: 100vh; 
        font-family: 'Inter', sans-serif; 
        color: var(--text-secondary); 
    }

    /* Force all headings to white */
    h1, h2, h3, h4, h5, h6, .page-title {
        color: var(--text-primary) !important;
    }

    /* Override Bootstrap muted text which is too dark */
    .text-muted {
        color: var(--text-muted) !important;
    }

    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* Header Section */
    .page-header { display: flex; justify-content: space-between; align-items: end; margin-bottom: 30px; }
    .page-title { font-size: 1.75rem; font-weight: 800; letter-spacing: -0.5px; margin: 0; }
    .page-subtitle { color: var(--text-muted); font-size: 0.95rem; margin-top: 5px; }

    /* Modern Filter Bar */
    .filter-container {
        background: var(--sa-panel); 
        border: 1px solid var(--sa-border);
        padding: 10px; border-radius: 12px; display: flex; gap: 10px;
        align-items: center; margin-bottom: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    
    .search-box {
        background: #0f172a; 
        border: 1px solid var(--sa-border); 
        color: var(--text-primary); /* Ensure input text is white */
        padding: 10px 15px; border-radius: 8px; flex-grow: 1; max-width: 350px;
        transition: 0.3s;
    }
    .search-box::placeholder { color: #64748b; }
    .search-box:focus { border-color: var(--sa-accent); outline: none; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }

    .filter-select {
        background: #0f172a; 
        border: 1px solid var(--sa-border); 
        color: var(--text-primary);
        padding: 10px 35px 10px 15px; border-radius: 8px; cursor: pointer;
    }

    /* Professional Data Table */
    .table-card {
        background: var(--sa-panel); 
        border: 1px solid var(--sa-border);
        border-radius: 16px; overflow: hidden; 
        box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
    }

    .sa-table { width: 100%; border-collapse: collapse; }
    
    .sa-table th {
        background: #182235; 
        color: var(--text-muted); /* Lighter Header Text */
        text-transform: uppercase;
        font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; padding: 18px 24px;
        text-align: left; border-bottom: 1px solid var(--sa-border);
    }

    .sa-table td {
        padding: 20px 24px; 
        border-bottom: 1px solid var(--sa-border);
        vertical-align: middle; 
        font-size: 0.9rem; 
        color: var(--text-secondary); /* Explicit Row Text Color */
    }

    .sa-table tr:hover td { background: rgba(255,255,255,0.02); }
    .sa-table tr:last-child td { border-bottom: none; }

    /* Visual Elements */
    .company-logo {
        width: 42px; height: 42px; border-radius: 10px; 
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 1.1rem; text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .info-row { display: flex; align-items: center; gap: 8px; color: var(--text-muted); font-size: 0.85rem; margin-top: 4px; }
    
    .revenue-text { color: #34d399; font-weight: 700; font-size: 1rem; letter-spacing: 0.5px; }
    .sub-text { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; margin-top: 4px; }

    /* Badges */
    .badge-status {
        padding: 6px 12px; border-radius: 30px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px;
    }
    .badge-active { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
    .badge-active::before { content: ''; width: 6px; height: 6px; background: #34d399; border-radius: 50%; }
    
    .badge-suspended { background: rgba(244, 63, 94, 0.15); color: #f43f5e; border: 1px solid rgba(244, 63, 94, 0.2); }
    .badge-suspended::before { content: ''; width: 6px; height: 6px; background: #f43f5e; border-radius: 50%; }

    /* Buttons */
    .btn-create {
        background: var(--sa-accent); color: white; padding: 12px 24px; border-radius: 10px;
        font-weight: 600; border: none; transition: 0.2s; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }
    .btn-create:hover { background: #4f46e5; transform: translateY(-2px); color: white; }

    .btn-icon {
        width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--sa-border);
        background: transparent; color: var(--text-secondary); display: flex; align-items: center; justify-content: center;
        transition: 0.2s;
    }
    .btn-icon:hover { background: white; color: #0f172a; border-color: white; }
    .btn-icon.danger:hover { background: #ef4444; color: white; border-color: #ef4444; }

    /* Modal Fixes */
    .modal-content {
        background-color: #1e293b;
        color: white;
        border: 1px solid #334155;
    }
    .modal-header { border-bottom: 1px solid #334155; }
    .modal-title { color: white; }
    .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
    
    /* Ensure modal inputs are readable */
    .form-control {
        background-color: #0f172a !important;
        border: 1px solid #334155 !important;
        color: white !important;
    }
    .form-control:focus {
        border-color: var(--sa-accent) !important;
        box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25) !important;
    }
    .form-label { color: var(--text-muted) !important; }

</style>

<main class="main-content">
    
    <div class="page-header">
        <div>
            <h1 class="page-title">Tenant Management</h1>
            <p class="page-subtitle">Oversee registered companies, manage access, and monitor performance.</p>
        </div>
        <button class="btn-create" data-bs-toggle="modal" data-bs-target="#createCompanyModal">
            <i class="fa-solid fa-plus me-2"></i> Onboard New Tenant
        </button>
    </div>

    <form method="GET" class="filter-container">
        <i class="fa-solid fa-search ms-3 text-muted"></i>
        <input type="text" name="search" class="search-box" placeholder="Search by Company Name, Email..." value="<?= htmlspecialchars($search) ?>">
        
        <div class="vr mx-2 bg-secondary opacity-25"></div>
        
        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Companies</option>
            <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
        </select>

        <div class="ms-auto me-3 small text-muted fw-bold">
            Showing <?= count($companies) ?> Results
        </div>
    </form>

    <div class="table-card">
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th width="30%">Company Profile</th>
                        <th>Admin Contact</th>
                        <th>Subscription</th>
                        <th>Revenue</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($companies)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-white">No companies found matching your criteria.</td></tr>
                    <?php else: foreach($companies as $co): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="company-logo"><?= strtoupper(substr($co['name'], 0, 1)) ?></div>
                                <div>
                                    <div class="fw-bold text-white fs-6"><?= htmlspecialchars($co['name']) ?></div>
                                    <div class="info-row"><i class="fa-solid fa-hashtag"></i> ID: <?= $co['company_id'] ?></div>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <div class="fw-medium text-white"><?= htmlspecialchars($co['email']) ?></div>
                            <div class="info-row">
                                <i class="fa-solid fa-phone small"></i> <?= htmlspecialchars($co['contact'] ?? 'No Phone') ?>
                            </div>
                        </td>

                        <td>
                            <span class="badge bg-dark border border-secondary text-white px-2 py-1 rounded">Standard Plan</span>
                            <div class="sub-text mt-2">
                                <i class="fa-solid fa-users me-1"></i> <?= $co['user_count'] ?> Users
                            </div>
                        </td>

                        <td>
                            <div class="revenue-text">
                                $<?= number_format((float)($co['total_revenue'] ?? 0), 2) ?>
                            </div>
                            <div class="sub-text"><?= $co['invoice_count'] ?> Invoices</div>
                        </td>

                        <td>
                            <?php if(($co['status'] ?? 'active') === 'active'): ?>
                                <span class="badge-status badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge-status badge-suspended">Suspended</span>
                            <?php endif; ?>
                        </td>

                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="company-report.php?id=<?= $co['company_id'] ?>" class="btn-icon" title="View Details"><i class="fa-solid fa-chart-pie"></i></a>
                                <a href="company-details.php?id=<?= $co['company_id'] ?>" class="btn-icon" title="Edit Company"><i class="fa-solid fa-pen"></i></a>
                                
                                <form method="POST" onsubmit="return confirm('Are you sure you want to change the status of this company?');">
                                    <input type="hidden" name="toggle_status" value="1">
                                    <input type="hidden" name="company_id" value="<?= $co['company_id'] ?>">
                                    <input type="hidden" name="status" value="<?= ($co['status'] ?? 'active') === 'active' ? 'suspended' : 'active' ?>">
                                    <button class="btn-icon danger" title="Toggle Access">
                                        <i class="fa-solid fa-power-off"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<div class="modal fade" id="createCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg"> <div class="modal-content shadow-lg" style="background-color: #1e293b; border: 1px solid #334155;">
            <div class="modal-header" style="border-bottom: 1px solid #334155;">
                <h5 class="modal-title fw-bold text-white">Register New Tenant</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="create_company" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">COMPANY NAME</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Acme Corp" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">ADMIN EMAIL</label>
                            <input type="email" name="email" class="form-control" placeholder="admin@company.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">CONTACT NUMBER</label>
                            <input type="text" name="phone" class="form-control" placeholder="+1 234 567 890">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">GST / VAT NUMBER</label>
                        <input type="text" name="gst_vat" class="form-control" placeholder="Optional Tax ID">
                    </div>

                    <div class="p-3 rounded mb-4" style="background: rgba(0,0,0,0.2); border: 1px solid #334155;">
                        <label class="form-label small fw-bold text-white mb-3"><i class="fa-solid fa-location-dot me-2"></i> LOCATION DETAILS</label>
                        
                        <div class="mb-3">
                            <input type="text" name="address" class="form-control" placeholder="Street Address, Building, Suite">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <input type="text" name="city" class="form-control" placeholder="City">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="postal_code" class="form-control" placeholder="Postal / Zip Code">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="state" class="form-control" placeholder="State / Province">
                            </div>
                            <div class="col-md-6">
                                <input type="text" name="country" class="form-control" placeholder="Country">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-create w-100 fw-bold py-3">
                        <i class="fa-solid fa-check-circle me-2"></i> Create Tenant & Send Invite
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>