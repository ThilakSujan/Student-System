<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
require_once '../config/db.php';

$page_title = "Class Roster";

$class_id = intval($_GET['class_id'] ?? 0);
if ($class_id <= 0) { header('Location: index.php'); exit; }

// Fetch class info
$cr = $mysqli->prepare(
    "SELECT c.*, u.username AS teacher_name
     FROM classes c LEFT JOIN users u ON u.id = c.class_teacher_id
     WHERE c.id = ?"
);
$cr->bind_param('i', $class_id);
$cr->execute();
$cls = $cr->get_result()->fetch_assoc();
if (!$cls) { header('Location: index.php'); exit; }

// Fetch enrolled students
$sr = $mysqli->prepare(
    "SELECT s.id, s.student_name, s.email, s.phone, s.department, s.gender, s.status, cs.assigned_at
     FROM class_students cs
     JOIN students s ON s.id = cs.student_id
     WHERE cs.class_id = ?
     ORDER BY s.student_name ASC"
);
$sr->bind_param('i', $class_id);
$sr->execute();
$students = $sr->get_result()->fetch_all(MYSQLI_ASSOC);

$total_enrolled = count($students);
$active_count   = count(array_filter($students, fn($s) => $s['status'] === 'Active'));
$gender_m       = count(array_filter($students, fn($s) => strtolower($s['gender']) === 'male'));
$gender_f       = count(array_filter($students, fn($s) => strtolower($s['gender']) === 'female'));

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
/* ── Responsive table column hiding ── */
@media (max-width: 767px) {
    .col-hide-sm  { display: none !important; }
    .roster-card-view { display: block !important; }
    .roster-table-view { display: none !important; }

    /* Banner stacks vertically */
    .class-banner-body {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 16px !important;
    }
    .class-banner-actions { width: 100%; }
    .class-banner-actions a { flex: 1; justify-content: center; text-align: center; }

    /* Toolbar stacks */
    .roster-toolbar { flex-direction: column !important; align-items: stretch !important; }
    .roster-toolbar .input-group { width: 100% !important; }

    /* Footer stacks */
    .roster-footer { flex-direction: column !important; gap: 10px !important; }
    .roster-footer a { width: 100%; text-align: center; }

    /* Stats: 2 per row on mobile */
    .stat-col { flex: 0 0 50%; max-width: 50%; }
}

@media (min-width: 768px) and (max-width: 991px) {
    .col-hide-md  { display: none !important; }
    .roster-card-view { display: none !important; }
    .roster-table-view { display: block !important; }
}

@media (min-width: 992px) {
    .roster-card-view { display: none !important; }
    .roster-table-view { display: block !important; }
}

/* ── Mobile student card ── */
.student-mob-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    transition: background .15s;
}
.student-mob-card:last-child { border-bottom: none; }
.student-mob-card:hover { background: #f8fafc; }
.student-mob-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg,#6366f1,#a78bfa);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 700; color: #fff;
    flex-shrink: 0; box-shadow: 0 2px 8px rgba(99,102,241,.28);
}
.student-mob-info { flex: 1; min-width: 0; }
.student-mob-name { font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.3; }
.student-mob-sub  { font-size: 12px; color: #94a3b8; margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.student-mob-badges { display: flex; gap: 5px; margin-top: 5px; flex-wrap: wrap; }
.pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
}

/* ── Table header style ── */
.roster-th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #94a3b8;
    font-weight: 700;
    padding-top: 14px;
    padding-bottom: 14px;
}

