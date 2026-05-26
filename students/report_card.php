<?php
session_start();
require_once '../includes/auth.php';
require_login();
require_once '../config/db.php';

if (!isset($_GET['student_id']) || !is_numeric($_GET['student_id'])) {
    header("Location: students.php"); exit();
}

$sid       = (int)$_GET['student_id'];
$student   = $mysqli->query("SELECT * FROM students WHERE id=$sid LIMIT 1")->fetch_assoc();
if (!$student) { header("Location: students.php"); exit(); }

$institute = $mysqli->query("SELECT * FROM institute_profile LIMIT 1")->fetch_assoc() ?? [];

$mres = $mysqli->query(
    "SELECT m.marks_obtained, m.total_marks, sub.subject_name, sub.subject_code
     FROM marks m JOIN subjects sub ON sub.id=m.subject_id
     WHERE m.student_id=$sid ORDER BY sub.subject_code ASC"
);
$marks_list = $mres ? $mres->fetch_all(MYSQLI_ASSOC) : [];

// Totals
$total_obtained = array_sum(array_column($marks_list, 'marks_obtained'));
$total_max      = array_sum(array_column($marks_list, 'total_marks'));
$percentage     = $total_max > 0 ? round($total_obtained / $total_max * 100, 2) : 0;
$pass           = empty(array_filter($marks_list, fn($m) => $m['marks_obtained'] < $m['total_marks'] * 0.35));

// Grade
function grade($pct) {
    if ($pct>=90) return ['A+','Outstanding'];
    if ($pct>=80) return ['A','Excellent'];
    if ($pct>=70) return ['B','Very Good'];
    if ($pct>=60) return ['C','Good'];
    if ($pct>=50) return ['D','Average'];
    return ['F','Fail'];
}
function subGrade($o,$m) { return grade($m>0?$o/$m*100:0)[0]; }
function gradeStyle($g) {
    return ['A+'=>'#d1fae5;color:#065f46','A'=>'#dbeafe;color:#1e40af',
            'B'=>'#e0e7ff;color:#3730a3','C'=>'#fef3c7;color:#92400e',
            'D'=>'#ffedd5;color:#9a3412','F'=>'#fee2e2;color:#991b1b'][$g] ?? '#f3f4f6;color:#374151';
}

[$grade, $grade_desc] = grade($percentage);
$pass_fail = ($pass && $grade!=='F') ? 'PASS' : 'FAIL';

