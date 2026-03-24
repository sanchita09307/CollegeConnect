<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$teacherId  = (int)($teacher['id'] ?? 0);
$branchCode = $conn->real_escape_string($teacher['branch_code'] ?? $teacher['department'] ?? '');

// â??â?? Filters â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
$filterSem   = isset($_GET['sem'])    && $_GET['sem']   !== 'all' ? (int)$_GET['sem']  : 0;
$filterGroup = isset($_GET['group'])  && $_GET['group'] !== 'all' ? $conn->real_escape_string($_GET['group']) : '';
$searchQ     = trim($_GET['q'] ?? '');

// â??â?? Build student query â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
// Fetch real approved students matching teacher's branch/department
// Also calculate attendance % per student from attendance table
$whereClauses = ["(s.department = '$branchCode' OR s.branch_code = '$branchCode')", "s.status = 'approved'"];
if ($filterSem)   $whereClauses[] = "s.semester = $filterSem";
if ($filterGroup) $whereClauses[] = "s.batch_group = '$filterGroup'";
if ($searchQ) {
    $sq = $conn->real_escape_string($searchQ);
    $whereClauses[] = "(s.full_name LIKE '%$sq%' OR s.student_roll_no LIKE '%$sq%' OR s.enrollment_no LIKE '%$sq%')";
}
$where = implode(' AND ', $whereClauses);

$sql = "
    SELECT
        s.id,
        s.full_name,
        s.email,
        s.phone,
        s.semester,
        s.student_roll_no,
        s.enrollment_no,
        s.batch_group,
        s.gender,
        s.profile_photo,
        s.last_login_at,
        s.department,
        s.branch_code,
        /* Attendance % - count present+late as attended */
        COALESCE(
            ROUND(
                SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END)
                / NULLIF(COUNT(a.id), 0) * 100
            , 0)
        , -1) AS att_pct,
        COUNT(a.id) AS att_total,
        /* Latest result - overall avg percentage */
        COALESCE(ROUND(AVG(r.percentage), 1), -1) AS avg_marks
    FROM students s
    LEFT JOIN attendance a ON a.student_id = s.id
    LEFT JOIN results    r ON r.student_id = s.id
    WHERE $where
    GROUP BY s.id
    ORDER BY s.student_roll_no ASC, s.full_name ASC
";

$result = $conn->query($sql);
$students = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

// â??â?? Distinct semesters & groups for filter tabs â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
$semRes   = $conn->query("SELECT DISTINCT semester FROM students WHERE (department='$branchCode' OR branch_code='$branchCode') AND status='approved' ORDER BY semester ASC");
$semesters = [];
if ($semRes) while ($r = $semRes->fetch_assoc()) $semesters[] = (int)$r['semester'];

$grpRes   = $conn->query("SELECT DISTINCT batch_group FROM students WHERE (department='$branchCode' OR branch_code='$branchCode') AND status='approved' AND batch_group IS NOT NULL AND batch_group != '' ORDER BY batch_group ASC");
$groups = [];
if ($grpRes) while ($r = $grpRes->fetch_assoc()) $groups[] = $r['batch_group'];

// â??â?? Stats â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
$totalCount = count($students);
$lowCount   = 0;
$totalAtt   = 0;
$attCounted = 0;
foreach ($students as $s) {
    if ($s['att_pct'] >= 0) { $totalAtt += $s['att_pct']; $attCounted++; }
    if ($s['att_pct'] >= 0 && $s['att_pct'] < 75) $lowCount++;
}
$avgAtt = $attCounted > 0 ? round($totalAtt / $attCounted) : 0;

