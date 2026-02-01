<?php
require_once __DIR__ . '/../config/database.php';

class SupportController {
    private $db;
    private $user_id;
    private $role;
    private $company_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        
        $this->user_id = $_SESSION['user_id'] ?? 0;
        $this->role = $_SESSION['role'] ?? 'guest'; 
        $this->company_id = $_SESSION['company_id'] ?? 0;
    }

    // --- 1. CREATE PLATFORM TICKET (Admin -> Superadmin) ---
    public function createPlatformTicket() {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if(empty($subject) || empty($message)) {
            return ['status' => 'error', 'message' => 'Subject and Message are required'];
        }

        try {
            // client_id = 0 indicates a "System/Platform" ticket
            $stmt = $this->db->prepare("INSERT INTO support_tickets (client_id, company_id, subject, message, status, created_at) VALUES (0, ?, ?, ?, 'open', NOW())");
            $stmt->execute([$this->company_id, $subject, $message]);
            
            return ['status' => 'success'];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // --- 2. GET TICKET DETAILS (Security & Context) ---
    public function getTicketDetails($ticket_id) {
        if (empty($ticket_id)) return ['error' => 'Invalid ID'];

        try {
            $sql = "SELECT t.*, 
                           COALESCE(c.name, 'Platform Support') as client_name, 
                           c.email as client_email, 
                           c.phone as client_phone,
                           comp.name as company_name
                    FROM support_tickets t
                    LEFT JOIN clients c ON t.client_id = c.client_id
                    LEFT JOIN companies comp ON t.company_id = comp.company_id
                    WHERE t.ticket_id = ?";
            
            $params = [$ticket_id];

            // Access Control
            if ($this->role === 'client') {
                $sql .= " AND t.client_id = ?";
                $params[] = $this->user_id;
            } elseif ($this->role === 'admin' || $this->role === 'staff') {
                $sql .= " AND t.company_id = ?";
                $params[] = $this->company_id;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) return ['error' => 'Access Denied'];

            // Messages
            $msgSql = "SELECT r.*, 
                              CASE 
                                WHEN r.sender_type = 'client' THEN c.name 
                                WHEN r.sender_type = 'superadmin' THEN 'Superadmin'
                                ELSE u.name 
                              END as sender_name,
                              DATE_FORMAT(r.created_at, '%b %d, %h:%i %p') as nice_date
                       FROM ticket_replies r
                       LEFT JOIN clients c ON (r.sender_id = c.client_id AND r.sender_type = 'client')
                       LEFT JOIN users u ON (r.sender_id = u.id AND r.sender_type != 'client')
                       WHERE r.ticket_id = ? ORDER BY r.created_at ASC";

            $msgStmt = $this->db->prepare($msgSql);
            $msgStmt->execute([$ticket_id]);
            $ticket['messages'] = $msgStmt->fetchAll(PDO::FETCH_ASSOC);

            return $ticket;

        } catch (Exception $e) { return ['error' => $e->getMessage()]; }
    }

    // --- 3. SUBMIT REPLY / RESOLVE ---
    public function submitReply() {
        $ticket_id = $_POST['ticket_id'] ?? 0;
        $message   = trim($_POST['message'] ?? '');
        $action    = $_POST['action_type'] ?? 'reply';

        if (empty($message)) return ['status' => 'error', 'message' => 'Message empty'];

        try {
            $check = $this->getTicketDetails($ticket_id);
            if (isset($check['error'])) return ['status' => 'error', 'message' => 'Unauthorized'];

            $senderType = $this->role; // 'admin', 'staff'

            $stmt = $this->db->prepare("INSERT INTO ticket_replies (ticket_id, sender_id, sender_type, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$ticket_id, $this->user_id, $senderType, $message]);

            // Status Logic
            $newStatus = $check['status'];
            if ($action === 'resolve') {
                $this->db->prepare("UPDATE support_tickets SET status = 'closed', solution = ?, updated_at = NOW() WHERE ticket_id = ?")->execute([$message, $ticket_id]);
                $newStatus = 'closed';
            } else {
                $this->db->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE ticket_id = ?")->execute([$ticket_id]);
                $newStatus = 'open';
            }

            return ['status' => 'success', 'new_status' => $newStatus];

        } catch (Exception $e) { return ['status' => 'error', 'message' => $e->getMessage()]; }
    }

    // --- 4. LISTING (Updated for Tabs) ---
    public function getCompanyTickets($search='', $status='', $view_mode='clients') {
        $sql = "SELECT t.*, COALESCE(c.name, 'Platform Support') as client_name 
                FROM support_tickets t 
                LEFT JOIN clients c ON t.client_id = c.client_id 
                WHERE t.company_id = ?";
        
        $params = [$this->company_id];

        // VIEW MODE FILTER
        if ($view_mode === 'platform') {
            // Show tickets sent TO Superadmin (client_id = 0)
            $sql .= " AND t.client_id = 0";
        } else {
            // Show tickets FROM Clients (client_id > 0)
            $sql .= " AND t.client_id != 0";
        }

        if($status){ $sql .= " AND t.status = ?"; $params[] = $status; }
        if($search){ $sql .= " AND (t.subject LIKE ? OR c.name LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
        
        $sql .= " ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getClientTickets() {
        $stmt = $this->db->prepare("SELECT * FROM support_tickets WHERE client_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->user_id]); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Superadmin fetch all
    public function getGlobalTickets($search='', $status='') {
        $sql = "SELECT t.*, comp.name as company_name, COALESCE(c.name, 'Platform Request') as client_name 
                FROM support_tickets t 
                LEFT JOIN companies comp ON t.company_id=comp.company_id 
                LEFT JOIN clients c ON t.client_id=c.client_id 
                WHERE 1=1";
        // ... (Same search logic as before) ...
        $params = [];
        if($status){ $sql .= " AND t.status = ?"; $params[] = $status; }
        if($search){ $sql .= " AND (t.subject LIKE ? OR c.name LIKE ?)"; $params[]="%$search%"; $params[]="%$search%"; }
        $sql .= " ORDER BY t.created_at DESC LIMIT 50";
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>