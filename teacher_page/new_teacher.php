<?php
require_once __DIR__ . '/new_teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$teacherId = (int)($teacher['id'] ?? 0);

$profileFields = ['name','email','department','designation','qualification','profile_photo','phone'];
$filled = 0;
foreach ($profileFields as $f) { if (!empty($teacher[$f])) $filled++; }
$completionPct = round(($filled / count($profileFields)) * 100);
$isComplete = $completionPct >= 100;

$chipMeta = [
    'name'          => ['icon'=>'badge',       'label'=>'Full Name'],
    'email'         => ['icon'=>'email',       'label'=>'Email'],
    'department'    => ['icon'=>'apartment',   'label'=>'Department'],
    'designation'   => ['icon'=>'stars',       'label'=>'Designation'],
    'qualification' => ['icon'=>'school',      'label'=>'Qualification'],
    'profile_photo' => ['icon'=>'add_a_photo', 'label'=>'Profile Photo'],
    'phone'         => ['icon'=>'phone',       'label'=>'Phone Number'],
];
$missingFields = [];
foreach ($profileFields as $f) { if (empty($teacher[$f])) $missingFields[] = $chipMeta[$f]; }

$subjectsCount = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM subjects WHERE teacher_id=$teacherId");
if ($res && $res->num_rows) $subjectsCount = (int)($res->fetch_assoc()['total'] ?? 0);

$dept = $conn->real_escape_string($teacher['department'] ?? '');
$studentCount = 0;
$res2 = $conn->query("SELECT COUNT(*) AS t FROM students WHERE department='$dept' AND status='approved'");
if ($res2 && $res2->num_rows) $studentCount = (int)($res2->fetch_assoc()['t'] ?? 0);

$photo = !empty($teacher['profile_photo'])
    ? '../uploads/profile_photos/'.htmlspecialchars($teacher['profile_photo'])
    : 'https://ui-avatars.com/api/?name='.urlencode($teacher['name']??'T').'&background=4349cf&color=fff&bold=true&size=128';

