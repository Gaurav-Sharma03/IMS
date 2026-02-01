<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SuperAdminController.php';

requireRole(['superadmin']);

$controller = new SuperAdminController();

// --- HANDLE POST ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create
    if (isset($_POST['create_user'])) {
        $controller->createUser($_POST);
        header("Location: users.php?success=created"); exit;
    }
    // Update
    if (isset($_POST['update_user'])) {
        $controller->updateUser($_POST);
        header("Location: users.php?success=updated"); exit;
    }
    // Toggle Status
    if (isset($_POST['toggle_status'])) {
        $controller->toggleUserStatus($_POST['user_id'], $_POST['status']);
        header("Location: users.php?success=status_changed"); exit;
    }
    // Delete
    if (isset($_POST['delete_user'])) {
        $controller->deleteUser($_POST['user_id']);
        header("Location: users.php?success=deleted"); exit;
    }
}

// --- VIEW LOGIC ---
$currentRoleTab = $_GET['tab'] ?? 'all'; 
$search = $_GET['search'] ?? '';
$companyFilter = $_GET['company_id'] ?? '';

// Map Tab to DB Role
$dbRole = '';
if ($currentRoleTab === 'superadmin') $dbRole = 'superadmin';
if ($currentRoleTab === 'admin') $dbRole = 'admin';
if ($currentRoleTab === 'staff') $dbRole = 'staff';

