<?php
require_once 'includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "View Students";
require 'includes/header.php';
require 'includes/sidebar.php';
include 'db.php';

$query  = "SELECT * FROM students";
$result = mysqli_query($conn, $query);
?>

<!-- Content -->
<div id="content">
<?php require 'includes/navbar.php'; ?>
<div id="main-content">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Students List</h4>
        <a href="index.php" class="btn btn-success btn-sm">
            <i class="bi bi-person-plus"></i> Add Student
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Gender</th>
                        <th>Department</th>
                        <th>Skills</th>
                        <th>DOB</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-4 d-block mb-2"></i>
                            <strong>No student details exist.</strong><br>
                            <small>Click <a href="index.php">Add Student</a> to add your first record.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo $row['student_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo $row['gender']; ?></td>
                            <td><?php echo $row['department']; ?></td>
                            <td><?php echo $row['skills']; ?></td>
                            <td><?php echo $row['dob']; ?></td>
                            <td>
                                <?php if ($row['status'] == "Active"): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <?php if ($row['status'] == "Active"): ?>
                                    <a href="actions.php?delete=<?php echo $row['id']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Make this student inactive?')">
                                        <i class="bi bi-person-dash"></i> Inactive
                                    </a>
                                <?php else: ?>
                                    <a href="actions.php?rejoin=<?php echo $row['id']; ?>"
                                       class="btn btn-success btn-sm">
                                        <i class="bi bi-person-check"></i> Rejoin
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

</div>
<?php require 'includes/footer.php'; ?>
</div>