<?php
/**
 * Email Template: Custom / Generic Email
 * Variables: $recipient_email, $subject, $message (HTML safe), $institute
 */
$instituteName  = htmlspecialchars($institute['institute_name'] ?? 'Student Management System');
$instituteEmail = htmlspecialchars($institute['email'] ?? '');
$institutePhone = htmlspecialchars($institute['phone'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($subject) ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f6f9;font-family:'Segoe UI',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f9;padding:30px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

  <!-- Header -->
  <tr>
    <td style="background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%);border-radius:12px 12px 0 0;padding:28px 40px;text-align:center;">
      <h1 style="color:#fff;margin:0;font-size:20px;font-weight:700;">🏫 <?= $instituteName ?></h1>
      <p style="color:rgba(255,255,255,0.55);margin:4px 0 0;font-size:12px;">Official Communication</p>
    </td>
  </tr>
  <tr><td style="height:4px;background:linear-gradient(90deg,#3b82f6,#8b5cf6,#06b6d4);"></td></tr>

  <!-- Body -->
  <tr>
    <td style="background:#fff;padding:36px 40px;">
      <div style="color:#374151;font-size:15px;line-height:1.7;">
        <?= $message ?>
      </div>
    </td>
  </tr>

  <!-- Footer -->
  <tr>
    <td style="background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;padding:20px 40px;">
      <p style="color:#374151;font-size:13px;margin:0 0 8px;">
        <strong><?= $instituteName ?></strong>
      </p>
      <p style="color:#9ca3af;font-size:12px;margin:0;">
        <?php if ($instituteEmail): ?><?= $instituteEmail ?><?php endif; ?>
        <?php if ($institutePhone): ?> | <?= $institutePhone ?><?php endif; ?>
      </p>
      <p style="color:#d1d5db;font-size:11px;margin:8px 0 0;">This message was sent via the Student Management System.</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
