<?php
require_once __DIR__ . '/../config/database.php';

class SubscriptionController {
    private $db;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
    }

    /* ==========================
       SUBSCRIPTION LOGIC
    ========================== */

    // 1. Fetch All Subscriptions
    public function getAllSubscriptions($search = '', $status = '') {
        $sql = "
            SELECT s.*, c.name as company_name, c.email as company_email, p.name as plan_name, p.price
            FROM subscriptions s
            JOIN companies c ON s.company_id = c.company_id
            JOIN plans p ON s.plan_id = p.plan_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($status)) {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        if (!empty($search)) {
            $sql .= " AND (c.name LIKE ? OR p.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY CASE WHEN s.status = 'pending_approval' THEN 0 ELSE 1 END, s.end_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Fetch Companies (FIXED: Added this missing method)
    public function getCompanies() {
        return $this->db->query("SELECT company_id, name FROM companies ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Approve Request
    public function approveSubscription($sub_id) {
        $sub = $this->db->query("SELECT p.duration FROM subscriptions s JOIN plans p ON s.plan_id = p.plan_id WHERE s.sub_id = $sub_id")->fetch();
        $duration = $sub['duration'] ?? 30;

        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$duration days"));

        $stmt = $this->db->prepare("UPDATE subscriptions SET status = 'active', start_date = ?, end_date = ?, payment_status = 'paid' WHERE sub_id = ?");
        return $stmt->execute([$start_date, $end_date, $sub_id]);
    }

    // 4. Cancel Subscription
    public function cancelSubscription($sub_id) {
        $stmt = $this->db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE sub_id = ?");
        return $stmt->execute([$sub_id]);
    }

    // 5. Assign Plan Manually
    public function assignSubscription($data) {
        $plan = $this->db->query("SELECT duration FROM plans WHERE plan_id = " . intval($data['plan_id']))->fetch();
        $duration = $plan['duration'] ?? 30;
        
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$duration days"));

        // Cancel previous active subscriptions for this company to avoid duplicates
        $this->db->prepare("UPDATE subscriptions SET status = 'cancelled' WHERE company_id = ? AND status = 'active'")->execute([$data['company_id']]);

        $sql = "INSERT INTO subscriptions (company_id, plan_id, start_date, end_date, status, payment_status) VALUES (?, ?, ?, ?, 'active', 'paid')";
        return $this->db->prepare($sql)->execute([$data['company_id'], $data['plan_id'], $start_date, $end_date]);
    }

    /* ==========================
       PLAN MANAGEMENT
    ========================== */

    public function getPlans() {
        return $this->db->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function savePlan($data) {
        if (!empty($data['plan_id'])) {
            $sql = "UPDATE plans SET name=?, price=?, duration=?, features=? WHERE plan_id=?";
            return $this->db->prepare($sql)->execute([$data['name'], $data['price'], $data['duration'], $data['features'], $data['plan_id']]);
        } else {
            $sql = "INSERT INTO plans (name, price, duration, features) VALUES (?, ?, ?, ?)";
            return $this->db->prepare($sql)->execute([$data['name'], $data['price'], $data['duration'], $data['features']]);
        }
    }

    public function deletePlan($id) {
        return $this->db->prepare("UPDATE plans SET deleted_at = NOW() WHERE plan_id = ?")->execute([$id]);
    }

    public function getStats() {
        return [
            'active_mrr' => $this->db->query("SELECT SUM(p.price) FROM subscriptions s JOIN plans p ON s.plan_id = p.plan_id WHERE s.status = 'active'")->fetchColumn() ?: 0,
            'pending_reqs' => $this->db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'pending_approval'")->fetchColumn(),
            'active_count' => $this->db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn()
        ];
    }
}
?>