<?php
require_once __DIR__ . "/../config/database.php";

class NotificationController {
    
    private $db;
    private $user_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        $this->user_id = $_SESSION['user_id'] ?? 0;
    }

    // 1. Trigger a New Notification
    public static function create($user_id, $title, $message, $type = 'info', $link = '#') {
        $db = (new Database())->connect();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type, $link]);
    }

    // 2. Fetch Latest for Header
    public function getHeaderNotifications($limit = 5) {
        if(!$this->user_id) return [];
        $sql = "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $this->user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Count Unread
    public function getUnreadCount() {
        if(!$this->user_id) return 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn();
    }

    // 4. Mark All as Read
    public function markAllRead() {
        $stmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$this->user_id]);
        header("Location: " . BASE_URL . "views/notification/notifications.php");
        exit;
    }

    /* ================= NEW METHODS FOR PAGINATION & CLEANUP ================= */

    // 5. Cleanup Old Notifications (> 14 Days)
    public function cleanupOldNotifications() {
        if(!$this->user_id) return;
        // Delete notifications created more than 14 days ago for this user
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE user_id = ? AND created_at < NOW() - INTERVAL 14 DAY");
        $stmt->execute([$this->user_id]);
    }

    // 6. Get Paginated Notifications
    public function getPaginatedNotifications($limit, $offset) {
        if(!$this->user_id) return [];
        $sql = "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $this->user_id, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 7. Get Total Count (For Pagination)
    public function getTotalCount() {
        if(!$this->user_id) return 0;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchColumn();
    }
}
?>