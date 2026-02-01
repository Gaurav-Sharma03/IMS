<?php
require_once __DIR__ . "/../config/database.php";

class Task {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->connect();
    }

    /* ================= CREATE TASK ================= */
    public function createTask($data) {
        $sql = "INSERT INTO tasks (company_id, title, description, assigned_to, assigned_by, priority, due_date, status)
                VALUES (:cid, :title, :desc, :to, :by, :prio, :due, 'pending')";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            ':cid' => $data['company_id'],
            ':title' => $data['title'],
            ':desc' => $data['description'],
            ':to' => $data['assigned_to'],
            ':by' => $data['assigned_by'],
            ':prio' => $data['priority'],
            ':due' => $data['due_date']
        ]);
    }

    /* ================= GET TASKS (For List/Board) ================= */
    public function getTasks($company_id, $user_id = null, $role = 'admin') {
        $sql = "SELECT t.*, 
                assignee.name as assignee_name, 
                assigner.name as assigner_name 
                FROM tasks t
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                LEFT JOIN users assigner ON t.assigned_by = assigner.id
                WHERE t.company_id = :cid";

        // If staff, only show tasks assigned TO them
        if ($role === 'staff') {
            $sql .= " AND t.assigned_to = :uid";
        }

        $sql .= " ORDER BY t.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':cid', $company_id);
        if ($role === 'staff') {
            $stmt->bindValue(':uid', $user_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ================= GET SINGLE TASK DETAILS ================= */
    public function getTaskById($task_id) {
        $stmt = $this->conn->prepare("
            SELECT t.*, assignee.name as assignee_name, assigner.name as assigner_name 
            FROM tasks t
            LEFT JOIN users assignee ON t.assigned_to = assignee.id
            LEFT JOIN users assigner ON t.assigned_by = assigner.id
            WHERE t.task_id = :id
        ");
        $stmt->execute([':id' => $task_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ================= UPDATE STATUS/SOLUTION ================= */
    public function updateTaskStatus($task_id, $status, $solution = null) {
        $sql = "UPDATE tasks SET status = :status";
        $params = [':status' => $status, ':id' => $task_id];

        if ($solution !== null) {
            $sql .= ", solution = :solution";
            $params[':solution'] = $solution;
        }

        $sql .= " WHERE task_id = :id";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    /* ================= COMMENTS SYSTEM ================= */
    public function addComment($task_id, $user_id, $message) {
        $stmt = $this->conn->prepare("INSERT INTO task_comments (task_id, user_id, message) VALUES (?, ?, ?)");
        return $stmt->execute([$task_id, $user_id, $message]);
    }

    public function getComments($task_id) {
        $stmt = $this->conn->prepare("
            SELECT c.*, u.name, u.role 
            FROM task_comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ?
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([$task_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>