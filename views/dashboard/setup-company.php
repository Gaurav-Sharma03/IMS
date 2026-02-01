<?php
// 1. Load Dependencies
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Company.php';

// 2. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin.php");
        exit;
    }
    header("Location: ../auth/login.php");
    exit;
}

// 3. Initialize Objects
$db = new Database();
$conn = $db->connect();
$userModel = new User($conn);
$companyModel = new Company($conn);

$error = "";

// 4. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. Handle Logo Upload
    $logo_path = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array(strtolower($ext), $allowed)) {
            $filename = 'logo_' . time() . '.' . $ext;
            $target_dir = __DIR__ . '/../../assets/uploads/company_logos/';
            
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_dir . $filename)) {
                $logo_path = 'assets/uploads/company_logos/' . $filename;
            }
        } else {
            $error = "Invalid file type. Only JPG, PNG, and WEBP allowed.";
        }
    }

    if (empty($error)) {
        // B. Prepare Data Array (Safety Checks Added)
        $companyData = [
            'name'        => trim($_POST['name'] ?? ''),
            'contact'     => trim($_POST['contact'] ?? ''),
            'email'       => trim($_POST['email'] ?? ''),
            'gst_vat'     => trim($_POST['gst_vat'] ?? ''),
            
            // Location Data
            'address'     => trim($_POST['address'] ?? ''), // Main Address
            'street'      => trim($_POST['street'] ?? ''),  // Added Street Field
            'city'        => trim($_POST['city'] ?? ''),
            'state'       => trim($_POST['state'] ?? ''),
            'country'     => trim($_POST['country'] ?? ''),
            'postal_code' => trim($_POST['postal_code'] ?? ''),
            
            'logo'        => $logo_path,
            'created_by'  => $_SESSION['user_id']
        ];

        // C. Create Company
        $newCompanyId = $companyModel->create($companyData);

        if ($newCompanyId) {
            // D. Upgrade User to Admin
            $userModel->upgradeToAdmin($_SESSION['user_id'], $newCompanyId);

            // E. Update Session & Redirect
            $_SESSION['role'] = 'admin';
            $_SESSION['company_id'] = $newCompanyId;
            
            header("Location: admin.php");
            exit;
        } else {
            if (!empty($companyModel->errors)) {
                $error = implode("<br>", $companyModel->errors);
            } else {
                $error = "Database Error: Could not register company.";
            }
        }
    }
}

include __DIR__ . '/../layouts/header.php';
?>

<style>
    .setup-wrapper { background: #f8fafc; min-height: 90vh; padding: 40px 20px; }
    .setup-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden; }
    .setup-header { background: #4f46e5; color: white; padding: 40px; text-align: center; }
    .setup-header h2 { font-weight: 800; margin: 0; }
    .setup-body { padding: 40px; }
    .form-section-title { font-size: 14px; text-transform: uppercase; color: #6b7280; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 5px; }
    .form-control { padding: 10px 15px; font-size: 14px; border-radius: 8px; border-color: #d1d5db; }
    .form-control:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
</style>

<div class="setup-wrapper">
    <div class="setup-container">
        <div class="setup-header">
            <h2>Setup Your Workspace</h2>
            <p>Tell us about your organization to get started.</p>
        </div>

        <div class="setup-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-section-title mt-0">Basic Information</div>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Business Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($_POST['contact'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Tax ID / GST / VAT</label>
                            <input type="text" name="gst_vat" class="form-control" value="<?= htmlspecialchars($_POST['gst_vat'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section-title">Location Details</div>
                <div class="row g-3 mb-4">
                    
                    <div class="col-12">
                        <div class="form-group">
                            <label>Full Address <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" placeholder="123 Main Street" required value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="col-12">
                         <div class="form-group">
                            <label>Street / Area (Optional)</label>
                            <input type="text" name="street" class="form-control" placeholder="Apt, Suite, Unit, etc." value="<?= htmlspecialchars($_POST['street'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>City <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control" required value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>State / Province <span class="text-danger">*</span></label>
                            <input type="text" name="state" class="form-control" required value="<?= htmlspecialchars($_POST['state'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Country <span class="text-danger">*</span></label>
                            <input type="text" name="country" class="form-control" required value="<?= htmlspecialchars($_POST['country'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Postal / Zip Code</label>
                            <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section-title">Branding</div>
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="form-group">
                            <label>Upload Logo</label>
                            <input type="file" name="logo" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-3 pt-3 border-top">
                    <a href="<?= BASE_URL ?>logout.php" class="btn btn-light text-muted">Logout</a>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Complete Setup</button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>