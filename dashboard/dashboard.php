<?php
session_start();
require_once '../includes/auth.php';
require_login();
require_once '../config/db.php';

$page_title = 'Dashboard';
$user_role  = $_SESSION['role'];

$total_students    = $mysqli->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
$active_students   = $mysqli->query("SELECT COUNT(*) FROM students WHERE status='Active'")->fetch_row()[0];
$inactive_students = $mysqli->query("SELECT COUNT(*) FROM students WHERE status='Inactive'")->fetch_row()[0];
$total_subjects    = $mysqli->query("SELECT COUNT(*) FROM subjects WHERE status='Active'")->fetch_row()[0];
$marks_entered     = $mysqli->query("SELECT COUNT(DISTINCT student_id) FROM marks")->fetch_row()[0];
$total_staff       = $mysqli->query("SELECT COUNT(*) FROM users WHERE role='staff'")->fetch_row()[0];
$total_users       = $mysqli->query("SELECT COUNT(*) FROM users")->fetch_row()[0];

$top3_res = $mysqli->query(
    "SELECT s.id, s.student_name, s.department,
            SUM(m.marks_obtained) AS total,
            ROUND(SUM(m.marks_obtained)/(COUNT(DISTINCT m.subject_id)*100)*100,1) AS pct
     FROM students s JOIN marks m ON m.student_id=s.id
     WHERE s.status='Active'
     GROUP BY s.id ORDER BY total DESC LIMIT 3"
);
$top3 = $top3_res ? $top3_res->fetch_all(MYSQLI_ASSOC) : [];

$dept_res = $mysqli->query("SELECT department, COUNT(*) AS cnt FROM students WHERE status='Active' GROUP BY department");
$dept_labels=[]; $dept_data=[];
while($d=$dept_res->fetch_assoc()){ $dept_labels[]=$d['department']; $dept_data[]=(int)$d['cnt']; }

$subavg_res = $mysqli->query("SELECT sub.subject_name, COALESCE(ROUND(AVG(m.marks_obtained),1),0) AS avg_marks FROM subjects sub LEFT JOIN marks m ON m.subject_id=sub.id WHERE sub.status='Active' GROUP BY sub.id");
$sub_labels=[]; $sub_avgs=[];
while($sa=$subavg_res->fetch_assoc()){ $sub_labels[]=$sa['subject_name']; $sub_avgs[]=(float)$sa['avg_marks']; }

$recent_res = $mysqli->query("SELECT * FROM students ORDER BY id DESC LIMIT 5");

$my_marks=[]; $my_total=0; $my_pct=0; $my_grade='';
if ($user_role==='student') {
    $sid = $_SESSION['student_id'] ?? null;
    if (!$sid) {
        $email = $mysqli->real_escape_string($_SESSION['email']??'');
        $sres  = $mysqli->query("SELECT id FROM students WHERE email='$email' LIMIT 1");
        if ($sres && $sres->num_rows>0) $sid=$sres->fetch_assoc()['id'];
    }
    if ($sid) {
        $mres = $mysqli->query("SELECT sub.subject_name, m.marks_obtained FROM marks m JOIN subjects sub ON sub.id=m.subject_id WHERE m.student_id=$sid");
        while($mr=$mres->fetch_assoc()){ $my_marks[]=$mr; $my_total+=$mr['marks_obtained']; }
        $count   = count($my_marks);
        $my_pct  = $count>0 ? round($my_total/($count*100)*100,1) : 0;
        $my_grade= $my_pct>=90?'A+':($my_pct>=80?'A':($my_pct>=70?'B':($my_pct>=60?'C':($my_pct>=50?'D':'F'))));
    }
}

require '../includes/header.php';
require '../includes/sidebar.php';
?>

