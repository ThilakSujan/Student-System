<?php
$file = 'c:\xampp\htdocs\student_system\fee\payments.php';
$content = file_get_contents($file);

$search_php = <<<'PHP'
// ── Fetch all payments ────────────────────────────────────────
$result = $mysqli->query(
    "SELECT fp.*, s.student_name, fc.name AS cat_name, fs.academic_year, fs.amount AS fee_amount,
            COALESCE(cl.class_name, 'All Classes') AS class_name,
            u.username AS recorded_by_name
     FROM fee_payments fp
     JOIN students s         ON s.id  = fp.student_id
     JOIN fee_structures fs  ON fs.id = fp.fee_assignment_id
     JOIN fee_categories fc  ON fc.id = fs.category_id
     LEFT JOIN classes cl    ON cl.id = fs.class_id
     LEFT JOIN users u       ON u.id  = fp.recorded_by
     ORDER BY fp.payment_date DESC, fp.created_at DESC"
);
PHP;

$replace_php = <<<'PHP'
// ── Fetch all payments with Filters ───────────────────────────
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$payment_mode = $_GET['payment_mode'] ?? '';
$category_id = $_GET['category_id'] ?? '';

$where = ["1=1"];
if ($from_date) $where[] = "fp.payment_date >= '" . $mysqli->real_escape_string($from_date) . "'";
if ($to_date) $where[] = "fp.payment_date <= '" . $mysqli->real_escape_string($to_date) . "'";
if ($payment_mode) $where[] = "fp.payment_mode = '" . $mysqli->real_escape_string($payment_mode) . "'";
if ($category_id) $where[] = "fc.id = '" . $mysqli->real_escape_string($category_id) . "'";

$result = $mysqli->query(
    "SELECT fp.*, s.student_name, fc.name AS cat_name, fs.academic_year, fs.amount AS fee_amount,
            COALESCE(cl.class_name, 'All Classes') AS class_name,
            u.username AS recorded_by_name
     FROM fee_payments fp
     JOIN students s         ON s.id  = fp.student_id
     JOIN fee_structures fs  ON fs.id = fp.fee_assignment_id
     JOIN fee_categories fc  ON fc.id = fs.category_id
     LEFT JOIN classes cl    ON cl.id = fs.class_id
     LEFT JOIN users u       ON u.id  = fp.recorded_by
     WHERE " . implode(' AND ', $where) . "
     ORDER BY fp.payment_date DESC, fp.created_at DESC"
);

$cat_res = $mysqli->query("SELECT id, name FROM fee_categories");
$categories = [];
if ($cat_res) while($r = $cat_res->fetch_assoc()) $categories[] = $r;

PHP;

$content = str_replace($search_php, $replace_php, $content);

$search_stats = <<<'HTML'
    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Payments',  count($payments),                    'bi-receipt',    '#e0e7ff','#4338ca'],
            ['Total Collected', '₹'.number_format($total_amount,2), 'bi-cash-stack', '#d1fae5','#065f46'],
        ] as [$l,$v,$ic,$bg,$fg]): ?>
        <div class="col-sm-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        <i class="bi <?= $ic ?>" style="color:<?= $fg ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px"><?= $l ?></div>
                        <div class="fw-bold" style="font-size:22px;line-height:1"><?= $v ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
HTML;

$replace_stats = <<<'HTML'
    <!-- Advanced Report Filters -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-light fw-bold">
            <i class="bi bi-funnel"></i> Report Filters
        </div>
        <div class="card-body">
            <form method="GET" action="payments.php">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Payment From</label>
                        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($from_date) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:13px">Payment To</label>
                        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($to_date) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">Payment Mode</label>
                        <select name="payment_mode" class="form-select">
                            <option value="">All Modes</option>
                            <option value="Cash" <?= $payment_mode === 'Cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="Bank Transfer" <?= $payment_mode === 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            <option value="Cheque" <?= $payment_mode === 'Cheque' ? 'selected' : '' ?>>Cheque</option>
                            <option value="Online" <?= $payment_mode === 'Online' ? 'selected' : '' ?>>Online</option>
                            <option value="Other" <?= $payment_mode === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:13px">Fee Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['id']) ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mt-3 d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-file-earmark-bar-graph"></i> Generate Report</button>
                        <a href="payments.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> Reset Filters</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Payments',  count($payments),                    'bi-receipt',    '#e0e7ff','#4338ca'],
            ['Total Collected', '₹'.number_format($total_amount,2), 'bi-cash-stack', '#d1fae5','#065f46'],
        ] as [$l,$v,$ic,$bg,$fg]): ?>
        <div class="col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:12px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">
                        <i class="bi <?= $ic ?>" style="color:<?= $fg ?>"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:12px"><?= $l ?></div>
                        <div class="fw-bold" style="font-size:22px;line-height:1"><?= $v ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
HTML;

$content = str_replace($search_stats, $replace_stats, $content);

file_put_contents('c:\xampp\htdocs\student_system\fix_payments.php', '<?php file_put_contents("' . addslashes($file) . '", ' . var_export($content, true) . '); echo "Done payments"; ?>');
