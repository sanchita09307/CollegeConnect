<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
$activeNav = 'attendance';

$studentId = (int)$student['id'];

$records = $conn->query("SELECT a.*, s.subject_name FROM attendance a LEFT JOIN subjects s ON a.subject_id = s.id WHERE a.student_id = $studentId ORDER BY a.date DESC LIMIT 60");

$totalResult = $conn->query("SELECT COUNT(*) as t, SUM(status='present') as p, SUM(status='absent') as ab FROM attendance WHERE student_id = $studentId");
$stats   = $totalResult ? $totalResult->fetch_assoc() : ['t'=>0,'p'=>0,'ab'=>0];
$total   = (int)$stats['t'];
$present = (int)$stats['p'];
$absent  = (int)$stats['ab'];
$pct     = $total > 0 ? round(($present/$total)*100) : 0;
$attColor = $pct >= 75 ? '#16a34a' : ($pct >= 60 ? '#ca8a04' : '#dc2626');
$attBg    = $pct >= 75 ? '#dcfce7'  : ($pct >= 60 ? '#fef9c3'  : '#fee2e2');
$attLabel = $pct >= 75 ? 'Good'     : ($pct >= 60 ? 'Low'      : 'Critical');

$subjectAtt = $conn->query("SELECT s.subject_name, s.subject_code, COUNT(a.id) as total, SUM(a.status='present') as present FROM attendance a JOIN subjects s ON a.subject_id = s.id WHERE a.student_id = $studentId GROUP BY a.subject_id ORDER BY s.subject_name");

