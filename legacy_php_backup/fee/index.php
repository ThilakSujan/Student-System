<?php
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = "Fee Management";

// ── Summary stats ────────────────────────────────────────────
$stats = [];

// Total categories
$r = $mysqli->query("SELECT COUNT(*) c FROM fee_categories WHERE status='Active'");
$stats['categories'] = $r ? $r->fetch_assoc()['c'] : 0;

// Total fee structures
$r = $mysqli->query("SELECT COUNT(*) c FROM fee_structures WHERE status='Active'");
$stats['structures'] = $r ? $r->fetch_assoc()['c'] : 0;

// Total collected this month
$r = $mysqli->query("SELECT COALESCE(SUM(amount_paid),0) total FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())");
$stats['this_month'] = $r ? $r->fetch_assoc()['total'] : 0;

// Total collected overall
$r = $mysqli->query("SELECT COALESCE(SUM(amount_paid),0) total FROM fee_payments");
$stats['overall'] = $r ? $r->fetch_assoc()['total'] : 0;

// Recent payments
$recent = $mysqli->query(
    "SELECT fp.*, s.student_name, fc.name AS cat_name, fs.academic_year
     FROM fee_payments fp
     JOIN students s         ON s.id  = fp.student_id
     JOIN fee_structures fs  ON fs.id = fp.fee_assignment_id
     JOIN fee_categories fc  ON fc.id = fs.category_id
     ORDER BY fp.created_at DESC LIMIT 8"
);
$recent_payments = $recent ? $recent->fetch_all(MYSQLI_ASSOC) : [];

// Monthly collection (last 6 months)
$monthly = $mysqli->query(
    "SELECT DATE_FORMAT(payment_date,'%b %Y') AS mo, MONTH(payment_date) m, YEAR(payment_date) y, SUM(amount_paid) total
     FROM fee_payments
     WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY y, m ORDER BY y, m"
);
$monthly_data = $monthly ? $monthly->fetch_all(MYSQLI_ASSOC) : [];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Page heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2 text-success"></i>Fee Management</h4>
            <small class="text-muted">Manage fee categories, structures and student payments</small>
        </div>
        <div class="d-flex gap-2">
            <a href="categories.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-tags me-1"></i>Categories
            </a>
            <a href="structures.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-list-check me-1"></i>Structures
            </a>
            <a href="payments.php" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle me-1"></i>Record Payment
            </a>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Fee Categories',     $stats['categories'], 'bi-tags-fill',     '#6366f1','#ede9fe','#4338ca'],
            ['Fee Structures',     $stats['structures'], 'bi-list-check',    '#0ea5e9','#e0f2fe','#0369a1'],
            ['Collected This Month','₹ '.number_format($stats['this_month'],2),'bi-calendar-check-fill','#10b981','#d1fae5','#065f46'],
            ['Total Collected',    '₹ '.number_format($stats['overall'],2),  'bi-cash-stack',    '#f59e0b','#fef3c7','#92400e'],
        ] as [$label,$val,$icon,$col,$bg,$fg]): ?>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:52px;height:52px;border-radius:14px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;">
                        <i class="bi <?= $icon ?>" style="color:<?= $fg ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px"><?= $label ?></div>
                        <div class="fw-bold" style="font-size:22px;line-height:1.2"><?= $val ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-4">
        <!-- Quick links -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-semibold">
                    <i class="bi bi-grid me-1"></i> Quick Actions
                </div>
                <div class="card-body p-3">
                    <div class="d-grid gap-2">
                        <a href="categories.php" class="btn btn-outline-primary text-start">
                            <i class="bi bi-tags me-2"></i>Manage Fee Categories
                        </a>
                        <a href="structures.php" class="btn btn-outline-success text-start">
                            <i class="bi bi-list-check me-2"></i>Manage Fee Structures
                        </a>
                        <a href="payments.php" class="btn btn-outline-info text-start">
                            <i class="bi bi-credit-card me-2"></i>View All Payments
                        </a>
                        <a href="payments.php?action=add" class="btn btn-success text-start">
                            <i class="bi bi-plus-circle me-2"></i>Record New Payment
                        </a>
                        <a href="report.php" class="btn btn-outline-warning text-start">
                            <i class="bi bi-bar-chart me-2"></i>Fee Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-semibold">
                    <i class="bi bi-graph-up me-1"></i> Monthly Fee Collection (Last 6 Months)
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="160"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-clock-history me-1"></i> Recent Payments</strong>
            <a href="payments.php" class="btn btn-sm btn-outline-light">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recent_payments)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                    <p class="text-muted">No payments recorded yet.</p>
                    <a href="payments.php?action=add" class="btn btn-success btn-sm">Record First Payment</a>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Category</th>
                            <th>Academic Year</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_payments as $p): ?>
                        <tr>
                            <td class="align-middle fw-semibold"><?= htmlspecialchars($p['student_name']) ?></td>
                            <td class="align-middle"><span class="badge bg-primary"><?= htmlspecialchars($p['cat_name']) ?></span></td>
                            <td class="align-middle text-muted"><?= htmlspecialchars($p['academic_year']) ?></td>
                            <td class="align-middle fw-bold text-success">₹<?= number_format($p['amount_paid'],2) ?></td>
                            <td class="align-middle">
                                <span class="badge bg-secondary"><?= htmlspecialchars($p['payment_mode']) ?></span>
                            </td>
                            <td class="align-middle text-muted" style="font-size:13px"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
                            <td class="align-middle text-center">
                                <a href="payments.php" class="btn btn-warning btn-sm" title="View Payments"><i class="bi bi-pencil"></i></a>
                                <button class="btn btn-danger btn-sm" onclick="deletePayment(<?= $p['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
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

<?php include '../includes/footer.php'; ?>
</div><!-- /#content -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Monthly chart
const labels = <?= json_encode(array_column($monthly_data, 'mo')) ?>;
const values = <?= json_encode(array_column($monthly_data, 'total')) ?>;

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: labels.length ? labels : ['No Data'],
        datasets: [{
            label: 'Amount Collected (₹)',
            data: values.length ? values : [0],
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderColor: 'rgba(99,102,241,1)',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => '₹'+v } } }
    }
});

function deletePayment(id) {
    if (!confirm('Delete this payment record? This cannot be undone.')) return;
    fetch('delete_payment.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) { showToast('Payment deleted.','success'); setTimeout(()=>location.reload(),600); }
        else showToast(d.message||'Error deleting.','danger');
    })
    .catch(()=>showToast('Unexpected error.','danger'));
}
</script>
