<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Invoice.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
    header("Location: " . BASE_URL . "views/auth/login.php");
    exit;
}

$invoice_id = $_GET['id'] ?? null;
$company_id = $_SESSION['company_id'];

// 2. Database Connections
$db = new Database();
$conn = $db->connect();
$model = new Invoice($conn);

// 3. Fetch Data
$inv = $invoice_id ? $model->getInvoiceById($invoice_id, $company_id) : null;

// Fetch Company Details
$companyStmt = $conn->prepare("SELECT * FROM companies WHERE company_id = ?");
$companyStmt->execute([$company_id]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);

// Fetch Client Specific Details (like GST/Tax ID) if not in main query
$clientExtra = [];
if ($inv) {
    $cStmt = $conn->prepare("SELECT gst_vat, postal_code, state FROM clients WHERE client_id = ?");
    $cStmt->execute([$inv['client_id']]);
    $clientExtra = $cStmt->fetch(PDO::FETCH_ASSOC);
}

// 4. Handle Not Found
if (!$inv || !$company) {
    include __DIR__ . '/../layouts/header.php';
    echo '<div class="d-flex justify-content-center align-items-center" style="height:80vh; background:#f1f5f9;">
            <div class="text-center p-5 bg-white shadow rounded">
                <h3 class="text-danger fw-bold">Invoice Not Found</h3>
                <p class="text-muted">This invoice does not exist or has been deleted.</p>
                <a href="manage.php" class="btn btn-dark btn-sm rounded-pill px-4">Go Back</a>
            </div>
          </div>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= htmlspecialchars($inv['invoice_number']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #0f172a;
            --accent: #2563eb;
            --text-main: #334155;
            --text-light: #64748b;
            --bg-light: #f8fafc;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #525659; /* PDF Viewer Background */
            margin: 0;
            padding: 40px 0;
            color: var(--text-main);
            -webkit-print-color-adjust: exact;
        }

        /* A4 Page */
        .page {
            background: white;
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 15mm 20mm;
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            position: relative;
            display: flex;
            flex-direction: column;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--primary);
            margin-bottom: 30px;
        }

        .brand-section { max-width: 50%; }
        .logo-img { height: 60px; object-fit: contain; margin-bottom: 5px; }
        .brand-text { font-size: 24px; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: -0.5px; margin: 0; }
        .company-details { font-size: 11px; color: var(--text-light); line-height: 1.5; margin-top: 8px; }

        .invoice-meta { text-align: right; }
        .doc-title { font-size: 32px; font-weight: 900; color: var(--text-main); text-transform: uppercase; letter-spacing: 2px; line-height: 1; margin-bottom: 5px; }
        .inv-number { font-size: 16px; font-weight: 700; color: var(--text-main); }
        .status-badge {
            display: inline-block; padding: 5px 12px; border-radius: 4px; 
            font-size: 10px; font-weight: 800; text-transform: uppercase; 
            margin-top: 8px; letter-spacing: 0.5px;
        }
        .st-paid { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .st-unpaid { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
        .st-partial { background: #fef9c3; color: #a16207; border: 1px solid #fde047; }

        /* Addresses */
        .bill-grid { display: flex; gap: 40px; margin-bottom: 40px; }
        .bill-col { flex: 1; }
        .bill-label { font-size: 10px; text-transform: uppercase; font-weight: 700; color: var(--text-light); letter-spacing: 0.5px; margin-bottom: 8px; border-bottom: 1px solid var(--border); padding-bottom: 4px; display: inline-block; }
        .bill-name { font-size: 14px; font-weight: 700; color: var(--primary); margin-bottom: 4px; display: block; }
        .bill-addr { font-size: 12px; line-height: 1.5; color: var(--text-main); }
        .tax-id { font-size: 11px; font-weight: 600; color: var(--text-main); margin-top: 4px; display: block; }

        .dates-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; background: var(--bg-light); padding: 10px; border-radius: 6px; }
        .date-box label { font-size: 10px; color: var(--text-light); display: block; font-weight: 600; text-transform: uppercase; }
        .date-box span { font-size: 13px; font-weight: 700; color: var(--primary); }

        /* Table */
        .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; font-size: 12px; }
        .inv-table th { background: var(--primary); color: white; text-transform: uppercase; font-size: 10px; font-weight: 700; padding: 10px; text-align: left; }
        .inv-table td { padding: 12px 10px; border-bottom: 1px solid var(--border); vertical-align: top; }
        .inv-table tr:nth-child(even) { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .item-name { font-weight: 600; color: var(--primary); display: block; }
        .item-desc { color: var(--text-light); font-size: 11px; margin-top: 2px; display: block; }

        /* Summary */
        .summary-wrapper { display: flex; justify-content: flex-end; page-break-inside: avoid; }
        .summary-table { width: 45%; border-collapse: collapse; font-size: 13px; }
        .summary-table td { padding: 6px 0; color: var(--text-main); }
        .summary-table .total-row td { border-top: 2px solid var(--primary); border-bottom: 2px solid var(--primary); padding: 10px 0; font-weight: 800; font-size: 16px; color: var(--primary); }
        .summary-table .balance-row td { padding-top: 10px; color: #b91c1c; font-weight: 700; }

        /* Footer / Terms */
        .footer-section { margin-top: auto; padding-top: 30px; border-top: 1px solid var(--border); display: flex; gap: 30px; font-size: 11px; color: var(--text-light); }
        .terms-box { flex: 2; }
        .auth-box { flex: 1; text-align: right; display: flex; flex-direction: column; justify-content: flex-end; }
        .disclaimer { font-style: italic; background: #f1f5f9; padding: 8px; border-radius: 4px; text-align: center; margin-top: 20px; font-size: 10px; color: var(--text-light); border: 1px dashed var(--border); }

        /* Controls */
        .fab-container { position: fixed; bottom: 30px; right: 30px; display: flex; flex-direction: column; gap: 10px; z-index: 999; }
        .fab { width: 50px; height: 50px; border-radius: 50%; background: var(--primary); color: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: transform 0.2s; text-decoration: none; }
        .fab:hover { transform: scale(1.1); background: #000; }
        .fab.back { background: white; color: var(--primary); }

        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; margin: 0; width: 100%; height: auto; }
            .fab-container { display: none; }
        }
    </style>
</head>
<body>

    <div class="fab-container">
        <button onclick="window.print()" class="fab" title="Print / Save PDF"><i class="fa-solid fa-print"></i></button>
        <a href="manage.php" class="fab back" title="Back to Dashboard"><i class="fa-solid fa-arrow-left"></i></a>
    </div>

    <div class="page">
        <header class="header">
            <div class="brand-section">
                <?php if (!empty($company['logo']) && file_exists(__DIR__ . '/../../assets/uploads/' . $company['logo'])): ?>
                    <img src="<?= BASE_URL . 'assets/uploads/' . $company['logo'] ?>" alt="Logo" class="logo-img">
                <?php else: ?>
                    <h1 class="brand-text"><?= htmlspecialchars($company['name']) ?></h1>
                <?php endif; ?>
                
                <div class="company-details">
                    <?= htmlspecialchars($company['address']) ?><br>
                    <?= htmlspecialchars($company['city']) ?>, <?= htmlspecialchars($company['country']) ?> - <?= htmlspecialchars($company['postal_code']) ?><br>
                    <strong>E:</strong> <?= htmlspecialchars($company['email']) ?> | <strong>P:</strong> <?= htmlspecialchars($company['contact']) ?>
                    <?php if(!empty($company['gst_vat'])): ?>
                        <br><strong>TAX/GST ID:</strong> <?= htmlspecialchars($company['gst_vat']) ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="invoice-meta">
                <div class="doc-title">INVOICE</div>
                <div class="inv-number">#<?= htmlspecialchars($inv['invoice_number']) ?></div>
                <?php $statusClass = match($inv['status']) { 'paid'=>'st-paid', 'unpaid'=>'st-unpaid', default=>'st-partial' }; ?>
                <span class="status-badge <?= $statusClass ?>"><?= $inv['status'] ?></span>
            </div>
        </header>

        <section class="bill-grid">
            <div class="bill-col">
                <span class="bill-label">Billed To</span>
                <span class="bill-name"><?= htmlspecialchars($inv['client_name']) ?></span>
                <div class="bill-addr">
                    <?= nl2br(htmlspecialchars($inv['client_address'] ?: 'No Address Provided')) ?><br>
                    <?= htmlspecialchars($inv['client_email']) ?><br>
                    <?= htmlspecialchars($inv['client_phone']) ?>
                </div>
                <?php if(!empty($clientExtra['gst_vat'])): ?>
                    <span class="tax-id">TAX ID: <?= htmlspecialchars($clientExtra['gst_vat']) ?></span>
                <?php endif; ?>
            </div>
            
            <div class="bill-col">
                <span class="bill-label">Invoice Details</span>
                <div class="dates-grid">
                    <div class="date-box">
                        <label>Issue Date</label>
                        <span><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></span>
                    </div>
                    <div class="date-box">
                        <label>Due Date</label>
                        <span style="color: #b91c1c;"><?= date('M d, Y', strtotime($inv['due_date'])) ?></span>
                    </div>
                    <div class="date-box">
                        <label>Currency</label>
                        <span><?= htmlspecialchars($inv['currency_code'] ?? 'USD') ?></span>
                    </div>
                    <div class="date-box">
                        <label>PO Number</label>
                        <span style="color:var(--text-main); font-weight:400;">—</span>
                    </div>
                </div>
            </div>
        </section>

        <table class="inv-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="45%">Item Description</th>
                    <th width="10%" class="text-center">Qty</th>
                    <th width="20%" class="text-right">Price</th>
                    <th width="20%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; foreach($inv['items'] as $item): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <span class="item-name"><?= htmlspecialchars($item['product_name']) ?></span>
                        <?php if(!empty($item['description'])): ?>
                            <span class="item-desc"><?= htmlspecialchars($item['description']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $item['quantity'] ?></td>
                    <td class="text-right"><?= $inv['currency_symbol'] . number_format($item['price'], 2) ?></td>
                    <td class="text-right fw-bold"><?= $inv['currency_symbol'] . number_format($item['total'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
        </table>

        <div class="summary-wrapper">
            <table class="summary-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right"><?= $inv['currency_symbol'] . number_format($inv['subtotal'], 2) ?></td>
                </tr>
                <?php if($inv['tax_total'] > 0): ?>
                <tr>
                    <td>Tax</td>
                    <td class="text-right"><?= $inv['currency_symbol'] . number_format($inv['tax_total'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <?php if($inv['discount'] > 0): ?>
                <tr>
                    <td style="color:#b91c1c;">Discount</td>
                    <td class="text-right" style="color:#b91c1c;">- <?= $inv['currency_symbol'] . number_format($inv['discount'], 2) ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td>Grand Total</td>
                    <td class="text-right"><?= $inv['currency_symbol'] . number_format($inv['grand_total'], 2) ?></td>
                </tr>
                <tr>
                    <td>Paid Amount</td>
                    <td class="text-right"><?= $inv['currency_symbol'] . number_format($inv['paid_amount'], 2) ?></td>
                </tr>
                <?php if($inv['outstanding_amount'] > 0): ?>
                <tr class="balance-row">
                    <td>Balance Due</td>
                    <td class="text-right"><?= $inv['currency_symbol'] . number_format($inv['outstanding_amount'], 2) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <footer class="footer-section">
            <div class="terms-box">
                <span class="bill-label" style="border:none; margin-bottom:5px;">Terms & Conditions</span>
                <p style="line-height:1.6; margin:0;">
                    <?= nl2br(htmlspecialchars($inv['terms_conditions'] ?: 'Payment is due within 15 days. Please include the invoice number in your payment reference.')) ?>
                </p>
                <div style="margin-top:15px;">
                    <strong>Bank Details:</strong><br>
                    Bank Name: Example Bank | Account: 123456789 | SWIFT: EXBKUS33
                </div>
            </div>
            
            <div class="auth-box">
                <div style="font-weight:700; font-size:14px; color:var(--primary);"><?= htmlspecialchars($company['name']) ?></div>
                <div style="font-size:10px;">(Authorized Signatory)</div>
            </div>
        </footer>

        <div class="disclaimer">
            This is a computer-generated invoice and does not require a physical signature.
        </div>

    </div>
</body>
</html>