<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Admin") {
    header("Location: ../login.php");
    exit();
}

$admin_id = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>