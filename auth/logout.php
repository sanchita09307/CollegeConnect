<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/activity_helper.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $user_id = (int) $_SESSION['user_id'];
    $role = $_SESSION['role'];

    if ($role === 'Student') {
        $stmt = $conn->prepare("UPDATE students SET is_logged_in = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        logActivity($conn, $user_id, 'student', 'logout', 'Student logged out');
    } elseif ($role === 'Teacher') {
        $stmt = $conn->prepare("UPDATE teachers SET is_logged_in = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        logActivity($conn, $user_id, 'teacher', 'logout', 'Teacher logged out');
    } elseif ($role === 'Admin') {
        logActivity($conn, $user_id, 'admin', 'logout', 'Admin logged out');
    }
}

session_unset();
session_destroy();

header("Location: ../login.php");
exit();
?>