// Fetch Data
$companies = $controller->getAllCompanies();
$users = $controller->getAllUsers($search, $dbRole, $companyFilter);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root { 
        --sa-bg: #0f172a; 
        --sa-panel: #1e293b; 
        --sa-border: rgba(255,255,255,0.06); 
        --text-white: #ffffff; 
        --accent: #6366f1; 
    }
    
    .main-content { 
        margin-left: var(--sidebar-width); 
        padding: 2rem; 
        background: var(--sa-bg); 
        min-height: 100vh; 
        color: white; 
        transition: all 0.3s ease;
    }

    /* --- RESPONSIVE LAYOUT ADJUSTMENTS --- */
    @media (max-width: 992px) {
        .main-content { margin-left: 0 !important; padding: 1.25rem; }
    }

    /* Tabs - Scrollable on mobile */
    .nav-tabs { 
        border-bottom: 1px solid var(--sa-border); 
        margin-bottom: 25px; 
        flex-wrap: nowrap; 
        overflow-x: auto; 
        overflow-y: hidden;
    }
    .nav-link { 
        white-space: nowrap; 
        font-weight: 600; 
        padding: 12px 20px; 
        border: none; 
        background: transparent; 
        color: rgba(255,255,255,0.6);
    }
    .nav-link.active { color: var(--accent) !important; border-bottom: 2px solid var(--accent); }



    
    /* Filter Bar - Responsive Grid */
    .filter-card {
        background: var(--sa-panel); 
        border: 1px solid var(--sa-border); 
        padding: 1rem; 
        border-radius: 12px;
        display: flex; 
        flex-wrap: wrap; 
        gap: 1rem; 
        align-items: center; 
        margin-bottom: 25px;
    }
    .search-group { display: flex; align-items: center; flex: 1; min-width: 250px; }
    .search-input { 
        background: #020617; border: 1px solid var(--sa-border); 
        color: white; padding: 8px 15px; border-radius: 8px; width: 100%; 
    }
    .filter-select { 
        background: #020617; border: 1px solid var(--sa-border); 
        color: white; padding: 8px 15px; border-radius: 8px; 
        width: 100%; max-width: 200px;
    }

    @media (max-width: 576px) {
        .filter-select { max-width: 100%; }
        .search-group { min-width: 100%; }
    }

    /* Table Container */
    .table-card { 
        background: var(--sa-panel); 
        border: 1px solid var(--sa-border); 
        border-radius: 16px; 
        overflow: hidden; 
    }
    .sa-table { width: 100%; border-collapse: collapse; }
    .sa-table th { 
        text-align: left; padding: 15px 20px; color: var(--text-white); 
        font-size: 0.75rem; text-transform: uppercase; font-weight: 700; 
        border-bottom: 1px solid var(--sa-border); background: rgba(0,0,0,0.2); 
    }
    .sa-table td { padding: 15px 20px; border-bottom: 1px solid var(--sa-border); color: #e2e8f0; font-size: 0.9rem; }

    /* Hide less important columns on mobile to keep it clean */
    @media (max-width: 768px) {
        .col-context { display: none; }
        .sa-table th, .sa-table td { padding: 12px 15px; }
    }

    /* Badges */
    .role-badge { padding: 4px 8px; border-radius: 6px; font-size: 10px; font-weight: 700; }
    .r-super { background: rgba(236, 72, 153, 0.1); color: #f472b6; border: 1px solid rgba(236, 72, 153, 0.2); }
    .r-admin { background: rgba(250, 204, 21, 0.1); color: #facc15; border: 1px solid rgba(250, 204, 21, 0.2); }
    .r-staff { background: rgba(96, 165, 250, 0.1); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.2); }

    /* Modal Mobile Optimization */
    .modal-content { background-color: #1e293b; color: white; border: 1px solid #334155; }
    .form-label { color: var(--text-white); font-size: 0.75rem; font-weight: 700; }
</style>

<main class="main-content">
    
    <div class="d-flex flex-row flex-md-row justify-content-between align-items-md-center gap-3 mb-5">
    <div class="header-content">
        <h2 class="fw-bold m-0 text-white" style="letter-spacing: -0.5px;">User Management</h2>
        <p class="mb-0 mt-1" style="color: #94a3b8; font-size: 0.85rem;">
            Control global system access, define tenant roles, and monitor security.
        </p>
    </div>
    
    <div class="header-actions">
        <button class="btn btn-primary fw-bold shadow-sm d-flex align-items-center justify-content-center" 
                onclick="openCreateModal()" 
                style="padding: 10px 24px; border-radius: 10px; min-width: 160px;">
            <i class="fa-solid fa-user-plus me-2" style="font-size: 0.9rem;"></i> 
            <span>Add New User</span>
        </button>
    </div>
</div>

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link <?= $currentRoleTab === 'all' ? 'active' : '' ?>" href="?tab=all">All Users</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentRoleTab === 'superadmin' ? 'active' : '' ?>" href="?tab=superadmin">Superadmins</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentRoleTab === 'admin' ? 'active' : '' ?>" href="?tab=admin">Tenant Admins</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $currentRoleTab === 'staff' ? 'active' : '' ?>" href="?tab=staff">Staff</a>
        </li>
    </ul>

    <form method="GET" class="filter-card">
        <input type="hidden" name="tab" value="<?= $currentRoleTab ?>">
        <div class="search-group">
            <i class="fa-solid fa-search text-dim me-2"></i>
            <input type="text" name="search" class="search-input" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
        </div>
        
        <?php if($currentRoleTab !== 'superadmin'): ?>
        <select name="company_id" class="filter-select" onchange="this.form.submit()">
            <option value="">All Companies</option>
            <?php foreach($companies as $c): ?>
                <option value="<?= $c['company_id'] ?>" <?= $companyFilter == $c['company_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <div class="ms-sm-auto text-white-50 small fw-bold">Showing <?= count($users) ?> result(s)</div>
    </form>

    <div class="table-card">
        <div class="table-responsive">
            <table class="sa-table">
                <thead>
                    <tr>
                        <th width="35%">Identity</th>
                        <th>Role</th>
                        <th class="col-context">Organization</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="5" class="text-center py-5">No users found.</td></tr>
                    <?php else: foreach($users as $u): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold shadow-sm" style="width:32px; height:32px; font-size:0.75rem; flex-shrink:0;">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </div>
                                    <div class="text-truncate">
                                        <div class="fw-bold text-white small"><?= htmlspecialchars($u['name']) ?></div>
                                        <div class="smaller text-white-50 d-none d-md-block"><?= htmlspecialchars($u['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php $rc = match($u['role']) { 'superadmin'=>'r-super', 'admin'=>'r-admin', default=>'r-staff' }; ?>
                                <span class="role-badge <?= $rc ?>"><?= substr($u['role'], 0, 5) ?>..</span>
                            </td>
                            <td class="col-context small">
                                <?= $u['role'] === 'superadmin' ? 'Global' : htmlspecialchars($u['company_name'] ?? 'System') ?>
                            </td>
                            <td>
                                <span class="badge rounded-pill <?= ($u['status'] ?? 'active') === 'active' ? 'bg-success' : 'bg-danger' ?>" style="font-size: 8px;">&nbsp;</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <button class="btn btn-sm text-white opacity-50 p-1" onclick='openEditModal(<?= json_encode($u) ?>)'><i class="fa-solid fa-pen-to-square"></i></button>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="status" value="<?= ($u['status'] ?? 'active') === 'active' ? 'suspended' : 'active' ?>">
                                        <button class="btn btn-sm text-warning opacity-75 p-1"><i class="fa-solid fa-power-off"></i></button>
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

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-white" id="modalTitle">Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <form method="POST" id="userForm">
                    <input type="hidden" name="create_user" id="actionInput" value="1">
                    <input type="hidden" name="user_id" id="userId">

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" id="uName" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="uEmail" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="uPass" class="form-control" placeholder="Leave empty to keep current (Edit mode)">
                    </div>

                    <div class="row mb-4">
                        <div class="col-6">
                            <label class="form-label">Role</label>
                            <select name="role" id="uRole" class="form-select" onchange="toggleCompanyField()" required>
                                <option value="staff">Staff</option>
                                <option value="admin">Tenant Admin</option>
                                <option value="superadmin">Superadmin</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Company</label>
                            <select name="company_id" id="uCompany" class="form-select">
                                <option value="">None / System</option>
                                <?php foreach($companies as $c): ?>
                                    <option value="<?= $c['company_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2" id="modalBtn">Create User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    const modal = new bootstrap.Modal(document.getElementById('userModal'));
    const companySelect = document.getElementById('uCompany');
    const roleSelect = document.getElementById('uRole');

    function toggleCompanyField() {
        if (roleSelect.value === 'superadmin') {
            companySelect.value = '';
            companySelect.disabled = true;
        } else {
            companySelect.disabled = false;
        }
    }

    function openCreateModal() {
        document.getElementById('modalTitle').innerText = 'Add New User';
        document.getElementById('actionInput').name = 'create_user';
        document.getElementById('userId').value = '';
        document.getElementById('uName').value = '';
        document.getElementById('uEmail').value = '';
        document.getElementById('uPass').required = true; // Password req for new users
        document.getElementById('modalBtn').innerText = 'Create User';
        document.getElementById('userForm').reset();
        toggleCompanyField();
        modal.show();
    }

    function openEditModal(user) {
        document.getElementById('modalTitle').innerText = 'Edit User';
        document.getElementById('actionInput').name = 'update_user';
        document.getElementById('userId').value = user.id;
        document.getElementById('uName').value = user.name;
        document.getElementById('uEmail').value = user.email;
        document.getElementById('uPass').required = false; // Password opt for edits
        document.getElementById('uRole').value = user.role;
        document.getElementById('uCompany').value = user.company_id || '';
        document.getElementById('modalBtn').innerText = 'Save Changes';
        toggleCompanyField();
        modal.show();
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>