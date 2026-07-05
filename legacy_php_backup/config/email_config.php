<?php
/**
 * Email Configuration
 * ─────────────────────────────────────────────────────────────────────
 * Fill in your SMTP credentials before using the email system.
 *
 * For Gmail:
 *  1. Enable 2-Step Verification on your Google account
 *  2. Go to: Google Account → Security → App Passwords
 *  3. Generate an App Password for "Mail" → use it as EMAIL_PASS
 *
 * For other providers (Outlook, Yahoo, custom SMTP):
 *  Adjust HOST, PORT and ENCRYPTION accordingly.
 * ─────────────────────────────────────────────────────────────────────
 */

// ── SMTP Connection ──────────────────────────────────────────────────
define('EMAIL_HOST',       'smtp.gmail.com');   // Gmail SMTP host
define('EMAIL_PORT',       587);                // 587 = STARTTLS | 465 = SSL
define('EMAIL_ENCRYPTION', 'tls');              // 'tls' or 'ssl'

// ── Authentication ───────────────────────────────────────────────────
define('EMAIL_USERNAME', 'techv10sion@gmail.com');     // Your Gmail address
define('EMAIL_PASSWORD', 'rheh ezir xgjh ktoh');   // Gmail App Password (16 chars)

// ── Sender Identity ──────────────────────────────────────────────────
define('EMAIL_FROM_NAME',  'Student Management System');
define('EMAIL_FROM_EMAIL', 'techv10sion@gmail.com');   // Same as EMAIL_USERNAME for Gmail

// ── Feature Flags ────────────────────────────────────────────────────
define('EMAIL_ENABLED',                   true);   // Master on/off switch
define('EMAIL_LOG_ALL',                   true);   // Log every send attempt
define('EMAIL_TIMEOUT',                   15);     // SMTP socket timeout (seconds)
define('EMAIL_LOW_ATTENDANCE_THRESHOLD',  75);     // % below which low-attendance warning fires

// ── Debug (set to false in production) ──────────────────────────────
define('EMAIL_DEBUG', false);
