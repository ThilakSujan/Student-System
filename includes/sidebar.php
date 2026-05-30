<?php
$cp   = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$base = '/student_system';
?>
<nav id="sidebar">
    <a href="<?= $base ?>/dashboard/dashboard.php" class="sidebar-brand">
        <div class="brand-icon"><i class="bi bi-mortarboard-fill text-white"></i></div>
        <div>
            <div class="brand-text">Student System</div>
            <div class="brand-sub">Management Portal</div>
        </div>
    </a>

    <?php if ($role === 'student'): ?>
    <!-- ── Student menu ── -->
    <div class="nav-section">My Portal</div>
    <a href="<?= $base ?>/dashboard/dashboard.php"
       class="nav-link <?= $cp==='dashboard.php'?'active':'' ?>">
        <i class="bi bi-speedometer2"></i> My Dashboard
    </a>
    <a href="<?= $base ?>/marks/index.php"
       class="nav-link <?= $cp==='index.php'&&strpos($_SERVER['PHP_SELF'],'/marks/')!==false?'active':'' ?>">
        <i class="bi bi-journal-check"></i> My Marks
    </a>
    <a href="<?= $base ?>/attendance/index.php"
       class="nav-link <?= $cp==='index.php'&&strpos($_SERVER['PHP_SELF'],'/attendance/')!==false?'active':'' ?>">
        <i class="bi bi-calendar2-check"></i> My Attendance
    </a>

    <?php else: ?>
    <!-- ── Admin / Staff menu ── -->
    <div class="nav-section">Main</div>
    <a href="<?= $base ?>/dashboard/dashboard.php"
       class="nav-link <?= $cp==='dashboard.php'?'active':'' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-section">Students</div>
    <a href="<?= $base ?>/students/students.php"
       class="nav-link <?= $cp==='students.php'?'active':'' ?>">
        <i class="bi bi-people"></i> View Students
    </a>
    <a href="<?= $base ?>/students/index.php"
       class="nav-link <?= $cp==='index.php'&&strpos($_SERVER['PHP_SELF'],'/students/')!==false?'active':'' ?>">
        <i class="bi bi-person-plus"></i> Add Student
    </a>

    <div class="nav-section">Attendance</div>
    <a href="<?= $base ?>/attendance/index.php"
       class="nav-link <?= $cp==='index.php'&&strpos($_SERVER['PHP_SELF'],'/attendance/')!==false?'active':'' ?>">
        <i class="bi bi-calendar2-check"></i> Attendance
    </a>
    <?php if ($role!=='student'): ?>
    <a href="<?= $base ?>/attendance/mark.php"
       class="nav-link <?= $cp==='mark.php'?'active':'' ?>">
        <i class="bi bi-calendar-check"></i> Mark Attendance
    </a>
    <?php endif; ?>

    <div class="nav-section">Marks</div>
    <a href="<?= $base ?>/marks/index.php"
       class="nav-link <?= $cp==='index.php'&&strpos($_SERVER['PHP_SELF'],'/marks/')!==false?'active':'' ?>">
        <i class="bi bi-list-ol"></i> View Marks
    </a>
    <a href="<?= $base ?>/marks/add.php"
       class="nav-link <?= $cp==='add.php'?'active':'' ?>">
        <i class="bi bi-pencil-square"></i> Enter Marks
    </a>

    <?php if ($role==='admin'): ?>
    <div class="nav-section">Admin</div>
    <a href="<?= $base ?>/subjects/index.php"
       class="nav-link <?= $cp==='index.php'&&strpos($_SERVER['PHP_SELF'],'/subjects/')!==false?'active':'' ?>">
        <i class="bi bi-book"></i> Subject Management
    </a>
    <a href="<?= $base ?>/staff/staff.php"
       class="nav-link <?= $cp==='staff.php'?'active':'' ?>">
        <i class="bi bi-people-fill"></i> Staff Management
    </a>
    <a href="<?= $base ?>/admin/admin_panel.php"
       class="nav-link <?= $cp==='admin_panel.php'?'active':'' ?>">
        <i class="bi bi-shield-lock"></i> User Management
    </a>
    <a href="<?= $base ?>/institute/index.php"
       class="nav-link <?= $cp==='index.php'&&strpos($_SERVER['PHP_SELF'],'/institute/')!==false?'active':'' ?>">
        <i class="bi bi-building"></i> Institute Profile
    </a>
    <?php endif; ?>
    <?php endif; ?>

    <div class="sidebar-footer">
        <i class="bi bi-circle-fill text-success" style="font-size:8px"></i>
        &nbsp;<?= htmlspecialchars($_SESSION['username']??'') ?>
        <span class="badge bg-secondary ms-1" style="font-size:9px"><?= ucfirst($role) ?></span>
    </div>
</nav>