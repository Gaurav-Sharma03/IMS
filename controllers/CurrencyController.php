<?php
require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Currency.php";

class CurrencyController {
    
    private $currencyModel;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        $this->currencyModel = new Currency();
        $this->company_id = $_SESSION['company_id'];
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add') {
                if (!empty($_POST['currency_id'])) {
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
            'company_id'    => $this->company_id,
            'code'          => strtoupper(trim($_POST['code'])),
            'name'          => trim($_POST['name']),
            'symbol'        => trim($_POST['symbol']),
            'exchange_rate' => (float)$_POST['exchange_rate']
        ];

        // Validation
        if (empty($data['code']) || empty($data['name'])) {
            header("Location: " . BASE_URL . "views/currencies/manage.php?error=required");
            exit;
        }

        $result = $this->currencyModel->addCurrency($data);

        if ($result['status']) {
            header("Location: " . BASE_URL . "views/currencies/manage.php?success=added");
            exit;
        } else {
            $msg = urlencode($result['message']);
            header("Location: " . BASE_URL . "views/currencies/manage.php?error=$msg");
            exit;
        }
    }

    /* ================= UPDATE ================= */
    public function update() {
        $data = [
            'currency_id'   => $_POST['currency_id'],
            'company_id'    => $this->company_id,
            'code'          => strtoupper(trim($_POST['code'])),
            'name'          => trim($_POST['name']),
            'symbol'        => trim($_POST['symbol']),
            'exchange_rate' => (float)$_POST['exchange_rate']
        ];

        if ($this->currencyModel->updateCurrency($data)) {
            header("Location: " . BASE_URL . "views/currencies/manage.php?success=updated");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/currencies/manage.php?error=db");
            exit;
        }
    }

    /* ================= DELETE ================= */
    public function delete() {
        $currency_id = $_POST['currency_id'];
        
        if ($this->currencyModel->deleteCurrency($currency_id, $this->company_id)) {
            header("Location: " . BASE_URL . "views/currencies/manage.php?success=deleted");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/currencies/manage.php?error=db");
            exit;
        }
    }

    /* ================= LIST ================= */
    public function index($search = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        return [
            'currencies' => $this->currencyModel->getAllCurrencies($this->company_id, $limit, $offset, $search),
            'total'      => $this->currencyModel->countCurrencies($this->company_id, $search)
        ];
    }
}
?>