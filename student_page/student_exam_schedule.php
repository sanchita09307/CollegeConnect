<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$activeNav   = 'exams';
$studentId   = (int)($student['id'] ?? 0);
$dept        = $student['department'] ?? '';
$sem         = (int)($student['semester'] ?? 1);
$rollNo      = $student['student_roll_no'] ?? 'N/A';
$studentName = $student['full_name'] ?? 'Student';

// ── Ensure tables ────────────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS exam_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_name VARCHAR(200) NOT NULL,
  branch_code VARCHAR(20),
  semester INT,
  subject_id INT,
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

// ── Fetch exam schedule for this student's branch + semester ─────────────────
$exams = [];
$today = date('Y-m-d');
$res = $conn->prepare("SELECT * FROM exam_schedules WHERE (branch_code=? OR branch_code IS NULL OR branch_code='') AND semester=? ORDER BY exam_date ASC, start_time ASC");
$res->bind_param("si", $dept, $sem);
$res->execute();
$rows = $res->get_result();
while ($r = $rows->fetch_assoc()) $exams[] = $r;
$res->close();

$upcoming = array_filter($exams, fn($e) => $e['exam_date'] >= $today);
$past     = array_filter($exams, fn($e) => $e['exam_date'] < $today);

// Hall ticket active config
$htConfig = $conn->query("SELECT * FROM hall_ticket_config WHERE is_active=1 ORDER BY id DESC LIMIT 1")?->fetch_assoc();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Exam Schedule – CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
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
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.nav-tab.active{color:#4349cf}
.nav-tab.active .nav-icon{font-variation-settings:'FILL' 1}
/* Exam type chips */
.chip-internal{background:#eff6ff;color:#2563eb;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.chip-external{background:#fef3c7;color:#b45309;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.chip-practical{background:#f0fdf4;color:#15803d;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.chip-viva{background:#fdf4ff;color:#9333ea;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
/* Hall ticket print styles */
@media print {
  #printable-ht * { visibility: visible; }
  body > *:not(#printable-ht) { display: none; }
  #printable-ht { position: fixed; top: 0; left: 0; width: 100%; z-index: 9999; background: white; padding: 20px; }
  .no-print { display: none !important; }
}
.modal-bg{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:none;align-items:flex-end}
.modal-bg.open{display:flex}
.modal-panel{background:white;border-radius:24px 24px 0 0;width:100%;max-height:92vh;overflow-y:auto;animation:slideUp .35s cubic-bezier(.22,1,.36,1) both}
.dark .modal-panel{background:#13142a}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-800 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<main class="px-4 pt-4 pb-28 space-y-4">

  <!-- Hero -->
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/40 fu">
    <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Exam Schedule</p>
    <h2 class="text-xl font-bold"><?= htmlspecialchars($dept) ?> — Semester <?= $sem ?></h2>
    <p class="text-white/70 text-xs mt-1"><?= count($upcoming) ?> upcoming exam<?= count($upcoming) !== 1 ? 's' : '' ?></p>
    <?php if ($htConfig): ?>
    <button onclick="showHallTicket()" class="mt-3 flex items-center gap-1.5 bg-white/20 hover:bg-white/30 transition-colors text-white text-xs font-bold px-4 py-2 rounded-xl">
      <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1">badge</span>
      Download Hall Ticket
    </button>
    <?php endif; ?>
  </div>

  <!-- Quick stats -->
  <div class="grid grid-cols-3 gap-2 fu1">
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-primary"><?= count($exams) ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Total Exams</p>
    </div>
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-amber-500"><?= count($upcoming) ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Upcoming</p>
    </div>
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-green-600"><?= count($past) ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Completed</p>
    </div>
  </div>

  <!-- Upcoming Exams -->
  <div class="fu2">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-base" style="font-variation-settings:'FILL' 1">upcoming</span>
      Upcoming Exams
    </h3>
    <?php if (empty($upcoming)): ?>
    <div class="card p-8 text-center">
      <span class="material-symbols-outlined text-5xl text-primary/20 block mb-3" style="font-variation-settings:'FILL' 1">quiz</span>
      <p class="text-sm font-bold">No upcoming exams</p>
      <p class="text-xs text-slate-400 mt-1">Check back when the admin publishes the schedule</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
      <?php foreach ($upcoming as $exam):
        $daysLeft = (int)((strtotime($exam['exam_date']) - time()) / 86400);
        $chipClass = 'chip-' . strtolower($exam['exam_type'] ?? 'internal');
        $urgent = $daysLeft <= 3;
      ?>
      <div class="card p-4 <?= $urgent ? 'border-red-200 dark:border-red-900' : '' ?>">
        <div class="flex items-start gap-3">
          <!-- Date badge -->
          <div class="shrink-0 w-12 text-center rounded-xl py-1.5 <?= $urgent ? 'bg-red-50 dark:bg-red-900/30' : 'bg-primary/8' ?>">
            <p class="text-[10px] font-bold <?= $urgent ? 'text-red-500' : 'text-primary' ?>"><?= date('M', strtotime($exam['exam_date'])) ?></p>
            <p class="text-xl font-extrabold <?= $urgent ? 'text-red-600' : 'text-primary' ?> leading-none"><?= date('d', strtotime($exam['exam_date'])) ?></p>
            <p class="text-[9px] <?= $urgent ? 'text-red-400' : 'text-slate-400' ?>"><?= date('D', strtotime($exam['exam_date'])) ?></p>
          </div>
          <!-- Details -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1">
              <span class="<?= $chipClass ?>"><?= $exam['exam_type'] ?></span>
              <?php if ($urgent): ?>
              <span style="background:#fee2e2;color:#dc2626;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px">
                <?= $daysLeft === 0 ? 'Today!' : ($daysLeft === 1 ? 'Tomorrow!' : "In $daysLeft days") ?>
              </span>
              <?php endif; ?>
            </div>
            <p class="font-bold text-sm"><?= htmlspecialchars($exam['subject_name'] ?? $exam['exam_name']) ?></p>
            <?php if ($exam['subject_code']): ?>
            <p class="text-xs text-slate-400"><?= htmlspecialchars($exam['subject_code']) ?></p>
            <?php endif; ?>
            <div class="flex items-center gap-3 mt-2 text-xs text-slate-500 flex-wrap">
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">schedule</span>
                <?= date('h:i A', strtotime($exam['start_time'])) ?> – <?= date('h:i A', strtotime($exam['end_time'])) ?>
              </span>
              <?php if ($exam['room']): ?>
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">meeting_room</span>
                Room <?= htmlspecialchars($exam['room']) ?>
              </span>
              <?php endif; ?>
              <?php if ($exam['max_marks']): ?>
              <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-xs">grade</span>
                <?= $exam['max_marks'] ?> Marks
              </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Past Exams -->
  <?php if (!empty($past)): ?>
  <div class="fu3">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
      <span class="material-symbols-outlined text-slate-400 text-base">history</span>
      Past Exams
    </h3>
    <div class="space-y-2">
      <?php foreach (array_reverse($past) as $exam): ?>
      <div class="card p-3 opacity-70">
        <div class="flex items-center gap-3">
          <div class="w-10 text-center">
            <p class="text-xs font-bold text-slate-400"><?= date('d M', strtotime($exam['exam_date'])) ?></p>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold"><?= htmlspecialchars($exam['subject_name'] ?? $exam['exam_name']) ?></p>
            <p class="text-xs text-slate-400"><?= date('h:i A', strtotime($exam['start_time'])) ?> <?= $exam['room'] ? '· Room '.$exam['room'] : '' ?></p>
          </div>
          <span class="chip-<?= strtolower($exam['exam_type']) ?>"><?= $exam['exam_type'] ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</main>

<!-- Bottom nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2 z-40">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">home</span><span class="text-[10px] font-bold">Home</span></a>
    <a href="student_attendance.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">assignment_turned_in</span><span class="text-[10px] font-medium">Attend.</span></a>
    <a href="student_exam_schedule.php" class="nav-tab active flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl text-primary"><span class="material-symbols-outlined nav-icon text-xl">quiz</span><span class="text-[10px] font-bold">Exams</span></a>
    <a href="student_message.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">message</span><span class="text-[10px] font-medium">Messages</span></a>
    <a href="student_profile.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">person</span><span class="text-[10px] font-medium">Profile</span></a>
  </div>
</nav>

<!-- Hall Ticket Modal -->
<div id="htModal" class="modal-bg" onclick="if(event.target===this)closeModal('htModal')">
  <div class="modal-panel" id="printable-ht">
    <div class="p-5">
      <div class="flex items-center justify-between mb-5 no-print">
        <h3 class="font-bold text-base">Hall Ticket</h3>
        <div class="flex gap-2">
          <button onclick="window.print()" class="flex items-center gap-1.5 px-4 py-2 rounded-xl text-white text-xs font-bold" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
            <span class="material-symbols-outlined text-sm">print</span> Print
          </button>
          <button onclick="closeModal('htModal')" class="p-1.5 rounded-xl hover:bg-slate-100"><span class="material-symbols-outlined text-slate-500">close</span></button>
        </div>
      </div>
      <!-- Hall Ticket Content -->
      <div class="border-2 border-primary/20 rounded-2xl overflow-hidden">
        <!-- Header -->
        <div class="hero-grad p-5 text-white text-center">
          <p class="font-bold text-base"><?= htmlspecialchars($settings['site_name'] ?? 'CollegeConnect') ?></p>
          <p class="text-white/80 text-xs mt-0.5"><?= htmlspecialchars($htConfig['exam_name'] ?? 'Examination Hall Ticket') ?></p>
          <p class="text-white/70 text-xs"><?= htmlspecialchars($htConfig['academic_year'] ?? date('Y').'-'.(date('Y')+1)) ?></p>
        </div>
        <!-- Student Info -->
        <div class="p-4 grid grid-cols-2 gap-3 bg-white dark:bg-slate-900">
          <div>
            <p class="text-xs text-slate-400 font-semibold">Student Name</p>
            <p class="text-sm font-bold"><?= htmlspecialchars($studentName) ?></p>
          </div>
          <div>
            <p class="text-xs text-slate-400 font-semibold">Roll No.</p>
            <p class="text-sm font-bold"><?= htmlspecialchars($rollNo) ?></p>
          </div>
          <div>
            <p class="text-xs text-slate-400 font-semibold">Branch</p>
            <p class="text-sm font-bold"><?= htmlspecialchars($dept) ?></p>
          </div>
          <div>
            <p class="text-xs text-slate-400 font-semibold">Semester</p>
            <p class="text-sm font-bold">Semester <?= $sem ?></p>
          </div>
        </div>
        <!-- Exam Table -->
        <div class="px-4 pb-4 bg-white dark:bg-slate-900">
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Exam Schedule</p>
          <?php if (!empty($upcoming)): ?>
          <div class="rounded-xl overflow-hidden border border-slate-100 dark:border-slate-700">
            <table class="w-full text-xs">
              <thead class="bg-slate-50 dark:bg-slate-800">
                <tr>
                  <th class="text-left px-3 py-2 text-slate-500 font-bold">Subject</th>
                  <th class="text-left px-3 py-2 text-slate-500 font-bold">Date</th>
                  <th class="text-left px-3 py-2 text-slate-500 font-bold">Time</th>
                  <th class="text-left px-3 py-2 text-slate-500 font-bold">Room</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($upcoming as $e): ?>
                <tr class="border-t border-slate-50 dark:border-slate-800">
                  <td class="px-3 py-2 font-medium"><?= htmlspecialchars($e['subject_name'] ?? $e['exam_name']) ?></td>
                  <td class="px-3 py-2 text-slate-600"><?= date('d/m/Y', strtotime($e['exam_date'])) ?></td>
                  <td class="px-3 py-2 text-slate-600"><?= date('h:i A', strtotime($e['start_time'])) ?></td>
                  <td class="px-3 py-2 text-slate-600"><?= htmlspecialchars($e['room'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <p class="text-xs text-slate-400 text-center py-4">No upcoming exams scheduled</p>
          <?php endif; ?>
        </div>
        <!-- Instructions -->
        <?php if ($htConfig && $htConfig['instructions']): ?>
        <div class="px-4 pb-4 bg-slate-50 dark:bg-slate-800/50">
          <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Instructions</p>
          <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed"><?= nl2br(htmlspecialchars($htConfig['instructions'])) ?></p>
        </div>
        <?php endif; ?>
        <!-- Signature -->
        <div class="flex justify-end px-4 pb-4 bg-white dark:bg-slate-900">
          <div class="text-center">
            <div class="w-24 h-0.5 bg-slate-400 mb-1"></div>
            <p class="text-xs text-slate-500">Principal's Signature</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
function showHallTicket(){ openModal('htModal'); }
</script>
<?php include 'topbar_scripts.php'; ?>
</body>
</html>