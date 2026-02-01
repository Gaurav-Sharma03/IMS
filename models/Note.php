<?php
require_once __DIR__ . "/../config/database.php";

class Note {
    private $db;
    private $user_id;

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $this->db = (new Database())->connect();
        $this->user_id = $_SESSION['user_id'] ?? 0;
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM personal_notes WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$this->user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($title, $content, $color) {
        $stmt = $this->db->prepare("INSERT INTO personal_notes (user_id, title, content, color) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$this->user_id, $title, $content, $color]);
    }

    public function update($id, $title, $content, $color) {
        $stmt = $this->db->prepare("UPDATE personal_notes SET title = ?, content = ?, color = ? WHERE note_id = ? AND user_id = ?");
        return $stmt->execute([$title, $content, $color, $id, $this->user_id]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM personal_notes WHERE note_id = ? AND user_id = ?");
        return $stmt->execute([$id, $this->user_id]);
    }
}
?>