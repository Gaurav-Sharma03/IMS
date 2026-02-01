<?php
ob_start();
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../controllers/SupportController.php';

requireRole(['admin', 'staff']);

$controller = new SupportController();
$role = $_SESSION['role'];

// --- AJAX ---
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_ticket') {
    ob_clean(); header('Content-Type: application/json');
    echo json_encode($controller->getTicketDetails($_GET['id'])); exit;
}

// --- CREATE PLATFORM TICKET ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_platform'])) {
    $res = $controller->createPlatformTicket();
    if($res['status'] === 'success') { header("Location: manage.php?view=platform&success=created"); exit; }
}

// --- DETERMINE VIEW MODE (Tabs) ---
$view_mode = $_GET['view'] ?? 'clients'; // 'clients' or 'platform'
$tickets = $controller->getCompanyTickets($_GET['search']??'', $_GET['status']??'', $view_mode);

// Stats
$total = count($tickets);
$open = 0; $closed = 0;
foreach($tickets as $t) { ($t['status'] === 'closed') ? $closed++ : $open++; }

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    :root { --app-bg: #f8fafc; --primary: #4f46e5; --text-main: #1e293b; --border: #e2e8f0; }
    .main-content { margin-left: 250px; padding: 30px; background: var(--app-bg); min-height: 100vh; font-family: 'Inter', sans-serif; }
    
    /* Tabs */
    .nav-pills .nav-link { color: #64748b; font-weight: 600; padding: 10px 20px; border-radius: 8px; margin-right: 10px; }
    .nav-pills .nav-link.active { background-color: var(--primary); color: white; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
    
    /* Panel */
    .ticket-panel { background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .table thead th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; padding: 16px 24px; letter-spacing: 0.05em; }
    .table tbody td { padding: 18px 24px; vertical-align: middle; border-bottom: 1px solid var(--border); }
    .table-hover tbody tr:hover { background-color: #f8fafc; cursor: pointer; }

    /* Badges */
    .badge-soft { padding: 5px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .badge-soft.open { background: #dcfce7; color: #166534; }
    .badge-soft.closed { background: #f1f5f9; color: #475569; }
    .type-badge { background: #e0e7ff; color: #3730a3; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }

    /* Chat Modal */
    .modal-content { border: 0; border-radius: 16px; overflow: hidden; }
    .chat-layout { display: flex; height: 650px; }
    .chat-main { flex: 1; display: flex; flex-direction: column; background: #fff; }
    .chat-sidebar { width: 300px; background: #f8fafc; border-left: 1px solid var(--border); padding: 24px; }
    .chat-scroll { flex: 1; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 16px; background: #f8fafc; }
    
    .msg { max-width: 80%; padding: 14px 18px; border-radius: 14px; font-size: 0.95rem; line-height: 1.5; }
    .msg-me { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 2px; }
    .msg-them { align-self: flex-start; background: white; border: 1px solid var(--border); color: #1e293b; border-bottom-left-radius: 2px; }
    
    .solution-box { background: #ecfdf5; border: 1px solid #6ee7b7; padding: 16px; border-radius: 8px; margin: 16px; display: none; }
    .solution-box.visible { display: block; }
    .resolve-active textarea { background: #ecfdf5 !important; border-color: #10b981 !important; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Support Center</h2>
            <p class="text-muted mb-0">Manage client tickets or contact platform support.</p>
        </div>
        <button class="btn btn-dark px-4 py-2 fw-bold shadow-sm rounded-3" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="fa-solid fa-headset me-2"></i> Contact Superadmin
        </button>
    </div>

    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $view_mode==='clients'?'active':'' ?>" href="?view=clients">
                <i class="fa-solid fa-users me-2"></i> Client Support Queue
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $view_mode==='platform'?'active':'' ?>" href="?view=platform">
                <i class="fa-solid fa-shield-halved me-2"></i> My Platform Requests
            </a>
        </li>
    </ul>

    <div class="ticket-panel">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <div class="fw-bold text-muted small text-uppercase">
                <?= $view_mode==='clients' ? 'Tickets from your Clients' : 'Tickets sent to Superadmin' ?>
            </div>
            <div class="d-flex gap-2">
                <select class="form-select form-select-sm" style="width:120px" onchange="location.href='?view=<?= $view_mode ?>&status='+this.value">
                    <option value="">Status</option><option value="open">Open</option><option value="closed">Closed</option>
                </select>
                <input type="text" class="form-control form-control-sm" style="width: 250px;" placeholder="Search...">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>ID</th><th><?= $view_mode==='clients'?'Client':'Type' ?></th><th>Subject</th><th>Status</th><th>Updated</th></tr></thead>
                <tbody>
                    <?php if(empty($tickets)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No tickets found in this queue.</td></tr>
                    <?php else: foreach($tickets as $t): ?>
                    <tr onclick="openTicket(<?= $t['ticket_id'] ?>)" id="row-<?= $t['ticket_id'] ?>">
                        <td class="fw-bold text-primary">#<?= $t['ticket_id'] ?></td>
                        <td>
                            <?php if($view_mode === 'clients'): ?>
                                <span class="fw-bold text-dark"><?= htmlspecialchars($t['client_name']) ?></span>
                            <?php else: ?>
                                <span class="type-badge">Platform Support</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($t['subject']) ?></td>
                        <td><span id="tbl-badge-<?= $t['ticket_id'] ?>" class="badge-soft <?= $t['status']=='open'?'open':'closed' ?>"><?= $t['status'] ?></span></td>
                        <td class="text-muted small"><?= date('M d, H:i', strtotime($t['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold">Contact Platform Support</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info small mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i> This ticket will be sent directly to the <strong>Superadmin</strong>. Use this for billing or system issues.
                </div>
                <form method="POST">
                    <input type="hidden" name="create_platform" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">SUBJECT</label>
                        <select name="subject" class="form-select" required>
                            <option value="">Select Topic...</option>
                            <option value="System Bug">System Bug / Error</option>
                            <option value="Billing Issue">Billing / Subscription</option>
                            <option value="Feature Request">Feature Request</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-muted">DETAILS</label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Describe the issue..." required></textarea>
                    </div>

                    <div class="d-grid">
                        <button class="btn btn-dark fw-bold">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ticketModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-0 row g-0">
                <div class="col-lg-8 chat-container">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-secondary me-2" id="mStatusBadge">...</span>
                            <strong id="mSubj">Loading...</strong>
                        </div>
                        <button type="button" class="btn-close" onclick="closeModal()"></button>
                    </div>

                    <div id="chatBox" class="chat-scroll"></div>

                    <div id="solutionDisplay" class="solution-box">
                        <div class="fw-bold text-success mb-1"><i class="fa-solid fa-check-circle"></i> Official Solution</div>
                        <div id="solutionText" class="text-dark small"></div>
                    </div>

                    <div class="p-3 border-top bg-white">
                        <form id="replyForm">
                            <input type="hidden" name="ajax_reply" value="1">
                            <input type="hidden" name="ticket_id" id="mId">
                            <input type="hidden" name="action_type" id="mAction" value="reply">
                            
                            <div class="d-flex flex-column gap-2" id="inputContainer">
                                <textarea name="message" id="mMsg" class="form-control" rows="2" placeholder="Type a reply..." required></textarea>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="resolveToggle" onchange="toggleResolve()">
                                        <label class="form-check-label small text-muted fw-bold" for="resolveToggle">Mark as Solution</label>
                                    </div>
                                    <button id="sendBtn" class="btn btn-primary px-4 fw-bold">Send Reply</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4 bg-light border-start p-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Ticket Info</h6>
                    <div class="mb-3">
                        <label class="small text-muted d-block">Requestor</label>
                        <span id="mClient" class="fw-bold text-dark"></span>
                    </div>
                    <div id="clientContactInfo">
                        <div class="mb-3"><label class="small text-muted d-block">Email</label><span id="mEmail" class="text-dark"></span></div>
                        <div class="mb-3"><label class="small text-muted d-block">Phone</label><span id="mPhone" class="text-dark"></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const MY_ROLE = '<?= $role ?>';
    const VIEW_MODE = '<?= $view_mode ?>';

    function updateUI(id, status, solution) {
        document.getElementById('mStatusBadge').innerText = status.toUpperCase();
        document.getElementById('mStatusBadge').className = `badge me-2 ${status === 'open' ? 'bg-primary' : 'bg-secondary'}`;
        const tBadge = document.getElementById(`tbl-badge-${id}`);
        if(tBadge) { tBadge.innerText = status; tBadge.className = `badge-soft ${status === 'open' ? 'open' : 'closed'}`; }
        
        const solBox = document.getElementById('solutionDisplay');
        if (status === 'closed' && solution) {
            solBox.classList.add('visible'); document.getElementById('solutionText').innerText = solution;
        } else { solBox.classList.remove('visible'); }
    }

    function toggleResolve() {
        const isChecked = document.getElementById('resolveToggle').checked;
        const container = document.getElementById('inputContainer');
        const input = document.getElementById('mMsg');
        const btn = document.getElementById('sendBtn');
        const action = document.getElementById('mAction');

        if(isChecked) {
            container.classList.add('resolve-active'); input.placeholder = "Write OFFICIAL SOLUTION here. This closes the ticket.";
            btn.className = 'btn btn-success px-4 fw-bold'; btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> Resolve'; action.value = 'resolve';
        } else {
            container.classList.remove('resolve-active'); input.placeholder = "Type a reply...";
            btn.className = 'btn btn-primary px-4 fw-bold'; btn.innerHTML = 'Send Reply'; action.value = 'reply';
        }
    }

    function openTicket(id) {
        const modal = new bootstrap.Modal(document.getElementById('ticketModal')); modal.show();
        document.getElementById('mId').value = id;
        document.getElementById('resolveToggle').checked = false; toggleResolve();
        document.getElementById('chatBox').innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>';

        fetch(`manage.php?ajax_action=get_ticket&id=${id}`).then(r=>r.json()).then(data => {
            document.getElementById('mSubj').innerText = data.subject;
            document.getElementById('mClient').innerText = data.client_name;
            document.getElementById('mEmail').innerText = data.client_email || '--';
            document.getElementById('mPhone').innerText = data.client_phone || '--';
            
            // Hide contact info if it's a platform ticket (optional visual cleanup)
            if(data.client_id == 0) document.getElementById('clientContactInfo').style.display = 'none';
            else document.getElementById('clientContactInfo').style.display = 'block';

            updateUI(id, data.status, data.solution);

            let html = renderMsg(data.client_name, 'client', data.message, 'Original Request');
            if(data.messages) data.messages.forEach(m => html += renderMsg(m.sender_name, m.sender_type, m.message, m.nice_date));
            document.getElementById('chatBox').innerHTML = html;
        });
    }

    function renderMsg(name, type, text, time) {
        // "Me" logic: 
        // 1. If I am Admin/Staff, and sender is Admin/Staff/Superadmin => Me (Right side).
        // 2. If sender is Client => Them (Left side).
        let isMe = (type === 'admin' || type === 'staff' || type === 'superadmin');
        const align = isMe ? 'msg-me' : 'msg-them';
        
        // Special highlighting for Superadmin replies in Platform view
        const bgClass = (type === 'superadmin') ? 'bg-dark text-white border-0' : '';

        return `<div class="d-flex flex-column ${isMe?'align-items-end':'align-items-start'} mb-3">
                    <div class="msg ${align} ${bgClass}">
                        <div class="small opacity-75 mb-1 fw-bold">${name}</div>
                        ${text.replace(/\n/g, '<br>')}
                    </div>
                    <div class="small text-muted mt-1" style="font-size:0.7rem">${time}</div>
                </div>`;
    }

    function closeModal() {
        bootstrap.Modal.getInstance(document.getElementById('ticketModal')).hide();
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove()); document.body.style = ''; document.body.classList.remove('modal-open');
    }

    document.getElementById('replyForm').addEventListener('submit', function(e){
        e.preventDefault();
        const id = document.getElementById('mId').value;
        const isResolve = document.getElementById('mAction').value === 'resolve';
        if(isResolve) {
            Swal.fire({ title: 'Resolve Ticket?', text: "This saves the solution and closes the ticket.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#10b981', confirmButtonText: 'Yes, Resolve' })
            .then((res) => { if(res.isConfirmed) submitData(this, id); });
        } else { submitData(this, id); }
    });

    function submitData(form, id) {
        const btn = document.getElementById('sendBtn'); const input = document.getElementById('mMsg'); const origText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = 'Sending...';
        fetch('reply.php', { method: 'POST', body: new FormData(form) }).then(r=>r.json()).then(res => {
            if(res.status === 'success') {
                openTicket(id); input.value = '';
                if(res.new_status) { let sol = (res.new_status === 'closed') ? input.value : null; updateUI(id, res.new_status, sol); }
                if(document.getElementById('mAction').value === 'resolve') Swal.fire('Resolved!', 'Ticket closed.', 'success');
            } else Swal.fire('Error', res.message, 'error');
        }).finally(() => { btn.disabled = false; btn.innerHTML = origText; });
    }

    if(new URLSearchParams(window.location.search).get('success')) {
        Swal.fire({ icon: 'success', title: 'Sent', text: 'Support request sent to Superadmin.', timer: 2500, showConfirmButton: false });
        window.history.replaceState({}, document.title, window.location.pathname);
    }
</script>
<?php include __DIR__ . '/../layouts/footer.php'; ?>