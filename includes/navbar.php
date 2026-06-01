<!-- Top Navbar -->
<nav class="navbar navbar-expand navbar-dark bg-dark px-3 px-md-4" style="min-height:56px;">

    <!-- Sidebar Toggle Button (Mobile) -->
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle" type="button">
        <i class="bi bi-list"></i>
    </button>

    <span class="navbar-text text-white fw-semibold ms-2 ms-md-0">
        <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
    </span>

    <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <span class="badge bg-warning text-dark hide-mobile">
                <i class="bi bi-shield-fill"></i> Admin
            </span>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'staff'): ?>
            <span class="badge bg-info text-dark hide-mobile">
                <i class="bi bi-people-fill"></i> Staff
            </span>
        <?php else: ?>
            <span class="badge bg-primary hide-mobile">
                <i class="bi bi-person-fill"></i> Student
            </span>
        <?php endif; ?>

        <div class="dropdown">
            <button class="btn btn-dark dropdown-toggle text-white-50 small d-flex align-items-center gap-2" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <span class="hide-mobile"><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                <li><a class="dropdown-item" href="/student_system/profile/view.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/student_system/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>

    </div>

</nav>

<!-- Sidebar Overlay (Mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Responsive JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && overlay) {
        // Toggle sidebar on button click
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });

        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });

        // Close sidebar when clicking a nav link (mobile)
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                sidebar.style.marginLeft = '0';
            }
        });
    }
});
</script>