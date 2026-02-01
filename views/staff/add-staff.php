<?php
// 1. Load Dependencies
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';

// 2. Security Check (Only Admin/Superadmin)
requireRole(['admin', 'superadmin']);

// 3. Initialize Objects
$db = new Database();
$conn = $db->connect();
$userModel = new User($conn);

$error = "";
$success = "";
$defaultPassword = "Staff@" . date("Y"); // Example: Staff@2026

// 4. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    // Basic Validation
    if (empty($name) || empty($email)) {
        $error = "Name and Email are required.";
    } elseif ($userModel->emailExists($email)) {
        $error = "This email is already registered.";
    } else {
        
        // Prepare Data for Registration
        $staffData = [
            'name'       => $name,
            'email'      => $email,
            'password'   => password_hash($defaultPassword, PASSWORD_DEFAULT), // Hash the default password
            'phone'      => $phone,
            'role'       => 'staff',
            'status'     => 'pending', // CRITICAL: Forces password reset on first login
            'company_id' => $_SESSION['company_id'] // Link to Admin's Company
        ];

        // Create Staff User
        if ($userModel->register($staffData)) {
            $success = "Staff member added successfully! <br> 
                        <strong>Temporary Password:</strong> " . $defaultPassword;
        } else {
            $error = "Failed to add staff member.";
        }
    }
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    /* Content Layout */
    .main-content { margin-left: 250px; padding: 30px; background: #f9fafb; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    .page-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        max-width: 800px;
        margin: 0 auto;
    }

    .card-header {
        padding: 25px 30px;
        border-bottom: 1px solid #e5e7eb;
        background: #fcfcfc;
        border-radius: 12px 12px 0 0;
    }

    .card-body { padding: 30px; }
    
    .form-label { font-weight: 600; font-size: 13px; color: #374151; margin-bottom: 8px; }
    .form-control { padding: 12px 15px; border-radius: 8px; border: 1px solid #d1d5db; font-size: 14px; }
    .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }

    .info-box {
        background: #eff6ff;
        border: 1px solid #dbeafe;
        color: #1e40af;
        padding: 15px;
        border-radius: 8px;
        font-size: 13px;
        margin-bottom: 25px;
        display: flex;
        gap: 12px;
    }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4 max-w-800 mx-auto">
        <div>
            <h4 class="fw-bold text-dark m-0">Add New Staff</h4>
            <p class="text-muted small m-0">Create a new account for your team members.</p>
        </div>
        <a href="manage-staff.php" class="btn btn-light border"><i class="fa-solid fa-arrow-left me-2"></i> Back</a>
    </div>

    <div class="page-card">
        
        <div class="card-header">
            <h6 class="m-0 fw-bold text-primary"><i class="fa-solid fa-user-plus me-2"></i> Staff Details</h6>
        </div>

        <div class="card-body">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fa-solid fa-circle-check"></i>
                        <span class="fw-bold">Success!</span>
                    </div>
                    <div><?= $success ?></div>
                    <div class="mt-2 small text-muted">
                        Share this email and password with your staff member. They will be required to change the password upon their first login.
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST">
                
                <div class="info-box">
                    <i class="fa-solid fa-circle-info mt-1"></i>
                    <div>
                        <strong>Default Password Logic:</strong><br>
                        The system will set a temporary password: <span class="badge bg-white text-primary border border-primary-subtle"><?= $defaultPassword ?></span><br>
                        The user status will be set to <strong>Pending</strong>. They cannot access the dashboard until they log in and set a new password.
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                            <input type="text" name="name" class="form-control border-start-0 ps-0" placeholder="e.g. Sarah Connor" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control border-start-0 ps-0" placeholder="sarah@company.com" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                            <input type="text" name="phone" class="form-control border-start-0 ps-0" placeholder="+1 (555) 000-0000">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select class="form-select text-muted" disabled>
                            <option selected>Staff Member</option>
                        </select>
                        <input type="hidden" name="role" value="staff">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-5 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fa-solid fa-plus me-2"></i> Create Account
                    </button>
                </div>

            </form>
        </div>
    </div>

</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>