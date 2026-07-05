<?php
session_start();

// 1. Initialize $error at the very top so the HTML always finds it
$error = "";
$login_type = isset($_POST['login_type']) ? $_POST['login_type'] : 'admin'; // 'admin' or 'student'

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

include '../config/db_pdo.php';
include '../config/db.php';

// ──────────────────────────────────
// ADMIN/STAFF LOGIN
// ──────────────────────────────────
if (isset($_POST['login']) && $login_type === 'admin') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // ── Status check ─────────────────────────────────────────
            $status = $user['account_status'] ?? 'Approved'; // backward-compat default

            if ($status === 'Pending') {
                $error = "Your account is awaiting administrator approval. You will be notified by email once approved.";
            } elseif ($status === 'Rejected') {
                $error = "Your registration request has been rejected. Please contact the administrator.";
            } elseif ($status === 'Suspended') {
                $error = "Your account has been suspended. Please contact the administrator.";
            } else {
                // Approved — allow login
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                $_SESSION['role']     = $user['role'];
                header("Location: ../dashboard/dashboard.php");
                exit();
            }
        } else {
            $error = "Invalid email or password.";
        }
    }
}

// ──────────────────────────────────
// STUDENT LOGIN
// ──────────────────────────────────
if (isset($_POST['login']) && $login_type === 'student') {
    $student_name = trim($_POST['student_name']);
    $dob_input    = trim($_POST['dob_password']); // dob in format YYYYMMDD

    if (empty($student_name) || empty($dob_input)) {
        $error = "Please fill in all fields.";
    } else {
        // Query student by name
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_name = :name AND status = 'Active'");
        $stmt->execute([':name' => $student_name]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            // Format the student's DOB as YYYYMMDD and compare
            $stored_dob = date('Ymd', strtotime($student['dob']));
            
            if ($dob_input === $stored_dob) {
                // Login successful - create a student user session
                $_SESSION['user_id']     = $student['id'];
                $_SESSION['username']    = $student['student_name'];
                $_SESSION['email']       = $student['email'];
                $_SESSION['role']        = 'student';
                $_SESSION['student_id']  = $student['id'];
                header("Location: ../dashboard/dashboard.php");
                exit();
            } else {
                $error = "Invalid student name or date of birth.";
            }
        } else {
            $error = "Invalid student name or date of birth.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Login | Academic Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #1e293b;
            --brand-accent: #3b82f6;
            --bg-surface: #f8fafc;
            --input-bg: #ffffff;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #e2e8f0;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 24px 24px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 1000px;
            max-width: 95%;
            height: 600px;
            background: white;
            border-radius: 20px;
            display: flex;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .info-side {
            background-color: var(--brand-primary);
            width: 40%;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #fff;
            position: relative;
        }

        .portal-icon {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            color: var(--brand-accent);
        }

        .info-side h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .badge-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .badge-item {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.85rem;
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.05);
            padding: 10px 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-side {
            flex: 1;
            padding: 60px;
            background: var(--bg-surface);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .form-input {
            width: 100%;
            padding: 14px 14px 14px 52px;
            background: var(--input-bg);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input.is-invalid {
            border-color: #dc2626;
            background-color: #fef2f2;
        }

        .form-input.is-invalid:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }

        .btn-submit {
            width: 100%;
            background: var(--brand-primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .divider {
            margin: 30px 0;
            display: flex;
            align-items: center;
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .divider:not(:empty)::before { margin-right: .75em; }
        .divider:not(:empty)::after { margin-left: .75em; }
        
        .nav-tabs .nav-link {
            color: #64748b;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            padding: 12px 0 12px 0;
            margin-right: 30px;
            transition: all 0.3s ease;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--brand-accent);
            border-bottom-color: var(--brand-accent);
            background: none;
        }
        
        .nav-tabs .nav-link:hover {
            color: var(--brand-primary);
        }
        
        .nav-tabs .nav-link i {
            margin-right: 8px;
        }

        /* Tablet and smaller screens */
        @media (max-width: 1024px) {
            .login-card {
                width: 95%;
                max-width: 500px;
                height: auto;
                flex-direction: column;
            }

            .info-side {
                width: 100%;
                padding: 40px 30px;
            }

            .form-side {
                padding: 40px 30px;
            }

            .badge-list {
                display: none;
            }

            .info-side h1 {
                font-size: 1.5rem;
            }
        }

        /* Mobile screens */
        @media (max-width: 768px) {
            body {
                background-size: 16px 16px;
            }

            .login-card {
                width: 90%;
                border-radius: 15px;
            }

            .info-side {
                padding: 30px 20px;
            }

            .form-side {
                padding: 30px 20px;
            }

            .portal-icon {
                font-size: 2rem;
                margin-bottom: 1rem;
            }

            .info-side h1 {
                font-size: 1.3rem;
                margin-bottom: 0.5rem;
            }

            .info-side p {
                font-size: 0.9rem;
            }

            .header-area h2 {
                font-size: 1.5rem;
            }

            .nav-tabs .nav-link {
                margin-right: 15px;
                padding: 10px 0;
                font-size: 0.9rem;
            }

            .nav-tabs .nav-link i {
                margin-right: 4px;
            }

            .input-wrapper {
                margin-bottom: 16px;
            }

            .form-input {
                padding: 12px 12px 12px 45px;
                font-size: 0.95rem;
            }

            .btn-submit {
                padding: 12px;
                font-size: 0.95rem;
            }
        }

        /* Small mobile screens */
        @media (max-width: 576px) {
            .login-card {
                width: 95%;
                border-radius: 12px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }

            .info-side {
                padding: 25px 18px;
            }

            .form-side {
                padding: 25px 18px;
            }

            .portal-icon {
                font-size: 1.8rem;
            }

            .info-side h1 {
                font-size: 1.2rem;
            }

            .info-side p {
                font-size: 0.85rem;
                line-height: 1.4;
            }

            .header-area h2 {
                font-size: 1.3rem;
                margin-bottom: 0.75rem;
            }

            .header-area p {
                font-size: 0.85rem;
            }

            .nav-tabs {
                flex-wrap: wrap;
            }

            .nav-tabs .nav-link {
                margin-right: 10px;
                padding: 8px 0;
                font-size: 0.85rem;
            }

            .nav-tabs .nav-link i {
                font-size: 0.9rem;
            }

            .input-wrapper {
                margin-bottom: 14px;
            }

            .input-wrapper i {
                left: 14px;
                font-size: 0.95rem;
            }

            .form-input {
                padding: 10px 10px 10px 40px;
                font-size: 0.9rem;
                border-radius: 8px;
            }

            .btn-submit {
                padding: 10px;
                font-size: 0.9rem;
                border-radius: 8px;
                margin-top: 12px;
            }

            .alert {
                font-size: 0.8rem;
                padding: 8px 12px;
            }

            .divider {
                margin: 20px 0;
                font-size: 0.75rem;
            }

            .text-muted {
                font-size: 0.8rem;
            }
        }

        /* Extra small mobile screens */
        @media (max-width: 400px) {
            .login-card {
                border-radius: 10px;
            }

            .info-side {
                padding: 20px 15px;
            }

            .form-side {
                padding: 20px 15px;
            }

            .portal-icon {
                font-size: 1.5rem;
                margin-bottom: 0.8rem;
            }

            .info-side h1 {
                font-size: 1rem;
            }

            .info-side p {
                font-size: 0.8rem;
            }

            .header-area h2 {
                font-size: 1.1rem;
            }

            .nav-tabs .nav-link {
                font-size: 0.8rem;
                margin-right: 8px;
            }

            .form-input {
                padding: 9px 9px 9px 36px;
                font-size: 0.85rem;
            }

            .btn-submit {
                padding: 9px;
                font-size: 0.85rem;
            }
        }

        /* ── Forgot Password Modal Styles ── */
        .fp-modal .modal-content {
            border: none;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.18);
        }
        .fp-modal .modal-header {
            background: linear-gradient(135deg, #1e293b, #334155);
            color: #fff;
            border: none;
            padding: 22px 28px;
        }
        .fp-modal .modal-header .btn-close {
            filter: invert(1);
            opacity: 0.7;
        }
        .fp-modal .modal-body {
            padding: 28px;
            background: #f8fafc;
        }
        .fp-step { display: none; }
        .fp-step.active { display: block; animation: fpFadeIn 0.35s ease; }
        @keyframes fpFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fp-otp-box {
            letter-spacing: 8px;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            font-family: monospace;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            background: #fff;
            width: 100%;
        }
        .fp-otp-box:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
            outline: none;
        }
        .fp-progress {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 24px;
        }
        .fp-progress-step {
            flex: 1;
            height: 4px;
            border-radius: 4px;
            background: #e2e8f0;
            transition: background 0.3s ease;
        }
        .fp-progress-step.done { background: #22c55e; }
        .fp-progress-step.active { background: #3b82f6; }
        .fp-msg {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            display: none;
        }
        .fp-msg.show { display: block; }
        .fp-msg.success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .fp-msg.error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .fp-btn {
            width: 100%;
            padding: 13px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            transition: opacity 0.2s;
            background: #1e293b;
            color: #fff;
        }
        .fp-btn:disabled { opacity: 0.65; cursor: not-allowed; }
        .fp-btn-secondary {
            background: none;
            border: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 0.85rem;
            padding: 8px;
            border-radius: 8px;
            width: 100%;
            margin-top: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .fp-btn-secondary:hover { background: #f1f5f9; color: #1e293b; }
        .fp-label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            display: block;
        }
        .fp-input {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }
        .fp-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .fp-success-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
        }
        .fp-timer { font-size: 12px; color: #94a3b8; text-align: right; }
        .fp-timer span { font-weight: 700; color: #ef4444; }
    </style>
</head>
<body>

<div class="login-card">
    <div class="info-side">
        <div class="portal-icon"><i class="bi bi-layers-half"></i></div>
        <h1>Academic <br>Management</h1>
        <p>A unified portal for educational administration and student records.</p>
        
        <div class="badge-list">
            <div class="badge-item"><i class="bi bi-shield-check"></i> Secure Authentication</div>
            <div class="badge-item"><i class="bi bi-lightning-charge"></i> Real-time Dashboard</div>
        </div>
    </div>

    <div class="form-side">
        <div class="header-area">
            <h2 class="mb-3">Welcome back</h2>
            
            <!-- Login Type Tabs -->
            <ul class="nav nav-tabs mb-4" id="loginTabs" role="tablist" style="border-bottom: 2px solid #e2e8f0;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $login_type === 'admin' ? 'active' : '' ?>" id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-pane" type="button" role="tab" aria-controls="admin-pane" aria-selected="<?= $login_type === 'admin' ? 'true' : 'false' ?>" onclick="document.getElementById('login_type_hidden').value='admin'; document.getElementById('form_subtitle').innerText='Enter your email and password.';">
                        <i class="bi bi-shield-lock"></i> Admin/Staff
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $login_type === 'student' ? 'active' : '' ?>" id="student-tab" data-bs-toggle="tab" data-bs-target="#student-pane" type="button" role="tab" aria-controls="student-pane" aria-selected="<?= $login_type === 'student' ? 'true' : 'false' ?>" onclick="document.getElementById('login_type_hidden').value='student'; document.getElementById('form_subtitle').innerText='Enter your name and date of birth.';">
                        <i class="bi bi-mortarboard"></i> Student
                    </button>
                </li>
            </ul>
            
            <p class="text-muted" id="form_subtitle">
                <?= $login_type === 'student' ? 'Enter your name and date of birth.' : 'Enter your email and password.' ?>
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 py-2 mb-4" style="background:#fef2f2; color:#b91c1c; border-radius:10px; font-size:0.85rem;">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <input type="hidden" id="login_type_hidden" name="login_type" value="<?= $login_type ?>">
            
            <!-- Tab Content -->
            <div class="tab-content" id="loginTabContent">
                <!-- Admin/Staff Login Tab -->
                <div class="tab-pane fade <?= $login_type === 'admin' ? 'show active' : '' ?>" id="admin-pane" role="tabpanel" aria-labelledby="admin-tab">
                    <div class="input-wrapper">
                        <i class="bi bi-person-circle"></i>
                        <input type="email" name="email" id="email-input" class="form-input" 
                               placeholder="Email Address" 
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>

                    <div class="input-wrapper">
                        <i class="bi bi-key"></i>
                        <input type="password" name="password" id="password-input" class="form-input" 
                               placeholder="Password">
                    </div>
                    <div class="text-end mt-1" id="forgot-pw-link-wrap">
                        <a href="#" id="forgotPwLink" class="text-decoration-none" style="font-size:0.82rem;color:var(--brand-accent);font-weight:500;">
                            <i class="bi bi-lock me-1"></i>Forgot Password?
                        </a>
                    </div>
                </div>
                
                <!-- Student Login Tab -->
                <div class="tab-pane fade <?= $login_type === 'student' ? 'show active' : '' ?>" id="student-pane" role="tabpanel" aria-labelledby="student-tab">
                    <div class="input-wrapper">
                        <i class="bi bi-mortarboard"></i>
                        <input type="text" name="student_name" id="student-name-input" class="form-input" 
                               placeholder="Full Name (as in records)">
                    </div>

                    <div class="input-wrapper">
                        <i class="bi bi-calendar"></i>
                        <input type="text" name="dob_password" id="dob-input" class="form-input" 
                               placeholder="DOB (YYYYMMDD format, e.g., 20050115)" 
                               pattern="\d{8}"
                               title="Please enter date in YYYYMMDD format (e.g., 20050115)">
                    </div>
                    
                    <small class="text-muted d-block mt-2">
                        <i class="bi bi-info-circle"></i> Enter your date of birth without any special characters (YYYYMMDD)
                    </small>
                </div>
            </div>

            <button type="submit" name="login" class="btn-submit mt-4">Sign In</button>
        </form>

        <!-- Register link only for Admin/Staff -->
        <div id="register-section" style="display: <?= $login_type === 'admin' ? 'block' : 'none' ?>;">
            <div class="divider">New User?</div>
            <div class="text-center mt-3">
                <small class="text-muted">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold" style="color:var(--brand-accent)">Register here</a></small>
            </div>
        </div>
    </div>
</div>

<!-- ══ Forgot Password Modal ══ -->
<div class="modal fade fp-modal" id="forgotPwModal" tabindex="-1" aria-labelledby="forgotPwModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0" id="forgotPwModalLabel">
            <i class="bi bi-shield-lock me-2"></i>Reset Password
          </h5>
          <p style="font-size:12px;color:rgba(255,255,255,0.6);margin:4px 0 0;">For Admin &amp; Staff accounts only</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <!-- Progress Bar -->
        <div class="fp-progress">
          <div class="fp-progress-step active" id="fpBar1"></div>
          <div class="fp-progress-step" id="fpBar2"></div>
          <div class="fp-progress-step" id="fpBar3"></div>
        </div>

        <!-- Message Box -->
        <div class="fp-msg" id="fpMsg"></div>

        <!-- ── Step 1: Email ── -->
        <div class="fp-step active" id="fpStep1">
          <div style="text-align:center;margin-bottom:20px;">
            <div style="font-size:36px;margin-bottom:8px;">📧</div>
            <h6 style="font-weight:700;color:#1e293b;margin:0;">Enter your email address</h6>
            <p style="font-size:13px;color:#64748b;margin:6px 0 0;">We'll send a one-time password to verify your identity.</p>
          </div>
          <label class="fp-label" for="fpEmail">Email Address</label>
          <input type="email" id="fpEmail" class="fp-input" placeholder="admin@example.com" autocomplete="email">
          <button class="fp-btn mt-4" id="fpSendOtpBtn" onclick="fpSendOtp()">
            <span id="fpSendOtpTxt"><i class="bi bi-send me-2"></i>Send OTP</span>
          </button>
        </div>

        <!-- ── Step 2: OTP ── -->
        <div class="fp-step" id="fpStep2">
          <div style="text-align:center;margin-bottom:20px;">
            <div style="font-size:36px;margin-bottom:8px;">🔑</div>
            <h6 style="font-weight:700;color:#1e293b;margin:0;">Enter the OTP</h6>
            <p style="font-size:13px;color:#64748b;margin:6px 0 0;">Check your inbox for the 6-digit code.</p>
          </div>
          <label class="fp-label" for="fpOtp">One-Time Password</label>
          <input type="text" id="fpOtp" class="fp-otp-box" placeholder="_ _ _ _ _ _" maxlength="6" inputmode="numeric" autocomplete="one-time-code">
          <div class="fp-timer mt-2" id="fpTimerWrap">Expires in: <span id="fpTimer">10:00</span></div>
          <button class="fp-btn mt-4" id="fpVerifyOtpBtn" onclick="fpVerifyOtp()">
            <span id="fpVerifyOtpTxt"><i class="bi bi-check2-circle me-2"></i>Verify OTP</span>
          </button>
          <button class="fp-btn-secondary" onclick="fpResendOtp()"><i class="bi bi-arrow-clockwise me-1"></i>Resend OTP</button>
        </div>

        <!-- ── Step 3: New Password ── -->
        <div class="fp-step" id="fpStep3">
          <div style="text-align:center;margin-bottom:20px;">
            <div style="font-size:36px;margin-bottom:8px;">🔒</div>
            <h6 style="font-weight:700;color:#1e293b;margin:0;">Set New Password</h6>
            <p style="font-size:13px;color:#64748b;margin:6px 0 0;">Choose a strong password (min. 8 characters).</p>
          </div>
          <label class="fp-label" for="fpNewPw">New Password</label>
          <input type="password" id="fpNewPw" class="fp-input" placeholder="At least 8 characters">
          <label class="fp-label mt-3" for="fpConfirmPw">Confirm Password</label>
          <input type="password" id="fpConfirmPw" class="fp-input" placeholder="Repeat new password">
          <button class="fp-btn mt-4" id="fpResetBtn" onclick="fpResetPassword()">
            <span id="fpResetTxt"><i class="bi bi-arrow-repeat me-2"></i>Reset Password</span>
          </button>
        </div>

        <!-- ── Step 4: Success ── -->
        <div class="fp-step" id="fpStep4">
          <div style="text-align:center;padding:10px 0;">
            <div class="fp-success-icon">✅</div>
            <h6 style="font-weight:700;color:#1e293b;margin-bottom:8px;">Password Reset!</h6>
            <p style="font-size:13.5px;color:#475569;margin-bottom:24px;">Your password has been updated successfully.<br>You can now sign in with your new password.</p>
            <button class="fp-btn" onclick="fpDone()"><i class="bi bi-box-arrow-in-right me-2"></i>Go to Login</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Handle tab switching
const adminTab = document.getElementById('admin-tab');
const studentTab = document.getElementById('student-tab');
const loginTypeHidden = document.getElementById('login_type_hidden');
const formSubtitle = document.getElementById('form_subtitle');
const emailInput = document.getElementById('email-input');
const passwordInput = document.getElementById('password-input');
const studentNameInput = document.getElementById('student-name-input');
const dobInput = document.getElementById('dob-input');
const loginForm = document.getElementById('loginForm');

function updateLoginType(type) {
    loginTypeHidden.value = type;
    
    // Toggle register section visibility
    const registerSection = document.getElementById('register-section');
    if (type === 'admin') {
        registerSection.style.display = 'block';
    } else {
        registerSection.style.display = 'none';
    }
    
    // Clear previous errors
    const inputs = loginForm.querySelectorAll('input');
    inputs.forEach(inp => inp.classList.remove('is-invalid'));
    
    if (type === 'admin') {
        formSubtitle.innerText = 'Enter your email and password.';
        // Set required on admin fields
        emailInput.required = true;
        passwordInput.required = true;
        studentNameInput.required = false;
        dobInput.required = false;
        // Clear student fields
        studentNameInput.value = '';
        dobInput.value = '';
    } else {
        formSubtitle.innerText = 'Enter your name and date of birth.';
        // Set required on student fields
        emailInput.required = false;
        passwordInput.required = false;
        studentNameInput.required = true;
        dobInput.required = true;
        // Clear admin fields
        emailInput.value = '';
        passwordInput.value = '';
    }
}

adminTab.addEventListener('click', function() {
    updateLoginType('admin');
});

studentTab.addEventListener('click', function() {
    updateLoginType('student');
});

// Form validation on submit
loginForm.addEventListener('submit', function(e) {
    const loginType = loginTypeHidden.value;
    let isValid = true;

    // Clear previous validation states
    loginForm.querySelectorAll('input').forEach(inp => inp.classList.remove('is-invalid'));

    if (loginType === 'admin') {
        // Validate admin fields
        if (!emailInput.value.trim()) {
            emailInput.classList.add('is-invalid');
            isValid = false;
        }
        if (!passwordInput.value.trim()) {
            passwordInput.classList.add('is-invalid');
            isValid = false;
        }
    } else {
        // Validate student fields
        if (!studentNameInput.value.trim()) {
            studentNameInput.classList.add('is-invalid');
            isValid = false;
        }
        if (!dobInput.value.trim()) {
            dobInput.classList.add('is-invalid');
            isValid = false;
        } else if (!/^\d{8}$/.test(dobInput.value.trim())) {
            dobInput.classList.add('is-invalid');
            dobInput.nextElementSibling.innerText = 'Please enter DOB in YYYYMMDD format (e.g., 20050115)';
            isValid = false;
        }
    }

    if (!isValid) {
        e.preventDefault();
    }
});

// Initialize with current login type
updateLoginType('<?= $login_type ?>');

// ── Forgot Password — open modal on link click ────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    const fpLink = document.getElementById('forgotPwLink');
    if (fpLink) {
        fpLink.addEventListener('click', function (e) {
            e.preventDefault();
            fpReset();
            const modal = new bootstrap.Modal(document.getElementById('forgotPwModal'));
            modal.show();
        });
    }

    // Show/hide "Forgot Password?" link based on active tab
    const adminTabBtn   = document.getElementById('admin-tab');
    const studentTabBtn = document.getElementById('student-tab');
    const fpWrap        = document.getElementById('forgot-pw-link-wrap');

    function updateFpLinkVisibility(type) {
        if (fpWrap) fpWrap.style.display = (type === 'admin') ? 'block' : 'none';
    }

    if (adminTabBtn)   adminTabBtn.addEventListener('click',   () => updateFpLinkVisibility('admin'));
    if (studentTabBtn) studentTabBtn.addEventListener('click', () => updateFpLinkVisibility('student'));

    // Reset modal state when it's closed
    document.getElementById('forgotPwModal').addEventListener('hidden.bs.modal', function () {
        fpReset();
    });
});

// ── Forgot Password State ─────────────────────────────────────────────
let fpCurrentEmail = '';
let fpTimerInterval = null;

function fpReset() {
    fpCurrentEmail = '';
    clearInterval(fpTimerInterval);
    fpShowStep(1);
    fpSetMsg('', '');
    document.getElementById('fpEmail').value = '';
    document.getElementById('fpOtp').value = '';
    document.getElementById('fpNewPw').value = '';
    document.getElementById('fpConfirmPw').value = '';
    fpSetProgress(1);
}

function fpShowStep(n) {
    [1, 2, 3, 4].forEach(function (i) {
        const el = document.getElementById('fpStep' + i);
        if (el) el.classList.remove('active');
    });
    const target = document.getElementById('fpStep' + n);
    if (target) target.classList.add('active');
}

function fpSetProgress(step) {
    const bars = [
        document.getElementById('fpBar1'),
        document.getElementById('fpBar2'),
        document.getElementById('fpBar3'),
    ];
    bars.forEach(function (bar, i) {
        if (!bar) return;
        bar.classList.remove('active', 'done');
        if (i + 1 < step)       bar.classList.add('done');
        else if (i + 1 === step) bar.classList.add('active');
    });
}

function fpSetMsg(text, type) {
    const el = document.getElementById('fpMsg');
    el.className = 'fp-msg';
    if (!text) { el.style.display = 'none'; return; }
    el.classList.add('show', type);
    el.innerHTML = (type === 'success' ? '✅ ' : '⚠️ ') + text;
    el.style.display = 'block';
}

function fpSetLoading(btnId, txtId, loading, label) {
    const btn = document.getElementById(btnId);
    const txt = document.getElementById(txtId);
    if (!btn || !txt) return;
    btn.disabled = loading;
    txt.innerHTML = loading
        ? '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Please wait…'
        : label;
}

// ── Step 1: Send OTP ──────────────────────────────────────────────────
function fpSendOtp() {
    const email = document.getElementById('fpEmail').value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        fpSetMsg('Please enter a valid email address.', 'error');
        return;
    }
    fpSetMsg('', '');
    fpSetLoading('fpSendOtpBtn', 'fpSendOtpTxt', true, '');

    const fd = new FormData();
    fd.append('action', 'send_otp');
    fd.append('email', email);

    fetch('forgot_password.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function (res) {
            fpSetLoading('fpSendOtpBtn', 'fpSendOtpTxt', false, '<i class="bi bi-send me-2"></i>Send OTP');
            if (res.success) {
                fpCurrentEmail = email;
                fpSetMsg(res.message, 'success');
                setTimeout(function () {
                    fpSetMsg('', '');
                    fpShowStep(2);
                    fpSetProgress(2);
                    fpStartTimer(600);
                    document.getElementById('fpOtp').focus();
                }, 1200);
            } else {
                fpSetMsg(res.message || 'An error occurred.', 'error');
            }
        })
        .catch(function () {
            fpSetLoading('fpSendOtpBtn', 'fpSendOtpTxt', false, '<i class="bi bi-send me-2"></i>Send OTP');
            fpSetMsg('Network error. Please try again.', 'error');
        });
}

// ── Timer ─────────────────────────────────────────────────────────────
function fpStartTimer(seconds) {
    clearInterval(fpTimerInterval);
    const timerEl = document.getElementById('fpTimer');
    let remaining = seconds;

    function tick() {
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        if (timerEl) timerEl.textContent = m + ':' + (s < 10 ? '0' : '') + s;
        if (remaining <= 0) {
            clearInterval(fpTimerInterval);
            if (timerEl) timerEl.textContent = 'Expired';
            fpSetMsg('OTP has expired. Please request a new one.', 'error');
        }
        remaining--;
    }
    tick();
    fpTimerInterval = setInterval(tick, 1000);
}

// ── Step 1 resend ─────────────────────────────────────────────────────
function fpResendOtp() {
    clearInterval(fpTimerInterval);
    fpShowStep(1);
    fpSetProgress(1);
    fpSetMsg('', '');
}

// ── Step 2: Verify OTP ────────────────────────────────────────────────
function fpVerifyOtp() {
    const otp = document.getElementById('fpOtp').value.trim();
    if (!otp || !/^\d{6}$/.test(otp)) {
        fpSetMsg('Please enter the 6-digit OTP sent to your email.', 'error');
        return;
    }
    fpSetMsg('', '');
    fpSetLoading('fpVerifyOtpBtn', 'fpVerifyOtpTxt', true, '');

    const fd = new FormData();
    fd.append('action', 'verify_otp');
    fd.append('email', fpCurrentEmail);
    fd.append('otp', otp);

    fetch('forgot_password.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function (res) {
            fpSetLoading('fpVerifyOtpBtn', 'fpVerifyOtpTxt', false, '<i class="bi bi-check2-circle me-2"></i>Verify OTP');
            if (res.success) {
                clearInterval(fpTimerInterval);
                fpSetMsg(res.message, 'success');
                setTimeout(function () {
                    fpSetMsg('', '');
                    fpShowStep(3);
                    fpSetProgress(3);
                    document.getElementById('fpNewPw').focus();
                }, 1000);
            } else {
                fpSetMsg(res.message || 'Verification failed.', 'error');
            }
        })
        .catch(function () {
            fpSetLoading('fpVerifyOtpBtn', 'fpVerifyOtpTxt', false, '<i class="bi bi-check2-circle me-2"></i>Verify OTP');
            fpSetMsg('Network error. Please try again.', 'error');
        });
}

// ── Step 3: Reset Password ────────────────────────────────────────────
function fpResetPassword() {
    const pw   = document.getElementById('fpNewPw').value;
    const pw2  = document.getElementById('fpConfirmPw').value;

    if (pw.length < 8) {
        fpSetMsg('Password must be at least 8 characters long.', 'error');
        return;
    }
    if (pw !== pw2) {
        fpSetMsg('Passwords do not match.', 'error');
        return;
    }
    fpSetMsg('', '');
    fpSetLoading('fpResetBtn', 'fpResetTxt', true, '');

    const fd = new FormData();
    fd.append('action', 'reset_password');
    fd.append('password', pw);
    fd.append('confirm_password', pw2);

    fetch('forgot_password.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function (res) {
            fpSetLoading('fpResetBtn', 'fpResetTxt', false, '<i class="bi bi-arrow-repeat me-2"></i>Reset Password');
            if (res.success) {
                fpSetProgress(4);
                fpShowStep(4);
            } else {
                fpSetMsg(res.message || 'Password reset failed.', 'error');
            }
        })
        .catch(function () {
            fpSetLoading('fpResetBtn', 'fpResetTxt', false, '<i class="bi bi-arrow-repeat me-2"></i>Reset Password');
            fpSetMsg('Network error. Please try again.', 'error');
        });
}

// ── Step 4: Done ──────────────────────────────────────────────────────
function fpDone() {
    bootstrap.Modal.getInstance(document.getElementById('forgotPwModal')).hide();
    fpReset();
}

// Allow pressing Enter in OTP field to submit
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('fpOtp').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') fpVerifyOtp();
    });
    document.getElementById('fpEmail').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') fpSendOtp();
    });
    document.getElementById('fpConfirmPw').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') fpResetPassword();
    });
});
</script>
</body>
</html>