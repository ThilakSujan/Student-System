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

function sidebarGroupActive($paths) {
    global $cp;
    foreach ($paths as $path) {
        if (strpos($_SERVER['PHP_SELF'], $path) !== false) return 'show';
    }
    return '';
}
?>

<style>
/* ══════════════════════════════════════════════
   PREMIUM SIDEBAR — full redesign
══════════════════════════════════════════════ */

/* Google Font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

#sidebar {
    width: 240px; /* Reduced width */
    height: 100vh;
    background: linear-gradient(160deg, #0f172a 0%, #1e1b4b 60%, #1a1040 100%);
    flex-shrink: 0;
    position: sticky;
    top: 0;
    transition: width 0.3s ease;
    overflow-y: auto;
    overflow-x: hidden;
    z-index: 1000;
    font-family: 'Inter', sans-serif;
    border-right: 1px solid rgba(255,255,255,0.06);
    display: flex;
    flex-direction: column;
}

#sidebar::-webkit-scrollbar { width: 4px; }
#sidebar::-webkit-scrollbar-track { background: transparent; }
#sidebar::-webkit-scrollbar-thumb { background: rgba(99,102,241,0.4); border-radius: 4px; }

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
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(99,102,241,0.45);
}

#sidebar .brand-text { font-size: 13.5px; font-weight: 700; color: #f1f5f9; }
#sidebar .brand-sub { font-size: 10px; color: rgba(148,163,184,0.8); }

#sidebar .nav-section {
    font-size: 9.5px; text-transform: uppercase; letter-spacing: 0.12em;
    color: rgba(148,163,184,0.55); padding: 18px 20px 6px; font-weight: 600;
}

#sidebar .nav-link, #sidebar .accordion-button {
    color: rgba(203,213,225,0.75);
    padding: 9px 14px 9px 20px;
    font-size: 13px; font-weight: 500;
    display: flex; align-items: center; gap: 11px;
    text-decoration: none; white-space: nowrap;
    transition: color 0.2s ease, background 0.2s ease;
    margin: 2px 10px; border-radius: 8px;
    background: transparent; border: none; box-shadow: none; outline: none;
}
#sidebar .accordion-button { padding-right: 14px; width: calc(100% - 20px); }
#sidebar .accordion-button::after { filter: invert(1) opacity(0.5); transform: scale(0.7); }
#sidebar .accordion-button:not(.collapsed)::after { transform: scale(0.7) rotate(-180deg); }

#sidebar .nav-link .nav-icon, #sidebar .accordion-button .nav-icon {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0;
    background: rgba(255,255,255,0.05);
    transition: background 0.2s ease, transform 0.2s ease;
}

#sidebar .nav-link:hover, #sidebar .accordion-button:hover { color: #fff; background: rgba(255,255,255,0.07); }
#sidebar .nav-link:hover .nav-icon, #sidebar .accordion-button:hover .nav-icon { background: rgba(99,102,241,0.25); transform: scale(1.08); }

#sidebar .nav-link.active, #sidebar .accordion-button:not(.collapsed) {
    color: #fff; background: linear-gradient(90deg, rgba(99,102,241,0.85), rgba(139,92,246,0.6));
    box-shadow: 0 4px 16px rgba(99,102,241,0.3);
}
#sidebar .nav-link.active .nav-icon, #sidebar .accordion-button:not(.collapsed) .nav-icon { background: rgba(255,255,255,0.18); }

#sidebar .nav-link.active::before, #sidebar .accordion-button:not(.collapsed)::before {
    content: ''; position: absolute; left: -10px; top: 20%; height: 60%; width: 3px; border-radius: 0 3px 3px 0; background: #818cf8;
}

/* Submenu Links */
#sidebar .accordion-body { padding: 4px 0 4px 38px; background: rgba(0,0,0,0.15); border-radius: 8px; margin: 0 10px; }
#sidebar .accordion-body .nav-link { margin: 2px 0; padding: 6px 12px; font-size: 12.5px; background: transparent; box-shadow: none; }
#sidebar .accordion-body .nav-link::before { display: none; }
#sidebar .accordion-body .nav-link.active { color: #818cf8; font-weight: 600; background: rgba(99,102,241,0.1); }
#sidebar .accordion-body .nav-link:hover { color: #fff; background: rgba(255,255,255,0.05); }

