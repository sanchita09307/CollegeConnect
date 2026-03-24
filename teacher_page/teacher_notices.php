<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';
$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Notice Board – CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"}}}}</script>
<style>
*{font-family:'Inter',sans-serif;}
h1,h2,h3,.font-display{font-family:'Fraunces',serif;}
:root{--primary:#4349cf;--grad:linear-gradient(135deg,#4349cf,#7479f5);}
body{min-height:100dvh;background:#f0f1ff;}
.dark body{background:#0d0e1c;}

@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}
@keyframes slideLeft{from{transform:translateX(20px);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes pinDrop{0%{transform:scale(0) rotate(-20deg)}70%{transform:scale(1.2) rotate(5deg)}100%{transform:scale(1) rotate(0deg)}}
@keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}

.fu{animation:fadeUp .42s ease both}
.fu1{animation:fadeUp .42s .07s ease both}
.fu2{animation:fadeUp .42s .14s ease both}
.fu3{animation:fadeUp .42s .21s ease both}
.pin-drop{animation:pinDrop .4s cubic-bezier(.34,1.5,.64,1) both;}

/* Notice cards */
.ncard{
  background:white;border-radius:20px;
  border:1px solid #eef0ff;
  box-shadow:0 2px 12px rgba(67,73,207,.06);
  overflow:hidden;
  transition:all .22s;
  position:relative;
}
.dark .ncard{background:#1a1b2e;border-color:#2a2b45;}
.ncard:hover{transform:translateY(-3px);box-shadow:0 12px 30px rgba(67,73,207,.15);}

/* Priority stripes */
.stripe-urgent{border-left:4px solid #ef4444;}
.stripe-important{border-left:4px solid #f97316;}
.stripe-general{border-left:4px solid #4349cf;}
.stripe-academic{border-left:4px solid #22c55e;}
.stripe-event{border-left:4px solid #a855f7;}

/* Tag chips */
.notice-tag{padding:4px 12px;border-radius:99px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;gap:4px;}
.tag-urgent{background:#fee2e2;color:#dc2626;}
.tag-important{background:#ffedd5;color:#ea580c;}
.tag-general{background:#eff6ff;color:#2563eb;}
.tag-academic{background:#dcfce7;color:#16a34a;}
.tag-event{background:#faf5ff;color:#9333ea;}
.tag-it{background:#f0fdfa;color:#0891b2;}
.dark .tag-urgent{background:#450a0a;color:#fca5a5;}
.dark .tag-important{background:#431407;color:#fdba74;}
.dark .tag-general{background:#172554;color:#93c5fd;}
.dark .tag-academic{background:#052e16;color:#86efac;}
.dark .tag-event{background:#3b0764;color:#d8b4fe;}

/* Filter pills */
.filter-pill{padding:7px 16px;border-radius:99px;font-size:12px;font-weight:700;transition:all .2s;cursor:pointer;border:1.5px solid transparent;white-space:nowrap;}
.filter-pill.active{background:var(--primary);color:white;border-color:var(--primary);}
.filter-pill:not(.active){background:white;color:#64748b;border-color:#e2e8f0;}
.dark .filter-pill:not(.active){background:#1a1b2e;color:#94a3b8;border-color:#2a2b45;}

/* Pinned notice */
.pinned-badge{
  position:absolute;top:12px;right:12px;
  width:28px;height:28px;border-radius:99px;
  background:var(--grad);
  display:flex;align-items:center;justify-content:center;
  box-shadow:0 3px 8px rgba(67,73,207,.4);
}

/* Compose modal */
#composeModal{position:fixed;inset:0;z-index:9990;background:rgba(0,0,0,.55);backdrop-filter:blur(8px);display:none;align-items:flex-end;}
#composeModal.open{display:flex;}
#composePanel{background:white;border-radius:28px 28px 0 0;width:100%;max-height:94vh;overflow-y:auto;transform:translateY(100%);transition:transform .38s cubic-bezier(.22,1,.36,1);}
.dark #composePanel{background:#13142a;}
#composeModal.open #composePanel{transform:translateY(0);}

/* Detail modal */
#detailModal{position:fixed;inset:0;z-index:9991;background:rgba(0,0,0,.55);backdrop-filter:blur(8px);display:none;align-items:flex-end;}
#detailModal.open{display:flex;}
#detailPanel{background:white;border-radius:28px 28px 0 0;width:100%;max-height:92vh;overflow-y:auto;transform:translateY(100%);transition:transform .38s cubic-bezier(.22,1,.36,1);}
.dark #detailPanel{background:#13142a;}
#detailModal.open #detailPanel{transform:translateY(0);}

/* Ticker */
.ticker-wrap{overflow:hidden;white-space:nowrap;}
.ticker-inner{display:inline-block;animation:marquee 20s linear infinite;}

/* Input styles */
.inp{background:#f8f9ff;border:1.5px solid #e8eaf6;border-radius:16px;padding:12px 16px;font-size:13px;transition:border .2s,box-shadow .2s;width:100%;}
.dark .inp{background:#1e2040;border-color:#2a2b45;color:white;}
.inp:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 4px rgba(67,73,207,.1);}

/* Btn */
.btn-p{background:var(--grad);color:white;font-weight:700;border-radius:14px;box-shadow:0 4px 14px rgba(67,73,207,.3);transition:all .2s;padding:12px 20px;font-size:13px;}
.btn-p:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(67,73,207,.4);}
.btn-p:active{transform:scale(.95);}

/* Toast */
.toast{position:fixed;top:72px;left:50%;transform:translateX(-50%);z-index:9999;animation:fadeUp .3s ease both;background:white;box-shadow:0 8px 28px rgba(0,0,0,.14);border-radius:16px;padding:10px 18px;display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;white-space:nowrap;}
.dark .toast{background:#1e2040;color:white;}

::-webkit-scrollbar{width:4px}::-webkit-scrollbar-thumb{background:#c7d0ff;border-radius:4px}
</style>
</head>
<body class="bg-[#f0f1ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">
<?php
$pageTitle  = "Notice Board";
$activePage = "notices";
include __DIR__ . '/teacher_topbar.php';
?>

<!-- HERO -->
<div class="px-4 pt-4 fu">
  <div class="rounded-3xl p-5 text-white relative overflow-hidden shadow-xl shadow-indigo-300/30" style="background:linear-gradient(135deg,#1e1b4b 0%,#3730a3 40%,#4349cf 75%,#6d74f5 100%)">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:170px;font-variation-settings:'FILL' 1;">campaign</span>
    </div>
    <div class="relative z-10">
      <p class="text-white/60 text-[11px] font-bold uppercase tracking-widest">CollegeConnect</p>
      <h1 class="font-display text-2xl font-bold mt-0.5">Notice Board</h1>
      <p class="text-white/70 text-xs mt-1">Official announcements & communications</p>

      <!-- Ticker -->
      <div class="mt-4 bg-white/10 backdrop-blur-sm rounded-2xl px-3 py-2 flex items-center gap-2">
        <span class="material-symbols-outlined text-sm text-yellow-300 shrink-0" style="font-variation-settings:'FILL' 1;">notifications_active</span>
        <div class="ticker-wrap flex-1 text-xs text-white/80">
          <div class="ticker-inner font-semibold">
            🔴 Server maintenance tonight 10PM &nbsp;&nbsp;&nbsp; 📅 Faculty meeting Friday 3PM &nbsp;&nbsp;&nbsp; 📚 Exam schedule released &nbsp;&nbsp;&nbsp; 🎓 Graduation forms open &nbsp;&nbsp;&nbsp; 🔴 Server maintenance tonight 10PM &nbsp;&nbsp;&nbsp; 📅 Faculty meeting Friday 3PM
          </div>
        </div>
      </div>

      <div class="flex gap-3 mt-3">
        <button onclick="openCompose()" class="flex items-center gap-2 bg-white text-primary font-bold text-sm px-4 py-2.5 rounded-2xl shadow-lg active:scale-95 transition hover:opacity-95">
          <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">edit_square</span>Post Notice
        </button>
        <div class="flex gap-2">
          <div class="bg-white/15 rounded-2xl px-3 py-2 text-center">
            <p class="font-bold text-base">18</p>
            <p class="text-white/60 text-[9px] font-bold">Total</p>
          </div>
          <div class="bg-white/15 rounded-2xl px-3 py-2 text-center">
            <p class="font-bold text-base text-red-300">3</p>
            <p class="text-white/60 text-[9px] font-bold">Urgent</p>
          </div>
          <div class="bg-white/15 rounded-2xl px-3 py-2 text-center">
            <p class="font-bold text-base text-green-300">5</p>
            <p class="text-white/60 text-[9px] font-bold">Mine</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<main class="px-4 py-4 pb-28 space-y-4">

<!-- FILTERS -->
<div class="fu1 flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none;scrollbar-width:none">
  <button class="filter-pill active" onclick="filterNotices(this,'all')">All</button>
  <button class="filter-pill" onclick="filterNotices(this,'urgent')">🔴 Urgent</button>
  <button class="filter-pill" onclick="filterNotices(this,'important')">🟠 Important</button>
  <button class="filter-pill" onclick="filterNotices(this,'academic')">📚 Academic</button>
  <button class="filter-pill" onclick="filterNotices(this,'event')">🎉 Events</button>
  <button class="filter-pill" onclick="filterNotices(this,'general')">📋 General</button>
  <button class="filter-pill" onclick="filterNotices(this,'mine')">👤 Mine</button>
</div>

<!-- PINNED NOTICE -->
<div class="fu2">
  <div class="flex items-center gap-2 mb-2">
    <span class="material-symbols-outlined text-amber-500 text-lg pin-drop" style="font-variation-settings:'FILL' 1;">push_pin</span>
    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pinned</span>
  </div>
  <div class="ncard stripe-urgent cursor-pointer" onclick="openDetail('Emergency Server Maintenance','IT Services','Tonight from 10:00 PM to 2:00 AM, the entire college portal including the grading system, LMS, and student database will be offline for emergency maintenance. Please ensure all marks and attendance are saved before 9:30 PM. Contact IT helpdesk for urgent issues.','urgent','2h ago','high','IT Services')">
    <div class="pinned-badge pin-drop"><span class="material-symbols-outlined text-white text-sm" style="font-variation-settings:'FILL' 1;">push_pin</span></div>
    <div class="p-4">
      <div class="flex items-center gap-2 mb-2">
        <span class="notice-tag tag-urgent">🔴 Urgent</span>
        <span class="notice-tag tag-it">IT Services</span>
      </div>
      <h3 class="font-display font-bold text-base leading-tight mb-1">Emergency Server Maintenance Tonight</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2">The grading portal will be offline for emergency maintenance tonight starting at 10 PM. Please save all marks beforehand.</p>
      <div class="flex items-center justify-between mt-3">
        <span class="text-[11px] text-slate-400 flex items-center gap-1">
          <span class="material-symbols-outlined text-sm">schedule</span>2 hours ago
        </span>
        <div class="flex items-center gap-2">
          <span class="text-[11px] text-slate-400 flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">visibility</span>142 views
          </span>
          <button onclick="event.stopPropagation();showToast('Notice pinned!','push_pin','text-amber-500')" class="p-1.5 rounded-lg hover:bg-amber-50 text-amber-500 transition">
            <span class="material-symbols-outlined text-sm">push_pin</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- NOTICES LIST -->
<div class="fu3">
  <div class="flex items-center justify-between mb-3">
    <h3 class="font-display font-bold text-sm flex items-center gap-2">
      <span class="w-6 h-6 rounded-lg bg-primary/10 flex items-center justify-center">
        <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings:'FILL' 1;">article</span>
      </span>
      All Notices
    </h3>
    <span id="noticeCount" class="text-[11px] bg-primary/10 text-primary font-bold px-2.5 py-1 rounded-full">18 notices</span>
  </div>

  <div id="noticeList" class="space-y-3">
    <?php
    $notices=[
      ['title'=>'Faculty Research Grant Applications 2024','dept'=>'Research Office','tag'=>'academic','priority'=>'important','time'=>'Oct 24, 2:30 PM','views'=>98,'body'=>'Applications for the 2024 Faculty Research Grant are now open. Faculty members are invited to submit their research proposals. The grant covers project expenses up to ₹5 lakhs. Last date for submission is November 15, 2024. Submit proposals through the research portal with all required supporting documents.','mine'=>false],
      ['title'=>'Department Head Meeting – Agenda Released','dept'=>'Administration','tag'=>'important','priority'=>'important','time'=>'Oct 23, 10:00 AM','views'=>67,'body'=>'The agenda for the upcoming Department Head meeting scheduled for Friday, October 27th at 3:00 PM has been uploaded to the shared drive. Topics include semester evaluation, faculty promotions, and infrastructure updates. All heads are requested to review the agenda and come prepared.','mine'=>false],
      ['title'=>'End-Term Examination Schedule','dept'=>'Examination Cell','tag'=>'academic','priority'=>'general','time'=>'Oct 22, 9:00 AM','views'=>234,'body'=>'The end-term examination schedule for Semester 5 and Semester 7 has been finalized. Examinations will commence from November 20, 2024. Detailed date sheets are available on the examination portal. Students must carry their admit cards on all examination days.','mine'=>false],
      ['title'=>'Annual Sports Week – Faculty Participation','dept'=>'Sports Committee','tag'=>'event','priority'=>'general','time'=>'Oct 21, 4:00 PM','views'=>89,'body'=>'The Annual Sports Week will be held from November 5–10, 2024. Faculty members are encouraged to participate in badminton, table tennis, and chess events. Registrations are open until October 30th. Contact the sports committee for more details.','mine'=>false],
      ['title'=>'Library Digital Resources Workshop','dept'=>'Library Services','tag'=>'academic','priority'=>'general','time'=>'Oct 20, 11:00 AM','views'=>55,'body'=>'Learn about new digital journal subscriptions including IEEE Xplore, Springer, and Elsevier. The workshop will be held in Room 302 on October 28 from 2 PM to 4 PM. Faculty can learn how to integrate these resources into course syllabi.','mine'=>false],
      ['title'=>'Physics Lab Exam – Room Change','dept'=>'Your Name','tag'=>'general','priority'=>'general','time'=>'Oct 19, 3:00 PM','views'=>41,'body'=>'The Physics Lab practical examination scheduled for October 26 has been moved from Lab 2 to Lab 4 due to maintenance work. All students of Semester 3 are informed accordingly. Please note the new venue.','mine'=>true],
      ['title'=>'Leave Policy Update 2024–25','dept'=>'HR Department','tag'=>'important','priority'=>'important','time'=>'Oct 18, 9:00 AM','views'=>178,'body'=>'The updated leave policy for academic year 2024–25 has been issued. Key changes include: casual leave increased to 12 days, medical leave now requires supporting documentation for leaves beyond 3 days, and earned leave encashment process has been updated. Please read the full policy document.','mine'=>false],
      ['title'=>'Congratulations – Best Paper Award','dept'=>'Research Office','tag'=>'event','priority'=>'general','time'=>'Oct 16, 5:00 PM','views'=>203,'body'=>'We are pleased to congratulate Dr. Priya Anand from the Computer Science Department for winning the Best Paper Award at IEEE ICCS 2024. Her paper on "Federated Learning in Healthcare" was recognized among over 400 submissions. We are proud of this achievement!','mine'=>false],
    ];
    foreach($notices as $i=>$n):
      $tagClass = 'tag-'.$n['tag'];
      $stripeClass = 'stripe-'.$n['priority'];
      $emoji=['urgent'=>'🔴','important'=>'🟠','academic'=>'📚','event'=>'🎉','general'=>'📋','it'=>'💻'][$n['tag']]??'📋';
      $delay=0.04+$i*.05;
    ?>
    <div class="ncard <?php echo $stripeClass;?> cursor-pointer"
         data-tag="<?php echo $n['tag'];?>"
         data-mine="<?php echo $n['mine']?'mine':'';?>"
         style="animation:fadeUp .38s <?php echo $delay;?>s ease both;opacity:0;animation-fill-mode:both"
         onclick="openDetail('<?php echo htmlspecialchars($n['title']);?>','<?php echo $n['dept'];?>',<?php echo json_encode($n['body']);?>,'<?php echo $n['tag'];?>','<?php echo $n['time'];?>','<?php echo $n['priority'];?>','<?php echo $n['dept'];?>')">
      <div class="p-4">
        <div class="flex items-start gap-3">
          <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
              <span class="notice-tag <?php echo $tagClass;?>"><?php echo $emoji.' '.ucfirst($n['tag']);?></span>
              <?php if($n['mine']):?><span class="notice-tag" style="background:#ede9fe;color:#7c3aed">👤 Mine</span><?php endif;?>
            </div>
            <h3 class="font-display font-semibold text-sm leading-tight line-clamp-2"><?php echo htmlspecialchars($n['title']);?></h3>
            <p class="text-xs text-slate-400 mt-0.5 line-clamp-1"><?php echo $n['dept'];?></p>
          </div>
          <?php if($n['mine']):?>
          <button onclick="event.stopPropagation();showOptions(this)" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0">
            <span class="material-symbols-outlined text-slate-400 text-lg">more_vert</span>
          </button>
          <?php endif;?>
        </div>
        <div class="flex items-center justify-between mt-3">
          <span class="text-[11px] text-slate-400 flex items-center gap-1">
            <span class="material-symbols-outlined text-xs">schedule</span><?php echo $n['time'];?>
          </span>
          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-400 flex items-center gap-1">
              <span class="material-symbols-outlined text-xs">visibility</span><?php echo $n['views'];?>
            </span>
            <span class="text-[11px] text-primary font-semibold flex items-center gap-0.5">
              Read more <span class="material-symbols-outlined text-xs">chevron_right</span>
            </span>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach;?>
  </div>

  <div id="emptyNotices" class="hidden text-center py-12">
    <span class="material-symbols-outlined text-5xl text-slate-300">campaign</span>
    <p class="text-sm text-slate-400 mt-2">No notices in this category</p>
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
<button onclick="openCompose()" class="fixed bottom-20 right-4 z-30 w-14 h-14 rounded-full flex items-center justify-center shadow-xl shadow-indigo-300/40 active:scale-90 transition hover:scale-110" style="background:var(--grad)">
  <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings:'FILL' 1;">edit_square</span>
</button>

<!-- COMPOSE MODAL -->
<div id="composeModal" onclick="if(event.target===this)closeCompose()">
  <div id="composePanel">
    <div class="flex justify-center pt-4 pb-2"><div class="w-10 h-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div></div>
    <div class="px-5 pb-2">
      <div class="flex items-center justify-between mb-5">
        <h3 class="font-display font-bold text-xl flex items-center gap-2">
          <span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings:'FILL' 1;">edit_square</span>Compose Notice
        </h3>
        <button onclick="closeCompose()" class="p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"><span class="material-symbols-outlined text-slate-400">close</span></button>
      </div>
      <div class="space-y-3">
        <div>
          <label class="text-xs font-bold text-slate-500 mb-1 block">Notice Title *</label>
          <input type="text" placeholder="Enter a clear, descriptive title..." class="inp"/>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Audience</label>
            <select class="inp">
              <option>All Students & Faculty</option>
              <option>Students Only</option>
              <option>Faculty Only</option>
              <option>My Department</option>
              <option>My Classes</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Priority</label>
            <select class="inp">
              <option value="general">📋 General</option>
              <option value="important">🟠 Important</option>
              <option value="urgent">🔴 Urgent</option>
            </select>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Category</label>
            <select class="inp">
              <option>Academic</option>
              <option>Administrative</option>
              <option>Event</option>
              <option>Examination</option>
              <option>Holiday</option>
            </select>
          </div>
          <div>
            <label class="text-xs font-bold text-slate-500 mb-1 block">Expiry Date</label>
            <input type="date" class="inp"/>
          </div>
        </div>
        <div>
          <label class="text-xs font-bold text-slate-500 mb-1 block">Notice Body *</label>
          <textarea rows="5" placeholder="Write the full notice content here. Be clear and include all relevant details..." class="inp resize-none"></textarea>
        </div>
        <!-- Attach file -->
        <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-700 transition">
          <span class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-lg">attach_file</span>
          </span>
          <div>
            <p class="text-sm font-semibold">Attach Document</p>
            <p class="text-[10px] text-slate-400">PDF, DOC up to 10MB</p>
          </div>
          <input type="file" class="hidden"/>
        </label>
        <!-- Options -->
        <div class="flex flex-wrap gap-3">
          <label class="flex items-center gap-2 cursor-pointer">
            <div class="relative"><input type="checkbox" checked class="sr-only peer"/><div class="w-9 h-5 bg-slate-200 peer-checked:bg-primary rounded-full transition-colors"></div><div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div></div>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Send Notification</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <div class="relative"><input type="checkbox" class="sr-only peer"/><div class="w-9 h-5 bg-slate-200 peer-checked:bg-primary rounded-full transition-colors"></div><div class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div></div>
            <span class="text-xs font-semibold text-slate-600 dark:text-slate-300">Pin to Top</span>
          </label>
        </div>
      </div>
      <div class="flex gap-3 mt-4 pb-8">
        <button onclick="closeCompose()" class="flex-1 py-3 rounded-2xl bg-slate-100 dark:bg-slate-800 font-bold text-sm active:scale-95 transition">Save Draft</button>
        <button onclick="publishNotice()" class="flex-1 py-3 btn-p rounded-2xl font-bold text-sm flex items-center justify-center gap-1.5">
          <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">publish</span>Publish
        </button>
      </div>
    </div>
  </div>
</div>

<!-- DETAIL MODAL -->
<div id="detailModal" onclick="if(event.target===this)closeDetail()">
  <div id="detailPanel">
    <div class="flex justify-center pt-4 pb-2"><div class="w-10 h-1 bg-slate-200 dark:bg-slate-700 rounded-full"></div></div>
    <div class="px-5 pb-8">
      <div class="flex items-start justify-between gap-3 mb-4">
        <div class="flex-1">
          <div id="detailTag" class="mb-2"></div>
          <h2 id="detailTitle" class="font-display font-bold text-lg leading-tight"></h2>
          <p id="detailMeta" class="text-xs text-slate-400 mt-1"></p>
        </div>
        <button onclick="closeDetail()" class="shrink-0 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition"><span class="material-symbols-outlined text-slate-400">close</span></button>
      </div>
      <div id="detailBody" class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed mb-5 bg-slate-50 dark:bg-slate-800 rounded-2xl p-4"></div>
      <div class="flex items-center gap-3">
        <button onclick="showToast('Notice shared!','share','text-primary')" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-2xl bg-primary/8 text-primary font-bold text-sm active:scale-95 transition">
          <span class="material-symbols-outlined text-lg">share</span>Share
        </button>
        <button onclick="showToast('Notice downloaded!','download','text-green-500')" class="flex-1 flex items-center justify-center gap-2 py-3 rounded-2xl bg-green-50 dark:bg-green-900/20 text-green-600 font-bold text-sm active:scale-95 transition">
          <span class="material-symbols-outlined text-lg">download</span>Save
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// ── Filter ─────────────────────────────────────────────
function filterNotices(btn, tag) {
  document.querySelectorAll('.filter-pill').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  const cards = document.querySelectorAll('#noticeList .ncard');
  let count = 0;
  cards.forEach(c=>{
    const show = tag==='all' || c.dataset.tag===tag || (tag==='mine' && c.dataset.mine==='mine');
    c.style.display = show ? '' : 'none';
    if(show) count++;
  });
  document.getElementById('noticeCount').textContent = count+' notices';
  document.getElementById('emptyNotices').classList.toggle('hidden', count>0);
}

// ── Compose modal ──────────────────────────────────────
function openCompose()  { document.getElementById('composeModal').classList.add('open'); }
function closeCompose() { document.getElementById('composeModal').classList.remove('open'); }
function publishNotice() {
  closeCompose();
  showToast('Notice published!','campaign','text-primary');
}

// ── Detail modal ───────────────────────────────────────
const tagMap={urgent:'tag-urgent',important:'tag-important',academic:'tag-academic',event:'tag-event',general:'tag-general',it:'tag-it'};
const emojiMap={urgent:'🔴 Urgent',important:'🟠 Important',academic:'📚 Academic',event:'🎉 Event',general:'📋 General'};
function openDetail(title, dept, body, tag, time, priority, from) {
  document.getElementById('detailTitle').textContent = title;
  document.getElementById('detailMeta').textContent = `From: ${from} • ${time}`;
  document.getElementById('detailBody').textContent = body;
  const tagEl = document.getElementById('detailTag');
  tagEl.innerHTML = `<span class="notice-tag ${tagMap[tag]||'tag-general'}">${emojiMap[tag]||tag}</span>`;
  document.getElementById('detailModal').classList.add('open');
}
function closeDetail() { document.getElementById('detailModal').classList.remove('open'); }

// ── Options ────────────────────────────────────────────
function showOptions(btn) {
  showToast('Edit/Delete options','more_vert','text-slate-500');
}

// ── Toast ──────────────────────────────────────────────
function showToast(msg, icon, cls) {
  const t = document.createElement('div');
  t.className = 'toast';
  t.innerHTML = `<span class="material-symbols-outlined ${cls} text-lg" style="font-variation-settings:'FILL' 1;">${icon}</span>${msg}`;
  document.body.appendChild(t);
  setTimeout(()=>{ t.style.transition='opacity .3s'; t.style.opacity='0'; setTimeout(()=>t.remove(),300); },2500);
}

document.addEventListener('keydown',e=>{ if(e.key==='Escape'){closeCompose();closeDetail();} });
</script>
</body>
</html>