<?php
/**
 * Email Template: Marks Published Notification
 * Variables: $student, $marks, $total_obtained, $total_max, $percentage, $institute
 */
$instituteName  = htmlspecialchars($institute['institute_name'] ?? 'Student Management System');
$instituteEmail = htmlspecialchars($institute['email'] ?? '');
$studentName    = htmlspecialchars($student['student_name']);

// Grade helper
function emailGrade($pct) {
    if ($pct >= 90) return ['A+', 'Outstanding',  '#059669'];
    if ($pct >= 80) return ['A',  'Excellent',    '#0891b2'];
    if ($pct >= 70) return ['B',  'Very Good',    '#6366f1'];
    if ($pct >= 60) return ['C',  'Good',         '#d97706'];
    if ($pct >= 50) return ['D',  'Average',      '#ea580c'];
    return                  ['F',  'Below Average','#dc2626'];
}
[$grade, $gradeDesc, $gradeColor] = emailGrade($percentage);
$pass = ($grade !== 'F');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Results Published</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#1e1b4b 0%,#312e81 50%,#4338ca 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
      <div style="font-size:36px;margin-bottom:10px;">🎓</div>
      <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;"><?= $instituteName ?></h1>
      <p style="color:rgba(255,255,255,0.65);margin:6px 0 0;font-size:13px;">Academic Results Notification</p>
    </td>
  </tr>

  <!-- Banner -->
  <tr>
    <td style="background:#4338ca;padding:14px 40px;text-align:center;">
      <span style="color:#fff;font-size:15px;font-weight:700;">📢 Your Results Have Been Published!</span>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="background:#ffffff;padding:36px 40px;">
      <p style="margin:0 0 6px;color:#374151;font-size:15px;">Dear <strong><?= $studentName ?></strong>,</p>
      <p style="margin:0 0 28px;color:#6b7280;font-size:14px;line-height:1.6;">
        We are pleased to inform you that your academic results have been officially published.
        Here is a summary of your performance:
      </p>

      <!-- Result Summary Cards -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
          <td width="33%" style="padding:0 6px 0 0;">
            <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;padding:16px;text-align:center;">
              <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Total Marks</div>
              <div style="font-size:24px;font-weight:800;color:#0369a1;"><?= $total_obtained ?></div>
              <div style="font-size:11px;color:#9ca3af;">out of <?= $total_max ?></div>
            </div>
          </td>
          <td width="33%" style="padding:0 3px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:16px;text-align:center;">
              <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Percentage</div>
              <div style="font-size:24px;font-weight:800;color:#059669;"><?= $percentage ?>%</div>
              <div style="font-size:11px;color:#9ca3af;"><?= $gradeDesc ?></div>
            </div>
          </td>
          <td width="33%" style="padding:0 0 0 6px;">
            <div style="background:#faf5ff;border:1px solid #e9d5ff;border-radius:10px;padding:16px;text-align:center;">
              <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">Grade</div>
              <div style="font-size:28px;font-weight:900;color:<?= $gradeColor ?>;"><?= $grade ?></div>
              <div style="font-size:11px;color:<?= $pass ? '#059669' : '#dc2626' ?>;font-weight:700;"><?= $pass ? 'PASS' : 'FAIL' ?></div>
            </div>
          </td>
        </tr>
      </table>

      <!-- Marks Table -->
      <?php if (!empty($marks)): ?>
      <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:24px;">
        <tr style="background:#111827;">
          <th style="color:#fff;padding:10px 14px;text-align:left;font-size:12px;">#</th>
          <th style="color:#fff;padding:10px 14px;text-align:left;font-size:12px;">Subject</th>
          <th style="color:#fff;padding:10px 14px;text-align:center;font-size:12px;">Marks</th>
          <th style="color:#fff;padding:10px 14px;text-align:center;font-size:12px;">%</th>
        </tr>
        <?php foreach ($marks as $i => $m):
            $pct = $m['total_marks'] > 0 ? round($m['marks_obtained'] / $m['total_marks'] * 100, 1) : 0;
            $color = $pct >= 75 ? '#059669' : ($pct >= 50 ? '#d97706' : '#dc2626');
        ?>
        <tr style="background:<?= ($i % 2 === 0) ? '#fff' : '#f9fafb' ?>;">
          <td style="padding:10px 14px;color:#6b7280;font-size:13px;"><?= $i + 1 ?></td>
          <td style="padding:10px 14px;">
            <span style="color:#111827;font-size:13px;font-weight:600;"><?= htmlspecialchars($m['subject_name']) ?></span>
            <span style="color:#9ca3af;font-size:11px;"> (<?= htmlspecialchars($m['subject_code']) ?>)</span>
          </td>
          <td style="padding:10px 14px;text-align:center;font-weight:700;color:#111827;"><?= $m['marks_obtained'] ?>/<?= $m['total_marks'] ?></td>
          <td style="padding:10px 14px;text-align:center;font-weight:700;color:<?= $color ?>;"><?= $pct ?>%</td>
        </tr>
        <?php endforeach; ?>
      </table>
      <?php endif; ?>

      <p style="color:#374151;font-size:14px;margin:0 0 24px;line-height:1.6;">
        <?php if ($pass): ?>
        Congratulations on your results! Keep up the great work and continue striving for excellence.
        <?php else: ?>
        We encourage you to work harder and seek additional support to improve your performance in the next examination.
        <?php endif; ?>
      </p>

      <p style="color:#374151;font-size:14px;margin:0;">
        Regards,<br>
        <strong><?= $instituteName ?></strong><br>
        Academic Department
      </p>
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;text-align:center;">
      <p style="color:#9ca3af;font-size:12px;margin:0;">
        <?= $instituteName ?>
        <?php if ($instituteEmail): ?> | <?= $instituteEmail ?><?php endif; ?>
      </p>
      <p style="color:#d1d5db;font-size:11px;margin:6px 0 0;">This is an automated notification. Please do not reply to this email.</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
