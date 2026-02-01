<?php
require_once __DIR__ . "/../config/database.php";

class Invoice {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function generateId() {
        return strtoupper(bin2hex(random_bytes(8)));
    }

    /* ==========================================================
       1. CREATE INVOICE
       - Saves Invoice & Items
       - Sends New Notification (Compatible with new table)
       - Sends Email
    ========================================================== */
    public function createInvoice($data, $items) {
        $this->conn->beginTransaction();
        try {
            $invoiceId = $this->generateId();
            
            // Calculate Totals
            $totals = $this->calculateTotals($items, $data['discount']);
            $outstanding = $totals['grand_total'] - $data['paid_amount'];

            // Insert Header
            $sql = "INSERT INTO invoices 
                    (invoice_id, company_id, client_id, currency_id, invoice_number, invoice_date, due_date, 
                     subtotal, tax_total, discount, grand_total, paid_amount, outstanding_amount, 
                     exchange_rate, status, remarks, terms_conditions, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $invoiceId, $data['company_id'], $data['client_id'], $data['currency_id'], $data['invoice_number'], 
                $data['invoice_date'], $data['due_date'], $totals['subtotal'], $totals['tax_total'], $data['discount'], 
                $totals['grand_total'], $data['paid_amount'], $outstanding, $data['exchange_rate'], $data['status'], 
                $data['notes'], $data['terms']
            ]);

            // Insert Items
            $this->insertItems($invoiceId, $items);

            // ----------------------------------------------------
            // 🔔 NEW NOTIFICATION LOGIC
            // ----------------------------------------------------
            
            // 1. Get Client's User ID (Required for new notification table)
            $clientUserId = $this->getUserIdFromClientId($data['client_id']);

            // 2. Notify Client
            if ($clientUserId) {
                $this->createNotification(
                    $data['company_id'],
                    $clientUserId,  // Recipient (Client)
                    "New Invoice #{$data['invoice_number']}",
                    "A new invoice has been generated. Total: " . number_format($totals['grand_total'], 2),
                    'info',         // New Type (Color)
                    BASE_URL . 'views/portal/my-invoices.php', // Link
                    $invoiceId
                );
            }

            // 3. Notify Staff (Creator)
            $this->createNotification(
                $data['company_id'],
                $_SESSION['user_id'], // Recipient (Self)
                "Invoice Created",
                "Invoice #{$data['invoice_number']} created successfully.",
                'success',
                BASE_URL . 'views/invoices/view.php?id=' . $invoiceId,
                $invoiceId
            );

            // 📧 Send Email
            $this->sendEmail($data['client_id'], "New Invoice Generated", "Your invoice #{$data['invoice_number']} is ready.");

            $this->conn->commit();
            return $invoiceId;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /* ==========================================================
       2. UPDATE INVOICE
    ========================================================== */
    public function updateInvoice($data, $items) {
        $this->conn->beginTransaction();
        try {
            $totals = $this->calculateTotals($items, $data['discount']);
            $outstanding = $totals['grand_total'] - $data['paid_amount'];
            $status = ($outstanding <= 0.01) ? 'paid' : (($data['paid_amount'] > 0) ? 'partial' : 'unpaid');

            $sql = "UPDATE invoices SET 
                    client_id=?, currency_id=?, invoice_number=?, invoice_date=?, due_date=?, 
                    subtotal=?, tax_total=?, discount=?, grand_total=?, paid_amount=?, outstanding_amount=?, 
                    exchange_rate=?, status=?, remarks=?, terms_conditions=?, updated_at=NOW() 
                    WHERE invoice_id=? AND company_id=?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['client_id'], $data['currency_id'], $data['invoice_number'], $data['invoice_date'], $data['due_date'], 
                $totals['subtotal'], $totals['tax_total'], $data['discount'], $totals['grand_total'], $data['paid_amount'], 
                $outstanding, $data['exchange_rate'], $status, $data['notes'], $data['terms'], 
                $data['invoice_id'], $data['company_id']
            ]);

            // Replace Items
            $delStmt = $this->conn->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $delStmt->execute([$data['invoice_id']]);
            $this->insertItems($data['invoice_id'], $items);

            // 🔔 Notify Client of Update
            $clientUserId = $this->getUserIdFromClientId($data['client_id']);
            if ($clientUserId) {
                $this->createNotification(
                    $data['company_id'],
                    $clientUserId,
                    "Invoice Updated #{$data['invoice_number']}",
                    "Invoice details updated. New Total: " . number_format($totals['grand_total'], 2),
                    'warning',
                    BASE_URL . 'views/portal/my-invoices.php',
                    $data['invoice_id']
                );
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    /* ==========================================================
       3. UPDATE PAYMENT
    ========================================================== */
    public function updatePayment($invoice_id, $amount, $method, $note, $company_id) {
        $invoice = $this->getInvoiceById($invoice_id, $company_id);
        if (!$invoice) return false;
        
        $outstanding = $invoice['grand_total'] - $amount;
        $status = ($outstanding <= 0.01) ? 'paid' : (($amount > 0) ? 'partial' : 'unpaid');

        $sql = "UPDATE invoices SET paid_amount = ?, outstanding_amount = ?, status = ?, payment_method = ?, remarks = ? WHERE invoice_id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if($stmt->execute([$amount, $outstanding, $status, $method, $note, $invoice_id])) {
            
            // 🔔 Notify Client of Payment
            $clientUserId = $this->getUserIdFromClientId($invoice['client_id']);
            if ($clientUserId) {
                $this->createNotification(
                    $company_id,
                    $clientUserId,
                    "Payment Received",
                    "We received a payment of {$amount} for Invoice #{$invoice['invoice_number']}.",
                    'success',
                    BASE_URL . 'views/portal/my-invoices.php',
                    $invoice_id
                );
            }
            
            return true;
        }
        return false;
    }

    /* ==========================================================
       HELPER: Create Notification (Updated for New Table)
    ========================================================== */
    private function createNotification($companyId, $targetUserId, $title, $message, $type, $link, $invoiceId) {
        $senderId = $_SESSION['user_id'] ?? null; 
        $senderType = isset($_SESSION['role']) ? $_SESSION['role'] : 'system';

        // Insert into the UPDATED notifications table
        $sql = "INSERT INTO notifications 
                (company_id, user_id, sender_id, sender_type, invoice_id, 
                 title, message, type, link, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $companyId, 
            $targetUserId, // Must be the USER ID, not Client ID
            $senderId, 
            $senderType, 
            $invoiceId, 
            $title, 
            $message, 
            $type, // 'info', 'success', 'warning', 'danger'
            $link
        ]);
    }

