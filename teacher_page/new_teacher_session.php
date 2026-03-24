<?php
/**
 * new_teacher_session.php
 * new_teacher.php à??à??à??à?? special session file
 * pending status à??à?? BLOCK à??à??à?? à??à??à??à?? - faqt login check à??à??à??à??
 */
ini_set('session.gc_maxlifetime',  7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.cookie_path',     '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Teacher") {
    header("Location: ../login.php");
    exit();
}

$teacher_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result  = $stmt->get_result();
$teacher = $result->fetch_assoc();
$stmt->close();

if (!$teacher) {
    header("Location: ../login.php");
    exit();
}
// NOTE: pending/rejected status à??à?? block à??à??à?? à??à??à??à?? - new_teacher page pending à??à?? à?ªà?? à??à??à??à??à??
?>
