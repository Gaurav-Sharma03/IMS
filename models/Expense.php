<?php
require_once __DIR__ . "/../config/database.php";

class Expense {
    private $db;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        $this->company_id = $_SESSION['company_id'] ?? 0;
    }

    /* =========================================================
       SECTION A: EXPENSE MANAGEMENT (CRUD)
    ========================================================= */

    // 1. Create New Expense
    public function create($data) {
        // Fix: Get a valid currency ID safely to prevent FK Error
        $currencyId = $this->getDefaultCurrencyId();

        $sql = "INSERT INTO expenses (expense_id, company_id, category_id, currency_id, description, amount, expense_date, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        // Generate random ID (e.g., A1B2C3D4E5)
        $id = strtoupper(bin2hex(random_bytes(5)));
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $id, 
            $this->company_id, 
            $data['category_id'], 
            $currencyId, 
            $data['description'], 
            $data['amount'], 
            $data['date']
        ]);
    }

    // 2. Update Existing Expense
    public function update($data) {
        $sql = "UPDATE expenses SET category_id = ?, description = ?, amount = ?, expense_date = ? 
                WHERE expense_id = ? AND company_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['category_id'],
            $data['description'],
            $data['amount'],
            $data['date'],
            $data['expense_id'],
            $this->company_id
        ]);
    }

    // 3. Delete Expense
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM expenses WHERE expense_id = ? AND company_id = ?");
        return $stmt->execute([$id, $this->company_id]);
    }

    /* =========================================================
       SECTION B: CATEGORY MANAGEMENT
    ========================================================= */

    // 4. Get All Categories
    public function getCategories() {
        $stmt = $this->db->prepare("SELECT * FROM expense_categories WHERE company_id = ? OR company_id = 0");
        $stmt->execute([$this->company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Create New Category
    public function createCategory($name) {
        $stmt = $this->db->prepare("INSERT INTO expense_categories (company_id, name) VALUES (?, ?)");
        return $stmt->execute([$this->company_id, $name]);
    }

    // 6. Delete Category
    public function deleteCategory($id) {
        $stmt = $this->db->prepare("DELETE FROM expense_categories WHERE category_id = ? AND company_id = ?");
        return $stmt->execute([$id, $this->company_id]);
    }

    /* =========================================================
       SECTION C: HELPERS
    ========================================================= */

    // Helper: Fixes the Integrity Constraint Violation Error
    // Finds the first available currency ID if the company doesn't have one set specifically
    private function getDefaultCurrencyId() {
        $stmt = $this->db->query("SELECT currency_id FROM currencies LIMIT 1");
        $id = $stmt->fetchColumn();
        return $id ?: null;
    }
}
?>