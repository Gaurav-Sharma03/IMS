<?php
require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/Client.php";
require_once __DIR__ . "/../models/Invoice.php";
require_once __DIR__ . "/../controllers/NotificationController.php";

class ClientController {
    
    private $db;
    private $clientModel;
    private $invoiceModel;
    
    // Context Variables
    private $company_id;
    private $user_role;
    private $client_id_for_portal; // For logged-in clients
     private $user_id;


   
    

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $this->user_id = $_SESSION['user_id'] ?? 0;
        $this->user_role = $_SESSION['role'] ?? 'guest';
        $this->company_id = $_SESSION['company_id'] ?? 0;
        $this->client_id_for_portal = $this->user_id;

        // 1. Global Security Check
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['company_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        // 2. Init DB & Models
        $this->db = (new Database())->connect();
        $this->clientModel = new Client($this->db);
        $this->invoiceModel = new Invoice($this->db);

        // 3. Set Context
        $this->company_id = $_SESSION['company_id'];
        $this->user_role = $_SESSION['role'] ?? 'client';
        
        // If logged in as client, set their specific ID
        if ($this->user_role === 'client') {
            $this->client_id_for_portal = $_SESSION['client_id'] ?? $_SESSION['user_id'];
        }
    }

    /* ==========================================================
       SECTION A: ADMIN / STAFF ACTIONS (Manage Clients)
       (Create, Update, Delete, List)
    ========================================================== */

