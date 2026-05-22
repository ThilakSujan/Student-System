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

        <span class="text-white-50 small">
            <i class="bi bi-person-circle"></i>
            <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>
        </span>

        <a href="../../auth/logout.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>

    </div>

</nav>