<style>
/* ── Entrance animations ── */
@keyframes fadeSlideUp {
    from { opacity:0; transform:translateY(24px); }
    to   { opacity:1; transform:translateY(0); }
}
@keyframes fadeSlideLeft {
    from { opacity:0; transform:translateX(-20px); }
    to   { opacity:1; transform:translateX(0); }
}
@keyframes fadeIn {
    from { opacity:0; }
    to   { opacity:1; }
}
@keyframes countUp {
    from { opacity:0; transform:scale(0.8); }
    to   { opacity:1; transform:scale(1); }
}

/* Page header */
.dash-header {
    animation: fadeSlideLeft 0.45s ease both;
}

/* Stat cards — staggered */
.stat-anim {
    opacity:0;
    animation: fadeSlideUp 0.5s ease forwards;
}
.stat-anim:nth-child(1){ animation-delay:.05s }
.stat-anim:nth-child(2){ animation-delay:.12s }
.stat-anim:nth-child(3){ animation-delay:.19s }
.stat-anim:nth-child(4){ animation-delay:.26s }

/* Stat value pop */
.stat-value-anim {
    animation: countUp 0.5s ease both;
    animation-delay: .35s;
}

/* Cards that fade in when visible */
.card-anim {
    opacity:0;
    transform:translateY(20px);
    transition: opacity 0.55s ease, transform 0.55s ease;
}
.card-anim.visible {
    opacity:1;
    transform:translateY(0);
}

