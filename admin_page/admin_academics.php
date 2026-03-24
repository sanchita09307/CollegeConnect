<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);

$success = '';
$error = '';

function cc_column_exists(mysqli $conn, string $table, string $column): bool {
  $table  = $conn->real_escape_string($table);
  $column = $conn->real_escape_string($column);
  $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
  return $res && $res->num_rows > 0;
}

function cc_ensure_column(mysqli $conn, string $table, string $column, string $definition): void {
  if (!cc_column_exists($conn, $table, $column)) {
    $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
  }
}

function ccv(array $row, string $key, $default = '') {
  return array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '' ? $row[$key] : $default;
}


// Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? CREATE TABLES IF NOT EXIST Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
$conn->query("CREATE TABLE IF NOT EXISTS branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_code VARCHAR(20) NOT NULL UNIQUE,
  branch_name VARCHAR(100) NOT NULL,
  duration_years INT DEFAULT 3,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS semesters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  semester_no INT NOT NULL,
  semester_name VARCHAR(50),
  UNIQUE KEY uniq_sem (semester_no)
)");

$conn->query("CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_code VARCHAR(20) NOT NULL UNIQUE,
  subject_name VARCHAR(150) NOT NULL,
  branch_code VARCHAR(20) NOT NULL,
  semester INT NOT NULL,
  subject_type ENUM('Core','Elective','Practical','Theory') DEFAULT 'Core',
  teacher_id INT DEFAULT NULL,
  credits INT DEFAULT 3,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100),
  department VARCHAR(100)
)");

