<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Product.php";

class ProductController {
    
    private $productModel;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            header("Location: ../auth/login.php");
            exit;
        }

        $this->productModel = new Product();
        $this->company_id = $_SESSION['company_id'];
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add') {
                if (!empty($_POST['product_id'])) {
                    $this->update();
                } else {
                    $this->store();
                }
            } elseif ($action === 'delete') {
                $this->delete();
            }
        }
    }

    /* =====================================================
       1. STORE (ADD)
    ===================================================== */
    public function store() {
        $data = [
            'company_id'     => $this->company_id,
            'name'           => trim($_POST['name']),
            'sku'            => trim($_POST['sku']),
            'category'       => trim($_POST['category'] ?? ''),
            'product_type'   => $_POST['product_type'] ?? 'product',
            'unit'           => trim($_POST['unit'] ?? 'pcs'),
            'price'          => (float)$_POST['price'],
            'currency_id'    => trim($_POST['currency_id']), // Fixed: String ID
            'tax_id'         => !empty($_POST['tax_id']) ? trim($_POST['tax_id']) : null, // Fixed: String ID
            'description'    => trim($_POST['description']),
            'stock_quantity' => (int)($_POST['stock_quantity'] ?? 0),
            'status'         => $_POST['status'] ?? 'active'
        ];

        if (empty($data['name']) || $data['price'] < 0) {
            header("Location: " . BASE_URL . "views/products/add.php?error=required");
            exit;
        }

        if ($this->productModel->addProduct($data)) {
            header("Location: " . BASE_URL . "views/products/manage.php?success=added");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/products/add.php?error=exists");
            exit;
        }
    }

    /* =====================================================
       2. UPDATE
    ===================================================== */
    public function update() {
        $data = [
            'product_id'     => $_POST['product_id'],
            'name'           => trim($_POST['name']),
            'sku'            => trim($_POST['sku']),
            'category'       => trim($_POST['category'] ?? ''),
            'product_type'   => $_POST['product_type'] ?? 'product',
            'unit'           => trim($_POST['unit'] ?? 'pcs'),
            'price'          => (float)$_POST['price'],
            'currency_id'    => trim($_POST['currency_id']), // Fixed
            'tax_id'         => !empty($_POST['tax_id']) ? trim($_POST['tax_id']) : null, // Fixed
            'stock_quantity' => (int)$_POST['stock_quantity'],
            'description'    => trim($_POST['description']),
            'status'         => $_POST['status']
        ];

        if ($this->productModel->updateProduct($data, $this->company_id)) {
            header("Location: " . BASE_URL . "views/products/manage.php?success=updated");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/products/manage.php?error=db");
            exit;
        }
    }

    /* =====================================================
       3. DELETE
    ===================================================== */
    public function delete() {
        $product_id = $_POST['product_id'];
        
        if ($this->productModel->deleteProduct($product_id, $this->company_id)) {
            header("Location: " . BASE_URL . "views/products/manage.php?success=deleted");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/products/manage.php?error=db");
            exit;
        }
    }

    public function index($search = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        return [
            'products' => $this->productModel->getAllProducts($this->company_id, $limit, $offset, $search),
            'total'    => $this->productModel->countProducts($this->company_id, $search)
        ];
    }
}
?>