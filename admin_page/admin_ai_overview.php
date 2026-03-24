<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

// ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ? Fetch AI scores for all students ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?ÃƒÂ?Ã?â??Â?Ã?â?šÂ?
$students = [];
$res = $conn->query("
    SELECT s.id, s.full_name, s.department, s.semester,
           (SELECT ROUND(SUM(a.status='present')/NULLIF(COUNT(a.id),0)*100,1) FROM attendance a WHERE a.student_id = s.id) as att_pct,
           (SELECT ROUND(AVG(r.percentage),1) FROM results r WHERE r.student_id = s.id) as avg_marks,
           (SELECT ROUND(COUNT(sub.id)/NULLIF((SELECT COUNT(*) FROM assignments asn WHERE asn.semester=s.semester),0)*100,1)
            FROM assignment_submissions sub WHERE sub.student_id=s.id) as assign_pct
    FROM students s WHERE s.status='approved'
");

$riskCounts = [1=>0,2=>0,3=>0,4=>0];
$deptStats  = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $att    = (float)($row['att_pct']    ?? 0);
        $marks  = (float)($row['avg_marks']  ?? 0);
        $assign = (float)($row['assign_pct'] ?? 100);
        $score  = round(($att*0.35)+($marks*0.45)+($assign*0.20), 1);
        $level  = $score>=75 ? 1 : ($score>=55 ? 2 : ($score>=35 ? 3 : 4));
        $riskCounts[$level]++;

        $dept = $row['department'] ?? 'Unknown';
        if (!isset($deptStats[$dept])) $deptStats[$dept] = ['total'=>0,'scoreSum'=>0,'critical'=>0];
        $deptStats[$dept]['total']++;
        $deptStats[$dept]['scoreSum'] += $score;
        if ($level >= 3) $deptStats[$dept]['critical']++;

        $row['ai_score'] = $score;
        $row['risk_level'] = $level;
        $students[] = $row;
    }
}

$totalStudents = count($students);
$atRisk        = $riskCounts[3] + $riskCounts[4];
$avgAiScore    = $totalStudents > 0 ? round(array_sum(array_column($students,'ai_score'))/$totalStudents,1) : 0;

