<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
$activeNav = 'results';

$studentId = (int)($student['id'] ?? 0);
$sem       = (int)($student['semester'] ?? 6);
$dept      = $student['department'] ?? 'CO';

// Fetch results for all years
$allYears = [];
$yRes = $conn->query("SELECT DISTINCT exam_year FROM results WHERE student_id = $studentId ORDER BY exam_year DESC");
if ($yRes) while ($yr = $yRes->fetch_assoc()) $allYears[] = $yr['exam_year'];

$selYear  = $_GET['year'] ?? ($allYears[0] ?? date('Y'));

// Fetch results for selected year
$results = [];
$sql = "SELECT r.*, s.subject_name, s.subject_code, s.subject_short, s.semester AS sub_sem
        FROM results r
        JOIN subjects s ON r.subject_id = s.id
        WHERE r.student_id = ? AND r.exam_year = ?
        ORDER BY s.semester ASC, s.id ASC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("is", $studentId, $selYear);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $results[] = $row;
    $stmt->close();
}

// Compute overall stats
$totalMarks = 0; $maxTotal = 0; $passCount = 0;
foreach ($results as $r) {
    $totalMarks += (float)($r['total_marks'] ?? 0);
    $maxTotal   += 100;
    if (($r['percentage'] ?? 0) >= 40) $passCount++;
}
$overallPct = $maxTotal > 0 ? round($totalMarks / $maxTotal * 100, 1) : 0;

