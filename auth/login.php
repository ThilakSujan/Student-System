<?php
session_start();

// 1. Initialize $error at the very top so the HTML always finds it
$error = "";

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

include '../config/db_pdo.php';

if (isset($_POST['login'])) {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];
            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password.";
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

        @media (max-width: 900px) {
            .login-card { flex-direction: column; height: auto; width: 450px; }
            .info-side { width: 100%; padding: 40px; }
            .badge-list { display: none; }
        }
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
            <h2 class="mb-2">Welcome back</h2>
            <p class="text-muted">Enter your credentials to access your account.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger border-0 py-2 mb-4" style="background:#fef2f2; color:#b91c1c; border-radius:10px; font-size:0.85rem;">
                <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <div class="input-wrapper">
                <i class="bi bi-person-circle"></i>
                <input type="email" name="email" class="form-input" 
                       placeholder="Email Address" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
            </div>

            <div class="input-wrapper">
                <i class="bi bi-key"></i>
                <input type="password" name="password" class="form-input" 
                       placeholder="Password" required>
            </div>

            <button type="submit" name="login" class="btn-submit">Sign In</button>
        </form>

        <div class="divider">Development Access</div>

        <div class="text-center mt-3">
            <small class="text-muted">Don't have an account? <a href="register.php" class="text-decoration-none fw-bold" style="color:var(--brand-accent)">Register</a></small>
        </div>
    </div>
</div>

</body>
</html>