<?php
/**
 * Email Template: Report Card Email
 * Variables: $student, $marks, $total_obtained, $total_max, $percentage, $institute
 */
$instituteName  = htmlspecialchars($institute['institute_name'] ?? 'Student Management System');
$instituteEmail = htmlspecialchars($institute['email'] ?? '');
$studentName    = htmlspecialchars($student['student_name']);
$academicYear   = date('Y') . '-' . (date('Y') + 1);

function rcEmailGrade($pct) {
    if ($pct >= 90) return ['A+', 'Outstanding',  '#059669'];
    if ($pct >= 80) return ['A',  'Excellent',    '#0891b2'];
    if ($pct >= 70) return ['B',  'Very Good',    '#6366f1'];
    if ($pct >= 60) return ['C',  'Good',         '#d97706'];
    if ($pct >= 50) return ['D',  'Average',      '#ea580c'];
    return                  ['F',  'Below Average','#dc2626'];
}
[$grade, $gradeDesc, $gradeColor] = rcEmailGrade($percentage);
$passArr = array_filter($marks, fn($m) => $m['marks_obtained'] >= $m['total_marks'] * 0.35);
$pass    = (count($passArr) === count($marks)) && $grade !== 'F';

function rcSubGrade($obtained, $total) {
    $pct = $total > 0 ? $obtained / $total * 100 : 0;
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B';
    if ($pct >= 60) return 'C';
    if ($pct >= 50) return 'D';
    return 'F';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Card</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-radius:12px 12px 0 0;padding:32px 40px;">
      <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">🏫 <?= $instituteName ?></h1>
            <p style="color:rgba(255,255,255,0.55);margin:4px 0 0;font-size:12px;">Academic Year: <?= $academicYear ?></p>
          </td>
          <td style="text-align:right;">
            <div style="background:#3b82f6;color:#fff;font-size:11px;font-weight:700;padding:6px 14px;border-radius:20px;display:inline-block;letter-spacing:1px;">REPORT CARD</div>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- Blue divider -->
  <tr><td style="height:5px;background:linear-gradient(90deg,#3b82f6,#8b5cf6,#06b6d4);"></td></tr>

  <!-- Body -->
  <tr>
    <td style="background:#fff;padding:32px 40px;">

      <p style="margin:0 0 4px;color:#374151;font-size:15px;">Dear <strong><?= $studentName ?></strong>,</p>
      <p style="margin:0 0 24px;color:#6b7280;font-size:14px;">Please find your official report card below for the academic year <?= $academicYear ?>.</p>

      <!-- Student Info -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:24px;">
        <tr>
          <td style="padding:16px 20px;">
            <table width="100%" cellpadding="4">
              <tr>
                <td style="color:#6b7280;font-size:12px;text-transform:uppercase;">Student</td>
                <td style="color:#111827;font-weight:700;font-size:14px;"><?= $studentName ?></td>
                <td style="color:#6b7280;font-size:12px;text-transform:uppercase;">ID</td>
                <td style="color:#111827;font-weight:700;font-size:14px;">#<?= str_pad($student['id'], 4, '0', STR_PAD_LEFT) ?></td>
              </tr>
              <?php if (!empty($student['department'])): ?>
              <tr>
                <td style="color:#6b7280;font-size:12px;text-transform:uppercase;">Department</td>
                <td colspan="3" style="color:#111827;font-weight:600;font-size:14px;"><?= htmlspecialchars($student['department']) ?></td>
              </tr>
              <?php endif; ?>
            </table>
          </td>
        </tr>
      </table>

      <!-- Marks Table -->
      <?php if (!empty($marks)): ?>
      <table width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;margin-bottom:20px;">
        <tr style="background:#1e293b;">
          <th style="color:#94a3b8;padding:11px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;">#</th>
          <th style="color:#94a3b8;padding:11px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;">Subject</th>
          <th style="color:#94a3b8;padding:11px 14px;text-align:center;font-size:11px;font-weight:600;text-transform:uppercase;">Max</th>
          <th style="color:#94a3b8;padding:11px 14px;text-align:center;font-size:11px;font-weight:600;text-transform:uppercase;">Scored</th>
          <th style="color:#94a3b8;padding:11px 14px;text-align:center;font-size:11px;font-weight:600;text-transform:uppercase;">Grade</th>
        </tr>
        <?php foreach ($marks as $i => $m):
            $pct  = $m['total_marks'] > 0 ? round($m['marks_obtained'] / $m['total_marks'] * 100, 1) : 0;
            $sg   = rcSubGrade($m['marks_obtained'], $m['total_marks']);
            $sPassed = $m['marks_obtained'] >= $m['total_marks'] * 0.35;
            $rowBg = ($i % 2 === 0) ? '#fff' : '#f8fafc';
        ?>
        <tr style="background:<?= $rowBg ?>;">
          <td style="padding:11px 14px;color:#9ca3af;font-size:13px;"><?= $i + 1 ?></td>
          <td style="padding:11px 14px;">
            <span style="color:#111827;font-size:13px;font-weight:600;"><?= htmlspecialchars($m['subject_name']) ?></span>
            <br><span style="color:#9ca3af;font-size:11px;"><?= htmlspecialchars($m['subject_code']) ?></span>
          </td>
          <td style="padding:11px 14px;text-align:center;color:#6b7280;font-size:13px;"><?= $m['total_marks'] ?></td>
          <td style="padding:11px 14px;text-align:center;font-weight:700;color:<?= $pct >= 50 ? '#059669' : '#dc2626' ?>;font-size:14px;"><?= $m['marks_obtained'] ?></td>
          <td style="padding:11px 14px;text-align:center;">
            <span style="font-weight:700;font-size:13px;"><?= $sg ?></span>
            <span style="font-size:10px;color:<?= $sPassed ? '#059669' : '#dc2626' ?>;display:block;"><?= $sPassed ? 'Pass' : 'Fail' ?></span>
          </td>
        </tr>
        <?php endforeach; ?>
        <!-- Total -->
        <tr style="background:#f0f2ff;border-top:2px solid #4338ca;">
          <td colspan="2" style="padding:13px 14px;font-weight:700;color:#1e1b4b;">Total</td>
          <td style="padding:13px 14px;text-align:center;font-weight:700;"><?= $total_max ?></td>
          <td style="padding:13px 14px;text-align:center;font-weight:800;font-size:16px;color:#4338ca;"><?= $total_obtained ?></td>
          <td style="padding:13px 14px;text-align:center;font-weight:700;"><?= $percentage ?>%</td>
        </tr>
      </table>
      <?php endif; ?>

      <!-- Final Result -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
        <tr>
          <td width="25%" style="padding:0 6px 0 0;">
            <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center;">
              <div style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Percentage</div>
              <div style="font-size:22px;font-weight:800;color:#0891b2;"><?= $percentage ?>%</div>
            </div>
          </td>
          <td width="25%" style="padding:0 3px;">
            <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center;">
              <div style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Grade</div>
              <div style="font-size:22px;font-weight:800;color:<?= $gradeColor ?>;"><?= $grade ?></div>
            </div>
          </td>
          <td width="25%" style="padding:0 3px;">
            <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center;">
              <div style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Remark</div>
              <div style="font-size:13px;font-weight:700;color:<?= $gradeColor ?>;"><?= $gradeDesc ?></div>
            </div>
          </td>
          <td width="25%" style="padding:0 0 0 6px;">
            <div style="background:<?= $pass ? '#f0fdf4' : '#fef2f2' ?>;border-radius:8px;padding:14px;text-align:center;border:1px solid <?= $pass ? '#bbf7d0' : '#fecaca' ?>;">
              <div style="font-size:10px;color:#6b7280;text-transform:uppercase;margin-bottom:4px;">Result</div>
              <div style="font-size:16px;font-weight:800;color:<?= $pass ? '#059669' : '#dc2626' ?>;"><?= $pass ? 'PASS' : 'FAIL' ?></div>
            </div>
          </td>
        </tr>
      </table>

      <p style="color:#374151;font-size:14px;margin:0;">
        Sincerely,<br>
        <strong><?= $instituteName ?></strong><br>
        Academic Affairs
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
      <p style="color:#d1d5db;font-size:11px;margin:6px 0 0;">Generated on <?= date('d M Y, h:i A') ?> — Official Document</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
