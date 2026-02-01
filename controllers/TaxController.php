<?php
require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Tax.php";

class TaxController {
    
    private $taxModel;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        $this->taxModel = new Tax();
        $this->company_id = $_SESSION['company_id'];
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add') {
                if (!empty($_POST['tax_id'])) {
                    $this->update();
                } else {
                    $this->store();
                }
            } elseif ($action === 'delete') {
                $this->delete();
            }
        }
    }

    /* ================= STORE (ADD) ================= */
    public function store() {
        $data = [
            'company_id'  => $this->company_id,
            'name'        => trim($_POST['name']),
            'rate'        => (float)$_POST['rate'],
            'country'     => trim($_POST['country']),
            'currency_id' => trim($_POST['currency_id']),
            'description' => trim($_POST['description'])
        ];

        // Validation
        if (empty($data['name']) || $data['rate'] < 0) {
            header("Location: " . BASE_URL . "views/taxes/manage.php?error=required");
            exit;
        }

        $result = $this->taxModel->addTax($data);

        if ($result) {
            header("Location: " . BASE_URL . "views/taxes/manage.php?success=added");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/taxes/manage.php?error=exists");
            exit;
        }
    }

    /* ================= UPDATE ================= */
    public function update() {
        $data = [
            'tax_id'      => $_POST['tax_id'],
            'company_id'  => $this->company_id,
            'name'        => trim($_POST['name']),
            'rate'        => (float)$_POST['rate'],
            'country'     => trim($_POST['country']),
            'currency_id' => trim($_POST['currency_id']),
            'description' => trim($_POST['description'])
        ];

        if ($this->taxModel->updateTax($data)) {
            header("Location: " . BASE_URL . "views/taxes/manage.php?success=updated");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/taxes/manage.php?error=db");
            exit;
        }
    }

    /* ================= DELETE ================= */
    public function delete() {
        $tax_id = $_POST['tax_id'];
        
        if ($this->taxModel->deleteTax($tax_id, $this->company_id)) {
            header("Location: " . BASE_URL . "views/taxes/manage.php?success=deleted");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/taxes/manage.php?error=db");
            exit;
        }
    }

    /* ================= LIST ================= */
    public function index($search = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        return [
            'taxes' => $this->taxModel->getAllTaxes($this->company_id, $limit, $offset, $search),
            'total' => $this->taxModel->countTaxes($this->company_id, $search)
        ];
    }
}
?>