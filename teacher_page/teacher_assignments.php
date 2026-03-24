<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';
$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
$teacherId = (int)($teacher['id'] ?? 0);
$teacherSubjects = $conn->query("SELECT * FROM subjects WHERE teacher_id = $teacherId ORDER BY semester ASC");
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Assignments – CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
tailwind.config = {
  darkMode:"class",
  theme:{extend:{colors:{primary:"#4349cf"},fontFamily:{body:["Plus Jakarta Sans"]}}}
}
</script>
<style>
*{font-family:'Plus Jakarta Sans',sans-serif;}
:root{--primary:#4349cf;--grad:linear-gradient(135deg,#4349cf,#7479f5);}
body{min-height:100dvh;background:#f0f1ff;}
.dark body{background:#0d0e1c;}

/* Keyframes */
@keyframes fadeUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.88)}to{opacity:1;transform:scale(1)}}
@keyframes progressAnim{from{width:0}to{width:var(--prog)}}
@keyframes countUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse{0%,100%{box-shadow:0 0 0 0 rgba(67,73,207,0.4)}50%{box-shadow:0 0 0 8px rgba(67,73,207,0)}}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}

.fu{animation:fadeUp .4s ease both}
.fu1{animation:fadeUp .4s .07s ease both}
.fu2{animation:fadeUp .4s .14s ease both}
.fu3{animation:fadeUp .4s .21s ease both}
.fu4{animation:fadeUp .4s .28s ease both}

