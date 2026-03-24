<?php
// topbar.php ÃƒÂ?Ã?â?šÂ?Ã?â??Â? reusable top bar with menu overlay
// Usage: include 'topbar.php'; after $settings and $student are set
// $activeNav: 'home' | 'attendance' | 'material' | 'messages' | 'profile' | 'timetable' etc.
$activeNav = $activeNav ?? '';
$siteName  = htmlspecialchars($settings['site_name'] ?? 'CollegeConnect');
$photo     = !empty($student['profile_photo'])
    ? "../uploads/profile_photos/" . htmlspecialchars($student['profile_photo'])
    : "https://ui-avatars.com/api/?name=" . urlencode($student['full_name'] ?? 'S') . "&background=4349cf&color=fff&bold=true&size=80";
?>
<!-- ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â? TOP BAR ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â? -->
<div class="sticky top-0 z-50 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md px-4 py-3 flex items-center justify-between border-b border-slate-200/70 dark:border-slate-800 topbar-enter">
    <!-- Left: hamburger + logo -->
    <div class="flex items-center gap-2">
        <button onclick="openMenu()" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors" aria-label="Menu">
            <span class="material-symbols-outlined text-slate-600 dark:text-slate-300 text-xl">menu</span>
        </button>
        <div class="flex items-center gap-2">
            <div class="hero-grad p-1.5 rounded-xl text-white shadow">
                <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">school</span>
            </div>
            <div>
                <p class="text-sm font-bold leading-none"><?php echo $siteName; ?></p>
                <p class="text-[10px] text-primary/60">Student Portal</p>
            </div>
        </div>
    </div>
    <!-- Right: dark, bell, avatar -->
    <div class="flex items-center gap-1">
        <button onclick="toggleDark()" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <span class="material-symbols-outlined text-slate-500 dark:text-slate-300 text-xl" id="darkIcon">dark_mode</span>
        </button>
        <a href="student_notifications.php" class="relative p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors block">
            <span class="material-symbols-outlined text-slate-500 dark:text-slate-300">notifications</span>
            <span class="absolute top-2 right-2 h-2 w-2 rounded-full bg-red-500 border border-white dark:border-slate-950 notif-pulse"></span>
        </a>
        <a href="student_profile.php" class="ml-1">
            <img src="<?php echo $photo; ?>" alt="Profile" class="w-9 h-9 rounded-full border-2 border-primary shadow-sm object-cover"/>
        </a>
    </div>
</div>