    public function handleRequest() {
        // Security: Only Admins/Staff can modify client records via this method
        if (!in_array($this->user_role, ['admin', 'superadmin', 'staff'])) {
            // Clients should NOT be able to access these actions
            return; 
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'add') {
                if (!empty($_POST['client_id'])) {
                    $this->update();
                } else {
                    $this->store();
                }
            } elseif ($action === 'delete') {
                $this->delete();
            }
        }
    }

    // 1. List Clients (For Admin View)
    public function index($search = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        return [
            'clients' => $this->clientModel->getClients($limit, $offset, $search, '', '', $this->company_id),
            'total'   => $this->clientModel->countClients($search, '', '', $this->company_id)
        ];
    }

    // 2. Store New Client
    public function store() {
        $client_id = 'CLT_' . strtoupper(bin2hex(random_bytes(4)));
        $defaultPassword = "Client@" . date("Y"); 

        $data = [
            'client_id'      => $client_id,
            'company_id'     => $this->company_id,
            'name'           => trim($_POST['name']),
            'contact_person' => trim($_POST['contact_person']),
            'email'          => trim($_POST['email']),
            'phone'          => trim($_POST['phone']),
            'gst_vat'        => trim($_POST['gst_vat']),
            'street'         => trim($_POST['street']),
            'city'           => trim($_POST['city']),
            'state'          => trim($_POST['state']),
            'country'        => trim($_POST['country']),
            'postal_code'    => trim($_POST['postal_code']),
            'address'        => trim($_POST['address']),
            'currency_id'    => trim($_POST['currency_id']),
            'password'       => $defaultPassword
        ];

        $result = $this->clientModel->addClient($data);

        if ($result['status']) {
            $_SESSION['flash_success'] = "Client added! Temp Password: <strong>$defaultPassword</strong>";
            header("Location: " . BASE_URL . "views/clients/manage.php");
        } else {
            header("Location: " . BASE_URL . "views/clients/add.php?error=" . urlencode($result['message']));
        }
        exit;
    }

    // 3. Update Client
    public function update() {
        $data = [
            'client_id'      => $_POST['client_id'],
            'company_id'     => $this->company_id,
            'name'           => trim($_POST['name']),
            'contact_person' => trim($_POST['contact_person']),
            'email'          => trim($_POST['email']),
            'phone'          => trim($_POST['phone']),
            'gst_vat'        => trim($_POST['gst_vat']),
            'street'         => trim($_POST['street']),
            'city'           => trim($_POST['city']),
            'state'          => trim($_POST['state']),
            'country'        => trim($_POST['country']),
            'postal_code'    => trim($_POST['postal_code']),
            'address'        => trim($_POST['address']),
            'currency_id'    => trim($_POST['currency_id'])
        ];

        if ($this->clientModel->updateClient($data)) {
            header("Location: " . BASE_URL . "views/clients/manage.php?success=updated");
        } else {
            header("Location: " . BASE_URL . "views/clients/manage.php?error=db");
        }
        exit;
    }

    // 4. Delete Client
    public function delete() {
        if ($this->clientModel->deleteClient($_POST['client_id'])) {
            header("Location: " . BASE_URL . "views/clients/manage.php?success=deleted");
        } else {
            header("Location: " . BASE_URL . "views/clients/manage.php?error=db");
        }
        exit;
    }





    
    /* ==========================================================
       SECTION B: CLIENT PORTAL ACTIONS (View My Data)
       (Dashboard, My Invoices, Support)
    ========================================================== */

    // 1. Client Dashboard Stats
    public function getDashboardData() {
        if ($this->user_role !== 'client') return []; 

        $myId = $this->client_id_for_portal;
        $stats = ['outstanding' => 0, 'paid' => 0];
        
        $invoices = $this->invoiceModel->getInvoicesByClientId($myId);
        
        foreach($invoices as $inv) {
            if($inv['status'] === 'paid') {
                $stats['paid'] += $inv['grand_total'];
            } else {
                $stats['outstanding'] += $inv['outstanding_amount'];
            }
        }

        // Fetch Notifications (Updated for new table schema)
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]); // Use USER_ID for notifications
        
        return [
            'stats' => $stats,
            'invoices' => array_slice($invoices, 0, 5),
            'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    }

    // 2. My Invoices List
    public function getMyInvoices($search = '', $status = '') {
        if ($this->user_role !== 'client') return [];

        $myId = $this->client_id_for_portal;
        
        $sql = "SELECT i.*, curr.symbol FROM invoices i 
                LEFT JOIN currencies curr ON i.currency_id = curr.currency_id 
                WHERE i.client_id = ?";
        
        $params = [$myId];

        if ($search) { 
            $sql .= " AND i.invoice_number LIKE ?"; 
            $params[] = "%$search%"; 
        }
        if ($status) { 
            $sql .= " AND i.status = ?"; 
            $params[] = $status; 
        }
        
        $sql .= " ORDER BY i.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================= CLIENT SUPPORT SYSTEM ================= */
// --- Create Ticket ---
    public function createTicket() {
        if ($this->user_role !== 'client') return; 
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
            $subject = trim($_POST['subject'] ?? ''); $message = trim($_POST['message'] ?? '');
            if ($subject && $message) {
                $stmt = $this->db->prepare("INSERT INTO support_tickets (client_id, company_id, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
                if ($stmt->execute([$this->client_id_for_portal, $this->company_id, $subject, $message])) return true;
            }
        }
        return false;
    }

    // --- List Tickets ---
    public function getMyTickets() {
        if ($this->user_role !== 'client') return [];
        $stmt = $this->db->prepare("SELECT * FROM support_tickets WHERE client_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->client_id_for_portal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- Get Details (Updated for Solution) ---
    public function getTicketDetails($ticketId) {
        if ($this->user_role !== 'client') return ['error' => 'Unauthorized'];

        // Added 'solution' to the select list
        $stmt = $this->db->prepare("SELECT ticket_id, subject, message, status, created_at, solution FROM support_tickets WHERE ticket_id = ? AND client_id = ?");
        $stmt->execute([$ticketId, $this->client_id_for_portal]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) return ['error' => 'Ticket not found'];

        // Fetch Messages
        $stmtMsg = $this->db->prepare("
            SELECT r.*, 
                   CASE WHEN r.sender_type = 'client' THEN 'You' ELSE u.name END as sender_name,
                   u.name as admin_name 
            FROM ticket_replies r
            LEFT JOIN users u ON (r.sender_id = u.id AND r.sender_type != 'client')
            WHERE r.ticket_id = ? 
            ORDER BY r.created_at ASC
        ");
        $stmtMsg->execute([$ticketId]);
        $ticket['messages'] = $stmtMsg->fetchAll(PDO::FETCH_ASSOC);

        return $ticket;
    }

    // --- Submit Reply ---
    public function submitReply() {
        if ($this->user_role !== 'client') return;
        $ticketId = $_POST['ticket_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');

        if (!empty($message) && $ticketId > 0) {
            $check = $this->db->prepare("SELECT ticket_id, status FROM support_tickets WHERE ticket_id = ? AND client_id = ?");
            $check->execute([$ticketId, $this->client_id_for_portal]);
            $ticketData = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($ticketData) {
                $stmt = $this->db->prepare("INSERT INTO ticket_replies (ticket_id, sender_id, sender_type, message, created_at) VALUES (?, ?, 'client', ?, NOW())");
                $stmt->execute([$ticketId, $this->client_id_for_portal, $message]);

                if ($ticketData['status'] === 'closed') {
                    $this->db->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE ticket_id = ?")->execute([$ticketId]);
                } else {
                    $this->db->prepare("UPDATE support_tickets SET updated_at = NOW() WHERE ticket_id = ?")->execute([$ticketId]);
                }
                return true;
            }
        }
        return false;
    }
}
?>