<?php
/**
 * Email Template: Attendance Absence Alert
 * Variables available: $student, $date, $institute
 */
$instituteName  = htmlspecialchars($institute['institute_name'] ?? 'Student Management System');
$instituteEmail = htmlspecialchars($institute['email'] ?? '');
$institutePhone = htmlspecialchars($institute['phone'] ?? '');
$studentName    = htmlspecialchars($student['student_name']);
$formattedDate  = date('l, d F Y', strtotime($date));
$parentName     = !empty($student['parent_name']) ? htmlspecialchars($student['parent_name']) : 'Parent/Guardian';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Attendance Alert</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%);border-radius:12px 12px 0 0;padding:32px 40px;text-align:center;">
      <div style="display:inline-block;background:rgba(255,255,255,0.1);border-radius:50%;width:60px;height:60px;line-height:60px;font-size:28px;margin-bottom:12px;">🏫</div>
      <h1 style="color:#ffffff;margin:0;font-size:22px;font-weight:700;letter-spacing:0.5px;"><?= $instituteName ?></h1>
      <p style="color:rgba(255,255,255,0.65);margin:6px 0 0;font-size:13px;">Student Attendance Notification</p>
    </td>
  </tr>

  <!-- Alert Banner -->
  <tr>
    <td style="background:#dc2626;padding:14px 40px;text-align:center;">
      <span style="color:#fff;font-size:15px;font-weight:700;letter-spacing:1px;">⚠ ABSENCE ALERT</span>
    </td>
  </tr>

  <!-- Body -->
  <tr>
    <td style="background:#ffffff;padding:36px 40px;">
      <p style="margin:0 0 20px;color:#374151;font-size:15px;">Dear <strong><?= $parentName ?></strong>,</p>

      <p style="margin:0 0 24px;color:#374151;font-size:15px;line-height:1.6;">
        This is to inform you that <strong><?= $studentName ?></strong> was marked
        <strong style="color:#dc2626;">ABSENT</strong> on <strong><?= $formattedDate ?></strong>.
      </p>

      <!-- Info Card -->
      <table width="100%" cellpadding="0" cellspacing="0" style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;margin-bottom:24px;">
        <tr>
          <td style="padding:20px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td style="padding:6px 0;">
                  <span style="color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Student Name</span><br>
                  <strong style="color:#111827;font-size:15px;"><?= $studentName ?></strong>
                </td>
                <td style="padding:6px 0;text-align:right;">
                  <span style="color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Attendance Status</span><br>
                  <span style="background:#dc2626;color:#fff;padding:3px 12px;border-radius:20px;font-size:13px;font-weight:700;">ABSENT</span>
                </td>
              </tr>
              <tr>
                <td style="padding:6px 0;padding-top:14px;" colspan="2">
                  <span style="color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Date</span><br>
                  <strong style="color:#111827;font-size:15px;">📅 <?= $formattedDate ?></strong>
                </td>
              </tr>
              <?php if (!empty($student['department'])): ?>
              <tr>
                <td style="padding:6px 0;padding-top:14px;" colspan="2">
                  <span style="color:#6b7280;font-size:12px;text-transform:uppercase;letter-spacing:0.5px;">Department</span><br>
                  <strong style="color:#111827;font-size:15px;"><?= htmlspecialchars($student['department']) ?></strong>
                </td>
              </tr>
              <?php endif; ?>
            </table>
          </td>
        </tr>
      </table>

      <p style="color:#6b7280;font-size:14px;line-height:1.6;margin:0 0 20px;">
        If your ward was present or this is an error, please contact the school administration immediately.
        Regular attendance is important for academic success.
      </p>

      <p style="color:#374151;font-size:14px;margin:0;">
        Best regards,<br>
        <strong><?= $instituteName ?></strong><br>
        Administration Office
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
      <p style="color:#d1d5db;font-size:11px;margin:6px 0 0;">
        This is an automated notification. Please do not reply to this email.
      </p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
