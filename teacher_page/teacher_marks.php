<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$teacherId = (int)($teacher['id'] ?? 0);
$msg = ''; $msgType = '';

// Handle marks save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $examYear  = $conn->real_escape_string($_POST['exam_year'] ?? date('Y'));
    $marksData = $_POST['marks'] ?? [];

    if ($subjectId && !empty($marksData)) {
        $saved = 0;
        foreach ($marksData as $studentId => $m) {
            $sid  = (int)$studentId;
            $fa   = min(30, max(0, (float)($m['fa'] ?? 0)));
            $sa   = min(70, max(0, (float)($m['sa'] ?? 0)));
            $total = $fa + $sa;
            $pct  = $total; // out of 100
            // Grade
            if ($pct >= 90) $grade = 'A+';
            elseif ($pct >= 80) $grade = 'A';
            elseif ($pct >= 70) $grade = 'B+';
            elseif ($pct >= 60) $grade = 'B';
            elseif ($pct >= 50) $grade = 'C';
            elseif ($pct >= 40) $grade = 'D';
            else $grade = 'F';

            $sql = "INSERT INTO results (student_id, subject_id, fa_marks, sa_marks, total_marks, percentage, grade, exam_year)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE fa_marks=VALUES(fa_marks), sa_marks=VALUES(sa_marks),
                    total_marks=VALUES(total_marks), percentage=VALUES(percentage), grade=VALUES(grade)";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iiddddss", $sid, $subjectId, $fa, $sa, $total, $pct, $grade, $examYear);
                $stmt->execute(); $stmt->close();
                $saved++;
            }
        }
        $msg = "Marks saved for $saved students!"; $msgType = 'success';
    } else {
        $msg = 'Please select a subject and enter marks.'; $msgType = 'error';
    }
}

// Get teacher's subjects
$subjects = [];
$sRes = $conn->query("SELECT s.*, b.branch_name FROM subjects s LEFT JOIN branches b ON s.branch_code=b.branch_code WHERE s.teacher_id = $teacherId ORDER BY s.semester ASC, s.subject_name ASC");
while ($row = $sRes->fetch_assoc()) $subjects[] = $row;

// Selected subject & year
$selSubject = (int)($_GET['subject'] ?? $_POST['subject_id'] ?? 0);
$selYear    = $_GET['year'] ?? $_POST['exam_year'] ?? date('Y');
$subInfo    = null;
$students   = [];
$existingMarks = [];

