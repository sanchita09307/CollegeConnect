<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
$tab    = $_GET['tab']    ?? 'students';
$search = trim($_GET['search'] ?? '');

$totalStudents   = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'] ?? 0;
$totalTeachers   = $conn->query("SELECT COUNT(*) AS total FROM teachers")->fetch_assoc()['total'] ?? 0;
$pendingStudents = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$pendingTeachers = $conn->query("SELECT COUNT(*) AS total FROM teachers WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$totalPending    = $pendingStudents + $pendingTeachers;

$studentSql = "SELECT id, full_name, email, department, batch_year, semester, phone, status, created_at FROM students";
$teacherSql = "SELECT id, name, email, department, designation, phone, status, created_at FROM teachers";

if ($search !== '') {
    $studentSql .= " WHERE full_name LIKE ? OR email LIKE ? OR department LIKE ? OR phone LIKE ?";
    $teacherSql .= " WHERE name LIKE ? OR email LIKE ? OR department LIKE ? OR designation LIKE ? OR phone LIKE ?";
}
$studentSql .= " ORDER BY id DESC";
$teacherSql .= " ORDER BY id DESC";

if ($search !== '') {
    $sl = "%{$search}%";
    $ss = $conn->prepare($studentSql); $ss->bind_param("ssss",$sl,$sl,$sl,$sl); $ss->execute(); $res=$ss->get_result();
    $ts = $conn->prepare($teacherSql); $ts->bind_param("sssss",$sl,$sl,$sl,$sl,$sl); $ts->execute(); $res2=$ts->get_result();
} else {
    $res  = $conn->query($studentSql);
    $res2 = $conn->query($teacherSql);
}

// Fetch ALL rows into plain PHP arrays so we can loop them multiple times
// (once for desktop table, once for mobile cards ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â MySQL result sets are forward-only)
$studentRows = [];
if ($res) { while ($r = $res->fetch_assoc()) $studentRows[] = $r; }

$teacherRows = [];
if ($res2) { while ($r = $res2->fetch_assoc()) $teacherRows[] = $r; }

$activeRows = $tab === 'students' ? $studentRows : $teacherRows;

function statusClass($s){
    return match($s){
        'approved'=>'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        'rejected'=>'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        default   =>'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    };
}
function statusDot($s){
    return match($s){
        'approved'=>'bg-emerald-500','rejected'=>'bg-red-500',default=>'bg-amber-500'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Users ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â <?= htmlspecialchars($settings['site_name']) ?></title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf","primary-dark":"#2630ed","bg-light":"#f0f2ff","bg-dark":"#0d0e1a"},fontFamily:{sans:["Plus Jakarta Sans","sans-serif"]}}}}
</script>
<style>
  *,*::before,*::after{box-sizing:border-box}
  body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0f2ff;min-height:100dvh}
  @keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
  @keyframes fadeIn{from{opacity:0}to{opacity:1}}
  .anim-fade-up{animation:fadeUp .45s ease both}
  .anim-fade-in{animation:fadeIn .3s ease both}
  .d1{animation-delay:.05s}.d2{animation-delay:.1s}.d3{animation-delay:.15s}.d4{animation-delay:.2s}.d5{animation-delay:.25s}

  .card{background:#fff;border-radius:1rem;border:1px solid rgba(67,73,207,.08);box-shadow:0 2px 12px rgba(67,73,207,.06)}
  .dark .card{background:#161728;border-color:rgba(67,73,207,.15)}

  .stat-card{padding:1.25rem;transition:transform .2s,box-shadow .2s}
  .stat-card:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(67,73,207,.12)}

  .sidebar{background:#fff;border-right:1px solid rgba(67,73,207,.08);width:260px}
  .dark .sidebar{background:#161728;border-color:rgba(67,73,207,.15)}
  .sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.75rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#64748b;transition:all .2s}
  .sidebar-link:hover{background:rgba(67,73,207,.06);color:#4349cf}
  .sidebar-link.active{background:#4349cf;color:#fff;box-shadow:0 4px 12px rgba(67,73,207,.3)}

  .tab-btn{padding:.5rem 1.25rem;border-radius:.625rem;font-size:.875rem;font-weight:600;transition:all .2s;white-space:nowrap}
  .tab-btn.active{background:#4349cf;color:#fff;box-shadow:0 4px 12px rgba(67,73,207,.25)}
  .tab-btn:not(.active){background:rgba(67,73,207,.06);color:#64748b}
  .tab-btn:not(.active):hover{background:rgba(67,73,207,.12);color:#4349cf}

  /* table */
  .data-table{width:100%;border-collapse:collapse}
  .data-table th{padding:.875rem 1rem;text-align:left;font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#94a3b8;background:rgba(67,73,207,.03);border-bottom:1px solid rgba(67,73,207,.07)}
  .data-table td{padding:.875rem 1rem;font-size:.875rem;border-bottom:1px solid rgba(67,73,207,.04);vertical-align:middle}
  .data-table tr:last-child td{border-bottom:none}
  .data-table tbody tr{transition:background .15s}
  .data-table tbody tr:hover{background:rgba(67,73,207,.03)}
  .dark .data-table th{background:rgba(67,73,207,.08);border-color:rgba(67,73,207,.1)}
  .dark .data-table td{border-color:rgba(67,73,207,.06)}
  .dark .data-table tbody tr:hover{background:rgba(67,73,207,.06)}

  /* mobile card view */
  .user-card{background:#fff;border-radius:.875rem;padding:1rem;border:1px solid rgba(67,73,207,.08);box-shadow:0 1px 6px rgba(67,73,207,.05);transition:transform .2s,box-shadow .2s}
  .user-card:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(67,73,207,.1)}
  .dark .user-card{background:#161728;border-color:rgba(67,73,207,.15)}

  .avatar{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;background:linear-gradient(135deg,#4349cf,#2630ed);color:#fff;flex-shrink:0}

  .status-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .625rem;border-radius:99px;font-size:.7rem;font-weight:700}
  .status-dot{width:6px;height:6px;border-radius:50%;flex-shrink:0}

  .action-btn{padding:.375rem .75rem;border-radius:.5rem;font-size:.75rem;font-weight:600;transition:all .15s}
  .btn-approve{background:rgba(34,197,94,.1);color:#15803d}
  .btn-approve:hover{background:#22c55e;color:#fff}
  .btn-reject{background:rgba(239,68,68,.08);color:#b91c1c}
  .btn-reject:hover{background:#ef4444;color:#fff}
  .dark .btn-approve{background:rgba(34,197,94,.15);color:#86efac}
  .dark .btn-reject{background:rgba(239,68,68,.12);color:#fca5a5}

  /* search */
  .search-wrap{position:relative}
  .search-wrap input{padding:.75rem 1rem .75rem 2.75rem;border:1.5px solid rgba(67,73,207,.15);border-radius:.75rem;background:#fff;font-family:inherit;font-size:.875rem;width:100%;outline:none;transition:border-color .2s,box-shadow .2s}
  .search-wrap input:focus{border-color:#4349cf;box-shadow:0 0 0 3px rgba(67,73,207,.12)}
  .dark .search-wrap input{background:#161728;border-color:rgba(67,73,207,.2);color:#fff}
  .search-wrap .icon{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none}

  .bottom-nav{background:rgba(255,255,255,.9);backdrop-filter:blur(12px);border-top:1px solid rgba(67,73,207,.08)}
  .nav-item{display:flex;flex-direction:column;align-items:center;gap:2px;color:#94a3b8;transition:color .2s}
  .nav-item.active{color:#4349cf}
  .nav-item span{font-size:22px}
  .nav-item p{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}

  .material-symbols-outlined{font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24}
  .fill-icon{font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24}
  ::-webkit-scrollbar{width:4px;height:4px}
  ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
</style>
</head>
<body class="dark:bg-bg-dark dark:text-slate-100">

<div class="flex min-h-screen">

  <!-- Sidebar -->
  <aside class="sidebar hidden lg:flex flex-col sticky top-0 h-screen p-5 shrink-0">
    <div class="flex items-center gap-3 pb-6 border-b border-slate-100 dark:border-slate-800 mb-4">
      <?php if(!empty($settings['site_logo'])): ?>
        <img src="../uploads/site/<?= htmlspecialchars($settings['site_logo']) ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200" alt="Logo"/>
      <?php else: ?>
        <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-primary/30">
          <?= strtoupper(substr($settings['site_name'],0,1)) ?>
        </div>
      <?php endif; ?>
      <div>
        <h1 class="font-bold text-slate-800 dark:text-white leading-none"><?= htmlspecialchars($settings['site_name']) ?></h1>
        <p class="text-xs text-slate-400 mt-0.5">Admin Panel</p>
      </div>
    </div>
    <nav class="flex flex-col gap-1 flex-1">
      <a href="admin_dashboard.php" class="sidebar-link"><span class="material-symbols-outlined">dashboard</span>Dashboard</a>
      <a href="admin_users.php" class="sidebar-link active"><span class="material-symbols-outlined fill-icon">group</span>User Management</a>
      <a href="admin_academics.php" class="sidebar-link"><span class="material-symbols-outlined">school</span>Academics</a>
      <a href="admin_ai_overview.php" class="sidebar-link"><span class="material-symbols-outlined">psychology</span>AI Overview</a>
      <a href="admin_qr_overview.php" class="sidebar-link"><span class="material-symbols-outlined">qr_code_scanner</span>QR Attendance</a>
      <a href="admin_settings.php" class="sidebar-link"><span class="material-symbols-outlined">settings</span>Settings</a>
    </nav>
    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
      <a href="../auth/logout.php" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-300 transition-colors text-sm font-medium">
        <span class="material-symbols-outlined text-lg">logout</span>Logout
      </a>
    </div>
  </aside>

  <!-- Main -->
  <div class="flex-1 flex flex-col min-w-0">

    <!-- Mobile header -->
    <header class="lg:hidden sticky top-0 z-50 bg-white/90 dark:bg-bg-dark/90 backdrop-blur-md border-b border-primary/8 px-4 py-3 flex items-center justify-between">
      <h1 class="text-base font-bold">User Management</h1>
      <div class="flex items-center gap-1">
        <button class="p-2 rounded-full hover:bg-primary/8 text-slate-500 transition-colors">
          <span class="material-symbols-outlined text-xl">notifications</span>
        </button>
      </div>
    </header>

    <!-- Desktop header -->
    <header class="hidden lg:flex sticky top-0 z-40 bg-white/80 dark:bg-bg-dark/80 backdrop-blur-md border-b border-primary/8 px-6 py-4 items-center justify-between">
      <div>
        <h2 class="text-xl font-bold text-slate-800 dark:text-white">User Management</h2>
        <p class="text-xs text-slate-400 mt-0.5">Manage students, teachers &amp; approvals</p>
      </div>
      <form method="GET" action="admin_users.php" class="flex items-center gap-3">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>"/>
        <div class="search-wrap w-64">
          <span class="material-symbols-outlined icon text-lg">search</span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users"/>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold hover:bg-primary-dark transition-colors shadow-md shadow-primary/20">Search</button>
      </form>
    </header>

    <main class="flex-1 p-4 md:p-6 pb-28 lg:pb-8 space-y-5 max-w-7xl w-full mx-auto">

      <!-- Mobile search -->
      <form method="GET" action="admin_users.php" class="lg:hidden flex gap-2 anim-fade-up d1">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>"/>
        <div class="search-wrap flex-1">
          <span class="material-symbols-outlined icon text-lg">search</span>
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users"/>
        </div>
        <button type="submit" class="px-4 py-2.5 bg-primary text-white rounded-xl text-sm font-semibold">Go</button>
      </form>

      <!-- Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        <?php
        $statItems = [
          ['label'=>'Total Students','val'=>$totalStudents,'icon'=>'school','color'=>'blue'],
          ['label'=>'Total Teachers','val'=>$totalTeachers,'icon'=>'person','color'=>'indigo'],
          ['label'=>'Pending Students','val'=>$pendingStudents,'icon'=>'pending_actions','color'=>'amber'],
          ['label'=>'Total Pending','val'=>$totalPending,'icon'=>'hourglass_top','color'=>'orange'],
        ];
        $di=2;
        foreach($statItems as $s):
          $c=$s['color'];
          $bgMap=['blue'=>'bg-blue-50 dark:bg-blue-900/20 text-blue-600','indigo'=>'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600','amber'=>'bg-amber-50 dark:bg-amber-900/20 text-amber-600','orange'=>'bg-orange-50 dark:bg-orange-900/20 text-orange-600'];
        ?>
        <div class="card stat-card anim-fade-up d<?= $di ?>">
          <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 rounded-xl <?= $bgMap[$c] ?> flex items-center justify-center">
              <span class="material-symbols-outlined text-xl"><?= $s['icon'] ?></span>
            </div>
          </div>
          <p class="text-2xl md:text-3xl font-extrabold"><?= $s['val'] ?></p>
          <p class="text-xs text-slate-500 mt-1 font-medium"><?= $s['label'] ?></p>
        </div>
        <?php $di++; endforeach; ?>
      </div>

      <!-- Table card -->
      <div class="card anim-fade-up d5">
        <!-- Tabs + info bar -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <a href="admin_users.php?tab=students&search=<?= urlencode($search) ?>" class="tab-btn <?= $tab==='students'?'active':'' ?>">
              Students <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full <?= $tab==='students'?'bg-white/20':'bg-primary/10 text-primary' ?>"><?= $totalStudents ?></span>
            </a>
            <a href="admin_users.php?tab=teachers&search=<?= urlencode($search) ?>" class="tab-btn <?= $tab==='teachers'?'active':'' ?>">
              Teachers <span class="ml-1 text-[10px] px-1.5 py-0.5 rounded-full <?= $tab==='teachers'?'bg-white/20':'bg-primary/10 text-primary' ?>"><?= $totalTeachers ?></span>
            </a>
          </div>
          <?php if($search): ?>
          <p class="text-xs text-slate-400">Results for: <span class="font-semibold text-slate-600 dark:text-slate-300">"<?= htmlspecialchars($search) ?>"</span></p>
          <?php endif; ?>
        </div>

        <!-- DESKTOP TABLE -->
        <div class="hidden md:block overflow-x-auto">
          <?php if($tab==='students'): ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Student</th><th>Department</th><th>Batch</th><th>Sem</th><th>Phone</th><th>Status</th><th>Joined</th><th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if(count($studentRows)>0):
              foreach($studentRows as $row):
                $initials = strtoupper(substr($row['full_name']??'?',0,1));
            ?>
            <tr class="anim-fade-in">
              <td>
                <div class="flex items-center gap-3">
                  <div class="avatar text-sm"><?= $initials ?></div>
                  <div>
                    <p class="font-semibold text-slate-800 dark:text-white"><?= htmlspecialchars($row['full_name']??'N/A') ?></p>
                    <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($row['email']) ?></p>
                  </div>
                </div>
              </td>
              <td class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['department']??'-') ?></td>
              <td class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['batch_year']??'-') ?></td>
              <td class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['semester']??'-') ?></td>
              <td class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['phone']??'-') ?></td>
              <td>
                <span class="status-badge <?= statusClass($row['status']) ?>">
                  <span class="status-dot <?= statusDot($row['status']) ?>"></span>
                  <?= ucfirst(htmlspecialchars($row['status'])) ?>
                </span>
              </td>
              <td class="text-slate-400 text-xs"><?= !empty($row['created_at'])?date('d M Y',strtotime($row['created_at'])):'-' ?></td>
              <td>
                <div class="flex items-center justify-center gap-2">
                  <?php if($row['status']!=='approved'): ?>
                  <a href="approve_user.php?type=student&id=<?= $row['id'] ?>" class="action-btn btn-approve">Approve</a>
                  <?php endif; ?>
                  <?php if($row['status']!=='rejected'): ?>
                  <a href="reject_user.php?type=student&id=<?= $row['id'] ?>" class="action-btn btn-reject">Reject</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8" class="py-12 text-center text-slate-400">
              <span class="material-symbols-outlined text-4xl block mb-2">manage_search</span>No students found.
            </td></tr>
            <?php endif; ?>
            </tbody>
          </table>

          <?php else: /* teachers */ ?>
          <table class="data-table">
            <thead>
              <tr>
                <th>Teacher</th><th>Department</th><th>Designation</th><th>Phone</th><th>Status</th><th>Joined</th><th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php if(count($teacherRows)>0):
              foreach($teacherRows as $row):
                $initials = strtoupper(substr($row['name']??'?',0,1));
            ?>
            <tr class="anim-fade-in">
              <td>
                <div class="flex items-center gap-3">
                  <div class="avatar text-sm"><?= $initials ?></div>
                  <div>
                    <p class="font-semibold text-slate-800 dark:text-white"><?= htmlspecialchars($row['name']??'N/A') ?></p>
                    <p class="text-xs text-slate-400 mt-0.5"><?= htmlspecialchars($row['email']) ?></p>
                  </div>
                </div>
              </td>
              <td class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['department']??'-') ?></td>
              <td class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['designation']??'-') ?></td>
              <td class="text-slate-600 dark:text-slate-300"><?= htmlspecialchars($row['phone']??'-') ?></td>
              <td>
                <span class="status-badge <?= statusClass($row['status']) ?>">
                  <span class="status-dot <?= statusDot($row['status']) ?>"></span>
                  <?= ucfirst(htmlspecialchars($row['status'])) ?>
                </span>
              </td>
              <td class="text-slate-400 text-xs"><?= !empty($row['created_at'])?date('d M Y',strtotime($row['created_at'])):'-' ?></td>
              <td>
                <div class="flex items-center justify-center gap-2">
                  <?php if($row['status']!=='approved'): ?>
                  <a href="approve_user.php?type=teacher&id=<?= $row['id'] ?>" class="action-btn btn-approve">Approve</a>
                  <?php endif; ?>
                  <?php if($row['status']!=='rejected'): ?>
                  <a href="reject_user.php?type=teacher&id=<?= $row['id'] ?>" class="action-btn btn-reject">Reject</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="py-12 text-center text-slate-400">
              <span class="material-symbols-outlined text-4xl block mb-2">manage_search</span>No teachers found.
            </td></tr>
            <?php endif; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

        <!-- MOBILE CARDS -->
        <div class="md:hidden p-4 space-y-3">
          <?php
          if(count($activeRows) > 0):
            foreach($activeRows as $row):
              $name = $tab==='students' ? ($row['full_name']??'N/A') : ($row['name']??'N/A');
              $init = strtoupper(substr($name,0,1));
              $sub  = $tab==='students' ? ($row['department']??'-').' Batch '.($row['batch_year']??'-') : ($row['designation']??'-');
              $type = $tab==='students' ? 'student' : 'teacher';
          ?>
          <div class="user-card anim-fade-in">
            <div class="flex items-start gap-3">
              <div class="avatar"><?= $init ?></div>
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <div class="min-w-0">
                    <p class="font-semibold text-slate-800 dark:text-white truncate"><?= htmlspecialchars($name) ?></p>
                    <p class="text-xs text-slate-400 truncate"><?= htmlspecialchars($row['email']) ?></p>
                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($sub) ?></p>
                  </div>
                  <span class="status-badge <?= statusClass($row['status']) ?> shrink-0">
                    <span class="status-dot <?= statusDot($row['status']) ?>"></span>
                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                  </span>
                </div>
                <?php if($row['status']!=='approved' || $row['status']!=='rejected'): ?>
                <div class="flex gap-2 mt-3">
                  <?php if($row['status']!=='approved'): ?>
                  <a href="approve_user.php?type=<?= $type ?>&id=<?= $row['id'] ?>" class="action-btn btn-approve flex-1 text-center">ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ Approve</a>
                  <?php endif; ?>
                  <?php if($row['status']!=='rejected'): ?>
                  <a href="reject_user.php?type=<?= $type ?>&id=<?= $row['id'] ?>" class="action-btn btn-reject flex-1 text-center">ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢ Reject</a>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; else: ?>
          <div class="py-10 text-center text-slate-400">
            <span class="material-symbols-outlined text-4xl block mb-2">manage_search</span>
            No <?= $tab ?> found.
          </div>
          <?php endif; ?>
        </div>

      </div><!-- /card -->
    </main>
  </div>
</div>

<!-- Bottom nav -->
<nav class="bottom-nav lg:hidden fixed bottom-0 left-0 right-0 z-50 px-4 py-3 flex justify-around" style="padding-bottom:max(.75rem,env(safe-area-inset-bottom));">
  <a href="admin_dashboard.php" class="nav-item" style="color:#94a3b8;">
    <span class="material-symbols-outlined" style="">home</span><p>Home</p>
  </a>
  <a href="admin_users.php" class="nav-item active" style="color:#4349cf;font-weight:700;">
    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">group</span><p>Users</p>
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
