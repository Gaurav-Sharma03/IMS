<?php
ob_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

requireRole(['superadmin']);

$controller = new SupportController();

// --- AJAX API ---
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_ticket') {
    ob_clean(); header('Content-Type: application/json');
    echo json_encode($controller->getTicketDetails($_GET['id'])); exit;
}

// --- FETCH DATA ---
$tickets = $controller->getGlobalTickets($_GET['search']??'', $_GET['status']??'');

// Stats Logic
$total = count($tickets);
$open = 0; $closed = 0;
foreach($tickets as $t) { ($t['status'] === 'closed') ? $closed++ : $open++; }

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root {
        --sa-primary: #6366f1; /* Indigo */
        --sa-bg: #0f172a; /* Slate 900 */
        --sa-surface: #1e293b; /* Slate 800 */
        --sa-border: #334155;
        --sa-text: #f8fafc;
        --sa-text-muted: #94a3b8;
    }

    body { 
        background-color: var(--sa-bg); 
        color: var(--sa-text); 
        font-family: 'Inter', sans-serif; 
        margin: 0;
        overflow-x: hidden;
    }

    /* --- RESPONSIVE LAYOUT --- */
    .main-content { 
        padding: 20px; 
        min-height: 100vh; 
        transition: margin-left 0.3s ease;
    }

    /* Desktop: Add margin for fixed sidebar */
    @media (min-width: 992px) {
        .main-content { margin-left: 250px; padding: 40px; }
    }

    /* --- STATS CARDS --- */
    .stat-card {
        background: var(--sa-surface); border: 1px solid var(--sa-border); border-radius: 16px;
        padding: 24px; display: flex; align-items: center; justify-content: space-between;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.3); border-color: var(--sa-primary); }
    .stat-val { font-size: 28px; font-weight: 800; color: white; line-height: 1; letter-spacing: -1px; }
    .stat-label { font-size: 11px; font-weight: 600; color: var(--sa-text-muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 6px; }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; background: rgba(255,255,255,0.05); }

    /* --- DATA TABLE --- */
    .glass-panel {
        background: rgba(30, 41, 59, 0.4); backdrop-filter: blur(12px);
        border: 1px solid var(--sa-border); border-radius: 20px; overflow: hidden; margin-top: 30px;
    }
    
    .panel-header {
        padding: 20px; border-bottom: 1px solid var(--sa-border); 
        display: flex; flex-direction: column; gap: 15px; 
        background: rgba(30, 41, 59, 0.8);
    }
    
    /* Desktop Panel Header */
    @media (min-width: 768px) {
        .panel-header { flex-direction: row; justify-content: space-between; align-items: center; }
    }

    .search-wrap { position: relative; width: 100%; }
    @media (min-width: 768px) { .search-wrap { width: 320px; } }

    .table-dark-custom th { 
        font-size: 11px; text-transform: uppercase; color: var(--sa-text-muted); font-weight: 700; 
        padding: 18px 24px; letter-spacing: 0.05em; border-bottom: 1px solid var(--sa-border); background-color: rgba(255,255,255,0.03); 
        white-space: nowrap; 
    }
    .table-dark-custom td { padding: 18px 24px; background-color: rgba(255,255,255,0.03); vertical-align: middle; border-bottom: 1px solid var(--sa-border); color: var(--sa-text); font-size: 14px; white-space: nowrap; }
    .table-hover tbody tr:hover { background-color: rgba(255,255,255,0.03); cursor: pointer; }

    /* --- BADGES --- */
    .status-pill { padding: 5px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 6px; }
    .status-pill.open { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
    .status-pill.closed { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }
    .dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

    /* --- RESPONSIVE MODAL --- */
    .modal-content { background: #0f172a; border: 1px solid #334155; color: white; border-radius: 16px; overflow: hidden; display: flex; flex-direction: column; }
    
    /* Layout Switcher: Column on Mobile, Row on Desktop */
    .chat-layout { display: flex; flex-direction: column; height: 90vh; }
    @media (min-width: 992px) { 
        .chat-layout { flex-direction: row; height: 80vh; max-height: 900px; } 
    }
    
    /* Left Side: Chat */
    .chat-main { 
        flex: 1; display: flex; flex-direction: column; background: #020617; 
        border-right: 0; border-bottom: 1px solid var(--sa-border);
        overflow: hidden;
    }
    @media (min-width: 992px) { .chat-main { border-right: 1px solid var(--sa-border); border-bottom: 0; } }

    .chat-header { padding: 16px 24px; border-bottom: 1px solid var(--sa-border); background: #0f172a; display: flex; justify-content: space-between; align-items: center; }
    .chat-stream { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 20px; }
    .chat-footer { padding: 16px 24px; border-top: 1px solid var(--sa-border); background: #0f172a; }

    /* Right Side: Sidebar info */
    .chat-sidebar { 
        width: 100%; background: #1e293b; padding: 0; overflow-y: auto; 
        max-height: 30vh; /* Limit height on mobile so chat is prioritized */
    }
    @media (min-width: 992px) { 
        .chat-sidebar { width: 320px; max-height: none; } 
    }

    .sidebar-section { padding: 24px; border-bottom: 1px solid var(--sa-border); }
    .sidebar-label { font-size: 11px; font-weight: 700; color: var(--sa-text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
    .info-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; font-size: 14px; }
    .info-row i { color: var(--sa-text-muted); width: 16px; text-align: center; }

    /* Bubbles */
    .msg-group { display: flex; gap: 14px; max-width: 85%; }
    .msg-group.me { align-self: flex-end; flex-direction: row-reverse; }
    
    .avatar { 
        width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; 
        justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0; 
    }
    .av-client { background: #334155; color: #cbd5e1; }
    .av-admin { background: var(--sa-primary); color: white; box-shadow: 0 0 15px rgba(99, 102, 241, 0.3); }

    .bubble { padding: 14px 18px; border-radius: 14px; font-size: 14px; line-height: 1.6; position: relative; word-wrap: break-word; }
    .msg-group.them .bubble { background: #1e293b; border: 1px solid #334155; color: #e2e8f0; border-top-left-radius: 4px; }
    .msg-group.me .bubble { background: var(--sa-primary); color: white; border-top-right-radius: 4px; }

    /* Inputs */
    .form-control-dark { background: #1e293b; border: 1px solid #334155; color: white; border-radius: 10px; }
    .form-control-dark:focus { background: #1e293b; border-color: var(--sa-primary); color: white; box-shadow: none; }
    .resolve-mode textarea { background: #064e3b !important; border-color: #059669 !important; }

    /* Resolve Toggle Responsiveness */
    .toggle-area { display: flex; flex-direction: column; gap: 15px; }
    @media (min-width: 576px) { 
        .toggle-area { flex-direction: row; justify-content: space-between; align-items: center; } 
    }
</style>

<main class="main-content">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4 mb-md-5 gap-3">
        <div>
            <h1 class="fw-bold text-white mb-1" style="letter-spacing: -0.5px; font-size: 1.75rem;">Command Center</h1>
            <p class="mb-0 font-weight-medium small text-white">Superadmin oversight and resolution desk.</p>
        </div>
        <div>
            <button class="btn btn-outline-light rounded-pill px-3 btn-sm" onclick="location.reload()">
                <i class="fa-solid fa-rotate-right me-2 text-white"></i> Refresh Data
            </button>
        </div>
    </div>

    <div class="row g-3 g-md-4 mb-4 mb-md-5">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= $total ?></div>
                    <div class="stat-label">Total Tickets</div>
                </div>
                <div class="stat-icon text-indigo-400"><i class="fa-solid fa-server"></i></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= $open ?></div>
                    <div class="stat-label">Pending Action</div>
                </div>
                <div class="stat-icon text-amber-400"><i class="fa-solid fa-clock"></i></div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div>
                    <div class="stat-val"><?= $closed ?></div>
                    <div class="stat-label">Resolved</div>
                </div>
                <div class="stat-icon text-emerald-400"><i class="fa-solid fa-check-double"></i></div>
            </div>
        </div>
    </div>

    <div class="glass-panel">
        <div class="panel-header">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 p-2 rounded-lg text-primary"><i class="fa-solid fa-list"></i></div>
                <h5 class="fw-bold m-0 text-white">Active Queue</h5>
            </div>
            <div class="search-wrap">
                <i class="fa-solid fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="saSearch" class="form-control bg-dark border-secondary text-white ps-5 rounded-pill w-100" placeholder="Search Tenant, Client or ID...">
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-dark-custom table-hover mb-0" id="saTable">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Tenant / Company</th>
                        <th>Client</th>
                        <th>Subject</th>
                        <th width="120">Status</th>
                        <th width="150" class="text-end">Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tickets as $t): ?>
                    <tr onclick="openGlobal(<?= $t['ticket_id'] ?>)">
                        <td class="fw-bold text-white">#<?= $t['ticket_id'] ?></td>
                        <td>
                            <div class="fw-bold text-white mb-1"><?= htmlspecialchars($t['company_name']) ?></div>
                            <div class="small text-white opacity-50" style="font-size: 11px;">ID: <?= $t['company_id'] ?></div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar bg-secondary" style="width:24px; height:24px; font-size:10px;">
                                    <?= substr($t['client_name'], 0, 1) ?>
                                </div>
                                <?= htmlspecialchars($t['client_name']) ?>
                            </div>
                        </td>
                        <td class="fw-medium text-light text-truncate" style="max-width: 200px;"><?= htmlspecialchars($t['subject']) ?></td>
                        <td>
                            <?php $isOpen = $t['status'] === 'open'; ?>
                            <span class="status-pill <?= $isOpen?'open':'closed' ?>" id="tbl-badge-<?= $t['ticket_id'] ?>">
                                <span class="dot"></span> <?= $t['status'] ?>
                            </span>
                        </td>
                        <td class="text-end text-white small font-monospace"><?= date('M d, H:i', strtotime($t['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal fade" id="saModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-fullscreen-lg-down modal-xl modal-dialog-centered" style="max-width: 1400px;">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="chat-layout">
                    
                    <div class="chat-main">
                        <div class="chat-header">
                            <div style="min-width: 0;">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="status-pill open" id="gStatusBadge"><span class="dot"></span> <span id="gStatusText">OPEN</span></span>
                                    <span class="text-white small fw-bold" style="letter-spacing: 1px;">#<span id="gIdDisp"></span></span>
                                </div>
                                <h4 class="fw-bold m-0 text-white text-truncate" id="gSubj" style="font-size: 1.1rem;">Loading...</h4>
                            </div>
                            <button type="button" class="btn-close btn-close-white ms-2" onclick="closeModal()"></button>
                        </div>

                        <div id="gChat" class="chat-stream"></div>

                        <div class="chat-footer" id="inputSection">
                            <form id="gReplyForm">
                                <input type="hidden" name="ajax_reply" value="1">
                                <input type="hidden" name="ticket_id" id="gId">
                                <input type="hidden" name="action_type" id="gActionType" value="reply">

                                <div class="d-flex flex-column gap-3">
                                    <div class="position-relative">
                                        <textarea name="message" id="gMessage" class="form-control form-control-dark p-3" rows="2" placeholder="Write an internal note or reply..." style="resize:none;" required></textarea>
                                    </div>
                                    
                                    <div class="toggle-area">
                                        <div class="form-check form-switch ps-0 d-flex align-items-center gap-2">
                                            <input class="form-check-input ms-0" type="checkbox" id="resolveToggle" onchange="toggleResolveMode()" style="width: 40px; height: 20px; flex-shrink: 0;">
                                            <label class="form-check-label text-white small fw-bold" for="resolveToggle">OFFICIAL SOLUTION</label>
                                        </div>
                                        <button id="sendBtn" class="btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm d-flex justify-content-center align-items-center gap-2 w-100 w-sm-auto">
                                            <span>Send Reply</span> <i class="fa-solid fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="chat-sidebar">
                        <div class="sidebar-section">
                            <label class="sidebar-label">REQUESTOR PROFILE</label>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="avatar bg-gradient text-white fs-5" style="width:48px; height:48px; background:linear-gradient(135deg, #6366f1, #a855f7);">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <h6 class="fw-bold text-white m-0 text-truncate" id="gClient">...</h6>
                                    <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary" style="font-size:10px;">CLIENT</span>
                                </div>
                            </div>
                            <div class="info-row text-truncate"><i class="fa-regular fa-envelope"></i> <span id="gEmail" class="text-truncate"></span></div>
                            <div class="info-row"><i class="fa-solid fa-phone"></i> <span id="gPhone"></span></div>
                        </div>

                        <div class="sidebar-section">
                            <label class="sidebar-label">TENANT ORGANIZATION</label>
                            <div class="p-3 rounded bg-dark border border-secondary">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="fa-solid fa-building text-primary"></i>
                                    <span class="fw-bold text-white text-truncate" id="gComp">...</span>
                                </div>
                                <div class="small text-muted">Tenant ID: <span class="font-monospace text-light">#<span id="gCompId"></span></span></div>
                            </div>
                        </div>

                        <div class="sidebar-section border-0">
                            <div class="alert alert-info border-0 bg-info bg-opacity-10 text-info small mb-0">
                                <div class="d-flex gap-2">
                                    <i class="fa-solid fa-circle-info mt-1"></i>
                                    <div>
                                        <strong>Protocol:</strong><br>
                                        Always resolve tickets with a formal solution summary.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- UI HELPERS ---
    function updateUI(id, status) {
        const isOpen = status === 'open';
        
        // Modal Header Badge
        const badge = document.getElementById('gStatusBadge');
        badge.className = `status-pill ${isOpen ? 'open' : 'closed'}`;
        document.getElementById('gStatusText').innerText = status.toUpperCase();

        // Background Table Badge
        const tBadge = document.getElementById(`tbl-badge-${id}`);
        if(tBadge) {
            tBadge.className = `status-pill ${isOpen ? 'open' : 'closed'}`;
            tBadge.innerHTML = `<span class="dot"></span> ${status}`;
        }

        // Lock Input if Closed
        const inputArea = document.getElementById('gMessage');
        const sendBtn = document.getElementById('sendBtn');
        
        if(!isOpen) {
            inputArea.disabled = true;
            inputArea.placeholder = "This ticket has been resolved and closed.";
            sendBtn.disabled = true;
            document.getElementById('resolveToggle').disabled = true;
        } else {
            inputArea.disabled = false;
            sendBtn.disabled = false;
            document.getElementById('resolveToggle').disabled = false;
        }
    }

    function toggleResolveMode() {
        const isChecked = document.getElementById('resolveToggle').checked;
        const inputDiv = document.getElementById('gMessage').parentElement;
        const input = document.getElementById('gMessage');
        const btn = document.getElementById('sendBtn');
        const action = document.getElementById('gActionType');

        if(isChecked) {
            inputDiv.classList.add('resolve-mode');
            input.placeholder = "Write the OFFICIAL SOLUTION here. This will be pinned for the client.";
            btn.className = 'btn btn-success px-4 py-2 fw-bold rounded-pill shadow-sm d-flex justify-content-center align-items-center gap-2 w-100 w-sm-auto';
            btn.innerHTML = '<span>Resolve & Close</span> <i class="fa-solid fa-check-circle"></i>';
            action.value = 'resolve';
        } else {
            inputDiv.classList.remove('resolve-mode');
            input.placeholder = "Write an internal note or reply...";
            btn.className = 'btn btn-primary px-4 py-2 fw-bold rounded-pill shadow-sm d-flex justify-content-center align-items-center gap-2 w-100 w-sm-auto';
            btn.innerHTML = '<span>Send Reply</span> <i class="fa-solid fa-paper-plane"></i>';
            action.value = 'reply';
        }
    }

    // --- FETCH & RENDER ---
    function openGlobal(id) {
        const modalEl = document.getElementById('saModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        
        document.getElementById('gId').value = id;
        document.getElementById('gIdDisp').innerText = id;
        
        // Reset UI State
        document.getElementById('resolveToggle').checked = false;
        toggleResolveMode();

        const box = document.getElementById('gChat');
        box.innerHTML = '<div class="h-100 d-flex justify-content-center align-items-center"><div class="spinner-border text-primary"></div></div>';
        
        // Clear old data to prevent flickering
        document.getElementById('gSubj').innerText = "Loading...";

        fetch(`global-tickets.php?ajax_action=get_ticket&id=${id}`)
            .then(r => r.json())
            .then(data => {
                document.getElementById('gSubj').innerText = data.subject;
                document.getElementById('gClient').innerText = data.client_name;
                document.getElementById('gComp').innerText = data.company_name;
                document.getElementById('gEmail').innerText = data.client_email || 'N/A';
                document.getElementById('gPhone').innerText = data.client_phone || 'N/A';
                if(data.company_id) document.getElementById('gCompId').innerText = data.company_id;

                updateUI(id, data.status);

                // Render Chat Stream
                let html = '';
                
                if(data.status === 'closed' && data.solution) {
                    html += `
                        <div class="p-3 mb-4 rounded-3 border border-success bg-success bg-opacity-10">
                            <div class="d-flex align-items-center gap-2 mb-1 text-success fw-bold">
                                <i class="fa-solid fa-check-circle"></i> Official Solution
                            </div>
                            <div class="text-light small opacity-75">${data.solution}</div>
                        </div>
                    `;
                }

                html += renderMsg(data.client_name, 'client', data.message, 'Original Request');

                if(data.messages) {
                    data.messages.forEach(m => {
                        html += renderMsg(m.sender_name, m.sender_type, m.message, m.nice_date);
                    });
                }
                box.innerHTML = html;
                setTimeout(() => { box.scrollTop = box.scrollHeight; }, 50);
            });
    }

    function renderMsg(name, type, text, time) {
        const isClient = (type === 'client');
        const align = isClient ? 'align-self-start' : 'align-self-end';
        const avClass = isClient ? 'av-client' : 'av-admin';
        const avTxt = isClient ? 'CL' : 'SA'; // Initials
        
        return `
            <div class="msg-group ${align}">
                <div class="avatar ${avClass}">${avTxt}</div>
                <div style="min-width: 0;">
                    <div class="bubble ${isClient ? 'bg-surface border border-secondary text-light' : 'bg-primary text-white'}">
                        ${text.replace(/\n/g, '<br>')}
                    </div>
                    <div class="small text-muted mt-2 ${isClient?'':'text-end'}" style="font-size:11px;">
                        ${name} • ${time}
                    </div>
                </div>
            </div>
        `;
    }

    function closeModal() {
        const modalEl = document.getElementById('saModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        setTimeout(() => {
             document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
             document.body.style = ''; 
             document.body.classList.remove('modal-open');
        }, 300);
    }

    // --- SUBMISSION ---
    document.getElementById('gReplyForm').addEventListener('submit', function(e){
        e.preventDefault();
        const id = document.getElementById('gId').value;
        const isResolve = document.getElementById('gActionType').value === 'resolve';
        
        if(isResolve) {
            Swal.fire({
                title: 'Close Ticket?',
                text: "This saves the solution and closes the thread.",
                icon: 'warning',
                background: '#1e293b',
                color: '#fff',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Yes, Resolve',
                cancelButtonColor: '#334155'
            }).then((res) => { if(res.isConfirmed) submitData(this, id); });
        } else {
            submitData(this, id);
        }
    });

    function submitData(form, id) {
        const btn = document.getElementById('sendBtn');
        const input = document.getElementById('gMessage');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';

        fetch('reply.php', { method: 'POST', body: new FormData(form) })
            .then(r => r.json())
            .then(res => {
                if(res.status === 'success') {
                    openGlobal(id); // Reload Chat
                    input.value = '';
                    
                    if(res.new_status) updateUI(id, res.new_status);
                    
                    if(document.getElementById('gActionType').value === 'resolve') {
                        Swal.fire({
                            title: 'Resolved!',
                            text: 'Ticket has been successfully closed.',
                            icon: 'success',
                            background: '#1e293b',
                            color: '#fff',
                            confirmButtonColor: '#6366f1'
                        });
                    }
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            })
            .finally(() => {
                btn.disabled = false;
                toggleResolveMode();
            });
    }

    // Filter Logic
    document.getElementById('saSearch').addEventListener('keyup', function() {
        let val = this.value.toLowerCase();
        document.querySelectorAll('#saTable tbody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
        });
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>