<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$announcementResult = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
$studentId = (int)$student['id'];
$dept      = $student['department'] ?? '';
$sem       = (int)($student['semester'] ?? 1);

// Attendance
$attendanceResult = $conn->query("SELECT COUNT(*) as total FROM attendance WHERE student_id = $studentId");
$totalClasses = $attendanceResult ? $attendanceResult->fetch_assoc()['total'] : 0;
$presentResult = $conn->query("SELECT COUNT(*) as present FROM attendance WHERE student_id = $studentId AND status = 'present'");
$presentClasses = $presentResult ? $presentResult->fetch_assoc()['present'] : 0;

// Upcoming exams count
$conn->query("CREATE TABLE IF NOT EXISTS exam_schedules (id INT AUTO_INCREMENT PRIMARY KEY, exam_name VARCHAR(200), branch_code VARCHAR(20), semester INT, subject_name VARCHAR(150), subject_code VARCHAR(30), exam_date DATE, start_time TIME, end_time TIME, room VARCHAR(50), exam_type ENUM('Internal','External','Practical','Viva') DEFAULT 'Internal', max_marks INT DEFAULT 100, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$upcomingExamsRes = $conn->prepare("SELECT COUNT(*) AS c FROM exam_schedules WHERE exam_date >= CURDATE() AND (branch_code=? OR branch_code IS NULL OR branch_code='') AND semester=?");
$upcomingExamsRes->bind_param("si", $dept, $sem);
$upcomingExamsRes->execute();
$upcomingExamsCount = $upcomingExamsRes->get_result()->fetch_assoc()['c'] ?? 0;
$upcomingExamsRes->close();

// Pending leave count
$conn->query("CREATE TABLE IF NOT EXISTS leave_requests (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, user_role ENUM('student','teacher') DEFAULT 'student', user_name VARCHAR(150), department VARCHAR(100), reason TEXT, leave_type VARCHAR(50), from_date DATE, to_date DATE, total_days INT DEFAULT 1, status ENUM('pending','approved','rejected') DEFAULT 'pending', admin_remark TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$leaveRes = $conn->prepare("SELECT COUNT(*) AS c FROM leave_requests WHERE user_id=? AND user_role='student' AND status='pending'");
$leaveRes->bind_param("i", $studentId);
$leaveRes->execute();
$pendingLeaveCount = $leaveRes->get_result()->fetch_assoc()['c'] ?? 0;
$leaveRes->close();

// Unread notifications
$conn->query("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(200), message TEXT, type ENUM('info','success','warning','danger') DEFAULT 'info', target_role ENUM('all','student','teacher','admin') DEFAULT 'all', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
$conn->query("CREATE TABLE IF NOT EXISTS notification_reads (id INT AUTO_INCREMENT PRIMARY KEY, notification_id INT, user_id INT, user_role VARCHAR(20) DEFAULT 'student', read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uniq_read (notification_id, user_id, user_role))");
$unreadNotifRes = $conn->prepare("SELECT COUNT(*) AS c FROM notifications n WHERE n.target_role IN ('all','student') AND NOT EXISTS (SELECT 1 FROM notification_reads nr WHERE nr.notification_id=n.id AND nr.user_id=? AND nr.user_role='student')");
$unreadNotifRes->bind_param("i", $studentId);
$unreadNotifRes->execute();
$unreadNotifCount = $unreadNotifRes->get_result()->fetch_assoc()['c'] ?? 0;
$unreadNotifRes->close();
$attendancePct = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100) : 0;
$attColor = $attendancePct >= 75 ? '#16a34a' : ($attendancePct >= 60 ? '#ca8a04' : '#dc2626');
$attBg    = $attendancePct >= 75 ? '#dcfce7' : ($attendancePct >= 60 ? '#fef9c3' : '#fee2e2');
$attLabel = $attendancePct >= 75 ? 'Good'    : ($attendancePct >= 60 ? 'Low'     : 'Critical');
$activeNav = 'home';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Student Dashboard CollegeConnect</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf","background-light":"#eef0ff","background-dark":"#0d0e1c"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
*{font-family:'Lexend',sans-serif}body{min-height:100dvh}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulseRed{0%,100%{opacity:1}50%{opacity:0.4}}
@keyframes topbarIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.topbar-enter{animation:topbarIn 0.35s ease both}
.fade-up-0{animation:fadeUp 0.45s 0.05s ease both}.fade-up-1{animation:fadeUp 0.45s 0.15s ease both}
.fade-up-2{animation:fadeUp 0.45s 0.25s ease both}.fade-up-3{animation:fadeUp 0.45s 0.35s ease both}
.fade-up-4{animation:fadeUp 0.45s 0.45s ease both}.fade-up-5{animation:fadeUp 0.45s 0.55s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.card{background:white;border-radius:1rem;box-shadow:0 1px 4px rgba(0,0,0,0.06);border:1px solid #e8eaf6;transition:transform 0.18s,box-shadow 0.18s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(67,73,207,0.12)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.btn-grad{background:linear-gradient(135deg,#4349cf,#7479f5);color:white;font-weight:700;transition:all 0.2s;box-shadow:0 4px 12px rgba(67,73,207,0.35)}
.btn-grad:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(67,73,207,0.45)}.btn-grad:active{transform:scale(0.96)}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.attRing{background:radial-gradient(closest-side,white 75%,transparent 76% 100%),conic-gradient(<?php echo $attColor;?> <?php echo $attendancePct;?>%,#e0e3ff 0)}
.dark .attRing{background:radial-gradient(closest-side,#1a1b2e 75%,transparent 76% 100%),conic-gradient(<?php echo $attColor;?> <?php echo $attendancePct;?>%,#2a2b45 0)}
.nav-tab.active{color:#4349cf}.nav-tab.active .nav-icon{font-variation-settings:'FILL' 1}
</style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-900 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<!-- HERO -->
<div class="px-4 pt-4 fade-up-1">
    <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/40 relative overflow-hidden">
        <div class="absolute -right-4 -top-4 opacity-10 pointer-events-none"><span class="material-symbols-outlined" style="font-size:120px;font-variation-settings:'FILL' 1;">auto_stories</span></div>
        <p class="text-white/60 text-xs font-semibold uppercase tracking-widest mb-1">Welcome back!</p>
        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($student['full_name'] ?? 'Student'); ?></h2>
        <div class="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-white/80 text-xs">
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">apartment</span><?php echo htmlspecialchars($student['department'] ?? 'Department not set'); ?></span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">layers</span>Sem <?php echo htmlspecialchars($student['semester'] ?? '-'); ?></span>
            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">badge</span><?php echo htmlspecialchars($student['student_roll_no'] ?? 'Roll not set'); ?></span>
        </div>
    </div>
</div>

<main class="px-4 py-4 space-y-5 pb-28">
<!-- STAT CARDS -->
<div class="grid grid-cols-3 gap-3 fade-up-2">
    <a href="student_attendance.php" class="card flex flex-col items-center gap-1 p-3 cursor-pointer active:scale-95">
        <div class="attRing w-14 h-14 rounded-full flex items-center justify-center">
            <span class="text-sm font-bold" style="color:<?php echo $attColor;?>"><?php echo $attendancePct;?>%</span>
        </div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Attendance</p>
        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full" style="background:<?php echo $attBg;?>;color:<?php echo $attColor;?>"><?php echo $attLabel;?></span>
    </a>
    <a href="student_course.php" class="card flex flex-col items-center gap-1 p-3 cursor-pointer active:scale-95">
        <div class="w-14 h-14 rounded-full bg-purple-50 dark:bg-purple-900/30 flex items-center justify-center">
            <span class="material-symbols-outlined text-purple-600 text-3xl" style="font-variation-settings:'FILL' 1;">menu_book</span>
        </div>
        <p class="text-lg font-bold text-purple-600">6</p>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subjects</p>
    </a>
    <div class="card flex flex-col items-center gap-1 p-3">
        <div class="w-14 h-14 rounded-full bg-green-50 dark:bg-green-900/30 flex items-center justify-center">
            <span class="material-symbols-outlined text-green-600 text-3xl" style="font-variation-settings:'FILL' 1;">grade</span>
        </div>
        <p class="text-lg font-bold text-green-600">85%</p>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Percentage</p>
    </div>
</div>

<!-- ALERT BANNER â€” Exams / Leave / Notifications -->
<?php if($upcomingExamsCount > 0 || $pendingLeaveCount > 0 || $unreadNotifCount > 0): ?>
<div class="space-y-2 fade-up-2">
<?php if($upcomingExamsCount > 0): ?>
<a href="student_exam_schedule.php" class="flex items-center gap-3 bg-pink-50 dark:bg-pink-900/20 border border-pink-200 dark:border-pink-800 rounded-xl px-3 py-2.5 active:scale-95 transition-all">
    <span class="material-symbols-outlined text-pink-600 text-xl" style="font-variation-settings:'FILL' 1;">quiz</span>
    <div class="flex-1">
        <p class="text-sm font-bold text-pink-800 dark:text-pink-300"><?= $upcomingExamsCount ?> upcoming exam<?= $upcomingExamsCount > 1 ? 's' : '' ?></p>
        <p class="text-xs text-pink-600/80">Tap to view exam schedule</p>
    </div>
    <span class="material-symbols-outlined text-pink-400 text-base">arrow_forward_ios</span>
</a>
<?php endif; ?>
<?php if($pendingLeaveCount > 0): ?>
<a href="student_leave.php" class="flex items-center gap-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl px-3 py-2.5 active:scale-95 transition-all">
    <span class="material-symbols-outlined text-amber-600 text-xl" style="font-variation-settings:'FILL' 1;">event_busy</span>
    <div class="flex-1">
        <p class="text-sm font-bold text-amber-800 dark:text-amber-300"><?= $pendingLeaveCount ?> leave request<?= $pendingLeaveCount > 1 ? 's' : '' ?> pending</p>
        <p class="text-xs text-amber-600/80">Waiting for approval</p>
    </div>
    <span class="material-symbols-outlined text-amber-400 text-base">arrow_forward_ios</span>
</a>
<?php endif; ?>
<?php if($unreadNotifCount > 0): ?>
<a href="student_notifications.php" class="flex items-center gap-3 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl px-3 py-2.5 active:scale-95 transition-all">
    <span class="material-symbols-outlined text-indigo-600 text-xl" style="font-variation-settings:'FILL' 1;">notifications_active</span>
    <div class="flex-1">
        <p class="text-sm font-bold text-indigo-800 dark:text-indigo-300"><?= $unreadNotifCount ?> unread notification<?= $unreadNotifCount > 1 ? 's' : '' ?></p>
        <p class="text-xs text-indigo-600/80">Tap to view</p>
    </div>
    <span class="material-symbols-outlined text-indigo-400 text-base">arrow_forward_ios</span>
</a>
<?php endif; ?>
</div>
<?php endif; ?>

<!-- QUICK ACTIONS -->
<div class="fade-up-3">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">apps</span>Quick Actions</h3>
    <div class="grid grid-cols-4 gap-2">
        <?php
        $qa=[
            ['href'=>'student_studymaterial.php','icon'=>'book','bg'=>'bg-blue-50 dark:bg-blue-900/30','tc'=>'text-blue-600','label'=>'Materials'],
            ['href'=>'student_attendance.php','icon'=>'assignment_turned_in','bg'=>'bg-green-50 dark:bg-green-900/30','tc'=>'text-green-600','label'=>'Attend.'],
            ['href'=>'student_course.php','icon'=>'menu_book','bg'=>'bg-purple-50 dark:bg-purple-900/30','tc'=>'text-purple-600','label'=>'Courses'],
            ['href'=>'student_timetable.php','icon'=>'calendar_month','bg'=>'bg-orange-50 dark:bg-orange-900/30','tc'=>'text-orange-600','label'=>'Timetable'],
            ['href'=>'student_results.php','icon'=>'workspace_premium','bg'=>'bg-yellow-50 dark:bg-yellow-900/30','tc'=>'text-yellow-600','label'=>'Results'],
            ['href'=>'student_assignments.php','icon'=>'edit_note','bg'=>'bg-red-50 dark:bg-red-900/30','tc'=>'text-red-600','label'=>'Tasks'],
            ['href'=>'student_exam_schedule.php','icon'=>'quiz','bg'=>'bg-pink-50 dark:bg-pink-900/30','tc'=>'text-pink-600','label'=>'Exams'],
            ['href'=>'student_leave.php','icon'=>'event_busy','bg'=>'bg-sky-50 dark:bg-sky-900/30','tc'=>'text-sky-600','label'=>'Leave'],
            ['href'=>'student_fees.php','icon'=>'receipt_long','bg'=>'bg-teal-50 dark:bg-teal-900/30','tc'=>'text-teal-600','label'=>'Fees'],
            ['href'=>'student_notifications.php','icon'=>'notifications','bg'=>'bg-amber-50 dark:bg-amber-900/30','tc'=>'text-amber-600','label'=>'Alerts'],
            ['href'=>'student_message.php','icon'=>'chat','bg'=>'bg-indigo-50 dark:bg-indigo-900/30','tc'=>'text-indigo-600','label'=>'Messages'],
        ];
        foreach($qa as $a): ?>
        <a href="<?php echo $a['href'];?>" class="card flex flex-col items-center gap-1.5 p-2.5 active:scale-95">
            <div class="w-11 h-11 rounded-full <?php echo $a['bg'];?> <?php echo $a['tc'];?> flex items-center justify-center">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;"><?php echo $a['icon'];?></span>
            </div>
            <span class="text-[9px] font-bold text-slate-600 dark:text-slate-300 text-center leading-tight"><?php echo $a['label'];?></span>
        </a>
        <?php endforeach;?>
    </div>
</div>

<!-- PROGRESS -->
<div class="card p-4 fade-up-3">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-sm flex items-center gap-2"><span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">trending_up</span>Academic Progress</h3>
        <span class="text-xs font-semibold text-green-600 bg-green-50 dark:bg-green-900/20 px-2 py-0.5 rounded-full">On Track</span>
    </div>
    <div class="space-y-4">
        <div>
            <div class="flex justify-between text-xs font-medium mb-1.5"><span class="text-slate-500">Credits Earned</span><span class="text-primary font-bold">24 / 30 Credits</span></div>
            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div id="credBar" class="h-full rounded-full bg-gradient-to-r from-primary to-indigo-400 transition-all duration-1000" style="width:0"></div></div>
        </div>
        <div>
            <div class="flex justify-between text-xs font-medium mb-1.5"><span class="text-slate-500">Attendance</span><span class="font-bold" style="color:<?php echo $attColor;?>"><?php echo $attendancePct;?>%</span></div>
            <div class="h-2 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden"><div id="attBar" class="h-full rounded-full transition-all duration-1000" style="width:0;background:<?php echo $attColor;?>"></div></div>
        </div>
    </div>
</div>

<!-- NOTICE BOARD -->
<div class="fade-up-4">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">campaign</span>Notice Board</h3>
    <div class="space-y-2">
        <?php if ($announcementResult && $announcementResult->num_rows > 0):
            while($notice = $announcementResult->fetch_assoc()): ?>
        <div class="card border-l-4 border-primary p-4">
            <h4 class="font-bold text-sm"><?php echo htmlspecialchars($notice['title']);?></h4>
            <p class="text-xs text-slate-500 mt-1"><?php echo htmlspecialchars($notice['message']);?></p>
            <p class="text-[10px] text-slate-400 mt-2 flex items-center gap-1"><span class="material-symbols-outlined text-xs">schedule</span><?php echo htmlspecialchars($notice['created_at']);?></p>
        </div>
        <?php endwhile; else: ?>
        <div class="card p-6 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
            <span class="material-symbols-outlined text-4xl text-slate-300">campaign</span>
            <p class="text-sm text-slate-400 mt-2">No announcements yet</p>
        </div>
        <?php endif;?>
    </div>
</div>

<!-- ASSIGNMENTS PREVIEW -->
<div class="fade-up-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="font-bold text-sm flex items-center gap-2"><span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">assignment</span>Assignments</h3>
        <a href="student_assignments.php" class="text-primary text-xs font-semibold">View all</a>
    </div>
    <div class="space-y-2">
        <div class="card flex items-center gap-3 p-3">
            <div class="h-10 w-10 bg-red-50 dark:bg-red-900/30 rounded-xl flex items-center justify-center text-red-500 shrink-0"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">terminal</span></div>
            <div class="flex-1 min-w-0"><p class="text-sm font-bold truncate">Data Structures: Lab 4</p><p class="text-[11px] text-red-500 font-medium flex items-center gap-1 mt-0.5"><span class="material-symbols-outlined text-xs">timer</span>Due in 2 days</p></div>
            <a href="student_assignments.php" class="btn-grad text-xs px-3 py-1.5 rounded-lg whitespace-nowrap">Submit</a>
        </div>
        <div class="card flex items-center gap-3 p-3">
            <div class="h-10 w-10 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center text-indigo-500 shrink-0"><span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">edit_note</span></div>
            <div class="flex-1 min-w-0"><p class="text-sm font-bold truncate">Technical Writing: Essay</p><p class="text-[11px] text-slate-500 mt-0.5">Due in 5 days</p></div>
            <a href="student_assignments.php" class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold px-3 py-1.5 rounded-lg">Open</a>
        </div>
    </div>
</div>

<div class="fade-up-5">
    <a href="../auth/logout.php" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-600 font-bold text-sm border border-red-100 dark:border-red-900/30 hover:bg-red-100 transition-colors">
        <span class="material-symbols-outlined">logout</span>Logout
    </a>
</div>
</main>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php" class="flex flex-col items-center gap-0.5 text-primary px-4 py-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">home</span><span class="text-[10px] font-bold">Home</span></a>
    <a href="student_message.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1 transition-colors"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="student_ai_predictor.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">psychology</span><span class="text-[10px] font-medium">AI</span></a>
    <a href="student_profile.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px] font-medium">Profile</span></a>
  </div>
</nav>

<?php include 'topbar_scripts.php'; ?>
<script>
window.addEventListener('load',()=>{
    setTimeout(()=>{
        document.getElementById('credBar').style.width='80%';
        document.getElementById('attBar').style.width='<?php echo $attendancePct;?>%';
    },400);
});
</script>
</body>
</html>