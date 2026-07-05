<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function require_role(array $roles, $redirect = 'dashboard.php') {
    require_login();
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        header("Location: $redirect");
        exit();
    }
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_staff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function is_student() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function current_user_email() {
    return $_SESSION['email'] ?? null;
}
