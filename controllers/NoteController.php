<?php
require_once __DIR__ . "/../models/Note.php";

class NoteController {
    private $model;

    public function __construct() {
        $this->model = new Note();
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['add_note'])) {
                $this->model->create($_POST['title'], $_POST['content'], $_POST['color']);
                header("Location: " . $_SERVER['REQUEST_URI']); exit;
            } elseif (isset($_POST['edit_note'])) {
                $this->model->update($_POST['note_id'], $_POST['title'], $_POST['content'], $_POST['color']);
                header("Location: " . $_SERVER['REQUEST_URI']); exit;
            } elseif (isset($_POST['delete_note'])) {
                $this->model->delete($_POST['note_id']);
                header("Location: " . $_SERVER['REQUEST_URI']); exit;
            }
        }
    }

    public function getNotes() {
        return $this->model->getAll();
    }
}
?>