<!-- ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â? SLIDE-IN MENU ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â?ÃƒÂ?Ã?â??Â?Ã?Â? -->
<div id="menuBg" class="fixed inset-0 z-[60] hidden" onclick="closeMenu()">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div id="menuPanel" class="absolute left-0 top-0 bottom-0 w-72 bg-white dark:bg-slate-900 shadow-2xl flex flex-col -translate-x-full transition-transform duration-300" onclick="event.stopPropagation()">
        <!-- Menu Header -->
        <div class="hero-grad px-5 pt-10 pb-6 flex items-center gap-3">
            <img src="<?php echo $photo; ?>" alt="Profile" class="w-14 h-14 rounded-2xl border-3 border-white/30 shadow-lg object-cover"/>
            <div class="text-white">
                <p class="font-bold leading-tight"><?php echo htmlspecialchars($student['full_name'] ?? 'Student'); ?></p>
                <p class="text-white/70 text-xs mt-0.5"><?php echo htmlspecialchars($student['department'] ?? ''); ?>  Sem <?php echo htmlspecialchars($student['semester'] ?? ''); ?></p>
                <p class="text-white/60 text-[10px] mt-0.5"><?php echo htmlspecialchars($student['student_roll_no'] ?? ''); ?></p>
            </div>
        </div>
        <!-- Menu Items -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <?php
            $menuItems = [
                ['href'=>'student_dashboard.php',    'icon'=>'home',                 'label'=>'Dashboard',        'key'=>'home'],
                ['href'=>'student_profile.php',       'icon'=>'person',               'label'=>'My Profile',       'key'=>'profile'],
                ['divider'=>true,'label'=>'Academic'],
                ['href'=>'student_attendance.php',    'icon'=>'assignment_turned_in', 'label'=>'Attendance',       'key'=>'attendance'],
                ['href'=>'student_qr_scan.php',       'icon'=>'qr_code_scanner',      'label'=>'Scan QR Attendance','key'=>'qr_scan'],
                ['href'=>'student_course.php',        'icon'=>'menu_book',            'label'=>'Courses',          'key'=>'course'],
                ['href'=>'student_results.php',       'icon'=>'workspace_premium',    'label'=>'Results & Grades', 'key'=>'results'],
                ['href'=>'student_ai_predictor.php',  'icon'=>'psychology',           'label'=>'AI Predictor',     'key'=>'ai_predictor'],
                ['href'=>'student_assignments.php',   'icon'=>'edit_note',            'label'=>'Assignments',      'key'=>'assignments'],
                ['href'=>'student_studymaterial.php', 'icon'=>'book',                 'label'=>'Study Materials',  'key'=>'material'],
                ['divider'=>true,'label'=>'Schedule'],
                ['href'=>'student_timetable.php',      'icon'=>'calendar_month',       'label'=>'Timetable',          'key'=>'timetable'],
                ['href'=>'student_exam_schedule.php',  'icon'=>'quiz',                 'label'=>'Exam Schedule',      'key'=>'exams'],
                ['divider'=>true,'label'=>'Leave & Notices'],
                ['href'=>'student_leave.php',          'icon'=>'event_busy',           'label'=>'Leave Application',  'key'=>'leave'],
                ['href'=>'student_notifications.php',  'icon'=>'notifications',        'label'=>'Notifications',      'key'=>'notifications'],
                ['divider'=>true,'label'=>'Finance & Communication'],
                ['href'=>'student_fees.php',           'icon'=>'receipt_long',         'label'=>'Fees & Payments',    'key'=>'fees'],
                ['href'=>'student_message.php',        'icon'=>'chat',                 'label'=>'Messages',           'key'=>'messages'],
                ['divider'=>true,'label'=>'Account'],
                ['href'=>'student_profile.php',       'icon'=>'manage_accounts',      'label'=>'Account Settings', 'key'=>'settings'],
                ['href'=>'../auth/logout.php',        'icon'=>'logout',               'label'=>'Logout',           'key'=>'logout','danger'=>true],
            ];
            foreach ($menuItems as $item):
                if (!empty($item['divider'])): ?>
                <div class="pt-3 pb-1 px-2">
                    <p class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?php echo $item['label']; ?></p>
                </div>
                <?php continue; endif;
                $isActive = ($activeNav === $item['key']);
                $isDanger = !empty($item['danger']);
            ?>
            <a href="<?php echo $item['href']; ?>"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all <?php echo $isActive ? 'bg-primary/10 text-primary font-bold' : ($isDanger ? 'text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium'); ?>"
               onclick="closeMenu()">
                <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' <?php echo $isActive ? '1' : '0'; ?>;"><?php echo $item['icon']; ?></span>
                <span class="text-sm"><?php echo $item['label']; ?></span>
                <?php if($isActive): ?><span class="ml-auto w-1.5 h-1.5 rounded-full bg-primary"></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <!-- Menu Footer -->
        <div class="px-4 py-4 border-t border-slate-100 dark:border-slate-800">
            <p class="text-[10px] text-slate-400 text-center"><?php echo $siteName; ?> &copy; <?php echo date('Y'); ?></p>
        </div>
        <!-- Close btn -->
        <button onclick="closeMenu()" class="absolute top-4 right-4 p-2 bg-white/20 rounded-full text-white">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
    </div>
</div>

<!-- ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â NOTIFICATION DRAWER ÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚ÂÃƒÂ¢Ã¢â‚¬Â¢Ã‚Â -->
<div id="notifBg" class="fixed inset-0 z-[60] hidden" onclick="hideNotif()">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div id="notifPanel" class="absolute bottom-0 left-0 right-0 bg-white dark:bg-slate-900 rounded-t-3xl shadow-2xl p-5 max-h-[70vh] overflow-y-auto translate-y-full transition-transform duration-300" onclick="event.stopPropagation()">
        <div class="w-10 h-1 bg-slate-300 dark:bg-slate-700 rounded-full mx-auto mb-4"></div>
        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">notifications</span>Notifications
        </h3>
        <div class="space-y-3">
            <div class="flex gap-3 p-3 bg-primary/5 rounded-xl">
                <div class="w-10 h-10 rounded-full bg-primary/15 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-primary">edit_note</span>
                </div>
                <div>
                    <p class="text-sm font-semibold">Assignment Due Tomorrow</p>
                    <p class="text-xs text-slate-500 mt-0.5">Technical Writing Essay is due tomorrow.</p>
                    <p class="text-[10px] text-slate-400 mt-1">2 hours ago</p>
                </div>
            </div>
            <div class="flex gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-green-600">check_circle</span>
                </div>
                <div>
                    <p class="text-sm font-semibold">Attendance Marked</p>
                    <p class="text-xs text-slate-500 mt-0.5">Today's attendance has been recorded.</p>
                    <p class="text-[10px] text-slate-400 mt-1">5 hours ago</p>
                </div>
            </div>
        </div>
        <button onclick="hideNotif()" class="mt-4 w-full py-3 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold text-sm">Close</button>
    </div>
</div>