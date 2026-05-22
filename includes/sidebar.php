<?php
// Determine current page for active link highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<nav id="sidebar">

    <a href="../dashboard/dashboard.php" class="sidebar-brand">
        🎓 Student System
    </a>

    <!-- Main Navigation -->
    <div class="nav-section">Main</div>

    <a href="../dashboard/dashboard.php"
       class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <!-- Student Panel -->
    <div class="nav-section">Student Panel</div>

    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff'], true)): ?>
        <a href="../students/index.php"
           class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="bi bi-person-plus"></i> Add Student
        </a>

        <a href="../students/students.php"
           class="nav-link <?php echo ($current_page == 'students.php') ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> View Students
        </a>
    <?php endif; ?>

    <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff', 'student'], true)): ?>
        <a href="../dashboard/marks.php"
           class="nav-link <?php echo ($current_page == 'marks.php') ? 'active' : ''; ?>">
            <i class="bi bi-card-checklist"></i> Marks
        </a>
    <?php endif; ?>

    <!-- Admin Panel — only visible to admin -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <div class="nav-section">Admin</div>

        <a href="../staff/staff.php"
           class="nav-link <?php echo ($current_page == 'staff.php' || $current_page == 'staff_add.php' || $current_page == 'staff_edit.php') ? 'active' : ''; ?>">
            <i class="bi bi-people-fill"></i> Staff Management
        </a>

        <a href="../admin/admin_panel.php"
           class="nav-link <?php echo ($current_page == 'admin_panel.php') ? 'active' : ''; ?>">
            <i class="bi bi-shield-lock"></i> User Management
        </a>
    <?php endif; ?>

</nav>