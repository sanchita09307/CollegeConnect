<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$teacherId = (int)($teacher['id'] ?? 0);

$subjectsCountRow = $conn->query("SELECT COUNT(*) AS total FROM subjects WHERE teacher_id = $teacherId");
$subjectsCount = ($subjectsCountRow && $subjectsCountRow->num_rows > 0)
    ? (int)($subjectsCountRow->fetch_assoc()['total'] ?? 0)
    : 0;

$teacherSubjects = $conn->query("SELECT * FROM subjects WHERE teacher_id = $teacherId ORDER BY semester ASC LIMIT 10");

$dept = $conn->real_escape_string($teacher['department'] ?? '');

// Get branch codes from subjects this teacher teaches
// Teacher's department may be full name ("Computer Engineering") but students store short code ("CO")
// So we match: students whose department/branch_code matches teacher's department (full or short)
// OR students in any branch where teacher teaches subjects

// First get all branch_codes from teacher's subjects
$branchCodes = [];
$bcRes = $conn->query("SELECT DISTINCT branch_code FROM subjects WHERE teacher_id = $teacherId AND branch_code IS NOT NULL AND branch_code != ''");
if ($bcRes) while ($bc = $bcRes->fetch_assoc()) $branchCodes[] = $conn->real_escape_string($bc['branch_code']);

// Build WHERE clause â€” match by department name (full/partial) OR branch_code
$matchConditions = [];
if ($dept) {
    $matchConditions[] = "department = '$dept'";
    $matchConditions[] = "branch_code = '$dept'";
    // Also try LIKE match for partial name (e.g. "Computer Engineering" contains "Computer")
    $deptWord = $conn->real_escape_string(explode(' ', $dept)[0]); // first word
    $matchConditions[] = "department LIKE '%$deptWord%'";
}
foreach ($branchCodes as $bc) {
    $matchConditions[] = "department = '$bc'";
    $matchConditions[] = "branch_code = '$bc'";
}
$matchWhere = $matchConditions ? '(' . implode(' OR ', $matchConditions) . ')' : '1=0';

$studentCountRow = $conn->query("SELECT COUNT(DISTINCT id) as t FROM students WHERE $matchWhere AND status='approved'");
$studentCount = ($studentCountRow && $studentCountRow->num_rows > 0)
    ? (int)($studentCountRow->fetch_assoc()['t'] ?? 0)
    : 0;

// Pending leave requests â€” same broad match
$leaveWhere = $matchConditions ? '(' . str_replace('department', 'lr.department', implode(' OR ', array_filter($matchConditions, fn($c) => str_contains($c, 'department')))) . ')' : "1=0";
$pendingLeaveRow = $conn->query("SELECT COUNT(*) as t FROM leave_requests lr WHERE user_role='student' AND status='pending' AND (lr.department='$dept' OR lr.department IN (" . (count($branchCodes) ? "'" . implode("','", $branchCodes) . "'" : "''") . "))");
$pendingLeaveCount = ($pendingLeaveRow && $pendingLeaveRow->num_rows > 0)
    ? (int)($pendingLeaveRow->fetch_assoc()['t'] ?? 0)
    : 0;