/* Chart cards */
.chart-anim {
    opacity:0;
    transform:translateY(20px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}
.chart-anim.visible { opacity:1; transform:translateY(0); }
.chart-anim:nth-child(2){ transition-delay:.1s }
.chart-anim:nth-child(3){ transition-delay:.2s }

/* Table rows */
.table-row-anim {
    opacity:0;
    transform:translateX(-10px);
    transition: opacity 0.4s ease, transform 0.4s ease;
}
.table-row-anim.visible { opacity:1; transform:translateX(0); }

/* Top 3 cards */
.rank-anim {
    opacity:0;
    transform:translateX(-16px);
    transition: opacity 0.45s ease, transform 0.45s ease;
}
.rank-anim.visible { opacity:1; transform:translateX(0); }
.rank-anim:nth-child(2){ transition-delay:.08s }
.rank-anim:nth-child(3){ transition-delay:.16s }

/* Card hover lift */
.card { transition: box-shadow .25s ease, transform .25s ease; }
.card:hover { box-shadow:0 6px 24px rgba(0,0,0,.10) !important; transform:translateY(-2px); }
</style>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

<?php if ($user_role==='student'): ?>
<!-- ══ STUDENT DASHBOARD ══ -->

<div class="content-header mb-4 dash-header">
    <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <p class="text-muted mb-0" style="font-size:13px">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</p>
</div>

<!-- Student stat cards -->
<div class="row g-3 mb-4">
    <?php
    $scards = [
        ['bi-journal-check','var(--primary-color)', "Total Marks",  "$my_total / ".count($my_marks)*100],
        ['bi-percent',       'var(--primary-color)', "Percentage",   "$my_pct%"],
        ['bi-award-fill',    'var(--secondary-color)','Grade',       $my_grade?:'—'],
        ['bi-book-fill',     'var(--secondary-color)','Subjects',    count($my_marks)],
    ];
    foreach($scards as [$ic,$cl,$lbl,$val]):
    ?>
    <div class="col-sm-6 col-xl-3 stat-anim">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:rgba(0,191,165,.1);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi <?= $ic ?>" style="color:<?= $cl ?>"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px"><?= $lbl ?></div>
                    <div class="fw-bold fs-4 stat-value-anim"><?= $val ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Subject marks table -->
<div class="card card-anim">
    <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-1"></i> My Subject Marks</h5>
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
                <?php foreach($my_marks as $mm):
                    $mpct=round($mm['marks_obtained']/100*100,1);
                    $mg=$mpct>=90?'A+':($mpct>=80?'A':($mpct>=70?'B':($mpct>=60?'C':($mpct>=50?'D':'F'))));
                    $mc=$mg=='A+'||$mg=='A'?'success':($mg=='B'?'info':($mg=='C'?'warning':'danger'));
                ?>
                <tr class="table-row-anim">
                    <td><?= htmlspecialchars($mm['subject_name']) ?></td>
                    <td><strong><?= $mm['marks_obtained'] ?></strong></td>
                    <td>100</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="progress flex-grow-1" style="height:6px">
                                <div class="progress-bar bg-<?= $mc ?>" style="width:0%" data-width="<?= $mpct ?>%"></div>
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
<!-- ══ ADMIN / STAFF DASHBOARD ══ -->

<div class="content-header mb-4 dash-header">
    <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <p class="text-muted mb-0" style="font-size:13px">Welcome, <?= htmlspecialchars($_SESSION['username']) ?> 👋</p>
</div>

<!-- Stat cards row 1 -->
<div class="row g-3 mb-3">
    <?php
    $cards1 = [
        ['bi-mortarboard-fill', 'var(--primary-color)',   'rgba(0,191,165,.1)',   'Total Students',    $total_students],
        ['bi-person-check-fill','var(--primary-color)',   'rgba(0,191,165,.1)',   'Active Students',   $active_students],
        ['bi-person-x-fill',    '#D32F2F',                'rgba(211,47,47,.1)',   'Inactive Students', $inactive_students],
        ['bi-journal-check',    'var(--secondary-color)', 'rgba(25,118,210,.1)',  'Marks Entered',     $marks_entered],
    ];
    foreach($cards1 as [$ic,$cl,$bg,$lbl,$val]):
    ?>
    <div class="col-sm-6 col-xl-3 stat-anim">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi <?= $ic ?>" style="color:<?= $cl ?>"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px"><?= $lbl ?></div>
                    <div class="fw-bold stat-value-anim" style="font-size:26px;line-height:1;color:<?= $cl ?>"><?= $val ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Stat cards row 2 -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3 stat-anim">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:rgba(25,118,210,.1);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-book-fill" style="color:var(--secondary-color)"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Active Subjects</div>
                    <div class="fw-bold stat-value-anim" style="font-size:26px;line-height:1;color:var(--secondary-color)"><?= $total_subjects ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php if ($user_role==='admin'): ?>
    <div class="col-sm-6 col-xl-3 stat-anim">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:rgba(0,191,165,.1);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-people-fill" style="color:var(--primary-light)"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Total Staff</div>
                    <div class="fw-bold stat-value-anim" style="font-size:26px;line-height:1;color:var(--primary-light)"><?= $total_staff ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3 stat-anim">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div style="width:50px;height:50px;border-radius:12px;background:rgba(0,191,165,.1);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">
                    <i class="bi bi-shield-lock-fill" style="color:var(--primary-dark)"></i>
                </div>
                <div>
                    <div class="text-muted" style="font-size:12px">Total Users</div>
                    <div class="fw-bold stat-value-anim" style="font-size:26px;line-height:1;color:var(--primary-dark)"><?= $total_users ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Top 3 + Charts -->
<div class="row g-3 mb-4">

    <div class="col-lg-4 chart-anim">
        <div class="card h-100">
            <div class="card-header" style="font-size:14px">
                <i class="bi bi-trophy-fill me-1" style="color:#F57C00"></i> Top 3 Students
            </div>
            <div class="card-body">
                <?php if (empty($top3)): ?>
                    <div class="text-center text-muted py-4"><i class="bi bi-inbox fs-2 d-block mb-2"></i>No marks entered yet</div>
                <?php else: ?>
                    <?php
                    $medals=['🥇','🥈','🥉'];
                    $pbg=['rgba(245,124,0,.1)','rgba(0,0,0,.04)','rgba(0,191,165,.1)'];
                    $ptxt=['#F57C00','#424242','#00897B'];
                    foreach($top3 as $i=>$t):
                    ?>
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2 rank-anim"
                         style="background:<?= $pbg[$i] ?>">
                        <div style="font-size:28px;line-height:1"><?= $medals[$i] ?></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-truncate" style="font-size:14px"><?= htmlspecialchars($t['student_name']) ?></div>
                            <div style="font-size:12px;color:#6b7280"><?= htmlspecialchars($t['department']) ?></div>
                            <div class="progress mt-1" style="height:4px">
                                <div class="progress-bar" style="width:0%" data-width="<?= $t['pct'] ?>%" background="<?= $ptxt[$i] ?>"></div>
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

    <div class="col-lg-4 chart-anim">
        <div class="card h-100">
            <div class="card-header bg-white border-bottom fw-semibold" style="font-size:14px">
                <i class="bi bi-pie-chart-fill text-primary me-1"></i> Students by Department
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px">
                <?= empty($dept_data) ? '<p class="text-muted">No data yet</p>' : '<canvas id="deptChart" style="max-height:200px"></canvas>' ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4 chart-anim">
        <div class="card h-100">
            <div class="card-header bg-white border-bottom fw-semibold" style="font-size:14px">
                <i class="bi bi-bar-chart-fill text-success me-1"></i> Subject Avg Marks
            </div>
            <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px">
                <?= empty($sub_avgs) ? '<p class="text-muted">No data yet</p>' : '<canvas id="subChart" style="max-height:200px"></canvas>' ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Students -->
<div class="card card-anim">
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
                <?php while($r=$recent_res->fetch_assoc()): ?>
                <tr class="table-row-anim">
                    <td><strong><?= htmlspecialchars($r['student_name']) ?></strong></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td><?= htmlspecialchars($r['department']) ?></td>
                    <td><?= $r['gender'] ?></td>
                    <td><?= $r['status']==='Active'?"<span class='badge bg-success'>Active</span>":"<span class='badge bg-danger'>Inactive</span>" ?></td>
                    <td><a href="../students/edit.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm py-0 px-2"><i class="bi bi-pencil"></i></a></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

</div>
</div><!-- /#main-content -->
<?php require '../includes/footer.php'; ?>
</div><!-- /#content -->

<?php if ($user_role!=='student' && !empty($dept_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('deptChart'),{
    type:'doughnut',
    data:{ labels:<?= json_encode($dept_labels) ?>, datasets:[{ data:<?= json_encode($dept_data) ?>, backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'], borderWidth:2 }] },
    options:{ plugins:{ legend:{ position:'bottom', labels:{ font:{size:11}, boxWidth:12 } } }, cutout:'65%' }
});
new Chart(document.getElementById('subChart'),{
    type:'bar',
    data:{ labels:<?= json_encode($sub_labels) ?>, datasets:[{ label:'Avg Marks', data:<?= json_encode($sub_avgs) ?>, backgroundColor:['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6'], borderRadius:6 }] },
    options:{ plugins:{ legend:{display:false} }, scales:{ y:{beginAtZero:true,max:100,ticks:{font:{size:11}}}, x:{ticks:{font:{size:11}}} } }
});
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── IntersectionObserver: fade in cards + rows when visible ──
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.classList.add('visible');
                observer.unobserve(e.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.card-anim, .chart-anim, .table-row-anim, .rank-anim')
            .forEach(el => observer.observe(el));

    // ── Animate progress bars after cards become visible ──
    const barObserver = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                e.target.querySelectorAll('.progress-bar[data-width]').forEach(bar => {
                    setTimeout(() => {
                        bar.style.transition = 'width 0.8s ease';
                        bar.style.width = bar.dataset.width;
                        if (bar.getAttribute('background'))
                            bar.style.background = bar.getAttribute('background');
                    }, 200);
                });
                barObserver.unobserve(e.target);
            }
        });
    }, { threshold: 0.2 });

    document.querySelectorAll('.card, .rank-anim').forEach(el => barObserver.observe(el));

    // ── Immediately animate bars already in view on load ──
    setTimeout(() => {
        document.querySelectorAll('.progress-bar[data-width]').forEach(bar => {
            bar.style.transition = 'width 0.8s ease';
            bar.style.width = bar.dataset.width;
            if (bar.getAttribute('background'))
                bar.style.background = bar.getAttribute('background');
        });
    }, 400);
});
</script>