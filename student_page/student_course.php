<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);

function getBranchCodeFromDepartment($department)
{
    $dept = strtolower(trim($department));
    $validCodes = ['co', 'if', 'ce', 'me', 'ee', 'ae', 'ej'];
    if (in_array($dept, $validCodes)) return strtoupper($dept);
    $map = [
        'computer engineering'=>'CO','computer science & engineering'=>'CO',
        'computer science engineering'=>'CO','computer'=>'CO',
        'information technology'=>'IF','it'=>'IF',
        'civil engineering'=>'CE','civil'=>'CE',
        'mechanical engineering'=>'ME','mechanical'=>'ME',
        'electrical engineering'=>'EE','electrical'=>'EE',
        'automobile engineering'=>'AE','automobile'=>'AE',
        'electronics & telecommunication'=>'EJ','electronics and telecommunication'=>'EJ',
        'entc'=>'EJ','electronics'=>'EJ'
    ];
    return $map[$dept] ?? '';
}

$studentName       = $student['full_name'] ?? 'Student';
$studentDepartment = $student['department'] ?? '';
$studentSemester   = (int)($student['semester'] ?? 0);
$branchCode        = getBranchCodeFromDepartment($studentDepartment);

$courses=$subjects=$timetables=false;
$pageError='';

if (empty($studentDepartment)) {
    $pageError="Your department is not set in profile. Please complete your profile first.";
} elseif ($studentSemester<=0) {
    $pageError="Your semester is not set in profile. Please update your profile first.";
} elseif (empty($branchCode)) {
    $pageError="Branch code mapping not found for your department: ".htmlspecialchars($studentDepartment);
} else {
    $st=$conn->prepare("SELECT * FROM courses WHERE branch_code=? AND semester=? AND is_active=1 ORDER BY id DESC");
    if($st){$st->bind_param("si",$branchCode,$studentSemester);$st->execute();$courses=$st->get_result();}
    $st=$conn->prepare("SELECT * FROM subjects WHERE branch_code=? AND semester=? ORDER BY subject_name ASC");
    if($st){$st->bind_param("si",$branchCode,$studentSemester);$st->execute();$subjects=$st->get_result();}
    $st=$conn->prepare("SELECT tt.*,s.subject_name,s.subject_code,t.name AS teacher_name FROM timetables tt LEFT JOIN subjects s ON tt.subject_id=s.id LEFT JOIN teachers t ON tt.teacher_id=t.id WHERE tt.branch_code=? AND tt.semester=? ORDER BY FIELD(tt.day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'),tt.start_time ASC");
    if($st){$st->bind_param("si",$branchCode,$studentSemester);$st->execute();$timetables=$st->get_result();}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>My Courses — <?php echo htmlspecialchars($settings['site_name']); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,500,0,0" rel="stylesheet"/>
<style>
:root{
  --p:#5b6af0;--p-l:#eef0fe;--p-d:#3d4cd6;
  --acc:#f97316;--acc-l:#fff3eb;
  --ok:#22c55e;--ok-l:#f0fdf4;
  --bg:#f1f3fb;--sur:#fff;--sur2:#f7f8ff;
  --bdr:#e4e7f7;--txt:#1a1d3a;--muted:#7880a8;--light:#a8afd4;
  --sh:0 4px 20px rgba(91,106,240,.10);--sh-sm:0 1px 4px rgba(91,106,240,.07);
  --r:16px;--r-sm:10px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--txt);min-height:100vh}

