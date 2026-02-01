<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SuperAdminController.php';

// Strict Role Check
requireRole(['superadmin']);

$controller = new SuperAdminController();

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->updateGlobalSettings($_POST, $_FILES);
    header("Location: settings.php?success=saved"); exit;
}

// Fetch Current Settings
$settings = $controller->getGlobalSettings();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root {
        --sa-bg: #0f172a; --sa-panel: #1e293b; --sa-border: #334155;
        --text-white: #ffffff; --text-muted: #94a3b8; --accent: #6366f1;
    }

    .main-content { margin-left: 250px; padding: 30px; background: var(--sa-bg); min-height: 100vh; font-family: 'Inter', sans-serif; color: var(--text-white); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* Layout */
    .settings-layout { display: grid; grid-template-columns: 250px 1fr; gap: 30px; }
    @media (max-width: 992px) { .settings-layout { grid-template-columns: 1fr; } }

    /* Sidebar Nav */
    .settings-nav { background: var(--sa-panel); border: 1px solid var(--sa-border); border-radius: 12px; padding: 15px; height: fit-content; }
    .nav-pills .nav-link {
        color: var(--text-muted); font-weight: 600; padding: 12px 20px; border-radius: 8px; margin-bottom: 5px; text-align: left;
        display: flex; align-items: center; gap: 10px; transition: 0.2s;
    }
    .nav-pills .nav-link:hover { background: rgba(255,255,255,0.05); color: white; }
    .nav-pills .nav-link.active { background: var(--accent); color: white; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4); }
    .nav-pills .nav-link i { width: 20px; text-align: center; }

    /* Content Cards */
    .card-dark { background: var(--sa-panel); border: 1px solid var(--sa-border); border-radius: 16px; padding: 30px; }
    .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--sa-border); color: white; }

    /* Forms */
    .form-label { color: var(--text-muted); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
    .form-control, .form-select {
        background-color: #020617; border: 1px solid var(--sa-border); color: white; padding: 12px 15px; border-radius: 8px;
    }
    .form-control:focus, .form-select:focus {
        border-color: var(--accent); background-color: #020617; color: white; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }
    .form-text { color: #64748b; font-size: 0.8rem; margin-top: 5px; }

    /* Switch */
    .form-check-input { background-color: #334155; border-color: #475569; width: 3em; height: 1.5em; cursor: pointer; }
    .form-check-input:checked { background-color: var(--accent); border-color: var(--accent); }

    .btn-save {
        background: var(--accent); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 700;
        transition: 0.2s; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3); float: right;
    }
    .btn-save:hover { background: #4f46e5; transform: translateY(-2px); color: white; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold m-0">Global Settings</h2>
            <p class="form-text mt-1 m-0">Configure system-wide parameters, branding, and integrations.</p>
        </div>
        <?php if(isset($_GET['success'])): ?>
            <div class="alert alert-success py-2 px-3 fw-bold rounded-pill border-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-check"></i> Changes Saved Successfully
            </div>
        <?php endif; ?>
    </div>

    <form method="POST" enctype="multipart/form-data" class="settings-layout">
        
        <div class="settings-nav">
            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-general" type="button">
                    <i class="fa-solid fa-sliders"></i> General
                </button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-brand" type="button">
                    <i class="fa-solid fa-palette"></i> Branding
                </button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-email" type="button">
                    <i class="fa-solid fa-envelope"></i> SMTP / Email
                </button>
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-security" type="button">
                    <i class="fa-solid fa-shield-halved"></i> Security & Maint.
                </button>
            </div>
        </div>

        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="tab-general">
                <div class="card-dark">
                    <div class="section-title">Application Defaults</div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <label class="form-label">Application Name</label>
                            <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($settings['app_name'] ?? 'SaaS App') ?>">
                            <div class="form-text">Displayed in emails and browser tabs.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Default Timezone</label>
                            <select name="app_timezone" class="form-select">
                                <option value="UTC" <?= ($settings['app_timezone'] ?? '') == 'UTC' ? 'selected' : '' ?>>UTC</option>
                                <option value="America/New_York" <?= ($settings['app_timezone'] ?? '') == 'America/New_York' ? 'selected' : '' ?>>New York (EST)</option>
                                <option value="Europe/London" <?= ($settings['app_timezone'] ?? '') == 'Europe/London' ? 'selected' : '' ?>>London (GMT)</option>
                                <option value="Asia/Kolkata" <?= ($settings['app_timezone'] ?? '') == 'Asia/Kolkata' ? 'selected' : '' ?>>India (IST)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Currency Code</label>
                            <input type="text" name="app_currency" class="form-control" value="<?= htmlspecialchars($settings['app_currency'] ?? 'USD') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Currency Symbol</label>
                            <input type="text" name="app_currency_symbol" class="form-control" value="<?= htmlspecialchars($settings['app_currency_symbol'] ?? '$') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-brand">
                <div class="card-dark">
                    <div class="section-title">Visual Identity</div>
                    
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <label class="form-label">Primary Logo</label>
                            <input type="file" name="app_logo" class="form-control">
                            <div class="form-text">Recommended size: 200x50px. PNG format.</div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 border border-secondary rounded bg-black d-inline-block">
                                <img src="<?= BASE_URL ?>public/uploads/logo.png" alt="Current Logo" style="max-height: 40px; opacity: 0.8;">
                            </div>
                            <div class="small form-text mt-2">Current Preview</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Footer Copyright Text</label>
                        <input type="text" name="footer_text" class="form-control" value="<?= htmlspecialchars($settings['footer_text'] ?? '© 2024 SaaS Command. All rights reserved.') ?>">
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-email">
                <div class="card-dark">
                    <div class="section-title">Email Configuration (SMTP)</div>
                    
                    <div class="row g-4 mb-3">
                        <div class="col-md-8">
                            <label class="form-label">SMTP Host</label>
                            <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" placeholder="smtp.example.com">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Port</label>
                            <input type="text" name="smtp_port" class="form-control" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                        </div>
                    </div>

                    <div class="row g-4 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">SMTP Username</label>
                            <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SMTP Password</label>
                            <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-security">
                <div class="card-dark">
                    <div class="section-title">System Control</div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background: rgba(255,255,255,0.05);">
                        <div>
                            <h6 class="fw-bold text-white mb-1">Maintenance Mode</h6>
                            <p class="form-text small m-0">Prevent users from accessing the dashboard while you perform updates.</p>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="maintenance_mode" value="0">
                            <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: rgba(255,255,255,0.05);">
                        <div>
                            <h6 class="fw-bold text-white mb-1">Allow New Company Registrations</h6>
                            <p class="form-text small m-0">If disabled, only Superadmins can create new tenants manually.</p>
                        </div>
                        <div class="form-check form-switch">
                            <input type="hidden" name="allow_registration" value="0">
                            <input class="form-check-input" type="checkbox" name="allow_registration" value="1" <?= ($settings['allow_registration'] ?? '1') == '1' ? 'checked' : '' ?>>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" name="save_settings" class="btn-save">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
                </button>
            </div>

        </div>
    </form>

</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>