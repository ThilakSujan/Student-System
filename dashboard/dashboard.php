<?php
session_start();
require_once '../includes/auth.php';
require_login();
require_once '../config/db.php';

$page_title  = 'Dashboard';
$user_role   = $_SESSION['role'];

// ── Stat counts ──────────────────────────────────────────
$total_students   = $mysqli->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$active_students  = $mysqli->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetch_row()[0];
$inactive_students= $mysqli->query("SELECT COUNT(*) FROM students WHERE status='Inactive'")->fetch_row()[0];
$total_subjects   = $mysqli->query("SELECT COUNT(*) FROM subjects WHERE status='Active'")->fetch_row()[0];
$marks_entered    = $mysqli->query("SELECT COUNT(DISTINCT student_id) FROM marks")->fetch_row()[0];
$total_staff      = $mysqli->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetch_row()[0];
$total_users      = $mysqli->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

// ── Top 3 students by total marks ────────────────────────
$top3_res = $mysqli->query(
    "SELECT s.id, s.student_name, s.department,
            SUM(m.marks_obtained) AS total,
            COUNT(DISTINCT m.subject_id) AS sub_count,
            ROUND(SUM(m.marks_obtained) / (COUNT(DISTINCT m.subject_id) * 100) * 100, 1) AS pct
     FROM students s
     JOIN marks m ON m.student_id = s.id
     WHERE s.status = 'Active'
     GROUP BY s.id
     ORDER BY total DESC
     LIMIT 3"
);
$top3 = $top3_res ? $top3_res->fetch_all(MYSQLI_ASSOC) : [];

// ── Department distribution (chart) ──────────────────────
$dept_res    = $mysqli->query("SELECT department, COUNT(*) AS cnt FROM students WHERE status='Active' GROUP BY department");
$dept_labels = []; $dept_data = [];
while ($d = $dept_res->fetch_assoc()) { $dept_labels[] = $d['department']; $dept_data[] = (int)$d['cnt']; }

// ── Subject average marks (chart) ────────────────────────
$subavg_res = $mysqli->query(
    "SELECT sub.subject_name, COALESCE(ROUND(AVG(m.marks_obtained),1),0) AS avg_marks
     FROM subjects sub
     LEFT JOIN marks m ON m.subject_id = sub.id
     WHERE sub.status = 'Active'
     GROUP BY sub.id"
);
$sub_labels = []; $sub_avgs = [];
while ($sa = $subavg_res->fetch_assoc()) { $sub_labels[] = $sa['subject_name']; $sub_avgs[] = (float)$sa['avg_marks']; }

// ── Recent 5 students ────────────────────────────────────
$recent_res = $mysqli->query("SELECT * FROM students ORDER BY id DESC LIMIT 5");

