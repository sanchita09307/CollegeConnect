<?php
/**
 * download_material.php
 * Place this file in: includes/download_material.php
 * 
 * Securely serves study material files for download.
 * Works for both students and teachers.
 */

session_start();
require_once __DIR__ . '/../config/db.php';

// Must be logged in as student or teacher
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Access denied. Please login first.');
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    die('Invalid request.');
}

// Fetch material from DB
$stmt = $conn->prepare("SELECT * FROM study_materials WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$mat = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mat) {
    http_response_code(404);
    die('Material not found.');
}

if (empty($mat['file_path'])) {
    http_response_code(404);
    die('No file attached to this material.');
}

// Build absolute path
// file_path stored as: ../uploads/materials/filename
// This file is in includes/, so __DIR__/../uploads/materials/filename
$filePath = realpath(__DIR__ . '/' . $mat['file_path']);

// Security check: make sure the resolved path is within uploads/materials/
$uploadsBase = realpath(__DIR__ . '/../uploads/materials/');
if (!$filePath || !$uploadsBase || strpos($filePath, $uploadsBase) !== 0) {
    http_response_code(403);
    die('Access denied: invalid file path.');
}

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found on server. Please contact your teacher.');
}

// Determine original display filename
$displayName = !empty($mat['file_name']) ? $mat['file_name'] : basename($filePath);
// Clean filename for download header
$displayName = preg_replace('/^\d+_\d+_/', '', $displayName); // strip teacher_id + timestamp prefix if any
if (empty($displayName)) $displayName = basename($filePath);

// MIME type
$ext  = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mime = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'zip'  => 'application/zip',
    'txt'  => 'text/plain',
    'mp4'  => 'video/mp4',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
][$ext] ?? 'application/octet-stream';

// Send file
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $displayName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache');
header('Pragma: no-cache');

ob_clean();
flush();
readfile($filePath);
exit;