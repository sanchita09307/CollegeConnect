<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$teacherId = (int)$teacher['id'];

$stmt = $conn->prepare("SELECT * FROM subjects WHERE teacher_id = ? ORDER BY semester ASC, subject_name ASC");
$stmt->bind_param("i", $teacherId);
$stmt->execute();
$classes = $stmt->get_result();

// Group by semester
$grouped = [];
while ($c = $classes->fetch_assoc()) {
    $grouped[$c['semester']][] = $c;
}
ksort($grouped);

$totalSubjects = array_sum(array_map('count', $grouped));

$colorMap = [
    'bg-blue-50 dark:bg-blue-900/30 text-blue-600',
    'bg-green-50 dark:bg-green-900/30 text-green-600',
    'bg-purple-50 dark:bg-purple-900/30 text-purple-600',
    'bg-orange-50 dark:bg-orange-900/30 text-orange-600',
    'bg-teal-50 dark:bg-teal-900/30 text-teal-600',
    'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600',
    'bg-red-50 dark:bg-red-900/30 text-red-600',
    'bg-yellow-50 dark:bg-yellow-900/30 text-yellow-600',
];
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>My Classes - CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
* { font-family: 'Lexend', sans-serif; }
body { min-height: 100dvh; background: #eef0ff; }
.dark body { background: #0d0e1c; }
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fu0{animation:fadeUp 0.4s 0.05s ease both}.fu1{animation:fadeUp 0.4s 0.15s ease both}.fu2{animation:fadeUp 0.4s 0.2s ease both}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 4px rgba(0,0,0,0.06);transition:transform 0.18s,box-shadow 0.18s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(67,73,207,0.1)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-grad{background:linear-gradient(135deg,#4349cf,#7479f5)}
.btn-grad{background:linear-gradient(135deg,#4349cf,#7479f5);color:white;font-weight:700;box-shadow:0 3px 10px rgba(67,73,207,0.3);transition:all 0.2s;font-size:11px;padding:7px 12px;border-radius:0.6rem}
.btn-grad:hover{transform:translateY(-1px)}.btn-grad:active{transform:scale(0.96)}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "My Classes";
$activePage = "classes";
include __DIR__ . '/teacher_topbar.php';
?>



<!-- TEACHER INFO BANNER -->
<div class="px-4 pt-4 fu1">
    <div class="hero-grad rounded-2xl p-4 text-white flex items-center gap-4 shadow-lg shadow-indigo-300/30">
        <?php
        $photo = !empty($teacher['profile_photo'])
            ? "../uploads/profile_photos/" . htmlspecialchars($teacher['profile_photo'])
            : "https://ui-avatars.com/api/?name=" . urlencode($teacher['name'] ?? 'T') . "&background=ffffff&color=4349cf&bold=true&size=80";
        ?>
        <img src="<?php echo $photo; ?>" class="w-14 h-14 rounded-full border-2 border-white/40 object-cover" alt=""/>
        <div>
            <p class="font-bold text-base"><?php echo htmlspecialchars($teacher['name'] ?? ''); ?></p>
            <p class="text-white/70 text-xs"><?php echo htmlspecialchars($teacher['designation'] ?? ''); ?> <?php echo htmlspecialchars($teacher['department'] ?? ''); ?></p>
            <p class="text-white/60 text-xs mt-0.5"><?php echo $totalSubjects; ?> subject<?php echo $totalSubjects !== 1 ? 's' : ''; ?> assigned</p>
        </div>
    </div>
</div>

<main class="px-4 py-4 space-y-5 pb-28 fu2">

<?php if (empty($grouped)): ?>
<div class="card p-10 text-center border-2 border-dashed border-slate-200 dark:border-slate-700 mt-4">
    <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">library_books</span>
    <h3 class="font-bold text-slate-600 dark:text-slate-300 mt-3">No Subjects Assigned</h3>
    <p class="text-sm text-slate-400 mt-1">Contact your admin to get subjects assigned.</p>
</div>
<?php else: ?>

<?php
$colIdx = 0;
foreach ($grouped as $sem => $subs): ?>

<div>
    <!-- Semester Header -->
    <div class="flex items-center gap-3 mb-3">
        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <span class="text-primary font-bold text-sm"><?php echo $sem; ?></span>
        </div>
        <div>
            <h3 class="font-bold text-sm">Semester <?php echo $sem; ?></h3>
            <p class="text-[10px] text-slate-400"><?php echo count($subs); ?> subject<?php echo count($subs) !== 1 ? 's' : ''; ?></p>
        </div>
    </div>

    <div class="space-y-2">
    <?php foreach($subs as $sub):
        $col = $colorMap[$colIdx % count($colorMap)]; $colIdx++;
        // Count students for this subject's semester/branch
        $br = $conn->real_escape_string($sub['branch_code'] ?? '');
        $subSem = (int)$sub['semester'];
        $studentCnt = $conn->query("SELECT COUNT(*) as c FROM students WHERE semester=$subSem AND status='approved'")->fetch_assoc()['c'] ?? 0;
        // Today's attendance
        $today = date('Y-m-d');
        $todayAtt = $conn->query("SELECT COUNT(*) as c FROM attendance WHERE subject_id={$sub['id']} AND date='$today'")->fetch_assoc()['c'] ?? 0;
    ?>
    <div class="card p-4">
        <div class="flex items-start gap-3">
            <div class="w-11 h-11 rounded-xl <?php echo $col; ?> flex items-center justify-center shrink-0">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">menu_book</span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-sm"><?php echo htmlspecialchars($sub['subject_name']); ?></p>
                <p class="text-xs text-slate-400 mt-0.5">
                    <?php echo htmlspecialchars($sub['subject_code'] ?? ''); ?>
                     Branch: <?php echo htmlspecialchars($sub['branch_code'] ?? 'All'); ?>
                     Sem <?php echo $sub['semester']; ?>
                </p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span class="text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-500 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">groups</span><?php echo $studentCnt; ?> students
                    </span>
                    <?php if($todayAtt > 0): ?>
                    <span class="text-[10px] font-semibold bg-green-100 dark:bg-green-900/30 text-green-600 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">check_circle</span>Today: <?php echo $todayAtt; ?> marked
                    </span>
                    <?php else: ?>
                    <span class="text-[10px] font-semibold bg-orange-100 dark:bg-orange-900/30 text-orange-600 px-2 py-0.5 rounded-full flex items-center gap-1">
                        <span class="material-symbols-outlined text-xs">pending</span>Not marked today
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="flex gap-2 mt-3">
            <a href="teacher_attendence.php?subject=<?php echo $sub['id']; ?>" class="btn-grad flex-1 flex items-center justify-center gap-1.5">
                <span class="material-symbols-outlined text-xs">assignment_turned_in</span>Take Attendance
            </a>
            <a href="teacher_portal.php?subject=<?php echo $sub['id']; ?>" class="flex-1 flex items-center justify-center gap-1.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold py-2 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <span class="material-symbols-outlined text-xs">cloud_upload</span>Upload Material
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<?php endforeach; ?>
<?php endif; ?>

</main>

<!-- FAB -->
<a href="teacher_portal.php" class="fixed bottom-20 right-4 z-30 w-14 h-14 rounded-full bg-primary text-white flex items-center justify-center shadow-lg shadow-primary/40 hover:bg-indigo-600 transition-all hover:scale-110 active:scale-95" title="Upload Material">
    <span class="material-symbols-outlined">cloud_upload</span>
</a>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
    <div class="max-w-xl mx-auto flex justify-around">
        <a href="teacher_dashboard.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
            <span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span>
        </a>
        <a href="teacher_classes.php" class="text-primary flex flex-col items-center gap-0.5 px-3 py-1">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">menu_book</span><span class="text-[10px] font-bold">Classes</span>
        </a>
        <a href="teacher_attendence.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
            <span class="material-symbols-outlined text-xl">assignment_turned_in</span><span class="text-[10px]">Attend.</span>
        </a>
        <a href="teacher_message.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
            <span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span>
        </a>
        <a href="teacher_profile.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition-colors">
            <span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span>
        </a>
    </div>
</nav>

<script>
if (localStorage.getItem('cc_dark') === '1') {
    document.documentElement.classList.add('dark');
    document.getElementById('dIcon').textContent = 'light_mode';
}
</script>
</body>
</html>
