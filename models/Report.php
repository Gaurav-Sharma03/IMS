<?php
require_once __DIR__ . "/../config/database.php";

class Report {
    private $db;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        $this->company_id = $_SESSION['company_id'];
    }

    // 1. Get Total Income (Paid Invoices)
    public function getIncome($from, $to) {
        $sql = "SELECT SUM(grand_total) as total, SUM(tax_total) as tax 
                FROM invoices 
                WHERE company_id = ? AND status = 'paid' 
                AND invoice_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->company_id, $from, $to]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. Get Total Expenses
    public function getExpenses($from, $to) {
        $sql = "SELECT SUM(amount) as total 
                FROM expenses 
                WHERE company_id = ? 
                AND expense_date BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->company_id, $from, $to]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Get Expense Breakdown by Category (For Charts)
    public function getExpenseByCategory($from, $to) {
        $sql = "SELECT c.name, SUM(e.amount) as total 
                FROM expenses e
                LEFT JOIN expense_categories c ON e.category_id = c.category_id
                WHERE e.company_id = ? AND e.expense_date BETWEEN ? AND ?
                GROUP BY c.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->company_id, $from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Get Detailed Ledger (With Pagination)
    public function getLedger($from, $to, $limit = 10, $offset = 0) {
        $sql = "
            (SELECT 
                invoice_id as id, 
                NULL as category_id, 
                invoice_date as date, 
                CONCAT('Invoice #', invoice_number) as description, 
                'Income' as type, 
                grand_total as amount 
             FROM invoices 
             WHERE company_id = ? AND status = 'paid' AND invoice_date BETWEEN ? AND ?)
            
            UNION ALL
            
            (SELECT 
                expense_id as id, 
                category_id, 
                expense_date as date, 
                description, 
                'Expense' as type, 
                amount 
             FROM expenses 
             WHERE company_id = ? AND expense_date BETWEEN ? AND ?)
            
            ORDER BY date DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind values explicitly to ensure Integers are treated correctly for LIMIT/OFFSET
        $stmt->bindValue(1, $this->company_id);
        $stmt->bindValue(2, $from);
        $stmt->bindValue(3, $to);
        $stmt->bindValue(4, $this->company_id);
        $stmt->bindValue(5, $from);
        $stmt->bindValue(6, $to);
        $stmt->bindValue(7, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(8, (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Count Total Ledger Entries (For Pagination Links)
    public function countLedgerEntries($from, $to) {
        $sql = "
            SELECT COUNT(*) as total FROM (
                (SELECT invoice_id 
                 FROM invoices 
                 WHERE company_id = ? AND status = 'paid' AND invoice_date BETWEEN ? AND ?)
                
                UNION ALL
                
                (SELECT expense_id 
                 FROM expenses 
                 WHERE company_id = ? AND expense_date BETWEEN ? AND ?)
            ) as combined_table
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->company_id, $from, $to, 
            $this->company_id, $from, $to
        ]);
        
        return $stmt->fetchColumn();
    }
}
?>