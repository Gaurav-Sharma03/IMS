<?php
// 1. Load Dependencies & Controller
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/StaffController.php';

// 2. Handle Actions (Update/Delete)
$controller = new StaffController();
$controller->handleRequest();

// 3. Fetch Data (Direct DB call for list to keep it simple, or move to Controller)
$db = new Database();
$conn = $db->connect();
$company_id = $_SESSION['company_id'];

// Fetch Staff
$stmt = $conn->prepare("SELECT id, name, email, phone, status, created_at FROM users WHERE role = 'staff' AND company_id = :cid ORDER BY created_at DESC");
$stmt->execute([':cid' => $company_id]);
$staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: calc(100vh - 70px); }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }
    
    .table-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }
    .custom-table th { background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; color: #64748b; padding: 16px; font-weight: 700; }
    .custom-table td { padding: 16px; vertical-align: middle; color: #334155; }
    
    .avatar-circle { width: 36px; height: 36px; background: #e0e7ff; color: #4f46e5; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; margin-right: 12px; }
    
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .status-active { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .status-pending { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
    .status-inactive { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .btn-icon { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid transparent; color: #64748b; background: transparent; transition: 0.2s; }
    .btn-icon:hover { background: #f1f5f9; color: #0f172a; }
    .btn-icon.delete:hover { background: #fee2e2; color: #ef4444; }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">Staff Management</h4>
            <p class="text-muted small m-0">Manage team access and details</p>
        </div>
        <a href="add-staff.php" class="btn btn-primary fw-bold px-4">
            <i class="fa-solid fa-plus me-2"></i> Add New Staff
        </a>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Action completed successfully!',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>

    <div class="table-card overflow-hidden">
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-start-0 ps-0" placeholder="Search staff...">
            </div>
            <div class="text-muted small">Total: <strong><?= count($staffList) ?></strong></div>
        </div>

        <div class="table-responsive">
            <table class="table custom-table mb-0" id="staffTable">
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Joined Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($staffList) > 0): ?>
                        <?php foreach ($staffList as $staff): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle">
                                            <?= strtoupper(substr($staff['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($staff['name']) ?></div>
                                            <div class="small text-muted">Role: Staff</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="fa-regular fa-envelope me-2 text-muted"></i> <?= htmlspecialchars($staff['email']) ?></div>
                                    <?php if(!empty($staff['phone'])): ?>
                                        <div class="small text-muted mt-1"><i class="fa-solid fa-phone me-2"></i> <?= htmlspecialchars($staff['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $statusClass = match($staff['status']) {
                                        'active' => 'status-active',
                                        'pending' => 'status-pending',
                                        default => 'status-inactive'
                                    };
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= ucfirst($staff['status']) ?></span>
                                </td>
                                <td><?= date("M d, Y", strtotime($staff['created_at'])) ?></td>
                                <td class="text-end">
                                    <button class="btn-icon" onclick='openEditModal(<?= json_encode($staff) ?>)' title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn-icon delete" onclick="confirmDelete(<?= $staff['id'] ?>)" title="Remove">
                                        <i class="fa-regular fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No staff members found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

<div class="modal fade" id="editStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Staff Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Full Name</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Email Address</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Account Status</label>
                    <select name="status" id="edit_status" class="form-select">
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                    </select>
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
    // 1. Open Modal & Populate Data
    function openEditModal(staff) {
        document.getElementById('edit_user_id').value = staff.id;
        document.getElementById('edit_name').value = staff.name;
        document.getElementById('edit_email').value = staff.email;
        document.getElementById('edit_phone').value = staff.phone || '';
        document.getElementById('edit_status').value = staff.status;
        
        var myModal = new bootstrap.Modal(document.getElementById('editStaffModal'));
        myModal.show();
    }

    // 2. SweetAlert Delete
    function confirmDelete(id) {
        Swal.fire({
            title: 'Remove Staff Member?',
            text: "This action cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove!'
        }).then((result) => {
            if (result.isConfirmed) {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        })
    }

    // 3. Simple Search Filter
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#staffTable tbody tr');
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>