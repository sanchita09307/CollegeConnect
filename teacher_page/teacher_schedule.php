<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$teacherId = (int)($teacher['id'] ?? 0);
$dept      = $teacher['department'] ?? $teacher['branch_code'] ?? 'CO';

// Fetch this teacher's weekly class schedule
$schedule = [];
$sql = "SELECT t.*, s.subject_name, s.subject_code, s.subject_short, s.branch_code, s.semester
        FROM timetables t
        JOIN subjects s ON t.subject_id = s.id
        WHERE t.teacher_id = ? AND t.timetable_type = 'class'
        ORDER BY FIELD(t.day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $schedule[] = $row;
    $stmt->close();
}

// Fetch exam schedule for teacher's subjects
$examSchedule = [];
$sql2 = "SELECT t.*, s.subject_name, s.subject_code, s.branch_code, s.semester
         FROM timetables t
         JOIN subjects s ON t.subject_id = s.id
         WHERE t.teacher_id = ? AND t.timetable_type = 'exam'
         ORDER BY t.exam_date ASC, t.start_time ASC";
$stmt2 = $conn->prepare($sql2);
if ($stmt2) {
    $stmt2->bind_param("i", $teacherId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) $examSchedule[] = $row;
    $stmt2->close();
}

$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$byDay = [];
foreach ($days as $d) $byDay[$d] = [];
foreach ($schedule as $r) $byDay[$r['day_name'] ?? 'Monday'][] = $r;

$totalSlots = count($schedule);
$today = date('l');