$photo = !empty($teacher['profile_photo'])
    ? "../uploads/profile_photos/" . htmlspecialchars($teacher['profile_photo'])
    : "https://ui-avatars.com/api/?name=" . urlencode($teacher['name'] ?? 'T') . "&background=4349cf&color=fff&bold=true&size=80";
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Teacher Dashboard - CollegeConnect</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                primary: "#4349cf",
                "background-light": "#eef0ff",
                "background-dark": "#0d0e1c"
            },
            fontFamily: {
                display: ["Lexend"]
            }
        }
    }
}
</script>
<style>
* { font-family: 'Lexend', sans-serif; }
body { min-height: 100dvh; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(18px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes pulseRed {
    0%,100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.fu0{animation:fadeUp 0.4s 0.05s ease both}
.fu1{animation:fadeUp 0.4s 0.15s ease both}
.fu2{animation:fadeUp 0.4s 0.25s ease both}
.fu3{animation:fadeUp 0.4s 0.35s ease both}
.fu4{animation:fadeUp 0.4s 0.45s ease both}
.fu5{animation:fadeUp 0.4s 0.55s ease both}

.card{
    background:white;
    border-radius:1rem;
    border:1px solid #e8eaf6;
    box-shadow:0 1px 4px rgba(0,0,0,0.06);
    transition:transform 0.18s, box-shadow 0.18s;
}
.card:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(67,73,207,0.1);
}
.dark .card{
    background:#1a1b2e;
    border-color:#2a2b45;
}
.hero-grad{
    background:linear-gradient(135deg,#4349cf 0%,#7479f5 55%,#a78bfa 100%);
}
.btn-grad{
    background:linear-gradient(135deg,#4349cf,#7479f5);
    color:white;
    font-weight:700;
    box-shadow:0 4px 12px rgba(67,73,207,0.3);
    transition:all 0.2s;
}
.btn-grad:hover{
    transform:translateY(-1px);
    box-shadow:0 6px 18px rgba(67,73,207,0.4);
}
.btn-grad:active{ transform:scale(0.96); }

.notif-pulse{ animation:pulseRed 2s infinite; }
.nav-active{ color:#4349cf; }
.nav-active .ni{ font-variation-settings:'FILL' 1; }

/* notification overlay */
#notifBackdrop{
    opacity:0;
    pointer-events:none;
    transition:opacity .25s ease;
}
#notifBackdrop.show{
    opacity:1;
    pointer-events:auto;
}
#notifPanel{
    transform:translateY(110%);
    transition:transform .3s ease;
}
#notifBackdrop.show #notifPanel{
    transform:translateY(0);
}
</style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "Dashboard";
$activePage = "home";
include __DIR__ . '/teacher_topbar.php';
?>

<!-- HERO -->
<div class="px-4 pt-4 fu1">
    <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/40 relative overflow-hidden">
        <div class="absolute -right-4 -top-4 opacity-10 pointer-events-none">
            <span class="material-symbols-outlined" style="font-size:120px;font-variation-settings:'FILL' 1;">person_pin</span>
        </div>
        <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Good day!</p>
        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($teacher['name'] ?? 'Teacher'); ?></h2>
        <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-white/80 text-xs">
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">apartment</span>
                <?php echo htmlspecialchars($teacher['department'] ?? 'Dept not set'); ?>
            </span>
            <span class="flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">stars</span>
                <?php echo htmlspecialchars($teacher['designation'] ?? 'Not set'); ?>
            </span>
        </div>
        <div class="flex gap-3 mt-3">
            <a href="teacher_attendence.php" class="flex items-center gap-1.5 bg-white/20 hover:bg-white/30 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-colors backdrop-blur-sm">
                <span class="material-symbols-outlined text-sm">assignment_turned_in</span>Take Attendance
            </a>
            <a href="teacher_portal.php" class="flex items-center gap-1.5 bg-white/15 hover:bg-white/25 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-colors backdrop-blur-sm">
                <span class="material-symbols-outlined text-sm">menu</span>Full Portal
            </a>
        </div>
    </div>
</div>

<main class="px-4 py-4 space-y-5 pb-28">

<!-- STATS -->
<div class="grid grid-cols-3 gap-3 fu2">
    <div class="card p-3 flex flex-col items-center gap-1">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-2xl" style="font-variation-settings:'FILL' 1;">menu_book</span>
        </div>
        <p class="text-xl font-bold text-primary"><?php echo $subjectsCount; ?></p>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Subjects</p>
    </div>

    <div class="card p-3 flex flex-col items-center gap-1">
        <div class="w-12 h-12 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600 text-2xl" style="font-variation-settings:'FILL' 1;">groups</span>
        </div>
        <p class="text-xl font-bold text-green-600"><?php echo $studentCount; ?></p>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Students</p>
    </div>

    <a href="teacher_leave.php" class="card p-3 flex flex-col items-center gap-1 active:scale-95 transition-all">
        <div class="w-12 h-12 rounded-full bg-orange-50 dark:bg-orange-900/30 flex items-center justify-center relative">
            <span class="material-symbols-outlined text-orange-600 text-2xl" style="font-variation-settings:'FILL' 1;">event_busy</span>
            <?php if($pendingLeaveCount > 0): ?>
            <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"><?php echo $pendingLeaveCount; ?></span>
            <?php endif; ?>
        </div>
        <p class="text-xl font-bold text-orange-600"><?php echo $pendingLeaveCount; ?></p>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider text-center">Leaves Pending</p>
    </a>
</div>

<!-- TEACHER DETAILS -->
<div class="card overflow-hidden fu2">
    <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-800">
        <h3 class="font-bold text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">badge</span>My Profile
        </h3>
        <a href="teacher_profile.php" class="text-primary text-xs font-semibold flex items-center gap-1 hover:underline">
            <span class="material-symbols-outlined text-sm">edit</span>Edit
        </a>
    </div>
    <div class="p-4 grid grid-cols-2 gap-y-3 gap-x-4 text-sm">
        <div>
            <p class="text-[10px] text-slate-400 font-semibold uppercase">Full Name</p>
            <p class="font-semibold"><?php echo htmlspecialchars($teacher['name'] ?? '-'); ?></p>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-semibold uppercase">Designation</p>
            <p class="font-semibold text-primary"><?php echo htmlspecialchars($teacher['designation'] ?? '-'); ?></p>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-semibold uppercase">Department</p>
            <p class="font-semibold"><?php echo htmlspecialchars($teacher['department'] ?? '-'); ?></p>
        </div>
        <div>
            <p class="text-[10px] text-slate-400 font-semibold uppercase">Qualification</p>
            <p class="font-semibold"><?php echo htmlspecialchars($teacher['qualification'] ?? '-'); ?></p>
        </div>
        <div class="col-span-2">
            <p class="text-[10px] text-slate-400 font-semibold uppercase">Email</p>
            <p class="font-semibold text-xs break-all"><?php echo htmlspecialchars($teacher['email'] ?? '-'); ?></p>
        </div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="fu3">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">apps</span>Quick Actions
    </h3>
    <div class="grid grid-cols-4 gap-2">
        <?php
        $acts = [
            ['href'=>'teacher_attendance.php','icon'=>'assignment_turned_in','color'=>'green','label'=>'Attendance'],
            ['href'=>'teacher_classes.php','icon'=>'menu_book','color'=>'blue','label'=>'Classes'],
            ['href'=>'teacher_marks.php','icon'=>'grade','color'=>'yellow','label'=>'Marks'],
            ['href'=>'teacher_portal.php','icon'=>'cloud_upload','color'=>'purple','label'=>'Upload'],
            ['href'=>'teacher_message.php','icon'=>'chat','color'=>'indigo','label'=>'Messages'],
            ['href'=>'teacher_students.php','icon'=>'groups','color'=>'teal','label'=>'Students'],
            ['href'=>'teacher_leave.php','icon'=>'event_busy','color'=>'orange','label'=>'Leaves'],
            ['href'=>'teacher_schedule.php','icon'=>'calendar_month','color'=>'red','label'=>'Schedule'],
            ['href'=>'teacher_profile.php','icon'=>'manage_accounts','color'=>'slate','label'=>'Profile'],
        ];

        $cls = [
            'green'=>['bg-green-50 dark:bg-green-900/30','text-green-600'],
            'blue'=>['bg-blue-50 dark:bg-blue-900/30','text-blue-600'],
            'yellow'=>['bg-yellow-50 dark:bg-yellow-900/30','text-yellow-600'],
            'purple'=>['bg-purple-50 dark:bg-purple-900/30','text-purple-600'],
            'indigo'=>['bg-indigo-50 dark:bg-indigo-900/30','text-indigo-600'],
            'teal'=>['bg-teal-50 dark:bg-teal-900/30','text-teal-600'],
            'orange'=>['bg-orange-50 dark:bg-orange-900/30','text-orange-600'],
            'red'=>['bg-red-50 dark:bg-red-900/30','text-red-600'],
            'pink'=>['bg-pink-50 dark:bg-pink-900/30','text-pink-600'],
            'slate'=>['bg-slate-100 dark:bg-slate-800','text-slate-600 dark:text-slate-300']
        ];

        foreach($acts as $a):
            $c = $cls[$a['color']];
        ?>
        <a href="<?php echo $a['href']; ?>" class="card flex flex-col items-center gap-1.5 p-2.5 active:scale-95 cursor-pointer">
            <div class="w-11 h-11 rounded-full <?php echo $c[0]; ?> <?php echo $c[1]; ?> flex items-center justify-center">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;"><?php echo $a['icon']; ?></span>
            </div>
            <span class="text-[9px] font-bold text-slate-600 dark:text-slate-300 text-center leading-tight"><?php echo $a['label']; ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- MY SUBJECTS -->
<div class="fu3">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">library_books</span>My Subjects
        </h3>
        <a href="teacher_classes.php" class="text-primary text-xs font-semibold">View all</a>
    </div>

    <?php if ($teacherSubjects && $teacherSubjects->num_rows > 0): ?>
    <div class="space-y-2">
        <?php
        while($sub = $teacherSubjects->fetch_assoc()):
            $colors = ['blue','green','purple','orange','teal','indigo','red','yellow'];
            $ci = crc32($sub['subject_name']) % count($colors);
            $col = $colors[$ci];
            $colMap = [
                'blue'=>'bg-blue-50 dark:bg-blue-900/30 text-blue-600',
                'green'=>'bg-green-50 dark:bg-green-900/30 text-green-600',
                'purple'=>'bg-purple-50 dark:bg-purple-900/30 text-purple-600',
                'orange'=>'bg-orange-50 dark:bg-orange-900/30 text-orange-600',
                'teal'=>'bg-teal-50 dark:bg-teal-900/30 text-teal-600',
                'indigo'=>'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600',
                'red'=>'bg-red-50 dark:bg-red-900/30 text-red-600',
                'yellow'=>'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600'
            ];
        ?>
        <div class="card flex items-center gap-3 p-3 cursor-pointer" onclick="window.location='teacher_classes.php'">
            <div class="w-10 h-10 rounded-xl <?php echo $colMap[$col]; ?> flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">menu_book</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($sub['subject_name']); ?></p>
                <p class="text-xs text-slate-400">
                    <?php echo htmlspecialchars($sub['branch_code'] ?? ''); ?> â€¢
                    Sem <?php echo htmlspecialchars($sub['semester']); ?> â€¢
                    <?php echo htmlspecialchars($sub['subject_code'] ?? ''); ?>
                </p>
            </div>
            <a href="teacher_attendence.php?subject=<?php echo (int)$sub['id']; ?>" class="btn-grad text-xs px-3 py-1.5 rounded-lg whitespace-nowrap">Attend.</a>
        </div>
        <?php endwhile; ?>
    </div>
    <?php else: ?>
    <div class="card p-8 text-center border-2 border-dashed border-slate-200 dark:border-slate-700">
        <span class="material-symbols-outlined text-5xl text-slate-300">library_books</span>
        <p class="text-sm text-slate-400 mt-2">No subjects assigned yet</p>
    </div>
    <?php endif; ?>
</div>

<!-- TODAY'S SCHEDULE -->
<div class="fu4">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">today</span>Today's Schedule
    </h3>
    <div class="space-y-2">
        <div class="card flex items-center gap-3 p-3">
            <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-primary/10 text-primary shrink-0">
                <span class="text-[10px] font-bold">10:00</span>
                <span class="text-[9px] font-bold">AM</span>
            </div>
            <div class="flex-1">
                <p class="font-bold text-sm">Theory Class</p>
                <p class="text-xs text-slate-400">Room 301 â€¢ Block A</p>
            </div>
            <button class="btn-grad text-xs px-3 py-1.5 rounded-lg">Join</button>
        </div>

        <div class="card flex items-center gap-3 p-3 opacity-70">
            <div class="flex flex-col items-center justify-center w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 shrink-0">
                <span class="text-[10px] font-bold">01:30</span>
                <span class="text-[9px] font-bold">PM</span>
            </div>
            <div class="flex-1">
                <p class="font-bold text-sm">Lab Session</p>
                <p class="text-xs text-slate-400">Lab 2 â€¢ Science Block</p>
            </div>
            <button class="bg-slate-100 dark:bg-slate-800 text-slate-400 text-xs font-bold px-3 py-1.5 rounded-lg" disabled>Upcoming</button>
        </div>
    </div>
</div>

<!-- QUICK UPLOAD -->
<div class="card p-4 fu4" style="background:linear-gradient(135deg,rgba(67,73,207,0.07),rgba(107,115,240,0.04));border-color:rgba(67,73,207,0.2)">
    <div class="flex items-center gap-3 mb-3">
        <div class="p-2 bg-primary rounded-xl text-white">
            <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">cloud_upload</span>
        </div>
        <h3 class="font-bold">Quick Upload</h3>
    </div>
    <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">Share notes, slides, or assignments with your students instantly.</p>

    <div class="flex gap-2">
        <div class="flex-1 relative">
            <select class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 text-sm appearance-none focus:ring-2 focus:ring-primary/30 focus:border-primary">
                <option>Select Subject</option>
                <?php
                $teacherSubjects2 = $conn->query("SELECT * FROM subjects WHERE teacher_id = $teacherId LIMIT 10");
                if ($teacherSubjects2):
                    while ($s = $teacherSubjects2->fetch_assoc()):
                ?>
                    <option value="<?php echo (int)$s['id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?></option>
                <?php
                    endwhile;
                endif;
                ?>
            </select>
            <span class="material-symbols-outlined absolute right-2 top-2 text-slate-400 pointer-events-none text-sm">expand_more</span>
        </div>

        <a href="teacher_portal.php" class="btn-grad w-10 h-10 rounded-xl flex items-center justify-center">
            <span class="material-symbols-outlined text-sm">add</span>
        </a>
    </div>
</div>

<!-- LOGOUT -->
<div class="fu5">
    <a href="../auth/logout.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 font-bold text-sm border border-red-100 dark:border-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
        <span class="material-symbols-outlined">logout</span>Logout
    </a>
</div>

</main>

<!-- FAB -->
<a href="teacher_portal.php" class="fixed bottom-20 right-4 z-30 w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/40 hover:bg-indigo-600 transition-all hover:scale-110 active:scale-95">
    <span class="material-symbols-outlined text-2xl">add</span>
</a>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="teacher_dashboard.php" class="flex flex-col items-center gap-0.5 text-primary px-4 py-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">home</span><span class="text-[10px] font-bold">Home</span></a>
    <a href="teacher_students.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">groups</span><span class="text-[10px] font-medium">Students</span></a>
    <a href="teacher_ai_atrisk.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">crisis_alert</span><span class="text-[10px] font-medium">AI Risk</span></a>
    <a href="teacher_profile.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px] font-medium">Profile</span></a>
  </div>
</nav>

<!-- NOTIFICATION BACKDROP + PANEL -->
<div id="notifBackdrop" class="fixed inset-0 z-50 bg-black/30" onclick="hideNotif()">
    <div id="notifPanel" class="absolute bottom-0 left-0 right-0 bg-white dark:bg-slate-900 rounded-t-3xl shadow-2xl p-5 max-h-[70vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="w-10 h-1 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mb-4"></div>
        <h3 class="font-bold text-lg mb-4">Notifications</h3>

        <div class="space-y-3">
            <div class="flex gap-3 p-3 bg-primary/5 rounded-xl">
                <div class="w-10 h-10 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-primary">assignment</span>
                </div>
                <div>
                    <p class="text-sm font-semibold">12 Ungraded Submissions</p>
                    <p class="text-xs text-slate-500 mt-0.5">Lab Report 4 needs your review.</p>
                    <p class="text-[10px] text-slate-400 mt-1">1 hour ago</p>
                </div>
            </div>
        </div>

        <button onclick="hideNotif()" class="mt-4 w-full py-3 rounded-xl bg-slate-100 dark:bg-slate-800 font-bold text-sm">Close</button>
    </div>
</div>

<script>
if (localStorage.getItem('cc_dark') === '1') {
    document.documentElement.classList.add('dark');
    const dIcon = document.getElementById('dIcon');
    if (dIcon) dIcon.textContent = 'light_mode';
}

function showNotif() {
    const backdrop = document.getElementById('notifBackdrop');
    if (backdrop) backdrop.classList.add('show');
}

function hideNotif() {
    const backdrop = document.getElementById('notifBackdrop');
    if (backdrop) backdrop.classList.remove('show');
}
</script>

</body>
</html>