<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/ClientController.php';
require_once __DIR__ . '/../../config/database.php';

// Fetch Currencies for Edit Modal
$db = new Database();
$conn = $db->connect();
$currencies = $conn->query("SELECT currency_id, code FROM currencies")->fetchAll(PDO::FETCH_ASSOC);

// Initialize Controller
$controller = new ClientController();
$controller->handleRequest();

// Fetch Data
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$data = $controller->index($search, $page, $limit);

$clients = $data['clients'];
$totalClients = $data['total'];
$totalPages = ceil($totalClients / $limit);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    .table-card { border-radius: 12px; border: 1px solid #e2e8f0; background: white; }
    .custom-table th { background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 16px; font-weight: 700; }
    .custom-table td { padding: 16px; vertical-align: middle; color: #334155; }
    .avatar-initial { width: 35px; height: 35px; background: #eff6ff; color: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
    .btn-icon { border:none; background:transparent; color:#64748b; transition:0.2s; }
    .btn-icon:hover { color:#0f172a; } .btn-icon.delete:hover { color:#ef4444; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0 text-dark">Clients</h4>
            <p class="text-muted small m-0">Manage your customer base</p>
        </div>
        <a href="<?= BASE_URL ?>views/clients/add.php" class="btn btn-primary fw-bold px-4">
            <i class="fa-solid fa-user-plus me-2"></i> Add Client
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success shadow-sm border-0 mb-4"><i class="fa-solid fa-check-circle me-2"></i> Action completed successfully.</div>
    <?php endif; ?>

    <div class="table-card overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <form method="GET" class="position-relative">
                <i class="fa-solid fa-search position-absolute text-muted" style="top:50%; left:12px; transform:translateY(-50%); font-size:0.85rem;"></i>
                <input type="text" name="search" class="form-control ps-5" placeholder="Search clients..." value="<?= htmlspecialchars($search) ?>" style="width: 280px;">
            </form>
            <div class="text-muted small">Total: <strong><?= $totalClients ?></strong></div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table mb-0">
                <thead>
                    <tr>
                        <th width="30%">Client Name</th>
                        <th>Contact Info</th>
                        <th>Location</th>
                        <th>Balance</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($clients) > 0): ?>
                        <?php foreach ($clients as $c): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-initial"><?= strtoupper(substr($c['name'], 0, 1)) ?></div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($c['name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($c['contact_person'] ?: 'No contact person') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="fa-regular fa-envelope me-2 text-muted"></i> <?= htmlspecialchars($c['email']) ?></div>
                                    <div class="small mt-1"><i class="fa-solid fa-phone me-2 text-muted"></i> <?= htmlspecialchars($c['phone']) ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted"><?= htmlspecialchars($c['city']) ?>, <?= htmlspecialchars($c['country']) ?></div>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark">
                                        <?= $c['currency_code'] ?? '$' ?> 0.00 </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn-icon" onclick='openEditModal(<?= json_encode($c) ?>)'><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon delete" onclick="confirmDelete('<?= $c['client_id'] ?>')"><i class="fa-regular fa-trash-can"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No clients found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Client Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="add"> <input type="hidden" name="client_id" id="edit_id">
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Client Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Contact Person</label>
                        <input type="text" name="contact_person" id="edit_person" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    
                    <div class="col-12 mt-4"><h6 class="small text-muted text-uppercase fw-bold border-bottom pb-2">Address Details</h6></div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Street Address</label>
                        <input type="text" name="street" id="edit_street" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">City</label>
                        <input type="text" name="city" id="edit_city" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">State</label>
                        <input type="text" name="state" id="edit_state" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Country</label>
                        <input type="text" name="country" id="edit_country" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Postal Code</label>
                        <input type="text" name="postal_code" id="edit_zip" class="form-control">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Currency</label>
                        <select name="currency_id" id="edit_currency" class="form-select">
                            <?php foreach($currencies as $c): ?>
                                <option value="<?= $c['currency_id'] ?>"><?= $c['code'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Tax ID (GST/VAT)</label>
                        <input type="text" name="gst_vat" id="edit_vat" class="form-control">
                    </div>
                    <input type="hidden" name="address" id="edit_addr" value="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(c) {
    document.getElementById('edit_id').value = c.client_id;
    document.getElementById('edit_name').value = c.name;
    document.getElementById('edit_person').value = c.contact_person;
    document.getElementById('edit_email').value = c.email;
    document.getElementById('edit_phone').value = c.phone;
    
    document.getElementById('edit_street').value = c.street;
    document.getElementById('edit_city').value = c.city;
    document.getElementById('edit_state').value = c.state;
    document.getElementById('edit_country').value = c.country;
    document.getElementById('edit_zip').value = c.postal_code;
    document.getElementById('edit_addr').value = c.address; // If your DB uses full address
    
    document.getElementById('edit_currency').value = c.currency_id;
    document.getElementById('edit_vat').value = c.gst_vat;
    
    new bootstrap.Modal(document.getElementById('editClientModal')).show();
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Delete Client?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="client_id" value="${id}">`;
            document.body.appendChild(form);
            form.submit();
        }
    })
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>