<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ClientController.php';

/** @var ClientController $clientCtrl */
$clientCtrl = new ClientController();

// --- 1. AJAX API (JSON) ---
if (isset($_GET['action']) && $_GET['action'] === 'get_details') {
    ob_clean();
    header('Content-Type: application/json');
    $ticketId = (int) ($_GET['id'] ?? 0);
    if ($ticketId > 0)
        echo json_encode($clientCtrl->getTicketDetails($ticketId));
    else
        echo json_encode(['error' => 'Invalid ID']);
    exit;
}

// --- 2. FORM HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ajax_reply'])) {
        ob_clean();
        header('Content-Type: application/json');
        $clientCtrl->submitReply();
        echo json_encode(['status' => 'success']);
        exit;
    }
    if (isset($_POST['create_ticket'])) {
        $clientCtrl->createTicket();
        header("Location: support.php?success=created");
        exit;
    }
}

// --- 3. FETCH DATA ---
$tickets = $clientCtrl->getMyTickets();
$total = count($tickets);
$open = 0;
$closed = 0;
foreach ($tickets as $t) {
    ($t['status'] === 'closed') ? $closed++ : $open++;
}

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
    rel="stylesheet">

<style>
    :root {
        --primary: #4f46e5;
        --primary-soft: #e0e7ff;
        --dark: #0f172a;
        --slate: #64748b;
        --light: #f8fafc;
        --white: #ffffff;
        --border: #e2e8f0;
        --radius-xl: 20px;
        --radius-lg: 12px;
        --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05);
        --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: #f1f5f9;
        color: var(--dark);
    }

    /* --- Layout --- */
    .main-content {
        margin-left: 250px;
        padding: 40px;
        min-height: 100vh;
        transition: all 0.3s ease;
    }

    @media (max-width: 992px) {
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
    }

    /* --- Hero Section --- */
    .support-hero {
        background: white;
        border-radius: var(--radius-xl);
        padding: 40px;
        margin-bottom: 30px;
        background-image: radial-gradient(circle at 100% 0%, #eef2ff 0%, transparent 25%);
        border: 1px solid var(--border);
    }

    /* --- Stats Cards (Modern) --- */
    .stat-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: 24px;
        border: 1px solid var(--border);
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
        overflow: hidden;
        height: 100%;
        box-shadow: var(--shadow-sm);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: #cbd5e1;
    }

    .stat-icon-bg {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 15px;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -1px;
        line-height: 1;
        margin-bottom: 5px;
    }

    .stat-label {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--slate);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* --- Inbox Ticket List --- */
    .ticket-container {
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .ticket-header-row {
        background: #f8fafc;
        padding: 15px 25px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ticket-item {
        display: flex;
        align-items: center;
        padding: 20px 25px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s;
        cursor: pointer;
        gap: 20px;
    }

    .ticket-item:last-child {
        border-bottom: none;
    }

    .ticket-item:hover {
        background: #f8fafc;
    }

    .t-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: #f1f5f9;
        color: var(--slate);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .ticket-item:hover .t-icon {
        background: var(--primary);
        color: white;
        transition: 0.2s;
    }

    .t-content {
        flex: 1;
        min-width: 0;
    }

    .t-subject {
        font-weight: 700;
        color: var(--dark);
        font-size: 1rem;
        margin-bottom: 4px;
        display: block;
    }

    .t-preview {
        color: var(--slate);
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .t-meta {
        text-align: right;
        min-width: 120px;
    }

    .t-date {
        font-size: 0.8rem;
        color: var(--slate);
        font-weight: 500;
        display: block;
        margin-bottom: 6px;
    }

    /* Status Badges */
    .badge-status {
        padding: 5px 12px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .st-open {
        background: #eff6ff;
        color: #3b82f6;
        border: 1px solid #dbeafe;
    }

    .st-closed {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }

    .dot-st {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    /* --- Chat Interface --- */
    .chat-wrapper {
        display: flex;
        flex-direction: column;
        height: 80vh;
        max-height: 800px;
        background: white;
    }

    .chat-head {
        padding: 20px 30px;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        z-index: 10;
    }

    .chat-body {
        flex: 1;
        overflow-y: auto;
        padding: 30px;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 20px;
        scroll-behavior: smooth;
    }

    /* Messages */
    .msg-row {
        display: flex;
        gap: 15px;
        max-width: 80%;
    }

    .msg-row.me {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .msg-avatar {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        flex-shrink: 0;
    }

    .av-sys {
        background: white;
        border: 1px solid var(--border);
        color: var(--dark);
    }

    .av-user {
        background: var(--primary);
        color: white;
        box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
    }

    .msg-bubble {
        padding: 16px 22px;
        border-radius: 18px;
        font-size: 0.95rem;
        line-height: 1.6;
        position: relative;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
    }

    .msg-row.sys .msg-bubble {
        background: white;
        border: 1px solid var(--border);
        color: var(--dark);
        border-top-left-radius: 2px;
    }

    .msg-row.me .msg-bubble {
        background: var(--primary);
        color: white;
        border-top-right-radius: 2px;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2);
    }

    .msg-time {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 6px;
        font-weight: 500;
    }

    .msg-row.me .msg-time {
        text-align: right;
    }

    /* Official Solution */
   /* Official Solution Card - Professional Dimensions */
.solution-card {
    /* Dimensions & Layout */
    width: 100%;
    max-width: 700px; /* Optimal reading width */
    margin: 0 auto 35px auto; /* Center horizontally + spacing from messages */
    height: auto;
    
    /* Visual Style */
    background: #f0fdf4; /* Very light emerald */
    border: 1px solid #86efac; /* Soft green border */
    border-radius: 12px; /* Slightly tighter radius than chat bubbles */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    position: relative;
    animation: fadeSlideDown 0.4s ease-out;
}

/* Header Styling */
.sol-header {
    background: #dcfce7;
    padding: 12px 20px;
    color: #166534;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid #bbf7d0;
}

/* Body Content */
.sol-body {
    padding: 20px 24px;
    color: #1e293b;
    font-size: 0.95rem;
    line-height: 1.6;
    white-space: pre-wrap; /* Preserves formatting */
    
    /* Safety scroll for very long solutions */
    max-height: 300px; 
    overflow-y: auto;
}

/* Custom Scrollbar for the solution body */
.sol-body::-webkit-scrollbar { width: 6px; }
.sol-body::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }

/* Animation */
@keyframes fadeSlideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

    /* Input Area */
    .chat-foot {
        padding: 20px 30px;
        border-top: 1px solid var(--border);
        background: white;
    }

    .chat-foot.disabled {
        opacity: 0.6;
        pointer-events: none;
        background: #f8fafc;
    }

    .input-box {
        border-radius: 15px;
        border: 1px solid #cbd5e1;
        padding: 15px;
        background: #f8fafc;
        transition: 0.2s;
    }

    .input-box:focus {
        background: white;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .ticket-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }

        .t-icon {
            display: none;
        }

        .t-meta {
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            justify-content: space-between;
            margin-top: 5px;
        }

        .t-date {
            margin-bottom: 0;
        }

        .msg-row {
            max-width: 90%;
        }

        .chat-body {
            padding: 15px;
        }
    }
</style>

<main class="main-content">

    <div class="support-hero d-flex flex-wrap justify-content-between align-items-center gap-4">
        <div>
            <h1 class="fw-bold mb-2 text-dark">Help Center</h1>
            <p class="text-muted mb-0" style="font-size: 1.05rem;">Manage your support requests and get expert
                assistance.</p>
        </div>
        <button class="btn btn-dark btn-lg px-5 py-3 rounded-pill fw-bold shadow-lg" data-bs-toggle="modal"
            data-bs-target="#createModal" style="transition: transform 0.2s;">
            <i class="fa-solid fa-plus me-2"></i> Open New Ticket
        </button>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div class="stat-icon-bg bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Tickets</div>
                <i class="fa-solid fa-ticket position-absolute opacity-10"
                    style="font-size: 8rem; right: -20px; bottom: -20px; color: #cbd5e1;"></i>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card">
                <div class="stat-icon-bg bg-warning bg-opacity-10 text-warning">
                    <i class="fa-solid fa-hourglass-start"></i>
                </div>
                <div class="stat-value"><?= $open ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="stat-card">
                <div class="stat-icon-bg bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <div class="stat-value"><?= $closed ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
    </div>

    <div class="ticket-container">
        <div class="ticket-header-row">
            <h5 class="fw-bold m-0 text-dark"><i class="fa-regular fa-folder-open me-2 text-muted"></i> Recent Requests
            </h5>
            <div class="position-relative" style="width: 250px;">
                <input type="text" id="ticketSearch" class="form-control ps-5 border-0 bg-white shadow-sm rounded-pill"
                    placeholder="Search tickets...">
                <i
                    class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
            </div>
        </div>

        <div id="ticketListBody">
            <?php if (empty($tickets)): ?>
                <div class="text-center py-5">
                    <div class="mb-3 text-muted opacity-25"><i class="fa-solid fa-inbox fa-4x"></i></div>
                    <h5 class="fw-bold text-muted">No support tickets found</h5>
                    <p class="small text-muted">Need help? Create a new ticket above.</p>
                </div>
            <?php else:
                foreach ($tickets as $t): ?>
                    <div class="ticket-item" onclick="openTicket(<?= $t['ticket_id'] ?>)">
                        <div class="t-icon">
                            <i
                                class="<?= ($t['status'] === 'open') ? 'fa-solid fa-envelope-open-text' : 'fa-solid fa-check' ?>"></i>
                        </div>
                        <div class="t-content">
                            <span class="t-subject">
                                <?= htmlspecialchars($t['subject']) ?>
                                <span class="text-muted fw-normal ms-2 small">#<?= $t['ticket_id'] ?></span>
                            </span>
                            <div class="t-preview"><?= htmlspecialchars($t['message']) ?></div>
                        </div>
                        <div class="t-meta">
                            <span class="t-date"><?= date('M d, Y', strtotime($t['created_at'])) ?></span>
                            <?php $isOpen = ($t['status'] === 'open'); ?>
                            <span class="badge-status <?= $isOpen ? 'st-open' : 'st-closed' ?>">
                                <span class="dot-st"></span> <?= $t['status'] ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
        </div>
    </div>

</main>

<div class="modal fade" id="viewTicketModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-2xl rounded-4 overflow-hidden">
            <div class="chat-wrapper w-100">
                <div class="chat-head">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-uppercase fw-bold text-muted"
                                style="font-size: 0.7rem; letter-spacing: 1px;">Ticket ID #<span id="vId"></span></span>
                            <span class="badge-status st-open" id="vStatusBadge"><span class="dot-st"></span> <span
                                    id="vStatusText">Open</span></span>
                        </div>
                        <h5 class="fw-bold m-0 text-dark" id="vSubject">Loading...</h5>
                    </div>
                    <button type="button" class="btn-close bg-light p-2 rounded-circle"
                        data-bs-dismiss="modal"></button>
                </div>

                <div class="chat-body" id="chatStream">
                </div>

                <div class="chat-foot" id="replyArea">
                    <form id="replyForm">
                        <input type="hidden" name="ajax_reply" value="1">
                        <input type="hidden" name="ticket_id" id="replyTicketId">

                        <div class="position-relative">
                            <textarea name="message" id="replyInput" class="form-control input-box" rows="1"
                                placeholder="Write a reply..."
                                style="padding-right: 60px; min-height: 55px; resize: none;" required></textarea>
                            <button type="submit" id="replyBtn"
                                class="btn btn-primary position-absolute bottom-0 end-0 m-2 rounded-circle shadow-sm"
                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <h4 class="modal-title fw-bold text-dark">Submit a Request</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-3">
                <p class="text-muted mb-4">Please describe your issue in detail.</p>
                <form method="POST">
                    <input type="hidden" name="create_ticket" value="1">
                    <div class="form-floating mb-3">
                        <select name="subject" class="form-select bg-light border-0 fw-medium" id="floatingSelect"
                            style="border-radius: 12px;" required>
                            <option value="">Select a topic...</option>
                            <option value="Technical Support">Technical Support</option>
                            <option value="Billing">Billing & Subscription</option>
                            <option value="Bug Report">Bug Report</option>
                            <option value="Other">Other Inquiry</option>
                        </select>
                        <label for="floatingSelect">Topic</label>
                    </div>
                    <div class="form-floating mb-4">
                        <textarea name="message" class="form-control bg-light border-0" id="floatingTextarea"
                            placeholder="Description" style="height: 150px; border-radius: 12px; resize: none;"
                            required></textarea>
                        <label for="floatingTextarea">Description</label>
                    </div>
                    <div class="d-grid">
                        <button class="btn btn-dark py-3 fw-bold rounded-3">Submit Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // --- JS LOGIC ---

    // Auto-Resize Textarea
    const tx = document.getElementById('replyInput');
    tx.addEventListener("input", function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        if (this.value === '') this.style.height = '55px';
    });

    // Filter Logic
    document.getElementById('ticketSearch').addEventListener('keyup', function () {
        let val = this.value.toLowerCase();
        let items = document.querySelectorAll('.ticket-item');
        items.forEach(item => {
            let text = item.innerText.toLowerCase();
            item.style.display = text.includes(val) ? 'flex' : 'none';
        });
    });

    // Open Ticket Modal
    function openTicket(id) {
        const modal = new bootstrap.Modal(document.getElementById('viewTicketModal'));
        modal.show();

        document.getElementById('replyTicketId').value = id;
        document.getElementById('vId').innerText = id;
        document.getElementById('vSubject').innerText = "Loading Conversation...";

        const chatBox = document.getElementById('chatStream');

        // Skeleton Loader
        chatBox.innerHTML = `
            <div class="d-flex flex-column gap-4 p-2 opacity-50">
                <div class="d-flex gap-3" style="max-width:60%">
                    <div class="bg-secondary rounded-3" style="width:40px; height:40px;"></div>
                    <div class="bg-secondary rounded-3 w-100" style="height:60px;"></div>
                </div>
                <div class="d-flex gap-3 align-self-end flex-row-reverse" style="max-width:60%">
                    <div class="bg-secondary rounded-3" style="width:40px; height:40px;"></div>
                    <div class="bg-secondary rounded-3 w-100" style="height:40px;"></div>
                </div>
            </div>`;

        fetch(`support.php?action=get_details&id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.error) { chatBox.innerHTML = `<div class="text-danger text-center p-5">${data.error}</div>`; return; }

                document.getElementById('vSubject').innerText = data.subject;
                const isOpen = (data.status === 'open');

                const badge = document.getElementById('vStatusBadge');
                badge.className = `badge-status ${isOpen ? 'st-open' : 'st-closed'}`;
                document.getElementById('vStatusText').innerText = data.status;

                let html = '';

                // Official Solution Box
                if (data.status === 'closed' && data.solution) {
                    html += `
        <div class="d-flex justify-content-center w-100">
            <div class="solution-card">
                <div class="sol-header">
                    <i class="fa-solid fa-circle-check"></i> 
                    Official Resolution
                </div>
                <div class="sol-body">${data.solution}</div>
            </div>
        </div>
    `;

                    // Disable Input Area Visuals
                    const replyInput = document.getElementById('replyInput');
                    const replyBtn = document.getElementById('replyBtn');
                    const replyArea = document.getElementById('replyArea');

                    replyInput.disabled = true;
                    replyInput.value = ""; // Clear any draft
                    replyInput.setAttribute("placeholder", "This ticket has been marked as resolved.");
                    replyInput.style.backgroundColor = "#f1f5f9"; // Visual grey out

                    replyBtn.disabled = true;
                    replyBtn.classList.add('disabled');

                    replyArea.classList.add('disabled');
                    replyArea.style.opacity = "0.7";
                } else {
                    // Enable Input
                    document.getElementById('replyInput').disabled = false;
                    document.getElementById('replyInput').placeholder = "Write a reply...";
                    document.getElementById('replyBtn').disabled = false;
                    document.getElementById('replyArea').classList.remove('disabled');
                }

                // Render Messages
                html += renderMsg(data.message, 'You', true, data.created_at);

                if (data.messages) {
                    data.messages.forEach(msg => {
                        const isMe = (msg.sender_type === 'client');
                        const name = isMe ? 'You' : (msg.sender_name || 'Support Agent');
                        html += renderMsg(msg.message, name, isMe, msg.created_at);
                    });
                }

                chatBox.innerHTML = html;
                chatBox.scrollTop = chatBox.scrollHeight;
            });
    }

    function renderMsg(text, name, isMe, time) {
        const align = isMe ? 'me' : 'sys';
        const avCls = isMe ? 'av-user' : 'av-sys';
        const avIcon = isMe ? '<i class="fa-regular fa-user"></i>' : '<i class="fa-solid fa-headset"></i>';

        return `
            <div class="msg-row ${align}">
                <div class="msg-avatar ${avCls}">${avIcon}</div>
                <div>
                    <div class="msg-bubble">
                        ${text.replace(/\n/g, '<br>')}
                    </div>
                    <div class="msg-time">
                        ${isMe ? '' : name + ' • '} ${new Date(time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </div>
                </div>
            </div>`;
    }

    // Submit Reply via AJAX
    document.getElementById('replyForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = document.getElementById('replyBtn');
        const input = document.getElementById('replyInput');

        if (!input.value.trim()) return;

        const originalIcon = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i>';

        const formData = new FormData(this);

        fetch('support.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    input.value = '';
                    input.style.height = '55px';
                    openTicket(formData.get('ticket_id')); // Refresh chat
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalIcon;
            });
    });

    // Success Alert
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === 'created') {
        Swal.fire({
            icon: 'success',
            title: 'Ticket Created',
            text: 'We have received your request and will respond shortly.',
            confirmButtonColor: '#0f172a',
            customClass: { popup: 'rounded-4' }
        }).then(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        });
    }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>