if ($selSubject) {
    foreach ($subjects as $s) { if ($s['id'] == $selSubject) { $subInfo = $s; break; } }
    if ($subInfo) {
        $branch = $conn->real_escape_string($subInfo['branch_code']);
        $sem    = (int)$subInfo['semester'];
        $sRes2  = $conn->query("SELECT id, full_name, student_roll_no FROM students WHERE status='approved' AND (department='$branch' OR branch_code='$branch') AND semester=$sem ORDER BY student_roll_no ASC");
        while ($row = $sRes2->fetch_assoc()) $students[] = $row;

        // Existing marks
        $safeYear = $conn->real_escape_string($selYear);
        $mRes = $conn->query("SELECT * FROM results WHERE subject_id=$selSubject AND exam_year='$safeYear'");
        while ($row = $mRes->fetch_assoc()) $existingMarks[$row['student_id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Marks & Grades â€“ CollegeConnect</title>
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
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.btn-grad{background:linear-gradient(135deg,#4349cf,#7479f5);color:white;font-weight:700;box-shadow:0 4px 12px rgba(67,73,207,.3);transition:all .2s;border:none;cursor:pointer}
.btn-grad:hover{transform:translateY(-1px)}
.marks-input{width:56px;border:2px solid #e2e8f0;border-radius:.625rem;padding:5px 8px;font-family:'Lexend',sans-serif;font-size:13px;font-weight:700;text-align:center;outline:none;transition:border-color .2s}
.marks-input:focus{border-color:#4349cf}
select.cc-sel{background:white;border:2px solid #e2e8f0;border-radius:.75rem;padding:8px 12px;font-size:13px;font-weight:600;color:#475569;outline:none;transition:border-color .2s;cursor:pointer;font-family:'Lexend',sans-serif;width:100%}
select.cc-sel:focus{border-color:#4349cf}
.dark select.cc-sel{background:#1a1b2e;border-color:#2a2b45;color:#94a3b8}
.grade-badge{display:inline-block;padding:2px 8px;border-radius:8px;font-size:11px;font-weight:700}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "Marks & Grades";
$activePage = "marks";
include __DIR__ . '/teacher_topbar.php';
?>

<!-- HERO -->
<div class="px-4 pt-4 fu0">
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/30 relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">grade</span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Academic</p>
    <h1 class="text-xl font-bold">Marks & Grades</h1>
    <p class="text-white/70 text-xs mt-1">Enter FA (Internal /30) + SA (External /70) = Total /100</p>
  </div>
</div>

<!-- ALERT -->
<?php if ($msg): ?>
<div class="px-4 pt-3 fu1">
  <div class="rounded-xl px-4 py-3 text-sm font-semibold flex items-center gap-2
    <?php echo $msgType==='success'?'bg-green-50 text-green-700 border border-green-200':'bg-red-50 text-red-600 border border-red-200';?>">
    <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;"><?php echo $msgType==='success'?'check_circle':'error';?></span>
    <?php echo htmlspecialchars($msg);?>
  </div>
</div>
<?php endif;?>

<!-- SELECT SUBJECT & YEAR -->
<div class="px-4 pt-4 fu1">
  <div class="card p-4">
    <form method="GET" class="space-y-3">
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Select Subject</label>
        <select name="subject" class="cc-sel" onchange="this.form.submit()">
          <option value="">Choose Subject </option>
          <?php foreach ($subjects as $s): ?>
          <option value="<?php echo $s['id'];?>" <?php echo $selSubject==$s['id']?'selected':'';?>>
            Sem <?php echo $s['semester'];?> <?php echo htmlspecialchars($s['subject_name']);?> (<?php echo $s['branch_code'];?>)
          </option>
          <?php endforeach;?>
        </select>
      </div>
      <div>
        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Exam Year</label>
        <select name="year" class="cc-sel" onchange="this.form.submit()">
          <?php for($y=date('Y');$y>=date('Y')-3;$y--): ?>
          <option value="<?php echo $y;?>" <?php echo $selYear==$y?'selected':'';?>>Winter <?php echo $y;?></option>
          <?php endfor;?>
        </select>
      </div>
    </form>
  </div>
</div>

<main class="px-4 pt-4 pb-28 fu2">

<?php if ($subInfo && !empty($students)): ?>

<!-- MARKS TABLE -->
<form method="POST">
  <input type="hidden" name="save_marks" value="1"/>
  <input type="hidden" name="subject_id" value="<?php echo $selSubject;?>"/>
  <input type="hidden" name="exam_year" value="<?php echo htmlspecialchars($selYear);?>"/>

  <div class="flex items-center justify-between mb-3">
    <h3 class="font-bold text-sm"><?php echo htmlspecialchars($subInfo['subject_name']);?>  <?php echo $selYear;?></h3>
    <span class="text-[10px] font-bold text-slate-400"><?php echo count($students);?> students</span>
  </div>

  <!-- Header -->
  <div class="card overflow-hidden mb-2">
    <div class="grid grid-cols-12 gap-1 px-3 py-2 bg-slate-50 dark:bg-slate-800/50 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
      <div class="col-span-1">#</div>
      <div class="col-span-4">Student</div>
      <div class="col-span-2 text-center">FA<br><span class="font-normal">/30</span></div>
      <div class="col-span-2 text-center">SA<br><span class="font-normal">/70</span></div>
      <div class="col-span-2 text-center">Total</div>
      <div class="col-span-1 text-center">Grd</div>
    </div>

    <?php foreach ($students as $idx => $stu):
      $sid     = $stu['id'];
      $em      = $existingMarks[$sid] ?? [];
      $fa      = $em['fa_marks'] ?? '';
      $sa      = $em['sa_marks'] ?? '';
      $total   = $em['total_marks'] ?? '';
      $grade   = $em['grade'] ?? '';
      $gradeColors = ['A+'=>'text-green-600 bg-green-50','A'=>'text-blue-600 bg-blue-50','B+'=>'text-purple-600 bg-purple-50','B'=>'text-cyan-600 bg-cyan-50','C'=>'text-yellow-700 bg-yellow-50','D'=>'text-orange-600 bg-orange-50','F'=>'text-red-600 bg-red-50'];
      $gc = $gradeColors[$grade] ?? 'text-slate-400 bg-slate-100';
    ?>
    <div class="grid grid-cols-12 gap-1 px-3 py-2.5 items-center border-t border-slate-100 dark:border-slate-800 <?php echo $idx%2===0?'':'bg-slate-50/50 dark:bg-slate-800/20';?>">
      <div class="col-span-1 text-[10px] text-slate-400 font-bold"><?php echo $idx+1;?></div>
      <div class="col-span-4">
        <p class="text-xs font-semibold truncate"><?php echo htmlspecialchars(explode(' ',$stu['full_name'])[0] ?? $stu['full_name']);?></p>
        <p class="text-[9px] text-slate-400"><?php echo htmlspecialchars($stu['student_roll_no'] ?? '');?></p>
      </div>
      <div class="col-span-2 text-center">
        <input type="number" name="marks[<?php echo $sid;?>][fa]"
               class="marks-input" min="0" max="30" step="0.5"
               value="<?php echo htmlspecialchars($fa);?>"
               onchange="calcTotal(this,<?php echo $sid;?>)"
               id="fa_<?php echo $sid;?>"/>
      </div>
      <div class="col-span-2 text-center">
        <input type="number" name="marks[<?php echo $sid;?>][sa]"
               class="marks-input" min="0" max="70" step="0.5"
               value="<?php echo htmlspecialchars($sa);?>"
               onchange="calcTotal(this,<?php echo $sid;?>)"
               id="sa_<?php echo $sid;?>"/>
      </div>
      <div class="col-span-2 text-center">
        <span id="total_<?php echo $sid;?>" class="text-sm font-bold <?php echo $total>=40?'text-green-600':'text-red-500';?>">
          <?php echo $total!==''?(float)$total:'â€“';?>
        </span>
      </div>
      <div class="col-span-1 text-center">
        <span id="grade_<?php echo $sid;?>" class="grade-badge <?php echo $gc;?>"><?php echo $grade ?: 'â€“';?></span>
      </div>
    </div>
    <?php endforeach;?>
  </div>

  <button type="submit" class="btn-grad w-full py-3 rounded-xl text-sm flex items-center justify-center gap-2">
    <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">save</span>
    Save All Marks
  </button>
</form>

<?php elseif ($selSubject && empty($students)): ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">group</span>
  <h3 class="font-bold text-slate-500 mt-3">No Students Found</h3>
  <p class="text-xs text-slate-400 mt-1">No approved students for this subject's branch and semester.</p>
</div>
<?php else: ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">grade</span>
  <h3 class="font-bold text-slate-500 mt-3">Select a Subject</h3>
  <p class="text-xs text-slate-400 mt-1">Choose a subject above to enter marks.</p>
</div>
<?php endif;?>

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
function calcTotal(el, sid) {
    const fa = parseFloat(document.getElementById('fa_'+sid)?.value) || 0;
    const sa = parseFloat(document.getElementById('sa_'+sid)?.value) || 0;
    const total = Math.round((fa + sa) * 10) / 10;
    const pct   = total;
    const tEl   = document.getElementById('total_'+sid);
    const gEl   = document.getElementById('grade_'+sid);
    if (tEl) {
        tEl.textContent = total || ;
        tEl.className = total >= 40 ? 'text-sm font-bold text-green-600' : 'text-sm font-bold text-red-500';
    }
    let grade = ;
    let gc = 'text-slate-400 bg-slate-100';
    if (pct >= 90) { grade='A+'; gc='text-green-600 bg-green-50'; }
    else if (pct >= 80) { grade='A'; gc='text-blue-600 bg-blue-50'; }
    else if (pct >= 70) { grade='B+'; gc='text-purple-600 bg-purple-50'; }
    else if (pct >= 60) { grade='B'; gc='text-cyan-600 bg-cyan-50'; }
    else if (pct >= 50) { grade='C'; gc='text-yellow-700 bg-yellow-50'; }
    else if (pct >= 40) { grade='D'; gc='text-orange-600 bg-orange-50'; }
    else if (fa > 0 || sa > 0) { grade='F'; gc='text-red-600 bg-red-50'; }
    if (gEl) { gEl.textContent = grade; gEl.className = 'grade-badge '+gc; }
}
</script>
</body>
</html>