/* Cards */
.acard{
  background:white;
  border-radius:20px;
  border:1px solid #eef0ff;
  box-shadow:0 2px 12px rgba(67,73,207,.06);
  transition:all .22s;
  overflow:hidden;
}
.dark .acard{background:#1a1b2e;border-color:#2a2b45;}
.acard:hover{transform:translateY(-3px);box-shadow:0 10px 28px rgba(67,73,207,.14);}

/* Status colors */
.status-active{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;}
.status-draft{background:#fefce8;color:#ca8a04;border:1px solid #fde68a;}
.status-closed{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;}
.status-overdue{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.dark .status-active{background:#052e16;color:#86efac;border-color:#166534;}
.dark .status-draft{background:#451a03;color:#fcd34d;border-color:#92400e;}
.dark .status-closed{background:#1e293b;color:#94a3b8;border-color:#334155;}
.dark .status-overdue{background:#450a0a;color:#fca5a5;border-color:#7f1d1d;}

/* Progress */
.prog-track{height:8px;background:#e8eaf6;border-radius:99px;overflow:hidden;}
.dark .prog-track{background:#1e2040;}
.prog-fill{height:100%;border-radius:99px;background:var(--grad);animation:progressAnim .7s ease both;}

/* Priority dots */
.dot-high{background:#ef4444;box-shadow:0 0 0 3px #fee2e2;}
.dot-med{background:#f97316;box-shadow:0 0 0 3px #ffedd5;}
.dot-low{background:#22c55e;box-shadow:0 0 0 3px #dcfce7;}

/* Submission card */
.sub-card{
  display:flex;align-items:center;gap:12px;
  padding:12px 14px;background:white;
  border-radius:16px;border:1px solid #f1f3ff;
  transition:all .18s;
}
.dark .sub-card{background:#1a1b2e;border-color:#2a2b45;}
.sub-card:hover{box-shadow:0 4px 16px rgba(67,73,207,.1);}

/* Modals */
.modal-bg{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.52);backdrop-filter:blur(6px);display:none;align-items:flex-end;}
.modal-bg.open{display:flex;}
.modal-panel{background:white;border-radius:28px 28px 0 0;width:100%;max-height:92vh;overflow-y:auto;transform:translateY(100%);transition:transform .38s cubic-bezier(.22,1,.36,1);}
.dark .modal-panel{background:#13142a;}
.modal-bg.open .modal-panel{transform:translateY(0);}

/* Grading modal (full screen) */
#gradeModal{position:fixed;inset:0;z-index:9992;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;padding:16px;}
#gradeModal.open{display:flex;}
#gradePanel{background:white;border-radius:28px;width:100%;max-height:90vh;overflow-y:auto;animation:scaleIn .3s cubic-bezier(.34,1.4,.64,1) both;}
.dark #gradePanel{background:#13142a;}

/* Btn */
.btn-primary{background:var(--grad);color:white;font-weight:700;border-radius:14px;box-shadow:0 4px 14px rgba(67,73,207,.3);transition:all .2s;}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(67,73,207,.4);}
.btn-primary:active{transform:scale(.95);}
.btn-ghost{background:white;border:1.5px solid #e8eaf6;color:#475569;font-weight:600;border-radius:14px;transition:all .2s;}
.dark .btn-ghost{background:#1a1b2e;border-color:#2a2b45;color:#94a3b8;}
.btn-ghost:hover{border-color:var(--primary);color:var(--primary);}

/* Grade bubble */
.grade-A{background:#f0fdf4;color:#16a34a;}
.grade-B{background:#eff6ff;color:#2563eb;}
.grade-C{background:#fff7ed;color:#ea580c;}
.grade-F{background:#fef2f2;color:#dc2626;}
.dark .grade-A{background:#052e16;color:#86efac;}
.dark .grade-B{background:#172554;color:#93c5fd;}
.dark .grade-C{background:#431407;color:#fdba74;}
.dark .grade-F{background:#450a0a;color:#fca5a5;}

/* Tab bar */
.atab{padding:10px 16px;font-size:12px;font-weight:700;color:#94a3b8;position:relative;cursor:pointer;transition:color .2s;white-space:nowrap;}
.atab::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;border-radius:3px 3px 0 0;background:var(--primary);transform:scaleX(0);transition:transform .28s cubic-bezier(.34,1.4,.64,1);}
.atab.active{color:var(--primary);}
.atab.active::after{transform:scaleX(1);}

/* Toast */
.toast{position:fixed;top:72px;left:50%;transform:translateX(-50%);z-index:9999;animation:countUp .3s ease both;background:white;box-shadow:0 8px 28px rgba(0,0,0,.14);border-radius:16px;padding:10px 18px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;white-space:nowrap;}
.dark .toast{background:#1e2040;color:white;}

::-webkit-scrollbar{width:4px}
::-webkit-scrollbar-thumb{background:#c7d0ff;border-radius:4px}
</style>
</head>
<body class="bg-[#f0f1ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<?php
$pageTitle  = "Assignments";
$activePage = "assignments";
include __DIR__ . '/teacher_topbar.php';
?>

<!-- HERO -->
<div class="px-4 pt-4 fu">
  <div class="rounded-3xl p-5 text-white relative overflow-hidden shadow-xl shadow-indigo-300/30" style="background:linear-gradient(135deg,#4349cf 0%,#6b72f0 50%,#9b8af5 100%)">
    <div class="absolute right-0 top-0 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:160px;font-variation-settings:'FILL' 1;">assignment</span>
    </div>
    <div class="relative z-10">
      <p class="text-white/60 text-[11px] font-bold uppercase tracking-widest">Assignment Manager</p>
      <h1 class="text-2xl font-bold mt-1" style="font-family:'Plus Jakarta Sans',sans-serif;">Track & Grade</h1>
      <p class="text-white/70 text-xs mt-1">Manage assignments & review submissions</p>
      <div class="flex flex-wrap gap-3 mt-4">
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl" id="heroTotal">5</p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Total</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl text-green-300" id="heroActive">3</p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Active</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl text-yellow-300" id="heroPending">28</p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Pending</p>
        </div>
        <div class="bg-white/15 backdrop-blur-sm rounded-2xl px-3 py-2 text-center min-w-[56px]">
          <p class="font-bold text-xl text-red-300" id="heroOverdue">4</p>
          <p class="text-white/60 text-[9px] font-bold uppercase">Overdue</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- TABS -->
<div class="bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 sticky top-0 z-10 fu1">
  <div class="flex overflow-x-auto" style="-ms-overflow-style:none;scrollbar-width:none">
    <button class="atab active" onclick="switchTab(this,'list')">📋 All Assignments</button>
    <button class="atab" onclick="switchTab(this,'submissions')">📥 Submissions <span id="subBadge" class="inline-flex items-center justify-center w-5 h-5 bg-red-500 text-white text-[9px] rounded-full font-bold ml-1">28</span></button>
    <button class="atab" onclick="switchTab(this,'graded')">✅ Graded</button>
    <button class="atab" onclick="switchTab(this,'analytics')">📊 Analytics</button>
  </div>
</div>

<main class="px-4 py-4 pb-28 space-y-4">

<!-- ══════ TAB: ASSIGNMENTS LIST ══════ -->
<div id="tab-list">
  <!-- Filter row -->
  <div class="flex items-center gap-2 overflow-x-auto pb-1 fu2" style="-ms-overflow-style:none;scrollbar-width:none">
    <button class="shrink-0 text-[11px] font-bold px-3 py-1.5 rounded-full bg-primary text-white" onclick="filterA(this,'all')">All</button>
    <button class="shrink-0 text-[11px] font-bold px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 text-slate-500" onclick="filterA(this,'active')">Active</button>
    <button class="shrink-0 text-[11px] font-bold px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 text-slate-500" onclick="filterA(this,'draft')">Draft</button>
    <button class="shrink-0 text-[11px] font-bold px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 text-slate-500" onclick="filterA(this,'overdue')">Overdue</button>
    <button class="shrink-0 text-[11px] font-bold px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 text-slate-500" onclick="filterA(this,'closed')">Closed</button>
    <button onclick="openCreate()" class="shrink-0 ml-auto flex items-center gap-1 text-[11px] font-bold px-3 py-1.5 rounded-full btn-primary">
      <span class="material-symbols-outlined text-[14px]">add</span>New
    </button>
  </div>

  <div id="assignList" class="space-y-3 fu3">
    <?php
    $assignments=[
      ['id'=>1,'title'=>'Physics Lab Report 4','sub'=>'Physics','due'=>'Oct 28, 2024','status'=>'active','priority'=>'high','total'=>42,'submitted'=>38,'graded'=>30,'desc'=>'Write a detailed lab report on the pendulum experiment with error analysis.','marks'=>20],
      ['id'=>2,'title'=>'Calculus Problem Set 7','sub'=>'Mathematics','due'=>'Oct 30, 2024','status'=>'active','priority'=>'med','total'=>55,'submitted'=>45,'graded'=>45,'desc'=>'Solve integration problems from Chapter 7 including definite and indefinite integrals.','marks'=>15],
      ['id'=>3,'title'=>'Organic Reactions Essay','sub'=>'Chemistry','due'=>'Oct 25, 2024','status'=>'overdue','priority'=>'high','total'=>38,'submitted'=>22,'graded'=>18,'desc'=>'Analyze substitution and elimination reactions with real-world examples.','marks'=>25],
      ['id'=>4,'title'=>'Data Structures Quiz','sub'=>'CS Fundamentals','due'=>'Nov 2, 2024','status'=>'active','priority'=>'low','total'=>30,'submitted'=>0,'graded'=>0,'desc'=>'Online quiz covering arrays, linked lists, and trees. Open book allowed.','marks'=>10],
      ['id'=>5,'title'=>'Lab Safety Report','sub'=>'Chemistry','due'=>'Oct 15, 2024','status'=>'closed','priority'=>'low','total'=>38,'submitted'=>36,'graded'=>36,'desc'=>'Documentation of lab safety procedures and incident reporting.','marks'=>5],
    ];
    foreach($assignments as $i=>$a):
      $pct = $a['total']>0 ? round($a['submitted']/$a['total']*100) : 0;
      $gpct = $a['submitted']>0 ? round($a['graded']/$a['submitted']*100) : 0;
      $delay=0.04+$i*.06;
      $pending = $a['submitted']-$a['graded'];
    ?>
    <div class="acard" data-status="<?php echo $a['status'];?>" style="animation:fadeUp .4s <?php echo $delay;?>s ease both;opacity:0;animation-fill-mode:both">
      <!-- Header -->
      <div class="p-4 pb-3">
        <div class="flex items-start justify-between gap-2 mb-2">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <div class="w-2 h-2 rounded-full <?php echo $a['priority']==='high'?'dot-high':($a['priority']==='med'?'dot-med':'dot-low');?>"></div>
              <span class="text-[10px] font-bold text-primary bg-primary/10 px-2 py-0.5 rounded-full"><?php echo $a['sub'];?></span>
            </div>
            <h3 class="font-bold text-sm leading-tight"><?php echo htmlspecialchars($a['title']);?></h3>
            <p class="text-xs text-slate-400 mt-0.5 line-clamp-1"><?php echo htmlspecialchars($a['desc']);?></p>
          </div>
          <span class="shrink-0 status-<?php echo $a['status'];?> text-[10px] font-bold px-2.5 py-1 rounded-full capitalize"><?php echo $a['status'];?></span>
        </div>
        <!-- Due date & marks -->
        <div class="flex items-center gap-3 text-[11px] text-slate-500 mb-3">
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">calendar_month</span>Due <?php echo $a['due'];?></span>
          <span class="flex items-center gap-1"><span class="material-symbols-outlined text-sm">grade</span><?php echo $a['marks'];?> marks</span>
        </div>
        <!-- Submission progress -->
        <div class="mb-1">
          <div class="flex justify-between text-[10px] font-semibold mb-1">
            <span class="text-slate-500">Submissions</span>
            <span class="text-primary"><?php echo $a['submitted'];?>/<?php echo $a['total'];?> (<?php echo $pct;?>%)</span>
          </div>
          <div class="prog-track"><div class="prog-fill" style="--prog:<?php echo $pct;?>%"></div></div>
        </div>
        <?php if($a['submitted']>0): ?>
        <div>
          <div class="flex justify-between text-[10px] font-semibold mb-1 mt-2">
            <span class="text-slate-500">Graded</span>
            <span class="text-green-600"><?php echo $a['graded'];?>/<?php echo $a['submitted'];?> (<?php echo $gpct;?>%)</span>
          </div>
          <div class="prog-track"><div class="prog-fill" style="--prog:<?php echo $gpct;?>%;background:linear-gradient(135deg,#22c55e,#4ade80)"></div></div>
        </div>
        <?php endif;?>
      </div>
      <!-- Actions -->
      <div class="px-4 pb-4 flex gap-2">
        <button onclick="openSubmissions(<?php echo $a['id'];?>,'<?php echo htmlspecialchars($a['title']);?>')" class="flex-1 btn-primary py-2.5 text-xs rounded-xl flex items-center justify-center gap-1.5">
          <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">assignment_turned_in</span>
          View Submissions <?php if($pending>0):?><span class="bg-white/30 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full"><?php echo $pending;?></span><?php endif;?>
        </button>
        <?php if($a['status']==='draft'):?>
        <button onclick="showToast('Assignment published!','campaign','text-primary')" class="btn-ghost px-3 py-2.5 text-xs rounded-xl flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">publish</span>Publish
        </button>
        <?php else:?>
        <button onclick="openEdit(<?php echo $a['id'];?>)" class="btn-ghost px-3 py-2.5 text-xs rounded-xl flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">edit</span>Edit
        </button>
        <?php endif;?>
      </div>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- ══════ TAB: SUBMISSIONS ══════ -->
<div id="tab-submissions" class="hidden">
  <div class="flex items-center justify-between mb-3 fu2">
    <h3 class="font-bold text-sm flex items-center gap-2">
      <span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings:'FILL' 1;">inbox</span>
      Pending Review
    </h3>
    <div class="flex gap-2">
      <select class="text-xs bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-2 py-1.5 font-semibold">
        <option>All Assignments</option>
        <option>Physics Lab Report 4</option>
        <option>Calculus Problem Set 7</option>
      </select>
    </div>
  </div>
  <div class="space-y-2 fu3">
    <?php
    $submissions=[
      ['n'=>'Alex Rivera','id'=>'CS21001','a'=>'Physics Lab Report 4','dt'=>'Oct 27, 10:45 AM','avatar'=>'AR','color'=>'#4349cf','graded'=>false,'grade'=>null,'late'=>false],
      ['n'=>'Jamie Chen','id'=>'CS21042','a'=>'Physics Lab Report 4','dt'=>'Oct 27, 11:20 AM','avatar'=>'JC','color'=>'#7c3aed','graded'=>false,'grade'=>null,'late'=>false],
      ['n'=>'Sarah Jenkins','id'=>'CS21018','a'=>'Calculus Problem Set 7','dt'=>'Oct 26, 9:00 AM','avatar'=>'SJ','color'=>'#0891b2','graded'=>false,'grade'=>null,'late'=>true],
      ['n'=>'Marcus Smith','id'=>'CS21033','a'=>'Physics Lab Report 4','dt'=>'Oct 25, 3:30 PM','avatar'=>'MS','color'=>'#059669','graded'=>true,'grade'=>'A','late'=>false],
      ['n'=>'Priya Sharma','id'=>'CS21055','a'=>'Organic Reactions Essay','dt'=>'Oct 24, 7:15 PM','avatar'=>'PS','color'=>'#db2777','graded'=>true,'grade'=>'B+','late'=>false],
      ['n'=>'Rohan Patil','id'=>'CS21067','a'=>'Calculus Problem Set 7','dt'=>'Oct 23, 5:00 PM','avatar'=>'RP','color'=>'#d97706','graded'=>false,'grade'=>null,'late'=>false],
    ];
    foreach($submissions as $i=>$s): $delay=0.04+$i*.05;
    ?>
    <div class="sub-card" style="animation:fadeUp .35s <?php echo $delay;?>s ease both;opacity:0;animation-fill-mode:both">
      <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white font-bold text-sm shrink-0" style="background:<?php echo $s['color'];?>"><?php echo $s['avatar'];?></div>
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-1.5">
          <p class="font-bold text-sm"><?php echo $s['n'];?></p>
          <?php if($s['late']):?><span class="text-[9px] font-bold bg-red-100 text-red-500 px-1.5 py-0.5 rounded-full">Late</span><?php endif;?>
        </div>
        <p class="text-[10px] text-primary font-semibold"><?php echo $s['id'];?></p>
        <p class="text-[10px] text-slate-400 truncate"><?php echo $s['a'];?> • <?php echo $s['dt'];?></p>
      </div>
      <?php if($s['graded']):?>
      <div class="shrink-0">
        <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl font-bold text-sm <?php echo 'grade-'.substr($s['grade'],0,1);?>"><?php echo $s['grade'];?></span>
      </div>
      <?php else:?>
      <button onclick="openGrade('<?php echo htmlspecialchars($s['n']);?>','<?php echo $s['avatar'];?>','<?php echo $s['color'];?>')"
        class="shrink-0 btn-primary text-xs px-3 py-2 rounded-xl flex items-center gap-1">
        <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">grade</span>Grade
      </button>
      <?php endif;?>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- ══════ TAB: GRADED ══════ -->
<div id="tab-graded" class="hidden">
  <div class="fu2 mb-4">
    <!-- Distribution -->
    <div class="acard p-4">
      <h3 class="font-bold text-sm mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings:'FILL' 1;">bar_chart</span>Grade Distribution
      </h3>
      <div class="space-y-2">
        <?php foreach([['A+/A',32,'#22c55e'],['B+/B',28,'#3b82f6'],['C+/C',18,'#f97316'],['D/F',10,'#ef4444']] as $g):
          $w=$g[1];?>
        <div class="flex items-center gap-3">
          <span class="text-xs font-bold w-10 text-slate-600 dark:text-slate-300"><?php echo $g[0];?></span>
          <div class="flex-1 prog-track"><div class="prog-fill" style="--prog:<?php echo $w;?>%;background:<?php echo $g[2];?>"></div></div>
          <span class="text-xs font-bold text-slate-400 w-8 text-right"><?php echo $w;?>%</span>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
  <div class="space-y-2 fu3">
    <?php foreach($submissions as $s): if($s['graded']): ?>
    <div class="sub-card">
      <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-white font-bold text-sm shrink-0" style="background:<?php echo $s['color'];?>"><?php echo $s['avatar'];?></div>
      <div class="flex-1 min-w-0">
        <p class="font-bold text-sm"><?php echo $s['n'];?></p>
        <p class="text-[10px] text-slate-400"><?php echo $s['a'];?></p>
      </div>
      <div class="shrink-0 flex items-center gap-2">
        <span class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-sm <?php echo 'grade-'.substr($s['grade'],0,1);?>"><?php echo $s['grade'];?></span>
        <button onclick="showToast('Marks updated','edit','text-primary')" class="w-7 h-7 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center hover:bg-primary/10 hover:text-primary transition">
          <span class="material-symbols-outlined text-sm">edit</span>
        </button>
      </div>
    </div>
    <?php endif; endforeach;?>
  </div>
</div>

<!-- ══════ TAB: ANALYTICS ══════ -->
<div id="tab-analytics" class="hidden">
  <div class="space-y-3 fu2">
    <div class="acard p-4">
      <h3 class="font-bold text-sm mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">insights</span>
        Submission Rate by Assignment
      </h3>
      <div class="space-y-3">
        <?php foreach($assignments as $a):
          $pct=$a['total']>0?round($a['submitted']/$a['total']*100):0;
          $colors=['active'=>'#4349cf','closed'=>'#22c55e','overdue'=>'#ef4444','draft'=>'#f97316'];
          $c=$colors[$a['status']];?>
        <div>
          <div class="flex justify-between text-xs mb-1">
            <span class="font-semibold truncate max-w-[70%]"><?php echo $a['title'];?></span>
            <span class="font-bold" style="color:<?php echo $c;?>"><?php echo $pct;?>%</span>
          </div>
          <div class="prog-track"><div class="prog-fill" style="--prog:<?php echo $pct;?>%;background:<?php echo $c;?>"></div></div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="acard p-4 text-center">
        <p class="text-3xl font-bold text-primary">82%</p>
        <p class="text-xs text-slate-400 mt-1 font-semibold">Avg Submission Rate</p>
      </div>
      <div class="acard p-4 text-center">
        <p class="text-3xl font-bold text-green-500">B+</p>
        <p class="text-xs text-slate-400 mt-1 font-semibold">Class Average Grade</p>
      </div>
      <div class="acard p-4 text-center">
        <p class="text-3xl font-bold text-orange-500">28</p>
        <p class="text-xs text-slate-400 mt-1 font-semibold">Pending Reviews</p>
      </div>
      <div class="acard p-4 text-center">
        <p class="text-3xl font-bold text-red-500">4</p>
        <p class="text-xs text-slate-400 mt-1 font-semibold">Late Submissions</p>
      </div>
    </div>
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
<button onclick="openCreate()" class="fixed bottom-20 right-4 z-30 w-14 h-14 rounded-full flex items-center justify-center shadow-xl shadow-indigo-300/40 active:scale-90 transition hover:scale-110" style="background:var(--grad)">
  <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings:'FILL' 1;">add</span>
</button>

<!-- CREATE ASSIGNMENT MODAL -->
<div id="createModal" class="modal-bg" onclick="if(event.target===this)closeCreate()">
  <div class="modal-panel">
    <div class="flex justify-center pt-4 pb-2"><div class="w-10 h-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div></div>
    <div class="px-5 pb-2">
      <div class="flex items-center justify-between mb-5">
        <h3 class="font-bold text-lg flex items-center gap-2">
          <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">add_circle</span>New Assignment
        </h3>
        <button onclick="closeCreate()" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"><span class="material-symbols-outlined text-slate-400">close</span></button>
      </div>
      <div class="space-y-3">
        <div>
          <label class="text-xs font-bold text-slate-500 mb-1 block">Title *</label>
          <input type="text" placeholder="Assignment title..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"/>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Subject</label>
            <select class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-3 text-sm focus:ring-2 focus:ring-primary/20">
              <option>Select subject</option>
              <?php if($teacherSubjects){$teacherSubjects->data_seek(0);while($s=$teacherSubjects->fetch_assoc()):?>
              <option><?php echo htmlspecialchars($s['subject_name']);?></option>
              <?php endwhile;}?>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Total Marks</label>
            <input type="number" placeholder="e.g. 20" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-3 text-sm focus:ring-2 focus:ring-primary/20"/>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Due Date</label>
            <input type="date" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-3 text-sm focus:ring-2 focus:ring-primary/20"/>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Priority</label>
            <select class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-3 text-sm focus:ring-2 focus:ring-primary/20">
              <option>🔴 High</option>
              <option>🟠 Medium</option>
              <option>🟢 Low</option>
            </select>
          </div>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 mb-1 block">Instructions</label>
          <textarea rows="3" placeholder="Assignment instructions..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-sm resize-none focus:ring-2 focus:ring-primary/20 placeholder:text-slate-400"></textarea>
        </div>
        <div class="flex gap-2">
          <label class="flex items-center gap-2 cursor-pointer">
            <div class="relative">
              <input type="checkbox" checked class="sr-only peer"/>
              <div class="w-9 h-5 bg-slate-200 peer-checked:bg-primary rounded-full transition-colors"></div>
              <div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
            </div>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Allow Late Submission</span>
          </label>
        </div>
      </div>
      <div class="flex gap-3 mt-4 pb-8">
        <button onclick="closeCreate()" class="flex-1 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 font-bold text-sm active:scale-95 transition">Save Draft</button>
        <button onclick="showToast('Assignment published!','campaign','text-primary');closeCreate();" class="flex-1 py-3 rounded-2xl btn-primary font-bold text-sm">Publish</button>
      </div>
    </div>
  </div>
</div>

<!-- GRADING MODAL -->
<div id="gradeModal" onclick="if(event.target===this)closeGrade()">
  <div id="gradePanel">
    <div class="p-5">
      <div class="flex items-center gap-3 mb-5">
        <div id="gradeAvatar" class="w-12 h-12 rounded-2xl flex items-center justify-center text-white font-bold text-base shrink-0"></div>
        <div class="flex-1">
          <p id="gradeName" class="font-bold"></p>
          <p class="text-xs text-slate-400">Review & Grade Submission</p>
        </div>
        <button onclick="closeGrade()" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"><span class="material-symbols-outlined text-slate-400">close</span></button>
      </div>
      <!-- File preview area -->
      <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl h-32 flex items-center justify-center mb-4">
        <div class="text-center">
          <span class="material-symbols-outlined text-4xl text-slate-300" style="font-variation-settings:'FILL' 1;">picture_as_pdf</span>
          <p class="text-xs text-slate-400 mt-1 font-medium">Submission Preview</p>
        </div>
      </div>
      <!-- Grade input -->
      <div class="mb-4">
        <label class="text-xs font-bold text-slate-500 mb-2 block">Score (out of 20)</label>
        <div class="grid grid-cols-5 gap-2 mb-3">
          <?php foreach(['A+','A','B+','B','C'] as $g):?>
          <button onclick="setGradeQuick('<?php echo $g;?>')" class="py-2.5 rounded-xl text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-primary/10 hover:text-primary transition active:scale-95">
            <?php echo $g;?>
          </button>
          <?php endforeach;?>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-[10px] font-bold text-slate-500 mb-1 block">Marks</label>
            <input type="number" id="gradeMarks" placeholder="0-20" max="20" class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/20"/>
          </div>
          <div>
            <label class="text-[10px] font-bold text-slate-500 mb-1 block">Grade</label>
            <input type="text" id="gradeLabel" placeholder="A, B+, C..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-primary/20"/>
          </div>
        </div>
      </div>
      <div class="mb-4">
        <label class="text-xs font-bold text-slate-500 mb-1 block">Feedback</label>
        <textarea rows="3" placeholder="Write feedback for the student..." class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl px-4 py-3 text-sm resize-none focus:ring-2 focus:ring-primary/20 placeholder:text-slate-400"></textarea>
      </div>
      <div class="flex gap-3">
        <button onclick="closeGrade()" class="flex-1 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 font-bold text-sm active:scale-95 transition">Cancel</button>
        <button onclick="submitGrade()" class="flex-1 py-3 rounded-2xl btn-primary font-bold text-sm flex items-center justify-center gap-1.5">
          <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">grade</span>Submit Grade
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// ── Tab switch ──────────────────────────────────────
const tabs = ['list','submissions','graded','analytics'];
function switchTab(btn, id) {
  document.querySelectorAll('.atab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  tabs.forEach(t=>{ const el=document.getElementById('tab-'+t); if(el) el.classList.toggle('hidden',t!==id); });
}

// ── Filter assignments ────────────────────────────
function filterA(btn, status) {
  document.querySelectorAll('#tab-list button[onclick^="filterA"]').forEach(b=>{
    b.className='shrink-0 text-[11px] font-bold px-3 py-1.5 rounded-full bg-white dark:bg-slate-800 text-slate-500';
  });
  btn.className='shrink-0 text-[11px] font-bold px-3 py-1.5 rounded-full bg-primary text-white';
  document.querySelectorAll('#assignList .acard').forEach(c=>{
    c.style.display = (status==='all'||c.dataset.status===status) ? '' : 'none';
  });
}

// ── Create modal ──────────────────────────────────
function openCreate()  { document.getElementById('createModal').classList.add('open'); }
function closeCreate() { document.getElementById('createModal').classList.remove('open'); }

// ── Open submissions drawer ───────────────────────
function openSubmissions(id, title) {
  switchTab(document.querySelectorAll('.atab')[1],'submissions');
}

// ── Edit ──────────────────────────────────────────
function openEdit(id) { showToast('Edit mode coming soon','edit','text-primary'); }

// ── Grade modal ───────────────────────────────────
function openGrade(name, av, color) {
  document.getElementById('gradeName').textContent = name;
  const a = document.getElementById('gradeAvatar');
  a.textContent = av; a.style.background = color;
  document.getElementById('gradeModal').classList.add('open');
}
function closeGrade()  { document.getElementById('gradeModal').classList.remove('open'); }
function setGradeQuick(g) { document.getElementById('gradeLabel').value = g; }
function submitGrade() {
  closeGrade();
  showToast('Grade submitted!','grade','text-green-500');
  const badge = document.getElementById('subBadge');
  const cur = parseInt(badge.textContent);
  if(cur>1) badge.textContent = cur-1; else badge.style.display='none';
}

// ── Toast ──────────────────────────────────────────
function showToast(msg, icon, cls) {
  const t = document.createElement('div');
  t.className = 'toast';
  t.innerHTML = `<span class="material-symbols-outlined ${cls} text-lg" style="font-variation-settings:'FILL' 1;">${icon}</span>${msg}`;
  document.body.appendChild(t);
  setTimeout(()=>{ t.style.transition='opacity .3s'; t.style.opacity='0'; setTimeout(()=>t.remove(),300); },2500);
}
document.addEventListener('keydown',e=>{ if(e.key==='Escape'){closeCreate();closeGrade();} });
</script>
</body>
</html>