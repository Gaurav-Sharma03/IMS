<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/CompanyController.php';

// Protect Page
requireRole(['admin', 'superadmin']);

$controller = new CompanyController();
$data = $controller->getEnhancedDashboardData();
extract($data);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* --- DARK THEME VARIABLES --- */
    :root {
        --dash-bg: #ffffff;       /* Deep Navy Background */
        --card-bg: #151e32;       /* Slightly Lighter Navy for Cards */
        --text-primary: #f9fafb;  /* White/Off-White */
        --text-secondary: #94a3b8;/* Muted Blue-Gray */
        --primary-color: #6366f1; /* Indigo 500 (Brighter for Dark Mode) */
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --border-color: #1e293b;  /* Dark Border */
        --glass-border: rgba(255, 255, 255, 0.08);
    }

    body { background-color: var(--dash-bg); color: var(--text-primary); font-family: 'Inter', system-ui, sans-serif; }

    /* --- LAYOUT --- */
    .main-content {
        margin-left: 250px;
        padding: 30px;
        min-height: 100vh;
        transition: margin-left 0.3s ease;
    }
    @media (max-width: 992px) { .main-content { margin-left: 0; padding: 20px; } }

    /* --- HEADER --- */
    .dashboard-header {
        background: var(--card-bg);
        padding: 25px 30px;
        border-radius: 16px;
        margin-bottom: 30px;
        border: 1px solid var(--glass-border);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
    }
    .welcome-text h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-primary); margin: 0; }
    .welcome-text p { color: var(--text-secondary); margin: 5px 0 0; font-size: 0.9rem; }

    .action-btn {
        background: var(--primary-color);
        color: white; padding: 10px 20px; border-radius: 8px;
        font-weight: 600; text-decoration: none; font-size: 0.9rem;
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.4); /* Glow Effect */
        transition: all 0.2s; border: none;
    }
    .action-btn:hover { background: #4f46e5; transform: translateY(-1px); color: white; box-shadow: 0 0 20px rgba(99, 102, 241, 0.6); }
    
    .action-btn.secondary { 
        background: transparent; 
        color: var(--text-primary); 
        border: 1px solid var(--border-color); 
        box-shadow: none; 
    }
    .action-btn.secondary:hover { background: rgba(255,255,255,0.05); border-color: var(--text-secondary); }

    /* --- STATS GRID --- */
    .stats-container {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 30px;
    }
    .stat-card {
        background: var(--card-bg);
        padding: 24px; border-radius: 16px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        transition: transform 0.2s;
        display: flex; flex-direction: column; justify-content: space-between; height: 100%;
    }
    .stat-card:hover { transform: translateY(-3px); border-color: var(--primary-color); }
    
    .stat-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px; }
    .icon-box {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    /* Dark Theme Icon Backgrounds */
    .icon-blue { background: rgba(99, 102, 241, 0.15); color: #818cf8; }
    .icon-green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
    .icon-orange { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
    .icon-purple { background: rgba(139, 92, 246, 0.15); color: #a78bfa; }

    .stat-number { font-size: 1.85rem; font-weight: 700; color: var(--text-primary); }
    .stat-title { font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 5px; }
    
    .trend-indicator {
        font-size: 0.75rem; font-weight: 600; padding: 2px 8px; border-radius: 20px; display: flex; align-items: center; gap: 4px;
    }
    .trend-up { background: rgba(16, 185, 129, 0.2); color: #34d399; }
    .trend-down { background: rgba(239, 68, 68, 0.2); color: #f87171; }

    /* --- CONTENT GRID --- */
    .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
    @media (max-width: 1100px) { .dashboard-grid { grid-template-columns: 1fr; } }

    /* --- CARDS --- */
    .dash-card {
        background: var(--card-bg);
        border-radius: 16px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        display: flex; flex-direction: column; overflow: hidden; height: 100%;
    }
    .dash-card-header {
        padding: 20px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex; justify-content: space-between; align-items: center;
        background: rgba(255, 255, 255, 0.02);
    }
    .dash-card-title { font-size: 1rem; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 8px; }
    
    .dash-card-body { padding: 0; flex: 1; overflow-y: auto; }
    .chart-wrapper { padding: 24px; height: 350px; position: relative; }

    /* --- LIST ITEMS --- */
    .list-item {
        padding: 16px 24px;
        border-bottom: 1px solid var(--border-color);
        display: flex; align-items: center;
        transition: background 0.15s;
    }
    .list-item:hover { background: rgba(255,255,255,0.03); }
    .list-item:last-child { border-bottom: none; }

    /* Task Specific */
    .task-check {
        width: 20px; height: 20px;
        border: 2px solid var(--text-secondary); border-radius: 6px;
        margin-right: 15px; cursor: pointer;
        display: grid; place-items: center; transition: 0.2s;
    }
    .task-check:hover { border-color: var(--primary-color); }
    .task-content { flex: 1; }
    .task-title { font-size: 0.9rem; font-weight: 600; color: var(--text-primary); display: block; margin-bottom: 2px; }
    .task-meta { font-size: 0.75rem; color: var(--text-secondary); display: flex; align-items: center; gap: 10px; }

    /* Activity Feed */
    .activity-feed { padding: 20px 24px; }
    .feed-line {
        position: relative; padding-left: 24px; padding-bottom: 24px;
        border-left: 2px solid var(--border-color);
    }
    .feed-line:last-child { padding-bottom: 0; border-left-color: transparent; }
    .feed-icon {
        position: absolute; left: -9px; top: 0;
        width: 16px; height: 16px; border-radius: 50%;
        background: var(--card-bg); border: 3px solid var(--primary-color);
    }
    .feed-content p { font-size: 0.85rem; margin: 0; color: var(--text-primary); line-height: 1.4; }
    .feed-date { font-size: 0.75rem; color: var(--text-secondary); margin-top: 4px; display: block; }
    
    /* Dropdown/Menu Buttons */
    .btn-icon { background: none; border: none; color: var(--text-secondary); }
    .btn-icon:hover { color: var(--text-primary); }

    /* Ticket Avatar */
    .avatar-sm {
        width: 32px; height: 32px;
        background: rgba(255,255,255,0.1); color: var(--text-primary);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.75rem; margin-right: 12px;
    }

    /* Scrollbar for Dark Mode */
    ::-webkit-scrollbar { width: 8px; }
    ::-webkit-scrollbar-track { background: var(--dash-bg); }
    ::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--text-secondary); }
    
    /* Inputs in Headers */
    .form-select-dark {
        background-color: transparent;
        color: var(--text-secondary);
        border: none;
        font-weight: bold;
    }
    .form-select-dark option { background-color: var(--card-bg); color: var(--text-primary); }
</style>

<main class="main-content">

    <div class="dashboard-header">
        <div class="welcome-text">
            <h2>Dashboard Overview</h2>
            <p>Real-time analytics and performance metrics.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>views/reports/report.php" class="action-btn secondary">
                <i class="fa-solid fa-download me-2"></i> Report
            </a>
            <a href="<?= BASE_URL ?>views/invoices/create.php" class="action-btn">
                <i class="fa-solid fa-plus me-2"></i> New Invoice
            </a>
        </div>
    </div>

    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-header">
                <div class="icon-box icon-green"><i class="fa-solid fa-wallet"></i></div>
                <span class="trend-indicator trend-up"><i class="fa-solid fa-arrow-up me-1"></i> 12.5%</span>
            </div>
            <div>
                <div class="stat-number">$<?= number_format($stats['revenue'], 2) ?></div>
                <div class="stat-title">Total Revenue</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="icon-box icon-orange"><i class="fa-solid fa-money-bill-transfer"></i></div>
                <span class="trend-indicator trend-down"><i class="fa-solid fa-arrow-down me-1"></i> 2.1%</span>
            </div>
            <div>
                <div class="stat-number">$<?= number_format($stats['expense_total'], 2) ?></div>
                <div class="stat-title">Total Expenses</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="icon-box icon-purple"><i class="fa-solid fa-file-invoice"></i></div>
            </div>
            <div>
                <div class="stat-number"><?= $stats['pending_count'] ?></div>
                <div class="stat-title">Unpaid Invoices</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div class="icon-box icon-blue"><i class="fa-solid fa-users"></i></div>
                <span class="trend-indicator trend-up"><i class="fa-solid fa-plus me-1"></i> 5 New</span>
            </div>
            <div>
                <div class="stat-number"><?= $stats['clients_count'] ?></div>
                <div class="stat-title">Active Clients</div>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        
        <div class="d-flex flex-column gap-4">
            
            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fa-solid fa-chart-area text-primary"></i> Performance</h5>
                    <select class="form-select form-select-sm w-auto form-select-dark" style="box-shadow:none;">
                        <option>This Year</option>
                        <option>Last Year</option>
                    </select>
                </div>
                <div class="dash-card-body">
                    <div class="chart-wrapper">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title"><i class="fa-solid fa-list-check text-success"></i> Tasks</h5>
                    <a href="<?= BASE_URL ?>views/tasks/manage.php" class="text-decoration-none small fw-bold text-primary">View All</a>
                </div>
                <div class="dash-card-body">
                    <?php if(empty($tasks)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fa-solid fa-clipboard-check fa-2x mb-3 opacity-25"></i>
                            <p class="m-0 small">No pending tasks.</p>
                        </div>
                    <?php else: foreach($tasks as $task): ?>
                        <div class="list-item">
                            <div class="task-check" title="Mark Complete"></div>
                            <div class="task-content">
                                <span class="task-title"><?= htmlspecialchars($task['title']) ?></span>
                                <div class="task-meta">
                                    <span class="text-danger"><i class="fa-regular fa-clock me-1"></i> <?= date('M d', strtotime($task['due_date'])) ?></span>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($task['priority'] ?? 'Normal') ?></span>
                                </div>
                            </div>
                            <button class="btn-icon"><i class="fa-solid fa-ellipsis"></i></button>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>

        <div class="d-flex flex-column gap-4">

            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">Activity Log</h5>
                </div>
                <div class="dash-card-body">
                    <div class="activity-feed">
                        <?php if(empty($activity)): ?>
                            <p class="text-muted small text-center">No recent activity.</p>
                        <?php else: foreach($activity as $act): ?>
                            <div class="feed-line">
                                <div class="feed-icon" style="border-color: <?= $act['type'] == 'invoice' ? '#10b981' : '#f59e0b' ?>;"></div>
                                <div class="feed-content">
                                    <p>
                                        <?php if($act['type'] == 'invoice'): ?>
                                            Created Invoice <a href="#" class="fw-bold text-primary text-decoration-none">#<?= htmlspecialchars($act['ref']) ?></a>
                                        <?php else: ?>
                                            New Ticket: <span class="fw-bold text-primary"><?= htmlspecialchars($act['ref']) ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <span class="feed-date"><?= date('M d, g:i a', strtotime($act['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-card-header">
                    <h5 class="dash-card-title">Support</h5>
                    <span class="badge bg-danger bg-opacity-75 rounded-pill"><?= count($tickets) ?></span>
                </div>
                <div class="dash-card-body">
                    <?php if(empty($tickets)): ?>
                        <div class="p-4 text-center small text-muted">No open tickets.</div>
                    <?php else: foreach($tickets as $t): ?>
                        <div class="list-item">
                            <div class="avatar-sm">
                                <?= strtoupper(substr($t['client_name'], 0, 1)) ?>
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-bold text-primary" style="font-size:0.85rem;"><?= htmlspecialchars($t['client_name']) ?></span>
                                    <small class="text-muted"><?= date('H:i', strtotime($t['created_at'])) ?></small>
                                </div>
                                <p class="m-0 text-secondary text-truncate small"><?= htmlspecialchars($t['subject']) ?></p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="p-3 border-top border-secondary bg-opacity-10 text-center">
                    <a href="<?= BASE_URL ?>views/support/manage.php" class="text-decoration-none fw-bold small text-primary">Open Helpdesk</a>
                </div>
            </div>

        </div>
    </div>

</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        
        // Gradient for Dark Theme
        let gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)'); 
        gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column(array_reverse($chartData), 'month')) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column(array_reverse($chartData), 'total')) ?>,
                    borderColor: '#6366f1',
                    backgroundColor: gradient,
                    borderWidth: 2,
                    pointBackgroundColor: '#1e1b4b',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f8fafc',
                        bodyColor: '#cbd5e1',
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: function(context) { return '$' + context.parsed.y.toLocaleString(); }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { borderDash: [4, 4], color: 'rgba(255,255,255,0.05)', drawBorder: false },
                        ticks: {
                            font: { size: 11, family: "'Inter', sans-serif" },
                            color: '#64748b',
                            callback: function(value) { return '$' + value; }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, family: "'Inter', sans-serif" },
                            color: '#64748b'
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>