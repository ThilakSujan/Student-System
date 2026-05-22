<?php
session_start();

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    header("Location: students.php");
    exit();
}

include 'db_pdo.php';

$error   = "";
$success = "";

if (isset($_POST['register'])) {
    $username        = trim($_POST['username']);
    $email           = trim($_POST['email']);
    $password        = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email or username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
        $stmt->execute([':email' => $email, ':username' => $username]);

        if ($stmt->rowCount() > 0) {
            $error = "Username or email already exists.";
        } else {
            // First registered user becomes admin, rest are students
            $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
            $userCount = $countStmt->fetchColumn();
            $role      = ($userCount == 0) ? 'admin' : 'student';

            // Hash password and insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)");
            $stmt->execute([
                ':username' => $username,
                ':email'    => $email,
                ':password' => $hashed,
                ':role'     => $role
            ]);
            $success = "Account created! You can now sign in. New accounts register as Student by default.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – Student System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #0d0f14;
            --panel:     #13161d;
            --border:    #1f2330;
            --accent:    #4f8ef7;
            --accent2:   #7c5cfc;
            --text:      #e8eaf0;
            --muted:     #6b7280;
            --danger:    #f25757;
            --success:   #34d399;
            --input-bg:  #1a1e28;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 0;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 48px 48px;
            opacity: 0.4;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(124,92,252,0.12) 0%, transparent 70%);
            bottom: -150px;
            right: -100px;
            z-index: 0;
            pointer-events: none;
        }

        .orb2 {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(79,142,247,0.10) 0%, transparent 70%);
            top: -100px;
            left: -80px;
            z-index: 0;
            pointer-events: none;
        }

        .card-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
            padding: 16px;
            animation: slideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(32px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 44px 40px;
            box-shadow: 0 32px 64px rgba(0,0,0,0.5);
        }

        .brand {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--accent2);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent2), var(--accent));
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 28px;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 32px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 6px;
            letter-spacing: 0.04em;
            display: block;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            padding: 11px 14px;
            font-size: 14px;
            font-family: 'DM Sans', sans-serif;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            background: var(--input-bg);
            border-color: var(--accent2);
            box-shadow: 0 0 0 3px rgba(124,92,252,0.15);
            color: var(--text);
            outline: none;
        }

        .form-control::placeholder { color: #3a3f52; }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--accent2) 0%, var(--accent) 100%);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 15px;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.15s;
            margin-top: 8px;
        }

        .btn-register:hover  { opacity: 0.9; transform: translateY(-1px); }
        .btn-register:active { transform: translateY(0); }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--muted);
            font-size: 12px;
        }
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .login-link {
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }

        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover { text-decoration: underline; }

        .alert-error {
            background: rgba(242,87,87,0.1);
            border: 1px solid rgba(242,87,87,0.3);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: var(--danger);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: rgba(52,211,153,0.1);
            border: 1px solid rgba(52,211,153,0.3);
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            color: var(--success);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mb-3 { margin-bottom: 18px; }
    </style>
</head>
<body>
<div class="orb2"></div>

<div class="card-wrap">
    <div class="card">

        <div class="brand">
            <div class="brand-dot"></div>
            Student System
        </div>

        <h1>Create account</h1>
        <p class="subtitle">Register to get access to the system</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert-success">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M9 12l2 2 4-4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                </svg>
                <?php echo htmlspecialchars($success); ?>
                <a href="login.php" style="color:var(--success); margin-left:6px; font-weight:600;">Sign in →</a>
            </div>
        <?php endif; ?>

        <form action="register.php" method="POST">

            <div class="mb-3">
                <label class="form-label">Username</label>
                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="yourname"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="you@example.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Min. 6 characters"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input
                    type="password"
                    name="confirm_password"
                    class="form-control"
                    placeholder="Repeat your password"
                    required
                >
            </div>

            <button type="submit" name="register" class="btn-register">Create Account</button>

        </form>

        <div class="divider">or</div>

        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>

    </div>
</div>

</body>
</html>