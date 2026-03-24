<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$activeNav   = 'leave';
$studentId   = (int)($student['id'] ?? 0);
$studentName = $student['full_name'] ?? ($student['name'] ?? 'Student');

/* ─────────────────────────────────────────────────────────────────────────────
   Ensure leave_requests table exists
───────────────────────────────────────────────────────────────────────────── */
$conn->query("
    CREATE TABLE IF NOT EXISTS leave_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_role ENUM('student','teacher') DEFAULT 'student',
        user_name VARCHAR(150),
        department VARCHAR(100),
        reason TEXT,
        leave_type VARCHAR(50) DEFAULT 'Personal',
        from_date DATE NOT NULL,
        to_date DATE NOT NULL,
        total_days INT DEFAULT 1,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        admin_remark TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
");

/* ─────────────────────────────────────────────────────────────────────────────
   Add missing columns if old table already exists
───────────────────────────────────────────────────────────────────────────── */
function ensureColumnExists(mysqli $conn, string $table, string $column, string $definition): void {
    $safeTable  = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $safeColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

    $check = $conn->query("SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition");
    }
}

ensureColumnExists($conn, 'leave_requests', 'department', "VARCHAR(100) NULL AFTER `user_name`");
ensureColumnExists($conn, 'leave_requests', 'leave_type', "VARCHAR(50) DEFAULT 'Personal' AFTER `reason`");
ensureColumnExists($conn, 'leave_requests', 'total_days', "INT DEFAULT 1 AFTER `to_date`");
ensureColumnExists($conn, 'leave_requests', 'status', "ENUM('pending','approved','rejected') DEFAULT 'pending' AFTER `total_days`");
ensureColumnExists($conn, 'leave_requests', 'admin_remark', "TEXT NULL AFTER `status`");
ensureColumnExists($conn, 'leave_requests', 'updated_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");

/* ─────────────────────────────────────────────────────────────────────────────
   Safe student activity log
───────────────────────────────────────────────────────────────────────────── */
function logStudentActivity(mysqli $conn, int $userId, string $activityText, string $activityType = 'leave'): void {
    $check = $conn->query("SHOW COLUMNS FROM activity_logs LIKE 'activity_type'");

    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, user_role, activity_type, activity_text)
            VALUES (?, 'student', ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $activityType, $activityText);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("
            INSERT INTO activity_logs (user_id, user_role, activity_text)
            VALUES (?, 'student', ?)
        ");
        if ($stmt) {
            $stmt->bind_param("is", $userId, $activityText);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   Handle new leave application
───────────────────────────────────────────────────────────────────────────── */
$msg = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $reason    = trim($_POST['reason'] ?? '');
    $leaveType = trim($_POST['leave_type'] ?? 'Personal');
    $fromDate  = trim($_POST['from_date'] ?? '');
    $toDate    = trim($_POST['to_date'] ?? '');

    $allowedLeaveTypes = ['Medical', 'Personal', 'Family', 'Academic', 'Emergency', 'Other'];
    if (!in_array($leaveType, $allowedLeaveTypes, true)) {
        $leaveType = 'Personal';
    }

    if ($reason !== '' && $fromDate !== '' && $toDate !== '' && $fromDate <= $toDate) {
        try {
            $from = new DateTime($fromDate);
            $to   = new DateTime($toDate);
            $days = $to->diff($from)->days + 1;

            $dept = $student['department'] ?? '';

            $stmt = $conn->prepare("
                INSERT INTO leave_requests
                (user_id, user_role, user_name, department, reason, leave_type, from_date, to_date, total_days)
                VALUES (?, 'student', ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt) {
                $stmt->bind_param(
                    "issssssi",
                    $studentId,
                    $studentName,
                    $dept,
                    $reason,
                    $leaveType,
                    $fromDate,
                    $toDate,
                    $days
                );

                if ($stmt->execute()) {
                    $msg = 'Leave application submitted successfully!';
                    $msgType = 'success';

                    logStudentActivity(
                        $conn,
                        $studentId,
                        "Applied for {$days}-day {$leaveType} leave",
                        'leave_apply'
                    );
                } else {
                    $msg = 'Failed to submit leave application.';
                    $msgType = 'error';
                }

                $stmt->close();
            } else {
                $msg = 'Database prepare error.';
                $msgType = 'error';
            }
        } catch (Exception $e) {
            $msg = 'Invalid date selected.';
            $msgType = 'error';
        }
    } else {
        $msg = 'Please fill all fields correctly. From date must be before or equal to To date.';
        $msgType = 'error';
    }
}

/* ─────────────────────────────────────────────────────────────────────────────
   Fetch this student's leave history
───────────────────────────────────────────────────────────────────────────── */
$leaveHistory = [];

$res = $conn->prepare("
    SELECT *
    FROM leave_requests
    WHERE user_id = ? AND user_role = 'student'
    ORDER BY created_at DESC, id DESC
");

if ($res) {
    $res->bind_param("i", $studentId);
    $res->execute();
    $rows = $res->get_result();

    while ($r = $rows->fetch_assoc()) {
        $leaveHistory[] = $r;
    }

    $res->close();
}

$totalApplied  = count($leaveHistory);
$totalApproved = count(array_filter($leaveHistory, fn($l) => ($l['status'] ?? '') === 'approved'));
$totalPending  = count(array_filter($leaveHistory, fn($l) => ($l['status'] ?? '') === 'pending'));
$totalDaysUsed = array_sum(array_column(array_filter($leaveHistory, fn($l) => ($l['status'] ?? '') === 'approved'), 'total_days'));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Leave Application – CollegeConnect</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode:"class",
  theme:{
    extend:{
      colors:{
        primary:"#4349cf",
        "background-light":"#eef0ff",
        "background-dark":"#0d0e1c"
      },
      fontFamily:{
        display:["Lexend"]
      }
    }
  }
}
</script>
<style>
*{font-family:'Lexend',sans-serif}
body{min-height:100dvh;background:#eef0ff}
.dark body{background:#0d0e1c}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes topbarIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulseRed{0%,100%{opacity:1}50%{opacity:.4}}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.fu{animation:fadeUp .4s ease both}
.fu1{animation:fadeUp .4s .07s ease both}
.fu2{animation:fadeUp .4s .14s ease both}
.fu3{animation:fadeUp .4s .21s ease both}
.fu4{animation:fadeUp .4s .28s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.nav-tab.active{color:#4349cf}
.nav-tab.active .nav-icon{font-variation-settings:'FILL' 1}
.modal-bg{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:none;align-items:flex-end}
.modal-bg.open{display:flex}
.modal-panel{background:white;border-radius:24px 24px 0 0;width:100%;max-height:90vh;overflow-y:auto;animation:slideUp .35s cubic-bezier(.22,1,.36,1) both}
.dark .modal-panel{background:#13142a}
.badge-pending{background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px}
.badge-approved{background:#dcfce7;color:#166534;font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px}
.badge-rejected{background:#fee2e2;color:#991b1b;font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px}
.type-chip{font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px;background:#eff6ff;color:#2563eb}
input,select,textarea{font-family:'Lexend',sans-serif!important}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-800 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<main class="px-4 pt-4 pb-28 space-y-4">

  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/40 fu">
    <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Leave Management</p>
    <h2 class="text-xl font-bold">Apply for Leave</h2>
    <p class="text-white/70 text-xs mt-1">Submit your leave application and track status</p>
    <button onclick="openModal('applyModal')" class="mt-3 flex items-center gap-1.5 bg-white/20 hover:bg-white/30 transition-colors text-white text-xs font-bold px-4 py-2 rounded-xl">
      <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1">add_circle</span>
      New Application
    </button>
  </div>

  <div class="grid grid-cols-4 gap-2 fu1">
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-primary"><?= $totalApplied ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Applied</p>
    </div>
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-amber-500"><?= $totalPending ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Pending</p>
    </div>
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-green-600"><?= $totalApproved ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Approved</p>
    </div>
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-indigo-600"><?= $totalDaysUsed ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Days Used</p>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="card p-3 flex items-center gap-3 fu <?= $msgType === 'success' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' ?>">
    <span class="material-symbols-outlined text-lg <?= $msgType === 'success' ? 'text-green-600' : 'text-red-600' ?>" style="font-variation-settings:'FILL' 1">
      <?= $msgType === 'success' ? 'check_circle' : 'error' ?>
    </span>
    <p class="text-sm font-medium <?= $msgType === 'success' ? 'text-green-800' : 'text-red-800' ?>"><?= htmlspecialchars($msg) ?></p>
  </div>
  <?php endif; ?>

  <div class="fu2">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-base" style="font-variation-settings:'FILL' 1">history</span>
      My Leave Applications
    </h3>

    <div class="space-y-3">
      <?php if (empty($leaveHistory)): ?>
      <div class="card p-8 text-center">
        <span class="material-symbols-outlined text-5xl text-primary/20 block mb-3" style="font-variation-settings:'FILL' 1">event_busy</span>
        <p class="text-sm font-bold">No leave applications yet</p>
        <p class="text-xs text-slate-400 mt-1">Tap "New Application" to apply for leave</p>
      </div>
      <?php else: ?>
        <?php foreach ($leaveHistory as $lv): 
          $days = (int)($lv['total_days'] ?? 1);
          $from = !empty($lv['from_date']) ? date('d M', strtotime($lv['from_date'])) : '-';
          $to   = !empty($lv['to_date']) ? date('d M Y', strtotime($lv['to_date'])) : '-';
          $status = $lv['status'] ?? 'pending';
        ?>
        <div class="card p-4">
          <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="type-chip"><?= htmlspecialchars($lv['leave_type'] ?? 'Personal') ?></span>
                <span class="badge-<?= htmlspecialchars($status) ?> capitalize"><?= htmlspecialchars($status) ?></span>
              </div>

              <p class="text-sm font-semibold mt-1"><?= htmlspecialchars($lv['reason'] ?? '') ?></p>

              <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                <span class="flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs">calendar_month</span>
                  <?= $from ?> → <?= $to ?>
                </span>
                <span class="flex items-center gap-1">
                  <span class="material-symbols-outlined text-xs">schedule</span>
                  <?= $days ?> day<?= $days > 1 ? 's' : '' ?>
                </span>
              </div>

              <?php if (!empty($lv['admin_remark'])): ?>
              <div class="mt-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg px-3 py-2">
                <p class="text-xs text-slate-500 font-semibold">Remark:</p>
                <p class="text-xs text-slate-700 dark:text-slate-300"><?= htmlspecialchars($lv['admin_remark']) ?></p>
              </div>
              <?php endif; ?>
            </div>

            <div class="text-right shrink-0">
              <?php
              $icon = match($status) {
                  'approved' => ['check_circle','text-green-500'],
                  'rejected' => ['cancel','text-red-500'],
                  default    => ['pending','text-amber-500']
              };
              ?>
              <span class="material-symbols-outlined <?= $icon[1] ?>" style="font-size:28px;font-variation-settings:'FILL' 1"><?= $icon[0] ?></span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</main>

<nav class="fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2 z-40">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl">
      <span class="material-symbols-outlined nav-icon text-xl">home</span>
      <span class="text-[10px] font-bold">Home</span>
    </a>
    <a href="student_attendance.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl">
      <span class="material-symbols-outlined nav-icon text-xl">assignment_turned_in</span>
      <span class="text-[10px] font-medium">Attend.</span>
    </a>
    <a href="student_leave.php" class="nav-tab active flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl text-primary">
      <span class="material-symbols-outlined nav-icon text-xl">event_busy</span>
      <span class="text-[10px] font-bold">Leave</span>
    </a>
    <a href="student_message.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl">
      <span class="material-symbols-outlined nav-icon text-xl">message</span>
      <span class="text-[10px] font-medium">Messages</span>
    </a>
    <a href="student_profile.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl">
      <span class="material-symbols-outlined nav-icon text-xl">person</span>
      <span class="text-[10px] font-medium">Profile</span>
    </a>
  </div>
</nav>

<div id="applyModal" class="modal-bg" onclick="if(event.target===this)closeModal('applyModal')">
  <div class="modal-panel p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-base">New Leave Application</h3>
      <button onclick="closeModal('applyModal')" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800" type="button">
        <span class="material-symbols-outlined text-slate-500">close</span>
      </button>
    </div>

    <form method="POST" class="space-y-4">
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Leave Type</label>
        <select name="leave_type" required class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30">
          <option value="Medical">Medical / Health</option>
          <option value="Personal" selected>Personal</option>
          <option value="Family">Family Function</option>
          <option value="Academic">Academic Event</option>
          <option value="Emergency">Emergency</option>
          <option value="Other">Other</option>
        </select>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">From Date</label>
          <input type="date" name="from_date" required min="<?= date('Y-m-d') ?>" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">To Date</label>
          <input type="date" name="to_date" required min="<?= date('Y-m-d') ?>" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
      </div>

      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Reason for Leave</label>
        <textarea name="reason" required rows="3" placeholder="Briefly describe your reason..." class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
      </div>

      <button type="submit" name="apply_leave" class="w-full py-3 rounded-xl text-white font-bold text-sm" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
        Submit Application
      </button>
    </form>
  </div>
</div>

<script>
function openModal(id){
  document.getElementById(id).classList.add('open');
}
function closeModal(id){
  document.getElementById(id).classList.remove('open');
}
</script>

<?php include 'topbar_scripts.php'; ?>
</body>
</html>