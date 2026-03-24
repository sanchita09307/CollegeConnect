<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';
$settings = getSiteSettings($conn);

// ÃƒÂ¢??ÃƒÂ¢?? Ensure tables ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??
$conn->query("CREATE TABLE IF NOT EXISTS exam_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_name VARCHAR(200) NOT NULL,
  branch_code VARCHAR(20),
  semester INT,
  subject_name VARCHAR(150),
  subject_code VARCHAR(30),
  exam_date DATE,
  start_time TIME,
  end_time TIME,
  room VARCHAR(50),
  exam_type ENUM('Internal','External','Practical','Viva') DEFAULT 'Internal',
  max_marks INT DEFAULT 100,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS hall_ticket_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_name VARCHAR(200),
  academic_year VARCHAR(20),
  instructions TEXT,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$msg = ''; $msgType = '';

// ÃƒÂ¢??ÃƒÂ¢?? Handle POST ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??ÃƒÂ¢??
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Add Exam
    if (isset($_POST['add_exam'])) {
        $examName  = trim($_POST['exam_name'] ?? '');
        $branch    = trim($_POST['branch_code'] ?? '');
        $sem       = (int)($_POST['semester'] ?? 0);
        $subName   = trim($_POST['subject_name'] ?? '');
        $subCode   = trim($_POST['subject_code'] ?? '');
        $examDate  = $_POST['exam_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime   = $_POST['end_time'] ?? '';
        $room      = trim($_POST['room'] ?? '');
        $examType  = $_POST['exam_type'] ?? 'Internal';
        $maxMarks  = (int)($_POST['max_marks'] ?? 100);

        if ($examName && $examDate && $startTime && $endTime) {
            $stmt = $conn->prepare("INSERT INTO exam_schedules (exam_name,branch_code,semester,subject_name,subject_code,exam_date,start_time,end_time,room,exam_type,max_marks) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("ssissssssi", $examName, $branch, $sem, $subName, $subCode, $examDate, $startTime, $endTime, $room, $examType, $maxMarks);
            $stmt->execute(); $stmt->close();
            $msg = 'Exam added successfully!'; $msgType = 'success';
            $conn->query("INSERT INTO activity_logs (user_id,user_role,activity_text) VALUES ({$_SESSION['user_id']},'admin','Added exam: $examName')");
        } else { $msg = 'Please fill all required fields.'; $msgType = 'error'; }
    }

    // Delete Exam
    if (isset($_POST['delete_exam'])) {
        $id = (int)$_POST['exam_id'];
        $conn->query("DELETE FROM exam_schedules WHERE id=$id");
        $msg = 'Exam deleted.'; $msgType = 'warning';
    }

    // Save Hall Ticket Config
    if (isset($_POST['save_ht_config'])) {
        $htName  = trim($_POST['ht_exam_name'] ?? '');
        $htYear  = trim($_POST['ht_year'] ?? date('Y').'-'.(date('Y')+1));
        $htInstr = trim($_POST['ht_instructions'] ?? '');
        // Deactivate old, insert new
        $conn->query("UPDATE hall_ticket_config SET is_active=0");
        $stmt = $conn->prepare("INSERT INTO hall_ticket_config (exam_name,academic_year,instructions,is_active) VALUES (?,?,?,1)");
        $stmt->bind_param("sss", $htName, $htYear, $htInstr);
        $stmt->execute(); $stmt->close();
        $msg = 'Hall ticket configuration saved!'; $msgType = 'success';
    }
}

// Fetch data
$exams    = [];
$res = $conn->query("SELECT * FROM exam_schedules ORDER BY exam_date ASC, start_time ASC");
if ($res) while ($r = $res->fetch_assoc()) $exams[] = $r;

$htConfig = $conn->query("SELECT * FROM hall_ticket_config WHERE is_active=1 ORDER BY id DESC LIMIT 1")?->fetch_assoc();

// Fetch branches for dropdown
$branches = [];
$br = $conn->query("SELECT branch_code, branch_name FROM branches ORDER BY branch_name");
if ($br) while ($r = $br->fetch_assoc()) $branches[] = $r;

$today    = date('Y-m-d');
$upcoming = array_filter($exams, fn($e) => $e['exam_date'] >= $today);
$past     = array_filter($exams, fn($e) => $e['exam_date'] < $today);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Exam Schedule ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“ Admin</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{sans:["Plus Jakarta Sans","sans-serif"]}}}}</script>
<style>
*{font-family:'Plus Jakarta Sans',sans-serif}
body{background:#f0f2ff;min-height:100dvh}
.dark body{background:#0d0e1a}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.fu{animation:fadeUp .4s ease both}
.card{background:#fff;border-radius:1rem;border:1px solid rgba(67,73,207,.08);box-shadow:0 2px 12px rgba(67,73,207,.06)}
.dark .card{background:#161728;border-color:rgba(67,73,207,.15)}
.sidebar{background:#fff;border-right:1px solid rgba(67,73,207,.08);width:260px;flex-shrink:0}
.dark .sidebar{background:#161728}
.sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#64748b;transition:all .2s;text-decoration:none}
.sidebar-link:hover{background:rgba(67,73,207,.06);color:#4349cf}
.sidebar-link.active{background:#4349cf;color:#fff}
.btn-primary{background:linear-gradient(135deg,#4349cf,#7479f5);color:#fff;font-weight:700;border-radius:.75rem;padding:.65rem 1.25rem;font-size:.875rem;border:none;cursor:pointer}
.chip-internal{background:#eff6ff;color:#2563eb;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.chip-external{background:#fef3c7;color:#b45309;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.chip-practical{background:#f0fdf4;color:#15803d;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.chip-viva{background:#fdf4ff;color:#9333ea;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.modal-bg{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:16px}
.modal-bg.open{display:flex}
.modal-panel{background:white;border-radius:1.5rem;width:100%;max-width:540px;max-height:90vh;overflow-y:auto}
.dark .modal-panel{background:#1a1b2e}
.fill-icon{font-variation-settings:'FILL' 1}
.tab-btn{padding:7px 16px;border-radius:9999px;font-size:12px;font-weight:700;border:none;cursor:pointer;transition:all .18s}
.tab-btn.active{background:#4349cf;color:#fff}
.tab-btn:not(.active){background:#fff;color:#64748b;border:1.5px solid #e2e8f0}
.bottom-nav{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-top:1px solid rgba(67,73,207,.08)}
.nav-item{display:flex;flex-direction:column;align-items:center;gap:2px;color:#94a3b8;font-size:9px;font-weight:700;text-transform:uppercase;text-decoration:none}
.nav-item.active{color:#4349cf}
input,select,textarea{font-family:'Plus Jakarta Sans',sans-serif!important}
</style>
</head>
<body class="dark:text-slate-100">
<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="sidebar hidden lg:flex flex-col sticky top-0 h-screen p-5 overflow-y-auto">
    <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800 mb-4">
      <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white"><span class="material-symbols-outlined fill-icon text-xl">school</span></div>
      <div><h1 class="font-bold text-slate-800 dark:text-white leading-none"><?= htmlspecialchars($settings['site_name'] ?? 'CollegeConnect') ?></h1><p class="text-xs text-slate-400">Admin Central</p></div>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1">Main</p>
      <a href="admin_dashboard.php" class="sidebar-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a>
      <a href="admin_users.php"     class="sidebar-link"><span class="material-symbols-outlined">group</span>Users</a>
      <a href="admin_academics.php" class="sidebar-link"><span class="material-symbols-outlined">school</span>Academics</a>
      <a href="admin_ai_overview.php" class="sidebar-link"><span class="material-symbols-outlined">psychology</span>AI Overview</a>
      <a href="admin_qr_overview.php" class="sidebar-link"><span class="material-symbols-outlined">qr_code_scanner</span>QR Attendance</a>
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">Management</p>
      <a href="admin_leaves.php"        class="sidebar-link"><span class="material-symbols-outlined">event_busy</span>Leave Requests</a>
      <a href="admin_exams.php"         class="sidebar-link active"><span class="material-symbols-outlined">quiz</span>Exam Schedule</a>
      <a href="admin_notifications.php" class="sidebar-link"><span class="material-symbols-outlined">notifications</span>Notifications</a>
      <a href="admin_announcements.php" class="sidebar-link"><span class="material-symbols-outlined">campaign</span>Announcements</a>
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">System</p>
      <a href="admin_settings.php" class="sidebar-link"><span class="material-symbols-outlined">settings</span>Settings</a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col min-w-0">
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-[#0d0e1a]/80 backdrop-blur-md border-b border-primary/8 px-4 md:px-6 py-4 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Exam Schedule</h2>
        <p class="text-xs text-slate-400"><?= count($upcoming) ?> upcoming Ãƒâ€šÃ‚Â· <?= count($past) ?> past</p>
      </div>
      <button onclick="openModal('addExamModal')" class="btn-primary flex items-center gap-2">
        <span class="material-symbols-outlined text-base">add</span> Add Exam
      </button>
    </header>

    <main class="flex-1 p-4 md:p-6 pb-24 lg:pb-8 space-y-5 max-w-5xl w-full mx-auto">

      <?php if ($msg): ?>
      <div class="card p-3 flex items-center gap-3 <?= $msgType==='success'?'border-green-200 bg-green-50':($msgType==='warning'?'border-amber-200 bg-amber-50':'border-red-200 bg-red-50') ?> fu">
        <span class="material-symbols-outlined text-lg fill-icon <?= $msgType==='success'?'text-green-600':($msgType==='warning'?'text-amber-600':'text-red-600') ?>"><?= $msgType==='success'?'check_circle':($msgType==='warning'?'warning':'error') ?></span>
        <p class="text-sm font-medium"><?= htmlspecialchars($msg) ?></p>
      </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="flex gap-2 fu">
        <button class="tab-btn active" onclick="showTab('upcoming',this)">Upcoming (<?= count($upcoming) ?>)</button>
        <button class="tab-btn" onclick="showTab('past',this)">Past (<?= count($past) ?>)</button>
        <button class="tab-btn" onclick="showTab('hallticket',this)">Hall Ticket Config</button>
      </div>

      <!-- Upcoming exams -->
      <div id="tab-upcoming" class="space-y-3 fu">
        <?php if (empty($upcoming)): ?>
        <div class="card p-10 text-center">
          <span class="material-symbols-outlined text-5xl text-slate-200 fill-icon block mb-3">quiz</span>
          <p class="font-bold text-slate-500">No upcoming exams</p>
          <button onclick="openModal('addExamModal')" class="mt-3 text-sm text-primary font-semibold hover:underline">+ Add First Exam</button>
        </div>
        <?php else: foreach ($upcoming as $exam):
          $chipClass = 'chip-' . strtolower($exam['exam_type'] ?? 'internal');
        ?>
        <div class="card p-4 fu">
          <div class="flex items-start gap-4">
            <div class="shrink-0 w-14 text-center bg-primary/8 rounded-xl py-2">
              <p class="text-xs font-bold text-primary"><?= date('M', strtotime($exam['exam_date'])) ?></p>
              <p class="text-2xl font-extrabold text-primary leading-none"><?= date('d', strtotime($exam['exam_date'])) ?></p>
              <p class="text-[10px] text-slate-400"><?= date('D', strtotime($exam['exam_date'])) ?></p>
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <span class="<?= $chipClass ?>"><?= $exam['exam_type'] ?></span>
                <?php if ($exam['branch_code']): ?>
                <span class="text-xs bg-indigo-50 text-indigo-600 font-bold px-2 py-0.5 rounded-full"><?= htmlspecialchars($exam['branch_code']) ?></span>
                <?php endif; ?>
                <?php if ($exam['semester']): ?>
                <span class="text-xs bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded-full">Sem <?= $exam['semester'] ?></span>
                <?php endif; ?>
              </div>
              <p class="font-bold text-sm"><?= htmlspecialchars($exam['subject_name'] ?: $exam['exam_name']) ?></p>
              <?php if ($exam['subject_code']): ?>
              <p class="text-xs text-slate-400"><?= htmlspecialchars($exam['exam_name']) ?> Ãƒâ€šÃ‚Â· <?= htmlspecialchars($exam['subject_code']) ?></p>
              <?php else: ?>
              <p class="text-xs text-slate-400"><?= htmlspecialchars($exam['exam_name']) ?></p>
              <?php endif; ?>
              <div class="flex items-center gap-4 mt-2 text-xs text-slate-500 flex-wrap">
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">schedule</span><?= date('h:i A', strtotime($exam['start_time'])) ?> ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“ <?= date('h:i A', strtotime($exam['end_time'])) ?></span>
                <?php if ($exam['room']): ?><span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">meeting_room</span>Room <?= htmlspecialchars($exam['room']) ?></span><?php endif; ?>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">grade</span><?= $exam['max_marks'] ?> marks</span>
              </div>
            </div>
            <form method="POST" onsubmit="return confirm('Delete this exam?')">
              <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>"/>
              <button type="submit" name="delete_exam" class="p-2 rounded-xl hover:bg-red-50 text-red-400 hover:text-red-600 transition-colors">
                <span class="material-symbols-outlined text-base">delete</span>
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

      <!-- Past exams -->
      <div id="tab-past" class="space-y-2 hidden">
        <?php foreach (array_reverse($past) as $exam): ?>
        <div class="card p-3 opacity-70 fu">
          <div class="flex items-center gap-3">
            <div class="w-10 text-center">
              <p class="text-xs font-bold text-slate-500"><?= date('d M', strtotime($exam['exam_date'])) ?></p>
            </div>
            <div class="flex-1">
              <p class="text-sm font-semibold"><?= htmlspecialchars($exam['subject_name'] ?: $exam['exam_name']) ?></p>
              <p class="text-xs text-slate-400"><?= date('h:i A', strtotime($exam['start_time'])) ?> <?= $exam['room'] ? 'Ãƒâ€šÃ‚Â· Room '.$exam['room'] : '' ?> <?= $exam['branch_code'] ? 'Ãƒâ€šÃ‚Â· '.$exam['branch_code'] : '' ?></p>
            </div>
            <span class="chip-<?= strtolower($exam['exam_type']) ?>"><?= $exam['exam_type'] ?></span>
            <form method="POST" onsubmit="return confirm('Delete?')">
              <input type="hidden" name="exam_id" value="<?= $exam['id'] ?>"/>
              <button type="submit" name="delete_exam" class="p-1.5 rounded-xl hover:bg-red-50 text-red-400"><span class="material-symbols-outlined text-sm">delete</span></button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($past)): ?>
        <div class="card p-8 text-center"><p class="text-slate-400 text-sm">No past exams</p></div>
        <?php endif; ?>
      </div>

      <!-- Hall Ticket Config -->
      <div id="tab-hallticket" class="hidden">
        <div class="card p-5 fu">
          <h3 class="font-bold text-base mb-1 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary fill-icon">badge</span>
            Hall Ticket Configuration
          </h3>
          <p class="text-xs text-slate-400 mb-5">Configure the hall ticket that students can download from their exam schedule page</p>
          <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Exam Name *</label>
                <input type="text" name="ht_exam_name" value="<?= htmlspecialchars($htConfig['exam_name'] ?? '') ?>" placeholder="e.g. End Semester Examination" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
              </div>
              <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Academic Year *</label>
                <input type="text" name="ht_year" value="<?= htmlspecialchars($htConfig['academic_year'] ?? date('Y').'-'.(date('Y')+1)) ?>" placeholder="e.g. 2025-2026" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
              </div>
            </div>
            <div>
              <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Instructions for Students</label>
              <textarea name="ht_instructions" rows="5" placeholder="1. Carry this hall ticket to the exam hall.&#10;2. Mobile phones are not allowed.&#10;3. Report 15 minutes before exam time." class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"><?= htmlspecialchars($htConfig['instructions'] ?? "1. Carry this hall ticket to the exam hall.\n2. Mobile phones are not allowed.\n3. Report 15 minutes before exam time.\n4. Bring a valid ID proof.") ?></textarea>
            </div>
            <div class="flex items-center gap-3">
              <button type="submit" name="save_ht_config" class="btn-primary">Save Configuration</button>
              <?php if ($htConfig): ?>
              <span class="text-xs text-green-600 font-semibold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm fill-icon">check_circle</span> Active ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â students can download hall tickets
              </span>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Mobile nav -->
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

<!-- Add Exam Modal -->
<div id="addExamModal" class="modal-bg" onclick="if(event.target===this)closeModal('addExamModal')">
  <div class="modal-panel p-5">
    <div class="flex items-center justify-between mb-5">
      <h3 class="font-bold text-base">Add Exam</h3>
      <button onclick="closeModal('addExamModal')" class="p-1.5 rounded-xl hover:bg-slate-100"><span class="material-symbols-outlined text-slate-500">close</span></button>
    </div>
    <form method="POST" class="space-y-4">
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Exam Name *</label>
        <input type="text" name="exam_name" required placeholder="e.g. End Semester Exam" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Branch</label>
          <select name="branch_code" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option value="">All Branches</option>
            <?php foreach ($branches as $b): ?>
            <option value="<?= htmlspecialchars($b['branch_code']) ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Semester</label>
          <select name="semester" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            <?php for ($i=1;$i<=8;$i++): ?><option value="<?= $i ?>">Sem <?= $i ?></option><?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Subject Name</label>
          <input type="text" name="subject_name" placeholder="e.g. Data Structures" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Subject Code</label>
          <input type="text" name="subject_code" placeholder="e.g. CO301" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
      </div>
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Exam Date *</label>
        <input type="date" name="exam_date" required min="<?= date('Y-m-d') ?>" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Start Time *</label>
          <input type="time" name="start_time" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">End Time *</label>
          <input type="time" name="end_time" required class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Exam Type</label>
          <select name="exam_type" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
            <option>Internal</option><option>External</option><option>Practical</option><option>Viva</option>
          </select>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Max Marks</label>
          <input type="number" name="max_marks" value="100" min="1" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
      </div>
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Room / Venue</label>
        <input type="text" name="room" placeholder="e.g. Hall A, Room 201" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"/>
      </div>
      <button type="submit" name="add_exam" class="btn-primary w-full py-3">Add Exam</button>
    </form>
  </div>
</div>

<script>
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function showTab(tab, btn) {
  ['upcoming','past','hallticket'].forEach(t => {
    document.getElementById('tab-'+t).classList.add('hidden');
  });
  document.getElementById('tab-'+tab).classList.remove('hidden');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
</script>
</body>
</html>