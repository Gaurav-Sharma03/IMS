<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/NoteController.php';

$noteCtrl = new NoteController();
$noteCtrl->handleRequest();
$notes = $noteCtrl->getNotes();

include __DIR__ . '/../layouts/header.php';
include __DIR__ . '/../layouts/sidebar.php';
?>

<style>
    .main-content { margin-left: 250px; padding: 30px; background: #f8fafc; min-height: 100vh; }
    @media (max-width: 768px) { .main-content { margin-left: 0; } }

    .note-card {
        border: none; border-radius: 12px; padding: 20px; transition: transform 0.2s, box-shadow 0.2s;
        min-height: 200px; display: flex; flex-direction: column; justify-content: space-between;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05); position: relative;
    }
    .note-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
    
    .note-actions { position: absolute; top: 15px; right: 15px; opacity: 0; transition: 0.2s; }
    .note-card:hover .note-actions { opacity: 1; }
    
    .bg-yellow { background: #fef3c7; color: #92400e; }
    .bg-blue { background: #dbeafe; color: #1e40af; }
    .bg-green { background: #dcfce7; color: #166534; }
    .bg-pink { background: #fce7f3; color: #9d174d; }
    .bg-white { background: #ffffff; border: 1px solid #e2e8f0; }

    .btn-circle { width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; border: none; background: rgba(255,255,255,0.6); transition: 0.2s; }
    .btn-circle:hover { background: white; transform: scale(1.1); }
</style>

<main class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark m-0">My Personal Notes</h4>
            <p class="text-muted small m-0">Keep track of your ideas and to-dos.</p>
        </div>
        <button class="btn btn-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addNoteModal">
            <i class="fa-solid fa-plus me-2"></i> New Note
        </button>
    </div>

    <div class="row g-4">
        <?php if(empty($notes)): ?>
            <div class="col-12 text-center py-5">
                <i class="fa-regular fa-note-sticky fa-4x text-muted opacity-25 mb-3"></i>
                <p class="text-muted">You haven't created any notes yet.</p>
            </div>
        <?php else: foreach($notes as $note): ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="note-card <?= htmlspecialchars($note['color']) ?>">
                    <div class="note-actions d-flex gap-2">
                        <button class="btn-circle text-primary" onclick="editNote(<?= htmlspecialchars(json_encode($note)) ?>)">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form method="POST" onsubmit="return confirm('Delete this note?');">
                            <input type="hidden" name="delete_note" value="1">
                            <input type="hidden" name="note_id" value="<?= $note['note_id'] ?>">
                            <button class="btn-circle text-danger"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-2"><?= htmlspecialchars($note['title']) ?></h6>
                        <p class="small mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($note['content']) ?></p>
                    </div>
                    <small class="mt-3 opacity-75" style="font-size: 11px;">
                        <?= date('M d, H:i', strtotime($note['updated_at'])) ?>
                    </small>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>

</main>

<div class="modal fade" id="addNoteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalTitle">Create Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="add_note" id="action_input" value="1">
                    <input type="hidden" name="note_id" id="note_id">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Title</label>
                        <input type="text" name="title" id="note_title" class="form-control" placeholder="e.g. Meeting Agenda" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Content</label>
                        <textarea name="content" id="note_content" class="form-control" rows="4" placeholder="Write something..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Color</label>
                        <div class="d-flex gap-2">
                            <div class="form-check">
                                <input class="form-check-input bg-yellow" type="radio" name="color" value="bg-yellow" checked>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input bg-blue" type="radio" name="color" value="bg-blue">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input bg-green" type="radio" name="color" value="bg-green">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input bg-pink" type="radio" name="color" value="bg-pink">
                            </div>
                            <div class="form-check">
                                <input class="form-check-input bg-white" type="radio" name="color" value="bg-white">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 fw-bold" id="saveBtn">Save Note</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function editNote(note) {
        document.getElementById('modalTitle').innerText = 'Edit Note';
        document.getElementById('action_input').name = 'edit_note';
        document.getElementById('note_id').value = note.note_id;
        document.getElementById('note_title').value = note.title;
        document.getElementById('note_content').value = note.content;
        document.getElementById('saveBtn').innerText = 'Update Note';
        
        // Select Color
        document.querySelector(`input[name="color"][value="${note.color}"]`).checked = true;
        
        var modal = new bootstrap.Modal(document.getElementById('addNoteModal'));
        modal.show();
    }

    // Reset Modal on Close
    document.getElementById('addNoteModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('modalTitle').innerText = 'Create Note';
        document.getElementById('action_input').name = 'add_note';
        document.querySelector('form').reset();
        document.getElementById('saveBtn').innerText = 'Save Note';
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>