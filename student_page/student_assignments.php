<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
$activeNav = 'assignments';

$studentId = (int)($student['id'] ?? 0);
$sem       = (int)($student['semester'] ?? 6);
$dept      = $student['department'] ?? 'CO';

// Handle file submission
$msg = ''; $msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_assignment'])) {
    $assignId = (int)($_POST['assignment_id'] ?? 0);
    $filePath = '';

    if ($assignId > 0) {
        // Handle file upload
        if (!empty($_FILES['submission_file']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/assignments/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $origName  = basename($_FILES['submission_file']['name']);
            $ext       = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed   = ['pdf','doc','docx','ppt','pptx','zip','ipynb','txt'];
            if (in_array($ext, $allowed)) {
                $newName  = $studentId . '_' . $assignId . '_' . time() . '.' . $ext;
                $destPath = $uploadDir . $newName;
                if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $destPath)) {
                    $filePath = '../uploads/assignments/' . $newName;
                } else {
                    $msg = 'File upload failed. Check folder permissions.'; $msgType = 'error';
                }
            } else {
                $msg = 'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, ZIP, IPYNB.'; $msgType = 'error';
            }
        }

        if ($msgType !== 'error') {
            // Check if already submitted
            $chk = $conn->prepare("SELECT id FROM assignment_submissions WHERE assignment_id=? AND student_id=?");
            $chk->bind_param("ii", $assignId, $studentId);
            $chk->execute();
            $exists = $chk->get_result()->num_rows > 0;
            $chk->close();

            if ($exists) {
                if ($filePath) {
                    $upd = $conn->prepare("UPDATE assignment_submissions SET file_path=?,submitted_at=NOW(),status='submitted' WHERE assignment_id=? AND student_id=?");
                    $upd->bind_param("sii", $filePath, $assignId, $studentId);
                    $upd->execute(); $upd->close();
                }
                $msg = 'Assignment re-submitted successfully!'; $msgType = 'success';
            } else {
                $ins = $conn->prepare("INSERT INTO assignment_submissions (assignment_id,student_id,file_path,submitted_at,status) VALUES (?,?,?,NOW(),'submitted')");
                $ins->bind_param("iis", $assignId, $studentId, $filePath);
                $ins->execute(); $ins->close();
                $msg = 'Assignment submitted successfully!'; $msgType = 'success';
            }
        }
    }
}

// Fetch assignments for this student's branch & semester
$assignments = [];
$sql = "SELECT a.*, s.subject_name, s.subject_code,
               sub.id AS sub_id, sub.status AS sub_status, sub.submitted_at AS sub_time, sub.marks AS sub_marks, sub.feedback AS sub_feedback
        FROM assignments a
        LEFT JOIN subjects s ON a.subject_id = s.id
        LEFT JOIN assignment_submissions sub ON sub.assignment_id = a.id AND sub.student_id = ?
        WHERE (a.branch_code = ? AND a.semester = ?) OR (a.branch_code IS NULL AND a.semester = ?)
        ORDER BY a.due_date ASC, a.created_at DESC";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("isii", $studentId, $dept, $sem, $sem);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $assignments[] = $row;
    $stmt->close();
}

