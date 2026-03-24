<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/activity_helper.php';

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid ID');
}

if ($type === 'student') {
    $stmt = $conn->prepare("UPDATE students SET status='rejected' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    logActivity($conn, $id, 'student', 'approval', 'Student rejected by admin');
} elseif ($type === 'teacher') {
    $stmt = $conn->prepare("UPDATE teachers SET status='rejected' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    logActivity($conn, $id, 'teacher', 'approval', 'Teacher rejected by admin');
}

header("Location: admin_users.php");
exit();
?>