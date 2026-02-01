<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireRole(['superadmin']);

$db = (new Database())->connect();
$company_id = $_GET['id'] ?? 0;

// Handle Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_company'])) {
    $sql = "UPDATE companies SET 
            name = ?, email = ?, contact = ?, gst_vat = ?, 
            address = ?, city = ?, state = ?, country = ?, postal_code = ?, 
            status = ? 
            WHERE company_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $_POST['name'], 
        $_POST['email'], 
        $_POST['contact'], 
        $_POST['gst_vat'],
        $_POST['address'], 
        $_POST['city'], 
        $_POST['state'], 
        $_POST['country'], 
        $_POST['postal_code'],
        $_POST['status'], 
        $company_id
    ]);
    
    header("Location: company-details.php?id=$company_id&success=updated"); exit;
}

// Fetch Company Data
$stmt = $db->prepare("SELECT * FROM companies WHERE company_id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) die("Company not found.");

// Fetch Users (Admins & Staff)
$users = $db->query("SELECT * FROM users WHERE company_id = $company_id ORDER BY role, name")->fetchAll(PDO::FETCH_ASSOC);

// Fetch Clients (Safely)
$clients = [];
try {
    $clients = $db->query("SELECT * FROM clients WHERE company_id = $company_id ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* Ignore if table empty or missing */ }

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    /* HIGH CONTRAST DARK THEME */
    :root {
        --sa-bg: #0f172a;       /* Dark Background */
        --sa-panel: #1e293b;    /* Panel Background */
        --sa-border: #334155;   /* Borders */
        --text-white: #ffffff;  /* Headings */
        --text-light: #e2e8f0;  /* Body text */
        --text-muted: #cbd5e1;  /* Secondary text */
        --accent: #6366f1;      /* Indigo */
    }

    .main-content { 
        margin-left: 250px; padding: 30px; 
        background: var(--sa-bg); min-height: 100vh; 
        font-family: 'Inter', sans-serif; color: var(--text-light); 
    }
    
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* Cards */
    .card-dark {
        background: var(--sa-panel); border: 1px solid var(--sa-border);
        border-radius: 16px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);
    }
    
    .card-title { color: var(--text-white); font-weight: 700; margin-bottom: 20px; font-size: 1.1rem; }

    /* Forms */
    .form-label { 
        color: var(--text-muted); font-size: 0.75rem; 
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; 
    }
    
    .form-control, .form-select {
        background-color: #020617; /* Very dark input bg */
        border: 1px solid var(--sa-border);
        color: var(--text-white); 
        padding: 10px 15px; border-radius: 8px;
    }
    
    .form-control:focus, .form-select:focus {
        background-color: #020617; color: var(--text-white);
        border-color: var(--accent); box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
    }

    /* Tabs */
    .nav-tabs { border-bottom: 1px solid var(--sa-border); margin-bottom: 20px; }
    .nav-link { color: var(--text-muted); font-weight: 600; border: none; padding: 12px 20px; }
    .nav-link:hover { color: var(--text-white); }
    .nav-link.active {
        color: var(--accent); background: transparent;
        border-bottom: 2px solid var(--accent);
    }

    /* Table */
    .sa-table { width: 100%; border-collapse: collapse; }
    .sa-table th {
        text-align: left; padding: 12px 15px; color: var(--text-muted);
        font-size: 0.75rem; text-transform: uppercase; font-weight: 700;
        border-bottom: 1px solid var(--sa-border); background: rgba(255,255,255,0.02);
    }
    .sa-table td {
        padding: 16px 15px; border-bottom: 1px solid var(--sa-border);
        color: var(--text-light); vertical-align: middle; font-size: 0.9rem;
    }
    .sa-table tr:last-child td { border-bottom: none; }
    
    /* Badges & Buttons */
    .badge-role { padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; }
    .role-admin { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .role-staff { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
    
    .btn-save {
        background: var(--accent); color: white; border: none; 
        padding: 12px; border-radius: 8px; font-weight: 600; width: 100%; transition: 0.2s;
    }
    .btn-save:hover { background: #4f46e5; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }

    .btn-back {
        text-decoration: none; color: var(--text-muted); font-weight: 600; font-size: 0.9rem;
        padding: 8px 16px; border: 1px solid var(--sa-border); border-radius: 8px;
    }
    .btn-back:hover { background: var(--sa-border); color: var(--text-white); }

</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold m-0 text-white"><?= htmlspecialchars($company['name'] ?? 'Unknown Company') ?></h2>
            <div class="text-white  mt-1 small">
                <i class="fa-solid fa-hashtag me-1"></i> <?= $company['company_id'] ?> &bull; 
                <i class="fa-regular fa-calendar me-1 ms-2"></i> Joined <?= date('M d, Y', strtotime($company['created_at'])) ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="companies.php" class="btn-back"><i class="fa-solid fa-arrow-left me-2"></i> Back List</a>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-xl-4 col-lg-5">
            <div class="card-dark h-100">
                <h5 class="card-title"><i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Company Profile</h5>
                
                <form method="POST">
                    <input type="hidden" name="update_company" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($company['name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($company['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Contact / Phone</label>
                            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($company['contact'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">GST / VAT Number</label>
                        <input type="text" name="gst_vat" class="form-control" value="<?= htmlspecialchars($company['gst_vat'] ?? '') ?>">
                    </div>

                    <div class="mb-3 p-3 rounded" style="background: rgba(0,0,0,0.2); border: 1px solid var(--sa-border);">
                        <label class="form-label text-white mb-2">Location Details</label>
                        
                        <div class="mb-2">
                            <input type="text" name="address" class="form-control mb-2" placeholder="Street Address" value="<?= htmlspecialchars($company['address'] ?? '') ?>">
                        </div>
                        
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <input type="text" name="city" class="form-control" placeholder="City" value="<?= htmlspecialchars($company['city'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <input type="text" name="postal_code" class="form-control" placeholder="Postal Code" value="<?= htmlspecialchars($company['postal_code'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="state" class="form-control" placeholder="State" value="<?= htmlspecialchars($company['state'] ?? '') ?>">
                            </div>
                            <div class="col-6">
                                <input type="text" name="country" class="form-control" placeholder="Country" value="<?= htmlspecialchars($company['country'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Account Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= ($company['status'] ?? 'active') == 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="suspended" <?= ($company['status'] ?? '') == 'suspended' ? 'selected' : '' ?>>Suspended</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-save">Save Changes</button>
                </form>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card-dark h-100">
                
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#staff-tab">
                            <i class="fa-solid fa-users-gear me-2"></i> Staff & Admins 
                            <span class="badge bg-secondary ms-1 text-dark"><?= count($users) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#client-tab">
                            <i class="fa-solid fa-briefcase me-2"></i> Clients 
                            <span class="badge bg-secondary ms-1 text-dark"><?= count($clients) ?></span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    
                    <div class="tab-pane fade show active" id="staff-tab">
                        <div class="table-responsive">
                            <table class="sa-table">
                                <thead>
                                    <tr>
                                        <th>User Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th class="text-end">Joined</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($users)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No users found.</td></tr>
                                    <?php else: foreach($users as $u): ?>
                                    <tr>
                                        <td class="fw-bold text-white">
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 28px; height: 28px; background: #334155; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                                                    <?= strtoupper(substr($u['name'] ?? 'U', 0, 1)) ?>
                                                </div>
                                                <?= htmlspecialchars($u['name'] ?? 'Unknown') ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                        <td>
                                            <?php if(($u['role'] ?? '') === 'admin'): ?>
                                                <span class="badge-role role-admin">Admin</span>
                                            <?php else: ?>
                                                <span class="badge-role role-staff">Staff</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end text-white small"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="client-tab">
                        <div class="table-responsive">
                            <table class="sa-table">
                                <thead>
                                    <tr>
                                        <th>Client Name</th>
                                        <th>Details</th>
                                        <th>Location</th>
                                        <th class="text-end">Since</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($clients)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-white">No clients added yet.</td></tr>
                                    <?php else: foreach($clients as $cl): ?>
                                    <tr>
                                        <td class="fw-bold text-white"><?= htmlspecialchars($cl['name'] ?? 'Unknown') ?></td>
                                        <td>
                                            <div class="text-white small"><?= htmlspecialchars($cl['email'] ?? '-') ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($cl['contact_person'] ?? '-') ?></div>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($cl['city'] ?? '') ?> 
                                            <?= !empty($cl['country']) ? '('.htmlspecialchars($cl['country']).')' : '' ?>
                                        </td>
                                        <td class="text-end text-white small"><?= date('M d, Y', strtotime($cl['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>