<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';
$settings = getSiteSettings($conn);

// Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢?? Ensure table Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??Ãƒ?Ã¢??Ã¢??
$conn->query("CREATE TABLE IF NOT EXISTS leave_requests (
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
)");

$msg = ''; $msgType = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['leave_id'])) {
        $lid    = (int)$_POST['leave_id'];
        $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
        $remark = trim($_POST['remark'] ?? '');
        $stmt   = $conn->prepare("UPDATE leave_requests SET status=?, admin_remark=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("ssi", $action, $remark, $lid);
        $stmt->execute();
        $stmt->close();
        $msg     = "Leave request " . ucfirst($action) . " successfully!";
        $msgType = $action === 'approved' ? 'success' : 'warning';

        // Log
        $conn->query("INSERT INTO activity_logs (user_id, user_role, activity_text) VALUES ({$_SESSION['user_id']}, 'admin', 'Leave #$lid $action')");
    }
}

// Filters
$filterStatus = $_GET['status'] ?? 'all';
$filterRole   = $_GET['role'] ?? 'all';
$search       = trim($_GET['q'] ?? '');

$where  = [];
$params = [];
$types  = '';
if ($filterStatus !== 'all') { $where[] = "status=?"; $params[] = $filterStatus; $types .= 's'; }
if ($filterRole   !== 'all') { $where[] = "user_role=?"; $params[] = $filterRole; $types .= 's'; }
if ($search) { $where[] = "user_name LIKE ?"; $params[] = "%$search%"; $types .= 's'; }

$sql = "SELECT * FROM leave_requests" . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY created_at DESC";
$leaves = [];
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $leaves[] = $r;
    $stmt->close();
} else {
    $res = $conn->query($sql);
    if ($res) while ($r = $res->fetch_assoc()) $leaves[] = $r;
}