$today   = date('Y-m-d');
$pending = array_filter($assignments, fn($a) => empty($a['sub_id']) && $a['due_date'] >= $today);
$past    = array_filter($assignments, fn($a) => !empty($a['sub_id']));
$overdue = array_filter($assignments, fn($a) => empty($a['sub_id']) && $a['due_date'] < $today);
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width,initial-scale=1.0" name="viewport"/>
<title>Assignments â€“ CollegeConnect</title>
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
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.fu0{animation:fadeUp .4s .00s ease both}
.fu1{animation:fadeUp .4s .08s ease both}
.fu2{animation:fadeUp .4s .16s ease both}
.fu3{animation:fadeUp .4s .24s ease both}
.topbar-enter{animation:topbarIn .35s ease both}
.notif-pulse{animation:pulseRed 2s infinite}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06);transition:transform .18s,box-shadow .18s}
.card:hover{transform:translateY(-2px);box-shadow:0 6px 22px rgba(67,73,207,.11)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}
.hero-grad{background:linear-gradient(135deg,#4349cf 0%,#7479f5 60%,#a78bfa 100%)}
.tab-btn{padding:7px 14px;border-radius:9999px;font-size:11px;font-weight:700;border:2px solid transparent;transition:all .18s;cursor:pointer}
.tab-btn.active{background:#4349cf;color:white}
.tab-btn:not(.active){background:white;color:#64748b;border-color:#e2e8f0}
.dark .tab-btn:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);z-index:50;display:flex;align-items:flex-end;justify-content:center}
.modal-box{background:white;border-radius:1.5rem 1.5rem 0 0;width:100%;max-width:480px;padding:1.5rem;animation:slideUp .3s ease both}
.dark .modal-box{background:#1a1b2e}
.upload-zone{border:2px dashed rgba(67,73,207,.25);border-radius:.875rem;padding:1.5rem;text-align:center;cursor:pointer;transition:all .2s}
.upload-zone:hover{border-color:#4349cf;background:rgba(67,73,207,.03)}
</style>
</head>
<body class="bg-[#eef0ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php include 'topbar.php'; ?>

<!-- HERO -->
<div class="px-4 pt-4 fu0">
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/30 relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">assignment</span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Academic</p>
    <h1 class="text-xl font-bold">My Assignments</h1>
    <p class="text-white/70 text-xs mt-1"><?php echo htmlspecialchars($dept);?> &bull; Sem <?php echo $sem;?></p>
    <div class="flex gap-3 mt-3">
      <div class="bg-white/20 rounded-xl px-3 py-1.5 text-center">
        <p class="text-lg font-bold leading-none"><?php echo count($pending);?></p>
        <p class="text-[10px] text-white/70">Pending</p>
      </div>
      <div class="bg-white/20 rounded-xl px-3 py-1.5 text-center">
        <p class="text-lg font-bold leading-none"><?php echo count($past);?></p>
        <p class="text-[10px] text-white/70">Submitted</p>
      </div>
      <div class="bg-white/20 rounded-xl px-3 py-1.5 text-center">
        <p class="text-lg font-bold leading-none"><?php echo count($overdue);?></p>
        <p class="text-[10px] text-white/70">Overdue</p>
      </div>
    </div>
  </div>
</div>

<!-- ALERT -->
<?php if ($msg): ?>
<div class="px-4 pt-3 fu1">
  <div class="rounded-xl px-4 py-3 text-sm font-semibold flex items-center gap-2
    <?php echo $msgType==='success'?'bg-green-50 text-green-700 border border-green-200':'bg-red-50 text-red-600 border border-red-200';?>">
    <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;"><?php echo $msgType==='success'?'check_circle':'error';?></span>
    <?php echo htmlspecialchars($msg);?>
  </div>
</div>
<?php endif;?>

<!-- TABS -->
<div class="px-4 pt-4 fu1 flex gap-2 overflow-x-auto pb-1">
  <button onclick="showTab('pending')" id="tab-pending" class="tab-btn active shrink-0">Pending (<?php echo count($pending);?>)</button>
  <button onclick="showTab('submitted')" id="tab-submitted" class="tab-btn shrink-0">Submitted (<?php echo count($past);?>)</button>
  <button onclick="showTab('overdue')" id="tab-overdue" class="tab-btn shrink-0">Overdue (<?php echo count($overdue);?>)</button>
</div>

<main class="px-4 pt-4 pb-28 space-y-3">

<!-- PENDING -->
<div id="section-pending">
<?php if (!empty($pending)): $idx=0; foreach($pending as $a): $idx++;
  $daysLeft = (int)round((strtotime($a['due_date']) - time()) / 86400);
  $urgColor = $daysLeft <= 2 ? '#dc2626' : ($daysLeft <= 5 ? '#ca8a04' : '#16a34a');
  $delay = 0.05 * $idx;
?>
<div class="card p-4" style="animation:fadeUp .4s <?php echo $delay;?>s ease both">
  <div class="flex items-start justify-between gap-2 mb-2">
    <div class="flex-1">
      <p class="font-bold text-sm"><?php echo htmlspecialchars($a['title']);?></p>
      <p class="text-[11px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($a['subject_name'] ?? 'General');?></p>
    </div>
    <span class="text-[10px] font-bold px-2 py-1 rounded-lg shrink-0" style="background:<?php echo $daysLeft<=2?'#fee2e2':($daysLeft<=5?'#fef9c3':'#dcfce7');?>;color:<?php echo $urgColor;?>">
      <?php echo $daysLeft===0?'Today':($daysLeft===1?'1 day':$daysLeft.' days');?>
    </span>
  </div>
  <?php if (!empty($a['description'])): ?>
  <p class="text-xs text-slate-500 mb-3 line-clamp-2"><?php echo htmlspecialchars($a['description']);?></p>
  <?php endif;?>
  <div class="flex items-center justify-between">
    <p class="text-[10px] text-slate-400 flex items-center gap-1">
      <span class="material-symbols-outlined text-xs">event</span>
      Due: <?php echo date('d M Y', strtotime($a['due_date']));?>
    </p>
    <button onclick="openSubmit(<?php echo $a['id'];?>, '<?php echo addslashes($a['title']);?>')"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white"
            style="background:linear-gradient(135deg,#4349cf,#7479f5)">
      <span class="material-symbols-outlined text-sm">upload</span>Submit
    </button>
  </div>
</div>
<?php endforeach; else: ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">check_circle</span>
  <h3 class="font-bold text-slate-500 mt-3">All Done!</h3>
  <p class="text-xs text-slate-400 mt-1">No pending assignments.</p>
</div>
<?php endif;?>
</div>

<!-- SUBMITTED -->
<div id="section-submitted" class="hidden">
<?php if (!empty($past)): $idx=0; foreach($past as $a): $idx++;
  $delay = 0.05 * $idx;
  $subStatus = $a['sub_status'] ?? 'submitted';
  $statusColors = ['submitted'=>['#2563eb','#dbeafe'],'graded'=>['#16a34a','#dcfce7'],'late'=>['#ca8a04','#fef9c3']];
  [$sc,$sbg] = $statusColors[$subStatus] ?? ['#64748b','#f1f5f9'];
?>
<div class="card p-4" style="animation:fadeUp .4s <?php echo $delay;?>s ease both">
  <div class="flex items-start justify-between gap-2 mb-2">
    <div class="flex-1">
      <p class="font-bold text-sm"><?php echo htmlspecialchars($a['title']);?></p>
      <p class="text-[11px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($a['subject_name'] ?? 'General');?></p>
    </div>
    <span class="text-[10px] font-bold px-2 py-1 rounded-lg shrink-0 capitalize" style="background:<?php echo $sbg;?>;color:<?php echo $sc;?>"><?php echo $subStatus;?></span>
  </div>
  <p class="text-[10px] text-slate-400 flex items-center gap-1 mb-2">
    <span class="material-symbols-outlined text-xs">schedule</span>
    Submitted: <?php echo date('d M Y, h:i A', strtotime($a['sub_time'] ?? 'now'));?>
  </p>
  <?php if ($a['sub_marks'] !== null): ?>
  <div class="flex items-center gap-2 bg-green-50 dark:bg-green-900/20 rounded-lg px-3 py-2 mb-2">
    <span class="material-symbols-outlined text-green-600 text-base" style="font-variation-settings:'FILL' 1;">grade</span>
    <p class="text-xs font-bold text-green-700">Marks: <?php echo (float)$a['sub_marks'];?>/100</p>
  </div>
  <?php endif;?>
  <?php if (!empty($a['sub_feedback'])): ?>
  <p class="text-xs text-slate-500 italic">"<?php echo htmlspecialchars($a['sub_feedback']);?>"</p>
  <?php endif;?>
  <button onclick="openSubmit(<?php echo $a['id'];?>, '<?php echo addslashes($a['title']);?>')"
          class="mt-2 flex items-center gap-1 text-[11px] font-bold text-primary">
    <span class="material-symbols-outlined text-sm">refresh</span>Resubmit
  </button>
</div>
<?php endforeach; else: ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">assignment</span>
  <h3 class="font-bold text-slate-500 mt-3">No Submissions Yet</h3>
  <p class="text-xs text-slate-400 mt-1">Submit an assignment to see it here.</p>
</div>
<?php endif;?>
</div>

<!-- OVERDUE -->
<div id="section-overdue" class="hidden">
<?php if (!empty($overdue)): $idx=0; foreach($overdue as $a): $idx++; $delay = 0.05 * $idx;?>
<div class="card p-4 border-red-100 dark:border-red-900/30" style="animation:fadeUp .4s <?php echo $delay;?>s ease both">
  <div class="flex items-start justify-between gap-2 mb-2">
    <div class="flex-1">
      <p class="font-bold text-sm"><?php echo htmlspecialchars($a['title']);?></p>
      <p class="text-[11px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($a['subject_name'] ?? 'General');?></p>
    </div>
    <span class="text-[10px] font-bold px-2 py-1 rounded-lg shrink-0 bg-red-50 text-red-600">Overdue</span>
  </div>
  <p class="text-[10px] text-red-400 flex items-center gap-1 mb-2">
    <span class="material-symbols-outlined text-xs">warning</span>
    Was due: <?php echo date('d M Y', strtotime($a['due_date']));?>
  </p>
  <button onclick="openSubmit(<?php echo $a['id'];?>, '<?php echo addslashes($a['title']);?>')"
          class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-white bg-red-500">
    <span class="material-symbols-outlined text-sm">upload</span>Submit Late
  </button>
</div>
<?php endforeach; else: ?>
<div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700">
  <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">check_circle</span>
  <h3 class="font-bold text-slate-500 mt-3">No Overdue Assignments</h3>
</div>
<?php endif;?>
</div>

</main>

<!-- SUBMIT MODAL -->
<div id="submitModal" class="modal-bg hidden" onclick="if(event.target===this)closeSubmit()">
  <div class="modal-box">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-base" id="modalTitle">Submit Assignment</h3>
      <button onclick="closeSubmit()" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="submit_assignment" value="1"/>
      <input type="hidden" name="assignment_id" id="modalAssignId"/>
      <label class="upload-zone block mb-4" for="sub_file">
        <span class="material-symbols-outlined text-4xl text-primary/40 block mb-2" style="font-variation-settings:'FILL' 1;">upload_file</span>
        <p class="text-sm font-bold text-slate-600 dark:text-slate-300">Click to upload file</p>
        <p class="text-[10px] text-slate-400 mt-1">PDF, DOC, DOCX, PPT, ZIP, IPYNB</p>
        <input type="file" id="sub_file" name="submission_file" class="hidden" onchange="updateFileName(this)"/>
      </label>
      <p id="fileName" class="text-xs text-primary font-semibold mb-4 hidden"></p>
      <button type="submit" class="w-full py-3 rounded-xl font-bold text-sm text-white"
              style="background:linear-gradient(135deg,#4349cf,#7479f5)">
        Submit Assignment
      </button>
    </form>
  </div>
</div>

<!-- BOTTOM NAV -->
<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="student_dashboard.php"    class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span></a>
    <a href="student_attendance.php"   class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">assignment_turned_in</span><span class="text-[10px]">Attend.</span></a>
    <a href="student_studymaterial.php"class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">book</span><span class="text-[10px]">Material</span></a>
    <a href="student_message.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">message</span><span class="text-[10px]">Messages</span></a>
    <a href="student_profile.php"      class="flex flex-col items-center gap-0.5 text-slate-400 hover:text-primary px-3 py-1"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span></a>
  </div>
</nav>

<script>
function showTab(tab) {
  ['pending','submitted','overdue'].forEach(t => {
    document.getElementById('section-'+t).classList.toggle('hidden', t!==tab);
    document.getElementById('tab-'+t).classList.toggle('active', t===tab);
  });
}
function openSubmit(id, title) {
  document.getElementById('modalAssignId').value = id;
  document.getElementById('modalTitle').textContent = 'Submit: ' + title;
  document.getElementById('submitModal').classList.remove('hidden');
  document.getElementById('fileName').classList.add('hidden');
}
function closeSubmit() {
  document.getElementById('submitModal').classList.add('hidden');
}
function updateFileName(input) {
  const p = document.getElementById('fileName');
  if (input.files.length > 0) {
    p.textContent = 'ðŸ“Ž ' + input.files[0].name;
    p.classList.remove('hidden');
  }
}
</script>

<?php include 'topbar_scripts.php'; ?>
</body>
</html>
