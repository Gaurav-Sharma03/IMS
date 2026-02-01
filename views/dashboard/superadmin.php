<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SuperAdminController.php';
require_once __DIR__ . '/../../config/database.php'; // Ensure DB class is loaded

requireRole(['superadmin']);

// 1. Fetch Controller Data (Stats, Charts, Companies)
$controller = new SuperAdminController();
$data = $controller->getSystemOverview();
extract($data); 

// 2. Initialize Database Connection for Custom Queries
$database = new Database();
$db = $database->connect();

// 3. FETCH DATA: Security Audit Logs
$stmt = $db->prepare("
    SELECT * FROM audit_logs 
    ORDER BY created_at DESC 
    LIMIT 7
");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. HELPER: Relative Time Function (FIXED)
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks separately to avoid modifying the DateInterval object
    $weeks = floor($diff->d / 7);
    $days = $diff->d - ($weeks * 7);

    // Define labels
    $labels = array(
        'y' => 'yr', 'm' => 'mo', 'w' => 'w',
        'd' => 'd', 'h' => 'h', 'i' => 'm', 's' => 's',
    );

    // Map actual values
    $values = array(
        'y' => $diff->y,
        'm' => $diff->m,
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    );

    $string = array();
    foreach ($labels as $k => $label) {
        if ($values[$k]) {
            $string[$k] = $values[$k] . $label;
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';

// Safe fallback for revenue
$subRevenue = $stats['subscription_revenue'] ?? 0;
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --sa-bg: #0f172a;
        --sa-panel: #1e293b;
        --sa-border: rgba(255, 255, 255, 0.06);
        --sa-accent: #6366f1;
        --text-bright: #ffffff;
        --text-smoky: #cbd5e1;
        --text-dim: #94a3b8;
    }

    /* Layout Spacing Fix - Responsive */
    .main-content {
        margin-left: var(--sidebar-width);
        padding: 2rem;
        background: var(--sa-bg);
        min-height: 100vh;
        color: var(--text-smoky);
        transition: all 0.3s ease;
    }

    /* Tablet & Mobile Breakpoint */
    @media (max-width: 992px) {
        .main-content {
            margin-left: 0 !important; /* Remove sidebar margin on mobile */
            padding: 1.25rem; /* Reduce padding for smaller screens */
        }
        
        .sa-hero {
            padding: 1.5rem !important;
            text-align: center;
            flex-direction: column;
        }

        .sa-hero .mt-4 {
            margin-top: 1.5rem !important;
            justify-content: center;
            width: 100%;
        }

        .metric-val {
            font-size: 1.5rem !important; /* Smaller text for mobile metrics */
        }
    }

    /* Existing Styles */
    .sa-hero {
        background: linear-gradient(135deg, #4f46e5 0%, #1e1b4b 100%);
        border-radius: 1.25rem;
        padding: 2.5rem;
        margin-bottom: 2.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .metric-card {
        background: var(--sa-panel);
        border: 1px solid var(--sa-border);
        padding: 1.75rem;
        border-radius: 1.25rem;
        transition: all 0.3s ease;
        height: 100%; /* Ensure cards are equal height */
    }
    .metric-card:hover { transform: translateY(-8px); border-color: var(--sa-accent); }

    .metric-val { font-size: 2rem; font-weight: 800; color: var(--text-bright); }
    .metric-label { font-size: 0.75rem; text-transform: uppercase; color: var(--text-dim); font-weight: 600; }

    .sa-panel { background: var(--sa-panel); border: 1px solid var(--sa-border); border-radius: 1.25rem; overflow: hidden; }
    .sa-table th { background: rgba(15, 23, 42, 0.5); color: var(--text-bright); padding: 1.25rem; font-size: 0.75rem; text-transform: uppercase; }
    .sa-table td { padding: 1.25rem; color: var(--text-smoky); border-bottom: 1px solid var(--sa-border); }

    .pulse-dot { height: 8px; width: 8px; background-color: #10b981; border-radius: 50%; display: inline-block; animation: pulse-green 2s infinite; }
    @keyframes pulse-green { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
</style>

<main class="main-content">
    <div class="sa-hero d-flex flex-column d-md-flex flex-md-row justify-content-between align-items-center">
        <div class="mb-3 mb-md-0">
            <h1 class="text-white fw-bold mb-2 fs-3 fs-md-2">SuperAdmin Command Dashboard</h1>
            <p class="text-smoky mb-0 fs-6">System Health: <span class="text-success fw-bold"><span class="pulse-dot me-1"></span> Operational</span></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>views/support/global-tickets.php" class="btn btn-primary px-3 px-md-4 py-2 fw-bold rounded-pill shadow-sm small">
                <i class="fa-solid fa-headset me-2"></i> Support
            </a>
            <button onclick="window.location.reload()" class="btn btn-outline-light rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                <i class="fa-solid fa-arrows-rotate"></i>
            </button>
        </div>
    </div>

    <div class="row g-3 g-md-4 mb-5">
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="text-emerald mb-2 mb-md-3" style="color: #10b981;"><i class="fa-solid fa-hand-holding-dollar fa-xl fa-md-2x"></i></div>
                <div class="metric-val">$<?= number_format($subRevenue, 2) ?></div>
                <div class="metric-label">Revenue</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="text-primary mb-2 mb-md-3"><i class="fa-solid fa-building-shield fa-xl fa-md-2x"></i></div>
                <div class="metric-val"><?= number_format($stats['total_companies']) ?></div>
                <div class="metric-label">Tenants</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="text-info mb-2 mb-md-3"><i class="fa-solid fa-users-gear fa-xl fa-md-2x"></i></div>
                <div class="metric-val"><?= number_format($stats['total_users']) ?></div>
                <div class="metric-label">Users</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="metric-card">
                <div class="text-warning mb-2 mb-md-3"><i class="fa-solid fa-bolt-lightning fa-xl fa-md-2x"></i></div>
                <div class="metric-val">99.9%</div>
                <div class="metric-label">Uptime</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="sa-panel p-3 p-md-4 mb-4">
                <h6 class="text-white fw-bold mb-4"><i class="fa-solid fa-chart-line me-2 text-primary"></i> Registration Velocity</h6>
                <div style="height: 300px; width: 100%;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <div class="sa-panel">
                <div class="p-3 p-md-4 border-bottom d-flex justify-content-between align-items-center bg-dark-subtle">
                    <h6 class="text-muted fw-bold m-0 small"><i class="fa-solid fa-sitemap me-2"></i> Organizations</h6>
                    <a href="<?= BASE_URL ?>views/superadmin/companies.php" class="btn btn-sm btn-outline-primary px-2 px-md-3">All</a>
                </div>
                <div class="table-responsive">
                    <table class="sa-table w-100">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Admin</th>
                                <th>Joined</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($recent_companies)): foreach ($recent_companies as $co): ?>
                                <tr>
                                    <td class="fw-bold text-white small"><?= htmlspecialchars($co['name'] ?? 'N/A') ?></td>
                                    <td class="text-smoky small d-none d-md-table-cell"><?= htmlspecialchars($co['email'] ?? 'N/A') ?></td>
                                    <td class="text-dim small"><?= date('M d, y', strtotime($co['created_at'])) ?></td>
                                    <td><span class="badge bg-success text-white smaller p-1 px-md-2">Active</span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm overflow-hidden" style="border-radius: 1.25rem;">
                
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold m-0 text-dark">
                        <i class="fa-solid fa-shield-cat me-2 text-primary"></i>Security Feed
                    </h6>
                    <span class="badge bg-light text-muted border">Live</span>
                </div>

                <div class="card-body p-0 position-relative">
                    <div class="p-4" style="max-height: 400px; overflow-y: auto;">
                        
                        <?php if(empty($logs)): ?>
                            <div class="text-center py-5 text-muted small">No activity recorded.</div>
                        <?php else: foreach($logs as $index => $log): 
                            // Dynamic Styling based on Action
                            $icon = 'fa-circle-info';
                            $color = 'text-primary';
                            $bg = 'bg-primary-subtle';
                            
                            if (strpos($log['action'], 'LOGIN_SUCCESS') !== false) {
                                $icon = 'fa-right-to-bracket'; $color = 'text-success'; $bg = 'bg-success-subtle';
                            } elseif (strpos($log['action'], 'FAILED') !== false || strpos($log['action'], 'DENIED') !== false) {
                                $icon = 'fa-triangle-exclamation'; $color = 'text-danger'; $bg = 'bg-danger-subtle';
                            } elseif (strpos($log['action'], 'UPDATE') !== false) {
                                $icon = 'fa-pen-to-square'; $color = 'text-info'; $bg = 'bg-info-subtle';
                            } elseif (strpos($log['action'], 'DELETE') !== false) {
                                $icon = 'fa-trash'; $color = 'text-danger'; $bg = 'bg-danger-subtle';
                            }
                            
                            $isLast = ($index === count($logs) - 1);
                        ?>
                            <div class="d-flex position-relative mb-3">
                                <?php if(!$isLast): ?>
                                    <div class="position-absolute" style="left: 19px; top: 35px; bottom: -20px; width: 2px; background: #f1f5f9;"></div>
                                <?php endif; ?>

                                <div class="me-3 position-relative z-1">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center <?= $bg ?> <?= $color ?>" style="width: 40px; height: 40px; border: 4px solid #fff;">
                                        <i class="fa-solid <?= $icon ?> fa-sm"></i>
                                    </div>
                                </div>

                                <div class="flex-grow-1 pt-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark small">
                                            <?= htmlspecialchars($log['user_name'] ?? 'Guest') ?>
                                        </span>
                                        <span class="text-muted" style="font-size: 0.7rem;">
                                            <?= time_elapsed_string($log['created_at']) ?>
                                        </span>
                                    </div>
                                    
                                    <p class="mb-1 text-secondary small text-truncate" style="max-width: 200px;">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', strtolower($log['action'])))) ?>
                                    </p>
                                    
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge border text-muted fw-normal" style="font-size: 0.65rem;">
                                            <?= strtoupper($log['user_role'] ?? 'GUEST') ?>
                                        </span>
                                        <?php if(!empty($log['ip_address'])): ?>
                                            <span class="text-muted" style="font-size: 0.65rem;">
                                                <i class="fa-solid fa-globe me-1"></i><?= $log['ip_address'] ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>

                    </div>
                    
                    <div class="position-absolute bottom-0 start-0 w-100" style="height: 50px; background: linear-gradient(to bottom, transparent, white); pointer-events: none;"></div>
                </div>

                <div class="card-footer bg-white border-top p-0">
                    <a href="<?= BASE_URL ?>views/superadmin/logs.php" class="btn btn-link text-decoration-none w-100 py-3 fw-bold small text-secondary hover-primary">
                        View Full Audit History <i class="fa-solid fa-chevron-right ms-1" style="font-size: 0.7rem;"></i>
                    </a>
                </div>
            </div>
        </div>

    </div>
</main>

<script>
    const ctx = document.getElementById('growthChart').getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 400);
    grad.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
    grad.addColorStop(1, 'rgba(99, 102, 241, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column(array_reverse($growthData), 'month')) ?>,
            datasets: [{
                data: <?= json_encode(array_column(array_reverse($growthData), 'count')) ?>,
                borderColor: '#818cf8',
                borderWidth: 4,
                backgroundColor: grad,
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#fff'
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>