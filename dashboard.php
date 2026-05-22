<?php
require_once 'includes/auth.php';
require_login();
include 'db.php';

$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

$student = null;
if (is_student()) {
    $currentEmail = mysqli_real_escape_string($conn, current_user_email());
    $studentQuery = mysqli_query($conn, "SELECT * FROM students WHERE email='$currentEmail' LIMIT 1");
    $student = mysqli_fetch_assoc($studentQuery);
}

/* Total Students */
$totalStudentsQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM students"
);

$totalStudents = mysqli_fetch_assoc($totalStudentsQuery)['total'];

/* Active Students */
$activeStudentsQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM students WHERE status='Active'"
);

$activeStudents = mysqli_fetch_assoc($activeStudentsQuery)['total'];

/* Inactive Students */
$inactiveStudentsQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM students WHERE status='Inactive'"
);

$inactiveStudents = mysqli_fetch_assoc($inactiveStudentsQuery)['total'];

/* Total Users */
$totalUsersQuery = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM users"
);

$totalUsers = mysqli_fetch_assoc($totalUsersQuery)['total'];

require 'includes/header.php';
require 'includes/sidebar.php';
?>

<div class="main-content d-flex flex-column min-vh-100">

    <?php require 'includes/navbar.php'; ?>

    <div class="content-area flex-grow-1">

        <div class="container-fluid">

            <!-- Heading -->
            <div class="row mb-4">

                <div class="col-12">

                    <h1 class="fw-bold mb-2">
                        Dashboard
                    </h1>

                    <p class="text-muted fs-5">
                        Welcome to the Student Information Management System
                    </p>

                </div>

            </div>

            <!-- Dashboard Cards -->
            <div class="row g-4">

                <?php if (is_student()): ?>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Name</h6>
                                <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($student['student_name'] ?? $_SESSION['username']); ?></h1>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars(current_user_email()); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Department</h6>
                                <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($student['department'] ?? 'N/A'); ?></h1>
                                <p class="text-muted mb-0">Status: <?php echo htmlspecialchars($student['status'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Phone</h6>
                                <h1 class="fw-bold mb-2"><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></h1>
                                <p class="text-muted mb-0">DOB: <?php echo htmlspecialchars($student['dob'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Review Marks</h6>
                                <h1 class="fw-bold mb-2">Only you</h1>
                                <p class="text-muted mb-0">Visit the Marks page to see your scores.</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>

                <!-- Total Students -->
                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <h6 class="text-muted mb-3">
                                        Total Students
                                    </h6>

                                    <h1 class="fw-bold">
                                        <?php echo $totalStudents; ?>
                                    </h1>

                                </div>

                                <div class="fs-1 text-primary">

                                    <i class="bi bi-mortarboard-fill"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Active Students -->
                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <h6 class="text-muted mb-3">
                                        Active Students
                                    </h6>

                                    <h1 class="fw-bold text-success">
                                        <?php echo $activeStudents; ?>
                                    </h1>

                                </div>

                                <div class="fs-1 text-success">

                                    <i class="bi bi-person-check-fill"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Inactive Students -->
                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <h6 class="text-muted mb-3">
                                        Inactive Students
                                    </h6>

                                    <h1 class="fw-bold text-danger">
                                        <?php echo $inactiveStudents; ?>
                                    </h1>

                                </div>

                                <div class="fs-1 text-danger">

                                    <i class="bi bi-person-x-fill"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <!-- Total Users -->
                <div class="col-md-6 col-xl-3">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center">

                                <div>

                                    <h6 class="text-muted mb-3">
                                        Total Users
                                    </h6>

                                    <h1 class="fw-bold">
                                        <?php echo $totalUsers; ?>
                                    </h1>

                                </div>

                                <div class="fs-1 text-dark">

                                    <i class="bi bi-people-fill"></i>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>
            <?php endif; ?>

            <!-- Extra Space Filling Section -->
            <div class="row mt-5">

                <div class="col-12">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body text-center py-5">

                            <h3 class="fw-bold mb-3">
                                Student Information Management System
                            </h3>

                            <p class="text-muted mb-0 fs-5">

                                Manage student records, monitor active and inactive students,
                                and control user access through the admin dashboard.

                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <?php require 'includes/footer.php'; ?>

</div>