<?php
require_once __DIR__ . '/../auth/admin_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';
$settings = getSiteSettings($conn);

$msg = ''; $msgType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // General settings
    if (isset($_POST['save_general'])) {
        $siteName = $conn->real_escape_string(trim($_POST['site_name'] ?? ''));
        $maintenance = (int)isset($_POST['maintenance_mode']);
        $maintMsg = $conn->real_escape_string(trim($_POST['maintenance_message'] ?? ''));
        $conn->query("UPDATE site_settings SET site_name='$siteName', maintenance_mode=$maintenance, maintenance_message='$maintMsg' WHERE id=1");
        $msg = 'General settings saved!'; $msgType = 'success';
    }

    // Add announcement
    if (isset($_POST['add_announcement'])) {
        $title = $conn->real_escape_string(trim($_POST['ann_title'] ?? ''));
        $body  = $conn->real_escape_string(trim($_POST['ann_body'] ?? ''));
        if ($title && $body) {
            $conn->query("INSERT INTO announcements (title, message, created_at) VALUES ('$title','$body',NOW())");
            $msg = 'Announcement added!'; $msgType = 'success';
        }
    }

    // Delete announcement
    if (isset($_POST['delete_ann'])) {
        $annId = (int)$_POST['ann_id'];
        $conn->query("DELETE FROM announcements WHERE id=$annId");
        $msg = 'Announcement deleted.'; $msgType = 'info';
    }

    // Add subject
    if (isset($_POST['add_subject'])) {
        $branchCode  = $conn->real_escape_string(trim($_POST['branch_code'] ?? ''));
        $semester    = (int)$_POST['semester'];
        $subjectCode = $conn->real_escape_string(trim($_POST['subject_code'] ?? ''));
        $subjectName = $conn->real_escape_string(trim($_POST['subject_name'] ?? ''));
        $subShort    = $conn->real_escape_string(trim($_POST['subject_short'] ?? ''));
        $teacherId   = (int)($_POST['teacher_id'] ?? 0) ?: 'NULL';
        if ($branchCode && $semester && $subjectName) {
            $conn->query("INSERT INTO subjects (branch_code, semester, subject_code, subject_name, subject_short, teacher_id) VALUES ('$branchCode', $semester, '$subjectCode', '$subjectName', '$subShort', ".($teacherId==='NULL'?'NULL':$teacherId).")");
            $msg = 'Subject added!'; $msgType = 'success';
        }
    }

    // Delete subject
    if (isset($_POST['delete_subject'])) {
        $subId = (int)$_POST['sub_id'];
        $conn->query("DELETE FROM subjects WHERE id=$subId");
        $msg = 'Subject deleted.'; $msgType = 'info';
    }

    // Add timetable slot
    if (isset($_POST['add_timetable'])) {
        $branchCode = $conn->real_escape_string(trim($_POST['tt_branch'] ?? ''));
        $semester   = (int)$_POST['tt_semester'];
        $ttType     = in_array($_POST['tt_type'] ?? '', ['class','exam']) ? $_POST['tt_type'] : 'class';
        $subjectId  = (int)$_POST['tt_subject'];
        $teacherId  = (int)($_POST['tt_teacher'] ?? 0) ?: 'NULL';
        $dayName    = $conn->real_escape_string($_POST['tt_day'] ?? '');
        $startTime  = $conn->real_escape_string($_POST['tt_start'] ?? '');
        $endTime    = $conn->real_escape_string($_POST['tt_end'] ?? '');
        $examDate   = !empty($_POST['tt_examdate']) ? "'".$conn->real_escape_string($_POST['tt_examdate'])."'" : 'NULL';
        $roomNo     = $conn->real_escape_string($_POST['tt_room'] ?? '');
        if ($branchCode && $semester && $subjectId) {
            $conn->query("INSERT INTO timetables (branch_code, semester, timetable_type, subject_id, teacher_id, day_name, start_time, end_time, exam_date, room_no)
                          VALUES ('$branchCode', $semester, '$ttType', $subjectId, ".($teacherId==='NULL'?'NULL':$teacherId).", '$dayName', '$startTime', '$endTime', $examDate, '$roomNo')");
            $msg = 'Timetable slot added!'; $msgType = 'success';
        }
    }

    // Delete timetable slot
    if (isset($_POST['delete_tt'])) {
        $ttId = (int)$_POST['tt_id'];
        $conn->query("DELETE FROM timetables WHERE id=$ttId");
        $msg = 'Timetable slot deleted.'; $msgType = 'info';
    }

    // Reload settings
    $settings = getSiteSettings($conn);
}

