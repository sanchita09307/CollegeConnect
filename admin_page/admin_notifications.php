<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);

/* -------------------- Create notifications table if not exists -------------------- */
$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        message TEXT,
        type ENUM('info','success','warning','danger') DEFAULT 'info',
        target_role ENUM('all','student','teacher','admin') DEFAULT 'all',
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$msg = '';
$msgType = '';

/* -------------------- Helper: log admin activity safely -------------------- */
function logAdminActivity($conn, $userId, $activityType, $activityText) {
    $check = $conn->query("SHOW COLUMNS FROM activity_logs LIKE 'activity_type'");
    if ($check && $check->num_rows > 0) {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_role, activity_type, activity_text) VALUES (?, 'admin', ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $activityType, $activityText);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_role, activity_text) VALUES (?, 'admin', ?)");
        if ($stmt) {
            $stmt->bind_param("is", $userId, $activityText);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/* -------------------- Handle form actions -------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['send_notif'])) {
        $title   = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $type    = $_POST['type'] ?? 'info';
        $target  = $_POST['target_role'] ?? 'all';

        $allowedTypes   = ['info', 'success', 'warning', 'danger'];
        $allowedTargets = ['all', 'student', 'teacher', 'admin'];

        if (!in_array($type, $allowedTypes, true)) {
            $type = 'info';
        }

        if (!in_array($target, $allowedTargets, true)) {
            $target = 'all';
        }

        if ($title !== '') {
            $stmt = $conn->prepare("INSERT INTO notifications (title, message, type, target_role) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssss", $title, $message, $type, $target);

                if ($stmt->execute()) {
                    $msg = 'Notification sent successfully!';
                    $msgType = 'success';

                    $adminId = (int)($_SESSION['user_id'] ?? 0);
                    logAdminActivity($conn, $adminId, 'notification', 'Sent notification: ' . $title);
                } else {
                    $msg = 'Failed to send notification.';
                    $msgType = 'error';
                }
                $stmt->close();
            } else {
                $msg = 'Database prepare error.';
                $msgType = 'error';
            }
        } else {
            $msg = 'Title is required.';
            $msgType = 'error';
        }
    }

    if (isset($_POST['delete_notif'])) {
        $id = (int)($_POST['notif_id'] ?? 0);

        if ($id > 0) {
            $titleForLog = '';

            $stmtGet = $conn->prepare("SELECT title FROM notifications WHERE id = ?");
            if ($stmtGet) {
                $stmtGet->bind_param("i", $id);
                $stmtGet->execute();
                $resGet = $stmtGet->get_result();
                if ($rowGet = $resGet->fetch_assoc()) {
                    $titleForLog = $rowGet['title'];
                }
                $stmtGet->close();
            }

            $stmtDel = $conn->prepare("DELETE FROM notifications WHERE id = ?");
            if ($stmtDel) {
                $stmtDel->bind_param("i", $id);
                $stmtDel->execute();
                $stmtDel->close();
            }

            $checkReads = $conn->query("SHOW TABLES LIKE 'notification_reads'");
            if ($checkReads && $checkReads->num_rows > 0) {
                $stmtReadDel = $conn->prepare("DELETE FROM notification_reads WHERE notification_id = ?");
                if ($stmtReadDel) {
                    $stmtReadDel->bind_param("i", $id);
                    $stmtReadDel->execute();
                    $stmtReadDel->close();
                }
            }

            $msg = 'Notification deleted.';
            $msgType = 'warning';

            $adminId = (int)($_SESSION['user_id'] ?? 0);
            logAdminActivity($conn, $adminId, 'notification_delete', 'Deleted notification: ' . $titleForLog);
        }
    }
}

/* -------------------- Fetch notifications -------------------- */
$notifs = [];
$res = $conn->query("SELECT * FROM notifications ORDER BY created_at DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $notifs[] = $r;
    }
}

