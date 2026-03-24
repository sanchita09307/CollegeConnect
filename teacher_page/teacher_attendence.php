<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$teacherId = (int)$teacher['id'];
$msg = '';
$msgType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    $subjectId = (int)($_POST['subject_id'] ?? 0);
    $date = $conn->real_escape_string($_POST['date'] ?? date('Y-m-d'));
    $attendanceData = $_POST['attendance'] ?? [];

    if ($subjectId && !empty($attendanceData)) {
        // Delete existing for that date+subject
        $conn->query("DELETE FROM attendance WHERE subject_id = $subjectId AND date = '$date'");
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, subject_id, date, status) VALUES (?, ?, ?, ?)");
        foreach ($attendanceData as $studentId => $status) {
            $sid = (int)$studentId;
            $s = $conn->real_escape_string($status);
            $stmt->bind_param("iiss", $sid, $subjectId, $date, $s);
            $stmt->execute();
        }
        $msg = "Attendance saved successfully!";
        $msgType = 'success';
    } else {
        $msg = "Please select a subject and mark attendance.";
        $msgType = 'error';
    }
}

// Get subjects
$subjects = $conn->query("SELECT * FROM subjects WHERE teacher_id = $teacherId ORDER BY subject_name");

// Get selected subject
$selSubject = (int)($_GET['subject'] ?? $_POST['subject_id'] ?? 0);
$selDate = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');
$students = false;
$existingAtt = [];

