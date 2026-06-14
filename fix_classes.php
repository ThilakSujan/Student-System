<?php file_put_contents("c:\\xampp\\htdocs\\student_system\\classes\\index.php", '<?php
session_start();
require_once \'../includes/auth.php\';
require_role([\'admin\']);
require_once \'../config/db.php\';

$page_title = "Classes";

$success = \'\';
$error   = \'\';

// ── Handle delete via GET (fallback) ──────────────────
if (isset($_GET[\'delete\'])) {
    $del_id = (int)$_GET[\'delete\'];
    if ($mysqli->query("DELETE FROM classes WHERE id = $del_id")) {
        $success = "Class deleted successfully.";
    } else {
        $error = "Failed to delete class.";
    }
}

// ── Fetch all classes with enrolled count & Filters ─────
$from_date = $_GET[\'from_date\'] ?? \'\';
$to_date = $_GET[\'to_date\'] ?? \'\';
$status = $_GET[\'status\'] ?? \'\';
$academic_year = $_GET[\'academic_year\'] ?? \'\';

$where = ["1=1"];
if ($from_date) $where[] = "DATE(c.created_at) >= \'" . $mysqli->real_escape_string($from_date) . "\'";
if ($to_date) $where[] = "DATE(c.created_at) <= \'" . $mysqli->real_escape_string($to_date) . "\'";
if ($status) $where[] = "c.status = \'" . $mysqli->real_escape_string($status) . "\'";
if ($academic_year) $where[] = "c.academic_year = \'" . $mysqli->real_escape_string($academic_year) . "\'";

$result = $mysqli->query(
    "SELECT c.*, u.username AS teacher_name,
            COUNT(cs.student_id) AS student_count
     FROM classes c
     LEFT JOIN users u ON u.id = c.class_teacher_id
     LEFT JOIN class_students cs ON cs.class_id = c.id
     WHERE " . implode(\' AND \', $where) . "
     GROUP BY c.id
     ORDER BY c.created_at DESC"
);
$classes = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$ay_res = $mysqli->query("SELECT DISTINCT academic_year FROM classes WHERE academic_year IS NOT NULL AND academic_year != \'\'");
$academic_years = [];
if ($ay_res) while($r = $ay_res->fetch_assoc()) $academic_years[] = $r[\'academic_year\'];

include \'../includes/header.php\';
include \'../includes/sidebar.php\';
?>

<div id="content">
<?php include \'../includes/navbar.php\'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Class Management</h4>
            <small class="text-muted">Create and manage classes, assign class teachers</small>
        </div>
        <a href="add.php" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i> Add New Class
        </a>
    </div>

    <!-- Stats row -->
    <?php
    $total    = count($classes);
    $active   = count(array_filter($classes, fn($c) => $c[\'status\'] === \'Active\'));
    $inactive = $total - $active;
    $assigned = count(array_filter($classes, fn($c) => !empty($c[\'class_teacher_id\'])));
    ?>
    <div class="row g-3 mb-4">
        <?php foreach ([
            [\'Total Classes\',    $total,    \'bi-building\',       \'primary\', \'#dbeafe\', \'#1e40af\'],
            [\'Active\',           $active,   \'bi-check-circle\',   \'success\', \'#d1fae5\', \'#065f46\'],
            [\'Inactive\',         $inactive, \'bi-dash-circle\',    \'danger\',  \'#fee2e2\', \'#991b1b\'],
            [\'Teacher Assigned\', $assigned, \'bi-person-check\',   \'warning\', \'#fef3c7\', \'#92400e\'],
        ] as [$label, $val, $icon, $col, $bg, $fg]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        <i class="bi <?= $icon ?>" style="color:<?= $fg ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px"><?= $label ?></div>
                        <div class="fw-bold" style="font-size:26px;line-height:1"><?= $val ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Advanced Report Filters -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-funnel"></i> Report Filters
        </div>
        <div class="card-body">
            <form method="GET" action="index.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Created From</label>
                        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Created To</label>
                        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="Active" <?= $status === \'Active\' ? \'selected\' : \'\' ?>>Active</option>
                            <option value="Inactive" <?= $status === \'Inactive\' ? \'selected\' : \'\' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">Academic Year</label>
                        <select name="academic_year" class="form-select">
                            <option value="">All</option>
                            <?php foreach($academic_years as $ay): ?>
                            <option value="<?= htmlspecialchars($ay) ?>" <?= $academic_year === $ay ? \'selected\' : \'\' ?>><?= htmlspecialchars($ay) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2 w-100 mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Classes table -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-list-ul me-1"></i>All Classes</strong>
            <?php if (is_admin()): ?>
            <div class="d-flex gap-2">
                <button onclick="exportTable(\'#classesTable\', \'Classes Report\', \'excel\')" class="btn btn-success btn-sm" title="Export to Excel">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </button>
                <button onclick="exportTable(\'#classesTable\', \'Classes Report\', \'pdf\')" class="btn btn-danger btn-sm" title="Export to PDF">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <?php if (empty($classes)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted mb-2">No classes found.</p>
                    <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>Add First Class</a>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="classesTable" class="table table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Class Name</th>
                            <th>Section</th>
                            <th>Academic Year</th>
                            <th>Class Teacher</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($classes as $i => $cls): ?>
                        <tr id="row-<?= $cls[\'id\'] ?>">
                            <td class="text-muted align-middle"><?= $i + 1 ?></td>
                            <td class="align-middle"><strong><?= htmlspecialchars($cls[\'class_name\']) ?></strong></td>
                            <td class="align-middle">
                                <?php if ($cls[\'section\']): ?>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($cls[\'section\']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle"><?= htmlspecialchars($cls[\'academic_year\'] ?: \'—\') ?></td>
                            <td class="align-middle">
                                <?php if ($cls[\'teacher_name\']): ?>
                                    <span class="badge bg-info text-dark">
                                        <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($cls[\'teacher_name\']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle">
                                <a href="students.php?class_id=<?= $cls[\'id\'] ?>" class="text-decoration-none">
                                    <span class="badge bg-primary rounded-pill">
                                        <i class="bi bi-people-fill me-1"></i><?= $cls[\'student_count\'] ?>
                                    </span>
                                </a>
                            </td>
                            <td class="align-middle">
                                <?php if ($cls[\'status\'] === \'Active\'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px">
                                <?= date(\'d M Y\', strtotime($cls[\'created_at\'])) ?>
                            </td>
                            <td class="align-middle text-center" style="white-space:nowrap">
                                <a href="students.php?class_id=<?= $cls[\'id\'] ?>" class="btn btn-info btn-sm" title="View Roster">
                                    <i class="bi bi-people"></i>
                                </a>
                                <a href="assign.php?class_id=<?= $cls[\'id\'] ?>" class="btn btn-success btn-sm" title="Assign Students">
                                    <i class="bi bi-person-plus"></i>
                                </a>
                                <a href="edit.php?id=<?= $cls[\'id\'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-danger btn-sm" title="Delete"
                                        onclick="deleteClass(<?= $cls[\'id\'] ?>, \'<?= htmlspecialchars(addslashes($cls[\'class_name\'])) ?>\')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /container-fluid -->
</div><!-- /#main-content -->

<?php
if (!empty($success)) echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType=\'success\';</script>";
if (!empty($error))   echo "<script>window._toastMsg=" . json_encode($error)   . ";window._toastType=\'danger\';</script>";
?>
<?php include \'../includes/footer.php\'; ?>
</div><!-- /#content -->

<script>
$(document).ready(function () {
    $(\'#classesTable\').DataTable({
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        columnDefs: [{ orderable: false, targets: 8 }],
        order: [[0, \'asc\']]
    });
});

function deleteClass(id, name) {
    if (!confirm(\'Delete class "\' + name + \'"? This cannot be undone.\')) return;
    fetch(\'delete.php\', {
        method: \'POST\',
        headers: { \'Content-Type\': \'application/x-www-form-urlencoded\' },
        body: \'id=\' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            window.showToast(\'Class "\' + name + \'" deleted successfully!\', \'success\');
            setTimeout(() => {
                const row = document.getElementById(\'row-\' + id);
                if (row) row.remove();
            }, 400);
        } else {
            window.showToast(data.message || \'Failed to delete class.\', \'danger\');
        }
    })
    .catch(() => window.showToast(\'An unexpected error occurred.\', \'danger\'));
}
</script>
'); echo "Done classes"; ?>