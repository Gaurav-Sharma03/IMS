<?php
ob_start(); // Critical for JSON response
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

requireRole(['superadmin', 'admin', 'staff', 'client']);

ob_clean(); // Clean buffer
header('Content-Type: application/json');

$controller = new SupportController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax_reply'])) {
        echo json_encode($controller->submitReply());
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
exit;
?>