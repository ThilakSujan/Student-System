<?php
session_start();

// If already logged in, go straight to the dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

include '../config/db_pdo.php';

$error = "";

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
    <title>Login – Student System</title>
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
            overflow: hidden;
        }

        /* Animated background grid */
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

        /* Glowing orbs */
        body::after {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(79,142,247,0.12) 0%, transparent 70%);
            top: -150px;
            right: -100px;
            z-index: 0;
            pointer-events: none;
        }

        .orb2 {
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(124,92,252,0.10) 0%, transparent 70%);
            bottom: -100px;
            left: -80px;
            z-index: 0;
            pointer-events: none;
        }

        .card-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
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
            color: var(--accent);
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .brand-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 28px;
            line-height: 1.2;
            margin-bottom: 6px;
            color: var(--text);
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
        }

        .form-control {
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
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(79,142,247,0.15);
            color: var(--text);
            outline: none;
        }

        .form-control::placeholder { color: #3a3f52; }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
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

        .btn-login:hover  { opacity: 0.9; transform: translateY(-1px); }
        .btn-login:active { transform: translateY(0); }

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

        .register-link {
            text-align: center;
            font-size: 14px;
            color: var(--muted);
        }

        .register-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover { text-decoration: underline; }

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

        <h1>Welcome back</h1>
        <p class="subtitle">Sign in to manage student records</p>

        <?php if ($error): ?>
            <div class="alert-error">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <path d="M12 8v4m0 4h.01" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">

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
                    placeholder="••••••••"
                    required
                >
            </div>

            <button type="submit" name="login" class="btn-login">Sign In</button>

        </form>

        <div class="divider">or</div>

        <div class="register-link">
            Don't have an account? <a href="register.php">Create one</a>
        </div>

    </div>
</div>

</body>
</html>