// ── Student own data ─────────────────────────────────────
$my_marks = []; $my_total = 0; $my_pct = 0; $my_grade = '';
if ($user_role === 'student') {
    $email = $mysqli->real_escape_string($_SESSION['email'] ?? '');
    $sres  = $mysqli->query("SELECT id FROM students WHERE email='$email' LIMIT 1");
    if ($sres && $sres->num_rows > 0) {
        $sid   = $sres->fetch_assoc()['id'];
        $mres  = $mysqli->query(
            "SELECT sub.subject_name, m.marks_obtained
             FROM marks m JOIN subjects sub ON sub.id=m.subject_id
             WHERE m.student_id=$sid"
        );
        while ($mr = $mres->fetch_assoc()) { $my_marks[] = $mr; $my_total += $mr['marks_obtained']; }
        $count   = count($my_marks);
        $my_pct  = $count > 0 ? round($my_total / ($count * 100) * 100, 1) : 0;
        $my_grade = $my_pct>=90?'A+':($my_pct>=80?'A':($my_pct>=70?'B':($my_pct>=60?'C':($my_pct>=50?'D':'F'))));
    }
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

<?php if ($user_role === 'student'): ?>
<!-- ══════════════════════════════════════════
     STUDENT DASHBOARD
══════════════════════════════════════════ -->

<div class="mb-4">
    <h4 class="fw-bold mb-0">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h4>
    <p class="text-muted" style="font-size:13px">Here's your academic summary</p>
</div>

<!-- Student stat cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-journal-check text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Total Marks</div>
                    <div class="fw-bold fs-4"><?= $my_total ?> / <?= count($my_marks)*100 ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#d1fae5;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-percent" style="color:#059669"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Percentage</div>
                    <div class="fw-bold fs-4"><?= $my_pct ?>%</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-award-fill" style="color:#d97706"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Grade</div>
                    <div class="fw-bold fs-4"><?= $my_grade ?: '—' ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#ede9fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-book-fill" style="color:#7c3aed"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Subjects</div>
                    <div class="fw-bold fs-4"><?= count($my_marks) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Subject breakdown -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-bar-chart-fill me-1"></i> My Subject Marks
    </div>
    <div class="card-body">
        <?php if (empty($my_marks)): ?>
            <p class="text-center text-muted py-3"><i class="bi bi-inbox fs-3 d-block mb-2"></i>No marks available yet.</p>
        <?php else: ?>
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Subject</th><th>Marks</th><th>Out of</th><th>Percentage</th><th>Grade</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($my_marks as $mm):
                        $mpct = round($mm['marks_obtained'] / 100 * 100, 1);
                        $mg   = $mpct>=90?'A+':($mpct>=80?'A':($mpct>=70?'B':($mpct>=60?'C':($mpct>=50?'D':'F'))));
                        $mc   = $mg=='A+'||$mg=='A'?'success':($mg=='B'?'info':($mg=='C'?'warning':'danger'));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($mm['subject_name']) ?></td>
                        <td><strong><?= $mm['marks_obtained'] ?></strong></td>
                        <td>100</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:6px">
                                    <div class="progress-bar bg-<?= $mc ?>" style="width:<?= $mpct ?>%"></div>
                                </div>
                                <span style="font-size:13px"><?= $mpct ?>%</span>
                            </div>
                        </td>
                        <td><span class="badge bg-<?= $mc ?>"><?= $mg ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════
     ADMIN / STAFF DASHBOARD
══════════════════════════════════════════ -->

<div class="mb-4">
    <h4 class="fw-bold mb-0">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</h4>
    <p class="text-muted" style="font-size:13px">Here's what's happening in the system today</p>
</div>

<!-- Stat cards row 1 -->
<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#dbeafe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-mortarboard-fill text-primary"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Total Students</div>
                    <div class="fw-bold" style="font-size:26px;line-height:1"><?= $total_students ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#d1fae5;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-person-check-fill" style="color:#059669"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Active Students</div>
                    <div class="fw-bold text-success" style="font-size:26px;line-height:1"><?= $active_students ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#fee2e2;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-person-x-fill text-danger"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Inactive Students</div>
                    <div class="fw-bold text-danger" style="font-size:26px;line-height:1"><?= $inactive_students ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-journal-check" style="color:#d97706"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Marks Entered</div>
                    <div class="fw-bold" style="font-size:26px;line-height:1;color:#d97706"><?= $marks_entered ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stat cards row 2 -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#ede9fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-book-fill" style="color:#7c3aed"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Active Subjects</div>
                    <div class="fw-bold" style="font-size:26px;line-height:1;color:#7c3aed"><?= $total_subjects ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($user_role === 'admin'): ?>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-people-fill" style="color:#0284c7"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Total Staff</div>
                    <div class="fw-bold" style="font-size:26px;line-height:1;color:#0284c7"><?= $total_staff ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-shield-lock-fill" style="color:#16a34a"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Total Users</div>
                    <div class="fw-bold" style="font-size:26px;line-height:1;color:#16a34a"><?= $total_users ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Top 3 + Charts -->
<div class="row g-3 mb-4">

    <!-- Top 3 Rankings -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom fw-semibold" style="font-size:14px">
                <i class="bi bi-trophy-fill text-warning me-1"></i> Top 3 Students
            </div>
            <div class="card-body">
                <?php if (empty($top3)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>No marks entered yet
                    </div>
                <?php else: ?>
                    <?php
                    $medals  = ['🥇','🥈','🥉'];
                    $pbg     = ['#fef3c7','#f1f5f9','#ffedd5'];
                    $ptxt    = ['#92400e','#475569','#9a3412'];
                    foreach ($top3 as $i => $t):
                    ?>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2"
                         style="background:<?= $pbg[$i] ?>">
                        <div style="font-size:28px;line-height:1"><?= $medals[$i] ?></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-truncate" style="font-size:14px">
                                <?= htmlspecialchars($t['student_name']) ?>
                            </div>
                            <div style="font-size:12px;color:#6b7280"><?= htmlspecialchars($t['department']) ?></div>
                            <div class="progress mt-1" style="height:4px;border-radius:2px">
                                <div class="progress-bar" style="width:<?= $t['pct'] ?>%;background:<?= $ptxt[$i] ?>"></div>
                            </div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="fw-bold" style="color:<?= $ptxt[$i] ?>;font-size:15px"><?= $t['total'] ?></div>
                            <div style="font-size:11px;color:#6b7280"><?= $t['pct'] ?>%</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dept chart -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom fw-semibold" style="font-size:14px">
                <i class="bi bi-pie-chart-fill text-primary me-1"></i> Students by Department
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px">
                <?php if (empty($dept_data)): ?>
                    <p class="text-muted">No data yet</p>
                <?php else: ?>
                    <canvas id="deptChart" style="max-height:200px"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Subject avg chart -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom fw-semibold" style="font-size:14px">
                <i class="bi bi-bar-chart-fill text-success me-1"></i> Subject Avg Marks
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px">
                <?php if (empty($sub_avgs)): ?>
                    <p class="text-muted">No data yet</p>
                <?php else: ?>
                    <canvas id="subChart" style="max-height:200px"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-semibold" style="font-size:14px">
            <i class="bi bi-clock-history text-primary me-1"></i> Recently Added Students
        </span>
        <a href="../students/students.php" class="btn btn-outline-primary btn-sm">View All</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Department</th><th>Gender</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php while ($r = $recent_res->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td><?= htmlspecialchars($r['department']) ?></td>
                    <td><?= $r['gender'] ?></td>
                    <td>
                        <?= $r['status']==='Active'
                            ? "<span class='badge bg-success'>Active</span>"
                            : "<span class='badge bg-danger'>Inactive</span>" ?>
                    </td>
                    <td>
                        <a href="../students/edit.php?id=<?= $r['id'] ?>"
                           class="btn btn-warning btn-sm py-0 px-2">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

</div><!-- /container-fluid -->
</div><!-- /#main-content -->

<?php require '../includes/footer.php'; ?>

</div><!-- /#content -->

<!-- Charts (admin/staff only) -->
<?php if ($user_role !== 'student' && !empty($dept_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('deptChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($dept_labels) ?>,
        datasets: [{
            data: <?= json_encode($dept_data) ?>,
            backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'],
            borderWidth: 2
        }]
    },
    options: {
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 }, boxWidth: 12 } } },
        cutout: '65%'
    }
});

new Chart(document.getElementById('subChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($sub_labels) ?>,
        datasets: [{
            label: 'Avg Marks',
            data: <?= json_encode($sub_avgs) ?>,
            backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6'],
            borderRadius: 6
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 100, ticks: { font: { size: 11 } } },
            x: { ticks: { font: { size: 11 } } }
        }
    }
});
</script>
<?php endif; ?>