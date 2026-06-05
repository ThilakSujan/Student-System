<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = "Fee Reports";

// ── Filters ─────────────────────────────────────────────────
$filter_year   = trim($_GET['year'] ?? '');
$filter_cat    = (int)($_GET['category'] ?? 0);
$filter_class  = (int)($_GET['class_id'] ?? 0);
$filter_month  = trim($_GET['month'] ?? '');

// Build WHERE
$where = ['1=1'];
$params = [];

if ($filter_year)  { $where[] = "fs.academic_year = ?"; $params[] = $filter_year; }
if ($filter_cat)   { $where[] = "fc.id = ?";            $params[] = $filter_cat; }
if ($filter_class) { $where[] = "fs.class_id = ?";      $params[] = $filter_class; }
if ($filter_month) { $where[] = "DATE_FORMAT(fp.payment_date,'%Y-%m') = ?"; $params[] = $filter_month; }

$where_sql = implode(' AND ', $where);

// Main query
$sql = "SELECT fp.*, s.student_name, fc.name AS cat_name, fs.academic_year, fs.amount AS fee_amount,
               COALESCE(cl.class_name,'All Classes') AS class_name, u.username AS recorded_by_name
        FROM fee_payments fp
        JOIN students s        ON s.id  = fp.student_id
        JOIN fee_structures fs ON fs.id = fp.id
        JOIN fee_categories fc ON fc.id = fs.category_id
        LEFT JOIN classes cl   ON cl.id = fs.class_id
        LEFT JOIN users u      ON u.id  = fp.recorded_by
        WHERE $where_sql
        ORDER BY fp.payment_date DESC";

