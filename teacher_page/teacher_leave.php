<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings    = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$teacherId   = (int)($teacher['id'] ?? 0);
$teacherName = $teacher['full_name'] ?? ($teacher['name'] ?? 'Teacher');
$dept        = $conn->real_escape_string($teacher['department'] ?? '');

// ── Get branch codes from teacher's subjects (handles "Computer Engineering" vs "CO" mismatch) ──
$branchCodes = [];
$bcRes = $conn->query("SELECT DISTINCT branch_code FROM subjects WHERE teacher_id = $teacherId AND branch_code IS NOT NULL AND branch_code != ''");
if ($bcRes) while ($bc = $bcRes->fetch_assoc()) $branchCodes[] = $conn->real_escape_string($bc['branch_code']);

// Build dept IN clause for queries — includes full name + all branch codes
$deptValues = array_unique(array_filter(array_merge([$dept], $branchCodes)));
$deptIn = implode("','", $deptValues);

$pageTitle  = 'Leave Requests';
$activePage = 'leave';

$photo = !empty($teacher['profile_photo'])
    ? "../uploads/profile_photos/" . htmlspecialchars($teacher['profile_photo'])
    : "https://ui-avatars.com/api/?name=" . urlencode($teacherName) . "&background=4349cf&color=fff&bold=true&size=80";

// ── Ensure table ─────────────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS leave_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_role ENUM('student','teacher') DEFAULT 'student',
  user_name VARCHAR(150), department VARCHAR(100), reason TEXT,
  leave_type VARCHAR(50) DEFAULT 'Personal',
  from_date DATE NOT NULL, to_date DATE NOT NULL,
  total_days INT DEFAULT 1,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  admin_remark TEXT, approved_by VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
// Add approved_by column if missing
$cc = $conn->query("SHOW COLUMNS FROM leave_requests LIKE 'approved_by'");
if (!$cc || $cc->num_rows === 0) {
    $conn->query("ALTER TABLE leave_requests ADD COLUMN approved_by VARCHAR(100) DEFAULT NULL AFTER admin_remark");
}

$msg = ''; $msgType = '';

// ── Approve / Reject student leave ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['leave_id'])) {
    $lid    = (int)$_POST['leave_id'];
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $remark = trim($_POST['remark'] ?? '');
    // Security: only leaves from this teacher's dept or branch codes
    $chk = $conn->query("SELECT id FROM leave_requests WHERE id=$lid AND department IN ('$deptIn') AND user_role='student'");
    if ($chk && $chk->num_rows > 0) {
        $upd = $conn->prepare("UPDATE leave_requests SET status=?, admin_remark=?, approved_by=?, updated_at=NOW() WHERE id=?");
        $upd->bind_param("sssi", $action, $remark, $teacherName, $lid);
        $upd->execute(); $upd->close();
        $msg = "Leave " . ucfirst($action) . " successfully!";
        $msgType = $action === 'approved' ? 'success' : 'warning';
        // Log if activity_logs exists
        if ($conn->query("SHOW TABLES LIKE 'activity_logs'")->num_rows > 0) {
            $conn->query("INSERT INTO activity_logs (user_id,user_role,activity_text) VALUES ($teacherId,'teacher','Leave #$lid $action by $teacherName')");
        }
    } else { $msg = 'Unauthorized.'; $msgType = 'error'; }

}

// ── Apply own leave ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_leave'])) {
    $reason    = trim($_POST['reason'] ?? '');
    $leaveType = trim($_POST['leave_type'] ?? 'Personal');
    $fromDate  = $_POST['from_date'] ?? '';
    $toDate    = $_POST['to_date'] ?? '';
    if ($reason && $fromDate && $toDate && $fromDate <= $toDate) {
        $days = (new DateTime($toDate))->diff(new DateTime($fromDate))->days + 1;
        $s = $conn->prepare("INSERT INTO leave_requests (user_id,user_role,user_name,department,reason,leave_type,from_date,to_date,total_days) VALUES (?,'teacher',?,?,?,?,?,?,?)");
        $s->bind_param("issssssi", $teacherId, $teacherName, $dept, $reason, $leaveType, $fromDate, $toDate, $days);
        $s->execute(); $s->close();
        $msg = 'Your leave application submitted!'; $msgType = 'success';
    } else { $msg = 'Fill all fields correctly. From date must be ≤ To date.'; $msgType = 'error'; }
}

