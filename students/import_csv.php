<?php
/**
 * students/import_csv.php
 * CSV Import handler for Student Module.
 *
 * Accepts: POST with multipart file upload (field: "csv_file")
 * Returns: JSON response with import summary and per-row errors.
 *
 * Access: Admin + Staff only.
 */

session_start();
require_once '../includes/auth.php';
require_role(['admin', 'staff']);
require_once '../config/db_pdo.php';

// ── Helper: emit JSON and exit ───────────────────────────────────────────────
function json_response(bool $success, string $message, array $data = []): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// ── Helper: activity log (file-based, matching project pattern) ──────────────
function log_csv_import(string $username, int $imported, int $skipped, int $total): void
{
    $logDir  = __DIR__ . '/../auth/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/csv_import.log';
    $ts      = date('Y-m-d H:i:s');
    $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $line    = "[$ts] [$ip] CSV_IMPORT | user=$username | total=$total | imported=$imported | skipped=$skipped\n";
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

// ── Only handle POST requests ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Invalid request method.');
}

// ── File presence check ──────────────────────────────────────────────────────
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server upload limit.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form upload limit.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the upload.',
    ];
    $errCode = $_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    json_response(false, $uploadErrors[$errCode] ?? 'Unknown upload error.');
}

$file     = $_FILES['csv_file'];
$fileName = $file['name'];
$fileTmp  = $file['tmp_name'];

// ── File type validation ─────────────────────────────────────────────────────
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    json_response(false, 'Invalid file type. Only .csv files are accepted.');
}

// ── Open and parse CSV ───────────────────────────────────────────────────────
$handle = fopen($fileTmp, 'r');
if ($handle === false) {
    json_response(false, 'Failed to open the uploaded file.');
}

// Strip BOM if present (common in Excel-saved CSV files)
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

// Read and validate header row
$header = fgetcsv($handle);
if ($header === false) {
    fclose($handle);
    json_response(false, 'The CSV file is empty.');
}

// Normalize header (lowercase, trimmed)
$header = array_map(fn($h) => strtolower(trim($h)), $header);

// ── Strict format validation ─────────────────────────────────────────────────
// Required columns that MUST appear in the CSV header
$requiredColumns = ['name', 'email', 'phone', 'department'];

// Optional columns that are recognised but not mandatory
$optionalColumns = ['gender'];

// All recognised columns (required + optional)
$recognisedColumns = array_merge($requiredColumns, $optionalColumns);

// 1. Detect completely empty header (all blank cells)
if (count(array_filter($header, fn($h) => $h !== '')) === 0) {
    fclose($handle);
    json_response(false,
        'The CSV header row is blank. Your file does not match the required format.',
        ['format_error' => true,
         'expected'     => implode(', ', $requiredColumns) . ' [, gender]',
         'found'        => '(empty)']
    );
}

// 2. Check for missing required columns
$missingColumns = [];
foreach ($requiredColumns as $col) {
    if (!in_array($col, $header, true)) {
        $missingColumns[] = $col;
    }
}

if (!empty($missingColumns)) {
    fclose($handle);
    // Build a readable diff: show expected vs what was actually found
    $foundCols = implode(', ', $header);
    $missingList = implode(', ', $missingColumns);
    json_response(false,
        "Your CSV is missing required column(s): \"{$missingList}\". "
        . "Please use the sample CSV as a template and make sure the first row is exactly: "
        . implode(', ', $requiredColumns) . " (gender is optional).",
        [
            'format_error' => true,
            'expected'     => implode(', ', $requiredColumns) . ' [, gender]',
            'found'        => $foundCols ?: '(no columns detected)',
            'missing'      => $missingColumns,
        ]
    );
}

// 3. Detect completely unrecognised columns (every column is foreign)
$unknownColumns = array_diff($header, $recognisedColumns);
$knownFound     = array_intersect($header, $recognisedColumns);

if (count($knownFound) === 0) {
    // Not a single recognised column — completely wrong file
    fclose($handle);
    json_response(false,
        "This does not look like a student CSV file. None of the columns match the expected format. "
        . "Expected: " . implode(', ', $requiredColumns) . " — Found: " . implode(', ', $header) . ".",
        [
            'format_error' => true,
            'expected'     => implode(', ', $requiredColumns) . ' [, gender]',
            'found'        => implode(', ', $header),
        ]
    );
}

// 4. Warn about unrecognised extra columns (non-blocking — they will be ignored)
$formatWarnings = [];
if (!empty($unknownColumns)) {
    $formatWarnings[] = "Unknown column(s) detected and ignored: \""
                      . implode('", "', $unknownColumns) . "\". "
                      . "Only recognised columns will be imported.";
}

// Map column names to indices
$colIdx = [
    'name'       => array_search('name',       $header),
    'email'      => array_search('email',      $header),
    'phone'      => array_search('phone',      $header),
    'department' => array_search('department', $header),
    'gender'     => array_search('gender',     $header), // optional column
];

// ── Prepare PDO statements ───────────────────────────────────────────────────
$checkEmailStmt = $pdo->prepare(
    'SELECT id FROM students WHERE email = :email LIMIT 1'
);
$checkPhoneStmt = $pdo->prepare(
    'SELECT id FROM students WHERE phone = :phone LIMIT 1'
);

