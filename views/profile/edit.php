<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ProfileController.php';

// Init
$profileCtrl = new ProfileController();
$profileCtrl->updateProfile(); // Handles POST requests
$user = $profileCtrl->getProfile();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php'; // Works for all roles if sidebar is dynamic
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* Profile Card */
    .profile-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; padding: 30px 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    
    .avatar-wrapper { position: relative; width: 120px; height: 120px; margin: 0 auto 20px; }
    .profile-avatar { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 4px solid #f1f5f9; }
    .avatar-upload-btn { 
        position: absolute; bottom: 5px; right: 5px; 
        background: #4f46e5; color: white; border: 2px solid white; 
        width: 32px; height: 32px; border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; 
        cursor: pointer; font-size: 14px; transition: 0.2s;
    }
    .avatar-upload-btn:hover { transform: scale(1.1); background: #4338ca; }

    /* Tabs */
    .nav-tabs .nav-link { color: #64748b; font-weight: 600; border: none; padding: 12px 20px; }
    .nav-tabs .nav-link.active { color: #4f46e5; border-bottom: 2px solid #4f46e5; background: transparent; }
    .tab-content { background: white; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; padding: 30px; }
    .tab-header { background: white; border: 1px solid #e2e8f0; border-radius: 12px 12px 0 0; padding: 10px 20px 0; }
</style>

<main class="main-content">

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="profile-card">
                <form id="avatarForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_info">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($user['name']) ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($user['phone']) ?>">
                    <input type="hidden" name="current_avatar" value="<?= $user['avatar'] ?? '' ?>">

                    <div class="avatar-wrapper">
                        <?php 
                            $avatarUrl = !empty($user['avatar']) ? BASE_URL . 'assets/uploads/avatars/' . $user['avatar'] : 'https://ui-avatars.com/api/?name='.urlencode($user['name']).'&background=random&size=128';
                        ?>
                        <img src="<?= $avatarUrl ?>" alt="Profile" class="profile-avatar">
                        <label for="avatarInput" class="avatar-upload-btn" title="Change Avatar">
                            <i class="fa-solid fa-camera"></i>
                        </label>
                        <input type="file" id="avatarInput" name="avatar" class="d-none" accept="image/*" onchange="document.getElementById('saveAvatarBtn').click()">
                    </div>
                    <button type="submit" id="saveAvatarBtn" class="d-none"></button>
                </form>

                <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                <p class="text-muted small mb-3"><?= htmlspecialchars($user['email']) ?></p>
                
                <?php 
                    $roleColor = match($user['role']) { 'superadmin'=>'danger', 'admin'=>'primary', 'staff'=>'info', default=>'success' };
                ?>
                <span class="badge bg-<?= $roleColor ?>-subtle text-<?= $roleColor ?> text-uppercase px-3 py-2 rounded-pill border border-<?= $roleColor ?>-subtle">
                    <?= $user['role'] ?>
                </span>

                <div class="mt-4 pt-4 border-top text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-muted fw-bold">Joined</span>
                        <span class="small text-dark"><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="small text-muted fw-bold">Phone</span>
                        <span class="small text-dark"><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-success mb-3" role="alert">
                    <i class="fa-solid fa-check-circle me-2"></i> 
                    <?= $_GET['success'] == 'password_changed' ? 'Password updated successfully.' : 'Profile details updated.' ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-danger mb-3" role="alert">
                    <i class="fa-solid fa-exclamation-circle me-2"></i> 
                    <?= $_GET['error'] == 'mismatch' ? 'New passwords do not match.' : 'Incorrect current password.' ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="tab-header">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button">Edit Details</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button">Security</button>
                    </li>
                </ul>
            </div>
            
            <div class="tab-content shadow-sm" id="myTabContent">
                
                <div class="tab-pane fade show active" id="details">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_info">
                        <input type="hidden" name="current_avatar" value="<?= $user['avatar'] ?? '' ?>">

                        <h6 class="fw-bold text-dark mb-4">Personal Information</h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Full Name</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Email Address</label>
                            <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly title="Contact Admin to change email">
                            <div class="form-text small"><i class="fa-solid fa-lock me-1"></i> Email cannot be changed directly.</div>
                        </div>

                        <?php if($user['role'] === 'client'): ?>
                            <hr class="my-4">
                            <h6 class="fw-bold text-dark mb-3">Billing Address</h6>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Street Address</label>
                                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">City</label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">State</label>
                                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Postal Code</label>
                                    <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>">
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary fw-bold px-4">Save Changes</button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade" id="security">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <h6 class="fw-bold text-dark mb-4">Change Password</h6>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required minlength="6">
                            </div>
                        </div>

                        <div class="alert alert-light border small text-muted">
                            <i class="fa-solid fa-shield-halved me-2 text-primary"></i> 
                            Make sure your password is at least 6 characters long and uses a mix of letters and numbers.
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-dark fw-bold px-4">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>