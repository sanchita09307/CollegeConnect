<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$department   = $student['department'] ?? 'CO';
$semester     = (int)($student['semester'] ?? 6);

$ttType       = $_GET['type'] ?? 'class';   // class | exam
$filterSem    = (int)($_GET['sem'] ?? $semester);
$filterBranch = $_GET['branch'] ?? $department;

// Fetch class timetable
$ttRows = [];
if ($ttType === 'class') {
    $sql = "SELECT t.*, s.subject_name, s.subject_code, s.subject_short, tea.name AS teacher_name
            FROM timetables t
            JOIN subjects s ON t.subject_id = s.id
            LEFT JOIN teachers tea ON t.teacher_id = tea.id
            WHERE t.branch_code = ? AND t.semester = ? AND t.timetable_type = 'class'
            ORDER BY FIELD(t.day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $filterBranch, $filterSem);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $ttRows[] = $r;
        $stmt->close();
    }
} else {
    // Exam timetable
    $sql = "SELECT t.*, s.subject_name, s.subject_code, s.subject_short
            FROM timetables t
            JOIN subjects s ON t.subject_id = s.id
            WHERE t.branch_code = ? AND t.semester = ? AND t.timetable_type = 'exam'
            ORDER BY t.exam_date ASC, t.start_time ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $filterBranch, $filterSem);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $ttRows[] = $r;
        $stmt->close();
    }
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// Group class timetable by day
$byDay = [];
if ($ttType === 'class') {
    foreach ($days as $d) $byDay[$d] = [];
    foreach ($ttRows as $r) $byDay[$r['day_name'] ?? 'Monday'][] = $r;
}

$activeNav = 'timetable';