$classesNeeded = $pct < 75 ? max(0, (int)ceil((0.75*$total - $present)/(1-0.75))) : 0;
$canMiss       = $pct >= 75 ? (int)floor(($present - 0.75*$total)/(0.75)) : 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>My Attendance â€“ CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
*{font-family:'Lexend',sans-serif}
body{min-height:100dvh;background:#eef0ff}
.dark body{background:#0d0e1c}

/* â”€â”€ Animations â”€â”€ */
@keyframes fadeUp   {from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
@keyframes topbarIn {from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulseRed {0%,100%{opacity:1}50%{opacity:.4}}
@keyframes growBar  {from{width:0}to{}}
@keyframes popIn    {from{opacity:0;transform:scale(.85)}to{opacity:1;transform:scale(1)}}
@keyframes ringFill {from{opacity:0;transform:scale(.7) rotate(-90deg)}to{opacity:1;transform:scale(1) rotate(0deg)}}
@keyframes countUp  {from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

.fu0{animation:fadeUp .45s .0s  ease both}
.fu1{animation:fadeUp .45s .10s ease both}
.fu2{animation:fadeUp .45s .20s ease both}
.fu3{animation:fadeUp .45s .30s ease both}
.fu4{animation:fadeUp .45s .40s ease both}
.pop{animation:popIn  .4s  .15s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse {animation:pulseRed 2s infinite}

/* â”€â”€ Base â”€â”€ */
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06);transition:transform .18s,box-shadow .18s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(67,73,207,.11)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}

/* â”€â”€ Donut ring â”€â”€ */
.att-ring{
  background:
    radial-gradient(closest-side,white 68%,transparent 69% 100%),
    conic-gradient(<?php echo $attColor;?> <?php echo $pct;?>%,#e0e3ff 0);
  animation:ringFill .9s .3s cubic-bezier(.34,1.56,.64,1) both;
}
.dark .att-ring{
  background:
    radial-gradient(closest-side,#1a1b2e 68%,transparent 69% 100%),
    conic-gradient(<?php echo $attColor;?> <?php echo $pct;?>%,#2a2b45 0);
}

/* â”€â”€ Status badges â”€â”€ */
.badge-present{background:#dcfce7;color:#16a34a}
.badge-absent {background:#fee2e2;color:#dc2626}
.badge-late   {background:#fef9c3;color:#ca8a04}

/* â”€â”€ Bar animate â”€â”€ */
.sub-bar{animation:growBar .9s ease both}

/* â”€â”€ Record row â”€â”€ */
.rec-row{transition:background .15s}
.rec-row:hover{background:#f8f9ff}
.dark .rec-row:hover{background:#1e1f35}

/* â”€â”€ Stat chip â”€â”€ */
.stat-chip{border-radius:.875rem;padding:14px 10px;display:flex;flex-direction:column;align-items:center;gap:4px;animation:countUp .5s ease both}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<!-- â”€â”€ HERO BANNER â”€â”€ -->
<div class="px-4 pt-4 fu0">
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/30 relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">assignment_turned_in</span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Attendance Overview</p>
    <h1 class="text-xl font-bold">My Attendance</h1>
    <p class="text-white/70 text-xs mt-1"><?php echo htmlspecialchars($student['department'] ?? '');?> &bull; Sem <?php echo htmlspecialchars($student['semester'] ?? '');?></p>
  </div>
</div>

<!-- â”€â”€ RING + STATS â”€â”€ -->
<div class="px-4 pt-4 fu1">
  <div class="card p-5">
    <div class="flex items-center gap-5">

      <!-- Donut Ring -->
      <div class="att-ring w-28 h-28 rounded-full flex items-center justify-center shrink-0 pop">
        <div class="text-center">
          <span class="text-2xl font-bold block leading-none" style="color:<?php echo $attColor;?>"><?php echo $pct;?>%</span>
          <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full mt-1 inline-block" style="background:<?php echo $attBg;?>;color:<?php echo $attColor;?>"><?php echo $attLabel;?></span>
        </div>
      </div>

      <!-- Stat chips -->
      <div class="flex-1 grid grid-cols-3 gap-2">
        <div class="stat-chip bg-slate-50 dark:bg-slate-800/60" style="animation-delay:.2s">
          <span class="text-xl font-bold text-slate-700 dark:text-slate-200"><?php echo $total;?></span>
          <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider text-center">Total</span>
        </div>
        <div class="stat-chip bg-green-50 dark:bg-green-900/20" style="animation-delay:.28s">
          <span class="text-xl font-bold text-green-600"><?php echo $present;?></span>
          <span class="text-[9px] font-bold text-green-500 uppercase tracking-wider text-center">Present</span>
        </div>
        <div class="stat-chip bg-red-50 dark:bg-red-900/20" style="animation-delay:.36s">
          <span class="text-xl font-bold text-red-500"><?php echo $absent;?></span>
          <span class="text-[9px] font-bold text-red-400 uppercase tracking-wider text-center">Absent</span>
        </div>
      </div>
    </div>

    <!-- Alert bar -->
    <div class="mt-4 rounded-xl px-3 py-2.5 flex items-center gap-2 text-xs font-semibold
      <?php echo $pct >= 75
        ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300'
        : 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-300';?>">
      <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">
        <?php echo $pct >= 75 ? 'check_circle' : 'warning';?>
      </span>
      <?php if($pct >= 75): ?>
        You can miss <strong class="mx-1"><?php echo $canMiss;?></strong> more class<?php echo $canMiss!==1?'es':'';?> and still stay above 75%
      <?php else: ?>
        Attend <strong class="mx-1"><?php echo $classesNeeded;?></strong> more class<?php echo $classesNeeded!==1?'es':'';?> to reach 75% attendance
      <?php endif;?>
    </div>

    <!-- Overall progress bar -->
    <div class="mt-3">
      <div class="h-2.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
        <div class="sub-bar h-full rounded-full" style="width:<?php echo $pct;?>%;background:<?php echo $attColor;?>"></div>
      </div>
      <div class="flex justify-between text-[10px] text-slate-400 mt-1">
        <span>0%</span>
        <span class="font-bold" style="color:<?php echo $attColor;?>"><?php echo $pct;?>%</span>
        <span>100%</span>
      </div>
    </div>
  </div>
</div>

<main class="px-4 pt-4 pb-28 space-y-4">

<!-- â”€â”€ SUBJECT-WISE â”€â”€ -->
<?php if($subjectAtt && $subjectAtt->num_rows > 0): ?>
<div class="fu2">
  <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
    <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">menu_book</span>Subject-wise Attendance
  </h3>
  <div class="space-y-2">
  <?php
    $idx = 0;
    while($sub = $subjectAtt->fetch_assoc()):
      $sp  = (int)$sub['total'] > 0 ? round(($sub['present']/$sub['total'])*100) : 0;
      $sc  = $sp >= 75 ? '#16a34a' : ($sp >= 60 ? '#ca8a04' : '#dc2626');
      $sbg = $sp >= 75 ? '#dcfce7' : ($sp >= 60 ? '#fef9c3' : '#fee2e2');
      $delay = .05 + $idx * .07;
      $idx++;
  ?>
  <div class="card p-4" style="animation:fadeUp .4s <?php echo $delay;?>s ease both">
    <div class="flex justify-between items-start mb-2">
      <div class="flex-1 min-w-0 pr-2">
        <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($sub['subject_name']);?></p>
        <p class="text-[11px] text-slate-400 mt-0.5">
          <?php if(!empty($sub['subject_code'])): ?><span class="font-semibold"><?php echo htmlspecialchars($sub['subject_code']);?></span> &bull; <?php endif;?>
          <?php echo (int)$sub['present'];?>/<?php echo (int)$sub['total'];?> classes attended
        </p>
      </div>
      <span class="text-sm font-bold px-2 py-0.5 rounded-full shrink-0" style="background:<?php echo $sbg;?>;color:<?php echo $sc;?>"><?php echo $sp;?>%</span>
    </div>
    <div class="h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
      <div class="sub-bar h-full rounded-full" style="width:<?php echo $sp;?>%;background:<?php echo $sc;?>;animation-delay:<?php echo $delay+.2;?>s"></div>
    </div>
  </div>
  <?php endwhile;?>
  </div>
</div>
<?php endif;?>

<!-- â”€â”€ RECENT RECORDS â”€â”€ -->
<div class="fu3">
  <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
    <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">history</span>Recent Records
  </h3>

  <?php if($records && $records->num_rows > 0): ?>
  <div class="card overflow-hidden">
    <?php $first=true; while($r = $records->fetch_assoc()):
      $st    = strtolower($r['status'] ?? 'absent');
      $badge = $st==='present' ? 'badge-present' : ($st==='late' ? 'badge-late' : 'badge-absent');
      $icon  = $st==='present' ? 'check_circle'  : ($st==='late' ? 'schedule'   : 'cancel');
      $ic    = $st==='present' ? 'text-green-500' : ($st==='late' ? 'text-yellow-500' : 'text-red-400');
      $dateStr = !empty($r['date']) ? date('d M Y', strtotime($r['date'])) : '';
    ?>
    <div class="rec-row flex items-center gap-3 px-4 py-3 <?php echo !$first?'border-t border-slate-100 dark:border-slate-800':'';?>">
      <div class="w-9 h-9 rounded-xl flex items-center justify-center <?php echo $st==='present'?'bg-green-50 dark:bg-green-900/20':($st==='late'?'bg-yellow-50 dark:bg-yellow-900/20':'bg-red-50 dark:bg-red-900/20');?> shrink-0">
        <span class="material-symbols-outlined text-lg <?php echo $ic;?>" style="font-variation-settings:'FILL' 1;"><?php echo $icon;?></span>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($r['subject_name'] ?? 'Subject');?></p>
        <p class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
          <span class="material-symbols-outlined text-xs">event</span><?php echo $dateStr;?>
        </p>
      </div>
      <span class="text-[11px] font-bold px-2.5 py-1 rounded-full capitalize <?php echo $badge;?> shrink-0"><?php echo htmlspecialchars($r['status'] ?? '');?></span>
    </div>
    <?php $first=false; endwhile;?>
  </div>

  <?php else: ?>
  <div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
    <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">assignment_turned_in</span>
    <h3 class="font-bold text-slate-500 mt-3">No Records Yet</h3>
    <p class="text-xs text-slate-400 mt-1">Your attendance records will appear here.</p>
  </div>
  <?php endif;?>
</div>

</main>

<!-- â”€â”€ BOTTOM NAV â”€â”€ -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php"    class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1 transition-colors"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span></a>
    <a href="student_attendence.php"   class="flex flex-col items-center gap-0.5 text-primary px-3 py-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">assignment_turned_in</span><span class="text-[10px] font-bold">Attend.</span></a>
    <a href="student_studymaterial.php"class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1 transition-colors"><span class="material-symbols-outlined text-xl">book</span><span class="text-[10px]">Material</span></a>
    <a href="student_message.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1 transition-colors"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="student_profile.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1 transition-colors"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span></a>
  </div>
</nav>

<?php include 'topbar_scripts.php'; ?>
</body>
</html>