$conn->query("CREATE TABLE IF NOT EXISTS timetables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  branch_code VARCHAR(20) NOT NULL,
  semester INT NOT NULL,
  section VARCHAR(5) DEFAULT 'A',
  day_name VARCHAR(10) NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  subject_id INT DEFAULT NULL,
  teacher_id INT DEFAULT NULL,
  room VARCHAR(50),
  slot_type ENUM('Lecture','Lab','Break','Lunch') DEFAULT 'Lecture',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? SCHEMA REPAIR FOR EXISTING DATABASES Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
cc_ensure_column($conn, 'branches',   'duration_years', "INT DEFAULT 3 AFTER branch_name");
cc_ensure_column($conn, 'branches',   'created_at',     "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

cc_ensure_column($conn, 'subjects',   'credits',        "INT DEFAULT 3 AFTER teacher_id");
cc_ensure_column($conn, 'subjects',   'created_at',     "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
cc_ensure_column($conn, 'subjects',   'teacher_id',     "INT DEFAULT NULL AFTER subject_type");
cc_ensure_column($conn, 'subjects',   'subject_type',   "ENUM('Core','Elective','Practical','Theory') DEFAULT 'Core' AFTER semester");

cc_ensure_column($conn, 'timetables', 'branch_code',    "VARCHAR(20) NOT NULL AFTER id");
cc_ensure_column($conn, 'timetables', 'semester',       "INT NOT NULL DEFAULT 1 AFTER branch_code");
cc_ensure_column($conn, 'timetables', 'section',        "VARCHAR(5) DEFAULT 'A' AFTER semester");
cc_ensure_column($conn, 'timetables', 'day_name',       "VARCHAR(10) NOT NULL DEFAULT 'Monday' AFTER section");
cc_ensure_column($conn, 'timetables', 'start_time',     "TIME NOT NULL DEFAULT '09:00:00' AFTER day_name");
cc_ensure_column($conn, 'timetables', 'end_time',       "TIME NOT NULL DEFAULT '10:00:00' AFTER start_time");
cc_ensure_column($conn, 'timetables', 'subject_id',     "INT DEFAULT NULL AFTER end_time");
cc_ensure_column($conn, 'timetables', 'teacher_id',     "INT DEFAULT NULL AFTER subject_id");
cc_ensure_column($conn, 'timetables', 'room',           "VARCHAR(50) NULL AFTER teacher_id");
cc_ensure_column($conn, 'timetables', 'slot_type',      "ENUM('Lecture','Lab','Break','Lunch') DEFAULT 'Lecture' AFTER room");
cc_ensure_column($conn, 'timetables', 'created_at',     "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");


// Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? SEED DEFAULT DIPLOMA BRANCHES Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
$existingBranches = $conn->query("SELECT COUNT(*) as cnt FROM branches")->fetch_assoc()['cnt'];
if ($existingBranches == 0) {
  $conn->query("INSERT IGNORE INTO branches (branch_code,branch_name,duration_years) VALUES
    ('CO','Computer Engineering',3),
    ('ME','Mechanical Engineering',3),
    ('CE','Civil Engineering',3),
    ('EE','Electrical Engineering',3),
    ('EJ','Electronics & Telecom Engineering',3),
    ('IF','Information Technology',3),
    ('CH','Chemical Engineering',3),
    ('IC','Instrumentation & Control',3)
  ");
}

$existingSems = $conn->query("SELECT COUNT(*) as cnt FROM semesters")->fetch_assoc()['cnt'];
if ($existingSems == 0) {
  $conn->query("INSERT IGNORE INTO semesters (semester_no,semester_name) VALUES
    (1,'First Semester'),(2,'Second Semester'),(3,'Third Semester'),
    (4,'Fourth Semester'),(5,'Fifth Semester'),(6,'Sixth Semester')
  ");
}

// Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? HANDLE ALL FORM SUBMISSIONS Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? BRANCH CRUD Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
  if ($action === 'add_branch') {
    $code = strtoupper(trim($conn->real_escape_string($_POST['branch_code'])));
    $name = trim($conn->real_escape_string($_POST['branch_name']));
    $yrs  = (int)$_POST['duration_years'];
    if ($code && $name) {
      if ($conn->query("INSERT INTO branches (branch_code,branch_name,duration_years) VALUES ('$code','$name',$yrs)"))
        $success = "Branch '$name' added successfully!";
      else $error = "Branch code '$code' already exists.";
    } else $error = "Branch code and name are required.";
  }

  if ($action === 'edit_branch') {
    $id   = (int)$_POST['branch_id'];
    $name = trim($conn->real_escape_string($_POST['branch_name']));
    $yrs  = (int)$_POST['duration_years'];
    $conn->query("UPDATE branches SET branch_name='$name',duration_years=$yrs WHERE id=$id");
    $success = "Branch updated successfully!";
  }

  if ($action === 'delete_branch') {
    $id = (int)$_POST['branch_id'];
    $conn->query("DELETE FROM branches WHERE id=$id");
    $success = "Branch deleted.";
  }

  // Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? SUBJECT CRUD Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
  if ($action === 'add_subject') {
    $code   = strtoupper(trim($conn->real_escape_string($_POST['subject_code'])));
    $name   = trim($conn->real_escape_string($_POST['subject_name']));
    $branch = $conn->real_escape_string($_POST['branch_code']);
    $sem    = (int)$_POST['semester'];
    $type   = $conn->real_escape_string($_POST['subject_type']);
    $tid    = $_POST['teacher_id'] ? (int)$_POST['teacher_id'] : 'NULL';
    $cred   = (int)($_POST['credits'] ?? 3);
    if ($code && $name && $branch && $sem) {
      if ($conn->query("INSERT INTO subjects (subject_code,subject_name,branch_code,semester,subject_type,teacher_id,credits) VALUES ('$code','$name','$branch',$sem,'$type',$tid,$cred)"))
        $success = "Subject '$name' added successfully!";
      else $error = "Subject code '$code' already exists.";
    } else $error = "All subject fields are required.";
  }

  if ($action === 'edit_subject') {
    $id     = (int)$_POST['subject_id'];
    $name   = trim($conn->real_escape_string($_POST['subject_name']));
    $branch = $conn->real_escape_string($_POST['branch_code']);
    $sem    = (int)$_POST['semester'];
    $type   = $conn->real_escape_string($_POST['subject_type']);
    $tid    = $_POST['teacher_id'] ? (int)$_POST['teacher_id'] : 'NULL';
    $cred   = (int)($_POST['credits'] ?? 3);
    $conn->query("UPDATE subjects SET subject_name='$name',branch_code='$branch',semester=$sem,subject_type='$type',teacher_id=$tid,credits=$cred WHERE id=$id");
    $success = "Subject updated!";
  }

  if ($action === 'delete_subject') {
    $id = (int)$_POST['subject_id'];
    $conn->query("DELETE FROM subjects WHERE id=$id");
    $success = "Subject deleted.";
  }

  // Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? TIMETABLE CRUD Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
  if ($action === 'add_slot') {
    $branch = $conn->real_escape_string($_POST['branch_code']);
    $sem    = (int)$_POST['semester'];
    $sec    = $conn->real_escape_string($_POST['section'] ?? 'A');
    $day    = $conn->real_escape_string($_POST['day_name']);
    $start  = $conn->real_escape_string($_POST['start_time']);
    $end    = $conn->real_escape_string($_POST['end_time']);
    $subj   = $_POST['subject_id'] ? (int)$_POST['subject_id'] : 'NULL';
    $tid    = $_POST['teacher_id'] ? (int)$_POST['teacher_id'] : 'NULL';
    $room   = $conn->real_escape_string($_POST['room'] ?? '');
    $stype  = $conn->real_escape_string($_POST['slot_type'] ?? 'Lecture');
    if ($branch && $day && $start && $end) {
      $conn->query("INSERT INTO timetables (branch_code,semester,section,day_name,start_time,end_time,subject_id,teacher_id,room,slot_type) VALUES ('$branch',$sem,'$sec','$day','$start','$end',$subj,$tid,'$room','$stype')");
      $success = "Timetable slot added!";
    } else $error = "Branch, day and times are required.";
  }

  if ($action === 'delete_slot') {
    $id = (int)$_POST['slot_id'];
    $conn->query("DELETE FROM timetables WHERE id=$id");
    $success = "Slot deleted.";
  }

  if ($action === 'edit_slot') {
    $id     = (int)$_POST['slot_id'];
    $day    = $conn->real_escape_string($_POST['day_name']);
    $start  = $conn->real_escape_string($_POST['start_time']);
    $end    = $conn->real_escape_string($_POST['end_time']);
    $subj   = $_POST['subject_id'] ? (int)$_POST['subject_id'] : 'NULL';
    $tid    = $_POST['teacher_id'] ? (int)$_POST['teacher_id'] : 'NULL';
    $room   = $conn->real_escape_string($_POST['room'] ?? '');
    $stype  = $conn->real_escape_string($_POST['slot_type'] ?? 'Lecture');
    $conn->query("UPDATE timetables SET day_name='$day',start_time='$start',end_time='$end',subject_id=$subj,teacher_id=$tid,room='$room',slot_type='$stype' WHERE id=$id");
    $success = "Slot updated!";
  }

  // Redirect to prevent re-submit
  $tab = $_POST['active_tab'] ?? 'departments';
  header("Location: admin_academics.php?tab=$tab&msg=" . urlencode($success ?: $error) . "&type=" . ($success ? 'success' : 'error'));
  exit;
}

// Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? FETCH DATA Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬
if (isset($_GET['msg'])) {
  if (($_GET['type'] ?? '') === 'success') $success = htmlspecialchars($_GET['msg']);
  else $error = htmlspecialchars($_GET['msg']);
}

$activeTab = $_GET['tab'] ?? 'departments';

$branches_res  = $conn->query("SELECT * FROM branches ORDER BY branch_name ASC");
$branches_arr  = [];
while ($r = $branches_res->fetch_assoc()) $branches_arr[] = $r;

$subjects_res  = $conn->query("SELECT s.*, t.name AS teacher_name FROM subjects s LEFT JOIN teachers t ON s.teacher_id = t.id ORDER BY s.branch_code, s.semester, s.subject_name");
$subjects_arr  = [];
while ($r = $subjects_res->fetch_assoc()) $subjects_arr[] = $r;

$teachers_res  = $conn->query("SELECT * FROM teachers ORDER BY name ASC");
$teachers_arr  = [];
while ($r = $teachers_res->fetch_assoc()) $teachers_arr[] = $r;

// Filter for timetable
$tt_branch  = $_GET['tt_branch'] ?? (!empty($branches_arr) ? ccv($branches_arr[0], 'branch_code', '') : '');
$tt_sem     = (int)($_GET['tt_sem'] ?? 1);
$tt_section = $_GET['tt_section'] ?? 'A';
$tt_day     = $_GET['tt_day'] ?? 'Monday';

$timetables_res = $conn->query("SELECT tt.*, s.subject_name, s.subject_code, t.name AS teacher_name
  FROM timetables tt
  LEFT JOIN subjects s ON tt.subject_id = s.id
  LEFT JOIN teachers t ON tt.teacher_id = t.id
  WHERE tt.branch_code='" . $conn->real_escape_string($tt_branch) . "'
    AND tt.semester=$tt_sem
    AND tt.section='" . $conn->real_escape_string($tt_section) . "'
    AND tt.day_name='" . $conn->real_escape_string($tt_day) . "'
  ORDER BY tt.start_time ASC");
$timetables_arr = [];
while ($r = $timetables_res->fetch_assoc()) $timetables_arr[] = $r;

// Stats
$totalBranches = count($branches_arr);
$totalSubjects = count($subjects_arr);
$totalSlots    = $conn->query("SELECT COUNT(*) as c FROM timetables")->fetch_assoc()['c'];

// Subjects filtered for timetable
$tt_subjects = array_filter($subjects_arr, fn($s) => $s['branch_code'] === $tt_branch && $s['semester'] == $tt_sem);

// Subject filter vars
$filter_branch = $_GET['sb_branch'] ?? '';
$filter_sem    = $_GET['sb_sem'] ?? '';
$filter_type   = $_GET['sb_type'] ?? '';

$filtered_subjects = array_filter($subjects_arr, function($s) use ($filter_branch, $filter_sem, $filter_type) {
  if ($filter_branch && $s['branch_code'] !== $filter_branch) return false;
  if ($filter_sem && $s['semester'] != $filter_sem) return false;
  if ($filter_type && $s['subject_type'] !== $filter_type) return false;
  return true;
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Academics ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“ <?= htmlspecialchars($settings['site_name']) ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf","primary-dark":"#2630ed","bg-light":"#f0f2ff","bg-dark":"#0d0e1a"},fontFamily:{sans:["Plus Jakarta Sans","sans-serif"]}}}}</script>
<style>
  *,*::before,*::after{box-sizing:border-box}
  body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0f2ff;min-height:100dvh}
  @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
  @keyframes tabSlide{from{opacity:0;transform:translateX(12px)}to{opacity:1;transform:translateX(0)}}
  .anim-fade-up{animation:fadeUp .45s ease both}
  .anim-tab{animation:tabSlide .3s ease both}
  .d1{animation-delay:.05s}.d2{animation-delay:.1s}.d3{animation-delay:.15s}.d4{animation-delay:.2s}.d5{animation-delay:.25s}.d6{animation-delay:.3s}
  .card{background:#fff;border-radius:1rem;border:1px solid rgba(67,73,207,.08);box-shadow:0 2px 12px rgba(67,73,207,.06)}
  .dark .card{background:#161728;border-color:rgba(67,73,207,.15)}
  .sidebar{background:#fff;border-right:1px solid rgba(67,73,207,.08);width:260px;flex-shrink:0}
  .dark .sidebar{background:#161728;border-color:rgba(67,73,207,.15)}
  .sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#64748b;transition:all .2s}
  .sidebar-link:hover{background:rgba(67,73,207,.06);color:#4349cf}
  .sidebar-link.active{background:#4349cf;color:#fff;box-shadow:0 4px 12px rgba(67,73,207,.3)}
  .top-tabs{display:flex;gap:.25rem;padding:.375rem;background:rgba(67,73,207,.06);border-radius:.875rem;flex-wrap:nowrap;overflow-x:auto}
  .top-tab{padding:.5rem 1rem;border-radius:.625rem;font-size:.8125rem;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .2s;color:#64748b;background:transparent;border:none}
  .top-tab.active{background:#4349cf;color:#fff;box-shadow:0 4px 12px rgba(67,73,207,.25)}
  .top-tab:not(.active):hover{background:rgba(67,73,207,.1);color:#4349cf}
  .tab-section{display:none}
  .tab-section.active{display:block}
  .mini-stat{display:flex;align-items:center;gap:.875rem;padding:1.125rem;background:#fff;border-radius:1rem;border:1px solid rgba(67,73,207,.08);box-shadow:0 1px 6px rgba(67,73,207,.05);transition:transform .2s,box-shadow .2s}
  .mini-stat:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(67,73,207,.1)}
  .dark .mini-stat{background:#161728;border-color:rgba(67,73,207,.15)}
  .mini-stat .icon-box{width:44px;height:44px;border-radius:.75rem;display:flex;align-items:center;justify-content:center;flex-shrink:0}
  .subject-row{display:flex;align-items:center;justify-content:space-between;padding:1rem;background:#fff;border-radius:.875rem;border:1px solid rgba(67,73,207,.07);box-shadow:0 1px 4px rgba(67,73,207,.04);transition:all .2s}
  .subject-row:hover{border-color:rgba(67,73,207,.3);box-shadow:0 4px 16px rgba(67,73,207,.1);}
  .dark .subject-row{background:#161728;border-color:rgba(67,73,207,.12)}
  .tt-slot{display:flex;gap:.875rem;align-items:flex-start}
  .tt-slot .time-col{min-width:64px;text-align:center;padding-top:.5rem}
  .tt-slot .slot-card{flex:1;background:#fff;border-radius:.875rem;padding:1rem;border-left:4px solid;box-shadow:0 1px 8px rgba(0,0,0,.05);transition:all .2s}
  .tt-slot .slot-card:hover{transform:translateX(4px);box-shadow:0 4px 16px rgba(0,0,0,.1)}
  .dark .tt-slot .slot-card{background:#161728}
  .dept-table{width:100%;border-collapse:collapse}
  .dept-table th{padding:.875rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;background:rgba(67,73,207,.03);border-bottom:1px solid rgba(67,73,207,.07)}
  .dept-table td{padding:.875rem 1rem;border-bottom:1px solid rgba(67,73,207,.04);font-size:.875rem}
  .dept-table tr:last-child td{border-bottom:none}
  .dept-table tbody tr{transition:background .15s}
  .dept-table tbody tr:hover{background:rgba(67,73,207,.03)}
  .dark .dept-table th{background:rgba(67,73,207,.08);border-color:rgba(67,73,207,.1)}
  .dark .dept-table td{border-color:rgba(67,73,207,.06)}
  .chip{display:inline-flex;align-items:center;gap:.3rem;padding:.375rem .875rem;border-radius:99px;font-size:.8125rem;font-weight:600;cursor:pointer;transition:all .2s;white-space:nowrap;border:1.5px solid transparent;text-decoration:none}
  .chip.active{background:#4349cf;color:#fff;border-color:#4349cf}
  .chip:not(.active){background:#fff;color:#64748b;border-color:rgba(67,73,207,.15)}
  .chip:not(.active):hover{border-color:#4349cf;color:#4349cf}
  .dark .chip:not(.active){background:#1e2035;border-color:rgba(67,73,207,.2)}
  .btn-primary{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:#4349cf;color:#fff;border-radius:.75rem;font-size:.875rem;font-weight:600;transition:all .2s;box-shadow:0 4px 12px rgba(67,73,207,.25);border:none;cursor:pointer}
  .btn-primary:hover{background:#2630ed;transform:translateY(-1px);box-shadow:0 6px 18px rgba(67,73,207,.35)}
  .btn-danger{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;background:#fee2e2;color:#dc2626;border-radius:.75rem;font-size:.8125rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}
  .btn-danger:hover{background:#fca5a5}
  .btn-edit{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1rem;background:rgba(67,73,207,.1);color:#4349cf;border-radius:.75rem;font-size:.8125rem;font-weight:600;border:none;cursor:pointer;transition:all .2s}
  .btn-edit:hover{background:rgba(67,73,207,.2)}
  .form-input{width:100%;padding:.625rem .875rem;border:1.5px solid rgba(67,73,207,.2);border-radius:.75rem;background:#fff;font-family:inherit;font-size:.875rem;outline:none;transition:border-color .2s,box-shadow .2s;color:#0f172a}
  .form-input:focus{border-color:#4349cf;box-shadow:0 0 0 3px rgba(67,73,207,.12)}
  .dark .form-input{background:#1e2035;border-color:rgba(67,73,207,.25);color:#fff}
  .form-label{display:block;font-size:.8125rem;font-weight:600;color:#475569;margin-bottom:.375rem}
  .dark .form-label{color:#94a3b8}
  /* Modal */
  .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:1000;align-items:center;justify-content:center;padding:1rem}
  .modal-backdrop.open{display:flex}
  .modal{background:#fff;border-radius:1.25rem;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.18);animation:fadeUp .3s ease}
  .dark .modal{background:#161728}
  .modal-header{padding:1.25rem 1.5rem;border-bottom:1px solid rgba(67,73,207,.1);display:flex;align-items:center;justify-content:space-between}
  .modal-body{padding:1.5rem}
  .modal-footer{padding:1rem 1.5rem;border-top:1px solid rgba(67,73,207,.1);display:flex;justify-content:flex-end;gap:.75rem}
  .day-picker{display:flex;background:rgba(67,73,207,.06);border-radius:.875rem;padding:.3rem;gap:.15rem;overflow-x:auto}
  .day-btn{flex:1;padding:.5rem .5rem;border-radius:.625rem;border:none;cursor:pointer;font-size:.8rem;font-weight:600;color:#64748b;background:transparent;transition:all .2s;white-space:nowrap}
  .day-btn.active{background:#fff;color:#4349cf;box-shadow:0 2px 8px rgba(67,73,207,.2)}
  .bottom-nav{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-top:1px solid rgba(67,73,207,.08)}
  .nav-item{display:flex;flex-direction:column;align-items:center;gap:2px;color:#94a3b8;transition:color .2s}
  .nav-item.active{color:#4349cf}
  .nav-item span{font-size:22px}
  .nav-item p{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
  .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
  .fill-icon{font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}
  ::-webkit-scrollbar{width:4px;height:4px}
  ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
  .no-scrollbar::-webkit-scrollbar{display:none}
  .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}

  /* Toast */
  .toast{position:fixed;top:1.25rem;right:1.25rem;z-index:9999;padding:.875rem 1.25rem;border-radius:.875rem;font-size:.875rem;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.15);display:flex;align-items:center;gap:.625rem;animation:fadeUp .4s ease;max-width:340px}
  .toast-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0}
  .toast-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}
  .badge{display:inline-flex;align-items:center;padding:.25rem .625rem;border-radius:99px;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
  .badge-core{background:#ecfdf5;color:#065f46}
  .badge-elective{background:#eff6ff;color:#1e40af}
  .badge-practical{background:#fef3c7;color:#92400e}
  .badge-theory{background:#f5f3ff;color:#5b21b6}
  .slot-lecture{border-left-color:#4349cf!important}
  .slot-lab{border-left-color:#22c55e!important}
  .slot-break{border-left-color:#f59e0b!important;background:#fffbeb!important}
  .slot-lunch{border-left-color:#94a3b8!important;background:#f8fafc!important}
</style>
</head>
<body class="dark:bg-bg-dark dark:text-slate-100">

<?php if ($success): ?>
<div class="toast toast-success" id="toast">
  <span class="material-symbols-outlined text-lg">check_circle</span><?= $success ?>
</div>
<?php elseif ($error): ?>
<div class="toast toast-error" id="toast">
  <span class="material-symbols-outlined text-lg">error</span><?= $error ?>
</div>
<?php endif; ?>

<div class="flex min-h-screen">

  <!-- ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ SIDEBAR ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ÃƒÂ¢Ã¢â‚¬ÂÃ¢â€šÂ¬ -->
  <aside class="sidebar hidden lg:flex flex-col sticky top-0 h-screen p-5">
    <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800 mb-4">
      <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-lg shadow-primary/30 font-bold">
        <?= strtoupper(substr($settings['site_name'],0,1)) ?>
      </div>
      <div>
        <h1 class="font-bold leading-none"><?= htmlspecialchars($settings['site_name']) ?></h1>
        <p class="text-xs text-slate-400 mt-0.5">Admin Panel</p>
      </div>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
      <a href="admin_dashboard.php" class="sidebar-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a>
      <a href="admin_users.php" class="sidebar-link"><span class="material-symbols-outlined">group</span>Users</a>
      <a href="admin_academics.php" class="sidebar-link active"><span class="material-symbols-outlined fill-icon">school</span>Academics</a>
      <a href="admin_ai_overview.php" class="sidebar-link"><span class="material-symbols-outlined">psychology</span>AI Overview</a>
      <a href="admin_qr_overview.php" class="sidebar-link"><span class="material-symbols-outlined">qr_code_scanner</span>QR Attendance</a>
      <a href="admin_settings.php" class="sidebar-link"><span class="material-symbols-outlined">settings</span>Settings</a>
    </nav>
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
      <a href="../auth/logout.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 transition-colors text-sm font-medium">
        <span class="material-symbols-outlined text-lg">logout</span>Logout
      </a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">

    <!-- Mobile Header -->
    <header class="lg:hidden sticky top-0 z-50 bg-white/90 dark:bg-bg-dark/90 backdrop-blur-md border-b border-primary/8 px-4 py-3 flex items-center justify-between">
      <h1 class="text-base font-bold">Academics</h1>
    </header>

    <!-- Desktop Header -->
    <header class="hidden lg:flex sticky top-0 z-40 bg-white/80 dark:bg-bg-dark/80 backdrop-blur-md border-b border-primary/8 px-6 py-4 items-center justify-between">
      <div>
        <h2 class="text-xl font-bold">Academic Management</h2>
        <p class="text-xs text-slate-400 mt-0.5">Branches, Subjects & Timetables ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“ Diploma Engineering</p>
      </div>
    </header>

    <main class="flex-1 p-4 md:p-6 pb-28 lg:pb-8 space-y-5 max-w-7xl w-full mx-auto">

      <!-- Top Tabs -->
      <div class="top-tabs no-scrollbar anim-fade-up d1">
        <button id="btnDepartments" class="top-tab <?= $activeTab==='departments'?'active':'' ?>" onclick="showTab('departments')">
          <span class="material-symbols-outlined align-middle text-lg mr-1" style="vertical-align:-4px">account_tree</span>Branches
        </button>
        <button id="btnSubjects" class="top-tab <?= $activeTab==='subjects'?'active':'' ?>" onclick="showTab('subjects')">
          <span class="material-symbols-outlined align-middle text-lg mr-1" style="vertical-align:-4px">book_2</span>Subjects
        </button>
        <button id="btnTimetables" class="top-tab <?= $activeTab==='timetables'?'active':'' ?>" onclick="showTab('timetables')">
          <span class="material-symbols-outlined align-middle text-lg mr-1" style="vertical-align:-4px">calendar_month</span>Timetable
        </button>
      </div>

      <!-- ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â DEPARTMENTS / BRANCHES ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â -->
      <section id="departmentsSection" class="tab-section <?= $activeTab==='departments'?'active':'' ?> space-y-4 anim-tab">

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 anim-fade-up d2">
          <div class="mini-stat">
            <div class="icon-box bg-blue-50"><span class="material-symbols-outlined text-blue-600">account_tree</span></div>
            <div><p class="text-xs text-slate-400 font-medium">Total Branches</p><p class="text-2xl font-extrabold"><?= $totalBranches ?></p></div>
          </div>
          <div class="mini-stat">
            <div class="icon-box bg-indigo-50"><span class="material-symbols-outlined text-indigo-600">book_2</span></div>
            <div><p class="text-xs text-slate-400 font-medium">Total Subjects</p><p class="text-2xl font-extrabold"><?= $totalSubjects ?></p></div>
          </div>
          <div class="mini-stat">
            <div class="icon-box bg-purple-50"><span class="material-symbols-outlined text-purple-600">calendar_month</span></div>
            <div><p class="text-xs text-slate-400 font-medium">Timetable Slots</p><p class="text-2xl font-extrabold"><?= $totalSlots ?></p></div>
          </div>
        </div>

        <!-- Add Branch -->
        <div class="card anim-fade-up d3">
          <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div><h3 class="font-bold">Branch Directory</h3><p class="text-xs text-slate-400">Diploma Engineering branches (3-year programs)</p></div>
            <button onclick="openModal('addBranchModal')" class="btn-primary">
              <span class="material-symbols-outlined text-lg">add_circle</span>Add Branch
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="dept-table">
              <thead><tr>
                <th>Branch Name</th><th>Code</th><th>Duration</th><th>Subjects</th><th class="text-right">Actions</th>
              </tr></thead>
              <tbody>
              <?php foreach ($branches_arr as $b):
                $bSubjectCount = count(array_filter($subjects_arr, fn($s) => $s['branch_code'] === $b['branch_code']));
                $initials = strtoupper(substr($b['branch_name'],0,2));
                $colors = ['bg-blue-100 text-blue-700','bg-indigo-100 text-indigo-700','bg-green-100 text-green-700','bg-purple-100 text-purple-700','bg-amber-100 text-amber-700','bg-rose-100 text-rose-700','bg-cyan-100 text-cyan-700','bg-teal-100 text-teal-700'];
                $colorClass = $colors[crc32($b['branch_code']) % count($colors)];
              ?>
              <tr>
                <td>
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs <?= $colorClass ?>"><?= $initials ?></div>
                    <span class="font-medium"><?= htmlspecialchars($b['branch_name']) ?></span>
                  </div>
                </td>
                <td><span class="font-mono text-xs bg-slate-100 dark:bg-slate-800 px-2 py-1 rounded-lg"><?= htmlspecialchars($b['branch_code']) ?></span></td>
                <td class="text-slate-500"><?= (int)ccv($b, 'duration_years', 3) ?> Years</td>
                <td><span class="text-slate-700 dark:text-slate-300 font-semibold"><?= $bSubjectCount ?></span> <span class="text-slate-400 text-xs">subjects</span></td>
                <td class="text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button onclick="openEditBranch(<?= (int)ccv($b, 'id', 0) ?>, '<?= htmlspecialchars(addslashes(ccv($b, 'branch_name', ''))) ?>', <?= (int)ccv($b, 'duration_years', 3) ?>)" class="btn-edit">
                      <span class="material-symbols-outlined text-sm">edit</span>Edit
                    </button>
                    <form method="POST" onsubmit="return confirm('Delete this branch?')">
                      <input type="hidden" name="action" value="delete_branch"/>
                      <input type="hidden" name="branch_id" value="<?= $b['id'] ?>"/>
                      <input type="hidden" name="active_tab" value="departments"/>
                      <button type="submit" class="btn-danger"><span class="material-symbols-outlined text-sm">delete</span></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($branches_arr)): ?>
              <tr><td colspan="5" class="text-center text-slate-400 py-8">No branches yet. Add your first branch above.</td></tr>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â SUBJECTS ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â -->
      <section id="subjectsSection" class="tab-section <?= $activeTab==='subjects'?'active':'' ?> space-y-4">

        <!-- Filters -->
        <div class="card p-4 anim-fade-up d1">
          <form method="GET" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="tab" value="subjects"/>
            <div class="flex-1 min-w-[140px]">
              <label class="form-label">Branch</label>
              <select name="sb_branch" class="form-input">
                <option value="">All Branches</option>
                <?php foreach ($branches_arr as $b): ?>
                <option value="<?= $b['branch_code'] ?>" <?= $filter_branch===$b['branch_code']?'selected':'' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="w-36">
              <label class="form-label">Semester</label>
              <select name="sb_sem" class="form-input">
                <option value="">All Semesters</option>
                <?php for($i=1;$i<=6;$i++): ?>
                <option value="<?= $i ?>" <?= $filter_sem==$i?'selected':'' ?>>Semester <?= $i ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="w-36">
              <label class="form-label">Type</label>
              <select name="sb_type" class="form-input">
                <option value="">All Types</option>
                <option value="Core" <?= $filter_type==='Core'?'selected':'' ?>>Core</option>
                <option value="Elective" <?= $filter_type==='Elective'?'selected':'' ?>>Elective</option>
                <option value="Practical" <?= $filter_type==='Practical'?'selected':'' ?>>Practical</option>
                <option value="Theory" <?= $filter_type==='Theory'?'selected':'' ?>>Theory</option>
              </select>
            </div>
            <button type="submit" class="btn-primary">
              <span class="material-symbols-outlined text-lg">filter_list</span>Filter
            </button>
            <button type="button" onclick="openModal('addSubjectModal')" class="btn-primary">
              <span class="material-symbols-outlined text-lg">add</span>Add Subject
            </button>
          </form>
        </div>

        <div class="space-y-2 anim-fade-up d2">
          <?php if (empty($filtered_subjects)): ?>
          <div class="card p-10 text-center text-slate-400">
            <span class="material-symbols-outlined text-4xl mb-2 block">book_2</span>
            No subjects found. Add subjects for Diploma branches.
          </div>
          <?php endif; ?>
          <?php foreach ($filtered_subjects as $s):
            $typeClass=['Core'=>'badge-core','Elective'=>'badge-elective','Practical'=>'badge-practical','Theory'=>'badge-theory'][$s['subject_type']] ?? 'badge-core';
            $iconMap=['Core'=>'terminal','Elective'=>'psychology','Practical'=>'science','Theory'=>'book'];
            $icon=$iconMap[$s['subject_type']] ?? 'book';
          ?>
          <div class="subject-row">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined"><?= $icon ?></span>
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <p class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($s['subject_name']) ?></p>
                  <span class="badge <?= $typeClass ?>"><?= $s['subject_type'] ?></span>
                </div>
                <div class="flex items-center gap-3 mt-0.5">
                  <span class="text-xs text-slate-400 font-mono"><?= htmlspecialchars($s['subject_code']) ?></span>
                  <span class="text-xs text-slate-400"><?= htmlspecialchars($s['branch_code']) ?> Ãƒâ€šÃ‚Â· Sem <?= $s['semester'] ?></span>
                  <?php if ((int)ccv($s, 'credits', 0) > 0): ?><span class="text-xs text-slate-400"><?= (int)ccv($s, 'credits', 0) ?> credits</span><?php endif; ?>
                </div>
                <?php if ($s['teacher_name']): ?>
                <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs">person</span><?= htmlspecialchars($s['teacher_name']) ?>
                </p>
                <?php endif; ?>
              </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
              <button onclick="openEditSubject(<?= htmlspecialchars(json_encode($s)) ?>)" class="btn-edit">
                <span class="material-symbols-outlined text-sm">edit</span>
              </button>
              <form method="POST" onsubmit="return confirm('Delete this subject?')">
                <input type="hidden" name="action" value="delete_subject"/>
                <input type="hidden" name="subject_id" value="<?= $s['id'] ?>"/>
                <input type="hidden" name="active_tab" value="subjects"/>
                <input type="hidden" name="sb_branch" value="<?= htmlspecialchars($filter_branch) ?>"/>
                <input type="hidden" name="sb_sem" value="<?= htmlspecialchars($filter_sem) ?>"/>
                <button type="submit" class="btn-danger"><span class="material-symbols-outlined text-sm">delete</span></button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â TIMETABLE ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â -->
      <section id="timetablesSection" class="tab-section <?= $activeTab==='timetables'?'active':'' ?> space-y-4">

        <!-- Timetable Filters (GET-based for URL sharing) -->
        <form method="GET" class="card p-4 flex flex-wrap gap-3 items-end anim-fade-up d1">
          <input type="hidden" name="tab" value="timetables"/>
          <div class="flex-1 min-w-[150px]">
            <label class="form-label">Branch</label>
            <select name="tt_branch" class="form-input" onchange="this.form.submit()">
              <?php foreach ($branches_arr as $b): ?>
              <option value="<?= $b['branch_code'] ?>" <?= $tt_branch===$b['branch_code']?'selected':'' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="w-36">
            <label class="form-label">Semester</label>
            <select name="tt_sem" class="form-input" onchange="this.form.submit()">
              <?php for($i=1;$i<=6;$i++): ?>
              <option value="<?= $i ?>" <?= $tt_sem==$i?'selected':'' ?>>Semester <?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="w-32">
            <label class="form-label">Section</label>
            <select name="tt_section" class="form-input" onchange="this.form.submit()">
              <?php foreach (['A','B','C','D'] as $sec): ?>
              <option value="<?= $sec ?>" <?= $tt_section===$sec?'selected':'' ?>>Section <?= $sec ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="button" onclick="openModal('addSlotModal')" class="btn-primary ml-auto">
            <span class="material-symbols-outlined text-lg">calendar_add_on</span><span class="hidden sm:inline">Add Slot</span>
          </button>
        </form>

        <!-- Day Picker -->
        <div class="day-picker anim-fade-up d2">
          <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day): ?>
          <a href="?tab=timetables&tt_branch=<?= urlencode($tt_branch) ?>&tt_sem=<?= $tt_sem ?>&tt_section=<?= urlencode($tt_section) ?>&tt_day=<?= urlencode($day) ?>" class="day-btn <?= $tt_day===$day?'active':'' ?>">
            <?= substr($day,0,3) ?>
          </a>
          <?php endforeach; ?>
        </div>

        <!-- Slots -->
        <div class="space-y-3 anim-fade-up d3">
          <?php if (empty($timetables_arr)): ?>
          <div class="card p-10 text-center text-slate-400">
            <span class="material-symbols-outlined text-4xl mb-2 block">calendar_month</span>
            No slots for <?= htmlspecialchars($tt_day) ?>. Add a slot using the button above.
          </div>
          <?php endif; ?>

          <?php foreach ($timetables_arr as $slot):
            $slotColors=['Lecture'=>'#4349cf','Lab'=>'#22c55e','Break'=>'#f59e0b','Lunch'=>'#94a3b8'];
            $color=$slotColors[$slot['slot_type']] ?? '#4349cf';
            $slotClass='slot-'.strtolower($slot['slot_type']);
          ?>
          <div class="tt-slot">
            <div class="time-col">
              <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><?= date('h:i',strtotime($slot['start_time'])) ?></p>
              <p class="text-[10px] text-slate-400 uppercase"><?= date('A',strtotime($slot['start_time'])) ?></p>
              <p class="text-[10px] text-slate-300 mt-1">ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬Å“</p>
              <p class="text-[10px] text-slate-400"><?= date('h:i A',strtotime($slot['end_time'])) ?></p>
            </div>
            <div class="slot-card <?= $slotClass ?>" style="border-left-color:<?= $color ?>">
              <div class="flex items-start justify-between gap-2">
                <div class="flex-1">
                  <?php if ($slot['slot_type']==='Break' || $slot['slot_type']==='Lunch'): ?>
                    <h4 class="font-bold text-slate-600 dark:text-slate-300"><?= $slot['slot_type'] ?> Time</h4>
                  <?php else: ?>
                    <h4 class="font-bold text-slate-800 dark:text-white"><?= htmlspecialchars($slot['subject_name'] ?? 'No Subject') ?></h4>
                    <p class="text-xs text-slate-400 mt-0.5 font-mono"><?= htmlspecialchars($slot['subject_code'] ?? '') ?></p>
                  <?php endif; ?>
                  <div class="flex flex-wrap gap-3 mt-2">
                    <?php if ($slot['teacher_name']): ?>
                    <p class="text-xs text-slate-500 flex items-center gap-1"><span class="material-symbols-outlined text-xs">person</span><?= htmlspecialchars($slot['teacher_name']) ?></p>
                    <?php endif; ?>
                    <?php if ($slot['room']): ?>
                    <p class="text-xs text-slate-500 flex items-center gap-1"><span class="material-symbols-outlined text-xs">location_on</span><?= htmlspecialchars($slot['room']) ?></p>
                    <?php endif; ?>
                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold" style="background:<?= $color ?>22;color:<?= $color ?>"><?= $slot['slot_type'] ?></span>
                  </div>
                </div>
                <div class="flex gap-1 shrink-0">
                  <button onclick="openEditSlot(<?= htmlspecialchars(json_encode($slot)) ?>)" class="p-1.5 rounded-lg hover:bg-primary/10 text-slate-400 hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-lg">edit</span>
                  </button>
                  <form method="POST" onsubmit="return confirm('Delete this slot?')">
                    <input type="hidden" name="action" value="delete_slot"/>
                    <input type="hidden" name="slot_id" value="<?= $slot['id'] ?>"/>
                    <input type="hidden" name="active_tab" value="timetables"/>
                    <button type="submit" class="p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition-colors">
                      <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </section>
    </main>
  </div>
</div>

<!-- Bottom Nav Mobile -->
<nav class="bottom-nav lg:hidden fixed bottom-0 left-0 right-0 z-50 px-4 py-3 flex justify-around" style="padding-bottom:max(.75rem,env(safe-area-inset-bottom));">
  <a href="admin_dashboard.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">home</span><p>Home</p>
  </a>
  <a href="admin_users.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">group</span><p>Users</p>
  </a>
  <a href="admin_ai_overview.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">psychology</span><p>AI</p>
  </a>
  <a href="admin_settings.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">settings</span><p>Settings</p>
  </a>
</nav>

<!-- ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â MODALS ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â -->

<!-- Add Branch Modal -->
<div class="modal-backdrop" id="addBranchModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="font-bold text-lg">Add New Branch</h3>
      <button onclick="closeModal('addBranchModal')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_branch"/>
      <input type="hidden" name="active_tab" value="departments"/>
      <div class="modal-body space-y-4">
        <div>
          <label class="form-label">Branch Code *</label>
          <input type="text" name="branch_code" class="form-input" placeholder="e.g. CO, ME, CE" maxlength="10" required/>
          <p class="text-xs text-slate-400 mt-1">Short uppercase code (max 10 chars)</p>
        </div>
        <div>
          <label class="form-label">Branch Name *</label>
          <input type="text" name="branch_name" class="form-input" placeholder="e.g. Computer Engineering" required/>
        </div>
        <div>
          <label class="form-label">Duration</label>
          <select name="duration_years" class="form-input">
            <option value="3" selected>3 Years (Diploma)</option>
            <option value="4">4 Years (Degree)</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('addBranchModal')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
        <button type="submit" class="btn-primary">Add Branch</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Branch Modal -->
<div class="modal-backdrop" id="editBranchModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="font-bold text-lg">Edit Branch</h3>
      <button onclick="closeModal('editBranchModal')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_branch"/>
      <input type="hidden" name="active_tab" value="departments"/>
      <input type="hidden" name="branch_id" id="editBranchId"/>
      <div class="modal-body space-y-4">
        <div>
          <label class="form-label">Branch Name *</label>
          <input type="text" name="branch_name" id="editBranchName" class="form-input" required/>
        </div>
        <div>
          <label class="form-label">Duration</label>
          <select name="duration_years" id="editBranchYears" class="form-input">
            <option value="3">3 Years (Diploma)</option>
            <option value="4">4 Years (Degree)</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('editBranchModal')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Subject Modal -->
<div class="modal-backdrop" id="addSubjectModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="font-bold text-lg">Add New Subject</h3>
      <button onclick="closeModal('addSubjectModal')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_subject"/>
      <input type="hidden" name="active_tab" value="subjects"/>
      <div class="modal-body space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Subject Code *</label>
            <input type="text" name="subject_code" class="form-input" placeholder="e.g. CO301" required/>
          </div>
          <div>
            <label class="form-label">Credits</label>
            <input type="number" name="credits" class="form-input" value="3" min="1" max="6"/>
          </div>
        </div>
        <div>
          <label class="form-label">Subject Name *</label>
          <input type="text" name="subject_name" class="form-input" placeholder="e.g. Data Structures" required/>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Branch *</label>
            <select name="branch_code" class="form-input" required>
              <option value="">Select Branch</option>
              <?php foreach ($branches_arr as $b): ?>
              <option value="<?= $b['branch_code'] ?>"><?= htmlspecialchars($b['branch_name']) ?> (<?= $b['branch_code'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Semester *</label>
            <select name="semester" class="form-input" required>
              <?php for($i=1;$i<=6;$i++): ?>
              <option value="<?= $i ?>">Semester <?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Type</label>
            <select name="subject_type" class="form-input">
              <option value="Core">Core</option>
              <option value="Elective">Elective</option>
              <option value="Practical">Practical</option>
              <option value="Theory">Theory</option>
            </select>
          </div>
          <div>
            <label class="form-label">Assign Teacher</label>
            <select name="teacher_id" class="form-input">
              <option value="">Not Assigned</option>
              <?php foreach ($teachers_arr as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('addSubjectModal')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
        <button type="submit" class="btn-primary">Add Subject</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Subject Modal -->
<div class="modal-backdrop" id="editSubjectModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="font-bold text-lg">Edit Subject</h3>
      <button onclick="closeModal('editSubjectModal')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_subject"/>
      <input type="hidden" name="active_tab" value="subjects"/>
      <input type="hidden" name="sb_branch" value="<?= htmlspecialchars($filter_branch) ?>"/>
      <input type="hidden" name="sb_sem" value="<?= htmlspecialchars($filter_sem) ?>"/>
      <input type="hidden" name="subject_id" id="editSubjectId"/>
      <div class="modal-body space-y-4">
        <div>
          <label class="form-label">Subject Name *</label>
          <input type="text" name="subject_name" id="editSubjectName" class="form-input" required/>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Branch *</label>
            <select name="branch_code" id="editSubjectBranch" class="form-input" required>
              <?php foreach ($branches_arr as $b): ?>
              <option value="<?= $b['branch_code'] ?>"><?= htmlspecialchars($b['branch_name']) ?> (<?= $b['branch_code'] ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Semester *</label>
            <select name="semester" id="editSubjectSem" class="form-input" required>
              <?php for($i=1;$i<=6;$i++): ?>
              <option value="<?= $i ?>">Semester <?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Type</label>
            <select name="subject_type" id="editSubjectType" class="form-input">
              <option value="Core">Core</option>
              <option value="Elective">Elective</option>
              <option value="Practical">Practical</option>
              <option value="Theory">Theory</option>
            </select>
          </div>
          <div>
            <label class="form-label">Credits</label>
            <input type="number" name="credits" id="editSubjectCredits" class="form-input" min="1" max="6"/>
          </div>
        </div>
        <div>
          <label class="form-label">Assign Teacher</label>
          <select name="teacher_id" id="editSubjectTeacher" class="form-input">
            <option value="">Not Assigned</option>
            <?php foreach ($teachers_arr as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('editSubjectModal')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Add Timetable Slot Modal -->
<div class="modal-backdrop" id="addSlotModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="font-bold text-lg">Add Timetable Slot</h3>
      <button onclick="closeModal('addSlotModal')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_slot"/>
      <input type="hidden" name="active_tab" value="timetables"/>
      <div class="modal-body space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Branch *</label>
            <select name="branch_code" class="form-input" required>
              <?php foreach ($branches_arr as $b): ?>
              <option value="<?= $b['branch_code'] ?>" <?= $tt_branch===$b['branch_code']?'selected':'' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Semester *</label>
            <select name="semester" class="form-input" required>
              <?php for($i=1;$i<=6;$i++): ?>
              <option value="<?= $i ?>" <?= $tt_sem==$i?'selected':'' ?>>Semester <?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Section</label>
            <select name="section" class="form-input">
              <?php foreach (['A','B','C','D'] as $sec): ?>
              <option value="<?= $sec ?>" <?= $tt_section===$sec?'selected':'' ?>><?= $sec ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Day *</label>
            <select name="day_name" class="form-input" required>
              <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
              <option value="<?= $d ?>" <?= $tt_day===$d?'selected':'' ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Start Time *</label>
            <input type="time" name="start_time" class="form-input" required/>
          </div>
          <div>
            <label class="form-label">End Time *</label>
            <input type="time" name="end_time" class="form-input" required/>
          </div>
        </div>
        <div>
          <label class="form-label">Slot Type</label>
          <select name="slot_type" class="form-input" id="addSlotType" onchange="toggleSubjectFields('add')">
            <option value="Lecture">Lecture</option>
            <option value="Lab">Lab / Practical</option>
            <option value="Break">Short Break</option>
            <option value="Lunch">Lunch Break</option>
          </select>
        </div>
        <div id="addSubjectFields">
          <div>
            <label class="form-label">Subject</label>
            <select name="subject_id" class="form-input">
              <option value="">-- Select Subject --</option>
              <?php foreach ($subjects_arr as $s): ?>
              <option value="<?= $s['id'] ?>">[<?= $s['branch_code'] ?> Sem<?= $s['semester'] ?>] <?= htmlspecialchars($s['subject_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mt-4">
            <label class="form-label">Teacher</label>
            <select name="teacher_id" class="form-input">
              <option value="">-- Select Teacher --</option>
              <?php foreach ($teachers_arr as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mt-4">
            <label class="form-label">Room / Venue</label>
            <input type="text" name="room" class="form-input" placeholder="e.g. Room 301, Lab B"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('addSlotModal')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
        <button type="submit" class="btn-primary">Add Slot</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Slot Modal -->
<div class="modal-backdrop" id="editSlotModal">
  <div class="modal">
    <div class="modal-header">
      <h3 class="font-bold text-lg">Edit Timetable Slot</h3>
      <button onclick="closeModal('editSlotModal')" class="p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500"><span class="material-symbols-outlined">close</span></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit_slot"/>
      <input type="hidden" name="active_tab" value="timetables"/>
      <input type="hidden" name="slot_id" id="editSlotId"/>
      <div class="modal-body space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Day</label>
            <select name="day_name" id="editSlotDay" class="form-input">
              <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?>
              <option value="<?= $d ?>"><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Slot Type</label>
            <select name="slot_type" id="editSlotType" class="form-input" onchange="toggleSubjectFields('edit')">
              <option value="Lecture">Lecture</option>
              <option value="Lab">Lab / Practical</option>
              <option value="Break">Short Break</option>
              <option value="Lunch">Lunch Break</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Start Time</label>
            <input type="time" name="start_time" id="editSlotStart" class="form-input"/>
          </div>
          <div>
            <label class="form-label">End Time</label>
            <input type="time" name="end_time" id="editSlotEnd" class="form-input"/>
          </div>
        </div>
        <div id="editSubjectFields">
          <div>
            <label class="form-label">Subject</label>
            <select name="subject_id" id="editSlotSubject" class="form-input">
              <option value="">-- Select Subject --</option>
              <?php foreach ($subjects_arr as $s): ?>
              <option value="<?= $s['id'] ?>">[<?= $s['branch_code'] ?> Sem<?= $s['semester'] ?>] <?= htmlspecialchars($s['subject_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mt-4">
            <label class="form-label">Teacher</label>
            <select name="teacher_id" id="editSlotTeacher" class="form-input">
              <option value="">-- Select Teacher --</option>
              <?php foreach ($teachers_arr as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mt-4">
            <label class="form-label">Room / Venue</label>
            <input type="text" name="room" id="editSlotRoom" class="form-input"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" onclick="closeModal('editSlotModal')" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-semibold text-slate-600 dark:text-slate-300">Cancel</button>
        <button type="submit" class="btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
// ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢?? TAB SWITCHING ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??
const activeTabInit = '<?= $activeTab ?>';
function showTab(name) {
  document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.top-tab').forEach(b => b.classList.remove('active'));
  document.getElementById(name + 'Section').classList.add('active');
  document.getElementById('btn' + name.charAt(0).toUpperCase() + name.slice(1)).classList.add('active');
  const sec = document.getElementById(name + 'Section');
  sec.classList.remove('anim-tab'); void sec.offsetWidth; sec.classList.add('anim-tab');
  window.scrollTo({ top: 0, behavior: 'smooth' });
}
// Set active tab on load
document.addEventListener('DOMContentLoaded', () => {
  if (activeTabInit) showTab(activeTabInit);
});

// Ãƒ???Ãƒ???Ãƒ??? MODAL HELPERS Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(b => {
  b.addEventListener('click', e => { if (e.target === b) b.classList.remove('open'); });
});

// Ãƒ???Ãƒ???Ãƒ??? BRANCH EDIT Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???
function openEditBranch(id, name, years) {
  document.getElementById('editBranchId').value = id;
  document.getElementById('editBranchName').value = name;
  document.getElementById('editBranchYears').value = years;
  openModal('editBranchModal');
}

// Ãƒ???Ãƒ???Ãƒ??? SUBJECT EDIT Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???
function openEditSubject(s) {
  document.getElementById('editSubjectId').value = s.id;
  document.getElementById('editSubjectName').value = s.subject_name;
  document.getElementById('editSubjectBranch').value = s.branch_code;
  document.getElementById('editSubjectSem').value = s.semester;
  document.getElementById('editSubjectType').value = s.subject_type;
  document.getElementById('editSubjectCredits').value = s.credits;
  document.getElementById('editSubjectTeacher').value = s.teacher_id || '';
  openModal('editSubjectModal');
}

// Ãƒ???Ãƒ???Ãƒ??? SLOT EDIT Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???
function openEditSlot(slot) {
  document.getElementById('editSlotId').value = slot.id;
  document.getElementById('editSlotDay').value = slot.day_name;
  document.getElementById('editSlotType').value = slot.slot_type;
  document.getElementById('editSlotStart').value = slot.start_time ? slot.start_time.substring(0,5) : '';
  document.getElementById('editSlotEnd').value = slot.end_time ? slot.end_time.substring(0,5) : '';
  document.getElementById('editSlotSubject').value = slot.subject_id || '';
  document.getElementById('editSlotTeacher').value = slot.teacher_id || '';
  document.getElementById('editSlotRoom').value = slot.room || '';
  toggleSubjectFields('edit');
  openModal('editSlotModal');
}

// Hide subject fields for Break/Lunch
function toggleSubjectFields(prefix) {
  const type = document.getElementById(prefix === 'add' ? 'addSlotType' : 'editSlotType').value;
  const fields = document.getElementById(prefix === 'add' ? 'addSubjectFields' : 'editSubjectFields');
  fields.style.display = (type === 'Break' || type === 'Lunch') ? 'none' : 'block';
}

// Ãƒ???Ãƒ???Ãƒ??? TOAST AUTO-DISMISS Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???Ãƒ???
const toast = document.getElementById('toast');
if (toast) { setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateY(-10px)'; toast.style.transition = 'all .4s'; setTimeout(() => toast.remove(), 400); }, 3500); }
</script>
</body>
</html>