// Sort depts
arsort($deptStats);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>AI Performance Overview ÃƒÂ?Ã?â?šÂ?Ã?â??Å? Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
*{font-family:'Lexend',sans-serif}
body{min-height:100dvh;background:#eef0ff}
.dark body{background:#0d0e1c}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes scanLine{0%{top:-4px}100%{top:110%}}
.fu0{animation:fadeUp .4s .00s ease both} .fu1{animation:fadeUp .4s .08s ease both}
.fu2{animation:fadeUp .4s .16s ease both} .fu3{animation:fadeUp .4s .24s ease both}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.ai-grad{background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#312e81 100%)}
.scan-line{animation:scanLine 2.5s linear infinite;position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#818cf8,transparent);opacity:.4}
/* Sidebar */
.sidebar{background:#fff;border-right:1px solid rgba(67,73,207,.08);width:260px;flex-shrink:0}
.dark .sidebar{background:#161728;border-right-color:rgba(67,73,207,.15)}
.sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#64748b;transition:all .2s;text-decoration:none}
.dark .sidebar-link{color:#94a3b8}
.sidebar-link:hover{background:rgba(67,73,207,.06);color:#4349cf}
.sidebar-link.active{background:#4349cf;color:#fff;box-shadow:0 4px 12px rgba(67,73,207,.3)}
.bottom-nav{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-top:1px solid rgba(67,73,207,.08)}
.dark .bottom-nav{background:rgba(13,14,26,.9);border-top-color:rgba(67,73,207,.2)}
.nav-item{display:flex;flex-direction:column;align-items:center;gap:2px;color:#94a3b8;transition:color .2s}
.nav-item.active{color:#4349cf}
.nav-item span.material-symbols-outlined{font-size:22px}
.nav-item p{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
.fill-icon{font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}
::-webkit-scrollbar{width:4px} ::-webkit-scrollbar-track{background:transparent} ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php $siteName = htmlspecialchars($settings['site_name'] ?? 'CollegeConnect'); ?>

<div class="flex min-h-screen">

  <!-- DESKTOP SIDEBAR -->
  <aside class="sidebar hidden lg:flex flex-col sticky top-0 h-screen p-5 overflow-y-auto">
    <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800 mb-4">
      <div class="w-10 h-10 rounded-xl bg-[#4349cf] flex items-center justify-center text-white shadow-lg shadow-[#4349cf]/30">
        <span class="material-symbols-outlined text-xl fill-icon">school</span>
      </div>
      <div>
        <h1 class="font-bold text-slate-800 dark:text-white leading-none"><?php echo $siteName; ?></h1>
        <p class="text-xs text-slate-400 mt-0.5">Admin Central</p>
      </div>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-1">Main</p>
      <a href="admin_dashboard.php" class="sidebar-link"><span class="material-symbols-outlined">dashboard</span> Dashboard</a>
      <a href="admin_users.php" class="sidebar-link"><span class="material-symbols-outlined">group</span> Users</a>
      <a href="admin_academics.php" class="sidebar-link"><span class="material-symbols-outlined">school</span> Academics</a>
      <a href="admin_ai_overview.php" class="sidebar-link active"><span class="material-symbols-outlined fill-icon">psychology</span> AI Overview</a>
      <a href="admin_qr_overview.php" class="sidebar-link"><span class="material-symbols-outlined">qr_code_scanner</span> QR Attendance</a>
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">Management</p>
      <a href="admin_leaves.php" class="sidebar-link"><span class="material-symbols-outlined">event_busy</span> Leave Requests</a>
      <a href="admin_exams.php" class="sidebar-link"><span class="material-symbols-outlined">quiz</span> Exam Schedule</a>
      <a href="admin_notifications.php" class="sidebar-link"><span class="material-symbols-outlined">notifications</span> Notifications</a>
      <a href="admin_announcements.php" class="sidebar-link"><span class="material-symbols-outlined">campaign</span> Announcements</a>
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">System</p>
      <a href="admin_settings.php" class="sidebar-link"><span class="material-symbols-outlined">settings</span> Settings</a>
    </nav>
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
      <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50">
        <div class="w-9 h-9 rounded-full bg-[#4349cf]/20 flex items-center justify-center border-2 border-[#4349cf]/30">
          <span class="material-symbols-outlined text-[#4349cf] text-lg fill-icon">manage_accounts</span>
        </div>
        <div>
          <p class="text-xs font-bold text-slate-700 dark:text-slate-200">Administrator</p>
          <p class="text-[10px] text-slate-400">Super Admin</p>
        </div>
      </div>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="flex-1 min-w-0 pb-24 lg:pb-8">

  <!-- MOBILE TOPBAR -->
  <div class="lg:hidden sticky top-0 z-50 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md px-4 py-3 flex items-center gap-2 border-b border-slate-200/70 dark:border-slate-800">
    <a href="admin_dashboard.php" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
      <span class="material-symbols-outlined text-slate-600 dark:text-slate-300 text-xl">arrow_back</span>
    </a>
    <div class="p-1.5 rounded-xl text-white shadow" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
      <span class="material-symbols-outlined text-lg fill-icon">psychology</span>
    </div>
    <div>
      <p class="text-sm font-bold leading-none"><?php echo $siteName; ?></p>
      <p class="text-[10px] text-[#4349cf]/70">AI Analytics</p>
    </div>
  </div>

  <!-- DESKTOP PAGE HEADER -->
  <div class="hidden lg:flex items-center justify-between px-8 pt-7 pb-2">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow-lg" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
        <span class="material-symbols-outlined fill-icon">psychology</span>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-white leading-none">AI Performance Overview</h1>
        <p class="text-xs text-slate-400 mt-0.5">College-wide AI analysis &bull; <?php echo $totalStudents; ?> students</p>
      </div>
    </div>
    <span class="text-[10px] font-bold bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300 px-3 py-1.5 rounded-full uppercase tracking-widest border border-indigo-200 dark:border-indigo-700/40">Ã¢Å“Â¦ AI Powered</span>
  </div>

<!-- HERO (mobile only) -->
<div class="lg:hidden px-4 pt-4 fu0">
  <div class="ai-grad rounded-2xl p-5 text-white shadow-xl relative overflow-hidden">
    <div class="scan-line"></div>
    <div class="absolute -right-4 -top-4 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">psychology</span>
    </div>
    <span class="text-[9px] font-bold bg-indigo-500/30 text-indigo-200 px-2 py-0.5 rounded-full uppercase tracking-widest border border-indigo-400/30">ÃƒÂ¢Ã…â€œÃ‚Â¦ AI Powered</span>
    <h1 class="text-xl font-bold mt-2">Performance Overview</h1>
    <p class="text-white/60 text-xs mt-1">College-wide AI analysis &bull; <?php echo $totalStudents; ?> students</p>
  </div>
</div>

<!-- KPI CARDS -->
<div class="px-4 lg:px-8 pt-4 fu1 grid grid-cols-2 lg:grid-cols-4 gap-3">
  <div class="card p-4">
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg AI Score</p>
    <p class="text-3xl font-bold mt-1" style="color:<?php echo $avgAiScore>=60?'#4349cf':($avgAiScore>=40?'#ca8a04':'#dc2626'); ?>"><?php echo $avgAiScore; ?></p>
    <p class="text-[10px] text-slate-400">out of 100</p>
  </div>
  <div class="card p-4" style="border-left:3px solid #dc2626">
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">At-Risk Students</p>
    <p class="text-3xl font-bold text-red-500 mt-1"><?php echo $atRisk; ?></p>
    <p class="text-[10px] text-slate-400">High + Critical</p>
  </div>
  <div class="card p-4" style="border-left:3px solid #dc2626">
    <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest">Critical</p>
    <p class="text-3xl font-bold text-red-500 mt-1"><?php echo $riskCounts[4]; ?></p>
    <p class="text-[10px] text-slate-400">Score &lt; 35</p>
  </div>
  <div class="card p-4" style="border-left:3px solid #16a34a">
    <p class="text-[10px] font-bold text-green-500 uppercase tracking-widest">Low Risk</p>
    <p class="text-3xl font-bold text-green-500 mt-1"><?php echo $riskCounts[1]; ?></p>
    <p class="text-[10px] text-slate-400">Score ÃƒÂ¢Ã¢â‚¬Â°Ã‚Â¥ 75</p>
  </div>
</div>

<!-- DONUT CHART -->
<div class="px-4 lg:px-8 pt-4 fu2 lg:grid lg:grid-cols-2 lg:gap-4">
  <div class="card p-4 lg:p-6">
    <p class="text-xs font-bold text-slate-500 mb-3 uppercase tracking-widest">Risk Distribution</p>
    <div class="flex items-center gap-4">
      <div style="width:160px;height:160px;flex-shrink:0">
        <canvas id="riskDonut"></canvas>
      </div>
      <div class="space-y-2 flex-1 text-xs">
        <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>Critical</span><span class="font-bold"><?php echo $riskCounts[4]; ?></span></div>
        <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-500 inline-block"></span>High Risk</span><span class="font-bold"><?php echo $riskCounts[3]; ?></span></div>
        <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-yellow-400 inline-block"></span>Moderate</span><span class="font-bold"><?php echo $riskCounts[2]; ?></span></div>
        <div class="flex justify-between"><span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>Low Risk</span><span class="font-bold"><?php echo $riskCounts[1]; ?></span></div>
      </div>
    </div>
  </div>

<!-- DEPT-WISE TABLE -->
<?php if (!empty($deptStats)): ?>
  <div class="card p-4 lg:p-6 mt-4 lg:mt-0 fu3">
    <p class="text-xs font-bold text-slate-500 mb-3 uppercase tracking-widest">Department-wise Analysis</p>
    <div class="space-y-3">
      <?php foreach ($deptStats as $dept => $ds):
        $avgScore = $ds['total'] > 0 ? round($ds['scoreSum']/$ds['total'],1) : 0;
        $dc = $avgScore>=60?'#16a34a':($avgScore>=40?'#ca8a04':'#dc2626');
      ?>
      <div>
        <div class="flex justify-between text-xs font-semibold mb-1">
          <span><?php echo htmlspecialchars($dept); ?> <span class="text-slate-400 font-normal">(<?php echo $ds['total']; ?> students)</span></span>
          <span style="color:<?php echo $dc; ?>"><?php echo $avgScore; ?>/100</span>
        </div>
        <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
          <div class="h-full rounded-full" style="width:<?php echo $avgScore; ?>%;background:<?php echo $dc; ?>;transition:width 1s ease"></div>
        </div>
        <?php if ($ds['critical'] > 0): ?>
        <p class="text-[9px] text-red-500 mt-0.5"><?php echo $ds['critical']; ?> student(s) need immediate attention</p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
<?php endif; ?>
</div>

<!-- QUICK LINKS -->
<div class="px-4 lg:px-8 pt-4 pb-10 fu3">
  <a href="teacher_ai_atrisk.php" class="card p-4 flex items-center gap-3 hover:shadow-lg transition-all">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:#fee2e2">
      <span class="material-symbols-outlined text-red-500 fill-icon">crisis_alert</span>
    </div>
    <div class="flex-1">
      <p class="font-bold text-sm">View At-Risk Students</p>
      <p class="text-xs text-slate-400"><?php echo $atRisk; ?> students need attention</p>
    </div>
    <span class="material-symbols-outlined text-slate-400">chevron_right</span>
  </a>
</div>

  </main>
</div>

<script>
const isDark = document.documentElement.classList.contains('dark');
new Chart(document.getElementById('riskDonut'), {
  type: 'doughnut',
  data: {
    labels: ['Critical','High Risk','Moderate','Low Risk'],
    datasets:[{
      data: [<?php echo $riskCounts[4]; ?>,<?php echo $riskCounts[3]; ?>,<?php echo $riskCounts[2]; ?>,<?php echo $riskCounts[1]; ?>],
      backgroundColor: ['#ef4444','#f97316','#eab308','#22c55e'],
      borderWidth: 0, borderRadius: 4
    }]
  },
  options:{
    cutout:'68%', responsive:true, maintainAspectRatio:true,
    plugins:{ legend:{ display:false } }
  }
});
</script>
<nav class="bottom-nav lg:hidden fixed bottom-0 left-0 right-0 z-50 px-4 py-3 flex justify-around" style="padding-bottom:max(.75rem,env(safe-area-inset-bottom));">
  <a href="admin_dashboard.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">home</span><p>Home</p>
  </a>
  <a href="admin_users.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">group</span><p>Users</p>
  </a>
  <a href="admin_ai_overview.php" class="nav-item active" style="color:#4349cf;font-weight:700;">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">psychology</span><p>AI</p>
  </a>
  <a href="admin_settings.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">settings</span><p>Settings</p>
  </a>
</nav>
</body>
</html>