// â??â?? Avatar color helper â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$avClasses = ['av-1','av-2','av-3','av-4','av-5','av-6','av-7','av-8'];
function avatarClass($index) {
    global $avClasses;
    return $avClasses[$index % count($avClasses)];
}
function initials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $init  = '';
    foreach (array_slice($parts, 0, 2) as $w) $init .= strtoupper(substr($w, 0, 1));
    return $init;
}
function attColor($pct) {
    if ($pct < 0)  return '#94a3b8';  // no data
    if ($pct >= 75) return '#22c55e';
    if ($pct >= 60) return '#f97316';
    return '#ef4444';
}
function attLabel($pct) {
    if ($pct < 0)   return ['badge-gray','No Data'];
    if ($pct >= 75) return ['badge-green','Good'];
    if ($pct >= 60) return ['badge-orange','Low'];
    return ['badge-red','âš ï¸ Alert'];
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Student Directory â€“ CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode: "class",
  theme: { extend: { colors: { primary: "#4349cf" }, fontFamily: { display: ["Outfit"] } } }
}
</script>
<style>
*{font-family:'Outfit',sans-serif;}
:root{--primary:#4349cf;--grad:linear-gradient(135deg,#4349cf,#7479f5);}
body{min-height:100dvh;background:#f0f1ff;}
.dark body{background:#0d0e1c;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.92)}to{opacity:1;transform:scale(1)}}
.fu{animation:fadeUp .42s ease both}
.fu1{animation:fadeUp .42s .07s ease both}
.fu2{animation:fadeUp .42s .14s ease both}
.fu3{animation:fadeUp .42s .21s ease both}

.s-card{
  background:white;border-radius:20px;border:1px solid #eef0ff;
  box-shadow:0 2px 12px rgba(67,73,207,.05);
  transition:all .22s;overflow:hidden;position:relative;
}
.dark .s-card{background:#1a1b2e;border-color:#2a2b45;}
.s-card:hover{transform:translateY(-4px);box-shadow:0 14px 36px rgba(67,73,207,.16);}
.s-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad);opacity:0;transition:opacity .22s;}
.s-card:hover::before{opacity:1;}

.p-row{
  display:flex;align-items:center;gap:12px;padding:14px;background:white;
  border-radius:18px;border:1px solid #eef0ff;
  box-shadow:0 1px 6px rgba(67,73,207,.04);
  transition:all .2s;cursor:pointer;
}
.dark .p-row{background:#1a1b2e;border-color:#2a2b45;}
.p-row:hover{box-shadow:0 6px 20px rgba(67,73,207,.12);transform:translateX(4px);}

.av-1{background:linear-gradient(135deg,#4349cf,#7479f5)}
.av-2{background:linear-gradient(135deg,#7c3aed,#a78bfa)}
.av-3{background:linear-gradient(135deg,#0891b2,#38bdf8)}
.av-4{background:linear-gradient(135deg,#059669,#34d399)}
.av-5{background:linear-gradient(135deg,#db2777,#f472b6)}
.av-6{background:linear-gradient(135deg,#d97706,#fbbf24)}
.av-7{background:linear-gradient(135deg,#dc2626,#f87171)}
.av-8{background:linear-gradient(135deg,#7c3aed,#c084fc)}

.badge{padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;}
.badge-green{background:#dcfce7;color:#16a34a;}
.badge-orange{background:#ffedd5;color:#ea580c;}
.badge-red{background:#fee2e2;color:#dc2626;}
.badge-gray{background:#f1f5f9;color:#94a3b8;}
.dark .badge-green{background:#052e16;color:#86efac;}
.dark .badge-orange{background:#431407;color:#fdba74;}
.dark .badge-red{background:#450a0a;color:#fca5a5;}
.dark .badge-gray{background:#1e293b;color:#64748b;}

.search-field{background:white;border:1.5px solid #e8eaf6;border-radius:16px;transition:border .2s,box-shadow .2s;}
.dark .search-field{background:#1a1b2e;border-color:#2a2b45;}
.search-field:focus-within{border-color:var(--primary);box-shadow:0 0 0 4px rgba(67,73,207,.1);}

.tag-chip{padding:6px 14px;border-radius:99px;font-size:11px;font-weight:600;border:1.5px solid transparent;cursor:pointer;transition:all .18s;white-space:nowrap;}
.tag-chip.active{background:var(--primary);color:white;border-color:var(--primary);}
.tag-chip:not(.active){background:white;color:#64748b;border-color:#e2e8f0;}
.dark .tag-chip:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45;}

.att-bar{height:6px;border-radius:99px;background:#e8eaf6;overflow:hidden;}
.dark .att-bar{background:#1e2040;}
.att-fill{height:100%;border-radius:99px;transition:width .6s ease;}

/* Profile bottom sheet */
#profileModal{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.55);backdrop-filter:blur(8px);display:none;align-items:flex-end;}
#profileModal.open{display:flex;}
#profilePanel{background:white;border-radius:28px 28px 0 0;width:100%;max-height:92vh;overflow-y:auto;transform:translateY(100%);transition:transform .38s cubic-bezier(.22,1,.36,1);}
.dark #profilePanel{background:#13142a;}
#profileModal.open #profilePanel{transform:translateY(0);}

.toast{position:fixed;top:72px;left:50%;transform:translateX(-50%);z-index:9999;animation:fadeUp .3s ease both;background:white;box-shadow:0 8px 28px rgba(0,0,0,.14);border-radius:16px;padding:10px 18px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;white-space:nowrap;}
.dark .toast{background:#1e2040;color:white;}

::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-thumb{background:#c7d0ff;border-radius:4px}

.empty-state{text-align:center;padding:56px 24px;}
.empty-state .ms{font-size:56px;color:#cbd5e1;}
</style>
</head>
<body class="bg-[#f0f1ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "Student Directory";
$activePage = "students";
include __DIR__ . '/teacher_topbar.php';
?>

<!-- HERO BANNER -->
<div class="px-4 pt-4 fu">
  <div class="rounded-3xl p-5 text-white relative overflow-hidden shadow-xl shadow-indigo-300/30"
       style="background:linear-gradient(135deg,#3730a3 0%,#4349cf 45%,#7479f5 100%)">
    <div class="absolute -right-4 -top-4 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:160px;font-variation-settings:'FILL' 1;">school</span>
    </div>
    <div class="relative z-10">
      <p class="text-white/60 text-[11px] font-bold uppercase tracking-widest">Department</p>
      <h1 class="text-2xl font-bold mt-0.5">Student Directory</h1>
      <p class="text-white/70 text-xs mt-1"><?php echo htmlspecialchars($teacher['department'] ?? $branchCode); ?></p>
      <div class="flex gap-3 mt-4 flex-wrap">
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl"><?php echo $totalCount; ?></p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Total</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl text-green-300"><?php echo $totalCount - $lowCount; ?></p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Regular</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl text-yellow-300"><?php echo $avgAtt > 0 ? $avgAtt.'%' : 'N/A'; ?></p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Avg Att.</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl text-red-300"><?php echo $lowCount; ?></p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Low Att.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<main class="px-4 py-4 pb-28 space-y-4">

<!-- SEARCH + FILTERS -->
<div class="fu1 space-y-3">

  <!-- Search bar with GET form -->
  <form method="GET" id="filterForm">
    <input type="hidden" name="sem"   id="hidSem"   value="<?php echo $filterSem ?: 'all'; ?>"/>
    <input type="hidden" name="group" id="hidGroup" value="<?php echo htmlspecialchars($filterGroup ?: 'all'); ?>"/>
    <div class="search-field flex items-center gap-2 px-4 py-3">
      <span class="material-symbols-outlined text-slate-400">search</span>
      <input type="text" name="q" id="searchInput"
             value="<?php echo htmlspecialchars($searchQ); ?>"
             placeholder="Search by name, roll no, enrollment noâ€¦"
             class="flex-1 bg-transparent text-sm border-none focus:ring-0 placeholder:text-slate-400"
             oninput="liveSearch(this.value)"/>
      <?php if($searchQ): ?>
      <a href="teacher_students.php" class="text-slate-300 hover:text-slate-500">
        <span class="material-symbols-outlined text-lg">close</span>
      </a>
      <?php endif; ?>
    </div>
  </form>

  <!-- Semester filter chips -->
  <div class="flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none;scrollbar-width:none">
    <button class="tag-chip <?php echo !$filterSem ? 'active' : ''; ?>" onclick="setSem('all',this)">All Sems</button>
    <?php foreach($semesters as $s): ?>
    <button class="tag-chip <?php echo $filterSem == $s ? 'active' : ''; ?>" onclick="setSem('<?php echo $s; ?>',this)">Sem <?php echo $s; ?></button>
    <?php endforeach; ?>
    <?php if(empty($semesters)): // fallback ?>
    <?php foreach(range(1,6) as $s): ?>
    <button class="tag-chip <?php echo $filterSem == $s ? 'active' : ''; ?>" onclick="setSem('<?php echo $s; ?>',this)">Sem <?php echo $s; ?></button>
    <?php endforeach; ?>
    <?php endif; ?>
    <button class="tag-chip <?php echo $filterSem === 0 && isset($_GET['low']) ? 'active' : ''; ?>"
            onclick="filterLow(this)">âš ï¸ Low Att.</button>
  </div>

  <!-- Group filter if groups exist -->
  <?php if(!empty($groups)): ?>
  <div class="flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none;scrollbar-width:none">
    <button class="tag-chip <?php echo !$filterGroup ? 'active' : ''; ?>" onclick="setGroup('all',this)">All Groups</button>
    <?php foreach($groups as $g): ?>
    <button class="tag-chip <?php echo $filterGroup === $g ? 'active' : ''; ?>" onclick="setGroup('<?php echo htmlspecialchars($g); ?>',this)"><?php echo htmlspecialchars($g); ?></button>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- SORT + VIEW TOGGLE -->
<div class="flex items-center justify-between fu2">
  <span class="text-sm font-bold text-slate-600 dark:text-slate-300">
    <?php echo $totalCount; ?> student<?php echo $totalCount !== 1 ? 's' : ''; ?>
    <?php if($filterSem): echo " Â· Sem $filterSem"; endif; ?>
    <?php if($filterGroup): echo " Â· $filterGroup"; endif; ?>
  </span>
  <div class="flex gap-1">
    <button id="gridBtn" onclick="setView('grid')" class="p-2 rounded-xl bg-primary text-white transition">
      <span class="material-symbols-outlined text-sm">grid_view</span>
    </button>
    <button id="listBtn" onclick="setView('list')" class="p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-400 transition">
      <span class="material-symbols-outlined text-sm">view_list</span>
    </button>
  </div>
</div>

<?php if(empty($students)): ?>
<!-- EMPTY STATE -->
<div class="empty-state fu2">
  <span class="material-symbols-outlined ms">manage_search</span>
  <p class="font-bold text-slate-500 mt-3">No students found</p>
  <p class="text-sm text-slate-400 mt-1">
    <?php if($searchQ || $filterSem || $filterGroup): ?>
    Try clearing the filters.
    <a href="teacher_students.php" class="text-primary font-bold block mt-2">Clear filters</a>
    <?php else: ?>
    No approved students in your department yet.
    <?php endif; ?>
  </p>
</div>

<?php else: ?>

<!-- GRID VIEW -->
<div id="gridView" class="grid grid-cols-2 gap-3 fu3">
<?php foreach($students as $i => $s):
    $av     = avatarClass($i);
    $init   = initials($s['full_name']);
    $att    = (int)$s['att_pct'];
    $hasAtt = $s['att_pct'] >= 0;
    $aC     = attColor($hasAtt ? $att : -1);
    [$aLabel, $aText] = attLabel($hasAtt ? $att : -1);
    $sem    = (int)$s['semester'];
    $roll   = $s['student_roll_no'] ?: ($s['enrollment_no'] ?: 'â€”');
    $avgM   = $s['avg_marks'] >= 0 ? $s['avg_marks'] : null;
    $isLow  = $hasAtt && $att < 75;
    $delay  = 0.04 + $i * 0.04;
    // Escape for JS
    $jName  = addslashes($s['full_name']);
    $jRoll  = addslashes($roll);
    $jEmail = addslashes($s['email'] ?? '');
    $jPhone = addslashes($s['phone'] ?? '');
    $jGroup = addslashes($s['batch_group'] ?? '');
?>
<div class="s-card p-4 cursor-pointer"
     data-name="<?php echo strtolower(htmlspecialchars($s['full_name'].' '.$roll)); ?>"
     data-sem="<?php echo $sem; ?>"
     data-att="<?php echo $hasAtt ? $att : -1; ?>"
     data-group="<?php echo strtolower(htmlspecialchars($s['batch_group'] ?? '')); ?>"
     style="animation:scaleIn .35s <?php echo $delay; ?>s ease both;opacity:0;animation-fill-mode:both"
     onclick="openProfile(<?php echo (int)$s['id']; ?>,'<?php echo $jName; ?>','<?php echo $init; ?>','<?php echo $av; ?>','<?php echo $jRoll; ?>',<?php echo $sem; ?>,<?php echo $hasAtt ? $att : -1; ?>,<?php echo $avgM !== null ? $avgM : 'null'; ?>,'<?php echo $jEmail; ?>','<?php echo $jPhone; ?>','<?php echo $jGroup; ?>')">

  <?php if($isLow): ?>
  <div class="absolute top-3 right-3 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center" title="Low attendance">
    <span class="material-symbols-outlined text-white" style="font-size:12px;">warning</span>
  </div>
  <?php endif; ?>

  <!-- Avatar -->
  <div class="w-12 h-12 rounded-2xl <?php echo $av; ?> flex items-center justify-center text-white font-bold text-base mb-3 shadow-md">
    <?php echo $init; ?>
  </div>

  <!-- Name + Roll -->
  <p class="font-bold text-sm leading-tight line-clamp-1"><?php echo htmlspecialchars($s['full_name']); ?></p>
  <p class="text-[10px] text-primary font-semibold mb-1"><?php echo htmlspecialchars($roll); ?></p>

  <!-- Sem + Group -->
  <div class="flex items-center gap-1 mb-2">
    <span class="text-[10px] text-slate-400">Sem <?php echo $sem; ?></span>
    <?php if(!empty($s['batch_group'])): ?>
    <span class="text-[10px] text-slate-300">â€¢</span>
    <span class="text-[10px] text-slate-400"><?php echo htmlspecialchars($s['batch_group']); ?></span>
    <?php endif; ?>
  </div>

  <!-- Attendance bar -->
  <?php if($hasAtt): ?>
  <div class="flex items-center justify-between text-[10px] mb-1.5">
    <span class="text-slate-400">Attendance</span>
    <span class="font-bold" style="color:<?php echo $aC; ?>"><?php echo $att; ?>%</span>
  </div>
  <div class="att-bar mb-2"><div class="att-fill" style="width:<?php echo $att; ?>%;background:<?php echo $aC; ?>"></div></div>
  <?php else: ?>
  <p class="text-[10px] text-slate-400 mb-2">No attendance data yet</p>
  <?php endif; ?>

  <!-- Badge + Marks -->
  <div class="flex items-center justify-between">
    <span class="badge <?php echo $aLabel; ?>"><?php echo $aText; ?></span>
    <?php if($avgM !== null): ?>
    <span class="text-[10px] font-bold text-slate-500"><?php echo $avgM; ?>%</span>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
</div>

<!-- LIST VIEW -->
<div id="listView" class="hidden space-y-2 fu3">
<?php foreach($students as $i => $s):
    $av     = avatarClass($i);
    $init   = initials($s['full_name']);
    $att    = (int)$s['att_pct'];
    $hasAtt = $s['att_pct'] >= 0;
    $aC     = attColor($hasAtt ? $att : -1);
    $sem    = (int)$s['semester'];
    $roll   = $s['student_roll_no'] ?: ($s['enrollment_no'] ?: 'â€”');
    $avgM   = $s['avg_marks'] >= 0 ? $s['avg_marks'] : null;
    $isLow  = $hasAtt && $att < 75;
    $delay  = 0.03 + $i * 0.03;
    $jName  = addslashes($s['full_name']);
    $jRoll  = addslashes($roll);
    $jEmail = addslashes($s['email'] ?? '');
    $jPhone = addslashes($s['phone'] ?? '');
    $jGroup = addslashes($s['batch_group'] ?? '');
?>
<div class="p-row"
     data-name="<?php echo strtolower(htmlspecialchars($s['full_name'].' '.$roll)); ?>"
     data-sem="<?php echo $sem; ?>"
     data-att="<?php echo $hasAtt ? $att : -1; ?>"
     data-group="<?php echo strtolower(htmlspecialchars($s['batch_group'] ?? '')); ?>"
     style="animation:fadeUp .35s <?php echo $delay; ?>s ease both;opacity:0;animation-fill-mode:both"
     onclick="openProfile(<?php echo (int)$s['id']; ?>,'<?php echo $jName; ?>','<?php echo $init; ?>','<?php echo $av; ?>','<?php echo $jRoll; ?>',<?php echo $sem; ?>,<?php echo $hasAtt ? $att : -1; ?>,<?php echo $avgM !== null ? $avgM : 'null'; ?>,'<?php echo $jEmail; ?>','<?php echo $jPhone; ?>','<?php echo $jGroup; ?>')">

  <div class="w-11 h-11 rounded-2xl <?php echo $av; ?> flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm relative">
    <?php echo $init; ?>
    <?php if($isLow): ?><span class="absolute -top-1 -right-1 w-3.5 h-3.5 bg-red-500 rounded-full border-2 border-white"></span><?php endif; ?>
  </div>

  <div class="flex-1 min-w-0">
    <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($s['full_name']); ?></p>
    <p class="text-[10px] text-primary font-semibold">
      <?php echo htmlspecialchars($roll); ?>
      â€¢ Sem <?php echo $sem; ?>
      <?php if(!empty($s['batch_group'])): echo ' â€¢ '.htmlspecialchars($s['batch_group']); endif; ?>
    </p>
  </div>

  <div class="flex flex-col items-end gap-1 shrink-0">
    <?php if($hasAtt): ?>
    <span class="font-bold text-sm" style="color:<?php echo $aC; ?>"><?php echo $att; ?>%</span>
    <?php else: ?>
    <span class="text-[11px] text-slate-400">â€”</span>
    <?php endif; ?>
    <?php if($avgM !== null): ?>
    <span class="text-[10px] text-slate-400"><?php echo $avgM; ?>% marks</span>
    <?php endif; ?>
  </div>

  <span class="material-symbols-outlined text-slate-300 text-lg shrink-0">chevron_right</span>
</div>
<?php endforeach; ?>
</div>

<?php endif; // end empty check ?>

</main>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="teacher_dashboard.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px] font-medium">Home</span></a>
    <a href="teacher_students.php" class="flex flex-col items-center gap-0.5 text-primary px-4 py-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">groups</span><span class="text-[10px] font-bold">Students</span></a>
    <a href="teacher_ai_atrisk.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">crisis_alert</span><span class="text-[10px] font-medium">AI Risk</span></a>
    <a href="teacher_profile.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px] font-medium">Profile</span></a>
  </div>
</nav>

<!-- PROFILE BOTTOM SHEET -->
<div id="profileModal" onclick="if(event.target===this)closeProfile()">
  <div id="profilePanel">
    <div class="flex justify-center pt-4 pb-2">
      <div class="w-10 h-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div>
    </div>
    <div class="px-5 pb-10">

      <!-- Header -->
      <div class="flex items-start gap-4 mb-5">
        <div id="profileAvatar" class="w-16 h-16 rounded-3xl flex items-center justify-center text-white font-bold text-xl shadow-lg shrink-0"></div>
        <div class="flex-1 min-w-0">
          <h2 id="profileName"  class="font-bold text-xl leading-tight"></h2>
          <p  id="profileRoll"  class="text-primary text-sm font-semibold mt-0.5"></p>
          <p  id="profileMeta"  class="text-slate-400 text-xs mt-0.5"></p>
        </div>
        <button onclick="closeProfile()" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0">
          <span class="material-symbols-outlined text-slate-400">close</span>
        </button>
      </div>

      <!-- Stats row -->
      <div class="grid grid-cols-3 gap-3 mb-5">
        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-3 text-center">
          <p id="profileAtt"   class="font-bold text-xl"></p>
          <p class="text-[10px] text-slate-400 font-semibold">Attendance</p>
        </div>
        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-3 text-center">
          <p id="profileMarks" class="font-bold text-xl text-primary"></p>
          <p class="text-[10px] text-slate-400 font-semibold">Avg Marks</p>
        </div>
        <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-3 text-center">
          <p id="profileSemBadge" class="font-bold text-xl text-orange-500"></p>
          <p class="text-[10px] text-slate-400 font-semibold">Semester</p>
        </div>
      </div>

      <!-- Attendance progress bar -->
      <div id="attSection" class="mb-5">
        <div class="flex justify-between text-xs font-semibold mb-1.5">
          <span class="text-slate-500">Attendance Progress</span>
          <span id="profileAttLabel" class="text-primary"></span>
        </div>
        <div class="att-bar h-3 rounded-full">
          <div id="profileAttBar" class="att-fill"></div>
        </div>
        <div class="flex justify-between text-[10px] text-slate-400 mt-1">
          <span>0%</span><span class="text-orange-500 font-semibold">Min 75%</span><span>100%</span>
        </div>
      </div>

      <!-- Contact info -->
      <div id="contactSection" class="mb-5 space-y-2">
        <h3 class="font-bold text-sm text-slate-700 dark:text-slate-300">Contact</h3>
        <div id="profileEmail" class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl text-sm hidden">
          <span class="material-symbols-outlined text-primary" style="font-size:18px;font-variation-settings:'FILL' 1;">mail</span>
          <span id="profileEmailText" class="text-slate-600 dark:text-slate-300 text-xs truncate"></span>
        </div>
        <div id="profilePhone" class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl text-sm hidden">
          <span class="material-symbols-outlined text-green-500" style="font-size:18px;font-variation-settings:'FILL' 1;">call</span>
          <span id="profilePhoneText" class="text-slate-600 dark:text-slate-300 text-xs"></span>
        </div>
      </div>

      <!-- Action buttons -->
      <div class="grid grid-cols-2 gap-3 mb-5">
        <a id="msgBtn" href="#" class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-primary/10 text-primary font-bold text-sm hover:bg-primary/20 transition active:scale-95 no-underline">
          <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">chat</span>Message
        </a>
        <a id="attBtn" href="teacher_attendence.php" class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-green-50 dark:bg-green-900/20 text-green-600 font-bold text-sm hover:bg-green-100 transition active:scale-95 no-underline">
          <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">how_to_reg</span>Attendance
        </a>
        <a id="marksBtn" href="teacher_marks.php" class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-orange-50 dark:bg-orange-900/20 text-orange-600 font-bold text-sm hover:bg-orange-100 transition active:scale-95 no-underline">
          <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">grade</span>Enter Marks
        </a>
        <button onclick="closeProfile();showToast('Coming soon!','info','text-purple-500')" class="flex items-center justify-center gap-2 py-3 rounded-2xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 font-bold text-sm hover:bg-purple-100 transition active:scale-95">
          <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">history</span>History
        </button>
      </div>

    </div>
  </div>
</div>

<script>
let currentView = 'grid';
let lowFilter   = false;

// â”€â”€ View toggle â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
function setView(v) {
  currentView = v;
  document.getElementById('gridView').classList.toggle('hidden', v === 'list');
  document.getElementById('listView').classList.toggle('hidden', v === 'grid');
  document.getElementById('gridBtn').className = v === 'grid'
    ? 'p-2 rounded-xl bg-primary text-white transition'
    : 'p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-400 transition';
  document.getElementById('listBtn').className = v === 'list'
    ? 'p-2 rounded-xl bg-primary text-white transition'
    : 'p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-400 transition';
}

// â??â?? Semester filter (server-side redirect) â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
function setSem(val, btn) {
  document.querySelectorAll('.tag-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('hidSem').value = val;
  lowFilter = false;
  document.getElementById('filterForm').submit();
}

function setGroup(val, btn) {
  // highlight only group chips
  btn.closest('.flex').querySelectorAll('.tag-chip').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('hidGroup').value = val;
  document.getElementById('filterForm').submit();
}

function filterLow(btn) {
  // Client-side filter for low attendance
  lowFilter = !lowFilter;
  btn.classList.toggle('active', lowFilter);
  const items = document.querySelectorAll('[data-att]');
  items.forEach(item => {
    const att = parseInt(item.dataset.att, 10);
    if (lowFilter) {
      item.style.display = (att >= 0 && att < 75) ? '' : 'none';
    } else {
      item.style.display = '';
    }
  });
}

// â??â?? Live search (client-side, no reload) â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
function liveSearch(q) {
  const sq = q.toLowerCase().trim();
  document.querySelectorAll('[data-name]').forEach(item => {
    item.style.display = item.dataset.name.includes(sq) ? '' : 'none';
  });
}

// â??â?? Profile bottom sheet â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
function openProfile(id, name, initials, avClass, roll, sem, att, marks, email, phone, group) {
  document.getElementById('profileName').textContent     = name;
  document.getElementById('profileRoll').textContent     = roll;
  document.getElementById('profileMeta').textContent     = 'Semester ' + sem + (group ? ' â?? ' + group : '');
  document.getElementById('profileSemBadge').textContent = sem;

  // Avatar
  const av = document.getElementById('profileAvatar');
  av.textContent = initials;
  av.className   = 'w-16 h-16 rounded-3xl flex items-center justify-center text-white font-bold text-xl shadow-lg shrink-0 ' + avClass;

  // Attendance
  const hasAtt = att >= 0;
  const attEl  = document.getElementById('profileAtt');
  if (hasAtt) {
    const c = att >= 75 ? '#22c55e' : (att >= 60 ? '#f97316' : '#ef4444');
    attEl.textContent  = att + '%';
    attEl.style.color  = c;
    document.getElementById('profileAttLabel').textContent = att + '%';
    document.getElementById('profileAttBar').style.cssText = 'width:' + att + '%;background:' + c;
    document.getElementById('attSection').style.display = '';
  } else {
    attEl.textContent = 'N/A';
    attEl.style.color = '#94a3b8';
    document.getElementById('attSection').style.display = 'none';
  }

  // Marks
  const marksEl = document.getElementById('profileMarks');
  marksEl.textContent = marks !== null && marks !== undefined ? marks + '%' : 'N/A';

  // Contact
  const emailDiv = document.getElementById('profileEmail');
  const phoneDiv = document.getElementById('profilePhone');
  if (email) {
    document.getElementById('profileEmailText').textContent = email;
    emailDiv.classList.remove('hidden');
  } else {
    emailDiv.classList.add('hidden');
  }
  if (phone) {
    document.getElementById('profilePhoneText').textContent = phone;
    phoneDiv.classList.remove('hidden');
  } else {
    phoneDiv.classList.add('hidden');
  }

  // Message button
  document.getElementById('msgBtn').href = 'teacher_message.php?to=' + id;

  document.getElementById('profileModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeProfile() {
  document.getElementById('profileModal').classList.remove('open');
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeProfile(); });

// â??â?? Toast â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
function showToast(msg, icon, cls) {
  const t = document.createElement('div');
  t.className = 'toast';
  t.innerHTML = `<span class="material-symbols-outlined ${cls} text-lg" style="font-variation-settings:'FILL' 1;">${icon}</span>${msg}`;
  document.body.appendChild(t);
  setTimeout(() => { t.style.transition='opacity .3s'; t.style.opacity='0'; setTimeout(()=>t.remove(),300); }, 2500);
}
</script>
</body>
</html>