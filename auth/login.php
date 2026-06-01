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
</script>
</body>
</html>