$siteName = htmlspecialchars($settings['site_name'] ?? 'CollegeConnect');
$circumference = 2 * M_PI * 34;
$dashOffset = round($circumference * (1 - $completionPct / 100), 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Faculty Welcome â€” <?= $siteName ?></title>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased}
body{font-family:'Lexend',sans-serif;background:#eef0ff;color:#0d0e1c;min-height:100dvh;line-height:1.6}
a{text-decoration:none;color:inherit}

/* ORIGINAL COLOR TOKENS  */
:root{
  --primary:#4349cf;
  --primary-d:#3338b0;
  --primary-lt:#eef0ff;
  --primary-mid:#6b6ff5;
  --primary-pale:#f3f4ff;
  --primary-border:#c7d2fe;
  --ink:#0d0e1c;--ink-2:#374151;--ink-3:#6b7280;
  --white:#fff;
  --r:12px;--r-lg:18px;--r-xl:24px;
  --shadow-sm:0 2px 12px rgba(67,73,207,.07);
  --shadow-md:0 8px 28px rgba(67,73,207,.13);
  --shadow-lg:0 16px 48px rgba(67,73,207,.2);
}

/*  KEYFRAMES  */
@keyframes fadeUp{from{opacity:0;transform:translateY(26px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideDown{from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:translateY(0)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
@keyframes shimmer{0%{background-position:-300% center}100%{background-position:300% center}}
@keyframes ringPulse{0%,100%{box-shadow:0 0 0 0 rgba(67,73,207,.35)}70%{box-shadow:0 0 0 10px rgba(67,73,207,0)}}
@keyframes blobA{0%,100%{border-radius:60% 40% 30% 70%/60% 30% 70% 40%}50%{border-radius:30% 60% 70% 40%/50% 60% 30% 60%}}
@keyframes badgeBob{0%,100%{transform:scale(1)}50%{transform:scale(1.07)}}
@keyframes checkPop{0%{transform:scale(0) rotate(-12deg);opacity:0}70%{transform:scale(1.18)}100%{transform:scale(1);opacity:1}}
@keyframes ripple{to{transform:scale(1);opacity:0}}
@keyframes heroOrb{0%,100%{transform:translate(0,0) scale(1)}40%{transform:translate(20px,-18px) scale(1.06)}70%{transform:translate(-14px,12px) scale(.96)}}

/* â”€â”€ REVEAL â”€â”€ */
.reveal{opacity:0;transform:translateY(20px);transition:opacity .6s cubic-bezier(.22,1,.36,1),transform .6s cubic-bezier(.22,1,.36,1)}
.reveal.visible{opacity:1;transform:none}
.rv1{transition-delay:.06s}.rv2{transition-delay:.15s}.rv3{transition-delay:.25s}.rv4{transition-delay:.36s}

/* â”€â”€ MATERIAL SYMBOLS â”€â”€ */
.mi{font-family:'Material Symbols Rounded';font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;line-height:1;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;user-select:none}

/* â”€â”€ GLASS â”€â”€ */
.glass{background:rgba(255,255,255,.88);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid rgba(255,255,255,.8)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• TOPNAV â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.topnav{position:sticky;top:0;z-index:50;animation:slideDown .45s ease both}
.topnav-inner{
  max-width:1280px;margin:0 auto;
  padding:0 clamp(14px,4vw,32px);
  height:60px;
  display:flex;align-items:center;justify-content:space-between;gap:12px;
}
.nav-logo{display:flex;align-items:center;gap:9px;flex-shrink:0}
.nav-logo-icon{
  width:36px;height:36px;
  background:linear-gradient(135deg,var(--primary),var(--primary-mid));
  border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:19px;
  box-shadow:0 3px 12px rgba(67,73,207,.35);
}
.nav-brand{font-size:16px;font-weight:700;color:var(--ink);letter-spacing:-.015em}
.nav-brand b{color:var(--primary)}
.nav-right{display:flex;align-items:center;gap:6px}
.nav-icon-btn{
  width:36px;height:36px;border-radius:10px;border:none;background:transparent;
  cursor:pointer;display:flex;align-items:center;justify-content:center;
  color:var(--ink-3);font-size:19px;
  transition:background .15s,color .15s;
  text-decoration:none;
}
.nav-icon-btn:hover{background:var(--primary-lt);color:var(--primary)}
.nav-avatar{
  width:36px;height:36px;border-radius:50%;
  border:2px solid var(--primary-border);
  overflow:hidden;cursor:pointer;flex-shrink:0;
  animation:ringPulse 2.5s ease-in-out infinite;
}
.nav-avatar img{width:100%;height:100%;object-fit:cover;display:block}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• PAGE â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.page{max-width:1280px;margin:0 auto;padding:clamp(18px,4vw,34px) clamp(14px,4vw,32px) 48px;display:flex;flex-direction:column;gap:22px}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• HERO â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.hero{
  background:linear-gradient(135deg,var(--primary) 0%,var(--primary-mid) 55%,#a78bfa 100%);
  border-radius:var(--r-xl);
  padding:clamp(22px,5vw,44px);
  position:relative;overflow:hidden;color:#fff;
  box-shadow:var(--shadow-lg);
  animation:fadeUp .6s .04s ease both;
}
.hero-dots{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.1) 1px,transparent 1px);background-size:24px 24px;pointer-events:none}
.hero-orb{position:absolute;border-radius:50%;filter:blur(50px);pointer-events:none}
.hero-orb-1{width:320px;height:320px;background:rgba(255,255,255,.1);top:-80px;right:-60px;animation:heroOrb 14s ease-in-out infinite}
.hero-orb-2{width:200px;height:200px;background:rgba(167,139,250,.25);bottom:-50px;left:5%;animation:blobA 18s ease-in-out infinite reverse}
.hero-float{
  position:absolute;top:18px;right:22px;
  width:44px;height:44px;
  background:rgba(255,255,255,.15);border:1.5px solid rgba(255,255,255,.25);
  border-radius:13px;backdrop-filter:blur(6px);
  display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;
  animation:float 4s ease-in-out infinite;
}
@media(max-width:480px){.hero-float{display:none}}
.hero-tag{
  display:inline-flex;align-items:center;gap:7px;
  background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.25);
  backdrop-filter:blur(6px);border-radius:99px;
  padding:4px 13px;font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  color:rgba(255,255,255,.9);margin-bottom:13px;width:fit-content;
}
.hero-tag-dot{width:7px;height:7px;background:#4ade80;border-radius:50%;flex-shrink:0}
.hero-name{font-size:clamp(20px,4.5vw,34px);font-weight:800;letter-spacing:-.025em;line-height:1.15;margin-bottom:5px}
.hero-role{font-size:clamp(12px,1.8vw,14px);color:rgba(255,255,255,.65);margin-bottom:22px}
.hero-stats{display:flex;flex-wrap:wrap;gap:9px}
.hero-stat{
  display:flex;align-items:center;gap:7px;
  background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.18);
  backdrop-filter:blur(6px);border-radius:10px;
  padding:7px 13px;font-size:12px;font-weight:700;
  flex-shrink:0;
}
.hero-stat .mi{font-size:15px;opacity:.8}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• MAIN GRID â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.main-grid{display:grid;grid-template-columns:1fr;gap:20px}
@media(min-width:900px){.main-grid{grid-template-columns:320px 1fr}}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• CARD â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.card{
  background:var(--white);border:1px solid #e8eaf6;
  border-radius:var(--r-lg);
  box-shadow:var(--shadow-sm);
  overflow:hidden;
  transition:transform .2s,box-shadow .2s;
}
.card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.card-shimmer-bar{
  height:4px;width:100%;
  background:linear-gradient(90deg,var(--primary) 0%,var(--primary-mid) 35%,#c7d2fe 50%,var(--primary-mid) 65%,var(--primary) 100%);
  background-size:300% 100%;
  animation:shimmer 2.5s linear infinite;
}
.card-header{
  padding:16px 20px 14px;border-bottom:1px solid var(--primary-lt);
  display:flex;align-items:center;gap:11px;
}
.card-header-icon{
  width:36px;height:36px;flex-shrink:0;
  background:var(--primary-lt);border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:19px;color:var(--primary);
}
.card-header-title{font-size:14px;font-weight:800;color:var(--ink);letter-spacing:-.01em}
.card-header-sub{font-size:11px;color:var(--ink-3);margin-top:1px}
.card-body{padding:20px}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• PROFILE CARD â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.profile-center{display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center}
.ring-wrap{position:relative;width:96px;height:96px}
.ring-wrap svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg)}
.ring-track{fill:none;stroke:#e0e4ff;stroke-width:7}
.ring-fill{fill:none;stroke-width:7;stroke-linecap:round;stroke:url(#rg);transition:stroke-dashoffset 1.4s cubic-bezier(.4,0,.2,1)}
.ring-photo{position:absolute;inset:9px;border-radius:50%;overflow:hidden;border:2px solid #fff;box-shadow:0 2px 10px rgba(67,73,207,.18)}
.ring-photo img{width:100%;height:100%;object-fit:cover;display:block}
.ring-pct{
  position:absolute;bottom:-8px;left:50%;transform:translateX(-50%);
  background:var(--primary);color:#fff;font-size:10px;font-weight:800;
  padding:2px 8px;border-radius:99px;white-space:nowrap;
  border:2px solid #fff;box-shadow:0 2px 8px rgba(67,73,207,.3);
}
.status-badge{display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:700;padding:4px 11px;border-radius:99px}
.badge-ok{background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a}
.badge-warn{background:#fffbeb;border:1px solid #fde68a;color:#d97706;animation:badgeBob 2s ease-in-out infinite}
.profile-name{font-size:16px;font-weight:800;color:var(--ink);letter-spacing:-.015em}
.profile-sub{font-size:11.5px;color:var(--ink-3)}

/* â”€â”€ PROGRESS BAR â”€â”€ */
.pbar-wrap{width:100%;margin:16px 0 12px}
.pbar-top{display:flex;justify-content:space-between;font-size:11px;font-weight:700;color:var(--ink-3);margin-bottom:6px}
.pbar-top em{color:var(--primary);font-style:normal}
.pbar-track{height:8px;background:var(--primary-lt);border-radius:99px;overflow:hidden}
.pbar-fill{
  height:100%;border-radius:99px;
  background:linear-gradient(90deg,var(--primary) 0%,var(--primary-mid) 35%,#c7d2fe 50%,var(--primary-mid) 65%,var(--primary) 100%);
  background-size:300% 100%;
  animation:shimmer 2.5s linear infinite;
  transition:width 1.2s cubic-bezier(.4,0,.2,1);
}

/* â”€â”€ MISSING CHIPS â”€â”€ */
.missing-label{font-size:10px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--ink-3);margin-bottom:7px}
.chips-wrap{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:14px}
.chip{
  display:inline-flex;align-items:center;gap:4px;
  background:var(--primary-lt);color:var(--primary);
  border:1px solid var(--primary-border);
  font-size:10px;font-weight:700;
  padding:3px 9px;border-radius:99px;
  transition:background .15s,transform .12s;
}
.chip:hover{background:#e0e4ff;transform:scale(1.04)}
.chip .mi{font-size:12px}

/* â”€â”€ BUTTONS â”€â”€ */
.btn-row{display:flex;flex-direction:column;gap:9px}
@media(min-width:440px){.btn-row{flex-direction:row}}
.btn-p{
  display:flex;align-items:center;justify-content:center;gap:6px;
  flex:1;height:46px;
  background:linear-gradient(135deg,var(--primary),var(--primary-mid));
  color:#fff;border:none;border-radius:var(--r);
  font-family:'Lexend',sans-serif;font-size:13.5px;font-weight:700;
  cursor:pointer;position:relative;overflow:hidden;
  box-shadow:0 4px 16px rgba(67,73,207,.32);
  transition:transform .15s,box-shadow .15s;text-decoration:none;
}
.btn-p::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent);
  background-size:300% auto;animation:shimmer 2.5s linear infinite;
}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(67,73,207,.42)}
.btn-p:active{transform:scale(.97)}
.btn-p::after{content:'';position:absolute;inset:0;border-radius:inherit;background:radial-gradient(circle,rgba(255,255,255,.25) 0%,transparent 65%);opacity:0;transition:opacity .3s}
.btn-p:hover::after{opacity:1}
.btn-o{
  display:flex;align-items:center;justify-content:center;gap:6px;
  flex:1;height:46px;
  background:transparent;color:var(--primary);
  border:1.5px solid var(--primary);
  border-radius:var(--r);
  font-family:'Lexend',sans-serif;font-size:13.5px;font-weight:700;
  cursor:pointer;transition:background .15s,color .15s,transform .15s;text-decoration:none;
}
.btn-o:hover{background:var(--primary);color:#fff;transform:translateY(-1px)}
.btn-o:active{transform:scale(.97)}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• FEATURES â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
.feat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
.feat-card{
  background:var(--white);border:1px solid #e8eaf6;
  border-radius:var(--r-lg);padding:18px 16px;
  display:flex;flex-direction:column;gap:11px;
  box-shadow:var(--shadow-sm);
  position:relative;overflow:hidden;cursor:default;
  transition:transform .2s,box-shadow .2s,border-color .2s;
}
.feat-card::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--primary),var(--primary-mid));transform:scaleX(0);transform-origin:left;transition:transform .25s ease}
.feat-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:var(--primary-border)}
.feat-card:hover::after{transform:scaleX(1)}
.feat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;transition:transform .2s}
.feat-card:hover .feat-icon{transform:scale(1.08)}
.feat-title{font-size:13.5px;font-weight:800;color:var(--ink)}
.feat-desc{font-size:11.5px;color:var(--ink-3);line-height:1.6}

/* â•â•â•â•â•â•â•â•â•â•â•â•â•â• STEPS â•â•â•â•â•â•â•â•â??â??â??â??â??â?? */
.steps-list{display:flex;flex-direction:column}
.step-item{
  display:flex;align-items:flex-start;gap:12px;
  padding:14px 0;border-bottom:1px solid var(--primary-lt);
  cursor:default;transition:background .15s;
  position:relative;
}
.step-item:last-child{border-bottom:none}
.step-item:hover{background:var(--primary-pale);margin:0 -20px;padding-left:20px;padding-right:20px;border-radius:var(--r)}
.step-num-box{
  width:30px;height:30px;flex-shrink:0;border-radius:9px;
  background:var(--primary-lt);
  display:flex;align-items:center;justify-content:center;
  font-size:11.5px;font-weight:800;color:var(--primary);
  transition:background .2s,color .2s;
}
.step-item:hover .step-num-box{background:var(--primary);color:#fff}
.step-icon-box{width:34px;height:34px;flex-shrink:0;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;transition:transform .2s}
.step-item:hover .step-icon-box{transform:scale(1.08)}
.step-title{font-size:13px;font-weight:800;color:var(--ink);margin-bottom:3px}
.step-desc{font-size:11.5px;color:var(--ink-3);line-height:1.55}
.step-arrow{margin-left:auto;flex-shrink:0;font-size:19px;color:#c7d2fe;transition:color .2s,transform .2s;align-self:center}
.step-item:hover .step-arrow{color:var(--primary);transform:translateX(3px)}

/* â??â??â??â??â??â??â??â??â??â??â??â??â??â?? INFO BANNER â??â??â??â??â??â??â??â??â??â??â??â??â??â?? */
.info-banner{
  background:rgba(67,73,207,.05);
  border:1.5px solid var(--primary-border);
  border-radius:var(--r-lg);padding:16px 18px;
  display:flex;align-items:flex-start;gap:13px;
}
.info-banner-icon{width:36px;height:36px;flex-shrink:0;background:var(--white);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;color:var(--primary);box-shadow:var(--shadow-sm)}
.info-banner-title{font-size:13px;font-weight:800;color:var(--ink);margin-bottom:4px}
.info-banner-text{font-size:12px;color:var(--ink-2);line-height:1.65}

/* â??â??â??â??â??â??â??â??â??â??â??â??â??â?? LOGOUT â??â??â??â??â??â??â??â??â??â??â??â??â??â?? */
.btn-logout{
  display:flex;align-items:center;justify-content:center;gap:7px;
  width:100%;height:44px;
  background:transparent;color:#ef4444;
  border:1.5px solid #fecaca;border-radius:var(--r);
  font-family:'Lexend',sans-serif;font-size:13px;font-weight:700;
  cursor:pointer;transition:background .15s,border-color .15s;text-decoration:none;
}
.btn-logout:hover{background:#fff1f2;border-color:#fca5a5}

/* â??â??â??â??â??â??â??â??â??â??â??â??â??â?? FOOTER â??â??â??â??â??â??â??â??â??â??â??â??â??â?? */
.foot{text-align:center;font-size:12px;color:var(--ink-3);padding:18px 0 4px;border-top:1px solid var(--primary-lt);margin-top:4px}
.foot a{color:var(--primary);font-weight:600}
.foot a:hover{text-decoration:underline}

/* â??â??â??â??â??â??â??â??â??â??â??â??â??â?? RESPONSIVE â??â??â??â??â??â??â??â??â??â??â??â??â??â?? */
@media(max-width:899px){.main-grid{grid-template-columns:1fr}}
@media(max-width:500px){
  .card-body{padding:16px}
  .card-header{padding:13px 16px 11px}
  .step-item:hover{margin:0 -16px;padding-left:16px;padding-right:16px}
}
</style>
</head>
<body>
<!-- â??â?? Approval Status Banner â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â?? -->
<?php
$teacher_status = $teacher['status'] ?? 'pending';
$teacher_display_name = $teacher['name'] ?? ($_SESSION['teacher_name'] ?? $_SESSION['name'] ?? 'Teacher');
?>
<?php if ($teacher_status === 'pending'): ?>
<div style="background:#fffbeb;border-bottom:2px solid #fcd34d;padding:12px 20px;display:flex;align-items:center;gap:10px;font-family:'Lexend',sans-serif;position:relative;z-index:100;">
  <span style="font-size:22px;"></span>
  <div>
    <p style="font-weight:700;font-size:14px;color:#92400e;margin:0;">Account Approval Pending</p>
    <p style="font-size:12px;color:#b45309;margin:0;">Your account is under review by the admin. You will be notified once approved.</p>
  </div>
</div>
<?php elseif ($teacher_status === 'approved'): ?>
<div style="background:#f0fdf4;border-bottom:2px solid #86efac;padding:12px 20px;display:flex;align-items:center;gap:10px;font-family:'Lexend',sans-serif;position:relative;z-index:100;">
  <span style="font-size:22px;"></span>
  <div>
    <p style="font-weight:700;font-size:14px;color:#166534;margin:0;">Account Approved! Welcome, <?php echo htmlspecialchars($teacher_display_name); ?>!</p>
    <p style="font-size:12px;color:#15803d;margin:0;">Complete your profile to access your full teacher dashboard.</p>
  </div>
</div>
<?php elseif ($teacher_status === 'rejected'): ?>
<div style="background:#fef2f2;border-bottom:2px solid #fca5a5;padding:12px 20px;display:flex;align-items:center;gap:10px;font-family:'Lexend',sans-serif;position:relative;z-index:100;">
  <span style="font-size:22px;"></span>
  <div>
    <p style="font-weight:700;font-size:14px;color:#991b1b;margin:0;">Account Rejected</p>
    <p style="font-size:12px;color:#dc2626;margin:0;">Your registration was not approved. Please contact the admin for more information.</p>
  </div>
</div>
<?php endif; ?>
<!-- â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->


<!-- â”€â”€ TOPNAV â”€â”€ -->
<header class="topnav glass">
  <div class="topnav-inner">
    <div class="nav-logo">
      <div class="nav-logo-icon"><span class="mi">school</span></div>
      <span class="nav-brand">College<b>Connect</b></span>
    </div>
    <div class="nav-right">
      <a href="teacher_dashboard.php" class="nav-icon-btn" title="Dashboard"><span class="mi">dashboard</span></a>
      <a href="teacher_complete_profile.php" class="nav-icon-btn" title="Edit Profile"><span class="mi">edit</span></a>
      <div class="nav-avatar">
        <img src="<?= $photo ?>" alt="Profile" onerror="this.src='https://ui-avatars.com/api/?name=T&background=4349cf&color=fff&bold=true&size=80'"/>
      </div>
    </div>
  </div>
</header>

<div class="page">

  <!-- â”€â”€ HERO â”€â”€ -->
  <section class="hero">
    <div class="hero-dots"></div>
    <div class="hero-orb hero-orb-1"></div>
    <div class="hero-orb hero-orb-2"></div>
    <div class="hero-float"><span class="mi">auto_awesome</span></div>

    <div class="hero-tag"><span class="hero-tag-dot"></span> Faculty Portal Live</div>
    <div class="hero-name">Welcome, <?= htmlspecialchars($teacher['name'] ?? 'Professor') ?>!</div>
    <div class="hero-role">
      <?= htmlspecialchars($teacher['designation'] ?? 'Designation not set') ?>
      <?php if (!empty($teacher['department'])): ?>&nbsp;&nbsp;<?= htmlspecialchars($teacher['department']) ?><?php endif; ?>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><span class="mi">menu_book</span><?= $subjectsCount ?> Subjects</div>
      <div class="hero-stat"><span class="mi">groups</span><?= $studentCount ?> Students</div>
      <div class="hero-stat"><span class="mi">workspace_premium</span><?= $completionPct ?>% Profile</div>
    </div>
  </section>

  <!-- â”€â”€ MAIN GRID â”€â”€ -->
  <div class="main-grid">

    <!-- LEFT COL -->
    <div style="display:flex;flex-direction:column;gap:18px">

      <!-- Profile Card -->
      <div class="card reveal rv1">
        <div class="card-shimmer-bar"></div>
        <div class="card-header">
          <div class="card-header-icon"><span class="mi">manage_accounts</span></div>
          <div>
            <div class="card-header-title">Your Profile</div>
            <div class="card-header-sub">Faculty account status</div>
          </div>
        </div>
        <div class="card-body">
          <div class="profile-center">
            <div class="ring-wrap">
              <svg viewBox="0 0 80 80">
                <defs>
                  <linearGradient id="rg" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" stop-color="<?= $isComplete ? '#16a34a' : '#4349cf' ?>"/>
                    <stop offset="100%" stop-color="<?= $isComplete ? '#4ade80' : '#a78bfa' ?>"/>
                  </linearGradient>
                </defs>
                <circle class="ring-track" cx="40" cy="40" r="34"/>
                <circle class="ring-fill" cx="40" cy="40" r="34"
                  stroke-dasharray="<?= round($circumference,1) ?>"
                  stroke-dashoffset="<?= $dashOffset ?>"
                  id="ringFill"/>
              </svg>
              <div class="ring-photo">
                <img src="<?= $photo ?>" alt="Photo" onerror="this.src='https://ui-avatars.com/api/?name=T&background=4349cf&color=fff&bold=true&size=128'"/>
              </div>
              <div class="ring-pct"><?= $completionPct ?>%</div>
            </div>

            <div class="profile-name"><?= htmlspecialchars($teacher['name'] ?? 'Professor') ?></div>
            <div class="profile-sub"><?= htmlspecialchars($teacher['designation'] ?? '') ?><?= !empty($teacher['department']) ? ' '.$teacher['department'] : '' ?></div>

            <?php if ($isComplete): ?>
              <span class="status-badge badge-ok"><span class="mi" style="font-size:14px;animation:checkPop .5s .2s cubic-bezier(.34,1.56,.64,1) both">check_circle</span> Profile Complete</span>
            <?php else: ?>
              <span class="status-badge badge-warn"><span class="mi" style="font-size:14px">warning</span> Incomplete</span>
            <?php endif; ?>
          </div>

          <div class="pbar-wrap">
            <div class="pbar-top"><span>Completion</span><em><?= $completionPct ?>%</em></div>
            <div class="pbar-track"><div class="pbar-fill" id="pbarFill" style="width:<?= $completionPct ?>%"></div></div>
          </div>

          <?php if (!$isComplete && !empty($missingFields)): ?>
          <div class="missing-label">Still needed</div>
          <div class="chips-wrap">
            <?php foreach($missingFields as $mf): ?>
            <span class="chip"><span class="mi"><?= $mf['icon'] ?></span><?= $mf['label'] ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <div class="btn-row">
            <a href="teacher_complete_profile.php" class="btn-p">
              <span class="mi" style="font-size:16px">edit_note</span>
              <?= $isComplete ? 'Edit Profile' : 'Finish Setup' ?>
              <span class="mi" style="font-size:16px">arrow_forward</span>
            </a>
            <a href="teacher_dashboard.php" class="btn-o">
              <span class="mi" style="font-size:16px">dashboard</span>Dashboard
            </a>
          </div>
        </div>
      </div>

      <!-- Info Banner -->
      <div class="info-banner reveal rv2">
        <div class="info-banner-icon"><span class="mi">info</span></div>
        <div>
          <div class="info-banner-title"><?= $isComplete ? 'All features unlocked' : 'Account pending activation' ?></div>
          <div class="info-banner-text">
            <?= $isComplete
              ? 'Your profile is fully active. Upload notices, share notes with sections, track attendance, and participate in the community forum.'
              : 'Complete your faculty profile to unlock notice uploads, section notes, attendance tracking, and the community forum. Students get real-time notifications for every resource you share.' ?>
          </div>
        </div>
      </div>

      <!-- Logout -->
      <div class="reveal rv3">
        <a href="../auth/logout.php" class="btn-logout"><span class="mi">logout</span>Logout</a>
      </div>
    </div>

    <!-- RIGHT COL -->
    <div style="display:flex;flex-direction:column;gap:18px">

      <!-- Features -->
      <div class="reveal rv1">
        <div style="font-size:10.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--ink-3);margin-bottom:11px">Platform Features</div>
        <div class="feat-grid">
          <?php
          $feats = [
            ['campaign',    'background:rgba(67,73,207,.08)',  'color:var(--primary)',  'Upload Notices',   'Post section-targeted announcements instantly'],
            ['description', 'background:rgba(107,111,245,.1)', 'color:#6b6ff5',        'Share Notes',      'Upload lecture docs and course files'],
            ['how_to_reg',  'background:rgba(34,197,94,.1)',   'color:#16a34a',        'Attendance',       'Track daily student attendance with ease'],
            ['forum',       'background:rgba(167,139,250,.15)','color:#7c3aed',        'Community Forum',  'Campus-wide discussions and Q&A'],
          ];
          foreach($feats as[$ic,$ibg,$icol,$ti,$de]):
          ?>
          <div class="feat-card">
            <div class="feat-icon" style="<?=$ibg?>"><span class="mi" style="<?=$icol?>"><?=$ic?></span></div>
            <div>
              <div class="feat-title"><?=$ti?></div>
              <div class="feat-desc"><?=$de?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Getting Started -->
      <div class="card reveal rv2">
        <div class="card-header">
          <div class="card-header-icon"><span class="mi">lightbulb</span></div>
          <div>
            <div class="card-header-title">Getting Started Guide</div>
            <div class="card-header-sub">5 steps to activate your full account</div>
          </div>
        </div>
        <div class="card-body" style="padding-top:4px;padding-bottom:4px">
          <div class="steps-list">
            <?php
            $steps = [
              ['01','edit_note',   'background:rgba(67,73,207,.08)', 'color:var(--primary)', 'Complete Your Profile',  'Add department, designation, qualification and photo.'],
              ['02','upload_file', 'background:rgba(107,111,245,.1)','color:#6b6ff5',        'Upload Course Notes',    'Share organised lecture documents under the right subject.'],
              ['03','campaign',    'background:rgba(67,73,207,.07)', 'color:var(--primary)', 'Post a Notice',          'Send section-targeted announcements to your students.'],
              ['04','how_to_reg',  'background:rgba(34,197,94,.1)',  'color:#16a34a',        'Configure Attendance',   'Set up and take your first attendance record.'],
              ['05','forum',       'background:rgba(167,139,250,.15)','color:#7c3aed',       'Join the Forum',         'Engage in campus-wide discussions and support students.'],
            ];
            foreach($steps as[$n,$ic,$ibg,$icol,$ti,$de]):
            ?>
            <div class="step-item">
              <div class="step-num-box"><?=$n?></div>
              <div class="step-icon-box" style="<?=$ibg?>"><span class="mi" style="<?=$icol?>;font-size:17px"><?=$ic?></span></div>
              <div style="flex:1;min-width:0">
                <div class="step-title"><?=$ti?></div>
                <div class="step-desc"><?=$de?></div>
              </div>
              <span class="mi step-arrow">chevron_right</span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div style="padding:4px 20px 18px">
          <a href="teacher_complete_profile.php" class="btn-p" style="width:100%">
            <span class="mi" style="font-size:17px">rocket_launch</span>
            Start with Step 1 Complete Your Profile
          </a>
        </div>
      </div>

    </div>
  </div><!-- /main-grid -->

  <footer class="foot">
    &copy; <?= date('Y') ?> <strong><?= $siteName ?></strong> &nbsp;&nbsp; Faculty Portal &nbsp;&nbsp;
    <a href="teacher_complete_profile.php">Complete Profile</a>
  </footer>

</div><!-- /page -->

<script>
// Scroll reveal
(function(){
  const els = document.querySelectorAll('.reveal');
  const io = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); } });
  }, {threshold:.1});
  els.forEach(el => io.observe(el));
})();

// Animate ring on load
window.addEventListener('load', () => {
  const ring = document.getElementById('ringFill');
  if (!ring) return;
  const final = ring.getAttribute('stroke-dashoffset');
  ring.style.strokeDashoffset = '<?= round($circumference,1) ?>';
  requestAnimationFrame(() => requestAnimationFrame(() => { ring.style.strokeDashoffset = final; }));
});

// Ripple on .btn-p
document.querySelectorAll('.btn-p').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const r = btn.getBoundingClientRect();
    const sz = Math.max(r.width, r.height) * 2;
    const rpl = document.createElement('span');
    rpl.style.cssText = `position:absolute;border-radius:50%;pointer-events:none;width:${sz}px;height:${sz}px;left:${e.clientX-r.left-sz/2}px;top:${e.clientY-r.top-sz/2}px;background:rgba(255,255,255,.22);transform:scale(0);animation:ripple .5s linear forwards;`;
    btn.appendChild(rpl);
    setTimeout(() => rpl.remove(), 520);
  });
});
</script>
</body>
</html>