    /* ================= HELPER: Get User ID from Client ID ================= */
    private function getUserIdFromClientId($client_id) {
        // This links the Client Profile to the User Login account via Email
        $stmt = $this->conn->prepare("
            SELECT u.id 
            FROM users u 
            JOIN clients c ON u.email = c.email 
            WHERE c.client_id = ?
        ");
        $stmt->execute([$client_id]);
        return $stmt->fetchColumn();
    }

    /* ==========================================================
       HELPER: Calculations & Item Insertion
    ========================================================== */
    private function calculateTotals($items, $discount) {
        $subtotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $lineTotal = $item['price'] * $item['qty'];
            $subtotal += $lineTotal;
            
            if (!empty($item['tax_id'])) {
                $stmt = $this->conn->prepare("SELECT rate FROM taxes WHERE tax_id = ?");
                $stmt->execute([$item['tax_id']]);
                $rate = $stmt->fetchColumn();
                $taxTotal += ($lineTotal * $rate / 100);
            }
        }
        return [
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => ($subtotal + $taxTotal) - $discount
        ];
    }

    private function insertItems($invoiceId, $items) {
        $sqlItem = "INSERT INTO invoice_items (item_id, invoice_id, product_id, description, price, quantity, tax_id, total) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtItem = $this->conn->prepare($sqlItem);

        foreach ($items as $item) {
            $itemId = $this->generateId();
            $lineTotal = $item['price'] * $item['qty'];
            $stmtItem->execute([
                $itemId, $invoiceId, $item['product_id'], $item['description'], 
                $item['price'], $item['qty'], $item['tax_id'], $lineTotal
            ]);
        }
    }

    /* ================= READ FUNCTIONS ================= */
    
    // REQUIRED: Fixes "Call to undefined method" error
    public function isInvoiceNumberExists($number, $company_id, $exclude_id = null) {
        $sql = "SELECT invoice_id FROM invoices WHERE invoice_number = ? AND company_id = ?";
        $params = [$number, $company_id];
        if ($exclude_id) { $sql .= " AND invoice_id != ?"; $params[] = $exclude_id; }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function getInvoiceById($id, $company_id) {
        $sql = "SELECT i.*, c.name AS client_name, c.email AS client_email, c.address AS client_address, c.phone AS client_phone,
                       curr.code AS currency_code, curr.symbol AS currency_symbol
                FROM invoices i
                LEFT JOIN clients c ON i.client_id = c.client_id
                LEFT JOIN currencies curr ON i.currency_id = curr.currency_id
                WHERE i.invoice_id = ? AND i.company_id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id, $company_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) return false;

        $stmtItems = $this->conn->prepare("
            SELECT ii.*, p.name AS product_name 
            FROM invoice_items ii
            LEFT JOIN products p ON ii.product_id = p.product_id
            WHERE ii.invoice_id = ?
        ");
        $stmtItems->execute([$id]);
        $invoice['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $invoice;
    }

    public function getInvoicesByClientId($client_id) {
        $stmt = $this->conn->prepare("
            SELECT i.*, curr.symbol 
            FROM invoices i
            LEFT JOIN currencies curr ON i.currency_id = curr.currency_id
            WHERE i.client_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$client_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteInvoice($id, $company_id) {
        $inv = $this->getInvoiceById($id, $company_id);
        if ($inv && $inv['status'] === 'paid') return false;

        $stmt = $this->conn->prepare("DELETE FROM invoices WHERE invoice_id = ? AND company_id = ?");
        return $stmt->execute([$id, $company_id]);
    }

    public function getInvoices($limit, $offset, $search, $from, $to) {
        $sql = "SELECT i.*, c.name AS client_name, curr.symbol, curr.code 
                FROM invoices i 
                LEFT JOIN clients c ON i.client_id = c.client_id 
                LEFT JOIN currencies curr ON i.currency_id = curr.currency_id 
                WHERE i.company_id = ?"; 
        
        $params = [$_SESSION['company_id']];

        if ($search) {
            $sql .= " AND (i.invoice_number LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY i.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countInvoices($search) {
        $sql = "SELECT COUNT(*) FROM invoices i LEFT JOIN clients c ON i.client_id = c.client_id WHERE i.company_id = ?";
        $params = [$_SESSION['company_id']];
        if ($search) {
            $sql .= " AND (i.invoice_number LIKE ? OR c.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    // Placeholder for Email
    private function sendEmail($clientId, $subject, $body) {
        return true; 
    }
}
?>