/* -------------------- Time ago helper -------------------- */
function timeAgoFormat($dt) {
    $diff = time() - strtotime($dt);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' day ago';
    return date('d M Y', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>Notifications â€“ Admin</title>

<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

<script>
tailwind.config = {
  darkMode: "class",
  theme: {
    extend: {
      colors: { primary: "#4349cf" },
      fontFamily: { sans: ["Plus Jakarta Sans", "sans-serif"] }
    }
  }
}
</script>

<style>
*{font-family:'Plus Jakarta Sans',sans-serif}
body{background:#f0f2ff;min-height:100dvh}
.dark body{background:#0d0e1a}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fu{animation:fadeUp .4s ease both}
.card{background:#fff;border-radius:1rem;border:1px solid rgba(67,73,207,.08);box-shadow:0 2px 12px rgba(67,73,207,.06)}
.dark .card{background:#161728;border-color:rgba(67,73,207,.15)}
.sidebar{background:#fff;border-right:1px solid rgba(67,73,207,.08);width:260px;flex-shrink:0}
.dark .sidebar{background:#161728}
.sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#64748b;transition:all .2s;text-decoration:none}
.sidebar-link:hover{background:rgba(67,73,207,.06);color:#4349cf}
.sidebar-link.active{background:#4349cf;color:#fff}
.btn-primary{background:linear-gradient(135deg,#4349cf,#7479f5);color:#fff;font-weight:700;border-radius:.75rem;padding:.65rem 1.25rem;font-size:.875rem;border:none;cursor:pointer}
.fill-icon{font-variation-settings:'FILL' 1}
.icon-info{background:#eff6ff;color:#2563eb}
.icon-success{background:#f0fdf4;color:#16a34a}
.icon-warning{background:#fffbeb;color:#d97706}
.icon-danger{background:#fef2f2;color:#dc2626}
.target-all{background:#ede9fe;color:#7c3aed;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.target-student{background:#eff6ff;color:#2563eb;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.target-teacher{background:#f0fdf4;color:#16a34a;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.target-admin{background:#fef3c7;color:#b45309;font-size:10px;font-weight:700;padding:2px 8px;border-radius:99px}
.bottom-nav{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-top:1px solid rgba(67,73,207,.08)}
.nav-item{display:flex;flex-direction:column;align-items:center;gap:2px;color:#94a3b8;font-size:9px;font-weight:700;text-transform:uppercase;text-decoration:none}
.nav-item.active{color:#4349cf}
input,select,textarea{font-family:'Plus Jakarta Sans',sans-serif!important}
</style>
</head>
<body class="dark:text-slate-100">
<div class="flex min-h-screen">

  <aside class="sidebar hidden lg:flex flex-col sticky top-0 h-screen p-5 overflow-y-auto">
    <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800 mb-4">
      <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white">
        <span class="material-symbols-outlined fill-icon text-xl">school</span>
      </div>
      <div>
        <h1 class="font-bold text-slate-800 dark:text-white leading-none"><?= htmlspecialchars($settings['site_name'] ?? 'CollegeConnect') ?></h1>
        <p class="text-xs text-slate-400">Admin Central</p>
      </div>
    </div>

    <nav class="flex flex-col gap-1 flex-1">
      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1">Main</p>
      <a href="admin_dashboard.php" class="sidebar-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a>
      <a href="admin_users.php" class="sidebar-link"><span class="material-symbols-outlined">group</span>Users</a>
      <a href="admin_academics.php" class="sidebar-link"><span class="material-symbols-outlined">school</span>Academics</a>

      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">Management</p>
      <a href="admin_leaves.php" class="sidebar-link"><span class="material-symbols-outlined">event_busy</span>Leave Requests</a>
      <a href="admin_exams.php" class="sidebar-link"><span class="material-symbols-outlined">quiz</span>Exam Schedule</a>
      <a href="admin_notifications.php" class="sidebar-link active"><span class="material-symbols-outlined">notifications</span>Notifications</a>
      <a href="admin_announcements.php" class="sidebar-link"><span class="material-symbols-outlined">campaign</span>Announcements</a>

      <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 px-3 mb-1 mt-4">System</p>
      <a href="admin_settings.php" class="sidebar-link"><span class="material-symbols-outlined">settings</span>Settings</a>
    </nav>
  </aside>

  <div class="flex-1 flex flex-col min-w-0">
    <header class="sticky top-0 z-40 bg-white/80 dark:bg-[#0d0e1a]/80 backdrop-blur-md border-b border-primary/8 px-4 md:px-6 py-4 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-800 dark:text-white">Notifications</h2>
        <p class="text-xs text-slate-400"><?= count($notifs) ?> total sent</p>
      </div>
    </header>

    <main class="flex-1 p-4 md:p-6 pb-24 lg:pb-8 max-w-5xl w-full mx-auto space-y-5">

      <?php if ($msg): ?>
        <div class="card p-3 flex items-center gap-3 <?= $msgType === 'success' ? 'border-green-200 bg-green-50' : ($msgType === 'warning' ? 'border-amber-200 bg-amber-50' : 'border-red-200 bg-red-50') ?> fu">
          <span class="material-symbols-outlined text-lg fill-icon <?= $msgType === 'success' ? 'text-green-600' : ($msgType === 'warning' ? 'text-amber-600' : 'text-red-600') ?>">
            <?= $msgType === 'success' ? 'check_circle' : 'warning' ?>
          </span>
          <p class="text-sm font-medium"><?= htmlspecialchars($msg) ?></p>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <div class="lg:col-span-1">
          <div class="card p-5 fu">
            <h3 class="font-bold text-sm mb-4 flex items-center gap-2">
              <span class="material-symbols-outlined text-primary fill-icon text-base">send</span>
              Send Notification
            </h3>

            <form method="POST" class="space-y-4">
              <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Title *</label>
                <input type="text" name="title" required placeholder="Notification title" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30"/>
              </div>

              <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Message</label>
                <textarea name="message" rows="3" placeholder="Optional message bodyâ€¦" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30 resize-none"></textarea>
              </div>

              <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Type</label>
                <select name="type" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30">
                  <option value="info">â„¹ Info</option>
                  <option value="success">âœ“ Success</option>
                  <option value="warning">âš  Warning</option>
                  <option value="danger">âœ• Danger / Alert</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1.5">Send To</label>
                <select name="target_role" class="w-full border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2.5 text-sm bg-white dark:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30">
                  <option value="all">Everyone</option>
                  <option value="student">Students Only</option>
                  <option value="teacher">Teachers Only</option>
                  <option value="admin">Admins Only</option>
                </select>
              </div>

              <button type="submit" name="send_notif" class="btn-primary w-full py-3">
                Send Notification
              </button>
            </form>
          </div>
        </div>

        <div class="lg:col-span-2 space-y-3 fu">
          <h3 class="font-bold text-sm flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-500 text-base">history</span>
            Sent Notifications
          </h3>

          <?php if (empty($notifs)): ?>
            <div class="card p-10 text-center">
              <span class="material-symbols-outlined text-5xl text-slate-200 fill-icon block mb-3">notifications_off</span>
              <p class="font-bold text-slate-500">No notifications sent yet</p>
              <p class="text-xs text-slate-400 mt-1">Use the form to send your first notification</p>
            </div>
          <?php else: ?>
            <?php foreach ($notifs as $n): ?>
              <?php
                $icons = [
                  'info' => 'info',
                  'success' => 'check_circle',
                  'warning' => 'warning',
                  'danger' => 'error'
                ];
                $icon = $icons[$n['type']] ?? 'info';
              ?>
              <div class="card p-4">
                <div class="flex items-start gap-3">
                  <div class="icon-<?= htmlspecialchars($n['type']) ?> w-9 h-9 rounded-xl flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-base fill-icon"><?= $icon ?></span>
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2">
                      <p class="font-bold text-sm"><?= htmlspecialchars($n['title']) ?></p>
                      <span class="target-<?= htmlspecialchars($n['target_role']) ?> capitalize shrink-0"><?= htmlspecialchars($n['target_role']) ?></span>
                    </div>

                    <?php if (!empty($n['message'])): ?>
                      <p class="text-xs text-slate-500 mt-0.5 leading-relaxed"><?= htmlspecialchars($n['message']) ?></p>
                    <?php endif; ?>

                    <p class="text-[10px] text-slate-400 mt-1.5"><?= htmlspecialchars(timeAgoFormat($n['created_at'])) ?></p>
                  </div>

                  <form method="POST" class="shrink-0">
                    <input type="hidden" name="notif_id" value="<?= (int)$n['id'] ?>"/>
                    <button type="submit" name="delete_notif" onclick="return confirm('Delete this notification?')" class="p-1.5 rounded-xl hover:bg-red-50 text-red-400 hover:text-red-600 transition-colors">
                      <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </main>
  </div>
</div>

<nav class="bottom-nav lg:hidden fixed bottom-0 left-0 right-0 z-50 px-4 py-3 flex justify-around" style="padding-bottom:max(.75rem,env(safe-area-inset-bottom));">
  <a href="admin_dashboard.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">home</span><p>Home</p>
  </a>
  <a href="admin_users.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">group</span><p>Users</p>
  </a>
  <a href="admin_ai_overview.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">psychology</span><p>AI</p>
  </a>
  <a href="admin_settings.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">settings</span><p>Settings</p>
  </a>
</nav>
</body>
</html>