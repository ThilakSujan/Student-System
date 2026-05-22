<?php
session_start();

// If user is logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/dashboard.php");
    exit();
}

// If not logged in, redirect to login
header("Location: auth/login.php");
exit();