$total    = count($leaves);
$pending  = count(array_filter($leaves, fn($l) => $l['status'] === 'pending'));
$approved = count(array_filter($leaves, fn($l) => $l['status'] === 'approved'));
$rejected = count(array_filter($leaves, fn($l) => $l['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Leave Management Ãƒ?Ã¢??Ã¢?Å“ Admin</title>
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
.dark .sidebar{background:#161728;border-right-color:rgba(67,73,207,.15)}
.sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#64748b;transition:all .2s;text-decoration:none}
.sidebar-link:hover{background:rgba(67,73,207,.06);color:#4349cf}
.sidebar-link.active{background:#4349cf;color:#fff}
.sidebar-badge{background:#ef4444;color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:99px;margin-left:auto}
.badge-pending{background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px}
.badge-approved{background:#dcfce7;color:#166534;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px}
.badge-rejected{background:#fee2e2;color:#991b1b;font-size:11px;font-weight:700;padding:3px 10px;border-radius:99px}
.btn-primary{background:linear-gradient(135deg,#4349cf,#7479f5);color:#fff;font-weight:700;border-radius:.75rem;padding:.6rem 1.2rem;font-size:.875rem;border:none;cursor:pointer;transition:opacity .2s}
.btn-primary:hover{opacity:.9}
.btn-approve{background:#dcfce7;color:#166534;font-weight:700;border-radius:.75rem;padding:.5rem 1rem;font-size:.8rem;border:none;cursor:pointer}
.btn-reject{background:#fee2e2;color:#991b1b;font-weight:700;border-radius:.75rem;padding:.5rem 1rem;font-size:.8rem;border:none;cursor:pointer}
.modal-bg{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:16px}
.modal-bg.open{display:flex}
.modal-panel{background:white;border-radius:1.5rem;width:100%;max-width:480px;max-height:90vh;overflow-y:auto}
.dark .modal-panel{background:#1a1b2e}
.fill-icon{font-variation-settings:'FILL' 1}
.bottom-nav{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-top:1px solid rgba(67,73,207,.08)}
.nav-item{display:flex;flex-direction:column;align-items:center;gap:2px;color:#94a3b8;transition:color .2s;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;text-decoration:none}
.nav-item.active{color:#4349cf}
</style>
</head>
<body class="dark:text-slate-100">
<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="sidebar hidden lg:flex flex-col sticky top-0 h-screen p-5 overflow-y-auto">
    <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800 mb-4">
      <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-lg shadow-primary/30">
        <span class="material-symbols-outlined text-xl fill-icon">school</span>
      </div>
      <div>
        <h1 class="font-bold text-slate-800 dark:text-white leading-none"><?= htmlspecialchars($settings['site_name'] ?? 'CollegeConnect') ?></h1>
        <p class="text-xs text-slate-400 mt-0.5">Admin Central</p>
      </div>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1">Main</p>
      <a href="admin_dashboard.php" class="sidebar-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a>
      <a href="admin_users.php" class="sidebar-link"><span class="material-symbols-outlined">group</span>Users</a>
      <a href="admin_academics.php" class="sidebar-link"><span class="material-symbols-outlined">school</span>Academics</a>
      <a href="admin_ai_overview.php" class="sidebar-link"><span class="material-symbols-outlined">psychology</span>AI Overview</a>
      <a href="admin_qr_overview.php" class="sidebar-link"><span class="material-symbols-outlined">qr_code_scanner</span>QR Attendance</a>
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">Management</p>
      <a href="admin_leaves.php" class="sidebar-link active"><span class="material-symbols-outlined">event_busy</span>Leave Requests</a>
      <a href="admin_exams.php" class="sidebar-link"><span class="material-symbols-outlined">quiz</span>Exam Schedule</a>
      <a href="admin_notifications.php" class="sidebar-link"><span class="material-symbols-outlined">notifications</span>Notifications</a>
      <a href="admin_announcements.php" class="sidebar-link"><span class="material-symbols-outlined">campaign</span>Announcements</a>
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">System</p>
      <a href="admin_settings.php" class="sidebar-link"><span class="material-symbols-outlined">settings</span>Settings</a>
    </nav>
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
      <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
        <div class="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center border-2 border-primary/30">
          <span class="material-symbols-outlined text-primary text-lg fill-icon">manage_accounts</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold truncate"><?= htmlspecialchars($admin['name'] ?? 'Admin') ?></p>
          <p class="text-xs text-slate-400">Super Admin</p>
        </div>
        <a href="../auth/logout.php" class="text-slate-400 hover:text-red-500 transition-colors"><span class="material-symbols-outlined text-lg">logout</span></a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col min-w-0">
    <!-- Top bar -->
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-[#0d0e1a]/80 backdrop-blur-md border-b border-primary/8 px-4 md:px-6 py-4 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Leave Management</h2>
        <p class="text-xs text-slate-400 mt-0.5"><?= $pending ?> requests pending action</p>
      </div>
      <a href="admin_dashboard.php" class="flex items-center gap-1.5 text-sm text-slate-500 hover:text-primary font-medium">
        <span class="material-symbols-outlined text-base">arrow_back</span> Dashboard
      </a>
    </header>

    <main class="flex-1 p-4 md:p-6 pb-24 lg:pb-8 space-y-5 max-w-5xl w-full mx-auto">

      <!-- Flash msg -->
      <?php if ($msg): ?>
      <div class="card p-3 flex items-center gap-3 <?= $msgType === 'success' ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' ?> fu">
        <span class="material-symbols-outlined text-lg <?= $msgType === 'success' ? 'text-green-600' : 'text-amber-600' ?> fill-icon"><?= $msgType === 'success' ? 'check_circle' : 'warning' ?></span>
        <p class="text-sm font-medium <?= $msgType === 'success' ? 'text-green-800' : 'text-amber-800' ?>"><?= htmlspecialchars($msg) ?></p>
      </div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 fu">
        <?php foreach ([
          ['Total',    $total,    'list',         'text-primary',   'bg-primary/8'],
          ['Pending',  $pending,  'pending',       'text-amber-600', 'bg-amber-50'],
          ['Approved', $approved, 'check_circle',  'text-green-600', 'bg-green-50'],
          ['Rejected', $rejected, 'cancel',        'text-red-500',   'bg-red-50'],
        ] as [$label, $val, $icon, $tc, $bg]): ?>
        <div class="card p-4">
          <div class="flex items-center justify-between mb-2">
            <div class="w-9 h-9 rounded-xl <?= $bg ?> flex items-center justify-center">
              <span class="material-symbols-outlined <?= $tc ?> text-lg fill-icon"><?= $icon ?></span>
            </div>
          </div>
          <p class="text-2xl font-extrabold <?= $tc ?>"><?= $val ?></p>
          <p class="text-xs text-slate-400 font-medium mt-0.5"><?= $label ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Filters -->
      <div class="card p-4 fu">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
          <div class="flex-1 min-w-[160px]">
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Search Name</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Student or teacher nameÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30"/>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Status</label>
            <select name="status" class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30">
              <option value="all" <?= $filterStatus==='all'?'selected':'' ?>>All Status</option>
              <option value="pending"  <?= $filterStatus==='pending'?'selected':'' ?>>Pending</option>
              <option value="approved" <?= $filterStatus==='approved'?'selected':'' ?>>Approved</option>
              <option value="rejected" <?= $filterStatus==='rejected'?'selected':'' ?>>Rejected</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Role</label>
            <select name="role" class="border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30">
              <option value="all"     <?= $filterRole==='all'?'selected':'' ?>>All Roles</option>
              <option value="student" <?= $filterRole==='student'?'selected':'' ?>>Students</option>
              <option value="teacher" <?= $filterRole==='teacher'?'selected':'' ?>>Teachers</option>
            </select>
          </div>
          <button type="submit" class="btn-primary">Filter</button>
          <?php if ($filterStatus !== 'all' || $filterRole !== 'all' || $search): ?>
          <a href="admin_leaves.php" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Clear</a>
          <?php endif; ?>
        </form>
      </div>

      <!-- Leave list -->
      <div class="space-y-3">
        <?php if (empty($leaves)): ?>
        <div class="card p-10 text-center fu">
          <span class="material-symbols-outlined text-5xl text-slate-200 block mb-3 fill-icon">event_available</span>
          <p class="font-bold text-slate-500">No leave requests found</p>
        </div>
        <?php else: foreach ($leaves as $lv):
          $days = $lv['total_days'] ?? 1;
        ?>
        <div class="card p-4 fu">
          <div class="flex flex-wrap gap-3 items-start">
            <!-- Avatar -->
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-primary text-lg fill-icon"><?= $lv['user_role'] === 'teacher' ? 'person_pin_circle' : 'person' ?></span>
            </div>
            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <p class="font-bold text-sm"><?= htmlspecialchars($lv['user_name'] ?? 'Unknown') ?></p>
                <span class="text-xs text-slate-400 capitalize bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full"><?= $lv['user_role'] ?></span>
                <?php if ($lv['department']): ?>
                <span class="text-xs text-indigo-600 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-0.5 rounded-full"><?= htmlspecialchars($lv['department']) ?></span>
                <?php endif; ?>
              </div>
              <p class="text-sm text-slate-600 dark:text-slate-300"><?= htmlspecialchars($lv['reason']) ?></p>
              <div class="flex items-center gap-3 mt-2 text-xs text-slate-500 flex-wrap">
                <span><strong><?= htmlspecialchars($lv['leave_type'] ?? 'Personal') ?></strong></span>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">calendar_month</span><?= date('d M Y', strtotime($lv['from_date'])) ?> ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ <?= date('d M Y', strtotime($lv['to_date'])) ?></span>
                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">schedule</span><?= $days ?> day<?= $days > 1 ? 's' : '' ?></span>
              </div>
              <?php if ($lv['admin_remark']): ?>
              <p class="text-xs text-slate-500 italic mt-1">Remark: <?= htmlspecialchars($lv['admin_remark']) ?></p>
              <?php endif; ?>
            </div>
            <!-- Status + Actions -->
            <div class="flex flex-col items-end gap-2 shrink-0">
              <span class="badge-<?= $lv['status'] ?> capitalize"><?= $lv['status'] ?></span>
              <?php if ($lv['status'] === 'pending'): ?>
              <div class="flex gap-2">
                <button onclick="openActionModal(<?= $lv['id'] ?>, 'approve', '<?= htmlspecialchars($lv['user_name'], ENT_QUOTES) ?>')" class="btn-approve">Approve</button>
                <button onclick="openActionModal(<?= $lv['id'] ?>, 'reject', '<?= htmlspecialchars($lv['user_name'], ENT_QUOTES) ?>')"  class="btn-reject">Reject</button>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>

    </main>
  </div>
</div>

<!-- Mobile bottom nav -->
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

<!-- Action Modal -->
<div id="actionModal" class="modal-bg" onclick="if(event.target===this)closeModal()">
  <div class="modal-panel p-5">
    <h3 class="font-bold text-base mb-1" id="modalTitle">Approve Leave</h3>
    <p class="text-sm text-slate-500 mb-4" id="modalSub">Add an optional remark</p>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="leave_id" id="modalLeaveId"/>
      <input type="hidden" name="action"   id="modalAction"/>
      <div>
        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Remark (optional)</label>
        <textarea name="remark" rows="3" placeholder="Add a note for the applicantÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
      </div>
      <button type="submit" id="modalBtn" class="btn-primary w-full py-3">Confirm</button>
    </form>
  </div>
</div>

<script>
function openActionModal(id, action, name) {
  document.getElementById('modalLeaveId').value = id;
  document.getElementById('modalAction').value  = action;
  document.getElementById('modalTitle').textContent = (action === 'approve' ? 'Approve' : 'Reject') + ' Leave';
  document.getElementById('modalSub').textContent   = name + "'s leave application";
  const btn = document.getElementById('modalBtn');
  btn.textContent = action === 'approve' ? 'ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“ Approve' : 'ÃƒÂ¢Ã…â€œÃ¢â‚¬â€ Reject';
  btn.style.background = action === 'approve' ? 'linear-gradient(135deg,#16a34a,#22c55e)' : 'linear-gradient(135deg,#dc2626,#ef4444)';
  document.getElementById('actionModal').classList.add('open');
}
function closeModal() { document.getElementById('actionModal').classList.remove('open'); }
</script>
</body>
</html>