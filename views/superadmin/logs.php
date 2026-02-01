<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SuperAdminController.php';

// Strict Role Check
requireRole(['superadmin']);

$controller = new SuperAdminController();

// Handle Clear Action
if (isset($_POST['clear_logs'])) {
    $controller->clearLogs();
    header("Location: logs.php?success=cleared"); exit;
}

// Fetch Logs
$roleFilter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';
$logs = $controller->getAuditLogs($roleFilter, $search);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    :root {
        --sa-bg: #0f172a;       /* Deep Slate */
        --sa-panel: #1e293b;    /* Lighter Panel */
        --sa-border: #334155;   /* Border */
        --text-white: #ffffff; 
        --text-light: #cbd5e1; 
        --text-muted: #94a3b8;
        --accent: #6366f1;      /* Indigo */
        --danger: #ef4444;      /* Red */
        --success: #10b981;     /* Green */
        --warning: #f59e0b;     /* Orange */
    }

    .main-content { margin-left: 250px; padding: 30px; background: var(--sa-bg); min-height: 100vh; font-family: 'Inter', sans-serif; color: var(--text-light); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* Header */
    .page-header { display: flex; justify-content: space-between; align-items: end; margin-bottom: 25px; }
    .page-title { font-size: 1.75rem; font-weight: 800; color: var(--text-white); margin: 0; }
    .page-subtitle { color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; }

    /* Filter Bar */
    .filter-bar {
        background: var(--sa-panel); border: 1px solid var(--sa-border);
        padding: 12px; border-radius: 12px; display: flex; gap: 12px;
        align-items: center; margin-bottom: 25px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    
    .search-input {
        background: #0f172a; border: 1px solid var(--sa-border); color: var(--text-white);
        padding: 10px 15px; border-radius: 8px; flex-grow: 1; max-width: 400px;
    }
    .search-input:focus { border-color: var(--accent); outline: none; }

    .filter-select {
        background: #0f172a; border: 1px solid var(--sa-border); color: var(--text-white);
        padding: 10px 15px; border-radius: 8px; cursor: pointer;
    }

    .btn-clear {
        background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: var(--danger);
        padding: 10px 20px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.2s;
    }
    .btn-clear:hover { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }

    /* Log Table (Terminal Style) */
    .log-card {
        background: #020617; /* Extra dark for terminal feel */
        border: 1px solid var(--sa-border); border-radius: 16px; overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.4);
    }

    .log-table { width: 100%; border-collapse: collapse; font-family: 'JetBrains Mono', 'Courier New', monospace; font-size: 0.85rem; }
    
    .log-table th {
        text-align: left; padding: 15px 20px; color: var(--text-muted);
        text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px;
        border-bottom: 1px solid var(--sa-border); background: var(--sa-panel);
    }

    .log-table td {
        padding: 12px 20px; border-bottom: 1px solid #1e293b; color: var(--text-light); vertical-align: middle;
    }
    .log-table tr:hover td { background: rgba(255,255,255,0.03); }

    /* Badges & Highlights */
    .user-badge { display: flex; align-items: center; gap: 8px; font-weight: 600; color: var(--text-white); }
    .role-tag { font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; background: #334155; color: #cbd5e1; }
    
    .action-tag { font-weight: 700; }
    .act-login { color: var(--success); }
    .act-delete { color: var(--danger); }
    .act-create { color: var(--accent); }
    .act-update { color: var(--warning); }

    .ip-address { color: #64748b; font-size: 0.75rem; }
    .timestamp { color: var(--text-muted); font-size: 0.8rem; }

</style>

<main class="main-content">
    
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fa-solid fa-shield-halved me-2 text-primary"></i> System Audit Logs</h1>
            <p class="page-subtitle">Real-time security tracking and activity monitoring.</p>
        </div>
        <form method="POST" onsubmit="return confirm('Clear logs older than 30 days?');">
            <input type="hidden" name="clear_logs" value="1">
            <button class="btn-clear"><i class="fa-solid fa-trash me-2"></i> Prune Old Logs</button>
        </form>
    </div>

    <form method="GET" class="filter-bar">
        <i class="fa-solid fa-search ms-2 text-white"></i>
        <input type="text" name="search" class="search-input" placeholder="Search user, action, or details..." value="<?= htmlspecialchars($search) ?>">
        
        <select name="role" class="filter-select" onchange="this.form.submit()">
            <option value="">All Roles</option>
            <option value="superadmin" <?= $roleFilter === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Company Admin</option>
            <option value="staff" <?= $roleFilter === 'staff' ? 'selected' : '' ?>>Staff</option>
        </select>
        
        <div class="ms-auto text-white small fw-bold me-2">
            <?= count($logs) ?> Events Found
        </div>
    </form>

    <div class="log-card">
        <div class="table-responsive">
            <table class="log-table">
                <thead>
                    <tr>
                        <th width="15%">Timestamp</th>
                        <th width="20%">User</th>
                        <th width="15%">Action</th>
                        <th width="35%">Details</th>
                        <th width="15%" class="text-end">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($logs)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No audit logs found.</td></tr>
                    <?php else: foreach($logs as $log): ?>
                        <?php
                            // Determine Color Class based on Action Keyword
                            $actClass = 'text-white';
                            if (strpos($log['action'], 'LOGIN') !== false) $actClass = 'act-login';
                            elseif (strpos($log['action'], 'DELETE') !== false) $actClass = 'act-delete';
                            elseif (strpos($log['action'], 'CREATE') !== false) $actClass = 'act-create';
                            elseif (strpos($log['action'], 'UPDATE') !== false) $actClass = 'act-update';
                        ?>
                        <tr>
                            <td class="timestamp">
                                <i class="fa-regular fa-clock me-1"></i>
                                <?= date('M d, H:i:s', strtotime($log['created_at'])) ?>
                            </td>
                            <td>
                                <div class="user-badge">
                                    <?= htmlspecialchars($log['user_name']) ?>
                                    <span class="role-tag"><?= strtoupper($log['user_role']) ?></span>
                                </div>
                            </td>
                            <td class="<?= $actClass ?> action-tag">
                                <?= htmlspecialchars($log['action']) ?>
                            </td>
                            <td class="text-light opacity-75">
                                <?= htmlspecialchars($log['details']) ?>
                            </td>
                            <td class="text-end ip-address">
                                <?= htmlspecialchars($log['ip_address']) ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>