// ── Fetch student leaves (this dept + branch codes) ─────────────────────────
$studentLeaves = [];
$r1 = $conn->query("SELECT * FROM leave_requests WHERE user_role='student' AND department IN ('$deptIn') ORDER BY FIELD(status,'pending','approved','rejected'), created_at DESC");
if ($r1) while ($r = $r1->fetch_assoc()) $studentLeaves[] = $r;

$pendingCount  = count(array_filter($studentLeaves, fn($l) => $l['status'] === 'pending'));
$approvedCount = count(array_filter($studentLeaves, fn($l) => $l['status'] === 'approved'));
$rejectedCount = count(array_filter($studentLeaves, fn($l) => $l['status'] === 'rejected'));

// ── Fetch teacher's own leaves ────────────────────────────────────────────────
$myLeaves = [];
$r2 = $conn->prepare("SELECT * FROM leave_requests WHERE user_id=? AND user_role='teacher' ORDER BY created_at DESC");
$r2->bind_param("i", $teacherId); $r2->execute();
$rw2 = $r2->get_result();
while ($r = $rw2->fetch_assoc()) $myLeaves[] = $r;
$r2->close();
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Leave Requests – CollegeConnect</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
*{font-family:'Lexend',sans-serif}
body{min-height:100dvh;background:#f0f1ff}
.dark body{background:#0d0e1c}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.92)}to{opacity:1;transform:scale(1)}}
.fu{animation:fadeUp .4s ease both}
.fu1{animation:fadeUp .4s .07s ease both}
.fu2{animation:fadeUp .4s .14s ease both}
.fu3{animation:fadeUp .4s .21s ease both}
.card{background:white;border-radius:1.25rem;border:1px solid #eef0ff;box-shadow:0 2px 12px rgba(67,73,207,.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.grad{background:linear-gradient(135deg,#4349cf,#7479f5)}
.badge-pending{background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;display:inline-block}
.badge-approved{background:#dcfce7;color:#166534;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;display:inline-block}
.badge-rejected{background:#fee2e2;color:#991b1b;font-size:10px;font-weight:700;padding:3px 10px;border-radius:99px;display:inline-block}
.tab-btn{padding:7px 16px;border-radius:9999px;font-size:11px;font-weight:700;border:none;cursor:pointer;transition:all .18s}
.tab-btn.active{background:#4349cf;color:white}
.tab-btn:not(.active){background:white;color:#64748b;border:1.5px solid #e2e8f0}
.dark .tab-btn:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45}
.btn-approve{background:#dcfce7;color:#166534;font-weight:700;border-radius:.75rem;padding:.45rem 1rem;font-size:.8rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .18s}
.btn-approve:hover{background:#bbf7d0;transform:translateY(-1px)}
.btn-reject{background:#fee2e2;color:#991b1b;font-weight:700;border-radius:.75rem;padding:.45rem 1rem;font-size:.8rem;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;transition:all .18s}
.btn-reject:hover{background:#fecaca;transform:translateY(-1px)}
.modal-bg{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.52);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:16px}
.modal-bg.open{display:flex}
.modal-panel{background:white;border-radius:1.5rem;width:100%;max-width:440px;max-height:90vh;overflow-y:auto;animation:scaleIn .28s cubic-bezier(.34,1.4,.64,1) both}
.dark .modal-panel{background:#1a1b2e}
.slide-modal-bg{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.52);backdrop-filter:blur(4px);display:none;align-items:flex-end}
.slide-modal-bg.open{display:flex}
.slide-modal-panel{background:white;border-radius:24px 24px 0 0;width:100%;max-height:90vh;overflow-y:auto;animation:slideUp .32s cubic-bezier(.22,1,.36,1) both}
.dark .slide-modal-panel{background:#1a1b2e}
.leave-card-pending{border-left:4px solid #f59e0b}
.leave-card-approved{border-left:4px solid #22c55e}
.leave-card-rejected{border-left:4px solid #ef4444}
input,select,textarea{font-family:'Lexend',sans-serif!important}
</style>
</head>
<body class="dark:text-slate-100">

<?php include 'teacher_topbar.php'; ?>

<main class="px-4 pt-4 pb-28 space-y-4 max-w-2xl mx-auto">

  <!-- Hero -->
  <div class="grad rounded-2xl p-5 text-white shadow-lg fu relative overflow-hidden">
    <div class="absolute -right-4 -top-4 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:100px;font-variation-settings:'FILL' 1">event_busy</span>
    </div>
    <div class="relative z-10">
      <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Leave Management</p>
      <h2 class="text-xl font-bold">Student Leave Requests</h2>
      <p class="text-white/70 text-xs mt-0.5"><?= htmlspecialchars($dept) ?> Department</p>
      <div class="flex gap-2 mt-3 flex-wrap">
        <?php if($pendingCount > 0): ?>
        <span class="inline-flex items-center gap-1.5 bg-white/20 text-white text-xs font-bold px-3 py-1.5 rounded-full">
          <span class="w-2 h-2 rounded-full bg-amber-300 animate-pulse inline-block"></span>
          <?= $pendingCount ?> pending
        </span>
        <?php endif; ?>
        <button onclick="openSlideModal('applyModal')" class="inline-flex items-center gap-1.5 bg-white/15 hover:bg-white/25 transition-colors text-white text-xs font-bold px-3 py-1.5 rounded-full">
          <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1">add</span>
          Apply My Leave
        </button>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-3 gap-2 fu1">
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-amber-500"><?= $pendingCount ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Pending</p>
    </div>
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-green-600"><?= $approvedCount ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Approved</p>
    </div>
    <div class="card p-3 text-center">
      <p class="text-2xl font-bold text-red-500"><?= $rejectedCount ?></p>
      <p class="text-[10px] text-slate-400 font-semibold uppercase mt-1">Rejected</p>
    </div>
  </div>

  <!-- Flash -->
  <?php if ($msg): ?>
  <div class="card p-3 flex items-center gap-3 fu <?= $msgType==='success'?'border-green-200 bg-green-50':($msgType==='warning'?'border-amber-200 bg-amber-50':'border-red-200 bg-red-50') ?>">
    <span class="material-symbols-outlined text-lg <?= $msgType==='success'?'text-green-600':($msgType==='warning'?'text-amber-600':'text-red-600') ?>" style="font-variation-settings:'FILL' 1">
      <?= $msgType==='success'?'check_circle':($msgType==='warning'?'warning':'error') ?>
    </span>
    <p class="text-sm font-semibold"><?= htmlspecialchars($msg) ?></p>
  </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="flex gap-2 fu2">
    <button class="tab-btn active" onclick="showTab('students',this)">
      Student Leaves <?php if($pendingCount>0): ?><span class="ml-1 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full"><?= $pendingCount ?></span><?php endif; ?>
    </button>
    <button class="tab-btn" onclick="showTab('mine',this)">My Leaves (<?= count($myLeaves) ?>)</button>
  </div>

  <!-- STUDENT LEAVES -->
  <div id="tab-students" class="space-y-3 fu3">
    <?php if (empty($studentLeaves)): ?>
    <div class="card p-10 text-center">
      <span class="material-symbols-outlined text-5xl text-primary/20 block mb-3" style="font-variation-settings:'FILL' 1">event_available</span>
      <p class="font-bold text-sm">No leave requests</p>
      <p class="text-xs text-slate-400 mt-1">Student leaves from your department appear here</p>
    </div>
    <?php else: foreach ($studentLeaves as $lv):
      $days = $lv['total_days'] ?? 1;
      $initials = mb_strtoupper(mb_substr($lv['user_name'] ?? 'S', 0, 1));
    ?>
    <div class="card p-4 leave-card-<?= $lv['status'] ?>">
      <div class="flex items-start gap-3">
        <!-- Initials avatar -->
        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0 font-bold text-primary text-sm">
          <?= $initials ?>
        </div>
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
              <p class="font-bold text-sm truncate"><?= htmlspecialchars($lv['user_name'] ?? 'Student') ?></p>
              <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                <span class="text-[10px] font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 px-2 py-0.5 rounded-full"><?= htmlspecialchars($lv['leave_type'] ?? 'Personal') ?></span>
                <span class="badge-<?= $lv['status'] ?> capitalize"><?= $lv['status'] ?></span>
              </div>
            </div>
            <div class="text-center shrink-0 bg-primary/5 rounded-xl px-2 py-1">
              <p class="text-lg font-extrabold text-primary leading-none"><?= $days ?></p>
              <p class="text-[9px] text-slate-400">day<?= $days>1?'s':'' ?></p>
            </div>
          </div>

          <p class="text-sm text-slate-600 dark:text-slate-300 mt-2 leading-relaxed"><?= htmlspecialchars($lv['reason']) ?></p>

          <div class="flex items-center gap-1.5 mt-1.5 text-xs text-slate-400">
            <span class="material-symbols-outlined text-xs">calendar_month</span>
            <?= date('d M', strtotime($lv['from_date'])) ?> → <?= date('d M Y', strtotime($lv['to_date'])) ?>
          </div>

          <?php if ($lv['admin_remark']): ?>
          <div class="mt-2 bg-slate-50 dark:bg-slate-800/60 rounded-xl px-3 py-2">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Remark</p>
            <p class="text-xs text-slate-600 dark:text-slate-300"><?= htmlspecialchars($lv['admin_remark']) ?></p>
            <?php if ($lv['approved_by']): ?>
            <p class="text-[10px] text-slate-400 mt-0.5">— <?= htmlspecialchars($lv['approved_by']) ?></p>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <!-- Approve / Reject buttons — only for pending -->
          <?php if ($lv['status'] === 'pending'): ?>
          <div class="flex gap-2 mt-3">
            <button onclick="openActionModal(<?= $lv['id'] ?>, 'approve', '<?= htmlspecialchars(addslashes($lv['user_name'] ?? ''), ENT_QUOTES) ?>')" class="btn-approve">
              <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1">check_circle</span> Approve
            </button>
            <button onclick="openActionModal(<?= $lv['id'] ?>, 'reject',  '<?= htmlspecialchars(addslashes($lv['user_name'] ?? ''), ENT_QUOTES) ?>')" class="btn-reject">
              <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1">cancel</span> Reject
            </button>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

  <!-- MY LEAVES -->
  <div id="tab-mine" class="space-y-3 hidden">
    <?php if (empty($myLeaves)): ?>
    <div class="card p-10 text-center">
      <span class="material-symbols-outlined text-5xl text-primary/20 block mb-3" style="font-variation-settings:'FILL' 1">event_busy</span>
      <p class="font-bold text-sm">No leave applications yet</p>
      <button onclick="openSlideModal('applyModal')" class="mt-3 text-primary text-xs font-bold hover:underline">+ Apply for Leave</button>
    </div>
    <?php else: foreach ($myLeaves as $lv):
      $days = $lv['total_days'] ?? 1;
      $icon = match($lv['status']) { 'approved'=>['check_circle','text-green-500'], 'rejected'=>['cancel','text-red-500'], default=>['pending','text-amber-500'] };
    ?>
    <div class="card p-4 leave-card-<?= $lv['status'] ?>">
      <div class="flex items-start gap-3">
        <span class="material-symbols-outlined <?= $icon[1] ?> shrink-0 mt-0.5" style="font-size:26px;font-variation-settings:'FILL' 1"><?= $icon[0] ?></span>
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-1 flex-wrap">
            <span class="text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded-full"><?= htmlspecialchars($lv['leave_type']) ?></span>
            <span class="badge-<?= $lv['status'] ?> capitalize"><?= $lv['status'] ?></span>
          </div>
          <p class="text-sm font-semibold"><?= htmlspecialchars($lv['reason']) ?></p>
          <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">calendar_month</span>
            <?= date('d M', strtotime($lv['from_date'])) ?> → <?= date('d M Y', strtotime($lv['to_date'])) ?> · <?= $days ?> day<?= $days>1?'s':'' ?>
          </p>
          <?php if ($lv['admin_remark']): ?>
          <div class="mt-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg px-3 py-1.5">
            <p class="text-xs text-slate-500">Remark: <?= htmlspecialchars($lv['admin_remark']) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

</main>

<!-- Bottom nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2 z-40">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="teacher_dashboard.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
      <span class="material-symbols-outlined text-xl">home</span><span class="text-[10px] font-medium">Home</span>
    </a>
    <a href="teacher_classes.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
      <span class="material-symbols-outlined text-xl">menu_book</span><span class="text-[10px] font-medium">Classes</span>
    </a>
    <a href="teacher_leave.php" class="text-primary flex flex-col items-center gap-0.5 px-3 py-1">
      <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1">event_busy</span>
      <span class="text-[10px] font-bold">Leave</span>
    </a>
    <a href="teacher_message.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
      <span class="material-symbols-outlined text-xl">message</span><span class="text-[10px] font-medium">Messages</span>
    </a>
    <a href="teacher_profile.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
      <span class="material-symbols-outlined text-xl">person</span><span class="text-[10px] font-medium">Profile</span>
    </a>
  </div>
</nav>

<!-- Approve/Reject Modal -->
<div id="actionModal" class="modal-bg" onclick="if(event.target===this)closeModal('actionModal')">
  <div class="modal-panel p-5">
    <h3 class="font-bold text-base mb-0.5" id="modalTitle">Approve Leave</h3>
    <p class="text-sm text-slate-400 mb-4" id="modalSub"></p>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="leave_id" id="modalLeaveId"/>
      <input type="hidden" name="action"   id="modalAction"/>
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Remark <span class="font-normal">(optional)</span></label>
        <textarea name="remark" rows="3" placeholder="Add a note for the student…"
          class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
      </div>
      <button type="submit" id="modalBtn" class="w-full py-3 rounded-xl text-white font-bold text-sm">Confirm</button>
    </form>
  </div>
</div>

<!-- Apply My Leave Modal (slide up) -->
<div id="applyModal" class="slide-modal-bg" onclick="if(event.target===this)closeSlideModal('applyModal')">
  <div class="slide-modal-panel p-5">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-base">Apply for Leave</h3>
      <button onclick="closeSlideModal('applyModal')" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
        <span class="material-symbols-outlined text-slate-500">close</span>
      </button>
    </div>
    <form method="POST" class="space-y-4">
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Leave Type</label>
        <select name="leave_type" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30">
          <option value="Medical">Medical / Health</option>
          <option value="Personal" selected>Personal</option>
          <option value="Family">Family Function</option>
          <option value="Academic">Academic / Training</option>
          <option value="Emergency">Emergency</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">From</label>
          <input type="date" name="from_date" required min="<?= date('Y-m-d') ?>"
            class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">To</label>
          <input type="date" name="to_date" required min="<?= date('Y-m-d') ?>"
            class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30"/>
        </div>
      </div>
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5 block">Reason</label>
        <textarea name="reason" required rows="3" placeholder="Briefly describe your reason…"
          class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
      </div>
      <button type="submit" name="apply_leave" class="w-full py-3 rounded-xl text-white font-bold text-sm" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
        Submit Application
      </button>
    </form>
  </div>
</div>

<script>
function showTab(tab, btn) {
  ['students','mine'].forEach(t => {
    document.getElementById('tab-'+t).classList.add('hidden');
  });
  document.getElementById('tab-'+tab).classList.remove('hidden');
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
function openActionModal(id, action, name) {
  document.getElementById('modalLeaveId').value = id;
  document.getElementById('modalAction').value  = action;
  document.getElementById('modalTitle').textContent = action === 'approve' ? 'Approve Leave' : 'Reject Leave';
  document.getElementById('modalSub').textContent   = name + "'s leave request";
  const btn = document.getElementById('modalBtn');
  btn.textContent  = action === 'approve' ? '✓ Approve' : '✕ Reject';
  btn.style.background = action === 'approve'
    ? 'linear-gradient(135deg,#16a34a,#22c55e)'
    : 'linear-gradient(135deg,#dc2626,#ef4444)';
  document.getElementById('actionModal').classList.add('open');
}
function closeModal(id)      { document.getElementById(id).classList.remove('open'); }
function openSlideModal(id)  { document.getElementById(id).classList.add('open');    }
function closeSlideModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
</body>
</html>