<?php
// â”€â”€ Session & DB â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../config/db.php';

// â”€â”€ Fetch QR session stats â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$totalSessions = $conn->query("SELECT COUNT(*) as c FROM qr_attendance_sessions")->fetch_assoc()['c'] ?? 0;
$activeSessions = $conn->query("SELECT COUNT(*) as c FROM qr_attendance_sessions WHERE is_active=1 AND expires_at > NOW()")->fetch_assoc()['c'] ?? 0;
$totalScans = $conn->query("SELECT COUNT(*) as c FROM qr_attendance_logs WHERE status='present'")->fetch_assoc()['c'] ?? 0;
$rejectedScans = $conn->query("SELECT COUNT(*) as c FROM qr_attendance_logs WHERE status='rejected'")->fetch_assoc()['c'] ?? 0;

// Recent sessions
$recentSessions = [];
$res = $conn->query("SELECT s.*, t.name as teacher_name, sub.subject_name,
    (SELECT COUNT(*) FROM qr_attendance_logs l WHERE l.session_id=s.id AND l.status='present') as scanned_count
    FROM qr_attendance_sessions s
    JOIN teachers t ON t.id=s.teacher_id
    JOIN subjects sub ON sub.id=s.subject_id
    ORDER BY s.created_at DESC LIMIT 20");
if ($res) while ($r = $res->fetch_assoc()) $recentSessions[] = $r;

// Active teachers
$activeTeachers = [];
$res2 = $conn->query("SELECT s.*, t.name as teacher_name, sub.subject_name,
    (SELECT COUNT(*) FROM qr_attendance_logs l WHERE l.session_id=s.id AND l.status='present') as scanned_count
    FROM qr_attendance_sessions s
    JOIN teachers t ON t.id=s.teacher_id
    JOIN subjects sub ON sub.id=s.subject_id
    WHERE s.is_active=1 AND s.expires_at > NOW()");
if ($res2) while ($r = $res2->fetch_assoc()) $activeTeachers[] = $r;
?>
<!DOCTYPE html>
<html lang="en" class="light">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>QR Attendance Overview â€“ Admin</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:'class',theme:{extend:{colors:{primary:'#4349cf'}}}}</script>
<style>
*{font-family:'Inter',sans-serif;}
body{background:#f8faff;}
.dark body{background:#0d0e1c;}
.card{background:white;border-radius:16px;border:1px solid #e8eaf6;box-shadow:0 2px 10px rgba(67,73,207,.05);}
.dark .card{background:#1a1b2e;border-color:#2a2b45;}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
.fu{animation:fadeUp .35s ease both}
.fu1{animation:fadeUp .35s .07s ease both}
.fu2{animation:fadeUp .35s .14s ease both}
</style>
</head>
<body class="p-6">

<div class="max-w-5xl mx-auto">

  <!-- Header -->
  <div class="flex items-center justify-between mb-6 fu">
    <div class="flex items-center gap-3">
      <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white shadow" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">qr_code_scanner</span>
      </div>
      <div>
        <h1 class="text-xl font-bold text-slate-800 dark:text-white">QR Attendance Overview</h1>
        <p class="text-xs text-slate-400">Monitor all QR-based attendance sessions</p>
      </div>
    </div>
    <a href="admin_dashboard.php" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">
      <span class="material-symbols-outlined text-sm">arrow_back</span>Dashboard
    </a>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 fu1">
    <?php
    $stats = [
      ['Total Sessions', $totalSessions, 'event_note', '#4349cf', '#eef0ff'],
      ['Active Now', $activeSessions, 'wifi_tethering', '#16a34a', '#dcfce7'],
      ['Total Scans', $totalScans, 'how_to_reg', '#7c3aed', '#ede9fe'],
      ['Proxy Blocked', $rejectedScans, 'location_off', '#dc2626', '#fee2e2'],
    ];
    foreach ($stats as [$label, $val, $icon, $color, $bg]):
    ?>
    <div class="card p-4">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:<?php echo $bg; ?>">
          <span class="material-symbols-outlined" style="color:<?php echo $color; ?>;font-variation-settings:'FILL' 1"><?php echo $icon; ?></span>
        </div>
        <div>
          <p class="text-2xl font-bold text-slate-800 dark:text-white leading-none"><?php echo $val; ?></p>
          <p class="text-xs text-slate-400 mt-0.5"><?php echo $label; ?></p>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Active Sessions -->
  <?php if ($activeTeachers): ?>
  <div class="card p-5 mb-5 fu2">
    <h2 class="font-bold text-slate-800 dark:text-white mb-3 flex items-center gap-2">
      <span class="relative flex h-2.5 w-2.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-green-500"></span>
      </span>
      Active QR Sessions
    </h2>
    <div class="space-y-2">
      <?php foreach ($activeTeachers as $s): ?>
      <div class="flex items-center justify-between p-3 rounded-xl" style="background:#f0fdf4;border:1px solid #bbf7d0">
        <div>
          <p class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($s['teacher_name']); ?></p>
          <p class="text-xs text-slate-500"><?php echo htmlspecialchars($s['subject_name']); ?> Â· Expires <?php echo date('h:i A', strtotime($s['expires_at'])); ?></p>
        </div>
        <span class="text-sm font-bold text-green-700 bg-green-100 px-3 py-1 rounded-full">
          <?php echo $s['scanned_count']; ?> scanned
        </span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Recent Sessions Table -->
  <div class="card overflow-hidden fu2">
    <div class="p-5 border-b border-slate-100 dark:border-slate-800">
      <h2 class="font-bold text-slate-800 dark:text-white">Recent QR Sessions</h2>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-xs font-bold uppercase tracking-wide text-slate-400 bg-slate-50 dark:bg-slate-800/50">
            <th class="text-left px-5 py-3">Teacher</th>
            <th class="text-left px-5 py-3">Subject</th>
            <th class="text-left px-5 py-3">Date</th>
            <th class="text-left px-5 py-3">Scanned</th>
            <th class="text-left px-5 py-3">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentSessions as $s): ?>
          <tr class="border-t border-slate-100 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/30 transition">
            <td class="px-5 py-3 font-medium text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($s['teacher_name']); ?></td>
            <td class="px-5 py-3 text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($s['subject_name']); ?></td>
            <td class="px-5 py-3 text-slate-500"><?php echo date('d M Y', strtotime($s['date'])); ?></td>
            <td class="px-5 py-3 font-bold text-indigo-600"><?php echo $s['scanned_count']; ?> students</td>
            <td class="px-5 py-3">
              <?php if ($s['is_active'] && strtotime($s['expires_at']) > time()): ?>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold text-green-700 bg-green-100">Active</span>
              <?php else: ?>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold text-slate-500 bg-slate-100">Ended</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($recentSessions)): ?>
          <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No QR sessions yet</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
<script>
// Dark mode
const on = localStorage.getItem('cc_dark')==='1';
document.documentElement.classList.toggle('dark', on);
// Auto refresh every 30s
setTimeout(()=>location.reload(), 30000);
</script>
</body>
</html>
