<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$department  = $student['department'] ?? 'CO';
$semester    = (int)($student['semester'] ?? 6);

$filterSem    = $_GET['sem']    ?? $semester;
$filterType   = $_GET['type']   ?? 'all';
$filterSubj   = (int)($_GET['subject'] ?? 0);
$searchQ      = trim($_GET['q'] ?? '');

// Build query using updated column names
$sql    = "SELECT sm.*, s.subject_name AS subj_name, s.subject_code, t.name AS uploader_name
           FROM study_materials sm
           LEFT JOIN subjects s ON sm.subject_id = s.id
           LEFT JOIN teachers t ON sm.uploaded_by = t.id
           WHERE sm.department = ?";
$params = [$department];
$types  = "s";

if ($filterSem !== 'all' && $filterSem !== '') {
    $sql .= " AND sm.semester = ?"; $params[] = (string)$filterSem; $types .= "s";
}
if ($filterType !== 'all') {
    $sql .= " AND sm.material_type = ?"; $params[] = $filterType; $types .= "s";
}
if ($filterSubj > 0) {
    $sql .= " AND sm.subject_id = ?"; $params[] = $filterSubj; $types .= "i";
}
if ($searchQ !== '') {
    $sql .= " AND (sm.title LIKE ? OR sm.description LIKE ?)";
    $params[] = "%$searchQ%"; $params[] = "%$searchQ%"; $types .= "ss";
}
$sql .= " ORDER BY sm.uploaded_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt && $types) {
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $materials = $stmt->get_result();
} else {
    $materials = false;
}

// Fetch subjects for filter
$subjectList = [];
$sRes = $conn->query("SELECT id, subject_name, semester FROM subjects WHERE branch_code='$department' ORDER BY semester, subject_name");
while ($row = $sRes->fetch_assoc()) $subjectList[] = $row;

function getMaterialIcon($type) {
    $map = ['pdf'=>'picture_as_pdf','video'=>'play_circle','link'=>'link','lab_manual'=>'science','notes'=>'article'];
    return $map[$type] ?? 'attach_file';
}
function getMaterialColor($type) {
    $map = ['pdf'=>'text-red-500 bg-red-50','video'=>'text-purple-500 bg-purple-50','link'=>'text-blue-500 bg-blue-50','lab_manual'=>'text-teal-500 bg-teal-50','notes'=>'text-orange-500 bg-orange-50'];
    return $map[$type] ?? 'text-slate-500 bg-slate-100';
}

