<?php
require_once __DIR__ . "/../config/database.php";

class CompanyController {
    
    private $db;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        $this->company_id = $_SESSION['company_id'];
    }

    public function getAdminDashboardData() {
        $cid = $this->company_id;
        $stats = [];

        // 1. STATS: Revenue (Paid Invoices)
        $stmt = $this->db->prepare("SELECT SUM(grand_total) FROM invoices WHERE company_id = ? AND status = 'paid'");
        $stmt->execute([$cid]);
        $stats['revenue'] = $stmt->fetchColumn() ?: 0.00;

        // 2. STATS: Pending Invoices Count
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM invoices WHERE company_id = ? AND status = 'unpaid'");
        $stmt->execute([$cid]);
        $stats['pending_count'] = $stmt->fetchColumn();

        // 3. STATS: Total Clients
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM clients WHERE company_id = ?");
        $stmt->execute([$cid]);
        $stats['clients_count'] = $stmt->fetchColumn();

        // 4. STATS: Overdue Amount
        $stmt = $this->db->prepare("SELECT SUM(outstanding_amount) FROM invoices WHERE company_id = ? AND status != 'paid' AND due_date < CURDATE()");
        $stmt->execute([$cid]);
        $stats['overdue_amount'] = $stmt->fetchColumn() ?: 0.00;

        // 5. RECENT INVOICES
        $stmt = $this->db->prepare("
            SELECT i.*, c.name as client_name, curr.symbol 
            FROM invoices i 
            LEFT JOIN clients c ON i.client_id = c.client_id
            LEFT JOIN currencies curr ON i.currency_id = curr.currency_id
            WHERE i.company_id = ? 
            ORDER BY i.created_at DESC LIMIT 6
        ");
        $stmt->execute([$cid]);
        $recentInvoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 6. STAFF TASKS (Mock table structure assumed, or use empty array if table missing)
        // Ensure you create a 'tasks' table or this returns empty
        $tasks = [];
        try {
            $stmt = $this->db->prepare("SELECT * FROM tasks WHERE company_id = ? AND status != 'completed' ORDER BY due_date ASC LIMIT 4");
            $stmt->execute([$cid]);
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* Ignore if table doesn't exist yet */ }

        // 7. SUPPORT TICKETS
        $tickets = [];
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, c.name as client_name 
                FROM support_tickets t 
                LEFT JOIN clients c ON t.client_id = c.client_id 
                WHERE t.company_id = ? AND t.status != 'closed' 
                ORDER BY t.created_at DESC LIMIT 4
            ");
            $stmt->execute([$cid]);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) { /* Ignore */ }

        return compact('stats', 'recentInvoices', 'tasks', 'tickets');
    }
    // Add this method inside CompanyController class
public function getEnhancedDashboardData() {
    $company_id = $_SESSION['company_id'];
    
    // 1. Basic Stats
    $stats = [
        'revenue' => $this->db->query("SELECT SUM(grand_total) FROM invoices WHERE company_id = $company_id AND status = 'paid'")->fetchColumn() ?: 0,
        'pending_count' => $this->db->query("SELECT COUNT(*) FROM invoices WHERE company_id = $company_id AND status != 'paid'")->fetchColumn(),
        'clients_count' => $this->db->query("SELECT COUNT(*) FROM clients WHERE company_id = $company_id")->fetchColumn(),
        'expense_total' => $this->db->query("SELECT SUM(amount) FROM expenses WHERE company_id = $company_id")->fetchColumn() ?: 0
    ];

    // 2. Chart Data (Last 6 Months Revenue)
    $chartData = $this->db->query("
        SELECT DATE_FORMAT(invoice_date, '%Y-%m') as month, SUM(grand_total) as total 
        FROM invoices WHERE company_id = $company_id AND status = 'paid' 
        GROUP BY month ORDER BY month DESC LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Recent Activity (Merged Invoices & Tickets)
    $activity = $this->db->query("
        (SELECT 'invoice' as type, invoice_number as ref, created_at FROM invoices WHERE company_id = $company_id)
        UNION
        (SELECT 'ticket' as type, subject as ref, created_at FROM support_tickets WHERE company_id = $company_id)
        ORDER BY created_at DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Tasks & Tickets
    $tasks = $this->db->query("SELECT * FROM tasks WHERE company_id = $company_id AND status != 'completed' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    $tickets = $this->db->query("SELECT t.*, c.name as client_name FROM support_tickets t JOIN clients c ON t.client_id = c.client_id WHERE t.company_id = $company_id AND t.status = 'open' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    return compact('stats', 'chartData', 'activity', 'tasks', 'tickets');
}
}
?>