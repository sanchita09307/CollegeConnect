<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
$activeNav = 'ai_predictor';

$studentId = (int)($student['id'] ?? 0);
$sem       = (int)($student['semester'] ?? 6);
$dept      = $student['department'] ?? 'CO';
$name      = $student['full_name'] ?? 'Student';

// ── 1. ATTENDANCE ──────────────────────────────────────────────────────────
$attRow = $conn->query("
    SELECT COUNT(*) as total,
           SUM(status='present') as present,
           SUM(status='absent')  as absent
    FROM attendance WHERE student_id = $studentId
")->fetch_assoc();
$attTotal   = (int)($attRow['total']   ?? 0);
$attPresent = (int)($attRow['present'] ?? 0);
$attPct     = $attTotal > 0 ? round($attPresent / $attTotal * 100, 1) : 0;

// ── 2. RESULTS (last available year) ───────────────────────────────────────
$yearRow = $conn->query("SELECT MAX(exam_year) as yr FROM results WHERE student_id = $studentId")->fetch_assoc();
$examYear = $yearRow['yr'] ?? null;

$avgMarks = 0; $subjectCount = 0; $failCount = 0; $subjectResults = [];
if ($examYear) {
    $res = $conn->query("
        SELECT r.*, s.subject_name, s.subject_code
        FROM results r JOIN subjects s ON r.subject_id = s.id
        WHERE r.student_id = $studentId AND r.exam_year = '$examYear'
        ORDER BY s.id ASC
    ");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $subjectResults[] = $row;
            $avgMarks   += (float)($row['percentage'] ?? 0);
            $subjectCount++;
            if ((float)($row['percentage'] ?? 0) < 40) $failCount++;
        }
        if ($subjectCount > 0) $avgMarks = round($avgMarks / $subjectCount, 1);
    }
}