$activeNav = 'material';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Study Materials â€“ CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{display:["Lexend"]}}}}</script>
<style>
*{font-family:'Lexend',sans-serif}
body{min-height:100dvh;background:#eef0ff}
.dark body{background:#0d0e1c}
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
@keyframes topbarIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulseRed{0%,100%{opacity:1}50%{opacity:.4}}
@keyframes cardPop{from{opacity:0;transform:scale(.94) translateY(12px)}to{opacity:1;transform:scale(1) translateY(0)}}
.fu{animation:fadeUp .4s ease both}
.fu1{animation:fadeUp .4s .08s ease both}
.fu2{animation:fadeUp .4s .16s ease both}
.fu3{animation:fadeUp .4s .24s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 4px rgba(0,0,0,.06);transition:transform .2s,box-shadow .2s}
.card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(67,73,207,.13)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.pill{border-radius:9999px;padding:5px 13px;font-size:11px;font-weight:700;border:2px solid transparent;transition:all .18s;cursor:pointer;white-space:nowrap}
.pill.active{background:#4349cf;color:white;border-color:#4349cf}
.pill:not(.active){background:white;color:#64748b;border-color:#e2e8f0}
.dark .pill:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45}
.btn-dl{background:linear-gradient(135deg,#4349cf,#7479f5);color:white;font-weight:700;border-radius:.75rem;padding:6px 14px;font-size:12px;display:inline-flex;align-items:center;gap:4px;text-decoration:none;transition:all .2s}
.btn-dl:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(67,73,207,.4)}
.mat-card{animation:cardPop .35s ease both}
.mat-card:nth-child(2){animation-delay:.05s}
.mat-card:nth-child(3){animation-delay:.1s}
.mat-card:nth-child(4){animation-delay:.15s}
.mat-card:nth-child(5){animation-delay:.2s}
.search-box{background:white;border:2px solid #e2e8f0;border-radius:.875rem;display:flex;align-items:center;gap:8px;padding:6px 12px;transition:border-color .2s}
.search-box:focus-within{border-color:#4349cf;box-shadow:0 0 0 3px rgba(67,73,207,.1)}
.dark .search-box{background:#1a1b2e;border-color:#2a2b45}
select.cc-sel{background:white;border:2px solid #e2e8f0;border-radius:.75rem;padding:7px 10px;font-size:12px;font-weight:600;color:#475569;outline:none;transition:border-color .2s;cursor:pointer;font-family:'Lexend',sans-serif}
select.cc-sel:focus{border-color:#4349cf}
.dark select.cc-sel{background:#1a1b2e;border-color:#2a2b45;color:#94a3b8}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<!-- HERO -->
<div class="px-4 pt-4 fu">
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/30 relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">library_books</span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Resources</p>
    <h1 class="text-xl font-bold">Study Materials</h1>
    <p class="text-white/70 text-xs mt-1"><?php echo htmlspecialchars($department);?> &bull; Semester <?php echo $filterSem;?></p>
  </div>
</div>

<!-- SEARCH -->
<div class="px-4 pt-4 fu1">
  <form method="GET">
    <input type="hidden" name="sem" value="<?php echo htmlspecialchars($filterSem);?>"/>
    <div class="search-box">
      <span class="material-symbols-outlined text-slate-400">search</span>
      <input type="text" name="q" placeholder="Search notes, subjects..." value="<?php echo htmlspecialchars($searchQ);?>"
             class="flex-1 outline-none text-sm bg-transparent"/>
      <?php if ($searchQ): ?>
      <a href="?" class="text-slate-400 hover:text-slate-600">
        <span class="material-symbols-outlined text-base">close</span>
      </a>
      <?php endif;?>
    </div>
  </form>
</div>

<!-- FILTERS -->
<div class="px-4 pt-3 fu2 space-y-3">
  <!-- Semester pills -->
  <div class="flex gap-2 overflow-x-auto pb-1">
    <a href="?sem=all&type=<?php echo $filterType;?>" class="pill shrink-0 <?php echo $filterSem==='all'?'active':'';?>">All Sem</a>
    <?php for($s=1;$s<=6;$s++): ?>
    <a href="?sem=<?php echo $s;?>&type=<?php echo $filterType;?>" class="pill shrink-0 <?php echo $filterSem==$s?'active':'';?>">Sem <?php echo $s;?></a>
    <?php endfor;?>
  </div>
  <!-- Type pills -->
  <div class="flex gap-2 overflow-x-auto pb-1">
    <?php foreach(['all'=>'All','notes'=>'Notes','pdf'=>'PDF','video'=>'Video','link'=>'Link','lab_manual'=>'Lab Manual'] as $k=>$v): ?>
    <a href="?sem=<?php echo $filterSem;?>&type=<?php echo $k;?>" class="pill shrink-0 <?php echo $filterType===$k?'active':'';?>"><?php echo $v;?></a>
    <?php endforeach;?>
  </div>
</div>

<!-- MATERIALS GRID -->
<main class="px-4 pt-4 pb-28">
<?php
$count = 0;
$hasMaterials = false;
if ($materials && $materials->num_rows > 0):
  $hasMaterials = true;
  while ($m = $materials->fetch_assoc()):
    $count++;
    $mtype   = $m['material_type'] ?? 'notes';
    $icon    = getMaterialIcon($mtype);
    $colors  = getMaterialColor($mtype);
    $delay   = 0.05 * ($count-1);
    $title   = $m['title'] ?? '';
    $desc    = $m['description'] ?? '';
    $subjName= $m['subj_name'] ?? $m['subject'] ?? '';
    $uploader= $m['uploader_name'] ?? '';
    $uploadedAt = !empty($m['uploaded_at']) ? date('d M Y', strtotime($m['uploaded_at'])) : '';
    $hasFile = !empty($m['file_path']);
    $hasLink = !empty($m['external_url']);
?>
<div class="card mat-card p-4 mb-3" style="animation-delay:<?php echo $delay;?>s">
  <div class="flex items-start gap-3">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?php echo $colors;?>">
      <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;"><?php echo $icon;?></span>
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-bold text-sm leading-snug"><?php echo htmlspecialchars($title);?></p>
      <?php if ($subjName): ?>
      <p class="text-[11px] text-primary font-semibold mt-0.5"><?php echo htmlspecialchars($subjName);?></p>
      <?php endif;?>
      <?php if ($desc): ?>
      <p class="text-xs text-slate-400 mt-1 line-clamp-2"><?php echo htmlspecialchars($desc);?></p>
      <?php endif;?>
      <div class="flex items-center gap-3 mt-2 flex-wrap">
        <?php if ($uploader): ?>
        <span class="text-[10px] text-slate-400 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">person</span><?php echo htmlspecialchars($uploader);?>
        </span>
        <?php endif;?>
        <?php if ($uploadedAt): ?>
        <span class="text-[10px] text-slate-400 flex items-center gap-1">
          <span class="material-symbols-outlined text-xs">event</span><?php echo $uploadedAt;?>
        </span>
        <?php endif;?>
        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full capitalize <?php echo $colors;?>"><?php echo $mtype;?></span>
      </div>
    </div>
  </div>

  <!-- Action buttons -->
  <div class="flex gap-2 mt-3">
    <?php if ($hasFile): ?>
    <a href="../includes/download_material.php?id=<?php echo (int)$m['id'];?>" class="btn-dl">
      <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">download</span>Download
    </a>
    <?php endif;?>
    <?php if ($hasLink): ?>
    <a href="<?php echo htmlspecialchars($m['external_url']);?>" target="_blank" class="btn-dl" style="background:linear-gradient(135deg,#0891b2,#06b6d4)">
      <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">open_in_new</span>Open Link
    </a>
    <?php endif;?>
    <?php if (!$hasFile && !$hasLink): ?>
    <span class="text-[11px] text-slate-400 italic py-1">No file attached</span>
    <?php endif;?>
  </div>
</div>
<?php endwhile;
endif;?>

<?php if (!$hasMaterials): ?>
<div class="card p-12 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">library_books</span>
  <h3 class="font-bold text-slate-500 mt-3">No Materials Found</h3>
  <p class="text-xs text-slate-400 mt-1">
    <?php echo $searchQ ? 'No results for "'.htmlspecialchars($searchQ).'"' : 'No study materials uploaded yet for this selection.';?>
  </p>
</div>
<?php endif;?>
</main>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php"    class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span></a>
    <a href="student_attendance.php"   class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">assignment_turned_in</span><span class="text-[10px]">Attend.</span></a>
    <a href="student_studymaterial.php"class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl active" style="font-variation-settings:'FILL' 1;color:#4349cf">book</span><span class="text-[10px] font-bold" style="color:#4349cf">Material</span></a>
    <a href="student_message.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="student_profile.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span></a>
  </div>
</nav>

<?php include 'topbar_scripts.php'; ?>
</body>
</html>