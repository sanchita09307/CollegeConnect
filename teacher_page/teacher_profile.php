<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$teacherId = (int)$teacher['id'];

// Handle photo upload
$uploadMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_photo'])) {
    $file = $_FILES['profile_photo'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $newName = time() . '_' . $teacherId . '.' . $ext;
            $dest = __DIR__ . '/../uploads/profile_photos/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $conn->query("UPDATE teachers SET profile_photo='$newName' WHERE id=$teacherId");
                $teacher['profile_photo'] = $newName;
                $uploadMsg = 'Photo updated!';
            }
        }
    }
}

$photo = !empty($teacher['profile_photo'])
    ? "../uploads/profile_photos/" . htmlspecialchars($teacher['profile_photo'])
    : "https://ui-avatars.com/api/?name=" . urlencode($teacher['name'] ?? 'T') . "&background=4349cf&color=fff&bold=true&size=200";

$subjectsCount = $conn->query("SELECT COUNT(*) AS t FROM subjects WHERE teacher_id=$teacherId")->fetch_assoc()['t'] ?? 0;
$dept = $conn->real_escape_string($teacher['department'] ?? '');
$studentsCount = $conn->query("SELECT COUNT(*) AS t FROM students WHERE department='$dept' AND status='approved'")->fetch_assoc()['t'] ?? 0;

