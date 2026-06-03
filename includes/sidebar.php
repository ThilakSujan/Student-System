<?php
$cp   = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? '';
$base = '/student_system';

// Helper: is a path active?
function sidebarActive(string $file, string $dir = ''): string {
    global $cp;
    $match = ($cp === $file);
    if ($dir) $match = $match && strpos($_SERVER['PHP_SELF'], $dir) !== false;
    return $match ? 'active' : '';
}
?>

<style>
/* ══════════════════════════════════════════════
   PREMIUM SIDEBAR — full redesign
══════════════════════════════════════════════ */

/* Google Font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

#sidebar {
    width: 255px;
    min-height: 100vh;
    background: linear-gradient(160deg, #0f172a 0%, #1e1b4b 60%, #1a1040 100%);
    flex-shrink: 0;
    position: relative;
    transition: margin-left 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    padding-bottom: 0;
    font-family: 'Inter', sans-serif;
    border-right: 1px solid rgba(255,255,255,0.06);
    display: flex;
    flex-direction: column;
}

/* Scrollbar */
#sidebar::-webkit-scrollbar { width: 4px; }
#sidebar::-webkit-scrollbar-track { background: transparent; }
#sidebar::-webkit-scrollbar-thumb { background: rgba(139,92,246,0.4); border-radius: 4px; }
#sidebar::-webkit-scrollbar-thumb:hover { background: rgba(139,92,246,0.7); }

/* ── Brand / Logo ── */
#sidebar .sidebar-brand {
    padding: 22px 20px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    background: rgba(255,255,255,0.03);
    flex-shrink: 0;
}

.sidebar-logo-wrap {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(99,102,241,0.45);
}

#sidebar .brand-text {
    font-size: 14px;
    font-weight: 700;
    color: #f1f5f9;
    line-height: 1.2;
    letter-spacing: 0.01em;
}

#sidebar .brand-sub {
    font-size: 10.5px;
    color: rgba(148,163,184,0.8);
    margin-top: 2px;
    font-weight: 400;
    letter-spacing: 0.02em;
}

/* ── Section labels ── */
#sidebar .nav-section {
    font-size: 9.5px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: rgba(148,163,184,0.55);
    padding: 18px 20px 6px;
    font-weight: 600;
}

/* ── Nav links ── */
#sidebar .nav-link {
    color: rgba(203,213,225,0.75);
    padding: 9px 14px 9px 20px;
    font-size: 13.5px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 11px;
    text-decoration: none;
    white-space: nowrap;
    border-radius: 0;
    position: relative;
    transition: color 0.2s ease, background 0.2s ease;
    margin: 1px 10px;
    border-radius: 10px;
}

#sidebar .nav-link .nav-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    background: rgba(255,255,255,0.05);
    transition: background 0.2s ease, transform 0.2s ease;
}

#sidebar .nav-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.07);
}

#sidebar .nav-link:hover .nav-icon {
    background: rgba(99,102,241,0.25);
    transform: scale(1.08);
}

/* Active state — glowing pill */
#sidebar .nav-link.active {
    color: #fff;
    background: linear-gradient(90deg, rgba(99,102,241,0.85), rgba(139,92,246,0.6));
    box-shadow: 0 4px 16px rgba(99,102,241,0.3);
}

#sidebar .nav-link.active .nav-icon {
    background: rgba(255,255,255,0.18);
}

/* Left accent bar on active */
#sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: -10px;
    top: 20%;
    height: 60%;
    width: 3px;
    border-radius: 0 3px 3px 0;
    background: #818cf8;
}

/* ── User info footer strip ── */
.sidebar-user-strip {
    margin-top: auto;
    border-top: 1px solid rgba(255,255,255,0.08);
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255,255,255,0.03);
    flex-shrink: 0;
}

.sidebar-user-avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #a78bfa);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    color: #fff;
    flex-shrink: 0;
    font-weight: 700;
}

