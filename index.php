<?php
require_once 'includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "Add Student";
require 'includes/header.php';
require 'includes/sidebar.php';
include 'db.php';
?>

<!-- Content -->
<div id="content">
<?php require 'includes/navbar.php'; ?>
<div id="main-content">

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-person-plus"></i> Student Registration</h5>
        </div>
        <div class="card-body">
            <form action="actions.php" method="POST">

                <div class="mb-3">
                    <label class="form-label">Student Name</label>
                    <input type="text" name="student_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Gender</label><br>
                    <input type="radio" name="gender" value="Male"> Male &nbsp;
                    <input type="radio" name="gender" value="Female"> Female
                </div>

                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select name="department" class="form-select">
                        <option value="CSE">CSE</option>
                        <option value="ECE">ECE</option>
                        <option value="EEE">EEE</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Skills</label><br>
                    <input type="checkbox" name="skills[]" value="HTML"> HTML &nbsp;
                    <input type="checkbox" name="skills[]" value="PHP"> PHP &nbsp;
                    <input type="checkbox" name="skills[]" value="Java"> Java
                </div>

                <div class="mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control">
                </div>

                <button type="submit" name="save_student" class="btn btn-success">
                    <i class="bi bi-save"></i> Save Student
                </button>
                <a href="students.php" class="btn btn-secondary">
                    <i class="bi bi-people"></i> View Students
                </a>

            </form>
        </div>
    </div>

</div>
<?php require 'includes/footer.php'; ?>
</div>