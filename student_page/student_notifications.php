<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$activeNav = 'notifications';
$studentId = (int)($student['id'] ?? 0);

// ── Ensure tables ────────────────────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  message TEXT,
  type ENUM('info','success','warning','danger') DEFAULT 'info',
  target_role ENUM('all','student','teacher','admin') DEFAULT 'all',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS notification_reads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  notification_id INT NOT NULL,
  user_id INT NOT NULL,
  user_role VARCHAR(20) DEFAULT 'student',
  read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_read (notification_id, user_id, user_role)
)");

// Mark as read if requested
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $nid = (int)$_GET['mark_read'];
    $conn->query("INSERT IGNORE INTO notification_reads (notification_id, user_id, user_role) VALUES ($nid, $studentId, 'student')");
    header("Location: student_notifications.php");
    exit();
}
if (isset($_GET['mark_all_read'])) {
    $allIds = $conn->query("SELECT id FROM notifications WHERE target_role IN ('all','student')");
    if ($allIds) while ($row = $allIds->fetch_assoc()) {
        $nid = (int)$row['id'];
        $conn->query("INSERT IGNORE INTO notification_reads (notification_id, user_id, user_role) VALUES ($nid, $studentId, 'student')");
    }
    header("Location: student_notifications.php");
    exit();
}

// Fetch all notifications for student
$notifs = [];
$res = $conn->prepare("
  SELECT n.*, 
    (SELECT id FROM notification_reads WHERE notification_id=n.id AND user_id=? AND user_role='student' LIMIT 1) AS read_id
  FROM notifications n
  WHERE n.target_role IN ('all','student')
  ORDER BY n.created_at DESC
");
$res->bind_param("i", $studentId);
$res->execute();
$rows = $res->get_result();
while ($r = $rows->fetch_assoc()) $notifs[] = $r;
$res->close();

$unread = count(array_filter($notifs, fn($n) => !$n['read_id']));
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Notifications – CollegeConnect</title>
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
.fu{animation:fadeUp .4s ease both}
.fu1{animation:fadeUp .4s .07s ease both}
.fu2{animation:fadeUp .4s .14s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.ncard{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06);transition:all .18s}
.ncard.unread{border-color:#c7d2fe;background:#f5f7ff}
.dark .ncard{background:#1a1b2e;border-color:#2a2b45}
.dark .ncard.unread{background:#1e2040;border-color:#3730a3}
.nav-tab.active{color:#4349cf}
.nav-tab.active .nav-icon{font-variation-settings:'FILL' 1}
.dot-info{background:#3b82f6}
.dot-success{background:#22c55e}
.dot-warning{background:#f59e0b}
.dot-danger{background:#ef4444}
.icon-info{background:#eff6ff;color:#2563eb}
.icon-success{background:#f0fdf4;color:#16a34a}
.icon-warning{background:#fffbeb;color:#d97706}
.icon-danger{background:#fef2f2;color:#dc2626}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-800 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<main class="px-4 pt-4 pb-28 space-y-4">

  <!-- Header -->
  <div class="flex items-center justify-between fu">
    <div>
      <h2 class="text-lg font-bold">Notifications</h2>
      <?php if ($unread > 0): ?>
      <p class="text-xs text-primary font-semibold"><?= $unread ?> unread</p>
      <?php else: ?>
      <p class="text-xs text-slate-400">All caught up!</p>
      <?php endif; ?>
    </div>
    <?php if ($unread > 0): ?>
    <a href="?mark_all_read=1" class="text-xs font-bold text-primary bg-primary/10 hover:bg-primary/20 px-3 py-1.5 rounded-xl transition-colors">Mark all read</a>
    <?php endif; ?>
  </div>

  <!-- Notifications list -->
  <div class="space-y-3 fu1">
    <?php if (empty($notifs)): ?>
    <div class="ncard p-10 text-center">
      <span class="material-symbols-outlined text-6xl text-primary/20 block mb-3" style="font-variation-settings:'FILL' 1">notifications_off</span>
      <p class="font-bold">No notifications yet</p>
      <p class="text-xs text-slate-400 mt-1">You're all caught up! Check back later.</p>
    </div>
    <?php else: foreach ($notifs as $n):
      $isUnread = !$n['read_id'];
      $icons = ['info'=>'info','success'=>'check_circle','warning'=>'warning','danger'=>'error'];
      $icon = $icons[$n['type']] ?? 'info';
      $timeAgo = function($dt) {
        $diff = time() - strtotime($dt);
        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff/60) . ' min ago';
        if ($diff < 86400) return floor($diff/3600) . ' hr ago';
        return floor($diff/86400) . ' day' . (floor($diff/86400) > 1 ? 's' : '') . ' ago';
      };
    ?>
    <a href="?mark_read=<?= $n['id'] ?>" class="ncard <?= $isUnread ? 'unread' : '' ?> p-4 flex gap-3 items-start block">
      <div class="icon-<?= $n['type'] ?> w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1"><?= $icon ?></span>
      </div>
      <div class="flex-1 min-w-0">
        <div class="flex items-start justify-between gap-2">
          <p class="text-sm font-bold <?= $isUnread ? 'text-primary' : '' ?>"><?= htmlspecialchars($n['title']) ?></p>
          <?php if ($isUnread): ?>
          <span class="w-2 h-2 rounded-full dot-<?= $n['type'] ?> shrink-0 mt-1.5"></span>
          <?php endif; ?>
        </div>
        <?php if ($n['message']): ?>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed"><?= htmlspecialchars($n['message']) ?></p>
        <?php endif; ?>
        <p class="text-[10px] text-slate-400 mt-1.5"><?= $timeAgo($n['created_at']) ?></p>
      </div>
    </a>
    <?php endforeach; endif; ?>
  </div>

</main>

<!-- Bottom nav -->
<nav class="fixed bottom-0 left-0 right-0 bg-white/90 dark:bg-slate-950/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2 z-40">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">home</span><span class="text-[10px] font-bold">Home</span></a>
    <a href="student_attendance.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">assignment_turned_in</span><span class="text-[10px] font-medium">Attend.</span></a>
    <a href="student_studymaterial.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">book</span><span class="text-[10px] font-medium">Material</span></a>
    <a href="student_message.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">message</span><span class="text-[10px] font-medium">Messages</span></a>
    <a href="student_profile.php" class="nav-tab text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 rounded-xl"><span class="material-symbols-outlined nav-icon text-xl">person</span><span class="text-[10px] font-medium">Profile</span></a>
  </div>
</nav>

<?php include 'topbar_scripts.php'; ?>
</body>
</html>