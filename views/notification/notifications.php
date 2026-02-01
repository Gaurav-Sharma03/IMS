<?php
// 1. Load Config & Controller
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/NotificationController.php';

$notifCtrl = new NotificationController();

// 2. Auto-Cleanup Old Notifications (Older than 2 weeks)
$notifCtrl->cleanupOldNotifications();

// 3. Handle "Mark All Read" Action
if (isset($_GET['action']) && $_GET['action'] == 'mark_read') {
    $notifCtrl->markAllRead();
}

// 4. Pagination Logic (This replaces the old getAllNotifications call)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Notifications per page
$offset = ($page - 1) * $limit;

$totalNotifs = $notifCtrl->getTotalCount();
$totalPages = ceil($totalNotifs / $limit);

// This calls the NEW method in your controller
$allNotifs = $notifCtrl->getPaginatedNotifications($limit, $offset);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    .notif-item { transition: 0.2s; border-left: 4px solid transparent; }
    .notif-item:hover { background-color: #f1f5f9; }
    .notif-unread { background-color: #ffffff; border-left-color: #3b82f6; }
    .notif-read { background-color: #f8fafc; border-left-color: #e2e8f0; opacity: 0.8; }
    
    .icon-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Notifications</h4>
            <p class="text-muted small m-0">Showing latest alerts (Auto-cleared after 14 days)</p>
        </div>
        <?php if(!empty($allNotifs)): ?>
            <a href="?action=mark_read" class="btn btn-outline-primary btn-sm fw-bold">
                <i class="fa-solid fa-check-double me-2"></i> Mark all as read
            </a>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4">
        <div class="list-group list-group-flush">
            <?php if(empty($allNotifs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-regular fa-bell-slash fa-3x mb-3 opacity-25"></i><br>
                    No notifications found.
                </div>
            <?php else: foreach($allNotifs as $n): ?>
                <?php 
                    $bgClass = $n['is_read'] ? 'notif-read' : 'notif-unread';
                    
                    // Dynamic Icons
                    $icon = match($n['type']) {
                        'success' => '<i class="fa-solid fa-check text-success"></i>',
                        'warning' => '<i class="fa-solid fa-triangle-exclamation text-warning"></i>',
                        'danger'  => '<i class="fa-solid fa-circle-xmark text-danger"></i>',
                        default   => '<i class="fa-solid fa-info text-primary"></i>'
                    };
                    $iconBg = match($n['type']) {
                        'success' => 'bg-success-subtle',
                        'warning' => 'bg-warning-subtle',
                        'danger'  => 'bg-danger-subtle',
                        default   => 'bg-primary-subtle'
                    };
                ?>
                <a href="<?= $n['link'] ?? '#' ?>" class="list-group-item list-group-item-action p-3 d-flex align-items-center gap-3 <?= $bgClass ?>">
                    <div class="icon-circle <?= $iconBg ?>">
                        <?= $icon ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($n['title']) ?></h6>
                            <small class="text-muted" style="font-size: 11px;"><?= date('M d, h:i A', strtotime($n['created_at'])) ?></small>
                        </div>
                        <p class="mb-0 small text-secondary"><?= htmlspecialchars($n['message']) ?></p>
                    </div>
                </a>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav aria-label="Notification Page Navigation">
        <ul class="pagination justify-content-center">
            
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>

        </ul>
    </nav>
    <?php endif; ?>

</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>