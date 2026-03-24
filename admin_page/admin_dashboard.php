<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);

// Core stats
$totalStudents = 0;
$totalTeachers = 0;
$totalCourses = 0;
$loggedStudents = 0;
$loggedTeachers = 0;
$pendingStudents = 0;
$pendingTeachers = 0;

$q = $conn->query("SELECT COUNT(*) AS c FROM students");
if ($q) {
    $r = $q->fetch_assoc();
    $totalStudents = (int)($r['c'] ?? 0);
}

$q = $conn->query("SELECT COUNT(*) AS c FROM teachers");
if ($q) {
    $r = $q->fetch_assoc();
    $totalTeachers = (int)($r['c'] ?? 0);
}

$q = $conn->query("SELECT COUNT(*) AS c FROM courses WHERE is_active = 1");
if ($q) {
    $r = $q->fetch_assoc();
    $totalCourses = (int)($r['c'] ?? 0);
}

$q = $conn->query("SELECT COUNT(*) AS c FROM students WHERE is_logged_in = 1");
if ($q) {
    $r = $q->fetch_assoc();
    $loggedStudents = (int)($r['c'] ?? 0);
}

$q = $conn->query("SELECT COUNT(*) AS c FROM teachers WHERE is_logged_in = 1");
if ($q) {
    $r = $q->fetch_assoc();
    $loggedTeachers = (int)($r['c'] ?? 0);
}

$q = $conn->query("SELECT COUNT(*) AS c FROM students WHERE status = 'pending'");
if ($q) {
    $r = $q->fetch_assoc();
    $pendingStudents = (int)($r['c'] ?? 0);
}

$q = $conn->query("SELECT COUNT(*) AS c FROM teachers WHERE status = 'pending'");
if ($q) {
    $r = $q->fetch_assoc();
    $pendingTeachers = (int)($r['c'] ?? 0);
}

$totalPending = $pendingStudents + $pendingTeachers;

// Activity log
$recentActivities = $conn->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 8");

// Attendance chart data
$attLabels = array();
$attPresent = array();
$attAbsent = array();

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $attLabels[] = date('D', strtotime($date));

    $present = 0;
    $absent = 0;

    $pr = $conn->query("SELECT COUNT(*) AS c FROM attendance WHERE date='$date' AND status='present'");
    if ($pr) {
        $pRow = $pr->fetch_assoc();
        $present = (int)($pRow['c'] ?? 0);
    }

    $ar = $conn->query("SELECT COUNT(*) AS c FROM attendance WHERE date='$date' AND status='absent'");
    if ($ar) {
        $aRow = $ar->fetch_assoc();
        $absent = (int)($aRow['c'] ?? 0);
    }

    $attPresent[] = $present;
    $attAbsent[] = $absent;
}

// Department enrollment
$deptData = array();
$deptRes = $conn->query("SELECT department, COUNT(*) AS c FROM students WHERE status='approved' GROUP BY department ORDER BY c DESC LIMIT 6");

if ($deptRes) {
    while ($row = $deptRes->fetch_assoc()) {
        $deptData[] = $row;
    }
}

$deptLabels = array_column($deptData, 'department');
$deptCounts = array_column($deptData, 'c');

// FIXED: prepare clean arrays in PHP instead of using fn() arrow function
$deptLabelsClean = array();
if (!empty($deptLabels)) {
    foreach ($deptLabels as $d) {
        $deptLabelsClean[] = (!empty($d)) ? $d : 'Unknown';
    }
}

$deptCountsClean = array();
if (!empty($deptCounts)) {
    foreach ($deptCounts as $c) {
        $deptCountsClean[] = (int)$c;
    }
}

