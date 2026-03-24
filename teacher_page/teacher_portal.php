<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';
$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
$teacherId = (int)($teacher['id'] ?? 0);
$teacherSubjects = $conn->query("SELECT * FROM subjects WHERE teacher_id = $teacherId ORDER BY semester ASC");

// ===== DELETE HANDLER =====
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    // Only allow teacher to delete their own material
    $delRow = $conn->query("SELECT * FROM study_materials WHERE id=$delId AND uploaded_by=$teacherId LIMIT 1");
    if ($delRow && $delRow->num_rows > 0) {
        $delMat = $delRow->fetch_assoc();
        // Delete physical file if exists
        if (!empty($delMat['file_path'])) {
            $absPath = realpath(__DIR__ . '/../' . ltrim(str_replace('../', '', $delMat['file_path']), '/'));
            if ($absPath && file_exists($absPath)) unlink($absPath);
        }
        $conn->query("DELETE FROM study_materials WHERE id=$delId AND uploaded_by=$teacherId");
        header("Location: teacher_portal.php?deleted=1");
        exit();
    }
}
if (isset($_GET['deleted'])) {
    $uploadMsg = 'Material deleted successfully.';
    $uploadMsgType = 'success';
}
// ===== END DELETE HANDLER =====

// ===== FIXED FILE UPLOAD HANDLER =====
$uploadMsg = ''; $uploadMsgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_material'])) {
    $matTitle  = trim($_POST['mat_title'] ?? '');
    $matDesc   = trim($_POST['mat_desc'] ?? '');
    $subjectId = (int)($_POST['mat_subject'] ?? 0);
    $matType   = in_array($_POST['mat_type'] ?? '', ['notes','pdf','video','link','lab_manual']) ? $_POST['mat_type'] : 'notes';
    $extUrl    = trim($_POST['mat_link'] ?? '');

    if (!$matTitle) {
        $uploadMsg = 'Please enter a title.'; $uploadMsgType = 'error';
    } else {
        // Get subject info for department/semester
        $subInfo = null;
        if ($subjectId) {
            $sRes = $conn->query("SELECT * FROM subjects WHERE id=$subjectId");
            $subInfo = $sRes ? $sRes->fetch_assoc() : null;
        }

        // ===== BUG FIX: Always use branch_code (short code like 'CO'), NOT full department name =====
        // Teacher table has both 'department' = 'Computer Engineering' AND 'branch_code' = 'CO'
        // study_materials.department must store branch_code (e.g., 'CO') to match student query
        $dept = $subInfo['branch_code'] ?? $teacher['branch_code'] ?? 'CO';
        // ===== END BUG FIX =====

        $sem      = $subInfo['semester'] ?? '';
        $filePath = '';
        $fileName = '';

        // File upload
        if (!empty($_FILES['mat_file']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/materials/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $orig  = basename($_FILES['mat_file']['name']);
            $ext   = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','zip','txt','mp4','png','jpg','jpeg'];
            if (in_array($ext, $allowed)) {
                $newName  = $teacherId . '_' . time() . '_' . preg_replace('/[^a-z0-9_.-]/', '', strtolower($orig));
                $destPath = $uploadDir . $newName;
                if (move_uploaded_file($_FILES['mat_file']['tmp_name'], $destPath)) {
                    $filePath = '../uploads/materials/' . $newName;
                    $fileName = $newName;
                    $matType  = $ext === 'pdf' ? 'pdf' : ($ext === 'mp4' ? 'video' : 'notes');
                } else {
                    $uploadMsg = 'File upload failed. Check uploads/materials/ folder permissions.';
                    $uploadMsgType = 'error';
                }
            } else {
                $uploadMsg = 'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP, TXT, MP4, PNG, JPG';
                $uploadMsgType = 'error';
            }
        }

        if ($uploadMsgType !== 'error') {
            $title   = $conn->real_escape_string($matTitle);
            $desc    = $conn->real_escape_string($matDesc);
            $dept2   = $conn->real_escape_string($dept);
            $extUrl2 = $conn->real_escape_string($extUrl);
            $fp2     = $conn->real_escape_string($filePath);
            $fn2     = $conn->real_escape_string($fileName);
            $subjName = $conn->real_escape_string($subInfo['subject_name'] ?? '');
            $result = $conn->query("INSERT INTO study_materials 
                (title, description, subject_id, subject, department, semester, material_type, file_name, file_path, external_url, uploaded_by, uploaded_at)
                VALUES ('$title','$desc'," . ($subjectId ?: 0) . ",'$subjName','$dept2','$sem','$matType','$fn2','$fp2','$extUrl2',$teacherId, NOW())");
            if ($result) {
                $uploadMsg = 'Material uploaded successfully! Students in ' . htmlspecialchars($dept) . ' can now see it.';
                $uploadMsgType = 'success';
            } else {
                $uploadMsg = 'Database error: ' . $conn->error;
                $uploadMsgType = 'error';
            }
        }
    }
}
// Fetch updated materials list after upload
$myMaterials = $conn->query("SELECT sm.*, s.subject_name AS subj_name 
    FROM study_materials sm 
    LEFT JOIN subjects s ON sm.subject_id = s.id 
    WHERE sm.uploaded_by = $teacherId 
    ORDER BY sm.uploaded_at DESC");
?>

<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Study Material â€“ CollegeConnect</title>
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode:"class",
  theme:{extend:{colors:{primary:"#4349cf","bg-light":"#eef0ff","bg-dark":"#0d0e1c"},fontFamily:{display:["Syne"],body:["DM Sans"]}}}
}
</script>
<style>
*{font-family:'DM Sans',sans-serif;}
h1,h2,h3,.font-display{font-family:'Syne',sans-serif;}
:root{--primary:#4349cf;--primary-light:#eef0ff;--grad:linear-gradient(135deg,#4349cf 0%,#6d74f5 55%,#9d8ffa 100%);}
body{min-height:100dvh;background:#f0f1ff;}
.dark body{background:#0d0e1c;}
@keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.92)}to{opacity:1;transform:scale(1)}}
@keyframes slideRight{from{transform:translateX(-100%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@keyframes progressBar{from{width:0}to{width:var(--w)}}
.fu{animation:fadeUp .45s ease both}
.fu1{animation:fadeUp .45s .08s ease both}
.fu2{animation:fadeUp .45s .16s ease both}
.fu3{animation:fadeUp .45s .24s ease both}
.fu4{animation:fadeUp .45s .32s ease both}
.scale-in{animation:scaleIn .35s cubic-bezier(.34,1.4,.64,1) both}
.glass-card{background:rgba(255,255,255,0.9);backdrop-filter:blur(12px);border:1px solid rgba(255,255,255,0.9);border-radius:20px;box-shadow:0 4px 24px rgba(67,73,207,0.08);transition:transform .2s,box-shadow .2s;}
.dark .glass-card{background:rgba(26,27,46,0.9);border-color:rgba(255,255,255,0.06);}
.glass-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(67,73,207,0.15);}
.badge-pdf{background:#fef2f2;color:#ef4444;}
.badge-doc{background:#eff6ff;color:#3b82f6;}
.badge-ppt{background:#fff7ed;color:#f97316;}
.badge-img{background:#f0fdf4;color:#22c55e;}
.badge-vid{background:#fdf4ff;color:#a855f7;}
.badge-zip{background:#fefce8;color:#eab308;}
.badge-link{background:#f0fdfa;color:#14b8a6;}
.badge-notes{background:#eef0ff;color:#4349cf;}
.badge-lab_manual{background:#f0fdf4;color:#22c55e;}
.dark .badge-pdf{background:#450a0a;color:#fca5a5;}
.dark .badge-doc{background:#172554;color:#93c5fd;}
.dark .badge-ppt{background:#431407;color:#fdba74;}
.dark .badge-vid{background:#3b0764;color:#d8b4fe;}
.dark .badge-notes{background:#1e2040;color:#818cf8;}
.drop-zone{border:2px dashed rgba(67,73,207,0.35);border-radius:20px;transition:all .25s;background:rgba(67,73,207,0.03);}
.drop-zone:hover,.drop-zone.dragover{border-color:var(--primary);background:rgba(67,73,207,0.06);}
.drop-zone.dragover .upload-icon{animation:float 1s ease infinite;}
.btn-primary{background:var(--grad);color:white;font-weight:700;border-radius:14px;box-shadow:0 4px 14px rgba(67,73,207,0.35);transition:all .2s;}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(67,73,207,.45);}
.btn-primary:active{transform:scale(.96);}
.progress-wrap{background:#e8eaf6;border-radius:99px;height:6px;overflow:hidden;}
.dark .progress-wrap{background:#1e2040;}
.progress-bar{background:var(--grad);height:100%;border-radius:99px;transition:width .6s ease;}
#uploadModal{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.5);backdrop-filter:blur(6px);display:none;align-items:flex-end;}
#uploadModal.open{display:flex;}
#uploadPanel{background:white;border-radius:28px 28px 0 0;width:100%;max-height:92vh;overflow-y:auto;transform:translateY(100%);transition:transform .38s cubic-bezier(.22,1,.36,1);}
.dark #uploadPanel{background:#13142a;}
#uploadModal.open #uploadPanel{transform:translateY(0);}
.search-bar{background:white;border:1.5px solid #e8eaf6;border-radius:16px;transition:border-color .2s,box-shadow .2s;}
.dark .search-bar{background:#1a1b2e;border-color:#2a2b45;}
.search-bar:focus-within{border-color:var(--primary);box-shadow:0 0 0 4px rgba(67,73,207,.12);}
.tab-pill{padding:8px 16px;border-radius:99px;font-size:13px;font-weight:600;transition:all .2s;cursor:pointer;white-space:nowrap;}
.tab-pill.active{background:var(--primary);color:white;box-shadow:0 4px 12px rgba(67,73,207,.35);}
.tab-pill:not(.active){color:#64748b;background:white;}
.dark .tab-pill:not(.active){color:#94a3b8;background:#1a1b2e;}
.file-row{display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:16px;background:white;border:1px solid #f1f3ff;transition:all .18s;cursor:pointer;}
.dark .file-row{background:#1a1b2e;border-color:#2a2b45;}
.file-row:hover{box-shadow:0 4px 16px rgba(67,73,207,.12);transform:translateX(4px);}
.hero-pattern{background:var(--grad);position:relative;overflow:hidden;}
.hero-pattern::before{content:'';position:absolute;top:-40px;right:-30px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.07);}
.hero-pattern::after{content:'';position:absolute;bottom:-50px;right:40px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.05);}
.toast-wrap{position:fixed;top:72px;left:50%;transform:translateX(-50%);z-index:9999;pointer-events:none;}
.toast-item{animation:fadeUp .3s ease both;background:white;box-shadow:0 8px 28px rgba(0,0,0,.14);border-radius:16px;padding:10px 18px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;min-width:200px;}
.dark .toast-item{background:#1e2040;color:white;}
::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-thumb{background:#c7d0ff;border-radius:4px}
.msg-success{background:#f0fdf4;border:1px solid #86efac;color:#166534;border-radius:14px;padding:12px 16px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;}
.msg-error{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;border-radius:14px;padding:12px 16px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;}
</style>
</head>
<body class="bg-[#f0f1ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "Study Material";
$activePage = "portal";
include __DIR__ . '/teacher_topbar.php';
?>

<!-- Upload status message -->
<?php if ($uploadMsg): ?>
<div class="px-4 pt-3">
  <div class="<?php echo $uploadMsgType === 'success' ? 'msg-success' : 'msg-error'; ?>">
    <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;"><?php echo $uploadMsgType === 'success' ? 'check_circle' : 'error'; ?></span>
    <?php echo htmlspecialchars($uploadMsg); ?>
  </div>
</div>
<?php endif; ?>

<!-- HERO BANNER -->
<div class="px-4 pt-4 fu">
  <div class="hero-pattern rounded-3xl p-5 text-white shadow-xl shadow-indigo-300/30 relative">
    <div class="relative z-10">
      <div class="flex items-start justify-between">
        <div>
          <p class="text-white/60 text-[11px] font-semibold uppercase tracking-widest">Resource Hub</p>
          <h1 class="font-display text-2xl font-bold mt-1">Study Material</h1>
          <p class="text-white/70 text-xs mt-1">Upload, organize &amp; share learning resources</p>
        </div>
        <div class="w-12 h-12 bg-white/15 rounded-2xl flex items-center justify-center backdrop-blur-sm">
          <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings:'FILL' 1;">auto_stories</span>
        </div>
      </div>
      <?php
      $totalFiles = $myMaterials ? $myMaterials->num_rows : 0;
      $subjectCount = $conn->query("SELECT COUNT(DISTINCT subject_id) as c FROM study_materials WHERE uploaded_by=$teacherId")->fetch_assoc()['c'] ?? 0;
      ?>
      <div class="grid grid-cols-3 gap-3 mt-4">
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-2.5 text-center">
          <p class="font-display font-bold text-xl"><?php echo $totalFiles; ?></p>
          <p class="text-white/60 text-[10px] font-semibold">Files</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-2.5 text-center">
          <p class="font-display font-bold text-xl"><?php echo $subjectCount; ?></p>
          <p class="text-white/60 text-[10px] font-semibold">Subjects</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl p-2.5 text-center">
          <p class="font-display font-bold text-xl"><?php echo $teacher['branch_code'] ?? 'CO'; ?></p>
          <p class="text-white/60 text-[10px] font-semibold">Branch</p>
        </div>
      </div>
      <button onclick="openUpload()" class="mt-4 flex items-center gap-2 bg-white text-primary font-bold text-sm px-5 py-2.5 rounded-2xl shadow-lg active:scale-95 transition hover:bg-opacity-95">
        <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">cloud_upload</span>Upload Material
      </button>
    </div>
  </div>
</div>

<main class="px-4 py-4 pb-28 space-y-5">

<!-- SEARCH -->
<div class="fu1">
  <div class="search-bar flex items-center gap-2 px-4 py-3 mb-3">
    <span class="material-symbols-outlined text-slate-400 text-xl">search</span>
    <input type="text" id="searchInput" placeholder="Search materials, subjects..." oninput="filterFiles(this.value)"
      class="flex-1 bg-transparent text-sm border-none focus:ring-0 placeholder:text-slate-400"/>
    <button onclick="document.getElementById('searchInput').value='';filterFiles('')" class="text-slate-300 hover:text-slate-500">
      <span class="material-symbols-outlined text-lg">close</span>
    </button>
  </div>
  <!-- Subject tabs -->
  <div class="flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none;scrollbar-width:none">
    <button class="tab-pill active" onclick="filterSubject(this,'all')">All</button>
    <?php
    if ($teacherSubjects && $teacherSubjects->num_rows > 0) {
        $teacherSubjects->data_seek(0);
        while ($s = $teacherSubjects->fetch_assoc()):
    ?>
    <button class="tab-pill" onclick="filterSubject(this,'<?php echo $s['id'];?>')" data-id="<?php echo $s['id'];?>"><?php echo htmlspecialchars($s['subject_name']); ?></button>
    <?php endwhile; } ?>
  </div>
</div>

<!-- SORT & VIEW TOGGLE -->
<div class="flex items-center justify-between fu2">
  <div class="flex items-center gap-2">
    <select id="sortSelect" class="text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-primary/20">
      <option value="newest">Newest First</option>
      <option value="oldest">Oldest First</option>
    </select>
    <button onclick="filterType('pdf')" class="text-xs font-semibold px-3 py-2 rounded-xl bg-red-50 text-red-500 dark:bg-red-900/20 hover:bg-red-100 transition">PDF</button>
    <button onclick="filterType('notes')" class="text-xs font-semibold px-3 py-2 rounded-xl bg-indigo-50 text-indigo-500 dark:bg-indigo-900/20 hover:bg-indigo-100 transition">Notes</button>
    <button onclick="filterType('all')" class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:bg-slate-200 transition">All</button>
  </div>
  <div class="flex gap-1">
    <button id="listViewBtn" onclick="setView('list')" class="p-2 rounded-xl bg-primary text-white transition">
      <span class="material-symbols-outlined text-sm">view_list</span>
    </button>
    <button id="gridViewBtn" onclick="setView('grid')" class="p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-400 transition">
      <span class="material-symbols-outlined text-sm">grid_view</span>
    </button>
  </div>
</div>

<!-- ALL MATERIALS LIST -->
<div class="fu3">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-display font-bold text-sm flex items-center gap-2">
      <span class="w-6 h-6 rounded-lg bg-primary/10 flex items-center justify-center">
        <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings:'FILL' 1;">folder_open</span>
      </span>
      My Uploaded Materials
    </h3>
    <span id="fileCount" class="text-[11px] bg-primary/10 text-primary font-bold px-2.5 py-1 rounded-full"><?php echo $totalFiles; ?> files</span>
  </div>

  <!-- LIST VIEW -->
  <div id="listView" class="space-y-2">
    <?php
    $typeIcons = [
        'pdf'        => ['picture_as_pdf','badge-pdf'],
        'notes'      => ['article','badge-notes'],
        'video'      => ['videocam','badge-vid'],
        'link'       => ['link','badge-link'],
        'lab_manual' => ['science','badge-lab_manual'],
    ];
    if ($myMaterials && $myMaterials->num_rows > 0) {
        $myMaterials->data_seek(0);
        $i = 0;
        while ($m = $myMaterials->fetch_assoc()):
            $i++;
            $mtype = $m['material_type'] ?? 'notes';
            [$ic, $bc] = $typeIcons[$mtype] ?? ['insert_drive_file','badge-notes'];
            $delay = 0.04 + $i * 0.04;
            $subjName = $m['subj_name'] ?? $m['subject'] ?? 'General';
            $uploadedAt = !empty($m['uploaded_at']) ? date('d M Y', strtotime($m['uploaded_at'])) : '';
            $hasFile = !empty($m['file_path']);
            $hasLink = !empty($m['external_url']);
    ?>
    <div class="file-row"
         data-name="<?php echo strtolower(htmlspecialchars($m['title'].' '.$subjName)); ?>"
         data-sub="<?php echo (int)$m['subject_id']; ?>"
         data-type="<?php echo htmlspecialchars($mtype); ?>"
         style="animation:slideRight .35s <?php echo $delay; ?>s ease both;opacity:0;animation-fill-mode:both">
      <div class="w-10 h-10 rounded-2xl <?php echo $bc; ?> flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;"><?php echo $ic; ?></span>
      </div>
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($m['title']); ?></p>
        <div class="flex items-center gap-2 mt-0.5 flex-wrap">
          <span class="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.5 rounded-full"><?php echo htmlspecialchars($subjName); ?></span>
          <span class="text-[10px] text-slate-400">Sem <?php echo htmlspecialchars($m['semester'] ?? 'â€”'); ?></span>
          <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-full uppercase <?php echo $bc; ?>"><?php echo $mtype; ?></span>
        </div>
      </div>
      <div class="flex flex-col items-end gap-1 shrink-0">
        <span class="text-[10px] text-slate-400"><?php echo $uploadedAt; ?></span>
        <div class="flex items-center gap-1">
          <?php if ($hasFile): ?>
          <a href="../includes/download_material.php?id=<?php echo (int)$m['id']; ?>"
             onclick="event.stopPropagation()"
             class="w-7 h-7 rounded-xl bg-primary/10 text-primary flex items-center justify-center hover:bg-primary/20 transition active:scale-90">
            <span class="material-symbols-outlined text-sm">download</span>
          </a>
          <?php endif; ?>
          <?php if ($hasLink): ?>
          <a href="<?php echo htmlspecialchars($m['external_url']); ?>" target="_blank"
             onclick="event.stopPropagation()"
             class="w-7 h-7 rounded-xl bg-teal-50 text-teal-500 flex items-center justify-center hover:bg-teal-100 transition active:scale-90">
            <span class="material-symbols-outlined text-sm">open_in_new</span>
          </a>
          <?php endif; ?>
          <a href="?delete_id=<?php echo (int)$m['id']; ?>" onclick="return confirm('Delete this material?')"
             class="w-7 h-7 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-400 flex items-center justify-center hover:bg-red-100 transition active:scale-90">
            <span class="material-symbols-outlined text-sm">delete</span>
          </a>
        </div>
      </div>
    </div>
    <?php endwhile;
    } ?>
  </div>

  <!-- GRID VIEW -->
  <div id="gridView" class="hidden grid grid-cols-2 gap-3">
    <?php
    if ($myMaterials && $myMaterials->num_rows > 0) {
        $myMaterials->data_seek(0);
        while ($m = $myMaterials->fetch_assoc()):
            $mtype = $m['material_type'] ?? 'notes';
            [$ic, $bc] = $typeIcons[$mtype] ?? ['insert_drive_file','badge-notes'];
            $subjName = $m['subj_name'] ?? $m['subject'] ?? 'General';
            $uploadedAt = !empty($m['uploaded_at']) ? date('d M Y', strtotime($m['uploaded_at'])) : '';
    ?>
    <div class="glass-card p-4 cursor-pointer"
         data-name="<?php echo strtolower(htmlspecialchars($m['title'].' '.$subjName)); ?>"
         data-sub="<?php echo (int)$m['subject_id']; ?>"
         data-type="<?php echo htmlspecialchars($mtype); ?>">
      <div class="w-10 h-10 rounded-2xl <?php echo $bc; ?> flex items-center justify-center mb-2">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;"><?php echo $ic; ?></span>
      </div>
      <p class="font-semibold text-xs leading-tight line-clamp-2 mb-1"><?php echo htmlspecialchars($m['title']); ?></p>
      <p class="text-[10px] text-slate-400 mb-1"><?php echo htmlspecialchars($subjName); ?></p>
      <p class="text-[10px] text-slate-400"><?php echo $uploadedAt; ?></p>
    </div>
    <?php endwhile; } ?>
  </div>

  <div id="emptyState" <?php echo ($totalFiles > 0) ? 'class="hidden"' : ''; ?> class="text-center py-12">
    <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">cloud_upload</span>
    <h3 class="font-bold text-slate-500 mt-3">No Materials Uploaded Yet</h3>
    <p class="text-xs text-slate-400 mt-1">Upload your first study material for students.</p>
    <button onclick="openUpload()" class="mt-3 btn-primary text-sm px-4 py-2">Upload First File</button>
  </div>
</div>

</main>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="teacher_dashboard.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span></a>
    <a href="teacher_classes.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">menu_book</span><span class="text-[10px]">Classes</span></a>
    <a href="teacher_attendence.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">assignment_turned_in</span><span class="text-[10px]">Attend.</span></a>
    <a href="teacher_message.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="teacher_profile.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span></a>
  </div>
</nav>

<!-- FAB -->
<button onclick="openUpload()" class="fixed bottom-20 right-4 z-30 w-14 h-14 rounded-full flex items-center justify-center shadow-xl shadow-indigo-300/40 active:scale-90 transition hover:scale-110" style="background:var(--grad)">
  <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings:'FILL' 1;">add</span>
</button>

<!-- UPLOAD MODAL (with real form POST) -->
<div id="uploadModal" onclick="if(event.target===this)closeUpload()">
  <div id="uploadPanel">
    <div class="flex justify-center pt-4 pb-2"><div class="w-10 h-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div></div>
    <div class="px-5 pb-2">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-display font-bold text-lg flex items-center gap-2">
          <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">cloud_upload</span>Upload Material
        </h3>
        <button onclick="closeUpload()" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"><span class="material-symbols-outlined text-slate-400">close</span></button>
      </div>

      <form method="POST" enctype="multipart/form-data" id="uploadForm">

        <!-- Title -->
        <div class="mb-3">
          <label class="text-xs font-bold text-slate-500 mb-1 block">Title <span class="text-red-500">*</span></label>
          <input type="text" name="mat_title" placeholder="e.g. Unit 3 - Arrays and Pointers" required
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400"/>
        </div>

        <!-- Drop zone -->
        <div id="dropZone" class="drop-zone p-6 text-center mb-3"
             ondrop="handleDrop(event)" ondragover="event.preventDefault();this.classList.add('dragover')" ondragleave="this.classList.remove('dragover')">
          <span class="material-symbols-outlined upload-icon text-4xl text-primary/40 mb-2 block" style="font-variation-settings:'FILL' 1;">cloud_upload</span>
          <p class="font-semibold text-sm text-slate-600 dark:text-slate-300">Drag & drop file here</p>
          <p class="text-xs text-slate-400 mt-1 mb-3">PDF, DOCX, PPTX, MP4, ZIP â€” max 50MB</p>
          <label class="btn-primary text-xs px-4 py-2 rounded-xl cursor-pointer inline-block">
            <span class="material-symbols-outlined text-sm align-middle">folder_open</span> Browse File
            <input type="file" name="mat_file" id="fileInput" onchange="handleFileSelect(this)" class="hidden"/>
          </label>
        </div>
        <div id="fileQueue" class="mb-3"></div>

        <div class="grid grid-cols-2 gap-3 mb-3">
          <!-- Subject -->
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Subject</label>
            <select name="mat_subject" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
              <option value="0">â€” Select subject â€”</option>
              <?php
              if ($teacherSubjects && $teacherSubjects->num_rows > 0) {
                  $teacherSubjects->data_seek(0);
                  while ($s = $teacherSubjects->fetch_assoc()):
              ?>
              <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?> (Sem <?php echo $s['semester']; ?>)</option>
              <?php endwhile; } ?>
            </select>
          </div>
          <!-- Material Type -->
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Type</label>
            <select name="mat_type" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
              <option value="notes">ðŸ“„ Notes</option>
              <option value="pdf">ðŸ“• PDF</option>
              <option value="video">ðŸŽ¥ Video</option>
              <option value="lab_manual">ðŸ”¬ Lab Manual</option>
              <option value="link">ðŸ”— External Link</option>
            </select>
          </div>
        </div>

        <!-- External Link (for link type) -->
        <div class="mb-3">
          <label class="text-xs font-bold text-slate-500 mb-1 block">External Link (optional â€” for YouTube, Drive, etc.)</label>
          <input type="url" name="mat_link" placeholder="https://..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400"/>
        </div>

        <!-- Description -->
        <div class="mb-4">
          <label class="text-xs font-bold text-slate-500 mb-1 block">Description (optional)</label>
          <textarea name="mat_desc" rows="2" placeholder="Add a note about this material..."
            class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2.5 text-sm resize-none focus:ring-2 focus:ring-primary/20 focus:border-primary placeholder:text-slate-400"></textarea>
        </div>

        <div class="flex gap-3 pb-8">
          <button type="button" onclick="closeUpload()" class="flex-1 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 font-bold text-sm active:scale-95 transition">Cancel</button>
          <button type="submit" name="upload_material" class="flex-1 py-3 rounded-2xl btn-primary font-bold text-sm flex items-center justify-center gap-1.5">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">cloud_upload</span>Upload
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
// â”€â”€ View toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function setView(v) {
  document.getElementById('listView').classList.toggle('hidden', v==='grid');
  document.getElementById('gridView').classList.toggle('hidden', v==='list');
  document.getElementById('listViewBtn').className = v==='list'
    ? 'p-2 rounded-xl bg-primary text-white transition'
    : 'p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-400 transition';
  document.getElementById('gridViewBtn').className = v==='grid'
    ? 'p-2 rounded-xl bg-primary text-white transition'
    : 'p-2 rounded-xl bg-white dark:bg-slate-800 text-slate-400 transition';
}

// â”€â”€ Filter â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let activeSubject='all', activeType='all', searchQuery='';
function updateDisplay() {
  const rows = [...document.querySelectorAll('#listView .file-row, #gridView .glass-card[data-name]')];
  let visible=0;
  rows.forEach(r=>{
    const n=r.dataset.name||'', sub=r.dataset.sub||'', type=r.dataset.type||'';
    const show = (activeSubject==='all'||sub===activeSubject) &&
                 (activeType==='all'||type===activeType) &&
                 n.includes(searchQuery.toLowerCase());
    r.style.display=show?'':'none';
    if(show)visible++;
  });
  const fc=document.getElementById('fileCount');
  if(fc) fc.textContent=visible+' files';
  const empty=document.getElementById('emptyState');
  if(empty) empty.classList.toggle('hidden', visible>0);
}
function filterSubject(btn, sub) {
  document.querySelectorAll('.tab-pill').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active'); activeSubject=sub; updateDisplay();
}
function filterType(t) { activeType=t; updateDisplay(); }
function filterFiles(q) { searchQuery=q; updateDisplay(); }

// â??â?? Upload modal â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
function openUpload() { document.getElementById('uploadModal').classList.add('open'); }
function closeUpload() {
  document.getElementById('uploadModal').classList.remove('open');
  document.getElementById('fileQueue').innerHTML='';
}

function handleFileSelect(input) {
  if (input.files && input.files[0]) renderQueue(input.files[0]);
}
function handleDrop(e) {
  e.preventDefault(); document.getElementById('dropZone').classList.remove('dragover');
  if(e.dataTransfer.files[0]) {
    document.getElementById('fileInput').files = e.dataTransfer.files;
    renderQueue(e.dataTransfer.files[0]);
  }
}
function renderQueue(file) {
  const q = document.getElementById('fileQueue');
  const ext = file.name.split('.').pop().toLowerCase();
  const icons = {pdf:'picture_as_pdf',docx:'description',pptx:'slideshow',mp4:'videocam',png:'image',jpg:'image',zip:'folder_zip'};
  const ic = icons[ext]||'insert_drive_file';
  q.innerHTML = `
    <div class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl">
      <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">${ic}</span>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-semibold truncate">${file.name}</p>
        <p class="text-[10px] text-slate-400 mt-0.5">${(file.size/1024/1024).toFixed(2)} MB</p>
      </div>
      <span class="text-green-500 text-xs font-bold">âœ? Ready</span>
    </div>`;
}

// â??â?? Toast â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
function showToast(msg, icon, cls) {
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className='toast-item';
  t.innerHTML=`<span class="material-symbols-outlined ${cls} text-lg" style="font-variation-settings:'FILL' 1;">${icon}</span>${msg}`;
  wrap.appendChild(t);
  setTimeout(()=>{ t.style.transition='opacity .3s'; t.style.opacity='0'; setTimeout(()=>t.remove(),300); },2500);
}

// Auto open upload modal if there was an error
<?php if ($uploadMsg && $uploadMsgType === 'error'): ?>
document.addEventListener('DOMContentLoaded', ()=> openUpload());
<?php endif; ?>
</script>
</body>
</html>
