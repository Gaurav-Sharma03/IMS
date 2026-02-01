<?php
require_once __DIR__ . "/../config/database.php";

class Payment {
    private $db;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        $this->company_id = $_SESSION['company_id'] ?? 0;
    }

    // 1. Get Pending Invoices for Client
    public function getPendingInvoices($client_email) {
        $stmt = $this->db->prepare("
            SELECT i.*, c.symbol 
            FROM invoices i
            JOIN clients cl ON i.client_id = cl.client_id
            JOIN currencies c ON i.currency_id = c.currency_id
            WHERE cl.email = ? AND i.status != 'paid'
            ORDER BY i.due_date ASC
        ");
        $stmt->execute([$client_email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Get Payment History
    public function getHistory($client_email) {
        $stmt = $this->db->prepare("
            SELECT p.*, i.invoice_number 
            FROM payments p
            JOIN clients cl ON p.client_id = cl.client_id
            JOIN invoices i ON p.invoice_id = i.invoice_id
            WHERE cl.email = ? 
            ORDER BY p.payment_date DESC
        ");
        $stmt->execute([$client_email]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Process Payment
    public function processPayment($invoice_id, $amount, $method, $client_email) {
        try {
            $this->db->beginTransaction();

            // A. Get Client ID
            $stmt = $this->db->prepare("SELECT client_id FROM clients WHERE email = ?");
            $stmt->execute([$client_email]);
            $client_id = $stmt->fetchColumn();

            if (!$client_id) throw new Exception("Client not found");

            // B. Generate Transaction ID
            $txn_id = 'TXN-' . strtoupper(bin2hex(random_bytes(6)));

            // C. Insert Payment
            // Note: Added 'payment_type' column if your table differentiates between 'invoice' vs 'subscription'
            $sql = "INSERT INTO payments (company_id, invoice_id, client_id, transaction_id, payment_method, amount, payment_date, status) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), 'success')";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$this->company_id, $invoice_id, $client_id, $txn_id, $method, $amount]);

            // D. Update Invoice Status
            $upd = $this->db->prepare("
                UPDATE invoices 
                SET status = 'paid', 
                    paid_amount = paid_amount + ?, 
                    outstanding_amount = 0 
                WHERE invoice_id = ?
            ");
            $upd->execute([$amount, $invoice_id]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            // error_log($e->getMessage()); // Good for debugging
            return false;
        }
    }
}
?>