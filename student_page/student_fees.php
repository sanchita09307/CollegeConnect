<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$activeNav = 'fees';

$title = ucwords(str_replace('_', ' ', str_replace('student_', '', basename(__FILE__, '.php'))));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo htmlspecialchars($title); ?> - CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<style>
* { font-family: 'Lexend', sans-serif; }
body { min-height: 100dvh; background: #eef0ff; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}

.fu { animation: fadeUp 0.4s ease both; }

.card {
    background: white;
    border-radius: 1rem;
    border: 1px solid #e8eaf6;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.hero-grad {
    background: linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%);
}

.topbar-enter { animation: topbarIn 0.35s ease both; }
.notif-pulse { animation: pulseRed 2s infinite; }

@keyframes topbarIn {
    from { opacity: 0; transform: translateY(-8px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes pulseRed {
    0%,100% { opacity:1; }
    50% { opacity:.4; }
}
</style>
</head>

<body class="bg-[#eef0ff] text-slate-900">

<div class="sticky top-0 z-40 bg-white/85 backdrop-blur-md px-4 py-3 flex items-center gap-3 border-b border-slate-200/70">
    <a href="student_dashboard.php" class="p-1.5 rounded-xl hover:bg-slate-100">
        <span class="material-symbols-outlined">arrow_back</span>
    </a>
    <h1 class="font-bold text-base"><?php echo htmlspecialchars($title); ?></h1>
</div>

<main class="px-4 py-8">
    <div class="card p-8 text-center fu">
        <span class="material-symbols-outlined text-6xl text-primary/30" style="font-variation-settings:'FILL' 1;">construction</span>
        <h2 class="font-bold text-xl mt-4">Coming Soon!</h2>
        <p class="text-sm text-slate-500 mt-2">This section is under development. Check back soon!</p>

        <a href="student_dashboard.php" class="inline-flex items-center gap-2 mt-6 px-4 py-2.5 rounded-xl font-bold text-sm text-white" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
            <span class="material-symbols-outlined text-sm">home</span>
            Back to Dashboard
        </a>
    </div>
</main>

<nav class="fixed bottom-0 left-0 right-0 bg-white/90 backdrop-blur-md border-t border-slate-200 px-2 py-2">
    <div class="max-w-xl mx-auto flex justify-around">
        
        <a href="student_dashboard.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-[#4349cf] px-3 py-1">
            <span class="material-symbols-outlined text-xl">home</span>
            <span class="text-[10px]">Home</span>
        </a>

        <a href="student_attendance.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-[#4349cf] px-3 py-1">
            <span class="material-symbols-outlined text-xl">assignment_turned_in</span>
            <span class="text-[10px]">Attend.</span>
        </a>

        <a href="student_studymaterial.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-[#4349cf] px-3 py-1">
            <span class="material-symbols-outlined text-xl">book</span>
            <span class="text-[10px]">Material</span>
        </a>

        <a href="student_message.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-[#4349cf] px-3 py-1">
            <span class="material-symbols-outlined text-xl">message</span>
            <span class="text-[10px]">Messages</span>
        </a>

        <a href="student_profile.php" class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-[#4349cf] px-3 py-1">
            <span class="material-symbols-outlined text-xl">person</span>
            <span class="text-[10px]">Profile</span>
        </a>

    </div>
</nav>

<?php include 'topbar_scripts.php'; ?>
</body>
</html>