$subjectColors = ['#4349cf','#7c3aed','#0891b2','#16a34a','#ca8a04','#ea580c','#dc2626','#2563eb'];
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>My Schedule â€“ CollegeConnect</title>
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
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.tab-btn{padding:7px 18px;border-radius:9999px;font-size:12px;font-weight:700;border:2px solid transparent;transition:all .18s;cursor:pointer;flex:1}
.tab-btn.active{color:white}
.tab-class.active{background:#4349cf;border-color:#4349cf}
.tab-exam.active{background:#dc2626;border-color:#dc2626}
.tab-btn:not(.active){background:white;color:#64748b;border-color:#e2e8f0}
.dark .tab-btn:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45}
.slot{border-radius:.875rem;padding:10px 12px;border-left:4px solid transparent;transition:transform .15s,box-shadow .15s}
.slot:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.08)}
.day-header{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:6px 14px;background:#eef0ff;color:#4349cf;border-radius:.5rem;display:inline-flex;align-items:center;gap:5px}
.dark .day-header{background:#1a1b2e}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "My Schedule";
$activePage = "schedule";
include __DIR__ . '/teacher_topbar.php';
?>

<!-- HERO -->
<div class="px-4 pt-4 fu0">
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/30 relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">calendar_month</span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Teaching</p>
    <h1 class="text-xl font-bold">My Schedule</h1>
    <p class="text-white/70 text-xs mt-1"><?php echo htmlspecialchars($teacher['name'] ?? '');?> &bull; <?php echo htmlspecialchars($dept);?></p>
    <div class="flex gap-3 mt-3">
      <div class="bg-white/20 rounded-xl px-3 py-1.5 text-center">
        <p class="text-lg font-bold leading-none"><?php echo $totalSlots;?></p>
        <p class="text-[10px] text-white/70">Weekly Slots</p>
      </div>
      <div class="bg-white/20 rounded-xl px-3 py-1.5 text-center">
        <p class="text-lg font-bold leading-none"><?php echo count($examSchedule);?></p>
        <p class="text-[10px] text-white/70">Upcoming Exams</p>
      </div>
    </div>
  </div>
</div>

<!-- TABS -->
<div class="px-4 pt-4 fu1 flex gap-2">
  <button onclick="showTab('class')" id="tab-class" class="tab-btn tab-class active">
    <span class="material-symbols-outlined text-sm align-middle">calendar_month</span> Class Schedule
  </button>
  <button onclick="showTab('exam')" id="tab-exam" class="tab-btn tab-exam">
    <span class="material-symbols-outlined text-sm align-middle">workspace_premium</span> Exam Duties
  </button>
</div>

<main class="px-4 pt-4 pb-28 space-y-3">

<!-- CLASS SCHEDULE -->
<div id="section-class">
<?php if (!empty($schedule)):
  foreach ($days as $dayIdx => $day):
    if (empty($byDay[$day])) continue;
    $isToday = ($day === $today);
    $delay = 0.05 * $dayIdx;
?>
<div class="space-y-2" style="animation:fadeUp .4s <?php echo $delay;?>s ease both">
  <div class="flex items-center gap-2">
    <div class="day-header">
      <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">calendar_today</span>
      <?php echo $day;?>
    </div>
    <?php if ($isToday): ?>
    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-primary text-white">Today</span>
    <?php endif;?>
  </div>
  <?php foreach ($byDay[$day] as $si => $slot):
    $color = $subjectColors[$si % count($subjectColors)];
    $st = date('h:i A', strtotime($slot['start_time']));
    $et = date('h:i A', strtotime($slot['end_time']));
  ?>
  <div class="card slot" style="border-left-color:<?php echo $color;?>">
    <div class="flex items-center justify-between gap-2">
      <div class="flex-1 min-w-0">
        <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($slot['subject_name']);?></p>
        <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400">
          <?php if (!empty($slot['subject_code'])): ?>
          <span class="font-semibold"><?php echo htmlspecialchars($slot['subject_code']);?></span>
          <span>&bull;</span>
          <?php endif;?>
          <span><?php echo htmlspecialchars($slot['branch_code']);?> Sem <?php echo $slot['semester'];?></span>
        </div>
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
<?php endforeach;
else: ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">calendar_month</span>
  <h3 class="font-bold text-slate-500 mt-3">No Class Schedule Found</h3>
  <p class="text-xs text-slate-400 mt-1">Contact admin to assign your timetable slots.</p>
</div>
<?php endif;?>
</div>

<!-- EXAM DUTIES -->
<div id="section-exam" class="hidden space-y-3">
<?php if (!empty($examSchedule)):
  $today2 = date('Y-m-d');
  $prevDate = null;
  foreach ($examSchedule as $idx => $exam):
    $examDate  = $exam['exam_date'] ?? '';
    $dateLabel = $examDate ? date('D, d M Y', strtotime($examDate)) : 'TBD';
    $isPast    = $examDate && $examDate < $today2;
    $isToday   = $examDate === $today2;
    $daysLeft  = $examDate ? (int)round((strtotime($examDate) - time()) / 86400) : -999;
    $color     = $subjectColors[$idx % count($subjectColors)];
    $delay     = 0.05 * $idx;
    if ($examDate !== $prevDate): $prevDate = $examDate;
?>
  <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest pt-2 flex items-center gap-1">
    <span class="material-symbols-outlined text-xs" style="font-variation-settings:'FILL' 1;">event</span>
    <?php echo $dateLabel;?>
    <?php if ($isToday): ?><span class="bg-red-500 text-white rounded-full px-2 py-0.5 text-[9px]">TODAY</span><?php endif;?>
  </p>
<?php endif;?>
  <div class="card p-4 border-l-4" style="border-left-color:<?php echo $color;?>;animation:fadeUp .4s <?php echo $delay;?>s ease both;<?php echo $isPast?'opacity:.6':'';?>">
    <div class="flex items-center justify-between gap-2">
      <div class="flex-1">
        <p class="font-bold text-sm"><?php echo htmlspecialchars($exam['subject_name']);?></p>
        <p class="text-[11px] text-slate-400 mt-0.5">
          <?php echo htmlspecialchars($exam['branch_code']);?> &bull; Sem <?php echo $exam['semester'];?>
          <?php if (!empty($exam['subject_code'])): ?>&bull; <?php echo htmlspecialchars($exam['subject_code']);?><?php endif;?>
        </p>
        <div class="flex gap-3 mt-2 text-[11px] text-slate-500">
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">schedule</span>
            <?php echo date('h:i A',strtotime($exam['start_time']??'11:00'));?> â€“ <?php echo date('h:i A',strtotime($exam['end_time']??'14:00'));?>
          </span>
          <?php if (!empty($exam['room_no'])): ?>
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-xs">meeting_room</span>
            <?php echo htmlspecialchars($exam['room_no']);?>
          </span>
          <?php endif;?>
        </div>
      </div>
      <?php if (!$isPast && $daysLeft >= 0): ?>
      <span class="text-[10px] font-bold px-2 py-1 rounded-lg shrink-0 <?php echo $daysLeft<=3?'bg-red-50 text-red-600':($daysLeft<=7?'bg-yellow-50 text-yellow-700':'bg-green-50 text-green-700');?>">
        <?php echo $daysLeft===0?'Today':$daysLeft.' days';?>
      </span>
      <?php elseif ($isPast): ?>
      <span class="text-[10px] font-bold px-2 py-1 rounded-lg bg-slate-100 text-slate-400">Done</span>
      <?php endif;?>
    </div>
  </div>
<?php endforeach;
else: ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">workspace_premium</span>
  <h3 class="font-bold text-slate-500 mt-3">No Exam Duties</h3>
  <p class="text-xs text-slate-400 mt-1">No exam duties assigned yet.</p>
</div>
<?php endif;?>
</div>

</main>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="teacher_dashboard.php"  class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span></a>
    <a href="teacher_classes.php"    class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">menu_book</span><span class="text-[10px]">Classes</span></a>
    <a href="teacher_attendence.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">assignment_turned_in</span><span class="text-[10px]">Attend.</span></a>
    <a href="teacher_message.php"    class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="teacher_profile.php"    class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span></a>
  </div>
</nav>

<script>
function showTab(tab) {
  ['class','exam'].forEach(t => {
    document.getElementById('section-'+t).classList.toggle('hidden', t!==tab);
    const btn = document.getElementById('tab-'+t);
    btn.classList.toggle('active', t===tab);
  });
}
</script>
</body>
</html>