if ($params) {
    $stmt = $mysqli->prepare($sql);
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $payments = $mysqli->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Summary by category
$cat_summary_r = $mysqli->query(
    "SELECT fc.name, SUM(fp.amount_paid) total, COUNT(fp.id) cnt
     FROM fee_payments fp
     JOIN fee_structures fs ON fs.id = fp.fee_assignment_id
     JOIN fee_categories fc ON fc.id = fs.category_id
     GROUP BY fc.id ORDER BY total DESC"
);
$cat_summary = $cat_summary_r ? $cat_summary_r->fetch_all(MYSQLI_ASSOC) : [];

// Fetch filter options
$years_r  = $mysqli->query("SELECT DISTINCT academic_year FROM fee_structures ORDER BY academic_year DESC");
$years    = $years_r ? $years_r->fetch_all(MYSQLI_ASSOC) : [];
$cats_r   = $mysqli->query("SELECT id, name FROM fee_categories ORDER BY name");
$cats     = $cats_r ? $cats_r->fetch_all(MYSQLI_ASSOC) : [];
$class_r  = $mysqli->query("SELECT id, class_name, section FROM classes ORDER BY class_name");
$classes  = $class_r ? $class_r->fetch_all(MYSQLI_ASSOC) : [];

$grand_total = array_sum(array_column($payments,'amount_paid'));

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-warning"></i>Fee Reports</h4>
            <small class="text-muted">Filter and analyze fee collection data</small>
        </div>
        <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
    </div>

    <!-- Filter card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-dark text-white fw-semibold"><i class="bi bi-funnel me-1"></i> Filter Reports</div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Academic Year</label>
                    <select name="year" class="form-select">
                        <option value="">All Years</option>
                        <?php foreach ($years as $y): ?>
                            <option value="<?= htmlspecialchars($y['academic_year']) ?>" <?= $filter_year === $y['academic_year'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($y['academic_year']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Fee Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $filter_cat == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Class</label>
                    <select name="class_id" class="form-select">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $cl): ?>
                            <option value="<?= $cl['id'] ?>" <?= $filter_class == $cl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cl['class_name'].($cl['section']?' ('.$cl['section'].')':'')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Month</label>
                    <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filter_month) ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Apply Filters</button>
                    <a href="report.php" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear</a>
                    <button type="button" class="btn btn-success ms-auto" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i>Print Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:52px;height:52px;border-radius:14px;background:#d1fae5;display:flex;align-items:center;justify-content:center;font-size:24px;">
                        <i class="bi bi-cash-stack" style="color:#065f46"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Total Collected (Filtered)</div>
                        <div class="fw-bold fs-3 text-success">₹<?= number_format($grand_total,2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:52px;height:52px;border-radius:14px;background:#e0e7ff;display:flex;align-items:center;justify-content:center;font-size:24px;">
                        <i class="bi bi-receipt" style="color:#4338ca"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">No. of Transactions</div>
                        <div class="fw-bold fs-3"><?= count($payments) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:52px;height:52px;border-radius:14px;background:#fef3c7;display:flex;align-items:center;justify-content:center;font-size:24px;">
                        <i class="bi bi-calculator" style="color:#92400e"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px">Avg per Transaction</div>
                        <div class="fw-bold fs-3">₹<?= count($payments) ? number_format($grand_total/count($payments),2) : '0.00' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Category breakdown chart -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-semibold"><i class="bi bi-pie-chart me-1"></i> Collection by Category</div>
                <div class="card-body">
                    <?php if (empty($cat_summary)): ?>
                        <div class="text-center text-muted py-4">No data</div>
                    <?php else: ?>
                    <canvas id="catChart" height="220"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Category breakdown table -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-semibold"><i class="bi bi-table me-1"></i> Category-wise Summary</div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Category</th><th>Transactions</th><th>Total Collected</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($cat_summary as $cs): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($cs['name']) ?></span></td>
                                <td><?= $cs['cnt'] ?></td>
                                <td class="fw-bold text-success">₹<?= number_format($cs['total'],2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-list-ul me-1"></i> Detailed Payment List</strong>
            <span class="badge bg-success">₹<?= number_format($grand_total,2) ?> total</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($payments)): ?>
                <div class="text-center py-5 text-muted">No records match the selected filters.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="reportTable" class="table table-hover table-bordered mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Class</th>
                            <th>Acad. Year</th>
                            <th>Paid</th>
                            <th>Method</th>
                            <th>Receipt</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($payments as $i => $p): ?>
                        <tr>
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($p['student_name']) ?></td>
                            <td><span class="badge bg-primary"><?= htmlspecialchars($p['cat_name']) ?></span></td>
                            <td class="text-muted" style="font-size:13px"><?= htmlspecialchars($p['class_name']) ?></td>
                            <td><?= htmlspecialchars($p['academic_year']) ?></td>
                            <td class="fw-bold text-success">₹<?= number_format($p['amount_paid'],2) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['payment_mode']) ?></span></td>
                            <td class="text-muted" style="font-size:13px"><?= htmlspecialchars($p['receipt_no'] ?: '—') ?></td>
                            <td class="text-muted" style="font-size:13px"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>
<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
$(document).ready(function() {
    $('#reportTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10,25,50,100],[10,25,50,100]],
        order: [[0,'asc']]
    });
});

// Category pie chart
const catLabels = <?= json_encode(array_column($cat_summary,'name')) ?>;
const catVals   = <?= json_encode(array_column($cat_summary,'total')) ?>;

if (catLabels.length) {
    new Chart(document.getElementById('catChart'), {
        type: 'doughnut',
        data: {
            labels: catLabels,
            datasets: [{
                data: catVals,
                backgroundColor: [
                    '#6366f1','#0ea5e9','#10b981','#f59e0b','#ef4444',
                    '#8b5cf6','#06b6d4','#84cc16','#f97316','#ec4899'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, font: { size: 12 } } },
                tooltip: { callbacks: { label: ctx => ctx.label + ': ₹' + Number(ctx.parsed).toLocaleString('en-IN') } }
            }
        }
    });
}
</script>