function gradeColor($g) {
    switch ($g) {
        case 'A+': return ['#16a34a','#dcfce7'];
        case 'A':  return ['#2563eb','#dbeafe'];
        case 'B+': return ['#7c3aed','#ede9fe'];
        case 'B':  return ['#0891b2','#cffafe'];
        case 'C':  return ['#ca8a04','#fef9c3'];
        case 'D':  return ['#ea580c','#ffedd5'];
        default:   return ['#dc2626','#fee2e2'];
    }
}
function pctColor($p) {
    if ($p >= 75) return '#16a34a';
    if ($p >= 60) return '#2563eb';
    if ($p >= 50) return '#ca8a04';
    if ($p >= 40) return '#ea580c';
    return '#dc2626';
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Results â€“ CollegeConnect</title>
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
@keyframes growBar{from{width:0}to{}}
@keyframes ringFill{from{opacity:0;transform:scale(.75)}to{opacity:1;transform:scale(1)}}
.fu0{animation:fadeUp .4s .00s ease both}
.fu1{animation:fadeUp .4s .08s ease both}
.fu2{animation:fadeUp .4s .16s ease both}
.fu3{animation:fadeUp .4s .24s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06);transition:transform .18s,box-shadow .18s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(67,73,207,.11)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.bar-fill{animation:growBar .8s ease both}
.result-row{transition:background .15s}
.result-row:hover{background:#f8f9ff}
.dark .result-row:hover{background:#1e1f35}
.year-tab{padding:6px 14px;border-radius:9999px;font-size:11px;font-weight:700;border:2px solid transparent;transition:all .18s;cursor:pointer}
.year-tab.active{background:#4349cf;color:white;border-color:#4349cf}
.year-tab:not(.active){background:white;color:#64748b;border-color:#e2e8f0}
.dark .year-tab:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<!-- HERO -->
<div class="px-4 pt-4 fu0">
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/30 relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">workspace_premium</span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Academic Results</p>
    <h1 class="text-xl font-bold">My Results</h1>
    <p class="text-white/70 text-xs mt-1"><?php echo htmlspecialchars($dept);?> &bull; Sem <?php echo $sem;?></p>
  </div>
</div>

<!-- YEAR SELECTOR -->
<?php if (!empty($allYears)): ?>
<div class="px-4 pt-4 fu1">
  <div class="flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
    <?php foreach ($allYears as $y): ?>
    <a href="?year=<?php echo urlencode($y);?>"
       class="year-tab shrink-0 <?php echo $selYear==$y?'active':'';?>">
      <?php echo htmlspecialchars($y);?>
    </a>
    <?php endforeach;?>
  </div>
</div>
<?php endif;?>

<?php if (!empty($results)): ?>

<!-- OVERALL SUMMARY -->
<div class="px-4 pt-4 fu1">
  <div class="card p-5">
    <div class="flex items-center gap-4">
      <div class="w-20 h-20 rounded-2xl flex items-center justify-center shrink-0" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
        <div class="text-center text-white">
          <span class="text-xl font-bold block leading-none"><?php echo $overallPct;?>%</span>
          <span class="text-[9px] font-bold">Overall</span>
        </div>
      </div>
      <div class="flex-1">
        <p class="font-bold text-sm">Exam Year: <?php echo htmlspecialchars($selYear);?></p>
        <p class="text-xs text-slate-400 mt-0.5"><?php echo count($results);?> subjects &bull; <?php echo $passCount;?>/<?php echo count($results);?> passed</p>
        <div class="mt-2 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
          <div class="bar-fill h-full rounded-full" style="width:<?php echo $overallPct;?>%;background:<?php echo pctColor($overallPct);?>"></div>
        </div>
      </div>
    </div>
    <!-- Quick stats row -->
    <div class="grid grid-cols-3 gap-2 mt-4">
      <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 text-center">
        <span class="text-lg font-bold text-slate-700 dark:text-slate-200"><?php echo $totalMarks;?></span>
        <p class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">Total Marks</p>
      </div>
      <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-3 text-center">
        <span class="text-lg font-bold text-green-600"><?php echo $passCount;?></span>
        <p class="text-[9px] font-bold text-green-500 uppercase mt-0.5">Passed</p>
      </div>
      <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3 text-center">
        <span class="text-lg font-bold text-red-500"><?php echo count($results)-$passCount;?></span>
        <p class="text-[9px] font-bold text-red-400 uppercase mt-0.5">Failed</p>
      </div>
    </div>
  </div>
</div>

<!-- SUBJECT-WISE RESULTS -->
<main class="px-4 pt-4 pb-28 space-y-3 fu2">
  <h3 class="font-bold text-sm flex items-center gap-2">
    <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">assignment</span>
    Subject-wise Results
  </h3>

  <?php
  $prevSem = null;
  foreach ($results as $idx => $r):
    $pct   = (float)($r['percentage'] ?? 0);
    $grade = $r['grade'] ?? getGrade($pct);
    [$gc,$gbg] = gradeColor($grade);
    $pc    = pctColor($pct);
    $fa    = (float)($r['fa_marks'] ?? 0);
    $sa    = (float)($r['sa_marks'] ?? 0);
    $delay = 0.05 + $idx * 0.06;
    $curSem = $r['sub_sem'] ?? '';

    // Semester group header
    if ($curSem && $curSem !== $prevSem):
      $prevSem = $curSem;
  ?>
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest pt-2">Semester <?php echo $curSem;?></p>
  <?php endif;?>

  <div class="card p-4 result-row" style="animation:fadeUp .4s <?php echo $delay;?>s ease both">
    <div class="flex items-start justify-between gap-2 mb-3">
      <div class="flex-1 min-w-0">
        <p class="font-bold text-sm leading-snug"><?php echo htmlspecialchars($r['subject_name']);?></p>
        <?php if (!empty($r['subject_code'])): ?>
        <p class="text-[10px] text-slate-400 mt-0.5 font-semibold"><?php echo htmlspecialchars($r['subject_code']);?><?php if (!empty($r['subject_short'])): ?> &bull; <?php echo htmlspecialchars($r['subject_short']);?><?php endif;?></p>
        <?php endif;?>
      </div>
      <span class="text-sm font-bold px-2.5 py-1 rounded-xl shrink-0" style="background:<?php echo $gbg;?>;color:<?php echo $gc;?>"><?php echo htmlspecialchars($grade);?></span>
    </div>

    <!-- Marks breakdown -->
    <div class="grid grid-cols-3 gap-2 mb-3">
      <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 text-center">
        <span class="text-sm font-bold"><?php echo $fa;?><span class="text-[9px] text-slate-400">/30</span></span>
        <p class="text-[9px] text-slate-400 uppercase tracking-wide mt-0.5">Internal</p>
      </div>
      <div class="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-2 text-center">
        <span class="text-sm font-bold"><?php echo $sa;?><span class="text-[9px] text-slate-400">/70</span></span>
        <p class="text-[9px] text-slate-400 uppercase tracking-wide mt-0.5">External</p>
      </div>
      <div class="rounded-lg p-2 text-center" style="background:<?php echo $gbg;?>">
        <span class="text-sm font-bold" style="color:<?php echo $gc;?>"><?php echo (float)($r['total_marks']??0);?><span class="text-[9px]">/100</span></span>
        <p class="text-[9px] uppercase tracking-wide mt-0.5" style="color:<?php echo $gc;?>">Total</p>
      </div>
    </div>

    <!-- Percentage bar -->
    <div>
      <div class="flex justify-between text-[10px] font-semibold mb-1">
        <span class="text-slate-400">Percentage</span>
        <span style="color:<?php echo $pc;?>"><?php echo $pct;?>%</span>
      </div>
      <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="bar-fill h-full rounded-full" style="width:<?php echo $pct;?>%;background:<?php echo $pc;?>;animation-delay:<?php echo $delay+.3;?>s"></div>
      </div>
    </div>
  </div>
  <?php endforeach;?>
</main>

<?php else: ?>
<main class="px-4 pt-4 pb-28 fu2">
  <div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
    <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">workspace_premium</span>
    <h3 class="font-bold text-slate-500 mt-3">No Results Found</h3>
    <p class="text-xs text-slate-400 mt-1">Results for <?php echo htmlspecialchars($selYear);?> have not been uploaded yet.</p>
  </div>
</main>
<?php endif;?>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php"    class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span></a>
    <a href="student_attendance.php"   class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">assignment_turned_in</span><span class="text-[10px]">Attend.</span></a>
    <a href="student_studymaterial.php"class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">book</span><span class="text-[10px]">Material</span></a>
    <a href="student_message.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="student_profile.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span></a>
  </div>
</nav>

<?php include 'topbar_scripts.php'; ?>

<?php
function getGrade($pct) {
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 40) return 'D';
    return 'F';
}
?>
</body>
</html>
