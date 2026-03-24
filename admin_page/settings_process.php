<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/activity_helper.php';

$site_name = trim($_POST['site_name'] ?? 'CollegeConnect');
$maintenance_mode = (int)($_POST['maintenance_mode'] ?? 0);
$maintenance_message = trim($_POST['maintenance_message'] ?? 'Website is under maintenance');

$current = $conn->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1")->fetch_assoc();
$logoName = $current['site_logo'] ?? null;

if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
    $uploadDir = __DIR__ . '/../uploads/site/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $logoName = time() . '_' . basename($_FILES['site_logo']['name']);
    move_uploaded_file($_FILES['site_logo']['tmp_name'], $uploadDir . $logoName);
}

$stmt = $conn->prepare("UPDATE site_settings SET site_name = ?, site_logo = ?, maintenance_mode = ?, maintenance_message = ? WHERE id = ?");
$stmt->bind_param("ssisi", $site_name, $logoName, $maintenance_mode, $maintenance_message, $current['id']);
$stmt->execute();

logActivity($conn, $admin['id'], 'admin', 'settings_update', 'Admin updated site settings');

header("Location: admin_settings.php");
exit();
?>