/* User strip */
.sidebar-user-strip { margin-top: auto; border-top: 1px solid rgba(255,255,255,0.08); padding: 14px 16px; display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,0.03); flex-shrink: 0; }
.sidebar-user-avatar { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #a78bfa); display: flex; align-items: center; justify-content: center; font-size: 15px; color: #fff; flex-shrink: 0; font-weight: 700; }
.sidebar-user-name { font-size: 12.5px; font-weight: 600; color: #e2e8f0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sidebar-user-role { font-size: 10.5px; color: rgba(148,163,184,0.7); }
.sidebar-logout-btn { margin-left: auto; color: rgba(148,163,184,0.6); font-size: 17px; transition: color 0.2s ease, transform 0.2s ease; }
.sidebar-logout-btn:hover { color: #ef4444; transform: translateX(2px); }

/* Bootstrap overrides */
.accordion-item { background: transparent; border: none; }
</style>

<nav id="sidebar">
    <a href="<?= $base ?>/dashboard/dashboard.php" class="sidebar-brand">
        <div class="sidebar-logo-wrap"><i class="bi bi-mortarboard-fill"></i></div>
        <div><div class="brand-text">Student System</div><div class="brand-sub">Management Portal</div></div>
    </a>

    <div style="flex:1; padding-top:8px; padding-bottom:8px;" class="accordion" id="sidebarAccordion">

    <?php if ($role === 'student'): ?>
        <div class="nav-section">Overview</div>
        <a href="<?= $base ?>/dashboard/dashboard.php" class="nav-link <?= sidebarActive('dashboard.php') ?>">
            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span> Dashboard
        </a>

        <div class="nav-section">My Portal</div>
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/marks/', '/attendance/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colPortal">
                <span class="nav-icon"><i class="bi bi-person-workspace"></i></span> Academics
            </button>
            <div id="colPortal" class="accordion-collapse collapse <?= sidebarGroupActive(['/marks/', '/attendance/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/marks/index.php" class="nav-link <?= sidebarActive('index.php', '/marks/') ?>">My Marks</a>
                    <a href="<?= $base ?>/attendance/index.php" class="nav-link <?= sidebarActive('index.php', '/attendance/') ?>">My Attendance</a>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/exam/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colExams">
                <span class="nav-icon"><i class="bi bi-calendar-event"></i></span> Exams
            </button>
            <div id="colExams" class="accordion-collapse collapse <?= sidebarGroupActive(['/exam/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/exam/index.php" class="nav-link <?= sidebarActive('index.php', '/exam/') ?>">Exam Schedule</a>
                </div>
            </div>
        </div>

        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/fee/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colFinance">
                <span class="nav-icon"><i class="bi bi-receipt"></i></span> Finance
            </button>
            <div id="colFinance" class="accordion-collapse collapse <?= sidebarGroupActive(['/fee/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/fee/student_report.php" class="nav-link <?= sidebarActive('student_report.php', '/fee/') ?>">My Fee Report</a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="nav-section">Overview</div>
        <a href="<?= $base ?>/dashboard/dashboard.php" class="nav-link <?= sidebarActive('dashboard.php') ?>">
            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span> Dashboard
        </a>
        <a href="<?= $base ?>/notifications/index.php" class="nav-link <?= sidebarActive('index.php', '/notifications/') ?>">
            <span class="nav-icon"><i class="bi bi-bell-fill"></i></span> Notifications
        </a>

        <div class="nav-section">Management</div>
        
        <!-- Students Accordion -->
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/students/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colStudents">
                <span class="nav-icon"><i class="bi bi-people"></i></span> Students
            </button>
            <div id="colStudents" class="accordion-collapse collapse <?= sidebarGroupActive(['/students/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/students/students.php" class="nav-link <?= sidebarActive('students.php') ?>">View Students</a>
                    <a href="<?= $base ?>/students/index.php" class="nav-link <?= sidebarActive('index.php', '/students/') ?>">Add Student</a>
                </div>
            </div>
        </div>

        <!-- Attendance Accordion -->
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/attendance/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colAtt">
                <span class="nav-icon"><i class="bi bi-calendar2-check"></i></span> Attendance
            </button>
            <div id="colAtt" class="accordion-collapse collapse <?= sidebarGroupActive(['/attendance/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/attendance/index.php" class="nav-link <?= sidebarActive('index.php', '/attendance/') ?>">View Attendance</a>
                    <a href="<?= $base ?>/attendance/mark.php" class="nav-link <?= sidebarActive('mark.php') ?>">Mark Attendance</a>
                </div>
            </div>
        </div>

        <!-- Marks Accordion -->
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/marks/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colMarks">
                <span class="nav-icon"><i class="bi bi-journal-check"></i></span> Marks
            </button>
            <div id="colMarks" class="accordion-collapse collapse <?= sidebarGroupActive(['/marks/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/marks/index.php" class="nav-link <?= sidebarActive('index.php', '/marks/') ?>">View Marks</a>
                    <a href="<?= $base ?>/marks/add.php" class="nav-link <?= sidebarActive('add.php') ?>">Enter Marks</a>
                </div>
            </div>
        </div>

        <!-- Exams Accordion -->
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/exam/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colExams">
                <span class="nav-icon"><i class="bi bi-calendar-event"></i></span> Exams
            </button>
            <div id="colExams" class="accordion-collapse collapse <?= sidebarGroupActive(['/exam/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/exam/index.php" class="nav-link <?= sidebarActive('index.php', '/exam/') ?>">Exam Schedule</a>
                    <a href="<?= $base ?>/exam/add.php" class="nav-link <?= sidebarActive('add.php', '/exam/') ?>">Schedule Exam</a>
                </div>
            </div>
        </div>

        <!-- Finance Accordion -->
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/fee/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colFinance">
                <span class="nav-icon"><i class="bi bi-cash-coin"></i></span> Finance
            </button>
            <div id="colFinance" class="accordion-collapse collapse <?= sidebarGroupActive(['/fee/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <?php if ($role === 'admin'): ?>
                    <a href="<?= $base ?>/fee/index.php" class="nav-link <?= sidebarActive('index.php', '/fee/') ?>">Fee Management</a>
                    <a href="<?= $base ?>/fee/categories.php" class="nav-link <?= sidebarActive('categories.php', '/fee/') ?>">Fee Categories</a>
                    <a href="<?= $base ?>/fee/structures.php" class="nav-link <?= sidebarActive('structures.php', '/fee/') ?>">Fee Structures</a>
                    <a href="<?= $base ?>/fee/payments.php" class="nav-link <?= sidebarActive('payments.php', '/fee/') ?>">Payments</a>
                    <a href="<?= $base ?>/fee/report.php" class="nav-link <?= sidebarActive('report.php', '/fee/') ?>">Fee Analytics</a>
                    <a href="<?= $base ?>/fee/staff_report.php" class="nav-link <?= sidebarActive('staff_report.php', '/fee/') ?>">Pending Dues</a>
                    <a href="<?= $base ?>/fee/student_report.php" class="nav-link <?= sidebarActive('student_report.php', '/fee/') ?>">Student Fee Report</a>
                    <?php elseif ($role === 'staff'): ?>
                    <a href="<?= $base ?>/fee/staff_report.php" class="nav-link <?= sidebarActive('staff_report.php', '/fee/') ?>">Pending Report</a>
                    <a href="<?= $base ?>/fee/student_report.php" class="nav-link <?= sidebarActive('student_report.php', '/fee/') ?>">Student Fee Report</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($role === 'admin'): ?>
        <div class="nav-section">System Administration</div>
        <!-- Admin Accordion -->
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/classes/', '/subjects/', '/staff/', '/admin/', '/institute/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colAdmin">
                <span class="nav-icon"><i class="bi bi-shield-lock"></i></span> Administration
            </button>
            <div id="colAdmin" class="accordion-collapse collapse <?= sidebarGroupActive(['/classes/', '/subjects/', '/staff/', '/admin/', '/institute/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/classes/index.php" class="nav-link <?= sidebarActive('index.php', '/classes/') ?>">Classes</a>
                    <a href="<?= $base ?>/subjects/index.php" class="nav-link <?= sidebarActive('index.php', '/subjects/') ?>">Subjects</a>
                    <a href="<?= $base ?>/staff/staff.php" class="nav-link <?= sidebarActive('staff.php') ?>">Staff</a>
                    <a href="<?= $base ?>/admin/admin_panel.php" class="nav-link <?= sidebarActive('admin_panel.php') ?>">User Management</a>
                    <?php
                    $pendingCount = 0;
                    if (isset($mysqli)) {
                        $pcRes = $mysqli->query("SELECT COUNT(*) AS c FROM users WHERE account_status='Pending'");
                        if ($pcRes) $pendingCount = (int)$pcRes->fetch_assoc()['c'];
                    }
                    ?>
                    <a href="<?= $base ?>/admin/approvals.php" class="nav-link <?= sidebarActive('approvals.php') ?>" style="<?= $pendingCount > 0 ? 'color:#fbbf24;' : '' ?>">
                        Approvals <?php if ($pendingCount > 0): ?><span class="badge bg-danger ms-1" style="font-size:10px;"><?= $pendingCount ?></span><?php endif; ?>
                    </a>
                    <a href="<?= $base ?>/institute/index.php" class="nav-link <?= sidebarActive('index.php', '/institute/') ?>">Institute Profile</a>
                </div>
            </div>
        </div>

        <!-- Communication Accordion -->
        <div class="accordion-item">
            <button class="accordion-button <?= sidebarGroupActive(['/email/']) ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#colComm">
                <span class="nav-icon"><i class="bi bi-envelope-check"></i></span> Communication
            </button>
            <div id="colComm" class="accordion-collapse collapse <?= sidebarGroupActive(['/email/']) ?>" data-bs-parent="#sidebarAccordion">
                <div class="accordion-body">
                    <a href="<?= $base ?>/email/index.php" class="nav-link <?= sidebarActive('index.php', '/email/') ?>">Email Logs</a>
                    <a href="<?= $base ?>/email/preview.php" class="nav-link <?= sidebarActive('preview.php', '/email/') ?>">Email Previewer</a>
                    <a href="<?= $base ?>/email/test.php" class="nav-link <?= sidebarActive('test.php', '/email/') ?>">SMTP Tester</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>

    <div class="sidebar-user-strip">
        <div class="sidebar-user-avatar">
            <?= strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)) ?>
        </div>
        <div style="flex:1; min-width:0;">
            <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></div>
            <div class="sidebar-user-role"><?= ucfirst($_SESSION['role'] ?? 'Role') ?></div>
        </div>
        <a href="<?= $base ?>/auth/logout.php" class="sidebar-logout-btn" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </div>
</nav>