<?php
require_once 'includes/auth.php';
require_role(['admin', 'staff', 'student']);

$pageTitle   = 'Marks';
$currentPage = 'marks';
require 'includes/header.php';
require 'includes/sidebar.php';
include 'db.php';

$student = null;
$students = [];

if (is_student()) {
    $currentEmail = mysqli_real_escape_string($conn, current_user_email());
    $studentQuery = mysqli_query($conn, "SELECT * FROM students WHERE email='$currentEmail' LIMIT 1");
    $student = mysqli_fetch_assoc($studentQuery);
} else {
    $students = mysqli_query($conn, "SELECT * FROM students ORDER BY student_name ASC");
}
?>

<div id="content">

<div class="main-content d-flex flex-column min-vh-100">

    <?php require 'includes/navbar.php'; ?>

    <div class="content-area flex-grow-1">

        <div class="container-fluid">

            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="fw-bold mb-2">Marks</h1>
                    <p class="text-muted fs-5">Access the marks module based on your role.</p>
                </div>
            </div>

            <?php if (is_student()): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5>Your Marks</h5>
                                <?php if ($student): ?>
                                    <p class="mb-2">Student: <strong><?php echo htmlspecialchars($student['student_name']); ?></strong></p>
                                    <p class="mb-2">Email: <strong><?php echo htmlspecialchars($student['email']); ?></strong></p>
                                    <p class="text-muted">Marks are available here for your own record. If marks are not yet entered, contact staff.</p>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        No student record was found for your account email.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="mb-4">Student Marks Overview</h5>

                                <?php if (mysqli_num_rows($students) === 0): ?>
                                    <div class="alert alert-secondary">
                                        <i class="bi bi-info-circle"></i>
                                        No student records available.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered mb-0">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Department</th>
                                                    <th>Status</th>
                                                    <th>Marks</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($row = mysqli_fetch_assoc($students)): ?>
                                                    <tr>
                                                        <td><?php echo $row['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                                        <td><span class="text-muted">Not entered</span></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <?php require 'includes/footer.php'; ?>

</div>