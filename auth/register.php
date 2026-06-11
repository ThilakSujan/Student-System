<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

include '../config/db_pdo.php';
include '../config/db.php';       // $mysqli for EmailService

$error   = "";
$success = "";
$isPending = false; // tracks whether to show pending message vs instant-login message

if (isset($_POST['register'])) {
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $email, ':username' => $username]);

        if ($stmt->rowCount() > 0) {
            $error = "Username or email already exists.";
        } else {
            $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
            $userCount = $countStmt->fetchColumn();

            // First user gets admin + auto-approved; subsequent users get pending
            $isFirstUser     = ($userCount == 0);
            $role            = $isFirstUser ? 'admin' : 'staff';
            $account_status  = $isFirstUser ? 'Approved' : 'Pending';
            $hashed          = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $pdo->prepare(
                "INSERT INTO users (username, email, password, role, account_status)
                 VALUES (:u, :e, :p, :r, :s)"
            );
            $insertStmt->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => $hashed,
                ':r' => $role,
                ':s' => $account_status,
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Send notification email
            try {
                require_once '../includes/email_service.php';
                $emailSvc = new EmailService($mysqli);
                if ($isFirstUser) {
                    // Admin needs no pending email — they can log in immediately
                } else {
                    $emailSvc->sendRegistrationPending($email, $username);
                }
            } catch (Throwable $e) {
                // Email failure must never break registration
            }

            // Audit log
            $logDir = __DIR__ . '/logs';
            if (!is_dir($logDir)) mkdir($logDir, 0755, true);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            file_put_contents(
                $logDir . '/approval.log',
                '[' . date('Y-m-d H:i:s') . "] [$ip] REGISTRATION_SUBMITTED | user_id=$newUserId | username=$username | email=$email | status=$account_status\n",
                FILE_APPEND | LOCK_EX
            );

            if ($isFirstUser) {
                $success = "Admin account created successfully! You can now sign in.";
                $isPending = false;
            } else {
                $success   = "Registration submitted successfully. Your account is pending administrator approval. You will be able to login after approval.";
                $isPending = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration | Academic Portal</title>
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
            height: auto; /* Allow growth for the longer register form */
            min-height: 650px;
            background: white;
            border-radius: 20px;
            display: flex;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        /* ── Info Side ── */
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

        .info-side p {
            color: #94a3b8;
            font-weight: 300;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .badge-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .badge-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 0.82rem;
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.05);
            padding: 12px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* ── Form Side ── */
        .form-side {
            flex: 1;
            padding: 50px 60px;
            background: var(--bg-surface);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .header-area h2 {
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        .header-area p {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }

        .input-wrapper {
            position: relative;
            margin-bottom: 15px;
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
            padding: 12px 12px 12px 52px;
            background: var(--input-bg);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--brand-accent);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .btn-submit {
            width: 100%;
            background: var(--brand-primary);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 10px;
            transition: 0.2s;
            cursor: pointer;
        }

        .btn-submit:hover {
            background: #0f172a;
            transform: translateY(-1px);
        }

        .divider {
            margin: 25px 0;
            display: flex;
            align-items: center;
            text-align: center;
            color: #94a3b8;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .divider:not(:empty)::before { margin-right: .75em; }
        .divider:not(:empty)::after { margin-left: .75em; }

        .footer-text {
            text-align: center;
            font-size: 0.9rem;
            color: #64748b;
        }

        .footer-text a {
            color: var(--brand-accent);
            text-decoration: none;
            font-weight: 600;
        }

        /* Tablet and smaller screens */
        @media (max-width: 1024px) {
            .login-card {
                width: 95%;
                max-width: 500px;
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
                margin-bottom: 0.5rem;
            }

            .header-area p {
                font-size: 0.9rem;
                margin-bottom: 20px;
            }

            .input-wrapper {
                margin-bottom: 14px;
            }

            .form-input {
                padding: 12px 12px 12px 45px;
                font-size: 0.95rem;
            }

            .btn-submit {
                padding: 12px;
                font-size: 0.95rem;
                margin-top: 8px;
            }

            .divider {
                margin: 20px 0;
                font-size: 0.8rem;
            }

            .footer-text {
                font-size: 0.85rem;
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
                margin-bottom: 1.5rem;
            }

            .header-area h2 {
                font-size: 1.3rem;
                margin-bottom: 0.75rem;
            }

            .header-area p {
                font-size: 0.85rem;
                margin-bottom: 15px;
            }

            .input-wrapper {
                margin-bottom: 12px;
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
                margin-top: 6px;
            }

            .alert {
                font-size: 0.8rem;
                padding: 8px 12px;
            }

            .divider {
                margin: 18px 0;
                font-size: 0.75rem;
            }

            .footer-text {
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

            .header-area p {
                font-size: 0.8rem;
            }

            .input-wrapper {
                margin-bottom: 10px;
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
    </style>
</head>
<body>

<div class="login-card">
    <div class="info-side">
        <div class="portal-icon">
            <i class="bi bi-person-plus-fill"></i>
        </div>
        <h1>Join the <br>Portal</h1>
        <p>Complete the registration to access the integrated academic ecosystem.</p>
        
        <div class="badge-list">
            <div class="badge-item">
                <i class="bi bi-info-circle-fill text-primary"></i>
                <span>First User Policy: The first account created assumes <strong>Admin</strong> privileges automatically.</span>
            </div>
            <div class="badge-item">
                <i class="bi bi-people-fill text-info"></i>
                <span>Student Default: Standard registration applies the <strong>Student</strong> role to all subsequent users.</span>
            </div>
        </div>
    </div>

    <div class="form-side">
        <div class="header-area">
            <h2>Create Account</h2>
            <p>Start your journey by setting up your profile.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 py-2 mb-3" style="background:#fef2f2; color:#b91c1c; border-radius:10px; font-size:0.85rem;">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <?php if ($isPending): ?>
                <div class="mb-3" style="background:#fffbeb;border:1px solid #fcd34d;border-radius:10px;padding:14px 18px;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-hourglass-split" style="color:#d97706;font-size:1.2rem;margin-top:2px;flex-shrink:0;"></i>
                        <div>
                            <div style="font-weight:700;color:#92400e;font-size:0.9rem;margin-bottom:4px;">Registration Submitted!</div>
                            <div style="color:#92400e;font-size:0.83rem;line-height:1.5;"><?= htmlspecialchars($success) ?></div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-success border-0 py-2 mb-3" style="background:#f0fdf4; color:#15803d; border-radius:10px; font-size:0.85rem;">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                    <a href="login.php" class="fw-bold text-decoration-none ms-1">Login here →</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="input-wrapper">
                <i class="bi bi-person"></i>
                <input type="text" name="username" class="form-input" 
                       placeholder="Choose Username" 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
            </div>

            <div class="input-wrapper">
                <i class="bi bi-envelope"></i>
                <input type="email" name="email" class="form-input" 
                       placeholder="Email Address" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>

            <div class="input-wrapper">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" class="form-input" 
                       placeholder="Password (Min. 6 characters)" required>
            </div>

            <div class="input-wrapper">
                <i class="bi bi-shield-lock"></i>
                <input type="password" name="confirm_password" class="form-input" 
                       placeholder="Confirm Password" required>
            </div>

            <button type="submit" name="register" class="btn-submit">
                Register Account
            </button>
        </form>

        <div class="divider">Already Registered?</div>

        <div class="footer-text">
            Have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
</div>

</body>
</html>