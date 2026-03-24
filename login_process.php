<?php
// â??â?? Session settings BEFORE session_start â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
ini_set('session.gc_maxlifetime',  7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_path',     '/');
// â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/activity_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit();
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');
$role     = trim($_POST['role']     ?? '');

if ($email === '' || $password === '' || $role === '') {
    echo "<script>alert('Please fill all fields.'); window.location.href='login.php';</script>";
    exit();
}

$table     = '';
$nameField = 'name';

if ($role === "Student") {
    $table     = 'students';
    $nameField = 'full_name';
} elseif ($role === "Teacher") {
    $table     = 'teachers';
    $nameField = 'name';
} elseif ($role === "Admin") {
    $table     = 'admins';
    $nameField = 'name';
} else {
    die("Invalid role selected.");
}

$stmt = $conn->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    echo "<script>alert('Invalid email or password'); window.location.href='login.php';</script>";
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

$isPasswordValid = false;

if (password_verify($password, $user['password'])) {
    $isPasswordValid = true;
} elseif ($user['password'] === $password) {
    $isPasswordValid = true;
    $newHash    = password_hash($password, PASSWORD_DEFAULT);
    $updateHash = $conn->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
    $updateHash->bind_param("si", $newHash, $user['id']);
    $updateHash->execute();
    $updateHash->close();
}

if (!$isPasswordValid) {
    echo "<script>alert('Invalid email or password'); window.location.href='login.php';</script>";
    exit();
}

if (($role === 'Student' || $role === 'Teacher') && isset($user['status'])) {
    if ($user['status'] !== 'approved') {
        echo "<script>alert('Your account is pending admin approval.'); window.location.href='login.php';</script>";
        exit();
    }
}

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['name']    = $user[$nameField] ?? 'User';
$_SESSION['role']    = $role;

if ($role === "Student" || $role === "Teacher") {
    $updateLogin = $conn->prepare("UPDATE {$table} SET is_logged_in = 1, last_login_at = NOW() WHERE id = ?");
    $updateLogin->bind_param("i", $user['id']);
    $updateLogin->execute();
    $updateLogin->close();
}

logActivity($conn, $user['id'], strtolower($role), 'login', $role . " logged in");

if ($role === "Student") {
    header("Location: student_page/student_dashboard.php");
} elseif ($role === "Teacher") {
    header("Location: teacher_page/teacher_dashboard.php");
} else {
    header("Location: admin_page/admin_dashboard.php");
}
exit();
?>
