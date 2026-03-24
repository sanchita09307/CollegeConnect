<?php
// Session settings BEFORE session_start - cookie_path '/' ensures
// session cookie works across all subdirectories (teacher_page/ etc.)
ini_set('session.gc_maxlifetime',  7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.cookie_path',     '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/activity_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: teacher_register.php");
    exit();
}

$name        = trim($_POST['name']        ?? '');
$email       = trim($_POST['email']       ?? '');
$department  = trim($_POST['department']  ?? '');
$designation = trim($_POST['designation'] ?? '');
$phone       = trim($_POST['phone']       ?? '');
$passwordRaw = $_POST['password']         ?? '';

if ($name === '' || $email === '' || $department === '' || $designation === '' || $phone === '' || $passwordRaw === '') {
    die("All fields are required.");
}

$check = $conn->prepare("SELECT id FROM teachers WHERE email = ? LIMIT 1");
$check->bind_param("s", $email);
$check->execute();
$checkResult = $check->get_result();
$check->close();

if ($checkResult->num_rows > 0) {
    die("This email is already registered.");
}

$password = password_hash($passwordRaw, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO teachers (name, email, password, department, designation, phone, profile_completed, status) VALUES (?, ?, ?, ?, ?, ?, 0, 'pending')");
$stmt->bind_param("ssssss", $name, $email, $password, $department, $designation, $phone);

if ($stmt->execute()) {
    $teacher_id = $stmt->insert_id;
    $stmt->close();

    logActivity($conn, $teacher_id, 'teacher', 'register', 'New teacher registered: ' . $name);

    $_SESSION['user_id']      = $teacher_id;
    $_SESSION['role']         = "Teacher";
    $_SESSION['teacher_name'] = $name;
    $_SESSION['name']         = $name;

    header("Location: teacher_page/new_teacher.php");
    exit();
} else {
    echo "Registration Failed: " . $stmt->error;
}
?>
