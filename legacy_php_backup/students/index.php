<?php
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
$page_title = "Add Student";
require '../includes/header.php';
require '../includes/sidebar.php';
include '../config/db.php';

$error = "";
$success = "";

if (isset($_POST['save_student'])) {
    $student_name = trim($_POST['student_name']);
    $email = trim($_POST['email']);
    $parent_name  = trim($_POST['parent_name']  ?? '');
    $parent_email = trim($_POST['parent_email'] ?? '');
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'] ?? '';
    $department = $_POST['department'] ?? '';
    $skills_selected = $_POST['skills'] ?? [];
    $dob = $_POST['dob'] ?? '';

    // Validation
    if (empty($student_name) || empty($email) || empty($phone)) {
        $error = "Name, email, and phone are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!empty($parent_email) && !filter_var($parent_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid parent/guardian email address.";
    } elseif (!preg_match('/^[0-9\-\+\s\(\)]+$/', $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
        $error = "Please enter a valid phone number (minimum 10 digits).";
    } elseif (empty($gender)) {
        $error = "Please select a gender.";
    } elseif (empty($department)) {
        $error = "Please select a department.";
    } elseif (empty($skills_selected)) {
        $error = "Please select at least one skill.";
    } elseif (!empty($dob) && !strtotime($dob)) {
        $error = "Invalid date of birth.";
    } else {
        // Check email uniqueness
        $email_check = "SELECT id FROM students WHERE email='$email'";
        $email_result = mysqli_query($conn, $email_check);
        
        if (mysqli_num_rows($email_result) > 0) {
            $error = "Email already exists for another student.";
        } else {
            $skills = implode(",", array_map('trim', $skills_selected));
            $pe = mysqli_real_escape_string($conn, $parent_email);
            $pn = mysqli_real_escape_string($conn, $parent_name);

            // Get next consecutive ID (no gaps even after deletions)
            $id_res  = mysqli_query($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM students");
            $next_id = (int) mysqli_fetch_assoc($id_res)['next_id'];

            $query = "INSERT INTO students
                (id, student_name, email, parent_name, parent_email, phone, gender, department, skills, dob, status)
                VALUES
                ($next_id,'$student_name','$email','$pn','$pe','$phone','$gender','$department','$skills','$dob','Active')";

            if (mysqli_query($conn, $query)) {
                // Keep AUTO_INCREMENT in sync so it never diverges
                mysqli_query($conn, "ALTER TABLE students AUTO_INCREMENT = 1");
                $success = "Student added successfully!";
                // Clear form
                $student_name = $email = $phone = $gender = $department = $dob = '';
                $skills_selected = [];
            } else {
                $error = "Failed to add student.";
            }

        }
    }
}
?>

<div id="content">
<?php require '../includes/navbar.php'; ?>
<div id="main-content">

    <div class="mb-4">
        <h4 class="mb-0">Add New Student</h4>
        <small class="text-muted">Register a new student in the system</small>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <div class="mt-2">
                <a href="students.php" class="btn btn-success btn-sm">
                    <i class="bi bi-eye"></i> View All Students
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-plus-fill"></i> Student Registration Form</h5>
                </div>
                <div class="card-body">
                    <form action="index.php" method="POST" novalidate>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Student Name *</label>
                                <input type="text" name="student_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($student_name ?? ''); ?>"
                                       placeholder="Full name">
                                <small class="text-muted">Enter complete name as per records</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       placeholder="student@example.com">
                                <small class="text-muted">Must be unique and valid</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Name</label>
                                <input type="text" name="parent_name" class="form-control"
                                       value="<?php echo htmlspecialchars($parent_name ?? ''); ?>"
                                       placeholder="Parent or guardian full name">
                                <small class="text-muted">Optional</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Parent/Guardian Email</label>
                                <input type="email" name="parent_email" class="form-control"
                                       value="<?php echo htmlspecialchars($parent_email ?? ''); ?>"
                                       placeholder="parent@example.com">
                                <small class="text-muted">Absence & fee alerts will be sent here if provided</small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone *</label>
                                <input type="tel" name="phone" class="form-control" required
                                       value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                       placeholder="+1-234-567-8900">
                                <small class="text-muted">Minimum 10 digits</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender *</label>
                                <select name="gender" class="form-select" required>
                                    <option value="">-- Select Gender --</option>
                                    <option value="Male" <?php if (($gender ?? '') == 'Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if (($gender ?? '') == 'Female') echo 'selected'; ?>>Female</option>
                                    <option value="Other" <?php if (($gender ?? '') == 'Other') echo 'selected'; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Department *</label>
                                <select name="department" class="form-select" required>
                                    <option value="">-- Select Department --</option>
                                    <option value="CSE" <?php if (($department ?? '') == 'CSE') echo 'selected'; ?>>CSE (Computer Science)</option>
                                    <option value="ECE" <?php if (($department ?? '') == 'ECE') echo 'selected'; ?>>ECE (Electronics)</option>
                                    <option value="EEE" <?php if (($department ?? '') == 'EEE') echo 'selected'; ?>>EEE (Electrical)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-control"
                                       value="<?php echo htmlspecialchars($dob ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Skills *</label>
                            <div class="border p-3 rounded bg-light">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="skills[]" value="HTML" class="form-check-input"
                                                   <?php if (in_array("HTML", $skills_selected ?? [])) echo "checked"; ?> id="skill_html">
                                            <label class="form-check-label" for="skill_html">
                                                <strong>HTML</strong> - Markup language
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="skills[]" value="CSS" class="form-check-input"
                                                   <?php if (in_array("CSS", $skills_selected ?? [])) echo "checked"; ?> id="skill_css">
                                            <label class="form-check-label" for="skill_css">
                                                <strong>CSS</strong> - Styling
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="skills[]" value="PHP" class="form-check-input"
                                                   <?php if (in_array("PHP", $skills_selected ?? [])) echo "checked"; ?> id="skill_php">
                                            <label class="form-check-label" for="skill_php">
                                                <strong>PHP</strong> - Backend
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check mb-2">
                                            <input type="checkbox" name="skills[]" value="JavaScript" class="form-check-input"
                                                   <?php if (in_array("JavaScript", $skills_selected ?? [])) echo "checked"; ?> id="skill_js">
                                            <label class="form-check-label" for="skill_js">
                                                <strong>JavaScript</strong> - Frontend
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input type="checkbox" name="skills[]" value="Java" class="form-check-input"
                                                   <?php if (in_array("Java", $skills_selected ?? [])) echo "checked"; ?> id="skill_java">
                                            <label class="form-check-label" for="skill_java">
                                                <strong>Java</strong> - OOP
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Select at least one skill</small>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="save_student" class="btn btn-success">
                                <i class="bi bi-save"></i> Save Student
                            </button>
                            <a href="students.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm bg-light mb-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-checklist"></i> Form Requirements</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-info"></i>
                            <strong>Name:</strong> Full legal name required
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-info"></i>
                            <strong>Email:</strong> Must be valid and unique
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-info"></i>
                            <strong>Phone:</strong> At least 10 digits
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-info"></i>
                            <strong>Gender:</strong> Required field
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-info"></i>
                            <strong>Department:</strong> Must be selected
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-info"></i>
                            <strong>Skills:</strong> At least 1 required
                        </li>
                        <li>
                            <i class="bi bi-info-circle text-info"></i>
                            <strong>DOB:</strong> Optional field
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-primary">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-lightbulb"></i> Tips</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2">✓ Fill all marked (*) fields</li>
                        <li class="mb-2">✓ Use a valid email address</li>
                        <li class="mb-2">✓ Select appropriate skills</li>
                        <li>✓ Review before submitting</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
<?php
// Inject toast notification
if (!empty($success)) {
    echo "<script>window._toastMsg=" . json_encode($success) . ";window._toastType='success';</script>";
} elseif (!empty($error)) {
    echo "<script>window._toastMsg=" . json_encode($error) . ";window._toastType='danger';</script>";
}
?>
<?php require '../includes/footer.php'; ?>
</div>