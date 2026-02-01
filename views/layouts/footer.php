<style>
    /* Professional Dashboard Footer */
    .dash-footer {
        padding: 1.25rem 1.5rem;
        background: #0f172a;
        border-top: 1px solid #e2e8f0;
        margin-left: var(--sidebar-width);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        z-index: 800;
    }

    .footer-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-text {
        font-size: 13px;
        color: #b9babd;
        font-weight: 500;
    }

    .footer-brand-text {
        color: #ffffff;
        font-weight: 800;
         font-size: 16px;
    }

    .footer-links {
        display: flex;
        gap: 1.5rem;
    }

    .footer-links a {
        color: #94a3b8;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: color 0.2s ease;
    }

    .footer-links a:hover {
        color: #6366f1; /* Matches the Header Brand Color */
    }

    /* System Status Dot */
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        background: #f0fdf4;
        color: #166534;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; }

    @media (max-width: 992px) {
        .dash-footer { margin-left: 0; text-align: center; }
        .footer-container { justify-content: center; }
    }
</style>

<footer class="dash-footer">
    <div class="footer-container">
        <div class="footer-text">
            &copy; <?= date('Y') ?> <span class="footer-brand-text">Invoice Management System (IMS)</span>. 
            All Rights Reserved. 
            <span class="d-none d-sm-inline-block ms-2">
                Created with <i class="fa-solid fa-heart text-danger"></i> by Gaurav
            </span>
        </div>

        <div class="d-flex align-items-center gap-4">
            <div class="status-indicator d-none d-md-flex">
                <span class="dot"></span> System Online
            </div>
            <div class="footer-links">
                <a href="#">Support</a>
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    /**
     * IMS UI Controller
     */
    
    // 1. Toggle Dropdowns (Notifications & Profile)
    function toggleDropdown(menuId) {
        const menu = document.getElementById(menuId);
        const allMenus = document.querySelectorAll('.custom-dropdown');
        
        // Close other open menus
        allMenus.forEach(m => {
            if (m.id !== menuId) m.classList.remove('show');
        });
        
        // Toggle target menu
        menu.classList.toggle('show');
    }

    // 2. Sidebar Toggle Controller
    const sidebar = document.getElementById('sidebarMain');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainWrapper = document.querySelector('.main-content'); // Assuming your main div has this class

    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (window.innerWidth > 992) {
                // Desktop: Toggle collapsed state
                sidebar.classList.toggle('collapsed');
                if(mainWrapper) mainWrapper.classList.toggle('expanded');
            } else {
                // Mobile: Toggle slide-in menu
                sidebar.classList.toggle('open');
            }
        });
    }

    // 3. Global Click Listener (Close menus when clicking outside)
    window.addEventListener('click', (e) => {
        // Close dropdowns
        if (!e.target.closest('.custom-dropdown') && 
            !e.target.closest('.icon-btn') && 
            !e.target.closest('.profile-trigger')) {
            document.querySelectorAll('.custom-dropdown').forEach(m => m.classList.remove('show'));
        }
        
        // Close mobile sidebar
        if (window.innerWidth < 992 && 
            !e.target.closest('.sidebar') && 
            !e.target.closest('#sidebarToggle')) {
            if(sidebar) sidebar.classList.remove('open');
        }
    });

    // 4. Auto-hide Alerts (Optional)
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>
</body>
</html>