.sidebar-user-name {
    font-size: 12.5px;
    font-weight: 600;
    color: #e2e8f0;
    line-height: 1.2;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.sidebar-user-role {
    font-size: 10.5px;
    color: rgba(148,163,184,0.7);
    margin-top: 1px;
}

.sidebar-logout-btn {
    margin-left: auto;
    color: rgba(148,163,184,0.6);
    font-size: 17px;
    text-decoration: none;
    transition: color 0.2s ease, transform 0.2s ease;
    flex-shrink: 0;
}

.sidebar-logout-btn:hover {
    color: #f87171;
    transform: translateX(2px);
}

/* ── Divider ── */
.sidebar-divider {
    height: 1px;
    background: rgba(255,255,255,0.06);
    margin: 6px 20px;
}
</style>

<nav id="sidebar">

    <!-- Brand -->
    <a href="<?= $base ?>/dashboard/dashboard.php" class="sidebar-brand">
        <div class="sidebar-logo-wrap">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <div class="brand-text">Student System</div>
            <div class="brand-sub">Management Portal</div>
        </div>
    </a>

    <!-- Nav Items -->
    <div style="flex:1; padding-top:8px; padding-bottom:8px;">

    <?php if ($role === 'student'): ?>
        <!-- ── Student menu ── -->
        <div class="nav-section">My Portal</div>

        <a href="<?= $base ?>/dashboard/dashboard.php"
           class="nav-link <?= sidebarActive('dashboard.php') ?>">
            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
            My Dashboard
        </a>
        <a href="<?= $base ?>/marks/index.php"
           class="nav-link <?= sidebarActive('index.php', '/marks/') ?>">
            <span class="nav-icon"><i class="bi bi-journal-check"></i></span>
            My Marks
        </a>
        <a href="<?= $base ?>/attendance/index.php"
           class="nav-link <?= sidebarActive('index.php', '/attendance/') ?>">
            <span class="nav-icon"><i class="bi bi-calendar2-check"></i></span>
            My Attendance
        </a>

    <?php else: ?>
        <!-- ── Admin / Staff menu ── -->
        <div class="nav-section">Overview</div>

        <a href="<?= $base ?>/dashboard/dashboard.php"
           class="nav-link <?= sidebarActive('dashboard.php') ?>">
            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span>
            Dashboard
        </a>

        <div class="nav-section">Students</div>
        <a href="<?= $base ?>/students/students.php"
           class="nav-link <?= sidebarActive('students.php') ?>">
            <span class="nav-icon"><i class="bi bi-people"></i></span>
            View Students
        </a>
        <a href="<?= $base ?>/students/index.php"
           class="nav-link <?= sidebarActive('index.php', '/students/') ?>">
            <span class="nav-icon"><i class="bi bi-person-plus"></i></span>
            Add Student
        </a>

        <div class="nav-section">Attendance</div>
        <a href="<?= $base ?>/attendance/index.php"
           class="nav-link <?= sidebarActive('index.php', '/attendance/') ?>">
            <span class="nav-icon"><i class="bi bi-calendar2-check"></i></span>
            Attendance
        </a>
        <a href="<?= $base ?>/attendance/mark.php"
           class="nav-link <?= sidebarActive('mark.php') ?>">
            <span class="nav-icon"><i class="bi bi-calendar-check"></i></span>
            Mark Attendance
        </a>

        <div class="nav-section">Marks</div>
        <a href="<?= $base ?>/marks/index.php"
           class="nav-link <?= sidebarActive('index.php', '/marks/') ?>">
            <span class="nav-icon"><i class="bi bi-list-ol"></i></span>
            View Marks
        </a>
        <a href="<?= $base ?>/marks/add.php"
           class="nav-link <?= sidebarActive('add.php') ?>">
            <span class="nav-icon"><i class="bi bi-pencil-square"></i></span>
            Enter Marks
        </a>

        <?php if ($role === 'admin'): ?>
        <div class="nav-section">Admin</div>
        <a href="<?= $base ?>/classes/index.php"
           class="nav-link <?= sidebarActive('index.php', '/classes/') ?>">
            <span class="nav-icon"><i class="bi bi-building-fill"></i></span>
            Classes
        </a>
        <a href="<?= $base ?>/subjects/index.php"
           class="nav-link <?= sidebarActive('index.php', '/subjects/') ?>">
            <span class="nav-icon"><i class="bi bi-book"></i></span>
            Subjects
        </a>
        <a href="<?= $base ?>/staff/staff.php"
           class="nav-link <?= sidebarActive('staff.php') ?>">
            <span class="nav-icon"><i class="bi bi-people-fill"></i></span>
            Staff
        </a>
        <a href="<?= $base ?>/admin/admin_panel.php"
           class="nav-link <?= sidebarActive('admin_panel.php') ?>">
            <span class="nav-icon"><i class="bi bi-shield-lock"></i></span>
            User Management
        </a>
        <a href="<?= $base ?>/institute/index.php"
           class="nav-link <?= sidebarActive('index.php', '/institute/') ?>">
            <span class="nav-icon"><i class="bi bi-building"></i></span>
            Institute Profile
        </a>
        <?php endif; ?>

    <?php endif; ?>

    </div>

    <!-- User strip at bottom -->
    <div class="sidebar-user-strip">
        <div class="sidebar-user-avatar">
            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
        </div>
        <div style="min-width:0;">
            <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
            <div class="sidebar-user-role"><?= ucfirst($role) ?></div>
        </div>
        <a href="/student_system/auth/logout.php" class="sidebar-logout-btn" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>

</nav>