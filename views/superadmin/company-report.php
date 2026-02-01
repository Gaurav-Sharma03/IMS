<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireRole(['superadmin']);

$db = (new Database())->connect();
$company_id = $_GET['id'] ?? 0;

// 1. Fetch Basic Company Info
$company = $db->query("SELECT name, email, created_at FROM companies WHERE company_id = $company_id")->fetch(PDO::FETCH_ASSOC);
if (!$company) die("Company not found.");

// 2. Advanced KPI Stats
$kpi = $db->query("
    SELECT 
        SUM(CASE WHEN status = 'paid' THEN grand_total ELSE 0 END) as total_revenue,
        SUM(CASE WHEN status != 'paid' THEN outstanding_amount ELSE 0 END) as pending_amount,
        COUNT(*) as total_invoices,
        AVG(grand_total) as avg_invoice_value
    FROM invoices 
    WHERE company_id = $company_id
")->fetch(PDO::FETCH_ASSOC);

// 3. Monthly Revenue Trend (Last 6 Months)
$trend_sql = "
    SELECT DATE_FORMAT(invoice_date, '%M') as month_name, SUM(grand_total) as total
    FROM invoices 
    WHERE company_id = $company_id AND status = 'paid' AND invoice_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(invoice_date, '%Y-%m')
    ORDER BY invoice_date ASC
";
$trend_data = $db->query($trend_sql)->fetchAll(PDO::FETCH_ASSOC);

// 4. Status Distribution
$status_sql = "SELECT status, COUNT(*) as count FROM invoices WHERE company_id = $company_id GROUP BY status";
$status_data = $db->query($status_sql)->fetchAll(PDO::FETCH_KEY_PAIR); // ['paid' => 10, 'unpaid' => 5]

// 5. Recent Transactions
$recent_inv = $db->query("
    SELECT invoice_number, invoice_date, grand_total, status 
    FROM invoices 
    WHERE company_id = $company_id 
    ORDER BY invoice_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// 6. Top Clients
$top_clients = $db->query("
    SELECT c.name, SUM(i.grand_total) as total_spent 
    FROM invoices i
    JOIN clients c ON i.client_id = c.client_id
    WHERE i.company_id = $company_id AND i.status = 'paid'
    GROUP BY i.client_id
    ORDER BY total_spent DESC LIMIT 4
")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    :root {
        --sa-bg: #0f172a; --sa-panel: #1e293b; --sa-border: #334155;
        --text-white: #ffffff; --text-light: #e2e8f0; --text-muted: #94a3b8;
        --accent: #6366f1; --success: #10b981; --warning: #f59e0b;
    }

    .main-content { margin-left: 250px; padding: 30px; background: var(--sa-bg); min-height: 100vh; color: var(--text-light); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* Print Styles */
    @media print {
        .sidebar, .btn-print, .btn-back { display: none !important; }
        .main-content { margin: 0; padding: 0; background: white; color: black; }
        .card-dark { border: 1px solid #ddd; background: white; color: black; box-shadow: none; }
        .text-white { color: black !important; }
        .text-muted { color: #666 !important; }
        canvas { max-height: 300px; }
    }

    /* Cards */
    .card-dark {
        background: var(--sa-panel); border: 1px solid var(--sa-border);
        border-radius: 16px; padding: 25px; height: 100%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    .card-header-custom {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
        border-bottom: 1px solid var(--sa-border); padding-bottom: 15px;
    }
    .card-title { font-weight: 700; color: var(--text-white); margin: 0; font-size: 1.1rem; }

    /* KPI Boxes */
    .kpi-card {
        background: linear-gradient(145deg, #1e293b, #24344d);
        border-radius: 12px; padding: 20px; border: 1px solid var(--sa-border);
        position: relative; overflow: hidden;
    }
    .kpi-icon {
        position: absolute; right: 15px; top: 15px; font-size: 2.5rem; opacity: 0.1; color: white;
    }
    .kpi-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; }
    .kpi-value { font-size: 1.8rem; font-weight: 800; color: var(--text-white); margin-top: 5px; }

    /* Tables */
    .mini-table { width: 100%; border-collapse: collapse; }
    .mini-table th { text-align: left; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; padding: 10px 0; border-bottom: 1px solid var(--sa-border); }
    .mini-table td { padding: 12px 0; border-bottom: 1px solid var(--sa-border); color: var(--text-light); font-size: 0.9rem; }
    .mini-table tr:last-child td { border-bottom: none; }

    .btn-print { background: var(--sa-panel); border: 1px solid var(--sa-border); color: var(--text-white); padding: 8px 16px; border-radius: 8px; font-weight: 600; transition: 0.2s; }
    .btn-print:hover { background: var(--accent); border-color: var(--accent); }
    .btn-back { color: var(--text-muted); text-decoration: none; font-weight: 600; }
    .btn-back:hover { color: var(--text-white); }

</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <div class="d-flex align-items-center gap-3">
                <a href="companies.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="fw-bold text-white m-0">Financial Analysis</h2>
            </div>
            <p class="text-white mt-1 ms-4 ps-2 small">
                Report for <strong class="text-white"><?= htmlspecialchars($company['name']) ?></strong> &bull; Generated on <?= date('M d, Y') ?>
            </p>
        </div>
        <button onclick="window.print()" class="btn-print"><i class="fa-solid fa-print me-2"></i> Print Report</button>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card">
                <i class="fa-solid fa-sack-dollar kpi-icon"></i>
                <div class="kpi-label text-success">Total Revenue</div>
                <div class="kpi-value">$<?= number_format($kpi['total_revenue'] ?? 0, 2) ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card">
                <i class="fa-solid fa-file-invoice-dollar kpi-icon"></i>
                <div class="kpi-label text-warning">Outstanding</div>
                <div class="kpi-value">$<?= number_format($kpi['pending_amount'] ?? 0, 2) ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card">
                <i class="fa-solid fa-chart-simple kpi-icon"></i>
                <div class="kpi-label">Avg. Invoice</div>
                <div class="kpi-value">$<?= number_format($kpi['avg_invoice_value'] ?? 0, 0) ?></div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="kpi-card">
                <i class="fa-solid fa-layer-group kpi-icon"></i>
                <div class="kpi-label">Total Invoices</div>
                <div class="kpi-value"><?= $kpi['total_invoices'] ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card-dark">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="fa-solid fa-arrow-trend-up me-2 text-primary"></i> Revenue Growth (6 Months)</h5>
                </div>
                <div style="height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-dark">
                <div class="card-header-custom">
                    <h5 class="card-title"><i class="fa-solid fa-chart-pie me-2 text-info"></i> Invoice Status</h5>
                </div>
                <div style="height: 300px; position: relative;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-6">
            <div class="card-dark">
                <div class="card-header-custom">
                    <h5 class="card-title text-white">Top Performing Clients</h5>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Client Name</th>
                            <th class="text-end">Total Contribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($top_clients)): ?>
                            <tr><td colspan="2" class="text-muted text-center py-3">No data available.</td></tr>
                        <?php else: foreach($top_clients as $c): ?>
                        <tr>
                            <td class="fw-bold text-white"><?= htmlspecialchars($c['name']) ?></td>
                            <td class="text-end text-success fw-bold">$<?= number_format($c['total_spent'], 2) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card-dark">
                <div class="card-header-custom">
                    <h5 class="card-title text-white">Recent Transactions</h5>
                </div>
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_inv)): ?>
                            <tr><td colspan="4" class="text-muted text-center py-3">No recent invoices.</td></tr>
                        <?php else: foreach($recent_inv as $inv): ?>
                        <tr>
                            <td class="text-white"><?= htmlspecialchars($inv['invoice_number']) ?></td>
                            <td class="text-muted"><?= date('M d', strtotime($inv['invoice_date'])) ?></td>
                            <td>
                                <?php 
                                    $sClass = match($inv['status']) { 'paid'=>'text-success', 'unpaid'=>'text-danger', default=>'text-warning' };
                                ?>
                                <span class="<?= $sClass ?> small fw-bold text-uppercase"><?= $inv['status'] ?></span>
                            </td>
                            <td class="text-end text-white fw-bold">$<?= number_format($inv['grand_total'], 2) ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</main>

<script>
    // 1. Revenue Chart Configuration
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    
    // Create Gradient
    let gradient = revCtx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)'); // Indigo opacity
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($trend_data, 'month_name')) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode(array_column($trend_data, 'total')) ?>,
                backgroundColor: gradient,
                borderColor: '#6366f1',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: '#334155' }, ticks: { color: '#94a3b8' }, beginAtZero: true },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });

    // 2. Status Donut Chart
    const statCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statCtx, {
        type: 'doughnut',
        data: {
            labels: ['Paid', 'Unpaid', 'Partial'],
            datasets: [{
                data: [
                    <?= $status_data['paid'] ?? 0 ?>, 
                    <?= $status_data['unpaid'] ?? 0 ?>, 
                    <?= $status_data['partial'] ?? 0 ?>
                ],
                backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'bottom', labels: { color: '#e2e8f0', usePointStyle: true, padding: 20 } } 
            },
            cutout: '70%'
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>