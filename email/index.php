<?php
/**
 * Email Logs Viewer — Admin Only
 * Displays all email sending history with filters and statistics.
 */
session_start();
require_once '../includes/auth.php';
require_role(['admin']);
require_once '../config/db.php';

$page_title = 'Email Logs';

// ── Filters ───────────────────────────────────────────────────────────
$filterType   = trim($_GET['type']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$filterDate   = trim($_GET['date']   ?? '');

$where  = [];
$params = [];
$types  = '';

if ($filterType && in_array($filterType, ['attendance','fee_invoice','marks_published','report_card','custom'])) {
    $where[]  = "email_type = ?";
    $params[] = $filterType;
    $types   .= 's';
}
if ($filterStatus && in_array($filterStatus, ['sent','failed'])) {
    $where[]  = "status = ?";
    $params[] = $filterStatus;
    $types   .= 's';
}
if ($filterDate) {
    $where[]  = "DATE(sent_at) = ?";
    $params[] = $filterDate;
    $types   .= 's';
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// ── Stats ─────────────────────────────────────────────────────────────
$statsRes = $mysqli->query(
    "SELECT
        COUNT(*) AS total,
        SUM(status='sent')   AS sent,
        SUM(status='failed') AS failed,
        SUM(email_type='attendance')      AS att,
        SUM(email_type='fee_invoice')     AS fee,
        SUM(email_type='marks_published') AS marks,
        SUM(email_type='report_card')     AS report,
        SUM(email_type='custom')          AS custom
     FROM email_logs"
);
$stats = $statsRes ? $statsRes->fetch_assoc() : [];

// ── Fetch logs ────────────────────────────────────────────────────────
$sql = "SELECT * FROM email_logs $whereSQL ORDER BY sent_at DESC LIMIT 500";
if ($params) {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $res  = $mysqli->query($sql);
    $logs = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// ── Type labels ───────────────────────────────────────────────────────
$typeLabels = [
    'attendance'      => ['Attendance Alert',    'danger',  'bi-person-x'],
    'fee_invoice'     => ['Fee Invoice',         'success', 'bi-receipt'],
    'marks_published' => ['Marks Published',     'primary', 'bi-graph-up'],
    'report_card'     => ['Report Card',         'info',    'bi-file-earmark-text'],
    'custom'          => ['Custom Email',        'secondary','bi-envelope'],
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div id="content">
<?php include '../includes/navbar.php'; ?>
<div id="main-content">
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="bi bi-envelope-open me-2 text-primary"></i>Email Logs</h4>
            <p class="text-muted mb-0" style="font-size:13px">Track all email notifications sent from the system</p>
        </div>
        <div class="d-flex gap-2">
            <a href="?<?= http_build_query(array_filter(['type'=>$filterType,'status'=>$filterStatus,'date'=>$filterDate])) ?>"
               class="btn btn-outline-secondary btn-sm" title="Refresh">
                <i class="bi bi-arrow-clockwise"></i>
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <?php
        $statCards = [
            ['Total Sent',    $stats['total']  ?? 0, 'bi-send',          '#dbeafe', '#1d4ed8'],
            ['✅ Successful', $stats['sent']   ?? 0, 'bi-check-circle',  '#d1fae5', '#065f46'],
            ['❌ Failed',     $stats['failed'] ?? 0, 'bi-x-circle',      '#fee2e2', '#991b1b'],
            ['Attendance',    $stats['att']    ?? 0, 'bi-person-x',      '#ffedd5', '#9a3412'],
            ['Fee Invoice',   $stats['fee']    ?? 0, 'bi-receipt',       '#f0fdf4', '#166534'],
            ['Marks',         $stats['marks']  ?? 0, 'bi-graph-up',      '#ede9fe', '#4c1d95'],
        ];
        foreach ($statCards as [$label, $value, $icon, $bg, $color]):
        ?>
        <div class="col-6 col-sm-4 col-xl-2">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;border-radius:10px;background:<?= $bg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;color:#6c757d;"><?= $label ?></div>
                        <div class="fw-bold fs-5"><?= number_format((int)$value) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-sm-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Email Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <?php foreach ($typeLabels as $val => [$lbl]): ?>
                            <option value="<?= $val ?>" <?= $filterType === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="sent"   <?= $filterStatus === 'sent'   ? 'selected' : '' ?>>Sent</option>
                        <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm" value="<?= htmlspecialchars($filterDate) ?>">
                </div>
                <div class="col-sm-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <strong><i class="bi bi-list-ul me-1"></i>Email Log Entries</strong>
            <small class="text-muted"><?= count($logs) ?> records</small>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                    <p>No email logs found<?= ($filterType || $filterStatus || $filterDate) ? ' for the selected filters' : '' ?>.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table id="emailLogsTable" class="table table-hover table-bordered mb-0" style="font-size:13px;">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Type</th>
                            <th class="text-center">Status</th>
                            <th>Sent At</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($logs as $i => $log):
                        [$typeLabel, $typeBadge, $typeIcon] = $typeLabels[$log['email_type']] ?? ['Unknown', 'secondary', 'bi-question'];
                    ?>
                        <tr>
                            <td class="text-muted align-middle"><?= $i + 1 ?></td>
                            <td class="align-middle">
                                <i class="bi bi-envelope text-muted me-1"></i>
                                <?= htmlspecialchars($log['recipient_email']) ?>
                            </td>
                            <td class="align-middle" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                <?= htmlspecialchars($log['subject']) ?>
                            </td>
                            <td class="align-middle">
                                <span class="badge bg-<?= $typeBadge ?>">
                                    <i class="bi <?= $typeIcon ?> me-1"></i><?= $typeLabel ?>
                                </span>
                            </td>
                            <td class="align-middle text-center">
                                <?php if ($log['status'] === 'sent'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sent</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Failed</span>
                                <?php endif; ?>
                            </td>
                            <td class="align-middle text-muted">
                                <?= date('d M Y, h:i A', strtotime($log['sent_at'])) ?>
                            </td>
                            <td class="align-middle">
                                <?php if ($log['error_message']): ?>
                                    <span class="text-danger" style="font-size:12px;" title="<?= htmlspecialchars($log['error_message']) ?>">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        <?= htmlspecialchars(mb_strimwidth($log['error_message'], 0, 60, '…')) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
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
</div>

<script>
$(document).ready(function () {
    $('#emailLogsTable').DataTable({
        pageLength : 25,
        lengthMenu : [[10, 25, 50, 100], [10, 25, 50, 100]],
        columnDefs : [{ orderable: false, targets: 6 }],
        order      : [[5, 'desc']]
    });
});
</script>
