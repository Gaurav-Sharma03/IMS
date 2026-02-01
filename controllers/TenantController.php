<?php
require_once __DIR__ . '/../config/database.php';

class TenantController {
    private $db;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        $this->company_id = $_SESSION['company_id'] ?? 0;
    }

    // 1. Fetch Available Plans
    public function getAvailablePlans() {
        return $this->db->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Get Current Subscription Status
    public function getCurrentSubscription() {
        $stmt = $this->db->prepare("
            SELECT s.*, p.name as plan_name 
            FROM subscriptions s 
            JOIN plans p ON s.plan_id = p.plan_id
            WHERE s.company_id = ? AND s.status IN ('active', 'pending_approval')
            ORDER BY s.created_at DESC LIMIT 1
        ");
        $stmt->execute([$this->company_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 3. Handle Plan Request
    public function requestPlan($plan_id) {
        // Security: Check if already has active/pending sub
        $current = $this->getCurrentSubscription();
        if ($current && $current['status'] === 'active') {
            return ['status' => 'error', 'message' => 'You already have an active plan.'];
        }
        if ($current && $current['status'] === 'pending_approval') {
            return ['status' => 'error', 'message' => 'A request is already pending.'];
        }

        // Fetch Plan Duration
        $plan = $this->db->query("SELECT duration, price FROM plans WHERE plan_id = " . intval($plan_id))->fetch();
        if (!$plan) return ['status' => 'error', 'message' => 'Invalid plan selected.'];

        $duration = $plan['duration'] ?? 30;
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$duration days"));

        // Insert Request
        $sql = "INSERT INTO subscriptions (company_id, plan_id, start_date, end_date, status, payment_status) 
                VALUES (?, ?, ?, ?, 'pending_approval', 'pending')";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$this->company_id, $plan_id, $start_date, $end_date])) {
            return ['status' => 'success', 'message' => 'Plan activation requested successfully.'];
        }
        return ['status' => 'error', 'message' => 'Database error.'];
    }
}
?>