$subjectColors = ['#4349cf','#7c3aed','#0891b2','#16a34a','#ca8a04','#ea580c','#dc2626','#2563eb'];
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Timetable â€“ CollegeConnect</title>
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
.fu0{animation:fadeUp .4s .00s ease both}
.fu1{animation:fadeUp .4s .08s ease both}
.fu2{animation:fadeUp .4s .16s ease both}
.fu3{animation:fadeUp .4s .24s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-class{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.hero-exam{background:linear-gradient(135deg,#dc2626 0%,#f97316 100%)}
.tab-btn{padding:7px 18px;border-radius:9999px;font-size:12px;font-weight:700;border:2px solid transparent;transition:all .18s;cursor:pointer;flex:1}
.tab-btn.active{color:white}
.tab-class.active{background:#4349cf;border-color:#4349cf}
.tab-exam.active{background:#dc2626;border-color:#dc2626}
.tab-btn:not(.active){background:white;color:#64748b;border-color:#e2e8f0}
.dark .tab-btn:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45}
.day-header{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:8px 16px;background:#eef0ff;color:#4349cf;border-radius:.5rem}
.dark .day-header{background:#1a1b2e}
.slot{border-radius:.875rem;padding:10px 12px;border-left:4px solid transparent;transition:transform .15s,box-shadow .15s}
.slot:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.today-row{background:rgba(67,73,207,.04);border-radius:.875rem}
select.cc-sel{background:white;border:2px solid #e2e8f0;border-radius:.75rem;padding:6px 10px;font-size:12px;font-weight:600;color:#475569;outline:none;transition:border-color .2s;cursor:pointer;font-family:'Lexend',sans-serif;width:100%}
select.cc-sel:focus{border-color:#4349cf}
.dark select.cc-sel{background:#1a1b2e;border-color:#2a2b45;color:#94a3b8}
.exam-card{border-radius:1rem;overflow:hidden;transition:transform .18s,box-shadow .18s}
.exam-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(220,38,38,.12)}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<!-- HERO -->
<div class="px-4 pt-4 fu0">
  <div class="<?php echo $ttType==='exam'?'hero-exam':'hero-class';?> rounded-2xl p-5 text-white shadow-lg relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;"><?php echo $ttType==='exam'?'workspace_premium':'calendar_month';?></span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">
      <?php echo $ttType==='exam'?'Examination':'Weekly Schedule';?>
    </p>
    <h1 class="text-xl font-bold"><?php echo $ttType==='exam'?'Exam Timetable':'Class Timetable';?></h1>
    <p class="text-white/70 text-xs mt-1"><?php echo htmlspecialchars($filterBranch);?> &bull; Semester <?php echo $filterSem;?></p>
    <div class="flex gap-3 mt-3">
      <div class="bg-white/20 rounded-xl px-3 py-1.5 text-center">
        <p class="text-lg font-bold leading-none"><?php echo count($ttRows);?></p>
        <p class="text-[10px] text-white/70"><?php echo $ttType==='exam'?'Exams':'Slots';?></p>
      </div>
      <div class="bg-white/20 rounded-xl px-3 py-1.5 text-center">
        <p class="text-lg font-bold leading-none">Sem <?php echo $filterSem;?></p>
        <p class="text-[10px] text-white/70">Semester</p>
      </div>
    </div>
  </div>
</div>

<!-- TYPE TABS -->
<div class="px-4 pt-4 fu1 flex gap-2">
  <a href="?type=class&sem=<?php echo $filterSem;?>&branch=<?php echo urlencode($filterBranch);?>"
     class="tab-btn tab-class <?php echo $ttType==='class'?'active':'';?>">
    <span class="material-symbols-outlined text-sm align-middle">calendar_month</span> Class
  </a>
  <a href="?type=exam&sem=<?php echo $filterSem;?>&branch=<?php echo urlencode($filterBranch);?>"
     class="tab-btn tab-exam <?php echo $ttType==='exam'?'active':'';?>">
    <span class="material-symbols-outlined text-sm align-middle">workspace_premium</span> Exam
  </a>
</div>

<!-- FILTER -->
<div class="px-4 pt-3 fu2">
  <div class="card p-3">
    <div class="grid grid-cols-2 gap-3">
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Branch</label>
        <select class="cc-sel" onchange="location.href='?type=<?php echo $ttType;?>&sem=<?php echo $filterSem;?>&branch='+encodeURIComponent(this.value)">
          <?php foreach(['CO','IF','CE','ME','EE','EJ'] as $b): ?>
          <option value="<?php echo $b;?>" <?php echo $filterBranch===$b?'selected':'';?>><?php echo $b;?></option>
          <?php endforeach;?>
        </select>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Semester</label>
        <select class="cc-sel" onchange="location.href='?type=<?php echo $ttType;?>&sem='+this.value+'&branch=<?php echo urlencode($filterBranch);?>'">
          <?php for($s=1;$s<=6;$s++): ?>
          <option value="<?php echo $s;?>" <?php echo $filterSem==$s?'selected':'';?>>Semester <?php echo $s;?></option>
          <?php endfor;?>
        </select>
      </div>
    </div>
  </div>
</div>

<main class="px-4 pt-4 pb-28 space-y-3 fu3">

<?php if (empty($ttRows)): ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">calendar_month</span>
  <h3 class="font-bold text-slate-500 mt-3">No Timetable Found</h3>
  <p class="text-xs text-slate-400 mt-1">Timetable for this selection hasn't been added yet.</p>
</div>

<?php elseif ($ttType === 'class'): ?>

  <?php
  $today = date('l'); // e.g. Monday
  foreach ($days as $dayIdx => $day):
    if (empty($byDay[$day])) continue;
    $isToday = ($day === $today);
  ?>
  <div class="<?php echo $isToday?'today-row':''?> space-y-2">
    <div class="flex items-center gap-2">
      <div class="day-header flex items-center gap-1">
        <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">calendar_today</span>
        <?php echo $day;?>
      </div>
      <?php if ($isToday): ?>
      <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary text-white">Today</span>
      <?php endif;?>
    </div>
    <?php foreach ($byDay[$day] as $slotIdx => $slot):
      $color = $subjectColors[$slotIdx % count($subjectColors)];
      $st = date('h:i A', strtotime($slot['start_time']));
      $et = date('h:i A', strtotime($slot['end_time']));
    ?>
    <div class="card slot p-3" style="border-left-color:<?php echo $color;?>">
      <div class="flex items-center justify-between gap-2">
        <div class="flex-1 min-w-0">
          <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($slot['subject_name']);?></p>
          <?php if (!empty($slot['teacher_name'])): ?>
          <p class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">person</span><?php echo htmlspecialchars($slot['teacher_name']);?>
          </p>
          <?php endif;?>
        </div>
        <div class="text-right shrink-0">
          <p class="text-xs font-bold" style="color:<?php echo $color;?>"><?php echo $st;?></p>
          <p class="text-[10px] text-slate-400"><?php echo $et;?></p>
          <?php if (!empty($slot['room_no'])): ?>
          <p class="text-[10px] text-slate-400 mt-0.5">
            <span class="material-symbols-outlined text-xs align-middle">meeting_room</span><?php echo htmlspecialchars($slot['room_no']);?>
          </p>
          <?php endif;?>
        </div>
      </div>
    </div>
    <?php endforeach;?>
  </div>
  <?php endforeach;?>

<?php else: /* EXAM TIMETABLE */ ?>

  <?php
  $today = date('Y-m-d');
  $prevDate = null;
  foreach ($ttRows as $idx => $exam):
    $examDate  = $exam['exam_date'] ?? '';
    $dateLabel = $examDate ? date('D, d M Y', strtotime($examDate)) : 'Date TBD';
    $isPast    = $examDate && $examDate < $today;
    $isToday   = $examDate === $today;
    $daysLeft  = $examDate ? (int)round((strtotime($examDate) - time()) / 86400) : -999;
    $color     = $subjectColors[$idx % count($subjectColors)];
    $delay     = 0.05 * $idx;
    if ($examDate !== $prevDate):
      $prevDate = $examDate;
  ?>
  <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest pt-2 flex items-center gap-1">
    <span class="material-symbols-outlined text-xs" style="font-variation-settings:'FILL' 1;">event</span>
    <?php echo $dateLabel;?>
    <?php if ($isToday): ?><span class="bg-red-500 text-white rounded-full px-2 py-0.5 text-[9px]">TODAY</span><?php endif;?>
  </p>
  <?php endif;?>

  <div class="card exam-card p-4 border-l-4" style="border-left-color:<?php echo $color;?>;animation:fadeUp .4s <?php echo $delay;?>s ease both;<?php echo $isPast?'opacity:.6':'';?>">
    <div class="flex items-start justify-between gap-2">
      <div class="flex-1 min-w-0">
        <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($exam['subject_name']);?></p>
        <?php if (!empty($exam['subject_code'])): ?>
        <p class="text-[10px] text-slate-400 mt-0.5 font-semibold"><?php echo htmlspecialchars($exam['subject_code']);?></p>
        <?php endif;?>
        <div class="flex items-center gap-3 mt-2 flex-wrap">
          <?php $st=date('h:i A',strtotime($exam['start_time']??'11:00')); $et=date('h:i A',strtotime($exam['end_time']??'14:00'));?>
          <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">schedule</span><?php echo $st;?> â€“ <?php echo $et;?>
          </span>
          <?php if (!empty($exam['room_no'])): ?>
          <span class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">meeting_room</span><?php echo htmlspecialchars($exam['room_no']);?>
          </span>
          <?php endif;?>
        </div>
      </div>
      <div class="shrink-0 text-right">
        <?php if ($isPast): ?>
        <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-400">Done</span>
        <?php elseif ($isToday): ?>
        <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-red-50 text-red-600">Today!</span>
        <?php elseif ($daysLeft >= 0): ?>
        <span class="text-[10px] font-bold px-2 py-1 rounded-lg <?php echo $daysLeft<=3?'bg-red-50 text-red-600':($daysLeft<=7?'bg-yellow-50 text-yellow-700':'bg-green-50 text-green-700');?>">
          <?php echo $daysLeft===0?'Today':$daysLeft.' days';?>
        </span>
        <?php endif;?>
      </div>
    </div>
  </div>
  <?php endforeach;?>

<?php endif;?>

</main>

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
</body>
</html>
