<?php
require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Invoice.php";
require_once __DIR__ . "/../controllers/NotificationController.php";

class InvoiceController {
    
    private $db;
    private $invoiceModel;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        $this->db = (new Database())->connect();
        $this->invoiceModel = new Invoice($this->db);
        $this->company_id = $_SESSION['company_id'];
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'create':
                    $this->store();
                    break;
                case 'update':
                    $this->update();
                    break;
                case 'update_payment':
                    $this->updatePayment();
                    break;
                case 'delete':
                    $this->delete();
                    break;
                case 'send':
                    $this->sendInvoice();
                    break;
            }
        }
    }

    /* ================= 1. SHOW CREATE FORM ================= */
    public function create() {
        $clients = $this->db->query("SELECT * FROM clients WHERE company_id = {$this->company_id}")->fetchAll(PDO::FETCH_ASSOC);
        
        $sqlProducts = "
            SELECT 
                p.product_id, p.name, p.price, p.description, p.currency_id, 
                COALESCE(c.exchange_rate, 1.00) as product_exchange_rate
            FROM products p
            LEFT JOIN currencies c ON p.currency_id = c.currency_id
            WHERE p.company_id = {$this->company_id} AND p.status = 'active'
        ";
        $products = $this->db->query($sqlProducts)->fetchAll(PDO::FETCH_ASSOC);
        
        $currencies = $this->db->query("SELECT * FROM currencies WHERE company_id = {$this->company_id}")->fetchAll(PDO::FETCH_ASSOC);
        $taxes = $this->db->query("SELECT * FROM taxes WHERE company_id = {$this->company_id}")->fetchAll(PDO::FETCH_ASSOC);

        $count = $this->db->query("SELECT COUNT(*) FROM invoices WHERE company_id = {$this->company_id}")->fetchColumn();
        $nextInvoiceNum = 'INV-' . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

        return compact('clients', 'products', 'currencies', 'taxes', 'nextInvoiceNum');
    }

    /* ================= 2. STORE (CREATE) ================= */
    public function store() {
        if ($this->invoiceModel->isInvoiceNumberExists($_POST['invoice_number'], $this->company_id)) {
            header("Location: " . BASE_URL . "views/invoices/create.php?error=Duplicate+Invoice+Number");
            exit;
        }

        $data = $this->collectPostData();
        $items = $this->collectItems();

        try {
            $invoiceId = $this->invoiceModel->createInvoice($data, $items);
            $_SESSION['new_invoice'] = ['id' => $invoiceId, 'number' => $data['invoice_number']];
            header("Location: " . BASE_URL . "views/invoices/manage.php?success=created");
            exit;
        } catch (Exception $e) {
            header("Location: " . BASE_URL . "views/invoices/create.php?error=" . urlencode($e->getMessage()));
            exit;
        }
    }

    /* ================= 3. SHOW EDIT FORM ================= */
    public function edit($id) {
        $invoice = $this->invoiceModel->getInvoiceById($id, $this->company_id);
        
        if (!$invoice) {
            header("Location: " . BASE_URL . "views/invoices/manage.php?error=not_found");
            exit;
        }

        $data = $this->create(); 
        return array_merge($data, ['invoice' => $invoice]);
    }

    /* ================= 4. UPDATE ================= */
    public function update() {
        if ($this->invoiceModel->isInvoiceNumberExists($_POST['invoice_number'], $this->company_id, $_POST['invoice_id'])) {
            header("Location: " . BASE_URL . "views/invoices/edit.php?id=".$_POST['invoice_id']."&error=Duplicate+Invoice+Number");
            exit;
        }

        $data = $this->collectPostData();
        $data['invoice_id'] = $_POST['invoice_id'];
        $items = $this->collectItems();

        if ($this->invoiceModel->updateInvoice($data, $items)) {
            header("Location: " . BASE_URL . "views/invoices/manage.php?success=updated");
        } else {
            header("Location: " . BASE_URL . "views/invoices/manage.php?error=db");
        }
        exit;
    }

    /* ================= 5. UPDATE PAYMENT ONLY ================= */
    public function updatePayment() {
        $id = $_POST['invoice_id'];
        $amount = (float)$_POST['paid_amount'];
        $method = $_POST['payment_method'];
        $note = $_POST['payment_note'];

        if ($this->invoiceModel->updatePayment($id, $amount, $method, $note, $this->company_id)) {
            header("Location: " . BASE_URL . "views/invoices/manage.php?success=payment_updated");
        } else {
            header("Location: " . BASE_URL . "views/invoices/manage.php?error=db");
        }
        exit;
    }

    /* ================= 6. DELETE INVOICE ================= */
    public function delete() {
        $id = $_POST['invoice_id'];
        
        if ($this->invoiceModel->deleteInvoice($id, $this->company_id)) {
            header("Location: " . BASE_URL . "views/invoices/manage.php?success=deleted");
        } else {
            header("Location: " . BASE_URL . "views/invoices/manage.php?error=db");
        }
        exit;
    }

    /* ================= 7. SEND INVOICE ================= */
    public function sendInvoice() {
        $invoiceId = $_POST['invoice_id'];
        $invoice = $this->invoiceModel->getInvoiceById($invoiceId, $this->company_id);
        
        if (!$invoice) {
            header("Location: " . BASE_URL . "views/invoices/manage.php?error=not_found");
            exit;
        }

        // Find Client User ID
        $stmt = $this->db->prepare("SELECT u.id FROM users u JOIN clients c ON u.email = c.email WHERE c.client_id = ?");
        $stmt->execute([$invoice['client_id']]);
        $clientUserId = $stmt->fetchColumn();

        $notifSent = false;

        if ($clientUserId) {
            NotificationController::create(
                $clientUserId,
                "Invoice Sent",
                "Invoice #{$invoice['invoice_number']} was resent to you.",
                'info',
                BASE_URL . 'views/portal/my-invoices.php'
            );
            $notifSent = true;
        }

        if ($notifSent) {
            header("Location: " . BASE_URL . "views/invoices/manage.php?success=sent_dashboard");
        } else {
            header("Location: " . BASE_URL . "views/invoices/manage.php?error=client_no_account");
        }
        exit;
    }

    /* ================= 8. LIST INVOICES (WITH PAGINATION) ================= */
    public function index($search = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        // 1. Get Records
        $invoices = $this->invoiceModel->getInvoices($limit, $offset, $search, '', '');
        
        // 2. Get Total Count (For Calculation)
        $totalRecords = $this->invoiceModel->countInvoices($search);
        
        // 3. Calculate Total Pages
        $totalPages = ceil($totalRecords / $limit);

        return [
            'invoices'      => $invoices,
            'total_records' => $totalRecords,
            'total_pages'   => $totalPages,
            'current_page'  => $page
        ];
    }

   /* ================= HELPERS ================= */
    private function collectPostData() {
        return [
            'company_id'      => $this->company_id,
            'client_id'       => $_POST['client_id'],
            'currency_id'     => $_POST['currency_id'],
            'exchange_rate'   => (float)$_POST['exchange_rate'],
            'invoice_number'  => $_POST['invoice_number'],
            'invoice_date'    => $_POST['invoice_date'],
            'due_date'        => $_POST['due_date'],
            'discount'        => (float)($_POST['discount_total'] ?? 0),
            'paid_amount'     => (float)($_POST['paid_amount'] ?? 0),
            'status'          => $_POST['status'],
            'notes'           => $_POST['notes'],
            'terms'           => $_POST['terms']
        ];
    }

    private function collectItems() {
        $items = [];
        $product_ids = $_POST['product_id'] ?? [];
        
        foreach ($product_ids as $i => $pid) {
            if (empty($pid)) continue;

            $items[] = [
                'product_id'  => $pid,
                'description' => $_POST['description'][$i] ?? '',
                'price'       => (float)$_POST['price'][$i],
                'qty'         => (int)$_POST['qty'][$i],
                'tax_id'      => !empty($_POST['tax_id'][$i]) ? $_POST['tax_id'][$i] : null
            ];
        }
        return $items;
    }
}
?>