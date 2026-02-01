<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/NotificationController.php";
class TaskController {
    
    private $db;
    private $company_id;
    private $user_id;
    private $role;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Security Check
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . BASE_URL . "views/auth/login.php");
            exit;
        }

        $this->db = (new Database())->connect();
        $this->company_id = $_SESSION['company_id'];
        $this->user_id = $_SESSION['user_id'];
        $this->role = $_SESSION['role'];
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';

            if ($action === 'create_task') $this->createTask();
            if ($action === 'update_status') $this->updateTaskStatus();
            if ($action === 'add_comment') $this->addComment();
        }
    }

    /* ================= GET DATA METHODS ================= */

    public function getMyTasks($statusFilter = '', $search = '') {
        $sql = "SELECT t.*, 
                       creator.name as creator_name, 
                       assignee.name as assignee_name,
                       (SELECT COUNT(*) FROM task_comments WHERE task_id = t.task_id) as comment_count
                FROM tasks t
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE t.company_id = ?";
        
        $params = [$this->company_id];

        // Role Logic: Admin sees all, Staff sees assigned + created by them
        if ($this->role === 'staff') {
            $sql .= " AND (t.assigned_to = ? OR t.created_by = ?)";
            $params[] = $this->user_id;
            $params[] = $this->user_id;
        }

        // Filters
        if (!empty($statusFilter)) {
            $sql .= " AND t.status = ?";
            $params[] = $statusFilter;
        }
        if (!empty($search)) {
            $sql .= " AND (t.title LIKE ? OR creator.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY t.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTaskDetails($task_id) {
        // Fetch Task
        $stmt = $this->db->prepare("
            SELECT t.*, u1.name as created_by_name, u2.name as assigned_to_name 
            FROM tasks t
            LEFT JOIN users u1 ON t.created_by = u1.id
            LEFT JOIN users u2 ON t.assigned_to = u2.id
            WHERE t.task_id = ? AND t.company_id = ?
        ");
        $stmt->execute([$task_id, $this->company_id]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) return null;

        // Fetch Comments
        $stmtComm = $this->db->prepare("
            SELECT c.*, u.name as user_name, u.role 
            FROM task_comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ? 
            ORDER BY c.created_at ASC
        ");
        $stmtComm->execute([$task_id]);
        $comments = $stmtComm->fetchAll(PDO::FETCH_ASSOC);

        return ['task' => $task, 'comments' => $comments];
    }

    public function getStaffList() {
        // Only Admin/Superadmin usually assign, but we allow fetching list for dropdowns
        $stmt = $this->db->prepare("SELECT id, name FROM users WHERE company_id = ? AND role != 'client'");
        $stmt->execute([$this->company_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

/* ================= ACTION METHODS ================= */

    private function createTask() {
        // 1. Prepare SQL
        $stmt = $this->db->prepare("
            INSERT INTO tasks (company_id, created_by, assigned_to, title, description, priority, status, due_date)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        
        // 2. Get Variables
        $assignedTo = $_POST['assigned_to'];
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        $prio = $_POST['priority'];
        $due = $_POST['due_date'];

        // 3. Execute Query
        if ($stmt->execute([$this->company_id, $this->user_id, $assignedTo, $title, $desc, $prio, $due])) {
            
            // 4. Get the New Task ID (Critical for the link)
            $newTaskId = $this->db->lastInsertId();

            // 5. Trigger Notification
            // Ensure the NotificationController is loaded
            require_once __DIR__ . '/NotificationController.php'; 
            
            NotificationController::create(
                $assignedTo, // Send to the Assigned Staff
                'New Task Assigned', 
                'You have been assigned a new task: ' . $title, 
                'info', 
                BASE_URL . 'views/tasks/manage.php?task_id=' . $newTaskId // Link to open the task modal
            );

            // 6. Redirect on Success
            header("Location: " . BASE_URL . "views/tasks/manage.php?success=created");
            exit;
        } else {
            // Optional: Handle error
            header("Location: " . BASE_URL . "views/tasks/manage.php?error=failed");
            exit;
        }
    }
    private function updateTaskStatus() {
        $task_id = $_POST['task_id'];
        $status = $_POST['status'];
        $solution = $_POST['solution'] ?? '';

        $stmt = $this->db->prepare("UPDATE tasks SET status = ?, solution = ? WHERE task_id = ? AND company_id = ?");
        $stmt->execute([$status, $solution, $task_id, $this->company_id]);

        header("Location: " . BASE_URL . "views/tasks/manage.php?task_id=" . $task_id); // Re-open modal
        exit;
    }

    private function addComment() {
        $task_id = $_POST['task_id'];
        $msg = trim($_POST['message']);

        if (!empty($msg)) {
            $stmt = $this->db->prepare("INSERT INTO task_comments (task_id, user_id, message) VALUES (?, ?, ?)");
            $stmt->execute([$task_id, $this->user_id, $msg]);
        }

        header("Location: " . BASE_URL . "views/tasks/manage.php?task_id=" . $task_id); // Re-open modal
        exit;
    }




    // Add to TaskController
public function getStaffDashboardData() {
    $user_id = $_SESSION['user_id'];
    
    // 1. Stats
    $stats = [
        'pending' => $this->db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $user_id AND status != 'completed'")->fetchColumn(),
        'completed' => $this->db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $user_id AND status = 'completed'")->fetchColumn(),
        'hours' => 32 // Placeholder, replace with actual logic if you have a time_logs table
    ];

    // 2. My Tasks (High Priority First)
    $tasks = $this->db->query("
        SELECT * FROM tasks 
        WHERE assigned_to = $user_id AND status != 'completed' 
        ORDER BY FIELD(priority, 'high', 'medium', 'low'), due_date ASC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 3. Recent Activity (Simulated)
    $activity = [
        ['msg' => 'You completed "Fix Login Bug"', 'time' => '2 hours ago'],
        ['msg' => 'Admin assigned you "New Project"', 'time' => '5 hours ago'],
        ['msg' => 'You commented on "UI Update"', 'time' => 'Yesterday']
    ];

    return compact('stats', 'tasks', 'activity');
}
}
?>