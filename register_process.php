<?php
// Session settings BEFORE session_start - cookie_path '/' ensures
// session cookie works across all subdirectories (student_page/, teacher_page/ etc.)
ini_set('session.gc_maxlifetime',  7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.cookie_path',     '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config/db.php";
require_once __DIR__ . "/includes/activity_helper.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: student_register.php");
    exit();
}

$full_name  = trim($_POST['full_name']  ?? '');
$email      = trim($_POST['email']      ?? '');
$department = trim($_POST['department'] ?? '');
$batch_year = trim($_POST['batch_year'] ?? '');
$phone      = trim($_POST['phone']      ?? '');
$password   = trim($_POST['password']   ?? '');

if ($full_name === '' || $email === '' || $department === '' || $batch_year === '' || $phone === '' || $password === '') {
    die("All fields are required.");
}

$check = $conn->prepare("SELECT id FROM students WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$checkResult = $check->get_result();
$check->close();

if ($checkResult->num_rows > 0) {
    die("This email is already registered.");
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO students (full_name, email, department, batch_year, phone, password, profile_completed, status) VALUES (?, ?, ?, ?, ?, ?, 0, 'pending')");
$stmt->bind_param("ssssss", $full_name, $email, $department, $batch_year, $phone, $hashedPassword);

if ($stmt->execute()) {
    $new_user_id = $stmt->insert_id;
    $stmt->close();

    logActivity($conn, $new_user_id, 'student', 'register', 'New student registered: ' . $full_name);

    $_SESSION['user_id']      = $new_user_id;
    $_SESSION['role']         = "Student";
    $_SESSION['student_name'] = $full_name;
    $_SESSION['name']         = $full_name;

    header("Location: student_page/new_student.php");
    exit();
} else {
    echo "Registration Failed: " . $stmt->error;
}
?>