/* HEADER */
.hdr{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.88);backdrop-filter:blur(16px);border-bottom:1px solid var(--bdr);height:64px;display:flex;align-items:center;padding:0 24px}
.hdr-in{max-width:1100px;margin:0 auto;width:100%;display:flex;align-items:center;gap:14px}
.back{display:flex;align-items:center;gap:5px;background:var(--p-l);color:var(--p);border:none;cursor:pointer;padding:8px 14px;border-radius:50px;font-family:inherit;font-size:13px;font-weight:700;text-decoration:none;transition:.2s}
.back:hover{background:var(--p);color:#fff}
.back .ms{font-size:18px}
.hdr-t{flex:1}.hdr-t h1{font-size:15px;font-weight:700}.hdr-t p{font-size:11.5px;color:var(--muted);margin-top:1px}
.logout{padding:7px 16px;border-radius:50px;background:#fff0f0;color:#e53e3e;font-size:13px;font-weight:600;text-decoration:none;border:1px solid #fecaca;transition:.2s}
.logout:hover{background:#e53e3e;color:#fff}

/* HERO */
.hero{background:linear-gradient(135deg,#5b6af0 0%,#7c3aed 55%,#a855f7 100%);padding:36px 24px 48px;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;inset:0;background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23fff' fill-opacity='.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6z'/%3E%3C/g%3E%3C/svg%3E")}
.hero-in{max-width:1100px;margin:0 auto;position:relative}
.hero-sub{font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.6);margin-bottom:6px}
.hero-name{font-size:28px;font-weight:800;color:#fff;margin-bottom:14px}
.badges{display:flex;flex-wrap:wrap;gap:8px}
.badge{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;border-radius:50px;padding:5px 12px;font-size:12.5px;font-weight:500;backdrop-filter:blur(8px)}
.badge .ms{font-size:14px}

/* TABS */
.tabs-bar{background:var(--sur);border-bottom:1px solid var(--bdr);position:sticky;top:64px;z-index:90}
.tabs{max-width:1100px;margin:0 auto;display:flex;padding:0 24px;overflow-x:auto;scrollbar-width:none}
.tabs::-webkit-scrollbar{display:none}
.tab{display:flex;align-items:center;gap:7px;padding:14px 20px;font-size:13.5px;font-weight:600;color:var(--muted);border:none;border-bottom:3px solid transparent;cursor:pointer;white-space:nowrap;transition:.2s;background:none;font-family:inherit}
.tab .ms{font-size:18px}
.tab:hover{color:var(--p)}
.tab.on{color:var(--p);border-bottom-color:var(--p)}

/* MAIN */
.main{max-width:1100px;margin:0 auto;padding:28px 24px 100px}
.sec{display:none}.sec.on{display:block}

/* SEC HEADER */
.sec-hdr{display:flex;align-items:center;gap:12px;margin-bottom:20px}
.sec-ic{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--p-l);color:var(--p)}
.sec-ic .ms{font-size:22px}
.sec-ttl{font-size:20px;font-weight:700}
.sec-cnt{margin-left:auto;background:var(--p-l);color:var(--p);font-size:12px;font-weight:700;padding:3px 10px;border-radius:50px}

/* COURSE CARDS */
.c-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
.c-card{background:var(--sur);border:1px solid var(--bdr);border-radius:var(--r);padding:22px;box-shadow:var(--sh-sm);transition:.25s;position:relative;overflow:hidden;animation:fadeUp .4s ease both}
.c-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--p),#7c3aed)}
.c-card:hover{box-shadow:var(--sh);transform:translateY(-2px)}
.c-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}
.c-icon{width:44px;height:44px;border-radius:12px;background:var(--p-l);color:var(--p);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.c-icon .ms{font-size:22px}
.c-name{font-size:15px;font-weight:700;line-height:1.4;flex:1}
.c-sem{background:var(--acc-l);color:var(--acc);font-size:11px;font-weight:700;padding:3px 9px;border-radius:50px;white-space:nowrap}
.c-code{font-family:'Fira Code',monospace;font-size:11.5px;color:var(--muted);background:var(--sur2);padding:2px 8px;border-radius:6px;display:inline-block;margin-bottom:10px}
.c-desc{font-size:13px;color:var(--muted);line-height:1.6}

/* TABLE */
.tbl-card{background:var(--sur);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden;box-shadow:var(--sh-sm);animation:fadeUp .4s .05s ease both}
.tbl-search{padding:14px 20px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;gap:10px}
.tbl-search .ms{color:var(--muted);font-size:20px}
.srch{border:none;outline:none;flex:1;font-family:inherit;font-size:14px;color:var(--txt);background:transparent}
.srch::placeholder{color:var(--light)}
table{width:100%;border-collapse:collapse}
thead tr{background:var(--sur2)}
th{padding:12px 18px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:var(--muted)}
tbody tr{border-top:1px solid var(--bdr);transition:background .15s}
tbody tr:hover{background:var(--sur2)}
td{padding:14px 18px;font-size:13.5px}
.s-code{font-family:'Fira Code',monospace;background:var(--p-l);color:var(--p);font-size:12px;font-weight:500;padding:3px 9px;border-radius:6px;display:inline-block}
.s-name{font-weight:600;color:var(--txt)}
.sem-b{background:var(--ok-l);color:var(--ok);font-size:12px;font-weight:600;padding:3px 10px;border-radius:50px;display:inline-block}

/* TIMETABLE */
.day-lbl{display:inline-flex;align-items:center;gap:4px;font-weight:700;font-size:13px}
.day-dot{width:8px;height:8px;border-radius:50%;background:var(--p);display:inline-block}
.t-chip{background:var(--sur2);border:1px solid var(--bdr);color:var(--muted);font-size:12px;font-weight:500;padding:3px 9px;border-radius:6px;white-space:nowrap;font-family:'Fira Code',monospace}
.tch-chip{display:flex;align-items:center;gap:5px;font-size:13px;color:var(--muted)}
.tch-chip .ms{font-size:15px;color:var(--p)}
.room{background:var(--acc-l);color:var(--acc);font-size:12px;font-weight:600;padding:3px 9px;border-radius:6px;display:inline-block}

/* EMPTY */
.empty{text-align:center;padding:56px 24px}
.e-ic{width:64px;height:64px;border-radius:20px;background:var(--sur2);border:1px solid var(--bdr);display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.e-ic .ms{font-size:32px;color:var(--light)}
.e-ttl{font-size:16px;font-weight:700;color:var(--muted);margin-bottom:6px}
.e-desc{font-size:13px;color:var(--light)}

/* ERROR */
.err-card{background:#fff8f8;border:1px solid #fecaca;border-radius:var(--r);padding:28px;display:flex;gap:16px;align-items:flex-start;margin-top:28px}
.err-ic{width:48px;height:48px;border-radius:14px;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.err-ic .ms{color:#e53e3e;font-size:26px}
.err-ttl{font-size:16px;font-weight:700;color:#c53030;margin-bottom:6px}
.err-msg{font-size:13.5px;color:#e53e3e;margin-bottom:16px}
.fix-btn{display:inline-flex;align-items:center;gap:6px;background:var(--p);color:#fff;padding:9px 18px;border-radius:50px;font-size:13.5px;font-weight:600;text-decoration:none;transition:.2s}
.fix-btn:hover{background:var(--p-d);transform:translateY(-1px)}
.fix-btn .ms{font-size:16px}

/* BOTTOM NAV */
.bnav{position:fixed;bottom:0;left:0;right:0;background:rgba(255,255,255,.95);backdrop-filter:blur(16px);border-top:1px solid var(--bdr);padding:8px 16px 12px;display:none;z-index:200}
.bnav-in{display:flex;justify-content:space-around}
.nv{display:flex;flex-direction:column;align-items:center;gap:3px;color:var(--light);text-decoration:none;font-size:10px;font-weight:600;padding:4px 12px;border-radius:12px;transition:.2s}
.nv .ms{font-size:22px}
.nv.on{color:var(--p);background:var(--p-l)}
.nv:hover:not(.on){color:var(--p)}

@media(max-width:640px){
  .bnav{display:block}
  .hero{padding:24px 16px 36px}
  .hero-name{font-size:22px}
  .main{padding:20px 16px 90px}
  .tabs{padding:0 12px}
  .tab{padding:12px 14px;font-size:13px}
  .c-grid{grid-template-columns:1fr}
  th,td{padding:10px 12px}
}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fu{animation:fadeUp .4s ease both}
.fu1{animation-delay:.05s}
.fu2{animation-delay:.10s}
.fu3{animation-delay:.15s}
</style>
</head>
<body>

<!-- HEADER -->
<header class="hdr">
  <div class="hdr-in">
    <a href="student_dashboard.php" class="back">
      <span class="material-symbols-rounded ms">arrow_back</span>Back
    </a>
    <div class="hdr-t">
      <h1><?php echo htmlspecialchars($settings['site_name']); ?></h1>
      <p>Student Course Portal</p>
    </div>
    <a href="../auth/logout.php" class="logout">Logout</a>
  </div>
</header>

<!-- HERO -->
<div class="hero">
  <div class="hero-in fu">
    <p class="hero-sub">Welcome back</p>
    <h2 class="hero-name"><?php echo htmlspecialchars($studentName); ?></h2>
    <div class="badges">
      <span class="badge"><span class="material-symbols-rounded ms">school</span><?php echo htmlspecialchars($studentDepartment ?: 'Dept not set'); ?></span>
      <span class="badge"><span class="material-symbols-rounded ms">layers</span>Semester <?php echo $studentSemester > 0 ? $studentSemester : '—'; ?></span>
      <span class="badge"><span class="material-symbols-rounded ms">tag</span>Branch: <?php echo htmlspecialchars($branchCode ?: 'Not mapped'); ?></span>
    </div>
  </div>
</div>

<?php if (!empty($pageError)): ?>
<div style="max-width:1100px;margin:0 auto;padding:0 24px">
  <div class="err-card fu">
    <div class="err-ic"><span class="material-symbols-rounded ms">warning</span></div>
    <div>
      <div class="err-ttl">Profile / Data Issue</div>
      <p class="err-msg"><?php echo $pageError; ?></p>
      <a href="complete_profile.php" class="fix-btn"><span class="material-symbols-rounded ms">edit</span>Complete Profile</a>
    </div>
  </div>
</div>

<?php else: ?>

<!-- TABS -->
<div class="tabs-bar">
  <div class="tabs">
    <button class="tab on" onclick="sw('courses',this)"><span class="material-symbols-rounded ms">menu_book</span>Courses</button>
    <button class="tab" onclick="sw('subjects',this)"><span class="material-symbols-rounded ms">subject</span>Subjects</button>
    <button class="tab" onclick="sw('timetable',this)"><span class="material-symbols-rounded ms">calendar_month</span>Timetable</button>
  </div>
</div>

<main class="main">

  <!-- COURSES -->
  <div class="sec on" id="tc">
    <div class="sec-hdr fu">
      <div class="sec-ic"><span class="material-symbols-rounded ms">menu_book</span></div>
      <h3 class="sec-ttl">My Courses</h3>
      <?php if($courses&&$courses->num_rows>0):?><span class="sec-cnt"><?php echo $courses->num_rows;?> courses</span><?php endif;?>
    </div>
    <div class="c-grid">
      <?php if($courses&&$courses->num_rows>0):?>
        <?php $i=0;while($c=$courses->fetch_assoc()):$i++;?>
          <div class="c-card fu<?php echo min($i,3);?>">
            <div class="c-card-top">
              <div class="c-icon"><span class="material-symbols-rounded ms">auto_stories</span></div>
              <div class="c-name"><?php echo htmlspecialchars($c['course_name']??'Course');?></div>
              <span class="c-sem">Sem <?php echo htmlspecialchars($c['semester']);?></span>
            </div>
            <div class="c-code"><?php echo htmlspecialchars($c['course_code']??'—');?></div>
            <p class="c-desc"><?php echo !empty($c['description'])?htmlspecialchars($c['description']):'No description available.';?></p>
          </div>
        <?php endwhile;?>
      <?php else:?>
        <div class="tbl-card" style="grid-column:1/-1">
          <div class="empty"><div class="e-ic"><span class="material-symbols-rounded ms">menu_book</span></div><div class="e-ttl">No courses found</div><p class="e-desc">No active courses for your department and semester yet.</p></div>
        </div>
      <?php endif;?>
    </div>
  </div>

  <!-- SUBJECTS -->
  <div class="sec" id="ts">
    <div class="sec-hdr fu">
      <div class="sec-ic"><span class="material-symbols-rounded ms">subject</span></div>
      <h3 class="sec-ttl">Subjects</h3>
      <?php if($subjects&&$subjects->num_rows>0):?><span class="sec-cnt"><?php echo $subjects->num_rows;?> subjects</span><?php endif;?>
    </div>
    <div class="tbl-card">
      <?php if($subjects&&$subjects->num_rows>0):?>
        <div class="tbl-search"><span class="material-symbols-rounded ms">search</span><input class="srch" id="ss" placeholder="Search subjects…" oninput="filt('ss','st')"/></div>
      <?php endif;?>
      <div style="overflow-x:auto">
        <table><thead><tr><th>#</th><th>Subject Code</th><th>Subject Name</th><th>Semester</th></tr></thead>
        <tbody id="st">
          <?php if($subjects&&$subjects->num_rows>0):?>
            <?php $n=0;while($s=$subjects->fetch_assoc()):$n++;?>
              <tr>
                <td style="color:var(--light);font-size:12px"><?php echo $n;?></td>
                <td><span class="s-code"><?php echo htmlspecialchars($s['subject_code']??'—');?></span></td>
                <td class="s-name"><?php echo htmlspecialchars($s['subject_name']??'—');?></td>
                <td><span class="sem-b">Sem <?php echo htmlspecialchars($s['semester']??'—');?></span></td>
              </tr>
            <?php endwhile;?>
          <?php else:?>
            <tr><td colspan="4"><div class="empty"><div class="e-ic"><span class="material-symbols-rounded ms">subject</span></div><div class="e-ttl">No subjects found</div><p class="e-desc">No subjects added yet.</p></div></td></tr>
          <?php endif;?>
        </tbody></table>
      </div>
    </div>
  </div>

  <!-- TIMETABLE -->
  <div class="sec" id="tt">
    <div class="sec-hdr fu">
      <div class="sec-ic"><span class="material-symbols-rounded ms">calendar_month</span></div>
      <h3 class="sec-ttl">Weekly Timetable</h3>
    </div>
    <div class="tbl-card">
      <div style="overflow-x:auto">
        <table><thead><tr><th>Day</th><th>Time</th><th>Subject</th><th>Teacher</th><th>Room</th></tr></thead>
        <tbody>
          <?php if($timetables&&$timetables->num_rows>0):?>
            <?php while($r=$timetables->fetch_assoc()):?>
              <tr>
                <td><span class="day-lbl"><span class="day-dot"></span><?php echo htmlspecialchars($r['day_name']??'—');?></span></td>
                <td><span class="t-chip"><?php echo date('h:i A',strtotime($r['start_time']));?> – <?php echo date('h:i A',strtotime($r['end_time']));?></span></td>
                <td>
                  <div class="s-name"><?php echo htmlspecialchars($r['subject_name']??'—');?></div>
                  <?php if(!empty($r['subject_code'])):?><div style="font-size:11px;color:var(--muted);font-family:'Fira Code',monospace;margin-top:2px"><?php echo htmlspecialchars($r['subject_code']);?></div><?php endif;?>
                </td>
                <td><span class="tch-chip"><span class="material-symbols-rounded ms">person</span><?php echo htmlspecialchars($r['teacher_name']??'Not Assigned');?></span></td>
                <td><?php if(!empty($r['room_no'])):?><span class="room"><?php echo htmlspecialchars($r['room_no']);?></span><?php else:?><span style="color:var(--light);font-size:12px">—</span><?php endif;?></td>
              </tr>
            <?php endwhile;?>
          <?php else:?>
            <tr><td colspan="5"><div class="empty"><div class="e-ic"><span class="material-symbols-rounded ms">calendar_month</span></div><div class="e-ttl">No timetable found</div><p class="e-desc">Timetable not set up yet.</p></div></td></tr>
          <?php endif;?>
        </tbody></table>
      </div>
    </div>
  </div>

</main>
<?php endif;?>

<!-- BOTTOM NAV -->
<nav class="bnav">
  <div class="bnav-in">
    <a href="student_dashboard.php" class="nv"><span class="material-symbols-rounded ms">home</span>Home</a>
    <a href="student_course.php" class="nv on"><span class="material-symbols-rounded ms">menu_book</span>Courses</a>
    <a href="student_studymaterial.php" class="nv"><span class="material-symbols-rounded ms">folder_open</span>Materials</a>
    <a href="student_profile.php" class="nv"><span class="material-symbols-rounded ms">person</span>Profile</a>
  </div>
</nav>

<script>
function sw(n,el){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('on'));
  document.querySelectorAll('.sec').forEach(s=>s.classList.remove('on'));
  el.classList.add('on');
  document.getElementById('t'+n[0]).classList.add('on');
}
function filt(iid,tid){
  const q=document.getElementById(iid).value.toLowerCase();
  document.querySelectorAll('#'+tid+' tr').forEach(r=>r.style.display=r.textContent.toLowerCase().includes(q)?'':'none');
}
</script>

<?php include 'topbar_scripts.php'; ?>
</body>
</html>