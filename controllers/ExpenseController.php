<?php
require_once __DIR__ . "/../models/Expense.php";

class ExpenseController {
    private $model;

    public function __construct() {
        $this->model = new Expense();
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Add Expense
            if (isset($_POST['add_expense'])) {
                $this->store();
            
            // 2. Edit Expense (NEW)
            } elseif (isset($_POST['edit_expense'])) {
                $this->update();

            // 3. Delete Expense
            } elseif (isset($_POST['delete_expense'])) {
                $this->delete();

            // 4. Add Category
            } elseif (isset($_POST['add_category'])) {
                $this->storeCategory();

            // 5. Delete Category (NEW)
            } elseif (isset($_POST['delete_category'])) {
                $this->removeCategory();
            }
        }
    }

    /* ================= EXPENSE ACTIONS ================= */

    // Create New Expense
    public function store() {
        $data = [
            'category_id' => $_POST['category_id'],
            'description' => $_POST['description'],
            'amount'      => $_POST['amount'],
            'date'        => $_POST['expense_date']
        ];
        
        if ($this->model->create($data)) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=expense_added");
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=failed");
        }
        exit;
    }

    // Update Existing Expense
    public function update() {
        $data = [
            'expense_id'  => $_POST['expense_id'],
            'category_id' => $_POST['category_id'],
            'description' => $_POST['description'],
            'amount'      => $_POST['amount'],
            'date'        => $_POST['expense_date']
        ];

        if ($this->model->update($data)) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=expense_updated");
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=failed");
        }
        exit;
    }

    // Delete Expense
    public function delete() {
        if ($this->model->delete($_POST['expense_id'])) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=expense_deleted");
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=failed");
        }
        exit;
    }

    /* ================= CATEGORY ACTIONS ================= */

    // Create New Category
    public function storeCategory() {
        $name = trim($_POST['category_name']);
        if (!empty($name) && $this->model->createCategory($name)) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=category_added");
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=category_failed");
        }
        exit;
    }

    // Delete Category
    public function removeCategory() {
        if ($this->model->deleteCategory($_POST['category_id'])) {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=category_deleted");
        } else {
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&error=failed");
        }
        exit;
    }

    // Fetch Categories (For Dropdown)
    public function getCategories() {
        return $this->model->getCategories();
    }
}
?>