// Fetch data
$announcements = [];
$aRes = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 20");
while ($row = $aRes->fetch_assoc()) $announcements[] = $row;

$subjects_all = [];
$sRes = $conn->query("SELECT s.*, t.name AS teacher_name FROM subjects s LEFT JOIN teachers t ON s.teacher_id=t.id ORDER BY s.branch_code, s.semester, s.subject_name LIMIT 100");
while ($row = $sRes->fetch_assoc()) $subjects_all[] = $row;

$teachers_all = [];
$tRes = $conn->query("SELECT id, name, department FROM teachers WHERE status='approved' ORDER BY name");
while ($row = $tRes->fetch_assoc()) $teachers_all[] = $row;

$branches_all = [];
$bRes = $conn->query("SELECT * FROM branches ORDER BY branch_code");
while ($row = $bRes->fetch_assoc()) $branches_all[] = $row;

$timetable_all = [];
$ttRes = $conn->query("SELECT tt.*, s.subject_name, t.name AS teacher_name FROM timetables tt JOIN subjects s ON tt.subject_id=s.id LEFT JOIN teachers t ON tt.teacher_id=t.id ORDER BY tt.branch_code, tt.semester, FIELD(tt.day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), tt.start_time LIMIT 100");
while ($row = $ttRes->fetch_assoc()) $timetable_all[] = $row;