// ── Open transaction & resolve next consecutive ID ───────────────────────────
// We get MAX(id) + 1 inside a transaction so that parallel imports
// cannot race and generate duplicate / non-consecutive IDs.
try {
    $pdo->beginTransaction();
    $nextId = (int) $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM students')->fetchColumn();
} catch (PDOException $e) {
    fclose($handle);
    json_response(false, 'Database error while preparing import. Please try again.');
}

$insertStmt = $pdo->prepare(
    'INSERT INTO students (id, student_name, email, phone, department, gender, status)
     VALUES (:id, :student_name, :email, :phone, :department, :gender, :status)'
);

// ── Process rows ─────────────────────────────────────────────────────────────
$rowNumber     = 1;  // 1 = header (already consumed)
$totalFound    = 0;
$importedCount = 0;
$skippedCount  = 0;
$rowErrors     = []; // ['row' => N, 'reason' => '...']

while (($row = fgetcsv($handle)) !== false) {
    $rowNumber++;

    // Skip completely blank rows
    if (count(array_filter(array_map('trim', $row))) === 0) {
        continue;
    }

    $totalFound++;

    // ── Extract fields ───────────────────────────────────────────────────────
    $name       = trim($row[$colIdx['name']]       ?? '');
    $email      = trim($row[$colIdx['email']]      ?? '');
    $phone      = trim($row[$colIdx['phone']]      ?? '');
    $department = trim($row[$colIdx['department']] ?? '');
    // gender is optional — default to empty string if column absent or blank
    $gender = '';
    if ($colIdx['gender'] !== false && isset($row[$colIdx['gender']])) {
        $gender = trim($row[$colIdx['gender']]);
    }

    // ── Per-row validation ───────────────────────────────────────────────────
    $rowError = null;

    if ($name === '') {
        $rowError = "Row $rowNumber: Name is missing.";
    } elseif ($email === '') {
        $rowError = "Row $rowNumber: Email is missing.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $rowError = "Row $rowNumber: Invalid email format ($email).";
    } elseif ($phone === '') {
        $rowError = "Row $rowNumber: Phone number is missing.";
    } elseif (!preg_match('/^[0-9\-\+\s\(\)]+$/', $phone) || strlen(preg_replace('/[^0-9]/', '', $phone)) < 7) {
        $rowError = "Row $rowNumber: Invalid phone number ($phone). Must contain at least 7 digits.";
    } elseif ($department === '') {
        $rowError = "Row $rowNumber: Department is missing.";
    }

    if ($rowError !== null) {
        $rowErrors[] = ['row' => $rowNumber, 'reason' => $rowError, 'type' => 'validation'];
        $skippedCount++;
        continue;
    }

    // ── Duplicate checks ─────────────────────────────────────────────────────
    try {
        $checkEmailStmt->execute([':email' => $email]);
        if ($checkEmailStmt->fetchColumn() !== false) {
            $rowErrors[] = [
                'row'    => $rowNumber,
                'reason' => "Row $rowNumber: Email already exists ($email).",
                'type'   => 'duplicate',
            ];
            $skippedCount++;
            continue;
        }

        $checkPhoneStmt->execute([':phone' => $phone]);
        if ($checkPhoneStmt->fetchColumn() !== false) {
            $rowErrors[] = [
                'row'    => $rowNumber,
                'reason' => "Row $rowNumber: Phone number already exists ($phone).",
                'type'   => 'duplicate',
            ];
            $skippedCount++;
            continue;
        }
    } catch (PDOException $e) {
        $rowErrors[] = [
            'row'    => $rowNumber,
            'reason' => "Row $rowNumber: Database error during duplicate check.",
            'type'   => 'error',
        ];
        $skippedCount++;
        continue;
    }

    // ── Insert record ────────────────────────────────────────────────────────
    try {
        $insertStmt->execute([
            ':id'           => $nextId,
            ':student_name' => $name,
            ':email'        => $email,
            ':phone'        => $phone,
            ':department'   => $department,
            ':gender'       => $gender,
            ':status'       => 'Active',
        ]);
        $nextId++;          // advance counter only on success
        $importedCount++;
    } catch (PDOException $e) {
        $rowErrors[] = [
            'row'    => $rowNumber,
            'reason' => "Row $rowNumber: Failed to insert record.",
            'type'   => 'error',
        ];
        $skippedCount++;
    }
}

fclose($handle);

// ── Commit or roll back ───────────────────────────────────────────────────────
try {
    if ($importedCount > 0) {
        $pdo->commit();
        // Sync AUTO_INCREMENT so future single-add inserts stay consecutive
        $pdo->exec('ALTER TABLE students AUTO_INCREMENT = 1');
    } else {
        $pdo->rollBack();
    }
} catch (PDOException $e) {
    $pdo->rollBack();
}

// ── Activity logging ─────────────────────────────────────────────────────────
$currentUser = $_SESSION['username'] ?? $_SESSION['email'] ?? 'Unknown';
log_csv_import($currentUser, $importedCount, $skippedCount, $totalFound);

// ── Build detailed error list ─────────────────────────────────────────────────
$errorMessages = array_column($rowErrors, 'reason');

// ── Return JSON summary ───────────────────────────────────────────────────────
json_response(true, 'Import completed.', [
    'total'           => $totalFound,
    'imported'        => $importedCount,
    'skipped'         => $skippedCount,
    'errors'          => $errorMessages,
    'format_warnings' => $formatWarnings,
    'user'            => $currentUser,
]);
