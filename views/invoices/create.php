<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/InvoiceController.php';

$controller = new InvoiceController();
$controller->handleRequest(); // Listen for POST
$data = $controller->create();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    .invoice-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 25px; margin-bottom: 25px; }
    .table th { background: #f8fafc; font-size: 0.75rem; text-transform: uppercase; color: #64748b; font-weight: 700; padding: 12px; }
    .total-box { background: #f8fafc; border-radius: 8px; padding: 20px; width: 300px; margin-left: auto; }
    .currency-symbol { font-weight: bold; margin-right: 3px; }
</style>

<main class="main-content">
    
    <form method="POST" id="invoiceForm">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="exchange_rate" id="hiddenExchangeRate" value="1">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark m-0">New Invoice</h4>
            <div class="d-flex gap-2">
                <a href="manage.php" class="btn btn-light border fw-bold">Cancel</a>
                <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fa-solid fa-save me-2"></i> Save Invoice</button>
            </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger mb-4 d-flex align-items-center gap-2">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="invoice-card">
                    <h6 class="text-uppercase fw-bold text-muted small mb-3 border-bottom pb-2">Invoice Details</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Client</label>
                            <select name="client_id" class="form-select" required>
                                <option value="">Select Client</option>
                                <?php foreach($data['clients'] as $c): ?>
                                    <option value="<?= $c['client_id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Invoice Number</label>
                            <input type="text" name="invoice_number" class="form-control bg-light" value="<?= htmlspecialchars($data['nextInvoiceNum']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Invoice Date</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Due Date</label>
                            <input type="date" name="due_date" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Invoice Currency</label>
                            <select name="currency_id" class="form-select" onchange="updateCurrency(this)" required>
                                <?php foreach($data['currencies'] as $cur): ?>
                                    <option value="<?= $cur['currency_id'] ?>" 
                                            data-symbol="<?= $cur['symbol'] ?>" 
                                            data-rate="<?= $cur['exchange_rate'] ?>">
                                        <?= $cur['code'] ?> - <?= $cur['name'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small text-primary fw-bold" id="rateDisplay">Base Rate: 1.00</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="unpaid">Unpaid</option>
                                <option value="paid">Paid</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="invoice-card h-100">
                    <h6 class="text-uppercase fw-bold text-muted small mb-3 border-bottom pb-2">Settings</h6>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Internal Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Terms & Conditions</label>
                        <textarea name="terms" class="form-control" rows="4">Payment is due within 15 days.</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="invoice-card mt-4 p-0 overflow-hidden">
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">Items</h6>
                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="addItemRow()">
                    <i class="fa-solid fa-plus me-1"></i> Add Item
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="itemsTable">
                    <thead>
                        <tr>
                            <th width="30%" class="ps-4">Product</th>
                            <th width="20%">Description</th>
                            <th width="10%">Qty</th>
                            <th width="15%">Price <span class="currency-symbol"></span></th>
                            <th width="15%">Tax</th>
                            <th width="10%" class="text-end pe-4">Total</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <div class="total-box">
                    <div class="total-row">
                        <span>Subtotal:</span> 
                        <span class="fw-bold"><span class="currency-symbol"></span> <span id="displaySubtotal">0.00</span></span>
                    </div>
                    <div class="total-row">
                        <span>Tax:</span> 
                        <span class="fw-bold"><span class="currency-symbol"></span> <span id="displayTax">0.00</span></span>
                    </div>
                    <div class="total-row text-danger align-items-center">
                        <span>Discount:</span> 
                        <div class="input-group input-group-sm w-50">
                            <span class="input-group-text currency-symbol"></span>
                            <input type="number" name="discount_total" id="inputDiscount" class="form-control text-end" value="0" step="0.01" oninput="calculateTotals()">
                        </div>
                    </div>
                    <div class="total-row align-items-center">
                        <span>Paid Amount:</span>
                        <div class="input-group input-group-sm w-50">
                            <span class="input-group-text currency-symbol"></span>
                            <input type="number" name="paid_amount" id="inputPaid" class="form-control text-end" value="0" step="0.01" oninput="calculateTotals()">
                        </div>
                    </div>
                    <div class="total-final d-flex justify-content-between mt-2 pt-2 border-top">
                        <span>Grand Total:</span> 
                        <span><span class="currency-symbol"></span> <span id="displayGrandTotal">0.00</span></span>
                    </div>
                    <div class="text-end small text-muted mt-1">
                        Balance: <span class="currency-symbol"></span> <span id="displayBalance">0.00</span>
                    </div>
                </div>
            </div>
        </div>

    </form>
</main>

<script>
    const products = <?= json_encode($data['products']) ?>;
    const taxes = <?= json_encode($data['taxes']) ?>;
    
    let invoiceRate = 1.0;
    let oldInvoiceRate = 1.0; 
    let currentSymbol = '';

    window.addEventListener('DOMContentLoaded', () => {
        const curSelect = document.querySelector('select[name="currency_id"]');
        updateCurrency(curSelect);
        addItemRow(); 
    });

    // 1. UPDATE CURRENCY & RECALCULATE
    function updateCurrency(select) {
        const option = select.options[select.selectedIndex];
        
        oldInvoiceRate = invoiceRate;
        invoiceRate = parseFloat(option.getAttribute('data-rate')) || 1.0;
        currentSymbol = option.getAttribute('data-symbol') || '';

        document.getElementById('hiddenExchangeRate').value = invoiceRate;
        document.getElementById('rateDisplay').innerText = `Rate: 1 Base = ${invoiceRate.toFixed(4)}`;
        document.querySelectorAll('.currency-symbol').forEach(el => el.innerText = currentSymbol);

        recalculateAllRows();
        convertManualFields();
    }

    // 2. CROSS-CURRENCY CALCULATION
    // Formula: (BasePrice * ProductRate) / InvoiceRate
    function calculateConvertedPrice(basePrice, productRate) {
        // Convert Product Price to System Base Currency
        const priceInBase = basePrice * productRate;
        // Convert System Base Currency to Selected Invoice Currency
        return priceInBase / invoiceRate;
    }

    function recalculateAllRows() {
        const rows = document.querySelectorAll('#itemsBody tr');
        rows.forEach(row => {
            const id = row.id.split('_')[1];
            const select = document.querySelector(`#row_${id} select[name="product_id[]"]`);
            
            if(select && select.value) {
                const option = select.options[select.selectedIndex];
                const basePrice = parseFloat(option.getAttribute('data-base-price')) || 0;
                const prodRate = parseFloat(option.getAttribute('data-prod-rate')) || 1.0; // Rate of product's currency
                
                const newPrice = calculateConvertedPrice(basePrice, prodRate);
                document.getElementById(`price_${id}`).value = newPrice.toFixed(2);
            }
            calculateRow(id);
        });
    }

    function updateRowProduct(select, id) {
        const option = select.options[select.selectedIndex];
        if (option.value) {
            const basePrice = parseFloat(option.getAttribute('data-base-price')) || 0;
            const prodRate = parseFloat(option.getAttribute('data-prod-rate')) || 1.0;
            
            const newPrice = calculateConvertedPrice(basePrice, prodRate);
            
            document.getElementById(`price_${id}`).value = newPrice.toFixed(2);
            document.getElementById(`desc_${id}`).value = option.getAttribute('data-desc');
            calculateRow(id);
        }
    }

    // 3. ROW LOGIC
    function addItemRow() {
        const rowId = Date.now();
        let productOptions = '<option value="">Select Product</option>';
        products.forEach(p => {
            productOptions += `<option value="${p.product_id}" 
                                       data-base-price="${p.price}" 
                                       data-prod-rate="${p.product_exchange_rate}" 
                                       data-desc="${p.description || ''}">
                                ${p.name}
                               </option>`;
        });

        let taxOptions = '<option value="">No Tax</option>';
        taxes.forEach(t => {
            taxOptions += `<option value="${t.tax_id}" data-rate="${t.rate}">${t.name} (${t.rate}%)</option>`;
        });

        const html = `
            <tr id="row_${rowId}">
                <td class="ps-4">
                    <select name="product_id[]" class="form-select form-select-sm" onchange="updateRowProduct(this, ${rowId})">
                        ${productOptions}
                    </select>
                </td>
                <td><input type="text" name="description[]" id="desc_${rowId}" class="form-control form-control-sm"></td>
                <td><input type="number" name="qty[]" id="qty_${rowId}" class="form-control form-control-sm" value="1" min="1" oninput="calculateRow(${rowId})"></td>
                <td><input type="number" name="price[]" id="price_${rowId}" class="form-control form-control-sm" step="0.01" oninput="calculateRow(${rowId})"></td>
                <td>
                    <select name="tax_id[]" id="tax_${rowId}" class="form-select form-select-sm" onchange="calculateRow(${rowId})">
                        ${taxOptions}
                    </select>
                </td>
                <td class="text-end pe-4 fw-bold align-middle row-total" id="total_${rowId}">0.00</td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm text-danger" onclick="removeRow(${rowId})"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
        `;
        document.getElementById('itemsBody').insertAdjacentHTML('beforeend', html);
    }

    function calculateRow(id) {
        const qty = parseFloat(document.getElementById(`qty_${id}`).value) || 0;
        const price = parseFloat(document.getElementById(`price_${id}`).value) || 0;
        const total = qty * price;
        document.getElementById(`total_${id}`).innerText = total.toFixed(2);
        calculateTotals();
    }

    function removeRow(id) {
        document.getElementById(`row_${id}`).remove();
        calculateTotals();
    }

    function convertManualFields() {
        const discInput = document.getElementById('inputDiscount');
        const paidInput = document.getElementById('inputPaid');
        let discVal = parseFloat(discInput.value) || 0;
        let paidVal = parseFloat(paidInput.value) || 0;

        if (invoiceRate > 0 && oldInvoiceRate > 0) {
            // Convert using Ratio: (Val * OldRate) / NewRate
            // Logic: OldVal -> Base -> NewVal
            const baseDisc = discVal * oldInvoiceRate; 
            const basePaid = paidVal * oldInvoiceRate;
            
            discInput.value = (baseDisc / invoiceRate).toFixed(2);
            paidInput.value = (basePaid / invoiceRate).toFixed(2);
        }
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0;
        let taxTotal = 0;

        const rows = document.querySelectorAll('#itemsBody tr');
        rows.forEach(row => {
            const id = row.id.split('_')[1];
            const qty = parseFloat(document.getElementById(`qty_${id}`).value) || 0;
            const price = parseFloat(document.getElementById(`price_${id}`).value) || 0;
            const lineTotal = qty * price;
            
            subtotal += lineTotal;

            const taxSelect = document.getElementById(`tax_${id}`);
            const taxRate = parseFloat(taxSelect.options[taxSelect.selectedIndex].getAttribute('data-rate')) || 0;
            taxTotal += (lineTotal * taxRate / 100);
        });

        const discount = parseFloat(document.getElementById('inputDiscount').value) || 0;
        const paid = parseFloat(document.getElementById('inputPaid').value) || 0;
        
        const grandTotal = subtotal + taxTotal - discount;
        const balance = grandTotal - paid;

        document.getElementById('displaySubtotal').innerText = subtotal.toFixed(2);
        document.getElementById('displayTax').innerText = taxTotal.toFixed(2);
        document.getElementById('displayGrandTotal').innerText = grandTotal.toFixed(2);
        document.getElementById('displayBalance').innerText = balance.toFixed(2);
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>