// ── 3. ASSIGNMENTS ─────────────────────────────────────────────────────────
$assignRow = $conn->query("
    SELECT COUNT(DISTINCT a.id) as total,
           COUNT(DISTINCT sub.id) as submitted
    FROM assignments a
    LEFT JOIN assignment_submissions sub ON sub.assignment_id = a.id AND sub.student_id = $studentId
    WHERE a.semester = $sem
")->fetch_assoc();
$assignTotal     = (int)($assignRow['total']     ?? 0);
$assignSubmitted = (int)($assignRow['submitted'] ?? 0);
$assignPct       = $assignTotal > 0 ? round($assignSubmitted / $assignTotal * 100, 1) : 100;

// ── 4. AI SCORE FORMULA ────────────────────────────────────────────────────
// Weighted: Attendance 35% + Marks 45% + Assignment 20%
$attScore    = min(100, $attPct);
$marksScore  = min(100, $avgMarks);
$assignScore = min(100, $assignPct);

$aiScore = round(($attScore * 0.35) + ($marksScore * 0.45) + ($assignScore * 0.20), 1);

// ── 5. RISK LEVEL ──────────────────────────────────────────────────────────
if ($aiScore >= 75)      { $risk = 'Low Risk';      $riskColor = '#16a34a'; $riskBg = '#dcfce7'; $riskIcon = 'verified';        $riskEmoji = '🟢'; }
elseif ($aiScore >= 55)  { $risk = 'Moderate Risk'; $riskColor = '#ca8a04'; $riskBg = '#fef9c3'; $riskIcon = 'warning';          $riskEmoji = '🟡'; }
elseif ($aiScore >= 35)  { $risk = 'High Risk';     $riskColor = '#ea580c'; $riskBg = '#ffedd5'; $riskIcon = 'error';            $riskEmoji = '🟠'; }
else                     { $risk = 'Critical';      $riskColor = '#dc2626'; $riskBg = '#fee2e2'; $riskIcon = 'crisis_alert';     $riskEmoji = '🔴'; }

// ── 6. AI RECOMMENDATIONS ──────────────────────────────────────────────────
$recommendations = [];

if ($attPct < 75) {
    $needed = max(0, (int)ceil((0.75 * $attTotal - $attPresent) / 0.25));
    $recommendations[] = [
        'icon'  => 'event_available',
        'color' => '#dc2626',
        'title' => 'Attendance Critical',
        'desc'  => "Your attendance is {$attPct}%. You need to attend $needed more classes to reach 75%.",
    ];
}
if ($avgMarks > 0 && $avgMarks < 50) {
    $recommendations[] = [
        'icon'  => 'school',
        'color' => '#ea580c',
        'title' => 'Marks Need Improvement',
        'desc'  => "Your average is {$avgMarks}%. Focus on weak subjects and practice previous exam papers.",
    ];
}
if ($failCount > 0) {
    $recommendations[] = [
        'icon'  => 'quiz',
        'color' => '#7c3aed',
        'title' => "$failCount Subject(s) Below Pass Mark",
        'desc'  => "You have failed $failCount subject(s). Prioritize these with extra study time immediately.",
    ];
}
if ($assignPct < 70) {
    $recommendations[] = [
        'icon'  => 'assignment_late',
        'color' => '#ca8a04',
        'title' => 'Pending Assignments',
        'desc'  => "Only {$assignPct}% assignments submitted. Complete pending ones before deadlines.",
    ];
}
if (empty($recommendations)) {
    $recommendations[] = [
        'icon'  => 'emoji_events',
        'color' => '#16a34a',
        'title' => 'Great Performance!',
        'desc'  => "Keep up the excellent work. Stay consistent and maintain your current pace.",
    ];
}

// ── 7. SUBJECT WEAKNESS RADAR ──────────────────────────────────────────────
$weakSubjects = array_filter($subjectResults, fn($r) => (float)($r['percentage'] ?? 0) < 60);
usort($weakSubjects, fn($a,$b) => $a['percentage'] <=> $b['percentage']);
$weakSubjects = array_slice($weakSubjects, 0, 5);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>AI Performance Predictor – CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
*{font-family:'Lexend',sans-serif}
body{min-height:100dvh;background:#eef0ff}
.dark body{background:#0d0e1c}
@keyframes fadeUp  {from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes topbarIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulseRed{0%,100%{opacity:1}50%{opacity:.4}}
@keyframes growBar {from{width:0}to{}}
@keyframes scoreCount{from{opacity:0;transform:scale(.6)}to{opacity:1;transform:scale(1)}}
@keyframes scanLine{0%{top:-4px}100%{top:110%}}
@keyframes brainPulse{0%,100%{transform:scale(1);filter:brightness(1)}50%{transform:scale(1.06);filter:brightness(1.15)}}
@keyframes cardReveal{from{opacity:0;transform:translateY(20px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
.fu0{animation:fadeUp .4s .00s ease both}
.fu1{animation:fadeUp .4s .08s ease both}
.fu2{animation:fadeUp .4s .16s ease both}
.fu3{animation:fadeUp .4s .24s ease both}
.fu4{animation:fadeUp .4s .32s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06);transition:transform .18s,box-shadow .18s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(67,73,207,.11)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.ai-grad{background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 50%,#312e81 100%)}
.bar-fill{animation:growBar .9s ease both}
.score-anim{animation:scoreCount .7s .4s cubic-bezier(.34,1.56,.64,1) both}
.brain-pulse{animation:brainPulse 3s ease-in-out infinite}
.scan-line{animation:scanLine 2.5s linear infinite;position:absolute;left:0;right:0;height:2px;background:linear-gradient(90deg,transparent,#818cf8,transparent);opacity:.4}
.rec-card{animation:cardReveal .5s ease both}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<!-- HERO -->
<div class="px-4 pt-4 fu0">
  <div class="ai-grad rounded-2xl p-5 text-white shadow-xl relative overflow-hidden">
    <div class="scan-line"></div>
    <div class="absolute -right-4 -top-4 opacity-10 pointer-events-none brain-pulse">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">psychology</span>
    </div>
    <div class="flex items-center gap-2 mb-2">
      <span class="text-[9px] font-bold bg-indigo-500/30 text-indigo-200 px-2 py-0.5 rounded-full uppercase tracking-widest border border-indigo-400/30">✦ AI Powered</span>
    </div>
    <h1 class="text-xl font-bold">Performance Predictor</h1>
    <p class="text-white/60 text-xs mt-1"><?php echo htmlspecialchars($dept); ?> &bull; Sem <?php echo $sem; ?> &bull; <?php echo htmlspecialchars($name); ?></p>
    <p class="text-white/40 text-[10px] mt-2">Analyzed: Attendance + Marks + Assignments</p>
  </div>
</div>

<!-- AI SCORE CARD -->
<div class="px-4 pt-4 fu1">
  <div class="card p-5 relative overflow-hidden">
    <div class="absolute top-0 right-0 w-32 h-32 rounded-full opacity-5 pointer-events-none" style="background:<?php echo $riskColor; ?>;transform:translate(30%,-30%)"></div>
    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">AI Performance Score</p>
    <div class="flex items-center gap-5">
      <!-- Score Circle -->
      <div class="relative w-24 h-24 shrink-0">
        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
          <circle cx="50" cy="50" r="40" fill="none" stroke="#e0e3ff" stroke-width="10"/>
          <circle cx="50" cy="50" r="40" fill="none"
            stroke="<?php echo $riskColor; ?>" stroke-width="10"
            stroke-dasharray="<?php echo round(2.513 * $aiScore, 1); ?> 251.3"
            stroke-linecap="round"
            style="transition:stroke-dasharray 1.2s ease"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <span class="text-xl font-bold score-anim" style="color:<?php echo $riskColor; ?>"><?php echo $aiScore; ?></span>
          <span class="text-[8px] text-slate-400 font-semibold">/100</span>
        </div>
      </div>
      <!-- Risk Badge + Breakdown -->
      <div class="flex-1">
        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-sm font-bold mb-2" style="background:<?php echo $riskBg; ?>;color:<?php echo $riskColor; ?>">
          <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;"><?php echo $riskIcon; ?></span>
          <?php echo $risk; ?>
        </div>
        <div class="space-y-1.5 text-xs">
          <div class="flex justify-between items-center">
            <span class="text-slate-500">Attendance (35%)</span>
            <span class="font-bold"><?php echo $attPct; ?>%</span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-500">Marks (45%)</span>
            <span class="font-bold"><?php echo $avgMarks > 0 ? $avgMarks.'%' : 'N/A'; ?></span>
          </div>
          <div class="flex justify-between items-center">
            <span class="text-slate-500">Assignments (20%)</span>
            <span class="font-bold"><?php echo $assignPct; ?>%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- METRIC BARS -->
<div class="px-4 pt-4 fu2 space-y-3">
  <h3 class="font-bold text-sm flex items-center gap-2">
    <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">analytics</span>
    Parameter Analysis
  </h3>

  <!-- Attendance Bar -->
  <div class="card p-4">
    <div class="flex justify-between text-xs font-semibold mb-1">
      <span class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;color:<?php echo $attPct>=75?'#16a34a':'#dc2626';?>">event_available</span>
        Attendance
      </span>
      <span style="color:<?php echo $attPct>=75?'#16a34a':($attPct>=60?'#ca8a04':'#dc2626'); ?>"><?php echo $attPct; ?>% <?php echo $attPct<75?'⚠️':'✓'; ?></span>
    </div>
    <div class="h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
      <div class="bar-fill h-full rounded-full" style="width:<?php echo $attPct; ?>%;background:<?php echo $attPct>=75?'#16a34a':($attPct>=60?'#ca8a04':'#dc2626'); ?>"></div>
    </div>
    <p class="text-[10px] text-slate-400 mt-1"><?php echo $attPresent; ?> present / <?php echo $attTotal; ?> total classes</p>
  </div>

  <!-- Marks Bar -->
  <?php if ($avgMarks > 0): ?>
  <div class="card p-4">
    <div class="flex justify-between text-xs font-semibold mb-1">
      <span class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;color:<?php echo $avgMarks>=60?'#2563eb':'#ea580c';?>">workspace_premium</span>
        Average Marks
      </span>
      <span style="color:<?php echo $avgMarks>=60?'#2563eb':($avgMarks>=40?'#ca8a04':'#dc2626'); ?>"><?php echo $avgMarks; ?>%</span>
    </div>
    <div class="h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
      <div class="bar-fill h-full rounded-full" style="width:<?php echo $avgMarks; ?>%;background:<?php echo $avgMarks>=60?'#2563eb':($avgMarks>=40?'#ca8a04':'#dc2626'); ?>"></div>
    </div>
    <p class="text-[10px] text-slate-400 mt-1"><?php echo $subjectCount; ?> subjects &bull; <?php echo $failCount; ?> failed</p>
  </div>
  <?php endif; ?>

  <!-- Assignment Bar -->
  <div class="card p-4">
    <div class="flex justify-between text-xs font-semibold mb-1">
      <span class="flex items-center gap-1.5">
        <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;color:<?php echo $assignPct>=70?'#7c3aed':'#ca8a04';?>">assignment_turned_in</span>
        Assignments
      </span>
      <span style="color:<?php echo $assignPct>=70?'#7c3aed':'#ca8a04'; ?>"><?php echo $assignPct; ?>%</span>
    </div>
    <div class="h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
      <div class="bar-fill h-full rounded-full" style="width:<?php echo $assignPct; ?>%;background:<?php echo $assignPct>=70?'#7c3aed':'#ca8a04'; ?>"></div>
    </div>
    <p class="text-[10px] text-slate-400 mt-1"><?php echo $assignSubmitted; ?> / <?php echo $assignTotal; ?> submitted</p>
  </div>
</div>

<!-- WEAK SUBJECTS -->
<?php if (!empty($weakSubjects)): ?>
<div class="px-4 pt-4 fu3">
  <h3 class="font-bold text-sm flex items-center gap-2 mb-3">
    <span class="material-symbols-outlined text-red-500" style="font-variation-settings:'FILL' 1;">report</span>
    Subjects Needing Attention
  </h3>
  <div class="space-y-2">
    <?php foreach ($weakSubjects as $i => $ws):
      $wp = (float)($ws['percentage'] ?? 0);
      $wc = $wp < 40 ? '#dc2626' : '#ea580c';
    ?>
    <div class="card p-3" style="animation:cardReveal .4s <?php echo .05+$i*.07; ?>s ease both">
      <div class="flex justify-between items-center mb-1.5">
        <span class="text-xs font-semibold truncate flex-1"><?php echo htmlspecialchars($ws['subject_name']); ?></span>
        <span class="text-xs font-bold ml-2 px-2 py-0.5 rounded-full" style="background:<?php echo $wp<40?'#fee2e2':'#ffedd5'; ?>;color:<?php echo $wc; ?>"><?php echo $wp; ?>%</span>
      </div>
      <div class="h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="bar-fill h-full rounded-full" style="width:<?php echo $wp; ?>%;background:<?php echo $wc; ?>"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- CHART (Subject-wise %) -->
<?php if (!empty($subjectResults)): ?>
<div class="px-4 pt-4 fu3">
  <div class="card p-4">
    <p class="text-xs font-bold text-slate-500 mb-3 uppercase tracking-widest">Subject Performance Chart</p>
    <div style="max-height:220px">
      <canvas id="subjectChart"></canvas>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- AI RECOMMENDATIONS -->
<div class="px-4 pt-4 pb-28 fu4">
  <h3 class="font-bold text-sm flex items-center gap-2 mb-3">
    <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">tips_and_updates</span>
    AI Recommendations
  </h3>
  <div class="space-y-3">
    <?php foreach ($recommendations as $i => $rec): ?>
    <div class="card p-4 rec-card" style="animation-delay:<?php echo .05 + $i*.09; ?>s;border-left:3px solid <?php echo $rec['color']; ?>">
      <div class="flex gap-3">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0" style="background:<?php echo $rec['color']; ?>1a">
          <span class="material-symbols-outlined text-lg" style="color:<?php echo $rec['color']; ?>;font-variation-settings:'FILL' 1;"><?php echo $rec['icon']; ?></span>
        </div>
        <div>
          <p class="text-sm font-bold"><?php echo htmlspecialchars($rec['title']); ?></p>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed"><?php echo htmlspecialchars($rec['desc']); ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Disclaimer -->
  <p class="text-[10px] text-slate-400 text-center mt-5 px-4 leading-relaxed">
    ✦ AI predictions are based on attendance, marks &amp; assignment data.
    Scores are indicative — consult your teacher for guidance.
  </p>
</div>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px] font-medium">Home</span></a>
    <a href="student_message.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1 transition-colors"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="student_ai_predictor.php" class="flex flex-col items-center gap-0.5 text-primary px-4 py-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">psychology</span><span class="text-[10px] font-bold">AI</span></a>
    <a href="student_profile.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px] font-medium">Profile</span></a>
  </div>
</nav>


<?php include 'topbar_scripts.php'; ?>

<?php if (!empty($subjectResults)): ?>
<script>
const labels  = <?php echo json_encode(array_map(fn($r) => $r['subject_code'] ?: $r['subject_name'], $subjectResults)); ?>;
const dataArr = <?php echo json_encode(array_map(fn($r) => (float)($r['percentage'] ?? 0), $subjectResults)); ?>;
const colors  = dataArr.map(v => v >= 60 ? '#4349cf' : v >= 40 ? '#ea580c' : '#dc2626');

const isDark = document.documentElement.classList.contains('dark');
new Chart(document.getElementById('subjectChart'), {
  type: 'bar',
  data: { labels, datasets: [{ data: dataArr, backgroundColor: colors, borderRadius: 6, borderSkipped: false }] },
  options: {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 9, family: 'Lexend' } }, grid: { display: false } },
      y: { min: 0, max: 100, ticks: { color: isDark ? '#94a3b8' : '#64748b', font: { size: 9 }, stepSize: 25 }, grid: { color: isDark ? '#1e293b' : '#f1f5f9' } }
    }
  }
});
</script>
<?php endif; ?>
</body>
</html>