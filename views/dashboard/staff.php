<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

// Protect Page
requireRole(['staff']);

$db = (new Database())->connect();
$user_id = $_SESSION['user_id'];

// 1. Fetch Real Stats
$pending = $db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $user_id AND status != 'completed'")->fetchColumn();
$completed = $db->query("SELECT COUNT(*) FROM tasks WHERE assigned_to = $user_id AND status = 'completed'")->fetchColumn();
// Calculate 'Hours' (Demo logic: 2 hours per task)
$hours = $completed * 2; 

// 2. Fetch Real Tasks
$tasks = $db->query("SELECT * FROM tasks WHERE assigned_to = $user_id AND status != 'completed' ORDER BY due_date ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch Real Activity (Last 5 completed tasks)
$activity = $db->query("SELECT title, updated_at FROM tasks WHERE assigned_to = $user_id AND status = 'completed' ORDER BY updated_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #4f46e5;
        --primary-dark: #4338ca;
        --secondary: #64748b;
        --bg-body: #f8fafc;
        --surface: #ffffff;
        --border: #e2e8f0;
        --radius-lg: 16px;
        --radius-md: 12px;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-card: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }

    body {
        font-family: 'Inter', sans-serif;
        background-color: var(--bg-body);
        color: #0f172a;
    }

    .main-content {
        margin-left: 250px;
        padding: 40px;
        min-height: 100vh;
        transition: all 0.3s ease;
    }

    @media (max-width: 992px) { .main-content { margin-left: 0; padding: 20px; } }

    /* --- Welcome Hero --- */
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 35px;
    }
    .welcome-text h2 { font-weight: 800; color: #1e293b; letter-spacing: -0.5px; margin-bottom: 5px; }
    .welcome-text p { color: var(--secondary); font-size: 0.95rem; }
    
    .date-badge {
        background: white;
        padding: 8px 16px;
        border-radius: 50px;
        border: 1px solid var(--border);
        font-weight: 600;
        color: var(--secondary);
        font-size: 0.85rem;
        box-shadow: var(--shadow-sm);
        display: flex; align-items: center; gap: 8px;
    }

    /* --- Stat Cards --- */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-bottom: 35px;
    }
    @media (max-width: 768px) { .stat-grid { grid-template-columns: 1fr; } }

    .stat-card {
        background: var(--surface);
        border-radius: var(--radius-lg);
        padding: 24px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-card); border-color: #cbd5e1; }

    .stat-info h3 { font-size: 2rem; font-weight: 800; margin: 0; line-height: 1; color: #0f172a; }
    .stat-info span { font-size: 0.85rem; font-weight: 600; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.5px; }
    
    .stat-icon {
        width: 56px; height: 56px;
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
    }
    .icon-todo { background: #fff7ed; color: #ea580c; }
    .icon-done { background: #f0fdf4; color: #16a34a; }
    .icon-hours { background: #eff6ff; color: #2563eb; }

    /* --- Main Layout Grid --- */
    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
    }
    @media (max-width: 992px) { .content-grid { grid-template-columns: 1fr; } }

    /* --- Task List Section --- */
    .card-modern {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
    }
    .card-header-modern {
        padding: 20px 25px;
        border-bottom: 1px solid var(--border);
        display: flex; justify-content: space-between; align-items: center;
        background: #fff;
    }
    .card-title { font-weight: 700; color: #0f172a; margin: 0; font-size: 1.1rem; }

    .task-list { list-style: none; padding: 0; margin: 0; }
    .task-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 25px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
    }
    .task-item:last-child { border-bottom: none; }
    .task-item:hover { background: #f8fafc; }

    .task-check {
        width: 22px; height: 22px;
        border: 2px solid #cbd5e1;
        border-radius: 6px;
        margin-right: 15px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        color: transparent; transition: all 0.2s;
    }
    .task-item:hover .task-check { border-color: var(--primary); }
    
    .task-details { flex-grow: 1; }
    .task-title { font-weight: 600; color: #1e293b; font-size: 0.95rem; display: block; margin-bottom: 4px; }
    .task-meta { font-size: 0.75rem; color: var(--secondary); display: flex; align-items: center; gap: 10px; }

    /* Badges */
    .priority-badge {
        padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
    }
    .p-high { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
    .p-medium { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .p-low { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

    /* --- Activity Timeline --- */
    .timeline-container { padding: 25px; }
    .timeline-item {
        position: relative;
        padding-left: 30px;
        margin-bottom: 25px;
    }
    .timeline-item:last-child { margin-bottom: 0; }
    
    .timeline-line {
        position: absolute; left: 0; top: 5px; bottom: -25px; width: 2px; background: #e2e8f0;
        margin-left: 7px;
    }
    .timeline-item:last-child .timeline-line { display: none; }
    
    .timeline-dot {
        position: absolute; left: 0; top: 6px;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: white;
        border: 3px solid var(--primary);
        z-index: 1;
    }
    
    .t-time { font-size: 0.75rem; font-weight: 600; color: var(--secondary); display: block; margin-bottom: 4px; }
    .t-content { font-size: 0.9rem; color: #334155; line-height: 1.4; }
    .t-content strong { color: #0f172a; }

    /* --- Quick Links --- */
    .quick-links { display: grid; grid-template-columns: 1fr; gap: 10px; }
    .link-btn {
        display: flex; align-items: center; gap: 12px;
        padding: 15px;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        color: #1e293b;
        font-weight: 600;
        font-size: 0.95rem;
        transition: all 0.2s;
        text-decoration: none;
    }
    .link-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: #fdfeff;
        transform: translateX(4px);
        box-shadow: var(--shadow-sm);
    }
    .link-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; color: #64748b; }
    .link-btn:hover .link-icon { background: var(--primary); color: white; }

</style>

<main class="main-content">
    
    <div class="dashboard-header">
        <div class="welcome-text">
            <h2>Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Staff') ?></h2>
            <p>Here's what's happening with your tasks today.</p>
        </div>
        <div class="d-none d-md-flex date-badge">
            <i class="fa-regular fa-calendar text-primary"></i>
            <?= date('l, M d, Y') ?>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-info">
                <span>Pending Tasks</span>
                <h3><?= $pending ?></h3>
            </div>
            <div class="stat-icon icon-todo"><i class="fa-solid fa-list-check"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span>Completed</span>
                <h3><?= $completed ?></h3>
            </div>
            <div class="stat-icon icon-done"><i class="fa-solid fa-clipboard-check"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <span>Est. Hours</span>
                <h3><?= $hours ?>h</h3>
            </div>
            <div class="stat-icon icon-hours"><i class="fa-solid fa-stopwatch"></i></div>
        </div>
    </div>

    <div class="content-grid">
        
        <div class="left-section">
            <div class="card-modern">
                <div class="card-header-modern">
                    <h5 class="card-title">Priority Tasks</h5>
                    <a href="<?= BASE_URL ?>views/tasks/manage.php" class="btn btn-sm btn-outline-dark fw-bold rounded-pill px-3">View All</a>
                </div>
                
                <div class="task-list">
                    <?php if(empty($tasks)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3 opacity-25"><i class="fa-solid fa-mug-hot fa-3x text-secondary"></i></div>
                            <h6 class="fw-bold text-dark">All caught up!</h6>
                            <p class="text-muted small">No pending tasks assigned to you.</p>
                        </div>
                    <?php else: foreach($tasks as $t): ?>
                        <div class="task-item">
                            <div class="d-flex align-items-center w-100">
                                <div class="task-check"><i class="fa-solid fa-check fa-xs"></i></div>
                                <div class="task-details">
                                    <span class="task-title"><?= htmlspecialchars($t['title']) ?></span>
                                    <div class="task-meta">
                                        <span><i class="fa-regular fa-clock me-1"></i> <?= date('M d', strtotime($t['due_date'])) ?></span>
                                        <?php if(strtotime($t['due_date']) < time()): ?>
                                            <span class="text-danger fw-bold">• Overdue</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php 
                                    $pClass = match(strtolower($t['priority'])) { 'high'=>'p-high', 'medium'=>'p-medium', default=>'p-low' }; 
                                ?>
                                <span class="priority-badge <?= $pClass ?>"><?= $t['priority'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <div class="right-section">
            
            <div class="card-modern mb-4">
                <div class="card-header-modern pb-2 border-0">
                    <h5 class="card-title" style="font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--secondary);">Quick Actions</h5>
                </div>
                <div class="p-3 pt-0 quick-links">
                    <a href="<?= BASE_URL ?>views/notes/manage.php" class="link-btn">
                        <div class="link-icon"><i class="fa-solid fa-note-sticky"></i></div>
                        <span>Personal Notes</span>
                    </a>
                    <a href="<?= BASE_URL ?>views/support/manage.php" class="link-btn">
                        <div class="link-icon"><i class="fa-solid fa-headset"></i></div>
                        <span>Support Center</span>
                    </a>
                </div>
            </div>

            <div class="card-modern">
                <div class="card-header-modern">
                    <h5 class="card-title">Recent Activity</h5>
                </div>
                <div class="timeline-container">
                    <?php if(empty($activity)): ?>
                        <p class="text-muted small m-0">No recent activity recorded.</p>
                    <?php else: foreach($activity as $act): ?>
                        <div class="timeline-item">
                            <div class="timeline-line"></div>
                            <div class="timeline-dot"></div>
                            <span class="t-time"><?= date('M d, H:i', strtotime($act['updated_at'])) ?></span>
                            <div class="t-content">
                                You completed <strong><?= htmlspecialchars($act['title']) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>

        </div>
    </div>

</main>

<?php include __DIR__ . '/../layouts/footer.php'; ?>