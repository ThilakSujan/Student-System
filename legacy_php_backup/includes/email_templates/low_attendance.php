<?php
/**
 * Email Template: Low Attendance Warning
 * Variables: $student, $attendance (array: total_classes, present_count, absent_count, percentage, threshold), $institute
 */
$instituteName  = htmlspecialchars($institute['institute_name'] ?? 'Student Management System');
$instituteEmail = htmlspecialchars($institute['email'] ?? '');
$institutePhone = htmlspecialchars($institute['phone'] ?? '');
$studentName    = htmlspecialchars($student['student_name']);
$parentName     = !empty($student['parent_name']) ? htmlspecialchars($student['parent_name']) : 'Parent/Guardian';

$pct        = $attendance['percentage'];
$total      = $attendance['total_classes'];
$present    = $attendance['present_count'];
$absent     = $attendance['absent_count'];
$threshold  = $attendance['threshold'];
$shortfall  = ceil(($threshold / 100 * $total) - $present); // classes needed to reach threshold

// Severity colour
if ($pct < 50)       { $sevColor = '#dc2626'; $sevLabel = 'Critical'; $sevBg = '#fef2f2'; $sevBorder = '#fecaca'; }
elseif ($pct < 65)   { $sevColor = '#ea580c'; $sevLabel = 'Very Low';  $sevBg = '#fff7ed'; $sevBorder = '#fed7aa'; }
else                  { $sevColor = '#d97706'; $sevLabel = 'Low';       $sevBg = '#fefce8'; $sevBorder = '#fde68a'; }

// Progress bar colour
$barColor = $pct < 50 ? '#dc2626' : ($pct < 65 ? '#ea580c' : '#d97706');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Low Attendance Warning</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#7c2d12 0%,#9a3412 50%,#c2410c 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
      <div style="font-size:40px;margin-bottom:10px;">⚠️</div>
      <h1 style="color:#fff;margin:0;font-size:22px;font-weight:700;"><?= $instituteName ?></h1>
      <p style="color:rgba(255,255,255,0.65);margin:6px 0 0;font-size:13px;">Low Attendance Warning</p>
    </td>
  </tr>

  <!-- Severity Banner -->
  <tr>
    <td style="background:<?= $sevColor ?>;padding:12px 40px;text-align:center;">
      <span style="color:#fff;font-size:14px;font-weight:700;letter-spacing:0.5px;">
        ⚠ <?= strtoupper($sevLabel) ?> ATTENDANCE — IMMEDIATE ATTENTION REQUIRED
      </span>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="background:#fff;padding:34px 40px;">
      <p style="margin:0 0 18px;color:#374151;font-size:15px;">Dear <strong><?= $parentName ?></strong>,</p>
      <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6;">
        We wish to bring to your attention that <strong><?= $studentName ?></strong>'s attendance has fallen
        below the minimum required threshold of <strong><?= $threshold ?>%</strong>.
        Immediate action is requested to avoid academic consequences.
      </p>

      <!-- Attendance Summary Card -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:<?= $sevBg ?>;border:1px solid <?= $sevBorder ?>;border-radius:10px;margin-bottom:24px;">
        <tr>
          <td style="padding:20px 24px;">

            <!-- Stats row -->
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
              <tr>
                <td width="25%" style="text-align:center;padding:8px;">
                  <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Total Classes</div>
                  <div style="font-size:26px;font-weight:800;color:#111827;"><?= $total ?></div>
                </td>
                <td width="25%" style="text-align:center;padding:8px;">
                  <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Present</div>
                  <div style="font-size:26px;font-weight:800;color:#059669;"><?= $present ?></div>
                </td>
                <td width="25%" style="text-align:center;padding:8px;">
                  <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Absent</div>
                  <div style="font-size:26px;font-weight:800;color:#dc2626;"><?= $absent ?></div>
                </td>
                <td width="25%" style="text-align:center;padding:8px;">
                  <div style="font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">Attendance</div>
                  <div style="font-size:26px;font-weight:800;color:<?= $sevColor ?>;"><?= $pct ?>%</div>
                </td>
              </tr>
            </table>

            <!-- Progress bar -->
            <div style="background:#e5e7eb;border-radius:999px;height:12px;overflow:hidden;margin-bottom:8px;">
              <div style="background:<?= $barColor ?>;width:<?= min($pct, 100) ?>%;height:100%;border-radius:999px;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11px;color:#6b7280;">
              <span>0%</span>
              <span style="color:<?= $sevColor ?>;font-weight:700;">Current: <?= $pct ?>%</span>
              <span style="color:#059669;font-weight:600;">Required: <?= $threshold ?>%</span>
              <span>100%</span>
            </div>
          </td>
        </tr>
      </table>

      <!-- What needs to happen -->
      <?php if ($shortfall > 0): ?>
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;margin-bottom:22px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0;color:#92400e;font-size:14px;">
              📌 <strong><?= $studentName ?> needs to attend at least <?= $shortfall ?> more consecutive class<?= $shortfall > 1 ? 'es' : '' ?></strong>
              without any absence to reach the <?= $threshold ?>% threshold.
            </p>
          </td>
        </tr>
      </table>
      <?php endif; ?>

      <!-- Consequences note -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border-left:4px solid #dc2626;border-radius:0 6px 6px 0;margin-bottom:22px;">
        <tr>
          <td style="padding:14px 18px;">
            <p style="margin:0;color:#991b1b;font-size:13px;line-height:1.6;">
              <strong>⚠ Important:</strong> Students with attendance below <?= $threshold ?>% may be
              <strong>barred from examinations</strong> or face other academic penalties as per
              institutional policy. Please contact the administration immediately.
            </p>
          </td>
        </tr>
      </table>

      <p style="color:#374151;font-size:14px;margin:0 0 4px;">
        We urge you to ensure regular attendance going forward.
      </p>
      <p style="color:#374151;font-size:14px;margin:0;">
        Regards,<br>
        <strong><?= $instituteName ?></strong><br>
        Academic Affairs Office
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
      <p style="color:#d1d5db;font-size:11px;margin:6px 0 0;">This is an automated notification. Please do not reply to this email.</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
