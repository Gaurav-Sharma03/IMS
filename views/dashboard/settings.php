<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/SettingsController.php';

$settingsCtrl = new SettingsController();
$settingsCtrl->updateSettings(); 
$data = $settingsCtrl->getSettings();

$user = $data['user'];
$org = $data['org']; 

$pageTitle = "Settings";
include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    .settings-card { border: none; box-shadow: 0 2px 6px rgba(0,0,0,0.02); border-radius: 12px; overflow: hidden; background: white; }
    .nav-settings .nav-link { color: #64748b; font-weight: 600; padding: 15px 25px; border-radius: 0; border-bottom: 2px solid transparent; }
    .nav-settings .nav-link:hover { background: #f8fafc; color: #334155; }
    .nav-settings .nav-link.active { color: #4f46e5; border-bottom-color: #4f46e5; background: #fdfdfd; }
    
    .avatar-upload { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 3px solid #f1f5f9; }
    .logo-preview { height: 60px; object-fit: contain; margin-top: 10px; border: 1px solid #eee; padding: 5px; border-radius: 6px; }
    
    .form-label { font-weight: 600; font-size: 0.85rem; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { padding: 10px 15px; border-color: #e2e8f0; }
    .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    
    .section-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin-bottom: 15px; border-left: 4px solid #4f46e5; padding-left: 10px; }
    .btn-save { padding: 10px 30px; font-weight: 600; background: #4f46e5; border: none; }
    .btn-save:hover { background: #4338ca; }
</style>

<main class="main-content">
    
    <div class="mb-4">
        <h4 class="fw-bold text-dark m-0">Settings</h4>
        <p class="text-muted small">Manage your profile, organization, and preferences.</p>
    </div>

    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-check-circle me-2"></i> Settings updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($_GET['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-3">
            <div class="card settings-card sticky-top" style="top: 90px;">
                <div class="list-group list-group-flush nav-settings" id="settingsTab" role="tablist">
                    <a class="list-group-item nav-link active" id="profile-tab" data-bs-toggle="list" href="#profile" role="tab">
                        <i class="fa-regular fa-user me-2"></i> My Profile
                    </a>
                    <a class="list-group-item nav-link" id="org-tab" data-bs-toggle="list" href="#organization" role="tab">
                        <i class="fa-solid fa-building me-2"></i> 
                        <?= ($_SESSION['role'] == 'client') ? 'Billing Details' : 'Company Info' ?>
                    </a>
                    <a class="list-group-item nav-link" id="security-tab" data-bs-toggle="list" href="#security" role="tab">
                        <i class="fa-solid fa-shield-halved me-2"></i> Security
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="tab-content" id="nav-tabContent">
                
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <div class="card settings-card p-4">
                        <h6 class="section-title">Personal Information</h6>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="update_type" value="profile">
                            
                            <div class="row mb-4 align-items-center">
                                <div class="col-auto">
                                    <?php if(!empty($user['avatar'])): ?>
                                        <img src="<?= BASE_URL ?>assets/uploads/avatars/<?= $user['avatar'] ?>" class="avatar-upload" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar-upload bg-light d-flex align-items-center justify-content-center text-secondary fs-1 fw-bold">
                                            <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col">
                                    <label class="form-label">Change Photo</label>
                                    <input type="file" name="avatar" class="form-control form-control-sm w-75">
                                    <div class="form-text text-muted small">Recommended size: 200x200px (JPG, PNG)</div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Account Role</label>
                                    <input type="text" class="form-control bg-light" value="<?= ucfirst($user['role']) ?>" readonly>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="organization" role="tabpanel">
                    <div class="card settings-card p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="update_type" value="organization">

                            <h6 class="section-title">Business Identity</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                  
    <label class="form-label">Company Logo</label>
    <div class="d-flex align-items-center gap-3">
        <input type="file" name="org_logo" class="form-control w-50">
        
        <?php 
            // 1. Get filename safely (handles undefined array key for Clients)
            $logoFile = $org['logo'] ?? ''; 

            // 2. Check if file physically exists on the server
            // Use __DIR__ to navigate from 'views/dashboard' to 'assets/uploads/logos'
            $physicalPath = __DIR__ . '/../../assets/uploads/logos/' . $logoFile;
            
            if (!empty($logoFile) && file_exists($physicalPath)): 
        ?>
            <div class="p-1 border rounded bg-white">
                <img src="<?= BASE_URL ?>assets/uploads/logos/<?= htmlspecialchars($logoFile) ?>" 
                     class="logo-preview" 
                     style="height: 50px; width: auto; object-fit: contain;" 
                     alt="Company Logo">
            </div>
        <?php endif; ?>
    </div>
</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Company Name</label>
                                    <input type="text" name="org_name" class="form-control" value="<?= htmlspecialchars($org['name'] ?? '') ?>" required>
                                </div>
                                
                                <?php if($_SESSION['role'] === 'client'): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($org['contact_person'] ?? '') ?>">
                                </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <label class="form-label">Official Email</label>
                                    <input type="email" name="org_email" class="form-control" value="<?= htmlspecialchars($org['email'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="org_phone" class="form-control" value="<?= htmlspecialchars($org['contact'] ?? $org['phone'] ?? '') ?>">
                                </div>
                            </div>

                            <h6 class="section-title">Location & Address</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label">Street Address</label>
                                    <input type="text" name="street" class="form-control" value="<?= htmlspecialchars($org['street'] ?? '') ?>" placeholder="123 Business Blvd">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($org['city'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">State / Province</label>
                                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($org['state'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Postal / Zip Code</label>
                                    <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($org['postal_code'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Country</label>
                                    <input type="text" name="country" class="form-control" value="<?= htmlspecialchars($org['country'] ?? '') ?>">
                                </div>
                            </div>

                            <h6 class="section-title">Tax & Legal</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">GST / VAT Number</label>
                                    <input type="text" name="gst_vat" class="form-control" value="<?= htmlspecialchars($org['gst_vat'] ?? '') ?>" placeholder="e.g. US123456789">
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top text-end">
                                <button type="submit" class="btn btn-primary btn-save">Update Information</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="card settings-card p-4">
                        <h6 class="section-title">Security Settings</h6>
                        <form method="POST">
                            <input type="hidden" name="update_type" value="security">
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-danger btn-save">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>