$subjects = $conn->query("SELECT * FROM subjects WHERE teacher_id=$teacherId ORDER BY subject_name LIMIT 10");
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Teacher Profile - CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
* { font-family: 'Lexend', sans-serif; }
body { min-height: 100dvh; background: #eef0ff; }
.dark body { background: #0d0e1c; }
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fu0{animation:fadeUp 0.4s 0.05s ease both}.fu1{animation:fadeUp 0.4s 0.15s ease both}.fu2{animation:fadeUp 0.4s 0.25s ease both}.fu3{animation:fadeUp 0.4s 0.35s ease both}.fu4{animation:fadeUp 0.4s 0.45s ease both}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 4px rgba(0,0,0,0.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.btn-grad{background:linear-gradient(135deg,#4349cf,#7479f5);color:white;font-weight:700;box-shadow:0 4px 12px rgba(67,73,207,0.3);transition:all 0.2s}
.btn-grad:hover{transform:translateY(-1px)}.btn-grad:active{transform:scale(0.96)}
.info-row{display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid #f1f3ff}
.dark .info-row{border-color:#2a2b45}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "My Profile";
$activePage = "profile";
include __DIR__ . '/teacher_topbar.php';
?>



<main class="px-4 py-4 space-y-4 pb-28">

<?php if($uploadMsg): ?>
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm font-medium fu0 flex items-center gap-2">
    <span class="material-symbols-outlined text-sm">check_circle</span><?php echo htmlspecialchars($uploadMsg); ?>
</div>
<?php endif; ?>

<!-- PROFILE HERO -->
<div class="card overflow-hidden fu1">
    <div class="hero-grad h-24 relative">
        <div class="absolute -bottom-14 left-1/2 -translate-x-1/2">
            <div class="relative">
                <img src="<?php echo $photo; ?>" alt="Profile" class="w-28 h-28 rounded-full border-4 border-white dark:border-slate-900 shadow-xl object-cover"/>
                <label for="photoUpload" class="absolute bottom-1 right-1 w-8 h-8 bg-primary rounded-full flex items-center justify-center cursor-pointer shadow-md hover:bg-indigo-600 transition-colors border-2 border-white">
                    <span class="material-symbols-outlined text-white text-sm">photo_camera</span>
                </label>
            </div>
        </div>
    </div>
    <form method="POST" enctype="multipart/form-data" id="photoForm">
        <input type="file" id="photoUpload" name="profile_photo" accept="image/*" class="hidden" onchange="document.getElementById('photoForm').submit()"/>
    </form>
    <div class="pt-16 pb-5 px-5 text-center">
        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($teacher['name'] ?? 'Teacher'); ?></h2>
        <p class="text-sm text-primary font-semibold mt-0.5"><?php echo htmlspecialchars($teacher['designation'] ?? ''); ?></p>
        <p class="text-xs text-slate-400 mt-0.5"><?php echo htmlspecialchars($teacher['department'] ?? ''); ?></p>
        <div class="flex justify-center gap-2 mt-3">
            <?php
            $sv = $teacher['status'] ?? 'pending';
            $sb = $sv === 'approved' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400';
            ?>
            <span class="text-xs font-bold px-3 py-1 rounded-full <?php echo $sb; ?> capitalize"><?php echo htmlspecialchars($sv); ?></span>
        </div>
    </div>
</div>

<!-- STATS -->
<div class="grid grid-cols-3 gap-3 fu2">
    <div class="card p-3 text-center">
        <p class="text-xl font-bold text-primary"><?php echo $subjectsCount; ?></p>
        <p class="text-[10px] text-slate-400 font-semibold uppercase">Subjects</p>
    </div>
    <div class="card p-3 text-center">
        <p class="text-xl font-bold text-green-600"><?php echo $studentsCount; ?></p>
        <p class="text-[10px] text-slate-400 font-semibold uppercase">Students</p>
    </div>
    <div class="card p-3 text-center">
        <p class="text-xl font-bold text-orange-500">3</p>
        <p class="text-[10px] text-slate-400 font-semibold uppercase">Pending</p>
    </div>
</div>

<!-- PERSONAL INFO -->
<div class="card px-4 py-2 fu2">
    <h3 class="font-bold text-sm py-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">badge</span>Teacher Information
    </h3>
    <?php
    $rows = [
        ['icon'=>'person','label'=>'Full Name','val'=>$teacher['name'] ?? '-'],
        ['icon'=>'stars','label'=>'Designation','val'=>$teacher['designation'] ?? '-'],
        ['icon'=>'apartment','label'=>'Department','val'=>$teacher['department'] ?? '-'],
        ['icon'=>'school','label'=>'Qualification','val'=>$teacher['qualification'] ?? '-'],
        ['icon'=>'mail','label'=>'Email','val'=>$teacher['email'] ?? '-'],
        ['icon'=>'call','label'=>'Phone','val'=>$teacher['phone'] ?? '-'],
        ['icon'=>'fingerprint','label'=>'Employee ID','val'=>$teacher['employee_id'] ?? '-'],
        ['icon'=>'calendar_today','label'=>'Joined','val'=>isset($teacher['created_at']) ? date('d M Y', strtotime($teacher['created_at'])) : '-'],
    ];
    foreach($rows as $r): ?>
    <div class="info-row">
        <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings:'FILL' 1;"><?php echo $r['icon']; ?></span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-[10px] text-slate-400 font-semibold uppercase"><?php echo $r['label']; ?></p>
            <p class="font-medium text-sm truncate"><?php echo htmlspecialchars($r['val']); ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- EDIT PROFILE -->
<a href="teacher_complete_profile.php" class="btn-grad flex items-center justify-center gap-2 w-full py-3 rounded-xl text-sm fu3">
    <span class="material-symbols-outlined text-sm">edit</span>Edit / Update Profile
</a>

<!-- ASSIGNED SUBJECTS -->
<?php if($subjects && $subjects->num_rows > 0): ?>
<div class="fu3">
    <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">library_books</span>Assigned Subjects
    </h3>
    <div class="space-y-2">
    <?php
    $colMap = ['bg-blue-50 dark:bg-blue-900/30 text-blue-600','bg-green-50 dark:bg-green-900/30 text-green-600','bg-purple-50 dark:bg-purple-900/30 text-purple-600','bg-orange-50 dark:bg-orange-900/30 text-orange-600','bg-teal-50 dark:bg-teal-900/30 text-teal-600','bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600'];
    $ci = 0;
    while($sub = $subjects->fetch_assoc()): $col = $colMap[$ci++ % count($colMap)]; ?>
    <div class="card flex items-center gap-3 p-3">
        <div class="w-10 h-10 rounded-xl <?php echo $col; ?> flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">menu_book</span>
        </div>
        <div class="flex-1 min-w-0">
            <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($sub['subject_name']); ?></p>
            <p class="text-xs text-slate-400"><?php echo htmlspecialchars($sub['branch_code'] ?? ''); ?> Sem <?php echo $sub['semester']; ?> <?php echo htmlspecialchars($sub['subject_code'] ?? ''); ?></p>
        </div>
        <a href="teacher_attendence.php?subject=<?php echo $sub['id']; ?>" class="text-xs font-bold bg-primary/10 text-primary px-2.5 py-1.5 rounded-lg hover:bg-primary/20 transition-colors whitespace-nowrap">Attend.</a>
    </div>
    <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<!-- ACCOUNT ACTIONS -->
<div class="card divide-y divide-slate-100 dark:divide-slate-800 fu4">
    <a href="teacher_complete_profile.php" class="flex items-center gap-3 px-4 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">manage_accounts</span>
        <span class="flex-1 font-medium text-sm">Complete Profile</span>
        <span class="material-symbols-outlined text-slate-300">chevron_right</span>
    </a>
    <a href="teacher_attendence.php" class="flex items-center gap-3 px-4 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <span class="material-symbols-outlined text-green-600" style="font-variation-settings:'FILL' 1;">assignment_turned_in</span>
        <span class="flex-1 font-medium text-sm">Take Attendance</span>
        <span class="material-symbols-outlined text-slate-300">chevron_right</span>
    </a>
    <a href="teacher_portal.php" class="flex items-center gap-3 px-4 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <span class="material-symbols-outlined text-indigo-600" style="font-variation-settings:'FILL' 1;">cloud_upload</span>
        <span class="flex-1 font-medium text-sm">Upload Study Material</span>
        <span class="material-symbols-outlined text-slate-300">chevron_right</span>
    </a>
    <a href="../auth/logout.php" class="flex items-center gap-3 px-4 py-4 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
        <span class="material-symbols-outlined text-red-500">logout</span>
        <span class="flex-1 font-medium text-sm text-red-600">Logout</span>
        <span class="material-symbols-outlined text-red-300">chevron_right</span>
    </a>
</div>

</main>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="teacher_dashboard.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px] font-medium">Home</span></a>
    <a href="teacher_students.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">groups</span><span class="text-[10px] font-medium">Students</span></a>
    <a href="teacher_ai_atrisk.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-4 py-1 transition-colors"><span class="material-symbols-outlined text-xl">crisis_alert</span><span class="text-[10px] font-medium">AI Risk</span></a>
    <a href="teacher_profile.php" class="flex flex-col items-center gap-0.5 text-primary px-4 py-1"><span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">person</span><span class="text-[10px] font-bold">Profile</span></a>
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