// Fees summary
$conn->query("CREATE TABLE IF NOT EXISTS student_fees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('paid','unpaid','partial') DEFAULT 'unpaid',
  due_date DATE,
  paid_date DATE,
  fee_type VARCHAR(100) DEFAULT 'Tuition',
  semester INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$feesPaid = 0;
$feesUnpaid = 0;

$q = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM student_fees WHERE status='paid'");
if ($q) {
    $r = $q->fetch_assoc();
    $feesPaid = (float)($r['s'] ?? 0);
}

$q = $conn->query("SELECT COALESCE(SUM(amount),0) AS s FROM student_fees WHERE status='unpaid'");
if ($q) {
    $r = $q->fetch_assoc();
    $feesUnpaid = (float)($r['s'] ?? 0);
}

$feesTotal = $feesPaid + $feesUnpaid;
$feesPct = ($feesTotal > 0) ? round(($feesPaid / $feesTotal) * 100) : 0;

// Leave requests
$conn->query("CREATE TABLE IF NOT EXISTS leave_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  user_role ENUM('student','teacher') DEFAULT 'student',
  user_name VARCHAR(150),
  reason TEXT,
  from_date DATE,
  to_date DATE,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pendingLeaves = 0;
$q = $conn->query("SELECT COUNT(*) AS c FROM leave_requests WHERE status='pending'");
if ($q) {
    $r = $q->fetch_assoc();
    $pendingLeaves = (int)($r['c'] ?? 0);
}
$recentLeaves = $conn->query("SELECT * FROM leave_requests ORDER BY created_at DESC LIMIT 5");

// Notifications
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  message TEXT,
  type ENUM('info','success','warning','danger') DEFAULT 'info',
  target_role ENUM('all','student','teacher','admin') DEFAULT 'all',
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$unreadNotifs = 0;
$q = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read=0");
if ($q) {
    $r = $q->fetch_assoc();
    $unreadNotifs = (int)($r['c'] ?? 0);
}
$recentNotifs = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 4");

// Exam schedule
$conn->query("CREATE TABLE IF NOT EXISTS exam_schedules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  exam_name VARCHAR(200) NOT NULL,
  branch_code VARCHAR(20),
  semester INT,
  subject_id INT,
  exam_date DATE,
  start_time TIME,
  end_time TIME,
  room VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$upcomingExams = 0;
$q = $conn->query("SELECT COUNT(*) AS c FROM exam_schedules WHERE exam_date >= CURDATE()");
if ($q) {
    $r = $q->fetch_assoc();
    $upcomingExams = (int)($r['c'] ?? 0);
}

$siteName = htmlspecialchars($settings['site_name'] ?? 'CollegeConnect');
$adminName = htmlspecialchars($admin['name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Dashboard ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“ CollegeConnect Admin</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: {
        primary: "#4349cf",
        "primary-dark": "#2630ed",
        "bg-light": "#f0f2ff",
        "bg-dark": "#0d0e1a"
      },
      fontFamily: {
        sans: ["Plus Jakarta Sans", "sans-serif"]
      }
    }
  }
}
</script>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f0f2ff; min-height: 100dvh; }

  @keyframes fadeUp { from { opacity:0; transform:translateY(20px);} to { opacity:1; transform:translateY(0);} }
  @keyframes countUp { from { opacity:0; transform:scale(.8);} to { opacity:1; transform:scale(1);} }
  @keyframes slideIn { from { opacity:0; transform:translateX(-16px);} to { opacity:1; transform:translateX(0);} }
  @keyframes pulse-ring { 0%,100%{box-shadow:0 0 0 0 rgba(67,73,207,.4)} 50%{box-shadow:0 0 0 8px rgba(67,73,207,0)} }

  .anim-fade-up { animation: fadeUp .5s ease both; }
  .anim-count { animation: countUp .6s cubic-bezier(.34,1.56,.64,1) both; }
  .anim-slide { animation: slideIn .45s ease both; }
  .d1{animation-delay:.05s}.d2{animation-delay:.1s}.d3{animation-delay:.15s}
  .d4{animation-delay:.2s}.d5{animation-delay:.25s}.d6{animation-delay:.3s}
  .d7{animation-delay:.35s}.d8{animation-delay:.4s}.d9{animation-delay:.45s}

  .stat-card {
    background: #fff;
    border-radius: 1rem;
    padding: 1.25rem;
    border: 1px solid rgba(67,73,207,.08);
    box-shadow: 0 2px 12px rgba(67,73,207,.06);
    transition: transform .2s, box-shadow .2s;
  }
  .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(67,73,207,.12); }

  .icon-blob {
    border-radius: 12px;
    width: 42px;
    height: 42px;
    display:flex;
    align-items:center;
    justify-content:center;
  }

  .hero-card {
    background: linear-gradient(135deg, #4349cf 0%, #2630ed 60%, #1a1fa8 100%);
    border-radius: 1.25rem;
    position: relative;
    overflow: hidden;
  }
  .hero-card::before {
    content:'';
    position:absolute;
    top:-40px;
    right:-40px;
    width:180px;
    height:180px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
  }
  .hero-card::after  {
    content:'';
    position:absolute;
    bottom:-60px;
    right:60px;
    width:120px;
    height:120px;
    background:rgba(255,255,255,.05);
    border-radius:50%;
  }

  .quick-btn {
    background:#fff;
    border:1px solid rgba(67,73,207,.1);
    border-radius:.875rem;
    padding:1rem;
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:.5rem;
    transition:all .2s;
    cursor:pointer;
    text-decoration:none;
    color:inherit;
  }
  .quick-btn:hover {
    border-color:#4349cf;
    box-shadow:0 4px 16px rgba(67,73,207,.15);
    transform:translateY(-2px);
  }
  .quick-btn .icon-wrap {
    width:44px;
    height:44px;
    border-radius:50%;
    background:rgba(67,73,207,.1);
    color:#4349cf;
    display:flex;
    align-items:center;
    justify-content:center;
    transition:background .2s,color .2s;
  }
  .quick-btn:hover .icon-wrap {
    background:#4349cf;
    color:#fff;
  }

  .activity-dot {
    width:8px;
    height:8px;
    border-radius:50%;
    background:#4349cf;
    animation:pulse-ring 2s infinite;
  }

  .bottom-nav {
    background:rgba(255,255,255,.9);
    backdrop-filter:blur(12px);
    border-top:1px solid rgba(67,73,207,.08);
  }
  .nav-item {
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:2px;
    color:#94a3b8;
    transition:color .2s;
  }
  .nav-item.active { color:#4349cf; }
  .nav-item span.material-symbols-outlined { font-size:22px; }
  .nav-item p {
    font-size:9px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
  }

  .sidebar {
    background:#fff;
    border-right:1px solid rgba(67,73,207,.08);
    width:260px;
    flex-shrink:0;
  }
  .sidebar-link {
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.75rem 1rem;
    border-radius:.75rem;
    font-size:.875rem;
    font-weight:500;
    color:#64748b;
    transition:all .2s;
    text-decoration:none;
  }
  .sidebar-link:hover {
    background:rgba(67,73,207,.06);
    color:#4349cf;
  }
  .sidebar-link.active {
    background:#4349cf;
    color:#fff;
    box-shadow:0 4px 12px rgba(67,73,207,.3);
  }
  .sidebar-badge {
    background:#ef4444;
    color:#fff;
    font-size:10px;
    font-weight:700;
    padding:1px 6px;
    border-radius:99px;
    margin-left:auto;
  }

  .online-badge {
    width:8px;
    height:8px;
    border-radius:50%;
    background:#22c55e;
    border:2px solid #fff;
    position:absolute;
    bottom:0;
    right:0;
  }

  .progress-track {
    background:#e2e8f0;
    border-radius:99px;
    height:6px;
    overflow:hidden;
  }
  .progress-fill {
    height:100%;
    border-radius:99px;
    background:linear-gradient(90deg,#4349cf,#2630ed);
    transition:width 1.2s ease;
  }

  .chart-card {
    background:#fff;
    border-radius:1rem;
    border:1px solid rgba(67,73,207,.08);
    box-shadow:0 2px 12px rgba(67,73,207,.06);
    padding:1.25rem;
  }

  .fees-bar {
    height:10px;
    border-radius:99px;
    overflow:hidden;
    background:#e2e8f0;
  }
  .fees-fill {
    height:100%;
    border-radius:99px;
    background:linear-gradient(90deg,#22c55e,#16a34a);
    transition:width 1.4s ease;
  }

  .badge-pending {
    background:#fef3c7;
    color:#92400e;
    font-size:10px;
    font-weight:700;
    padding:2px 8px;
    border-radius:99px;
  }
  .badge-approved {
    background:#dcfce7;
    color:#166534;
    font-size:10px;
    font-weight:700;
    padding:2px 8px;
    border-radius:99px;
  }
  .badge-rejected {
    background:#fee2e2;
    color:#991b1b;
    font-size:10px;
    font-weight:700;
    padding:2px 8px;
    border-radius:99px;
  }

  .notif-dot-info    { background:#3b82f6; }
  .notif-dot-success { background:#22c55e; }
  .notif-dot-warning { background:#f59e0b; }
  .notif-dot-danger  { background:#ef4444; }

  #notif-panel { display:none; }
  #notif-panel.open { display:block; }

  .dark body { background:#0d0e1a; }
  .dark .stat-card,
  .dark .quick-btn,
  .dark .chart-card {
    background:#161728;
    border-color:rgba(67,73,207,.15);
  }
  .dark .bottom-nav {
    background:rgba(13,14,26,.9);
    border-top-color:rgba(67,73,207,.2);
  }
  .dark .sidebar {
    background:#161728;
    border-right-color:rgba(67,73,207,.15);
  }
  .dark .sidebar-link { color:#94a3b8; }

  .material-symbols-outlined {
    font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
  }
  .fill-icon {
    font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;
  }

  ::-webkit-scrollbar { width:4px; height:4px; }
  ::-webkit-scrollbar-track { background:transparent; }
  ::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:99px; }
</style>
</head>
<body class="dark:bg-bg-dark dark:text-slate-100">

<div class="flex min-h-screen">

  <aside class="sidebar hidden lg:flex flex-col sticky top-0 h-screen p-5 overflow-y-auto">
    <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800 mb-4">
      <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white shadow-lg shadow-primary/30">
        <span class="material-symbols-outlined text-xl fill-icon">school</span>
      </div>
      <div>
        <h1 class="font-bold text-slate-800 dark:text-white leading-none"><?php echo $siteName; ?></h1>
        <p class="text-xs text-slate-400 mt-0.5">Admin Central</p>
      </div>
    </div>

    <nav class="flex flex-col gap-1 flex-1">
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-1">Main</p>
      <a href="admin_dashboard.php" class="sidebar-link active">
        <span class="material-symbols-outlined fill-icon">dashboard</span> Dashboard
      </a>
      <a href="admin_users.php" class="sidebar-link">
        <span class="material-symbols-outlined">group</span> Users
        <?php if ($totalPending > 0) { ?>
          <span class="sidebar-badge"><?php echo $totalPending; ?></span>
        <?php } ?>
      </a>
      <a href="admin_academics.php" class="sidebar-link">
        <span class="material-symbols-outlined">school</span> Academics
      </a>
      <a href="admin_ai_overview.php" class="sidebar-link">
        <span class="material-symbols-outlined">psychology</span> AI Overview
      </a>
      <a href="admin_qr_overview.php" class="sidebar-link">
        <span class="material-symbols-outlined">qr_code_scanner</span> QR Attendance
      </a>

      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">Management</p>
      <a href="admin_fees.php" class="sidebar-link">
        <span class="material-symbols-outlined">payments</span> Fees
      </a>
      <a href="admin_leaves.php" class="sidebar-link">
        <span class="material-symbols-outlined">event_busy</span> Leave Requests
        <?php if ($pendingLeaves > 0) { ?>
          <span class="sidebar-badge"><?php echo $pendingLeaves; ?></span>
        <?php } ?>
      </a>
      <a href="admin_exams.php" class="sidebar-link">
        <span class="material-symbols-outlined">quiz</span> Exam Schedule
      </a>
      <a href="admin_notifications.php" class="sidebar-link">
        <span class="material-symbols-outlined">notifications</span> Notifications
        <?php if ($unreadNotifs > 0) { ?>
          <span class="sidebar-badge"><?php echo $unreadNotifs; ?></span>
        <?php } ?>
      </a>
      <a href="admin_announcements.php" class="sidebar-link">
        <span class="material-symbols-outlined">campaign</span> Announcements
      </a>

      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">System</p>
      <a href="admin_settings.php" class="sidebar-link">
        <span class="material-symbols-outlined">settings</span> Settings
      </a>
    </nav>

    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
      <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
        <div class="relative">
          <div class="w-9 h-9 rounded-full bg-primary/20 flex items-center justify-center border-2 border-primary/30">
            <span class="material-symbols-outlined text-primary text-lg fill-icon">manage_accounts</span>
          </div>
          <div class="online-badge"></div>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold truncate"><?php echo $adminName; ?></p>
          <p class="text-xs text-slate-400">Super Admin</p>
        </div>
        <a href="../auth/logout.php" class="text-slate-400 hover:text-red-500 transition-colors">
          <span class="material-symbols-outlined text-lg">logout</span>
        </a>
      </div>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">

    <header class="lg:hidden sticky top-0 z-50 bg-white/90 dark:bg-bg-dark/90 backdrop-blur-md border-b border-primary/8 px-4 py-3 flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-primary flex items-center justify-center text-white">
          <span class="material-symbols-outlined text-lg fill-icon">school</span>
        </div>
        <div>
          <h1 class="text-sm font-bold leading-none"><?php echo $siteName; ?></h1>
          <p class="text-[10px] text-slate-400">Admin Central</p>
        </div>
      </div>
      <div class="flex items-center gap-1">
        <button onclick="toggleNotifPanel()" class="p-2 rounded-full hover:bg-primary/8 text-slate-500 transition-colors relative">
          <span class="material-symbols-outlined text-xl">notifications</span>
          <?php if ($unreadNotifs > 0 || $totalPending > 0) { ?>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
          <?php } ?>
        </button>
      </div>
    </header>

    <header class="hidden lg:flex sticky top-0 z-40 bg-white/80 dark:bg-bg-dark/80 backdrop-blur-md border-b border-primary/8 px-6 py-4 items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white">Dashboard</h2>
        <p class="text-xs text-slate-400 mt-0.5">Overview of campus activity ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â <?php echo date('l, d M Y'); ?></p>
      </div>
      <div class="flex items-center gap-2">
        <a href="admin_fees.php" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-primary/8 text-primary text-sm font-medium hover:bg-primary/15 transition-colors">
          <span class="material-symbols-outlined text-base">download</span> Export Report
        </a>
        <div class="relative">
          <button onclick="toggleNotifPanel()" class="p-2 rounded-xl hover:bg-primary/8 text-slate-500 transition-colors relative">
            <span class="material-symbols-outlined">notifications</span>
            <?php if ($unreadNotifs > 0 || $totalPending > 0) { ?>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
            <?php } ?>
          </button>

          <div id="notif-panel" class="absolute right-0 top-12 w-80 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 z-50 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-700">
              <h3 class="font-bold text-sm">Notifications</h3>
              <a href="admin_notifications.php" class="text-xs text-primary font-semibold hover:underline">View all</a>
            </div>
            <div class="max-h-72 overflow-y-auto">
              <?php if ($totalPending > 0) { ?>
              <a href="admin_users.php" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="w-2 h-2 rounded-full bg-amber-500 mt-1.5 shrink-0"></div>
                <div>
                  <p class="text-sm font-medium"><?php echo $totalPending; ?> Pending Approval<?php echo ($totalPending > 1 ? 's' : ''); ?></p>
                  <p class="text-xs text-slate-400">User registrations waiting for review</p>
                </div>
              </a>
              <?php } ?>

              <?php if ($pendingLeaves > 0) { ?>
              <a href="admin_leaves.php" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                <div>
                  <p class="text-sm font-medium"><?php echo $pendingLeaves; ?> Leave Request<?php echo ($pendingLeaves > 1 ? 's' : ''); ?></p>
                  <p class="text-xs text-slate-400">Leave applications pending action</p>
                </div>
              </a>
              <?php } ?>

              <?php
              if ($recentNotifs && $recentNotifs->num_rows > 0) {
                  while ($n = $recentNotifs->fetch_assoc()) {
              ?>
              <div class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <div class="w-2 h-2 rounded-full notif-dot-<?php echo htmlspecialchars($n['type']); ?> mt-1.5 shrink-0"></div>
                <div>
                  <p class="text-sm font-medium"><?php echo htmlspecialchars($n['title']); ?></p>
                  <p class="text-xs text-slate-400"><?php echo htmlspecialchars(substr($n['message'] ?? '', 0, 60)); ?></p>
                </div>
              </div>
              <?php
                  }
              } else {
              ?>
              <p class="text-xs text-slate-400 text-center py-6">No new notifications</p>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-1 p-4 md:p-6 pb-28 lg:pb-8 space-y-5 max-w-7xl w-full mx-auto">

      <div class="hero-card p-6 text-white anim-fade-up d1">
        <div class="relative z-10">
          <p class="text-xs font-semibold uppercase tracking-widest text-blue-200 mb-1">Welcome back</p>
          <h2 class="text-2xl md:text-3xl font-extrabold"><?php echo $adminName; ?></h2>
          <p class="text-blue-100 text-sm mt-1 opacity-90">Here's what's happening at campus today.</p>
          <div class="flex flex-wrap gap-2 mt-4">
            <?php if ($totalPending > 0) { ?>
            <a href="admin_users.php" class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-white/25 transition-colors">
              <span class="material-symbols-outlined text-sm fill-icon">pending_actions</span>
              <?php echo $totalPending; ?> approval<?php echo ($totalPending > 1 ? 's' : ''); ?> waiting
            </a>
            <?php } ?>
            <?php if ($pendingLeaves > 0) { ?>
            <a href="admin_leaves.php" class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-white/25 transition-colors">
              <span class="material-symbols-outlined text-sm">event_busy</span>
              <?php echo $pendingLeaves; ?> leave<?php echo ($pendingLeaves > 1 ? 's' : ''); ?> pending
            </a>
            <?php } ?>
            <?php if ($upcomingExams > 0) { ?>
            <a href="admin_exams.php" class="inline-flex items-center gap-1.5 bg-white/15 backdrop-blur px-3 py-1.5 rounded-full text-xs font-semibold hover:bg-white/25 transition-colors">
              <span class="material-symbols-outlined text-sm">quiz</span>
              <?php echo $upcomingExams; ?> upcoming exam<?php echo ($upcomingExams > 1 ? 's' : ''); ?>
            </a>
            <?php } ?>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">

        <div class="stat-card anim-fade-up d2">
          <div class="flex items-center justify-between mb-3">
            <div class="icon-blob bg-blue-50"><span class="material-symbols-outlined text-blue-600 text-xl fill-icon">groups</span></div>
            <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Students</span>
          </div>
          <p class="text-2xl md:text-3xl font-extrabold anim-count d3"><?php echo $totalStudents; ?></p>
          <p class="text-xs text-slate-500 mt-1 font-medium">Total Students</p>
          <div class="progress-track mt-3"><div class="progress-fill" style="width:<?php echo min(100, $totalStudents > 0 ? 75 : 0); ?>%"></div></div>
        </div>

        <div class="stat-card anim-fade-up d3">
          <div class="flex items-center justify-between mb-3">
            <div class="icon-blob bg-indigo-50"><span class="material-symbols-outlined text-indigo-600 text-xl fill-icon">person_pin_circle</span></div>
            <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">Teachers</span>
          </div>
          <p class="text-2xl md:text-3xl font-extrabold anim-count d4"><?php echo $totalTeachers; ?></p>
          <p class="text-xs text-slate-500 mt-1 font-medium">Total Teachers</p>
          <div class="progress-track mt-3"><div class="progress-fill" style="width:<?php echo min(100, $totalTeachers > 0 ? 55 : 0); ?>%"></div></div>
        </div>

        <div class="stat-card anim-fade-up d4">
          <div class="flex items-center justify-between mb-3">
            <div class="icon-blob bg-green-50"><span class="material-symbols-outlined text-green-600 text-xl fill-icon">payments</span></div>
            <span class="text-xs font-semibold text-green-600 bg-green-50 px-2 py-0.5 rounded-full"><?php echo $feesPct; ?>% collected</span>
          </div>
          <p class="text-2xl md:text-3xl font-extrabold anim-count d5">ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¹<?php echo number_format($feesPaid); ?></p>
          <p class="text-xs text-slate-500 mt-1 font-medium">Fees Collected</p>
          <div class="fees-bar mt-3"><div class="fees-fill" style="width:<?php echo $feesPct; ?>%"></div></div>
        </div>

        <div class="stat-card anim-fade-up d5">
          <div class="flex items-center justify-between mb-3">
            <div class="icon-blob bg-amber-50"><span class="material-symbols-outlined text-amber-600 text-xl fill-icon">pending_actions</span></div>
            <?php if ($totalPending > 0) { ?>
            <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Action needed</span>
            <?php } ?>
          </div>
          <p class="text-2xl md:text-3xl font-extrabold anim-count d6"><?php echo $totalPending; ?></p>
          <p class="text-xs text-slate-500 mt-1 font-medium">Pending Approvals</p>
          <div class="progress-track mt-3"><div class="progress-fill" style="width:<?php echo min(100, $totalPending * 10); ?>%;background:linear-gradient(90deg,#f59e0b,#ef4444);"></div></div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="lg:col-span-2 chart-card anim-fade-up d6">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="font-bold text-sm text-slate-800 dark:text-white">Attendance Overview</h3>
              <p class="text-xs text-slate-400 mt-0.5">Last 7 days ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â present vs absent</p>
            </div>
            <div class="flex items-center gap-3 text-xs">
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span>Present</span>
              <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-red-400 inline-block"></span>Absent</span>
            </div>
          </div>
          <div style="height:220px;position:relative;">
            <canvas id="attendanceChart"></canvas>
          </div>
        </div>

        <div class="chart-card anim-fade-up d7">
          <div class="mb-4">
            <h3 class="font-bold text-sm text-slate-800 dark:text-white">Students by Branch</h3>
            <p class="text-xs text-slate-400 mt-0.5">Approved enrolments</p>
          </div>
          <div style="height:180px;position:relative;">
            <canvas id="deptChart"></canvas>
          </div>
          <div class="mt-3 space-y-1.5" id="deptLegend"></div>
        </div>

      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <div class="stat-card anim-fade-up d6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold flex items-center gap-2">
              <span class="material-symbols-outlined text-green-600 text-base fill-icon">account_balance_wallet</span>
              Fees Summary
            </h3>
            <a href="admin_fees.php" class="text-xs text-primary font-semibold hover:underline">Manage ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢</a>
          </div>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-xs text-slate-500 font-medium">Collected</span>
              <span class="text-sm font-bold text-green-600">ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¹<?php echo number_format($feesPaid); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-xs text-slate-500 font-medium">Outstanding</span>
              <span class="text-sm font-bold text-red-500">ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¹<?php echo number_format($feesUnpaid); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-xs text-slate-500 font-medium">Total Demand</span>
              <span class="text-sm font-bold">ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¹<?php echo number_format($feesTotal); ?></span>
            </div>
            <div>
              <div class="flex justify-between text-xs mb-1.5">
                <span class="text-slate-400">Collection rate</span>
                <span class="font-bold text-green-600"><?php echo $feesPct; ?>%</span>
              </div>
              <div class="fees-bar"><div class="fees-fill" style="width:<?php echo $feesPct; ?>%"></div></div>
            </div>
          </div>
        </div>

        <div class="stat-card anim-fade-up d7">
          <div class="flex items-center gap-2 mb-4">
            <div class="activity-dot"></div>
            <h3 class="text-sm font-bold text-slate-700 dark:text-slate-200">Online Now</h3>
          </div>
          <div class="flex flex-col gap-4">
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-blue-500 text-lg fill-icon">groups</span>
                  <span class="text-sm font-medium">Students</span>
                </div>
                <span class="text-lg font-extrabold text-primary"><?php echo $loggedStudents; ?></span>
              </div>
              <div class="progress-track"><div class="progress-fill" style="width:<?php echo ($totalStudents > 0 ? round(($loggedStudents / $totalStudents) * 100) : 0); ?>%"></div></div>
            </div>
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2">
                  <span class="material-symbols-outlined text-indigo-500 text-lg fill-icon">person</span>
                  <span class="text-sm font-medium">Teachers</span>
                </div>
                <span class="text-lg font-extrabold text-primary"><?php echo $loggedTeachers; ?></span>
              </div>
              <div class="progress-track"><div class="progress-fill" style="width:<?php echo ($totalTeachers > 0 ? round(($loggedTeachers / $totalTeachers) * 100) : 0); ?>%"></div></div>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 grid grid-cols-2 gap-2 text-center">
            <div>
              <p class="text-xs text-slate-400">Courses</p>
              <p class="text-lg font-extrabold text-purple-600"><?php echo $totalCourses; ?></p>
            </div>
            <div>
              <p class="text-xs text-slate-400">Exams (upcoming)</p>
              <p class="text-lg font-extrabold text-orange-500"><?php echo $upcomingExams; ?></p>
            </div>
          </div>
        </div>

        <div class="anim-fade-up d8">
          <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 px-1">Quick Actions</h3>
          <div class="grid grid-cols-2 gap-3">
            <a href="admin_users.php" class="quick-btn">
              <div class="icon-wrap"><span class="material-symbols-outlined text-lg">person_add</span></div>
              <span class="text-xs font-semibold text-center">Approve Users</span>
            </a>
            <a href="admin_fees.php" class="quick-btn">
              <div class="icon-wrap"><span class="material-symbols-outlined text-lg">payments</span></div>
              <span class="text-xs font-semibold text-center">Manage Fees</span>
            </a>
            <a href="admin_leaves.php" class="quick-btn">
              <div class="icon-wrap"><span class="material-symbols-outlined text-lg">event_busy</span></div>
              <span class="text-xs font-semibold text-center">Leave Requests</span>
            </a>
            <a href="admin_exams.php" class="quick-btn">
              <div class="icon-wrap"><span class="material-symbols-outlined text-lg">quiz</span></div>
              <span class="text-xs font-semibold text-center">Exam Schedule</span>
            </a>
            <a href="admin_announcements.php" class="quick-btn">
              <div class="icon-wrap"><span class="material-symbols-outlined text-lg">campaign</span></div>
              <span class="text-xs font-semibold text-center">Post Notice</span>
            </a>
            <a href="admin_academics.php" class="quick-btn">
              <div class="icon-wrap"><span class="material-symbols-outlined text-lg">calendar_month</span></div>
              <span class="text-xs font-semibold text-center">Timetable</span>
            </a>
          </div>
        </div>

      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div class="stat-card anim-fade-up d8">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold flex items-center gap-2">
              <span class="material-symbols-outlined text-blue-500 text-base fill-icon">event_busy</span>
              Leave Requests
            </h3>
            <a href="admin_leaves.php" class="text-xs text-primary font-semibold hover:underline">View all ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢</a>
          </div>
          <?php
          $hasLeaves = false;
          if ($recentLeaves && $recentLeaves->num_rows > 0) {
              $hasLeaves = true;
              while ($lv = $recentLeaves->fetch_assoc()) {
          ?>
          <div class="flex items-center gap-3 py-2.5 border-b border-slate-50 dark:border-slate-800 last:border-0">
            <div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="material-symbols-outlined text-primary text-sm fill-icon">person</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($lv['user_name'] ?? 'User'); ?></p>
              <p class="text-xs text-slate-400"><?php echo htmlspecialchars($lv['from_date'] ?? ''); ?> ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢ <?php echo htmlspecialchars($lv['to_date'] ?? ''); ?></p>
            </div>
            <span class="badge-<?php echo htmlspecialchars($lv['status']); ?> capitalize"><?php echo htmlspecialchars($lv['status']); ?></span>
          </div>
          <?php
              }
          }
          if (!$hasLeaves) {
          ?>
          <div class="py-8 text-center">
            <span class="material-symbols-outlined text-4xl text-slate-200 block mb-2">event_available</span>
            <p class="text-sm text-slate-400">No leave requests yet</p>
            <a href="admin_leaves.php" class="text-xs text-primary font-semibold hover:underline mt-1 inline-block">Go to Leave Management</a>
          </div>
          <?php } ?>
        </div>

        <div class="stat-card anim-fade-up d9">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold flex items-center gap-2">
              <span class="material-symbols-outlined text-slate-500 text-base fill-icon">history</span>
              Recent Activity
            </h3>
            <span class="text-xs text-slate-400 font-medium">Last 8 actions</span>
          </div>
          <div class="space-y-0">
            <?php
            if ($recentActivities && $recentActivities->num_rows > 0) {
                $i = 0;
                $totalRows = $recentActivities->num_rows;
                while ($activity = $recentActivities->fetch_assoc()) {
                    $i++;
            ?>
            <div class="flex gap-3 py-2.5 border-b border-slate-50 dark:border-slate-800/50 last:border-0 anim-slide" style="animation-delay:<?php echo ($i * .04); ?>s">
              <div class="flex flex-col items-center">
                <div class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0"></div>
                <?php if ($i < min(8, $totalRows)) { ?>
                <div class="w-0.5 flex-1 bg-slate-100 dark:bg-slate-800 mt-1"></div>
                <?php } ?>
              </div>
              <div class="flex-1 min-w-0 pb-1">
                <p class="text-sm font-medium leading-snug"><?php echo htmlspecialchars($activity['activity_text']); ?></p>
                <p class="text-xs text-slate-400 mt-0.5 capitalize"><?php echo htmlspecialchars($activity['user_role']); ?> &middot; <?php echo htmlspecialchars($activity['created_at']); ?></p>
              </div>
            </div>
            <?php
                }
            } else {
            ?>
            <div class="py-8 text-center">
              <span class="material-symbols-outlined text-4xl text-slate-200 mb-2 block">history</span>
              <p class="text-sm text-slate-400">No recent activity found.</p>
            </div>
            <?php } ?>
          </div>
        </div>

      </div>

    </main>
  </div>
</div>

<nav class="bottom-nav lg:hidden fixed bottom-0 left-0 right-0 z-50 px-4 py-3 flex justify-around" style="padding-bottom:max(.75rem,env(safe-area-inset-bottom));">
  <a href="admin_dashboard.php" class="nav-item active" style="color:#4349cf;font-weight:700;">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">home</span><p>Home</p>
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

<script>
const attLabels  = <?php echo json_encode($attLabels); ?>;
const attPresent = <?php echo json_encode($attPresent); ?>;
const attAbsent  = <?php echo json_encode($attAbsent); ?>;
const deptLabels = <?php echo json_encode($deptLabelsClean); ?>;
const deptCounts = <?php echo json_encode($deptCountsClean); ?>;

const brandColors = ['#4349cf','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#64748b'];

const attCanvas = document.getElementById('attendanceChart');
if (attCanvas) {
  const attCtx = attCanvas.getContext('2d');
  new Chart(attCtx, {
    type: 'bar',
    data: {
      labels: attLabels,
      datasets: [
        {
          label: 'Present',
          data: attPresent,
          backgroundColor: '#4349cf',
          borderRadius: 6,
          borderSkipped: false,
          barPercentage: 0.55,
          categoryPercentage: 0.7
        },
        {
          label: 'Absent',
          data: attAbsent,
          backgroundColor: '#fca5a5',
          borderRadius: 6,
          borderSkipped: false,
          barPercentage: 0.55,
          categoryPercentage: 0.7
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#fff',
          titleColor: '#334155',
          bodyColor: '#64748b',
          borderColor: '#e2e8f0',
          borderWidth: 1,
          padding: 10,
          cornerRadius: 10
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: {
            font: { family: 'Plus Jakarta Sans', size: 11 },
            color: '#94a3b8'
          }
        },
        y: {
          grid: { color: '#f1f5f9' },
          ticks: {
            font: { family: 'Plus Jakarta Sans', size: 11 },
            color: '#94a3b8',
            stepSize: 1
          },
          beginAtZero: true
        }
      }
    }
  });
}

const deptCanvas = document.getElementById('deptChart');
if (deptCanvas) {
  const deptCtx = deptCanvas.getContext('2d');
  new Chart(deptCtx, {
    type: 'doughnut',
    data: {
      labels: deptLabels.length ? deptLabels : ['No data'],
      datasets: [{
        data: deptCounts.length ? deptCounts : [1],
        backgroundColor: deptLabels.length ? brandColors.slice(0, deptLabels.length) : ['#e2e8f0'],
        borderWidth: 2,
        borderColor: '#fff',
        hoverOffset: 6
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#fff',
          titleColor: '#334155',
          bodyColor: '#64748b',
          borderColor: '#e2e8f0',
          borderWidth: 1,
          padding: 10,
          cornerRadius: 10
        }
      }
    }
  });
}

const legendEl = document.getElementById('deptLegend');
if (legendEl) {
  if (deptLabels.length) {
    deptLabels.forEach(function(lbl, i) {
      legendEl.innerHTML += `
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5">
            <span style="width:8px;height:8px;border-radius:50%;background:${brandColors[i]};display:inline-block;"></span>
            <span style="font-size:11px;color:#64748b;font-family:'Plus Jakarta Sans',sans-serif;">${lbl}</span>
          </div>
          <span style="font-size:11px;font-weight:700;color:#334155;font-family:'Plus Jakarta Sans',sans-serif;">${deptCounts[i]}</span>
        </div>`;
    });
  } else {
    legendEl.innerHTML = '<p style="font-size:11px;color:#94a3b8;text-align:center;">No branch data yet</p>';
  }
}

function toggleNotifPanel() {
  const panel = document.getElementById('notif-panel');
  if (panel) {
    panel.classList.toggle('open');
  }
}

document.addEventListener('click', function(e) {
  const panel = document.getElementById('notif-panel');
  if (panel && !panel.contains(e.target) && !e.target.closest('button[onclick="toggleNotifPanel()"]')) {
    panel.classList.remove('open');
  }
});

const html = document.documentElement;
const saved = localStorage.getItem('cc_admin_theme');
if (saved === 'dark') {
  html.classList.add('dark');
}

window.addEventListener('load', function() {
  document.querySelectorAll('.progress-fill, .fees-fill').forEach(function(el) {
    const target = el.style.width;
    el.style.width = '0%';
    setTimeout(function() {
      el.style.width = target;
    }, 300);
  });
});
</script>

</body>
</html>