<!-- Top Navbar -->
<nav class="navbar navbar-expand navbar-dark bg-dark px-4" style="min-height:56px;">

    <span class="navbar-text text-white fw-semibold">
        <?php echo isset($page_title) ? $page_title : 'Dashboard'; ?>
    </span>

    <div class="ms-auto d-flex align-items-center gap-3">

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <span class="badge bg-warning text-dark">
                <i class="bi bi-shield-fill"></i> Admin
            </span>
        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] == 'staff'): ?>
            <span class="badge bg-info text-dark">
                <i class="bi bi-people-fill"></i> Staff
            </span>
        <?php else: ?>
            <span class="badge bg-primary">
                <i class="bi bi-person-fill"></i> Student
            </span>
        <?php endif; ?>

        <div class="dropdown">
            <button class="btn btn-dark dropdown-toggle text-white-50 small d-flex align-items-center gap-2" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                <li><a class="dropdown-item" href="/student_system/profile/view.php"><i class="bi bi-person me-2"></i> My Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="/student_system/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>

    </div>

</nav>