/* ── Section label decorations ── */
.stat-icon-box {
    width: 44px; height: 44px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
</style>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0" style="font-size:13px">
            <li class="breadcrumb-item"><a href="index.php">Classes</a></li>
            <li class="breadcrumb-item active">
                <?= htmlspecialchars($cls['class_name']) ?>
                <?= $cls['section'] ? '— '.htmlspecialchars($cls['section']) : '' ?>
            </li>
        </ol>
    </nav>

    <!-- ── Class banner ── -->
    <div class="card border-0 shadow-sm mb-4"
         style="background:linear-gradient(110deg,#4f46e5,#7c3aed);color:#fff;overflow:hidden;position:relative;">
        <!-- Decorative circles -->
        <div style="position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.06);pointer-events:none"></div>
        <div style="position:absolute;right:60px;bottom:-40px;width:100px;height:100px;border-radius:50%;background:rgba(255,255,255,.04);pointer-events:none"></div>

        <div class="card-body p-3 p-md-4 d-flex gap-3 class-banner-body" style="align-items:center">
            <!-- Icon -->
            <div class="d-none d-sm-flex flex-shrink-0"
                 style="width:60px;height:60px;border-radius:14px;background:rgba(255,255,255,.18);
                        align-items:center;justify-content:center;font-size:28px;">
                <i class="bi bi-building-fill"></i>
            </div>

            <!-- Info -->
            <div style="flex:1;min-width:0">
                <h5 class="fw-bold mb-1" style="font-size:clamp(16px,3vw,22px)">
                    <?= htmlspecialchars($cls['class_name']) ?>
                    <?php if ($cls['section']): ?>
                        <span class="badge ms-2"
                              style="background:rgba(255,255,255,.2);font-size:13px;vertical-align:middle">
                            <?= htmlspecialchars($cls['section']) ?>
                        </span>
                    <?php endif; ?>
                </h5>
                <div class="d-flex flex-wrap gap-2 gap-sm-3" style="font-size:12.5px;opacity:.85">
                    <?php if ($cls['academic_year']): ?>
                        <span><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($cls['academic_year']) ?></span>
                    <?php endif; ?>
                    <span>
                        <i class="bi bi-person-fill me-1"></i>
                        <?= $cls['teacher_name'] ? htmlspecialchars($cls['teacher_name']) : '<em>No teacher</em>' ?>
                    </span>
                    <span class="badge <?= $cls['status']==='Active' ? 'bg-success' : 'bg-danger' ?>">
                        <?= $cls['status'] ?>
                    </span>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-2 flex-wrap class-banner-actions">
                <a href="assign.php?class_id=<?= $class_id ?>" class="btn btn-light btn-sm">
                    <i class="bi bi-person-plus me-1"></i><span class="d-none d-sm-inline">Manage </span>Students
                </a>
                <a href="edit.php?id=<?= $class_id ?>" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-pencil me-1"></i><span class="d-none d-sm-inline">Edit Class</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ── Stats row ── -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Enrolled', $total_enrolled, 'bi-people-fill',       '#dbeafe','#1e40af'],
            ['Active',   $active_count,   'bi-check-circle-fill', '#d1fae5','#065f46'],
            ['Male',     $gender_m,       'bi-gender-male',       '#e0e7ff','#3730a3'],
            ['Female',   $gender_f,       'bi-gender-female',     '#fce7f3','#9d174d'],
        ] as [$lbl,$val,$icon,$bg,$fg]): ?>
        <div class="col-6 col-xl-3 stat-col">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="stat-icon-box" style="background:<?= $bg ?>">
                        <i class="bi <?= $icon ?>" style="color:<?= $fg ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px"><?= $lbl ?></div>
                        <div class="fw-bold" style="font-size:22px;line-height:1"><?= $val ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Students card ── -->
    <div class="card shadow-sm border-0">

        <!-- Card header -->
        <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap gap-2"
             style="background:linear-gradient(100deg,#1e293b,#334155);">
            <strong style="font-size:14px">
                <i class="bi bi-people me-2"></i>Enrolled Students
                <span class="badge ms-1" style="background:rgba(255,255,255,.18);font-size:11px"><?= $total_enrolled ?></span>
            </strong>
            <a href="assign.php?class_id=<?= $class_id ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-person-plus me-1"></i><span class="d-none d-sm-inline">Manage </span>Students
            </a>
        </div>

        <?php if (empty($students)): ?>
        <div class="card-body text-center py-5">
            <div style="width:68px;height:68px;border-radius:50%;background:#f1f5f9;
                        display:flex;align-items:center;justify-content:center;
                        font-size:30px;margin:0 auto 14px">
                <i class="bi bi-people text-muted"></i>
            </div>
            <p class="fw-semibold text-dark mb-1">No students enrolled yet</p>
            <p class="text-muted mb-4" style="font-size:13px">Assign students to this class to see them here.</p>
            <a href="assign.php?class_id=<?= $class_id ?>" class="btn btn-primary btn-sm px-4">
                <i class="bi bi-person-plus me-1"></i>Assign Students Now
            </a>
        </div>

        <?php else: ?>

        <!-- Toolbar -->
        <div class="px-3 px-md-4 py-3 border-bottom d-flex align-items-center gap-3 roster-toolbar"
             style="background:#f8fafc;justify-content:space-between;">
            <p class="mb-0 text-muted" style="font-size:13px;flex-shrink:0">
                Showing <strong id="visibleCount"><?= $total_enrolled ?></strong>
                of <strong><?= $total_enrolled ?></strong>
            </p>
            <div class="input-group input-group-sm" style="max-width:260px;width:100%">
                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="rosterSearch" class="form-control"
                       placeholder="Search name or email…" style="font-size:13px">
            </div>
        </div>

        <!-- ════ DESKTOP TABLE (md+) ════ -->
        <div class="roster-table-view table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0">
                        <th class="px-4 roster-th" style="width:46px">#</th>
                        <th class="roster-th">Student</th>
                        <th class="roster-th col-hide-md">Department</th>
                        <th class="roster-th">Gender</th>
                        <th class="roster-th col-hide-md">Phone</th>
                        <th class="roster-th">Status</th>
                        <th class="roster-th col-hide-md">Enrolled On</th>
                    </tr>
                </thead>
                <tbody id="rosterBodyDesktop">
                <?php foreach ($students as $i => $s):
                    $isMale   = strtolower($s['gender'] ?? '') === 'male';
                    $isActive = ($s['status'] ?? '') === 'Active';
                ?>
                    <tr class="roster-row" style="border-bottom:1px solid #f1f5f9"
                        data-name="<?= strtolower(htmlspecialchars($s['student_name'])) ?> <?= strtolower(htmlspecialchars($s['email'])) ?>">

                        <td class="px-4 text-muted" style="font-size:13px"><?= $i + 1 ?></td>

                        <!-- Student -->
                        <td class="py-3" style="min-width:180px">
                            <div class="d-flex align-items-center gap-3">
                                <div style="width:40px;height:40px;border-radius:50%;
                                            background:linear-gradient(135deg,#6366f1,#a78bfa);
                                            display:flex;align-items:center;justify-content:center;
                                            font-size:16px;font-weight:700;color:#fff;flex-shrink:0;
                                            box-shadow:0 2px 8px rgba(99,102,241,.25)">
                                    <?= strtoupper(substr($s['student_name'],0,1)) ?>
                                </div>
                                <div style="min-width:0">
                                    <div class="fw-semibold text-dark" style="font-size:13.5px;line-height:1.3">
                                        <?= htmlspecialchars($s['student_name']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:11.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px">
                                        <?= htmlspecialchars($s['email']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Department (hidden on md) -->
                        <td class="col-hide-md">
                            <?php if ($s['department']): ?>
                                <span class="pill" style="background:#ede9fe;color:#5b21b6">
                                    <?= htmlspecialchars($s['department']) ?>
                                </span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>

                        <!-- Gender -->
                        <td>
                            <?php if ($s['gender']): ?>
                                <span class="pill"
                                      style="background:<?= $isMale?'#dbeafe':'#fce7f3' ?>;color:<?= $isMale?'#1d4ed8':'#9d174d' ?>">
                                    <i class="bi bi-gender-<?= strtolower($s['gender']) ?>"></i>
                                    <?= htmlspecialchars($s['gender']) ?>
                                </span>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>

                        <!-- Phone (hidden on md) -->
                        <td class="text-muted col-hide-md" style="font-size:13px">
                            <?= htmlspecialchars($s['phone'] ?: '—') ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <span class="pill"
                                  style="background:<?= $isActive?'#d1fae5':'#f1f5f9' ?>;color:<?= $isActive?'#065f46':'#64748b' ?>">
                                <i class="bi <?= $isActive?'bi-check-circle-fill':'bi-dash-circle-fill' ?>"></i>
                                <?= htmlspecialchars($s['status'] ?? 'Active') ?>
                            </span>
                        </td>

                        <!-- Enrolled On (hidden on md) -->
                        <td class="text-muted col-hide-md" style="font-size:12px;white-space:nowrap">
                            <i class="bi bi-calendar2 me-1 text-primary" style="opacity:.6"></i>
                            <?= date('d M Y', strtotime($s['assigned_at'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ════ MOBILE CARD LIST (< md) ════ -->
        <div class="roster-card-view" id="rosterBodyMobile">
            <?php foreach ($students as $i => $s):
                $isMale   = strtolower($s['gender'] ?? '') === 'male';
                $isActive = ($s['status'] ?? '') === 'Active';
            ?>
            <div class="student-mob-card roster-row"
                 data-name="<?= strtolower(htmlspecialchars($s['student_name'])) ?> <?= strtolower(htmlspecialchars($s['email'])) ?>">
                <div class="student-mob-avatar">
                    <?= strtoupper(substr($s['student_name'],0,1)) ?>
                </div>
                <div class="student-mob-info">
                    <div class="student-mob-name"><?= htmlspecialchars($s['student_name']) ?></div>
                    <div class="student-mob-sub"><?= htmlspecialchars($s['email']) ?></div>
                    <div class="student-mob-badges">
                        <?php if ($s['department']): ?>
                            <span class="pill" style="background:#ede9fe;color:#5b21b6">
                                <?= htmlspecialchars($s['department']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($s['gender']): ?>
                            <span class="pill"
                                  style="background:<?= $isMale?'#dbeafe':'#fce7f3' ?>;color:<?= $isMale?'#1d4ed8':'#9d174d' ?>">
                                <i class="bi bi-gender-<?= strtolower($s['gender']) ?>"></i>
                                <?= htmlspecialchars($s['gender']) ?>
                            </span>
                        <?php endif; ?>
                        <span class="pill"
                              style="background:<?= $isActive?'#d1fae5':'#f1f5f9' ?>;color:<?= $isActive?'#065f46':'#64748b' ?>">
                            <i class="bi <?= $isActive?'bi-check-circle-fill':'bi-dash-circle-fill' ?>"></i>
                            <?= htmlspecialchars($s['status'] ?? 'Active') ?>
                        </span>
                    </div>
                </div>
                <div class="text-muted text-end" style="font-size:11px;flex-shrink:0">
                    <i class="bi bi-calendar2 d-block text-primary mb-1"></i>
                    <?= date('d M Y', strtotime($s['assigned_at'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer -->
        <div class="px-3 px-md-4 py-3 border-top d-flex align-items-center roster-footer"
             style="background:#f8fafc;font-size:12.5px;justify-content:space-between">
            <span class="text-muted">
                <i class="bi bi-people me-1 text-primary"></i>
                <strong><?= $total_enrolled ?></strong> student(s) enrolled
            </span>
            <a href="assign.php?class_id=<?= $class_id ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-person-plus me-1"></i>Add / Remove Students
            </a>
        </div>

        <?php endif; ?>
    </div>

</div>
</div>
<?php include '../includes/footer.php'; ?>
</div>

<script>
// Search across BOTH desktop table rows AND mobile cards
const rosterSearch = document.getElementById('rosterSearch');
if (rosterSearch) {
    rosterSearch.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('.roster-row').forEach(row => {
            const match = !q || (row.dataset.name || '').includes(q);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        // Deduplicate count (desktop + mobile render same data, so halve)
        const allRows = document.querySelectorAll('.roster-row').length;
        const desktopRows = document.querySelectorAll('#rosterBodyDesktop .roster-row').length;
        const mobileRows  = document.querySelectorAll('#rosterBodyMobile .roster-row').length;
        // Count only desktop rows if both exist, else whichever is visible
        let count = 0;
        if (desktopRows > 0) {
            document.querySelectorAll('#rosterBodyDesktop .roster-row').forEach(r => {
                if (r.style.display !== 'none') count++;
            });
        } else {
            document.querySelectorAll('#rosterBodyMobile .roster-row').forEach(r => {
                if (r.style.display !== 'none') count++;
            });
        }
        const el = document.getElementById('visibleCount');
        if (el) el.textContent = count;
    });
}
</script>