if ($selSubject) {
    $subInfo = $conn->query("SELECT * FROM subjects WHERE id = $selSubject AND teacher_id = $teacherId")->fetch_assoc();
    if ($subInfo) {
        $branch = $conn->real_escape_string($subInfo['branch_code'] ?? '');
        $sem = (int)($subInfo['semester'] ?? 0);
        // Fix: filter by BOTH branch_code/department AND semester so only correct class students show
        $students = $conn->query("SELECT id, full_name, student_roll_no FROM students WHERE status='approved' AND (department='$branch' OR branch_code='$branch') AND semester = $sem ORDER BY student_roll_no ASC");
        // Get existing attendance
        $safeDate = $conn->real_escape_string($selDate);
        $attResult = $conn->query("SELECT student_id, status FROM attendance WHERE subject_id = $selSubject AND date = '$safeDate'");
        if ($attResult) while ($ar = $attResult->fetch_assoc()) $existingAtt[$ar['student_id']] = $ar['status'];
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Take Attendance - CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
* { font-family: 'Lexend', sans-serif; }
body { min-height: 100dvh; background: #eef0ff; }
.dark body { background: #0d0e1c; }
@keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
.fu0{animation:fadeUp 0.4s 0.05s ease both}.fu1{animation:fadeUp 0.4s 0.15s ease both}.fu2{animation:fadeUp 0.4s 0.25s ease both}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 4px rgba(0,0,0,0.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-grad{background:linear-gradient(135deg,#4349cf,#7479f5)}
.btn-grad{background:linear-gradient(135deg,#4349cf,#7479f5);color:white;font-weight:700;box-shadow:0 4px 12px rgba(67,73,207,0.3);transition:all 0.2s}
.btn-grad:hover{transform:translateY(-1px)}.btn-grad:active{transform:scale(0.96)}

.att-btn { transition: all 0.15s; border:2px solid transparent; }
.att-btn.present { background:#dcfce7; color:#16a34a; border-color:#16a34a; font-weight:700; }
.att-btn.absent { background:#fee2e2; color:#dc2626; border-color:#dc2626; font-weight:700; }
.att-btn.late { background:#fef9c3; color:#ca8a04; border-color:#ca8a04; font-weight:700; }
.att-btn:not(.present):not(.absent):not(.late) { background:#f1f5f9; color:#64748b; }
.dark .att-btn:not(.present):not(.absent):not(.late) { background:#1e293b; color:#94a3b8; }
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "Take Attendance";
$activePage = "attendance";
include __DIR__ . '/teacher_topbar.php';
?>



<main class="px-4 py-4 space-y-4 pb-28">

<?php if ($msg): ?>
<div class="<?php echo $msgType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2 fu0">
    <span class="material-symbols-outlined"><?php echo $msgType === 'success' ? 'check_circle' : 'error'; ?></span>
    <?php echo htmlspecialchars($msg); ?>
</div>
<?php endif; ?>

<!-- FILTERS -->
<form method="GET" class="card p-4 space-y-3 fu1">
    <h3 class="font-bold text-sm flex items-center gap-2">
        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">filter_list</span>Select Class
    </h3>
    <div>
        <label class="text-xs font-semibold text-slate-500 uppercase mb-1 block">Subject</label>
        <select name="subject" onchange="this.form.submit()" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm appearance-none focus:ring-2 focus:ring-primary/30">
            <option value="">-- Select Subject --</option>
            <?php if($subjects) while($s = $subjects->fetch_assoc()): ?>
            <option value="<?php echo $s['id']; ?>" <?php echo $selSubject == $s['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($s['subject_name']); ?> (Sem <?php echo $s['semester']; ?>)
            </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500 uppercase mb-1 block">Date</label>
        <input type="date" name="date" value="<?php echo htmlspecialchars($selDate); ?>" onchange="this.form.submit()" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/30"/>
    </div>
</form>

<?php if ($selSubject && $students): ?>
<!-- ATTENDANCE FORM -->
<form method="POST" id="attForm">
    <input type="hidden" name="subject_id" value="<?php echo $selSubject; ?>"/>
    <input type="hidden" name="date" value="<?php echo htmlspecialchars($selDate); ?>"/>
    <input type="hidden" name="submit_attendance" value="1"/>

    <!-- BULK ACTIONS -->
    <div class="flex gap-2 fu2">
        <button type="button" onclick="markAll('present')" class="flex-1 py-2 rounded-xl bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-bold hover:bg-green-200 transition-colors">All Present</button>
        <button type="button" onclick="markAll('absent')" class="flex-1 py-2 rounded-xl bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-bold hover:bg-red-200 transition-colors"> All Absent</button>
        <button type="button" onclick="markAll('')" class="flex-1 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 text-xs font-bold hover:bg-slate-200 transition-colors"> Clear</button>
    </div>

    <!-- SUBJECT INFO -->
    <?php if(!empty($subInfo)): ?>
    <div class="card p-3 flex items-center gap-3 fu2">
        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">menu_book</span>
        </div>
        <div>
            <p class="font-bold text-sm"><?php echo htmlspecialchars($subInfo['subject_name']); ?></p>
            <p class="text-xs text-slate-400"><?php echo htmlspecialchars($subInfo['branch_code'] ?? ''); ?> Sem <?php echo $subInfo['semester']; ?> <?php echo htmlspecialchars($selDate); ?></p>
        </div>
        <div class="ml-auto text-right">
            <p class="text-lg font-bold text-primary" id="presentCount">0</p>
            <p class="text-[10px] text-slate-400">Present</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- STUDENT LIST -->
    <div class="space-y-2 fu2">
        <?php $studCount = 0; if($students->num_rows > 0): ?>
        <?php while($stud = $students->fetch_assoc()):
            $studCount++;
            $curStatus = $existingAtt[$stud['id']] ?? '';
            $spic = "https://ui-avatars.com/api/?name=" . urlencode($stud['full_name']) . "&background=4349cf&color=fff&size=40&bold=true";
        ?>
        <div class="card flex items-center gap-3 p-3" id="row_<?php echo $stud['id']; ?>">
            <div class="w-8 h-8 rounded-full bg-cover bg-center shrink-0 bg-primary/10 flex items-center justify-center text-xs font-bold text-primary">
                <?php echo strtoupper(substr($stud['full_name'],0,1)); ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($stud['full_name']); ?></p>
                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($stud['student_roll_no'] ?? ''); ?></p>
            </div>
            <input type="hidden" name="attendance[<?php echo $stud['id']; ?>]" id="att_<?php echo $stud['id']; ?>" value="<?php echo htmlspecialchars($curStatus); ?>"/>
            <div class="flex gap-1.5 shrink-0">
                <button type="button" onclick="setStatus(<?php echo $stud['id']; ?>, 'present')"
                    class="att-btn w-9 h-8 rounded-lg text-xs <?php echo $curStatus === 'present' ? 'present' : ''; ?>"
                    id="btn_P_<?php echo $stud['id']; ?>" title="Present">P</button>
                <button type="button" onclick="setStatus(<?php echo $stud['id']; ?>, 'absent')"
                    class="att-btn w-9 h-8 rounded-lg text-xs <?php echo $curStatus === 'absent' ? 'absent' : ''; ?>"
                    id="btn_A_<?php echo $stud['id']; ?>" title="Absent">A</button>
                <button type="button" onclick="setStatus(<?php echo $stud['id']; ?>, 'late')"
                    class="att-btn w-9 h-8 rounded-lg text-xs <?php echo $curStatus === 'late' ? 'late' : ''; ?>"
                    id="btn_L_<?php echo $stud['id']; ?>" title="Late">L</button>
            </div>
        </div>
        <?php endwhile; ?>
        <?php else: ?>
        <div class="card p-8 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
            <span class="material-symbols-outlined text-5xl text-slate-300">groups</span>
            <p class="text-sm text-slate-400 mt-2">No students found for this class</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if($studCount > 0): ?>
    <button type="submit" class="btn-grad w-full py-3.5 rounded-xl text-sm sticky bottom-20 z-10">
        <span class="flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">save</span>Save Attendance
        </span>
    </button>
    <?php endif; ?>
</form>

<?php elseif(!$selSubject): ?>
<div class="card p-8 text-center fu2">
    <span class="material-symbols-outlined text-5xl text-slate-300">assignment_turned_in</span>
    <p class="font-bold text-slate-600 dark:text-slate-300 mt-3">Select a subject above</p>
    <p class="text-sm text-slate-400 mt-1">Choose a subject and date to take attendance</p>
</div>
<?php endif; ?>

</main>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
    <div class="max-w-xl mx-auto flex justify-around">
        <a href="teacher_dashboard.php" class="text-slate-400 flex flex-col items-center gap-0.5 px-3 py-1 transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span>
        </a>
        <a href="teacher_classes.php" class="text-slate-400 flex flex-col items-center gap-0.5 px-3 py-1 transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-xl">menu_book</span><span class="text-[10px]">Classes</span>
        </a>
        <a href="teacher_attendence.php" class="text-primary flex flex-col items-center gap-0.5 px-3 py-1">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">assignment_turned_in</span><span class="text-[10px] font-bold">Attend.</span>
        </a>
        <a href="teacher_message.php" class="text-slate-400 flex flex-col items-center gap-0.5 px-3 py-1 transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span>
        </a>
        <a href="teacher_profile.php" class="text-slate-400 flex flex-col items-center gap-0.5 px-3 py-1 transition-colors hover:text-primary">
            <span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span>
        </a>
    </div>
</nav>

<script>
if (localStorage.getItem('cc_dark') === '1') {
    document.documentElement.classList.add('dark');
    document.getElementById('dIcon').textContent = 'light_mode';
}

function setStatus(id, status) {
    const statuses = ['present','absent','late'];
    statuses.forEach(s => {
        const btn = document.getElementById('btn_' + s.charAt(0).toUpperCase() + '_' + id);
        if(btn) btn.className = btn.className.replace(/\b(present|absent|late)\b/g,'').trim();
    });
    const input = document.getElementById('att_' + id);
    if (input.value === status) {
        input.value = '';
    } else {
        input.value = status;
        const btn = document.getElementById('btn_' + status.charAt(0).toUpperCase() + '_' + id);
        if(btn) btn.classList.add(status);
    }
    updateCount();
}

function markAll(status) {
    document.querySelectorAll('[id^="att_"]').forEach(input => {
        const id = input.id.replace('att_','');
        ['present','absent','late'].forEach(s => {
            const b = document.getElementById('btn_'+s.charAt(0).toUpperCase()+'_'+id);
            if(b) b.className = b.className.replace(/\b(present|absent|late)\b/g,'').trim();
        });
        input.value = status;
        if(status) {
            const b = document.getElementById('btn_'+status.charAt(0).toUpperCase()+'_'+id);
            if(b) b.classList.add(status);
        }
    });
    updateCount();
}

function updateCount() {
    const c = document.querySelectorAll('[id^="att_"]');
    let p = 0;
    c.forEach(i => { if(i.value === 'present') p++; });
    const el = document.getElementById('presentCount');
    if(el) el.textContent = p;
}

updateCount();
</script>
</body>
</html>