$activeSection = $_GET['section'] ?? 'general';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Settings CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{sans:["Plus Jakarta Sans","sans-serif"]}}}}</script>
<style>
*,*::before,*::after{box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f0f2ff;min-height:100dvh}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.anim{animation:fadeUp .4s ease both}
.card{background:#fff;border-radius:1rem;border:1px solid rgba(67,73,207,.08);box-shadow:0 2px 12px rgba(67,73,207,.06);padding:1.25rem}
.sidebar{background:#fff;border-right:1px solid rgba(67,73,207,.08);width:220px;flex-shrink:0;min-height:100dvh;padding:1rem}
.sidebar-link{display:flex;align-items:center;gap:.75rem;padding:.65rem 1rem;border-radius:.75rem;font-size:.875rem;font-weight:500;color:#64748b;transition:all .2s;text-decoration:none}
.sidebar-link:hover{background:rgba(67,73,207,.06);color:#4349cf}
.sidebar-link.active{background:#4349cf;color:#fff;box-shadow:0 4px 12px rgba(67,73,207,.3)}
.form-label{display:block;font-size:.8125rem;font-weight:600;color:#64748b;margin-bottom:.375rem}
.form-input{width:100%;padding:.625rem .875rem;border:1.5px solid rgba(67,73,207,.15);border-radius:.75rem;background:#f8f9ff;font-family:inherit;font-size:.875rem;outline:none;transition:border-color .2s;color:#1e293b}
.form-input:focus{border-color:#4349cf;box-shadow:0 0 0 3px rgba(67,73,207,.12);background:#fff}
select.form-input{cursor:pointer}
textarea.form-input{resize:vertical;min-height:80px}
.btn-primary{display:inline-flex;align-items:center;gap:.5rem;padding:.625rem 1.25rem;background:#4349cf;color:#fff;border-radius:.75rem;font-size:.875rem;font-weight:600;transition:all .2s;box-shadow:0 4px 12px rgba(67,73,207,.25);border:none;cursor:pointer}
.btn-primary:hover{background:#2630ed;transform:translateY(-1px)}
.btn-danger{display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .875rem;background:rgba(239,68,68,.08);color:#dc2626;border-radius:.625rem;font-size:.8125rem;font-weight:600;border:1.5px solid rgba(239,68,68,.2);cursor:pointer;transition:all .2s}
.btn-danger:hover{background:rgba(239,68,68,.15)}
.toggle-wrap{display:flex;align-items:center;justify-content:space-between;padding:.875rem 0;border-bottom:1px solid rgba(67,73,207,.06)}
.toggle-wrap:last-child{border-bottom:none}
.toggle{position:relative;display:inline-flex;cursor:pointer}
.toggle input{opacity:0;width:0;height:0;position:absolute}
.toggle-track{width:44px;height:24px;background:#e2e8f0;border-radius:99px;transition:background .2s;position:relative}
.toggle input:checked~.toggle-track{background:#4349cf}
.toggle-thumb{position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:transform .2s}
.toggle input:checked~.toggle-track .toggle-thumb{transform:translateX(20px)}
.section-heading{display:flex;align-items:center;gap:.5rem;margin-bottom:1rem}
.section-heading .material-symbols-outlined{color:#4349cf}
.section-heading h2{font-size:1rem;font-weight:700}
</style>
</head>
<body>
<div class="flex min-h-screen">

  <!-- SIDEBAR (desktop) -->
  <aside class="sidebar hidden md:flex flex-col gap-1 sticky top-0 h-screen overflow-y-auto">
    <div class="flex items-center gap-2 mb-6 px-2">
      <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center">
        <span class="material-symbols-outlined text-white text-sm" style="font-variation-settings:'FILL' 1;">settings</span>
      </div>
      <p class="font-bold text-sm">Settings</p>
    </div>
    <?php
    $sections = [
      'general'       => ['settings','General'],
      'announcements' => ['campaign','Announcements'],
      'subjects'      => ['menu_book','Subjects'],
      'timetable'     => ['calendar_month','Timetable'],
    ];
    foreach ($sections as $k => [$icon,$label]):
    ?>
    <a href="?section=<?php echo $k;?>" class="sidebar-link <?php echo $activeSection===$k?'active':'';?>">
      <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' <?php echo $activeSection===$k?'1':'0';?>;"><?php echo $icon;?></span>
      <?php echo $label;?>
    </a>
    <?php endforeach;?>
    <div class="mt-auto pt-4 border-t border-slate-100">
      <a href="admin_dashboard.php" class="sidebar-link">
        <span class="material-symbols-outlined text-lg">arrow_back</span>Back to Dashboard
      </a>
    </div>
  </aside>

  <!-- MAIN CONTENT -->
  <main class="flex-1 p-4 md:p-6 max-w-3xl">

    <!-- Mobile nav pills -->
    <div class="flex gap-2 overflow-x-auto pb-3 md:hidden mb-4">
      <?php foreach ($sections as $k => [$icon,$label]): ?>
      <a href="?section=<?php echo $k;?>"
         class="flex-shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all
         <?php echo $activeSection===$k?'bg-primary text-white border-primary':'bg-white text-slate-600 border-slate-200';?>">
        <span class="material-symbols-outlined text-sm"><?php echo $icon;?></span><?php echo $label;?>
      </a>
      <?php endforeach;?>
    </div>

    <!-- ALERT -->
    <?php if ($msg): ?>
    <div class="mb-4 rounded-xl px-4 py-3 text-sm font-semibold flex items-center gap-2 anim
      <?php echo $msgType==='success'?'bg-green-50 text-green-700 border border-green-200':'bg-blue-50 text-blue-700 border border-blue-200';?>">
      <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;"><?php echo $msgType==='success'?'check_circle':'info';?></span>
      <?php echo htmlspecialchars($msg);?>
    </div>
    <?php endif;?>

    <!-- ===== GENERAL SETTINGS ===== -->
    <?php if ($activeSection === 'general'): ?>
    <div class="card anim">
      <div class="section-heading">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">settings</span>
        <h2>General Settings</h2>
      </div>
      <form method="POST" class="space-y-4">
        <div>
          <label class="form-label">Site Name</label>
          <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'CollegeConnect');?>"/>
        </div>
        <div class="toggle-wrap">
          <div>
            <p class="font-semibold text-sm">Maintenance Mode</p>
            <p class="text-xs text-slate-400 mt-0.5">When ON, only admins can access the site.</p>
          </div>
          <label class="toggle">
            <input type="checkbox" name="maintenance_mode" <?php echo !empty($settings['maintenance_mode'])?'checked':'';?>/>
            <div class="toggle-track"><div class="toggle-thumb"></div></div>
          </label>
        </div>
        <div>
          <label class="form-label">Maintenance Message</label>
          <input type="text" name="maintenance_message" class="form-input"
                 value="<?php echo htmlspecialchars($settings['maintenance_message'] ?? '');?>"/>
        </div>
        <button type="submit" name="save_general" class="btn-primary">
          <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">save</span>Save Settings
        </button>
      </form>
    </div>

    <!-- ===== ANNOUNCEMENTS ===== -->
    <?php elseif ($activeSection === 'announcements'): ?>
    <div class="space-y-4">
      <div class="card anim">
        <div class="section-heading">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">campaign</span>
          <h2>Add Announcement</h2>
        </div>
        <form method="POST" class="space-y-3">
          <div>
            <label class="form-label">Title</label>
            <input type="text" name="ann_title" class="form-input" placeholder="Announcement title..."/>
          </div>
          <div>
            <label class="form-label">Message</label>
            <textarea name="ann_body" class="form-input" placeholder="Announcement body..."></textarea>
          </div>
          <button type="submit" name="add_announcement" class="btn-primary">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">add</span>Post Announcement
          </button>
        </form>
      </div>

      <div class="card anim" style="animation-delay:.1s">
        <div class="section-heading">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">list</span>
          <h2>All Announcements (<?php echo count($announcements);?>)</h2>
        </div>
        <?php if (empty($announcements)): ?>
        <p class="text-sm text-slate-400 text-center py-6">No announcements yet.</p>
        <?php else: foreach ($announcements as $ann): ?>
        <div class="flex items-start gap-3 py-3 border-b border-slate-100 last:border-0">
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm"><?php echo htmlspecialchars($ann['title']);?></p>
            <p class="text-xs text-slate-400 mt-0.5 line-clamp-2"><?php echo htmlspecialchars($ann['message']);?></p>
            <p class="text-[10px] text-slate-300 mt-1"><?php echo date('d M Y', strtotime($ann['created_at']));?></p>
          </div>
          <form method="POST">
            <input type="hidden" name="ann_id" value="<?php echo $ann['id'];?>"/>
            <button type="submit" name="delete_ann" class="btn-danger p-2" onclick="return confirm('Delete this announcement?')">
              <span class="material-symbols-outlined text-sm">delete</span>
            </button>
          </form>
        </div>
        <?php endforeach; endif;?>
      </div>
    </div>

    <!-- ===== SUBJECTS ===== -->
    <?php elseif ($activeSection === 'subjects'): ?>
    <div class="space-y-4">
      <div class="card anim">
        <div class="section-heading">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">menu_book</span>
          <h2>Add Subject</h2>
        </div>
        <form method="POST" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">Branch</label>
              <select name="branch_code" class="form-input">
                <?php foreach ($branches_all as $b): ?>
                <option value="<?php echo $b['branch_code'];?>"><?php echo $b['branch_code'];?>  <?php echo $b['branch_name'];?></option>
                <?php endforeach;?>
              </select>
            </div>
            <div>
              <label class="form-label">Semester</label>
              <select name="semester" class="form-input">
                <?php for($s=1;$s<=6;$s++): ?><option value="<?php echo $s;?>">Sem <?php echo $s;?></option><?php endfor;?>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">Subject Code</label>
              <input type="text" name="subject_code" class="form-input" placeholder="e.g. 316318"/>
            </div>
            <div>
              <label class="form-label">Short Name</label>
              <input type="text" name="subject_short" class="form-input" placeholder="e.g. MLG" maxlength="10"/>
            </div>
          </div>
          <div>
            <label class="form-label">Subject Name</label>
            <input type="text" name="subject_name" class="form-input" placeholder="e.g. Machine Learning" required/>
          </div>
          <div>
            <label class="form-label">Assign Teacher (optional)</label>
            <select name="teacher_id" class="form-input">
              <option value="">Not Assigned</option>
              <?php foreach ($teachers_all as $t): ?>
              <option value="<?php echo $t['id'];?>"><?php echo htmlspecialchars($t['name']);?> (<?php echo $t['department'];?>)</option>
              <?php endforeach;?>
            </select>
          </div>
          <button type="submit" name="add_subject" class="btn-primary">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">add</span>Add Subject
          </button>
        </form>
      </div>

      <div class="card anim" style="animation-delay:.1s">
        <div class="section-heading">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">list</span>
          <h2>All Subjects (<?php echo count($subjects_all);?>)</h2>
        </div>
        <?php
        $prevBranchSem = '';
        foreach ($subjects_all as $sub):
          $bsKey = $sub['branch_code'].'_'.$sub['semester'];
          if ($bsKey !== $prevBranchSem): $prevBranchSem = $bsKey;
        ?>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-3 mb-1">
          <?php echo $sub['branch_code'];?> Sem <?php echo $sub['semester'];?>
        </p>
        <?php endif;?>
        <div class="flex items-center justify-between py-2 border-b border-slate-100 last:border-0 gap-2">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($sub['subject_name']);?></p>
            <p class="text-[10px] text-slate-400">
              <?php echo htmlspecialchars($sub['subject_code']);?>
              <?php if (!empty($sub['teacher_name'])): ?> &bull; <?php echo htmlspecialchars($sub['teacher_name']);?><?php endif;?>
            </p>
          </div>
          <form method="POST">
            <input type="hidden" name="sub_id" value="<?php echo $sub['id'];?>"/>
            <button type="submit" name="delete_subject" class="btn-danger p-2" onclick="return confirm('Delete this subject?')">
              <span class="material-symbols-outlined text-sm">delete</span>
            </button>
          </form>
        </div>
        <?php endforeach;?>
      </div>
    </div>

    <!-- ===== TIMETABLE ===== -->
    <?php elseif ($activeSection === 'timetable'): ?>
    <div class="space-y-4">
      <div class="card anim">
        <div class="section-heading">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">calendar_month</span>
          <h2>Add Timetable Slot</h2>
        </div>
        <form method="POST" class="space-y-3">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="form-label">Branch</label>
              <select name="tt_branch" class="form-input">
                <?php foreach ($branches_all as $b): ?><option value="<?php echo $b['branch_code'];?>"><?php echo $b['branch_code'];?></option><?php endforeach;?>
              </select>
            </div>
            <div>
              <label class="form-label">Semester</label>
              <select name="tt_semester" class="form-input">
                <?php for($s=1;$s<=6;$s++): ?><option value="<?php echo $s;?>">Sem <?php echo $s;?></option><?php endfor;?>
              </select>
            </div>
            <div>
              <label class="form-label">Type</label>
              <select name="tt_type" class="form-input" onchange="document.getElementById('examDateRow').classList.toggle('hidden',this.value!=='exam')">
                <option value="class">Class</option>
                <option value="exam">Exam</option>
              </select>
            </div>
          </div>
          <div>
            <label class="form-label">Subject</label>
            <select name="tt_subject" class="form-input">
              <?php foreach ($subjects_all as $sub): ?>
              <option value="<?php echo $sub['id'];?>"><?php echo $sub['branch_code'];?> Sem<?php echo $sub['semester'];?> <?php echo htmlspecialchars($sub['subject_name']);?></option>
              <?php endforeach;?>
            </select>
          </div>
          <div>
            <label class="form-label">Teacher (optional)</label>
            <select name="tt_teacher" class="form-input">
              <option value="">Not Assigned</option>
              <?php foreach ($teachers_all as $t): ?><option value="<?php echo $t['id'];?>"><?php echo htmlspecialchars($t['name']);?></option><?php endforeach;?>
            </select>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="form-label">Day</label>
              <select name="tt_day" class="form-input">
                <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d): ?><option><?php echo $d;?></option><?php endforeach;?>
              </select>
            </div>
            <div>
              <label class="form-label">Room No.</label>
              <input type="text" name="tt_room" class="form-input" placeholder="e.g. R-301"/>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div><label class="form-label">Start Time</label><input type="time" name="tt_start" class="form-input" value="08:00"/></div>
            <div><label class="form-label">End Time</label><input type="time" name="tt_end" class="form-input" value="09:00"/></div>
          </div>
          <div id="examDateRow" class="hidden">
            <label class="form-label">Exam Date</label>
            <input type="date" name="tt_examdate" class="form-input"/>
          </div>
          <button type="submit" name="add_timetable" class="btn-primary">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">add</span>Add Slot
          </button>
        </form>
      </div>

      <div class="card anim" style="animation-delay:.1s">
        <div class="section-heading">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">list</span>
          <h2>Timetable Entries (<?php echo count($timetable_all);?>)</h2>
        </div>
        <?php foreach ($timetable_all as $tt): ?>
        <div class="flex items-center gap-3 py-2 border-b border-slate-100 last:border-0">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold truncate"><?php echo htmlspecialchars($tt['subject_name']);?></p>
            <p class="text-[10px] text-slate-400">
              <?php echo $tt['branch_code'];?> Sem<?php echo $tt['semester'];?>
              &bull; <?php echo $tt['timetable_type'] === 'exam' ? ($tt['exam_date'] ? date('d M', strtotime($tt['exam_date'])) : 'Exam') : ' ' . ($tt['day_name'] ?? '');?>
              &bull; <?php echo date('h:i A',strtotime($tt['start_time']));?><?php echo date('h:i A',strtotime($tt['end_time']));?>
              <?php if (!empty($tt['room_no'])): ?>&bull; <?php echo htmlspecialchars($tt['room_no']);?><?php endif;?>
            </p>
          </div>
          <form method="POST">
            <input type="hidden" name="tt_id" value="<?php echo $tt['id'];?>"/>
            <button type="submit" name="delete_tt" class="btn-danger p-2" onclick="return confirm('Delete this slot?')">
              <span class="material-symbols-outlined text-sm">delete</span>
            </button>
          </form>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <?php endif;?>

  </main>
</div>
</body>
</html>
