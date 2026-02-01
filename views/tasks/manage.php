<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/TaskController.php';

// 1. Initialize
$controller = new TaskController();
$controller->handleRequest();

// 2. Filters
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['search'] ?? '';

// 3. Fetch Data
$tasks = $controller->getMyTasks($filterStatus, $filterSearch);
$staffList = $controller->getStaffList();

// 4. Handle Modal Logic
$activeTask = null;
$activeComments = [];
if (isset($_GET['task_id'])) {
    $details = $controller->getTaskDetails($_GET['task_id']);
    if($details) {
        $activeTask = $details['task'];
        $activeComments = $details['comments'];
    }
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f1f5f9; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    /* Task Card Styles */
    .task-card { 
        background: white; border-radius: 12px; border: 1px solid #e2e8f0; 
        transition: all 0.2s ease-in-out; cursor: pointer; position: relative; overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .task-card:hover { transform: translateY(-4px); box-shadow: 0 12px 20px -5px rgba(0, 0, 0, 0.1); border-color: #cbd5e1; }
    
    .task-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 5px; }
    .prio-low::before { background-color: #10b981; } 
    .prio-medium::before { background-color: #3b82f6; } 
    .prio-high::before { background-color: #f59e0b; } 
    .prio-urgent::before { background-color: #ef4444; }

    /* Badges & Avatars */
    .badge-status { font-size: 10px; padding: 4px 8px; border-radius: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .st-pending { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .st-in_progress { background: #eff6ff; color: #2563eb; border: 1px solid #dbeafe; }
    .st-review { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .st-completed { background: #f0fdf4; color: #15803d; border: 1px solid #dcfce7; }
    .st-cancelled { background: #fef2f2; color: #991b1b; border: 1px solid #fee2e2; }

    .avatar-circle { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; color: white; }

    /* Chat Styles */
    .chat-container { height: 350px; overflow-y: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; display: flex; flex-direction: column; gap: 12px; }
    .chat-bubble { max-width: 80%; padding: 10px 14px; border-radius: 12px; font-size: 13px; line-height: 1.5; position: relative; }
    .bubble-me { align-self: flex-end; background: #4f46e5; color: white; border-bottom-right-radius: 2px; }
    .bubble-other { align-self: flex-start; background: white; border: 1px solid #e2e8f0; color: #334155; border-bottom-left-radius: 2px; }
    .chat-meta { font-size: 10px; margin-top: 4px; display: block; opacity: 0.7; }
</style>

<main class="main-content">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold m-0 text-dark">Task Manager</h4>
            <p class="text-muted small m-0">Collaborate and track project progress</p>
        </div>
        
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 130px;">
                    <option value="">All Status</option>
                    <option value="pending" <?= $filterStatus=='pending'?'selected':''?>>Pending</option>
                    <option value="in_progress" <?= $filterStatus=='in_progress'?'selected':''?>>In Progress</option>
                    <option value="completed" <?= $filterStatus=='completed'?'selected':''?>>Completed</option>
                </select>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= htmlspecialchars($filterSearch) ?>">
            </form>
            
            <?php if ($_SESSION['role'] !== 'client'): ?>
                <button class="btn btn-dark btn-sm fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                    <i class="fa-solid fa-plus me-1"></i> New Task
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <?php if (empty($tasks)): ?>
            <div class="col-12 text-center py-5">
                <div class="text-muted opacity-25 mb-3"><i class="fa-solid fa-clipboard-check fa-4x"></i></div>
                <h6 class="fw-bold text-secondary">No tasks found</h6>
                <p class="small text-muted">Try adjusting your filters or create a new task.</p>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $t): ?>
                <?php 
                    $colors = ['#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899'];
                    $name = $t['assignee_name'] ?? 'Unknown';
                    $avColor = $colors[ord(substr($name, 0, 1)) % count($colors)];
                ?>
                <div class="col-md-6 col-xl-4">
                    <div class="task-card prio-<?= $t['priority'] ?> p-3" onclick="window.location.href='?task_id=<?= $t['task_id'] ?>'">
                        
                        <div class="d-flex justify-content-between align-items-start mb-2 ps-2">
                            <span class="badge-status st-<?= $t['status'] ?>"><?= str_replace('_', ' ', $t['status']) ?></span>
                            
                            <?php if(strtotime($t['due_date']) < time() && $t['status'] != 'completed'): ?>
                                <span class="badge bg-danger rounded-pill" style="font-size:10px;">Overdue</span>
                            <?php else: ?>
                                <small class="text-muted fw-bold" style="font-size: 11px;">
                                    <i class="fa-regular fa-calendar me-1"></i> <?= date('M d', strtotime($t['due_date'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="ps-2">
                            <h6 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($t['title'] ?? '') ?></h6>
                            
                            <p class="text-secondary small text-truncate mb-3" style="font-size: 13px;">
                                <?= htmlspecialchars($t['description'] ?? 'No description') ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle shadow-sm" style="background-color: <?= $avColor ?>;">
                                        <?= strtoupper(substr($name, 0, 1)) ?>
                                    </div>
                                    <div style="line-height: 1.1;">
                                        <span class="d-block text-dark fw-bold" style="font-size: 11px;"><?= explode(' ', $name)[0] ?></span>
                                        <span class="text-muted" style="font-size: 10px;">Assignee</span>
                                    </div>
                                </div>
                                <div class="text-muted small">
                                    <i class="fa-regular fa-comment-dots"></i> <?= $t['comment_count'] ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</main>

<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Create New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="create_task">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="Task Summary">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Assign To</label>
                        <select name="assigned_to" class="form-select" required>
                            <option value="">Choose Staff...</option>
                            <?php foreach ($staffList as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Due Date</label>
                    <input type="date" name="due_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Detailed instructions..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Create Task</button>
            </div>
        </form>
    </div>
</div>

<?php if ($activeTask): ?>
<div class="modal fade show" id="viewTaskModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.6);" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content h-100 overflow-hidden">
            <div class="modal-header bg-white border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <h5 class="modal-title fw-bold text-dark m-0"><?= htmlspecialchars($activeTask['title'] ?? '') ?></h5>
                    <span class="badge-status st-<?= $activeTask['status'] ?>"><?= strtoupper(str_replace('_', ' ', $activeTask['status'])) ?></span>
                </div>
                <a href="manage.php" class="btn-close"></a>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0 h-100">
                    
                    <div class="col-lg-7 p-4 border-end bg-white" style="max-height: 80vh; overflow-y: auto;">
                        
                        <div class="d-flex justify-content-between mb-4 bg-light p-3 rounded-3 border">
                            <div class="small">
                                <span class="text-muted d-block mb-1">Assigned To</span>
                                <strong><?= htmlspecialchars($activeTask['assigned_to_name'] ?? 'Unassigned') ?></strong>
                            </div>
                            <div class="small">
                                <span class="text-muted d-block mb-1">Created By</span>
                                <strong><?= htmlspecialchars($activeTask['created_by_name'] ?? 'Unknown') ?></strong>
                            </div>
                            <div class="small">
                                <span class="text-muted d-block mb-1">Due Date</span>
                                <strong class="<?= strtotime($activeTask['due_date']) < time() ? 'text-danger' : '' ?>"><?= date('M d, Y', strtotime($activeTask['due_date'])) ?></strong>
                            </div>
                        </div>

                        <h6 class="small fw-bold text-uppercase text-muted border-bottom pb-2 mb-3">Task Description</h6>
                        <div class="text-secondary mb-4" style="line-height: 1.7; font-size: 14px;">
                            <?= nl2br(htmlspecialchars($activeTask['description'] ?? 'No description provided.')) ?>
                        </div>

                        <?php if(!empty($activeTask['solution'])): ?>
                            <div class="alert alert-success border-success small mb-4">
                                <strong class="d-block mb-1"><i class="fa-solid fa-check-circle me-1"></i> Solution / Completion Note</strong>
                                <?= nl2br(htmlspecialchars($activeTask['solution'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="task_id" value="<?= $activeTask['task_id'] ?>">
                                    
                                    <div class="row align-items-end g-2">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Update Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="pending" <?= $activeTask['status']=='pending'?'selected':'' ?>>Pending</option>
                                                <option value="in_progress" <?= $activeTask['status']=='in_progress'?'selected':'' ?>>In Progress</option>
                                                <option value="review" <?= $activeTask['status']=='review'?'selected':'' ?>>Under Review</option>
                                                <option value="completed" <?= $activeTask['status']=='completed'?'selected':'' ?>>Completed</option>
                                                <option value="cancelled" <?= $activeTask['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label small fw-bold">Work Note / Solution</label>
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="solution" class="form-control" placeholder="Optional note..." value="<?= htmlspecialchars($activeTask['solution'] ?? '') ?>">
                                                <button class="btn btn-primary fw-bold">Update</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5 d-flex flex-column bg-white h-100">
                        <div class="p-3 border-bottom bg-light">
                            <h6 class="m-0 fw-bold small text-uppercase"><i class="fa-regular fa-comments me-2"></i> Activity Log</h6>
                        </div>
                        
                        <div class="chat-container flex-grow-1 p-3" id="taskChatBox">
                            <?php if(empty($activeComments)): ?>
                                <div class="text-center text-muted small my-auto opacity-50">No activity yet.</div>
                            <?php else: foreach($activeComments as $c): ?>
                                <?php $isMe = ($c['user_id'] == $_SESSION['user_id']); ?>
                                <div class="chat-bubble <?= $isMe ? 'bubble-me' : 'bubble-other' ?>">
                                    <div class="fw-bold" style="font-size: 11px; margin-bottom: 2px;">
                                        <?= $isMe ? 'You' : htmlspecialchars($c['user_name'] ?? 'Unknown') ?> 
                                        <span style="font-weight: normal; opacity: 0.7;">(<?= ucfirst($c['role'] ?? 'user') ?>)</span>
                                    </div>
                                    <?= nl2br(htmlspecialchars($c['message'] ?? '')) ?>
                                    <span class="chat-meta text-end"><?= date('M d, H:i', strtotime($c['created_at'])) ?></span>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                        <div class="p-3 border-top bg-white">
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="action" value="add_comment">
                                <input type="hidden" name="task_id" value="<?= $activeTask['task_id'] ?>">
                                <input type="text" name="message" class="form-control" placeholder="Type a comment..." required autocomplete="off">
                                <button class="btn btn-dark"><i class="fa-solid fa-paper-plane"></i></button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.onload = function() {
        var el = document.getElementById('taskChatBox');
        if(el) el.scrollTop = el.scrollHeight;
    }
</script>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>