// Rank
$above     = $mysqli->query("SELECT COUNT(DISTINCT student_id) AS c FROM marks GROUP BY student_id HAVING SUM(marks_obtained)>$total_obtained")->fetch_all();
$rank      = count($above) + 1;
$total_rnk = $mysqli->query("SELECT COUNT(DISTINCT student_id) FROM marks")->fetch_row()[0];
$medals    = [1=>'🥇',2=>'🥈',3=>'🥉'];
$medal     = $medals[$rank] ?? '🎓';
$acad_year = date('Y').'-'.(date('Y')+1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Report Card — <?= htmlspecialchars($student['student_name']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
body { background:#f0f2f5; font-family:'Segoe UI',sans-serif; margin:0; }

/* Action bar */
.action-bar {
    background:#212529; padding:12px 24px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:100;
}
.action-bar a { color:#fff; font-size:14px; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:8px; }
.action-bar a i { color:#0d6efd; font-size:18px; }

/* Card */
.report-wrap { max-width:800px; margin:32px auto; padding:0 16px 48px; }
.report-card { background:#fff; border-radius:12px; box-shadow:0 4px 24px rgba(0,0,0,.10); overflow:hidden; position:relative; }

/* Header */
.rc-header {
    background:#212529; padding:28px 32px;
    display:flex; align-items:center; gap:24px; position:relative; overflow:hidden;
}
.rc-header::before {
    content:''; position:absolute; inset:0; opacity:.05;
    background-image:repeating-linear-gradient(45deg,#fff 0,#fff 1px,transparent 0,transparent 50%);
    background-size:12px 12px;
}
.rc-logo {
    width:80px; height:80px; border-radius:12px; flex-shrink:0;
    border:3px solid rgba(255,255,255,.2); overflow:hidden;
    background:#fff; display:flex; align-items:center; justify-content:center;
}
.rc-logo img { width:100%; height:100%; object-fit:cover; }
.rc-logo i   { font-size:36px; color:#212529; }
.rc-institute { flex:1; position:relative; z-index:1; }
.rc-institute h2 { color:#fff; font-size:20px; font-weight:700; margin:0 0 4px; }
.rc-institute p  { color:#adb5bd; font-size:12px; margin:0; line-height:1.6; }
.rc-badge { position:relative; z-index:1; text-align:center; flex-shrink:0; }
.rc-badge span {
    background:#0d6efd; color:#fff; font-size:11px; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase;
    padding:6px 14px; border-radius:20px; display:block; margin-bottom:6px;
}
.rc-badge small { color:#6c757d; font-size:11px; }
.rc-badge strong { color:#fff; font-size:13px; display:block; }

.rc-divider { height:5px; background:linear-gradient(90deg,#0d6efd,#6610f2,#0dcaf0); }

/* Sections */
.rc-section { padding:24px 32px; border-bottom:1px solid #f0f0f0; }
.rc-section h5 {
    font-size:12px; font-weight:700; text-transform:uppercase;
    letter-spacing:.1em; color:#6c757d; margin-bottom:16px;
}

/* Info grid */
.info-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
.info-item .lbl { font-size:11px; color:#9ca3af; font-weight:600; text-transform:uppercase; letter-spacing:.06em; margin-bottom:2px; }
.info-item .val { font-size:14px; font-weight:600; color:#1a2332; }

/* Marks table */
.marks-table { width:100%; border-collapse:collapse; font-size:13.5px; }
.marks-table thead tr { background:#212529; color:#fff; }
.marks-table thead th { padding:10px 14px; font-weight:600; font-size:12px; }
.marks-table tbody tr { border-bottom:1px solid #f0f2f5; }
.marks-table tbody tr:nth-child(even) { background:#f8f9fa; }
.marks-table tbody td { padding:10px 14px; }
.marks-table tfoot tr { background:#f0f2f5; font-weight:700; }
.marks-table tfoot td { padding:10px 14px; font-size:13px; }
.mini-bar { height:5px; border-radius:3px; background:#e9ecef; margin-top:4px; }
.mini-fill { height:100%; border-radius:3px; }

/* Result grid */
.result-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
.result-box { text-align:center; padding:16px 12px; border-radius:10px; background:#f8f9fa; }
.result-box .rb-lbl { font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:.08em; font-weight:600; margin-bottom:8px; }
.result-box .rb-val { font-size:26px; font-weight:800; line-height:1; margin-bottom:4px; }
.result-box .rb-sub { font-size:11px; color:#9ca3af; }
.pass-badge { display:inline-block; padding:6px 20px; border-radius:30px; font-size:15px; font-weight:800; }
.pass-badge.pass { background:#d1fae5; color:#065f46; }
.pass-badge.fail { background:#fee2e2; color:#991b1b; }

/* Rank */
.rc-rank { padding:16px 32px; background:#f8f9fa; border-bottom:1px solid #f0f0f0; display:flex; align-items:center; gap:16px; }

/* Remarks */
.remarks-box { background:#f8f9fa; border-radius:8px; border-left:4px solid #0d6efd; padding:12px 16px; font-size:13px; color:#374151; }

/* Footer */
.rc-footer { padding:20px 32px; display:flex; justify-content:space-between; align-items:flex-end; }
.sig-box { text-align:center; min-width:140px; }
.sig-line { border-top:1.5px solid #212529; margin-bottom:4px; }
.sig-lbl { font-size:11px; color:#6c757d; }
.stamp-circle {
    width:72px; height:72px; border-radius:50%; border:2.5px dashed #0d6efd;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; color:#0d6efd; font-weight:700; text-transform:uppercase;
    letter-spacing:.05em; text-align:center; line-height:1.3; margin:0 auto 4px;
}
.rc-strip { background:#212529; padding:10px 32px; display:flex; justify-content:space-between; }
.rc-strip span { color:#6c757d; font-size:11px; }

/* Watermark */
.watermark {
    position:absolute; top:50%; left:50%;
    transform:translate(-50%,-50%) rotate(-30deg);
    font-size:100px; font-weight:900; color:rgba(220,53,69,.06);
    pointer-events:none; white-space:nowrap; z-index:0;
}

@media print {
    .action-bar { display:none !important; }
    body { background:#fff; }
    .report-wrap { margin:0; padding:0; max-width:100%; }
    .report-card { box-shadow:none; border-radius:0; }
    @page { margin:10mm; size:A4; }
}
</style>
</head>
<body>

<div class="action-bar">
    <a href="students.php"><i class="bi bi-arrow-left-circle-fill"></i> Back to Students</a>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-light btn-sm">
            <i class="bi bi-printer me-1"></i> Print
        </button>
        <button onclick="downloadPDF()" class="btn btn-primary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </button>
    </div>
</div>

<div class="report-wrap">
<div class="report-card">

    <?php if ($pass_fail==='FAIL'): ?><div class="watermark">FAIL</div><?php endif; ?>

    <!-- Header -->
    <div class="rc-header">
        <div class="rc-logo">
            <?php
            $lp = '../uploads/institute/'.($institute['logo']??'');
            if (!empty($institute['logo']) && file_exists($lp)):
            ?>
                <img src="<?= $lp ?>" alt="Logo">
            <?php else: ?>
                <i class="bi bi-building"></i>
            <?php endif; ?>
        </div>
        <div class="rc-institute">
            <h2><?= htmlspecialchars($institute['institute_name']??'My Institute') ?></h2>
            <p>
                <?php if (!empty($institute['address'])): ?>
                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($institute['address']) ?><br>
                <?php endif; ?>
                <?php if (!empty($institute['phone'])): ?>
                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($institute['phone']) ?>&nbsp;&nbsp;
                <?php endif; ?>
                <?php if (!empty($institute['email'])): ?>
                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($institute['email']) ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="rc-badge">
            <span>Report Card</span>
            <small>Academic Year</small>
            <strong><?= $acad_year ?></strong>
        </div>
    </div>

    <div class="rc-divider"></div>

    <!-- Student info -->
    <div class="rc-section">
        <h5><i class="bi bi-person-fill me-1"></i>Student Information</h5>
        <div class="info-grid">
            <?php
            $fields = [
                'Student Name' => $student['student_name'],
                'Student ID'   => '#'.str_pad($student['id'],4,'0',STR_PAD_LEFT),
                'Department'   => $student['department'] ?? 'N/A',
                'Gender'       => $student['gender']     ?? 'N/A',
                'Date of Birth'=> !empty($student['dob']) ? date('d M Y',strtotime($student['dob'])) : 'N/A',
                'Email'        => $student['email']       ?? 'N/A',
            ];
            foreach ($fields as $lbl => $val):
            ?>
            <div class="info-item">
                <div class="lbl"><?= $lbl ?></div>
                <div class="val" style="<?= $lbl==='Email'?'font-size:12px':'' ?>"><?= htmlspecialchars($val) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Marks -->
    <div class="rc-section">
        <h5><i class="bi bi-journal-check me-1"></i>Marks Sheet</h5>
        <?php if (empty($marks_list)): ?>
            <div class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>No marks found.
            </div>
        <?php else: ?>
        <table class="marks-table">
            <thead>
                <tr>
                    <th>#</th><th>Code</th><th>Subject</th>
                    <th>Max</th><th>Obtained</th><th>Progress</th>
                    <th>Grade</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($marks_list as $i => $m):
                $sp  = round($m['marks_obtained']/$m['total_marks']*100,1);
                $sg  = subGrade($m['marks_obtained'],$m['total_marks']);
                $bc  = $sp>=75?'#198754':($sp>=50?'#ffc107':'#dc3545');
                $gs  = gradeStyle($sg);
            ?>
            <tr>
                <td class="text-muted"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($m['subject_code']) ?></strong></td>
                <td><?= htmlspecialchars($m['subject_name']) ?></td>
                <td><?= $m['total_marks'] ?></td>
                <td><strong><?= $m['marks_obtained'] ?></strong></td>
                <td style="min-width:80px">
                    <div style="font-size:11px;color:#6c757d"><?= $sp ?>%</div>
                    <div class="mini-bar"><div class="mini-fill" style="width:<?= $sp ?>%;background:<?= $bc ?>"></div></div>
                </td>
                <td><span style="background:<?= $gs ?>;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700"><?= $sg ?></span></td>
                <td>
                    <?php if ($m['marks_obtained']>=$m['total_marks']*0.35): ?>
                        <span class="text-success fw-semibold" style="font-size:12px"><i class="bi bi-check-circle-fill me-1"></i>Pass</span>
                    <?php else: ?>
                        <span class="text-danger fw-semibold" style="font-size:12px"><i class="bi bi-x-circle-fill me-1"></i>Fail</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total</strong></td>
                    <td><strong><?= $total_max ?></strong></td>
                    <td><strong><?= $total_obtained ?></strong></td>
                    <td style="font-size:12px;color:#6c757d"><?= $percentage ?>%</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        <?php endif; ?>
    </div>

    <!-- Result summary -->
    <div class="rc-section">
        <div class="result-grid">
            <div class="result-box">
                <div class="rb-lbl">Total Marks</div>
                <div class="rb-val text-primary"><?= $total_obtained ?></div>
                <div class="rb-sub">out of <?= $total_max ?></div>
            </div>
            <div class="result-box">
                <div class="rb-lbl">Percentage</div>
                <div class="rb-val" style="color:#0891b2"><?= $percentage ?>%</div>
                <div class="rb-sub"><?= $grade_desc ?></div>
            </div>
            <div class="result-box">
                <div class="rb-lbl">Grade</div>
                <div class="rb-val" style="color:#7c3aed"><?= $grade ?></div>
                <div class="rb-sub"><?= $grade_desc ?></div>
            </div>
            <div class="result-box">
                <div class="rb-lbl">Result</div>
                <div class="rb-val" style="font-size:18px;margin-top:4px">
                    <span class="pass-badge <?= strtolower($pass_fail) ?>"><?= $pass_fail ?></span>
                </div>
                <div class="rb-sub mt-1">Overall Result</div>
            </div>
        </div>
    </div>

    <!-- Rank -->
    <?php if (!empty($marks_list)): ?>
    <div class="rc-rank">
        <div style="font-size:32px"><?= $medal ?></div>
        <div>
            <strong style="font-size:18px;color:#1a2332">Rank <?= $rank ?></strong>
            <span style="font-size:13px;color:#374151"> out of <?= $total_rnk ?> students</span>
            <div style="font-size:12px;color:#6b7280;margin-top:2px">Based on total marks across all subjects</div>
        </div>
        <div class="ms-auto text-end">
            <div style="font-size:11px;color:#9ca3af;text-transform:uppercase">Class Rank</div>
            <div style="font-size:32px;font-weight:800;color:#1a2332;line-height:1"><?= $rank ?></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Remarks -->
    <div class="rc-section">
        <h5><i class="bi bi-chat-square-text me-1"></i>Remarks</h5>
        <div class="remarks-box">
            <?php
            $remarks = [
                90 => "Outstanding performance! Keep up the excellent work.",
                80 => "Excellent performance. Shows great dedication and effort.",
                70 => "Very good performance. Continue to work hard and improve.",
                60 => "Good performance. There is room for improvement in some areas.",
                50 => "Average performance. Needs to put in more effort and focus.",
                0  => empty($marks_list) ? "No marks have been recorded for this student yet."
                                         : "Below average performance. Requires additional support.",
            ];
            foreach ($remarks as $threshold => $text) {
                if ($percentage >= $threshold) { echo $text; break; }
            }
            ?>
        </div>
    </div>

    <!-- Signatures -->
    <div class="rc-footer">
        <div class="sig-box">
            <div style="height:36px"></div>
            <div class="sig-line"></div>
            <div class="sig-lbl">Class Teacher</div>
        </div>
        <div class="text-center">
            <div class="stamp-circle"><?= htmlspecialchars(strtoupper(substr($institute['institute_name']??'Institute',0,11))) ?></div>
            <div style="font-size:11px;color:#9ca3af">Official Seal</div>
        </div>
        <div class="sig-box">
            <div style="height:36px">
                <?php if (!empty($institute['principal_name'])): ?>
                    <div style="font-size:13px;font-weight:600;color:#374151"><?= htmlspecialchars($institute['principal_name']) ?></div>
                <?php endif; ?>
            </div>
            <div class="sig-line"></div>
            <div class="sig-lbl">Principal</div>
        </div>
    </div>

    <!-- Bottom strip -->
    <div class="rc-strip">
        <span>Generated on <?= date('d M Y, h:i A') ?></span>
        <span><?= htmlspecialchars($institute['institute_name']??'Student System') ?> &mdash; Student Management System</span>
    </div>

</div><!-- /report-card -->
</div><!-- /report-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function downloadPDF() {
    const bar = document.querySelector('.action-bar');
    bar.style.display = 'none';
    window.print();
    bar.style.display = 'flex';
}
</script>
</body>
</html>