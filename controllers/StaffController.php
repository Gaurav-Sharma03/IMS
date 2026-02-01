<?php
// 1. Load Configuration & Models
require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/User.php";

class StaffController {
    
    private $userModel;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Security Check
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        $db = new Database();
        $this->userModel = new User($db->connect());
        $this->company_id = $_SESSION['company_id'];
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'update') {
                $this->update();
            } elseif ($action === 'delete') {
                $this->delete();
            }
        }
    }

    /* ================= UPDATE STAFF ================= */
    public function update() {
        $id = $_POST['user_id'];
        
        $data = [
            'name'   => trim($_POST['name']),
            'email'  => trim($_POST['email']),
            'phone'  => trim($_POST['phone']),
            'status' => $_POST['status']
        ];

        if ($this->userModel->updateUser($id, $data)) {
            header("Location: " . BASE_URL . "views/staff/manage-staff.php?success=updated");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/staff/manage-staff.php?error=db");
            exit;
        }
    }

    /* ================= DELETE STAFF ================= */
    public function delete() {
        $id = $_POST['user_id'];

        if ($this->userModel->deleteUser($id, $this->company_id)) {
            header("Location: " . BASE_URL . "views/staff/manage-staff.php?success=deleted");
            exit;
        } else {
            header("Location: " . BASE_URL . "views/staff/manage-staff.php?error=db");
            exit;
        }
    }
}
?>