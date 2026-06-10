<?php
/**
 * Email Template: Fee Payment Invoice
 * Variables available: $student, $payments (array), $institute
 */
$instituteName  = htmlspecialchars($institute['institute_name'] ?? 'Student Management System');
$instituteEmail = htmlspecialchars($institute['email'] ?? '');
$institutePhone = htmlspecialchars($institute['phone'] ?? '');
$studentName    = htmlspecialchars($student['student_name']);
$totalPaid      = array_sum(array_column($payments, 'amount_paid'));
$invoiceDate    = date('d M Y');
$invoiceNo      = 'INV-' . date('Ymd') . '-' . str_pad($student['id'], 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fee Payment Receipt</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#064e3b 0%,#065f46 50%,#047857 100%);border-radius:12px 12px 0 0;padding:32px 40px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;">🏫 <?= $instituteName ?></h1>
            <p style="color:rgba(255,255,255,0.65);margin:4px 0 0;font-size:13px;">Finance & Accounts Department</p>
          </td>
          <td style="text-align:right;">
            <div style="background:rgba(255,255,255,0.15);border-radius:8px;padding:10px 16px;display:inline-block;">
              <div style="color:rgba(255,255,255,0.7);font-size:10px;text-transform:uppercase;letter-spacing:1px;">RECEIPT</div>
              <div style="color:#fff;font-size:14px;font-weight:700;"><?= $invoiceNo ?></div>
            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Confirmation Banner -->
  <tr>
    <td style="background:#059669;padding:14px 40px;text-align:center;">
      <span style="color:#fff;font-size:15px;font-weight:700;">✅ Payment Confirmed — Thank You!</span>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="background:#ffffff;padding:36px 40px;">

      <p style="margin:0 0 6px;color:#374151;font-size:15px;">Dear <strong><?= $studentName ?></strong>,</p>
      <p style="margin:0 0 24px;color:#6b7280;font-size:14px;">Your fee payment has been successfully recorded. Here are your payment details:</p>

      <!-- Student Info Row -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0fdf4;border-radius:8px;margin-bottom:24px;">
        <tr>
          <td style="padding:16px 20px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:4px 0;">
                  <span style="color:#6b7280;font-size:12px;">Student</span><br>
                  <strong style="color:#111827;"><?= $studentName ?></strong>
                </td>
                <td style="padding:4px 0;">
                  <span style="color:#6b7280;font-size:12px;">Student ID</span><br>
                  <strong style="color:#111827;">#<?= str_pad($student['id'], 4, '0', STR_PAD_LEFT) ?></strong>
                </td>
                <td style="padding:4px 0;text-align:right;">
                  <span style="color:#6b7280;font-size:12px;">Payment Date</span><br>
                  <strong style="color:#111827;"><?= $invoiceDate ?></strong>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>

      <!-- Payment Table -->
      <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:20px;">
        <tr style="background:#111827;">
          <th style="color:#fff;padding:12px 16px;text-align:left;font-size:12px;font-weight:600;text-transform:uppercase;">#</th>
          <th style="color:#fff;padding:12px 16px;text-align:left;font-size:12px;font-weight:600;text-transform:uppercase;">Fee Category</th>
          <th style="color:#fff;padding:12px 16px;text-align:left;font-size:12px;font-weight:600;text-transform:uppercase;">Method</th>
          <th style="color:#fff;padding:12px 16px;text-align:right;font-size:12px;font-weight:600;text-transform:uppercase;">Amount</th>
        </tr>
        <?php foreach ($payments as $i => $p): ?>
        <tr style="background:<?= ($i % 2 === 0) ? '#fff' : '#f9fafb' ?>;">
          <td style="padding:12px 16px;color:#6b7280;font-size:13px;"><?= $i + 1 ?></td>
          <td style="padding:12px 16px;">
            <strong style="color:#111827;font-size:14px;"><?= htmlspecialchars($p['cat_name'] ?? $p['category'] ?? 'Fee') ?></strong>
            <?php if (!empty($p['academic_year'])): ?>
              <br><span style="color:#9ca3af;font-size:12px;"><?= htmlspecialchars($p['academic_year']) ?></span>
            <?php endif; ?>
            <?php if (!empty($p['receipt_no'])): ?>
              <br><span style="color:#9ca3af;font-size:11px;">Receipt: <?= htmlspecialchars($p['receipt_no']) ?></span>
            <?php endif; ?>
          </td>
          <td style="padding:12px 16px;color:#374151;font-size:13px;"><?= htmlspecialchars($p['payment_mode'] ?? $p['payment_method'] ?? 'Cash') ?></td>
          <td style="padding:12px 16px;text-align:right;font-weight:700;color:#059669;font-size:14px;">₹<?= number_format($p['amount_paid'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <!-- Total Row -->
        <tr style="background:#f0fdf4;border-top:2px solid #059669;">
          <td colspan="3" style="padding:14px 16px;font-weight:700;font-size:14px;color:#111827;">Total Amount Paid</td>
          <td style="padding:14px 16px;text-align:right;font-weight:800;font-size:18px;color:#059669;">₹<?= number_format($totalPaid, 2) ?></td>
        </tr>
      </table>

      <div style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:4px;padding:12px 16px;margin-bottom:24px;">
        <p style="margin:0;color:#92400e;font-size:13px;">
          💡 <strong>Note:</strong> Please keep this receipt for your records. Contact the accounts department for any discrepancies.
        </p>
      </div>

      <p style="color:#374151;font-size:14px;margin:0;">
        Thank you for your payment.<br><br>
        Regards,<br>
        <strong><?= $instituteName ?></strong><br>
        Accounts Department
      </p>
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
      <p style="color:#9ca3af;font-size:12px;margin:0;">
        <?= $instituteName ?>
        <?php if ($instituteEmail): ?> | <?= $instituteEmail ?><?php endif; ?>
        <?php if ($institutePhone): ?> | <?= $institutePhone ?><?php endif; ?>
      </p>
      <p style="color:#d1d5db;font-size:11px;margin:6px 0 0;">This is an automated receipt. Please do not reply to this email.</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
