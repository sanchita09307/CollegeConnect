<?php
// Session settings must match register_process.php
ini_set('session.gc_maxlifetime',  7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.cookie_path',     '/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings  = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
$site_name = htmlspecialchars($settings['site_name'] ?? 'CollegeConnect');

// Session check - redirect to login if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: ../login.php");
    exit();
}

// Fetch student from DB
$_sid  = (int)$_SESSION['user_id'];
$_sres = $conn->prepare("SELECT full_name, department, semester, status FROM students WHERE id = ? LIMIT 1");
$_sres->bind_param("i", $_sid);
$_sres->execute();
$_srow = $_sres->get_result()->fetch_assoc();
$_sres->close();

$student_name   = $_srow['full_name']   ?? ($_SESSION['student_name'] ?? $_SESSION['name'] ?? 'Student');
$student_status = $_srow['status']      ?? 'pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= $site_name ?> Student Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#fff;color:#0a1628;overflow-x:hidden;line-height:1.6}
a{text-decoration:none;color:inherit}
:root{
  --blue:#1a6bff;--blue-dark:#0050e6;--blue-light:#e8f0ff;--blue-mid:#4d8dff;
  --white:#fff;--gray-50:#f7f9fc;--gray-100:#eef2f8;--gray-300:#c8d4e8;
  --gray-500:#7a8ba8;--gray-700:#3d5273;--ink:#0a1628;
  --r:16px;--r-lg:24px;
  --shadow-sm:0 2px 12px rgba(26,107,255,.08);
  --shadow-md:0 8px 32px rgba(26,107,255,.13);
  --shadow-lg:0 20px 60px rgba(26,107,255,.18);
}
@keyframes fadeUp{from{opacity:0;transform:translateY(36px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.45}}
@keyframes shimmer{0%{background-position:-200% center}100%{background-position:200% center}}
@keyframes blobA{0%,100%{border-radius:60% 40% 30% 70%/60% 30% 70% 40%}50%{border-radius:30% 60% 70% 40%/50% 60% 30% 60%}}
@keyframes blobB{0%,100%{border-radius:40% 60% 60% 40%/40% 60% 40% 60%}50%{border-radius:60% 40% 40% 60%/60% 40% 60% 40%}}
@keyframes slideBar{from{width:0}to{width:var(--tw,30%)}}
@keyframes marquee{0%{transform:translateX(0)}100%{transform:translateX(-50%)}}
.reveal{opacity:0;transform:translateY(32px);transition:opacity .65s cubic-bezier(.22,1,.36,1),transform .65s cubic-bezier(.22,1,.36,1)}
.reveal.visible{opacity:1;transform:none}
.d1{transition-delay:.08s}.d2{transition-delay:.17s}.d3{transition-delay:.27s}
.d4{transition-delay:.37s}.d5{transition-delay:.48s}.d6{transition-delay:.60s}
.d7{transition-delay:.72s}.d8{transition-delay:.86s}
.mi{font-family:'Material Symbols Rounded';font-size:1em;line-height:1;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;user-select:none}

/* NAV */
nav{position:fixed;top:0;left:0;right:0;z-index:100;background:rgba(255,255,255,.9);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid var(--gray-100);height:64px;padding:0 6vw;display:flex;align-items:center;justify-content:space-between;animation:fadeIn .5s ease both}
.nav-logo{display:flex;align-items:center;gap:10px}
.nav-logo-icon{width:38px;height:38px;background:var(--blue);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;box-shadow:0 4px 14px rgba(26,107,255,.32)}
.nav-brand{font-size:17px;font-weight:800;letter-spacing:-.02em;color:var(--ink)}
.nav-brand b{color:var(--blue);font-weight:800}
.nav-btn{display:inline-flex;align-items:center;gap:7px;background:var(--blue);color:#fff;border:none;border-radius:10px;padding:9px 20px;font-family:inherit;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(26,107,255,.28);transition:transform .15s,box-shadow .15s;text-decoration:none}
.nav-btn:hover{transform:translateY(-1px);box-shadow:0 8px 22px rgba(26,107,255,.38)}
.nav-btn:active{transform:scale(.97)}

/* HERO */
.hero{min-height:100svh;display:flex;align-items:center;padding:80px 6vw 60px;position:relative;overflow:hidden;background:#fff}
.hero-bg{position:absolute;inset:0;pointer-events:none;overflow:hidden}
.blob{position:absolute;filter:blur(70px);opacity:.5}
.blob-1{width:clamp(260px,48vw,540px);height:clamp(260px,48vw,540px);background:rgba(26,107,255,.16);top:-8%;right:-6%;animation:blobA 14s ease-in-out infinite}
.blob-2{width:clamp(180px,32vw,360px);height:clamp(180px,32vw,360px);background:rgba(77,141,255,.13);bottom:4%;left:-4%;animation:blobB 18s ease-in-out infinite}
.dots{position:absolute;inset:0;background-image:radial-gradient(var(--gray-300) 1px,transparent 1px);background-size:28px 28px;opacity:.35}
.hero-inner{position:relative;z-index:2;max-width:1160px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;gap:56px;align-items:center}
.hero-left{animation:fadeUp .7s .1s ease both}
.htag{display:inline-flex;align-items:center;gap:8px;background:var(--blue-light);color:var(--blue);border-radius:99px;padding:6px 14px 6px 8px;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;margin-bottom:22px}
.htag-dot{width:8px;height:8px;background:var(--blue);border-radius:50%;animation:pulse 2s ease infinite;flex-shrink:0}
h1{font-size:clamp(34px,5vw,62px);font-weight:800;line-height:1.1;letter-spacing:-.03em;color:var(--ink);margin-bottom:20px}
h1 .blue{color:var(--blue)}
h1 span{display:block}
.hdesc{font-size:clamp(14px,1.6vw,17px);color:var(--gray-700);line-height:1.75;margin-bottom:32px;max-width:470px}
.hbtns{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.btn-p{display:inline-flex;align-items:center;gap:8px;background:var(--blue);color:#fff;border:none;border-radius:12px;padding:14px 24px;font-family:inherit;font-size:14.5px;font-weight:700;cursor:pointer;position:relative;overflow:hidden;box-shadow:0 6px 24px rgba(26,107,255,.34);transition:transform .15s,box-shadow .15s;text-decoration:none}
.btn-p::before{content:'';position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);background-size:200% auto;animation:shimmer 2.5s linear infinite}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 12px 30px rgba(26,107,255,.44)}
.btn-p:active{transform:scale(.97)}
.btn-s{display:inline-flex;align-items:center;gap:7px;background:transparent;color:var(--blue);border:2px solid var(--blue-light);border-radius:12px;padding:12px 20px;font-family:inherit;font-size:14.5px;font-weight:700;cursor:pointer;transition:background .15s,border-color .15s;text-decoration:none}
.btn-s:hover{background:var(--blue-light);border-color:var(--blue-light)}

/* hero visual */
.hero-right{display:flex;justify-content:center;animation:fadeUp .7s .25s ease both}
.card-stack{position:relative;width:clamp(240px,100%,360px)}
.hcard{background:#fff;border:1.5px solid var(--gray-100);border-radius:var(--r-lg);padding:22px;box-shadow:var(--shadow-md)}
.hcard-main{animation:float 6s ease-in-out infinite}
.hcard-back{position:absolute;top:14px;left:50%;transform:translateX(-50%) scale(.92);width:90%;background:var(--blue-light);border:1.5px solid rgba(26,107,255,.15);z-index:-1;box-shadow:none}
.hcard-head{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.hcard-av{width:44px;height:44px;background:var(--blue);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;flex-shrink:0}
.hcard-nm{font-weight:700;font-size:15px;color:var(--ink)}
.hcard-sb{font-size:12px;color:var(--gray-500)}
.hcard-badge{margin-left:auto;background:rgba(26,107,255,.1);color:var(--blue);font-size:10px;font-weight:700;padding:3px 9px;border-radius:99px;white-space:nowrap}
.hbar-label{display:flex;justify-content:space-between;font-size:12px;font-weight:600;color:var(--gray-700);margin-bottom:7px}
.hbar-label em{color:var(--blue);font-weight:800;font-style:normal}
.hbar-track{height:8px;background:var(--gray-100);border-radius:99px;overflow:hidden;margin-bottom:16px}
.hbar-fill{height:100%;background:linear-gradient(90deg,var(--blue),var(--blue-mid));border-radius:99px;width:0;animation:slideBar 1.2s 1s cubic-bezier(.4,0,.2,1) forwards;--tw:30%}
.chips{display:flex;gap:7px;flex-wrap:wrap}
.chip{display:inline-flex;align-items:center;gap:5px;background:var(--gray-50);border:1px solid var(--gray-100);border-radius:8px;padding:5px 10px;font-size:11px;font-weight:600;color:var(--gray-700)}
.chip .mi{color:var(--blue);font-size:14px}

/* MARQUEE */
.mstrip{padding:15px 0;background:var(--blue);overflow:hidden;white-space:nowrap}
.minner{display:inline-flex;animation:marquee 26s linear infinite}
.mitem{display:inline-flex;align-items:center;gap:9px;padding:0 28px;color:rgba(255,255,255,.82);font-size:13px;font-weight:600}
.mitem .mi{font-size:15px;color:rgba(255,255,255,.65)}
.mdot{width:4px;height:4px;background:rgba(255,255,255,.35);border-radius:50%;margin:0 4px}

/* SECTIONS */
section{padding:78px 6vw}
.si{max-width:1160px;margin:0 auto}
.eyebrow{display:inline-flex;align-items:center;gap:7px;background:var(--blue-light);color:var(--blue);border-radius:99px;padding:5px 14px;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;margin-bottom:16px}
h2{font-size:clamp(26px,3.8vw,42px);font-weight:800;letter-spacing:-.025em;color:var(--ink);line-height:1.15;margin-bottom:12px}
h2 .blue{color:var(--blue)}
.sdesc{font-size:clamp(14px,1.5vw,16.5px);color:var(--gray-700);line-height:1.75;max-width:520px}

/* FEATURES */
.feat-bg{background:var(--gray-50)}
.fgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:18px;margin-top:46px}
.fcard{background:#fff;border:1.5px solid var(--gray-100);border-radius:var(--r-lg);padding:26px 22px;display:flex;flex-direction:column;gap:13px;position:relative;overflow:hidden;transition:transform .22s,box-shadow .22s,border-color .22s;cursor:default}
.fcard::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--blue),var(--blue-mid));transform:scaleX(0);transform-origin:left;transition:transform .28s ease}
.fcard:hover{transform:translateY(-4px);box-shadow:var(--shadow-md);border-color:rgba(26,107,255,.2)}
.fcard:hover::after{transform:scaleX(1)}
.ficon{width:50px;height:50px;background:var(--blue-light);border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--blue);transition:background .22s,transform .22s}
.fcard:hover .ficon{background:var(--blue);color:#fff;transform:scale(1.07)}
.ftitle{font-size:16px;font-weight:800;color:var(--ink)}
.fdesc{font-size:13px;color:var(--gray-700);line-height:1.65}
.ftag{display:inline-block;background:var(--blue-light);color:var(--blue);font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:3px 10px;border-radius:99px;width:fit-content}

/* STEPS */
.sgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:0;margin-top:50px;position:relative}
.step{display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 20px 28px;position:relative}
.snum{width:68px;height:68px;background:#fff;border:2.5px solid var(--blue);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:var(--blue);margin-bottom:18px;position:relative;z-index:1;box-shadow:0 0 0 8px rgba(26,107,255,.07);transition:background .22s,color .22s,transform .22s}
.step:hover .snum{background:var(--blue);color:#fff;transform:scale(1.08)}
.sicon{font-size:26px;color:var(--blue);margin-bottom:10px}
.stitle{font-size:15px;font-weight:800;color:var(--ink);margin-bottom:6px}
.sdescr{font-size:13px;color:var(--gray-700);line-height:1.65}

/* STATS */
.sband{background:linear-gradient(135deg,var(--blue-dark) 0%,var(--blue) 55%,var(--blue-mid) 100%);padding:56px 6vw}
.sgrid2{max-width:960px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:28px;text-align:center}
.snum2{font-size:clamp(36px,5.5vw,54px);font-weight:800;color:#fff;letter-spacing:-.03em;line-height:1}
.slabel{font-size:13px;font-weight:600;color:rgba(255,255,255,.68);margin-top:5px;letter-spacing:.02em}
.sicon2{font-size:22px;color:rgba(255,255,255,.38);margin-bottom:7px}

/* CTA */
.ctasec{background:#fff;padding:78px 6vw}
.ctacard{max-width:880px;margin:0 auto;background:linear-gradient(135deg,#f0f5ff,#e6eeff);border:2px solid rgba(26,107,255,.14);border-radius:28px;padding:clamp(30px,6vw,58px);display:grid;grid-template-columns:1fr auto;gap:36px;align-items:center;position:relative;overflow:hidden}
.ctacard-bg{position:absolute;inset:0;background-image:radial-gradient(rgba(26,107,255,.06) 1.5px,transparent 1.5px);background-size:20px 20px;pointer-events:none}
.ctacard-blob{position:absolute;width:280px;height:280px;background:rgba(26,107,255,.08);border-radius:50%;right:-70px;bottom:-70px;filter:blur(48px);pointer-events:none}
.ctaeyebrow{display:inline-flex;align-items:center;gap:6px;background:rgba(26,107,255,.12);color:var(--blue);border-radius:99px;padding:4px 12px;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;margin-bottom:14px}
.ctatitle{font-size:clamp(22px,3.2vw,34px);font-weight:800;color:var(--ink);letter-spacing:-.025em;line-height:1.2;margin-bottom:10px}
.ctadesc{font-size:14px;color:var(--gray-700);line-height:1.75;margin-bottom:24px}
.checklist{display:flex;flex-direction:column;gap:9px;margin-bottom:30px}
.ccheck{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:600;color:var(--gray-700)}
.ccheck-icon{width:22px;height:22px;flex-shrink:0;background:var(--blue);border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px}
/* phone mockup */
.pmock{flex-shrink:0;width:clamp(90px,18vw,148px)}
.pframe{background:var(--ink);border-radius:26px;padding:7px;box-shadow:var(--shadow-lg);animation:float 5s ease-in-out infinite}
.pscreen{background:#fff;border-radius:20px;overflow:hidden;aspect-ratio:9/16;display:flex;flex-direction:column}
.pbar{height:5px;background:var(--blue);width:100%}
.pcontent{padding:10px;flex:1;display:flex;flex-direction:column;gap:7px}
.prow{height:7px;background:var(--gray-100);border-radius:99px}
.prow.b{background:rgba(26,107,255,.18);width:65%}
.pirow{display:flex;gap:4px;margin-top:3px}
.pibox{flex:1;aspect-ratio:1;background:var(--blue-light);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--blue)}

/* FOOTER */
footer{background:var(--ink);color:rgba(255,255,255,.55);text-align:center;padding:28px 20px;font-size:13px}
footer strong{color:#fff}
footer a{color:rgba(77,141,255,.85);font-weight:600}
footer a:hover{color:var(--blue-mid)}

/* RESPONSIVE */
@media(max-width:880px){
  .hero-inner{grid-template-columns:1fr;gap:36px;text-align:center}
  .hero-left{display:flex;flex-direction:column;align-items:center}
  .hdesc{text-align:center}
  .ctacard{grid-template-columns:1fr}
  .pmock{display:none}
}
@media(max-width:580px){
  section{padding:58px 5vw}
  .fgrid{grid-template-columns:1fr}
  .sgrid{grid-template-columns:1fr 1fr}
  nav{padding:0 4vw}
}
@media(max-width:360px){.sgrid{grid-template-columns:1fr}}
</style>
</head>
<body>

<!-- â”€â”€ Approval Status Banner â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<?php if ($student_status === 'pending'): ?>
<div style="background:#fffbeb;border-bottom:2px solid #fcd34d;padding:12px 20px;display:flex;align-items:center;gap:10px;font-family:'Plus Jakarta Sans',sans-serif;">
  <span style="font-size:22px;">â³</span>
  <div>
    <p style="font-weight:700;font-size:14px;color:#92400e;margin:0;">Account Approval Pending</p>
    <p style="font-size:12px;color:#b45309;margin:0;">Your account is under review by the admin. You will be notified once approved.</p>
  </div>
</div>
<?php elseif ($student_status === 'approved'): ?>
<div style="background:#f0fdf4;border-bottom:2px solid #86efac;padding:12px 20px;display:flex;align-items:center;gap:10px;font-family:'Plus Jakarta Sans',sans-serif;">
  <span style="font-size:22px;"></span>
  <div>
    <p style="font-weight:700;font-size:14px;color:#166534;margin:0;">Account Approved!</p>
    <p style="font-size:12px;color:#15803d;margin:0;">Welcome, <?php echo htmlspecialchars($student_name); ?>! Complete your profile to unlock your dashboard.</p>
  </div>
</div>
<?php elseif ($student_status === 'rejected'): ?>
<div style="background:#fef2f2;border-bottom:2px solid #fca5a5;padding:12px 20px;display:flex;align-items:center;gap:10px;font-family:'Plus Jakarta Sans',sans-serif;">
  <span style="font-size:22px;"></span>
  <div>
    <p style="font-weight:700;font-size:14px;color:#991b1b;margin:0;">Account Rejected</p>
    <p style="font-size:12px;color:#dc2626;margin:0;">Your registration was not approved. Please contact the admin for more information.</p>
  </div>
</div>
<?php endif; ?>
<!-- â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->

<!-- NAV -->
<nav>
  <div class="nav-logo">
    <div class="nav-logo-icon"><span class="mi">school</span></div>
    <span class="nav-brand">College<b>Connect</b></span>
  </div>
  <a href="complete_profile.php" class="nav-btn">
    <span class="mi" style="font-size:16px">person_edit</span>
    Complete Profile
  </a>
</nav>

<!-- HERO -->
<section class="hero" id="top">
  <div class="hero-bg">
    <div class="dots"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
  </div>
  <div class="hero-inner">
    <div class="hero-left">
      <div class="htag"><span class="htag-dot"></span>Student Portal 2026</div>
      <h1>
        <span>Everything for</span>
        <span class="blue">college life,</span>
        <span>one portal.</span>
      </h1>
      <p class="hdesc">CollegeConnct gives students instant access to schedules, grades, attendance, notices, clubs, and official documents all in one fast, clean platform.</p>
      <div class="hbtns">
        <a href="complete_profile.php" class="btn-p">
          <span class="mi" style="font-size:18px">arrow_forward</span>
          Get Started Complete Profile
        </a>
        <a href="#features" class="btn-s">
          <span class="mi" style="font-size:16px">explore</span>
          See Features
        </a>
      </div>
    </div>
    <div class="hero-right">
      <div class="card-stack">
        <div class="hcard hcard-back"></div>
        <div class="hcard hcard-main">
          <div class="hcard-head">
            <div class="hcard-av"><span class="mi">person</span></div>
            <div>
              <div class="hcard-nm">New Student</div>
              <div class="hcard-sb">Semester 1 2026</div>
            </div>
            <span class="hcard-badge">Active</span>
          </div>
          <div class="hbar-label">Profile Setup <em>30%</em></div>
          <div class="hbar-track"><div class="hbar-fill" style="--tw:30%"></div></div>
          <div class="chips">
            <span class="chip"><span class="mi">calendar_month</span> Schedule</span>
            <span class="chip"><span class="mi">grade</span> Grades</span>
            <span class="chip"><span class="mi">event_available</span> Attendance</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- MARQUEE -->
<div class="mstrip" aria-hidden="true">
  <div class="minner">
    <?php
    $items=[['schedule','Timetable'],['grade','Live Grades'],['event_available','Attendance'],['campaign','Notices'],['groups','Clubs'],['description','Documents'],['payments','Fee Portal'],['chat','Student Chat'],['verified','Results'],['school','Academic Record']];
    for($i=0;$i<2;$i++) foreach($items as[$ic,$lb]):
    ?><span class="mitem"><span class="mi"><?=$ic?></span><?=$lb?><span class="mdot"></span></span><?php endforeach;?>
  </div>
</div>

<!-- FEATURES -->
<section class="feat-bg" id="features">
  <div class="si">
    <div class="reveal">
      <div class="eyebrow"><span class="mi" style="font-size:14px">auto_awesome</span> What We Offer</div>
      <h2>Built for <span class="blue">student success</span></h2>
      <p class="sdesc">Every tool a student needs from day one to graduation designed to be fast, clear, and always available.</p>
    </div>
    <div class="fgrid">
      <?php
      $feats=[
        ['calendar_month','Smart Timetable','View your full class schedule, track changes, upcoming exams, and get reminders â€” all synced live.','Timetable'],
        ['grade','Grades & GPA','Access marks the moment they are published. Track GPA and subject progress across every semester.','Academics'],
        ['event_available','Attendance Tracker','Monitor attendance per subject with real-time updates. Get instant alerts when you fall below the limit.','Tracking'],
        ['campaign','Live Notices','Receive college announcements, circulars, exam schedules, and fee reminders the second they go out.','Notices'],
        ['groups','Clubs & Events','Discover college fests, sports, clubs, and co-curricular activities. Apply and register directly.','Community'],
        ['description','Documents','Download bonafides, ID cards, fee receipts, and official letters instantly â€” no office queues ever.','Files'],
        ['payments','Fee Portal','View fee dues and make secure online payments. Full transaction history at your fingertips.','Payments'],
        ['chat','Student Connect','Message classmates, join study groups, and collaborate with peers right inside the portal.','Social'],
      ];
      foreach($feats as $i=>[$ic,$ti,$de,$ta]):
        $d=$i<8?" d".($i+1):"";
      ?>
      <div class="fcard reveal<?=$d?>">
        <div class="ficon"><span class="mi"><?=$ic?></span></div>
        <div>
          <div class="ftitle"><?=$ti?></div>
          <div class="fdesc"><?=$de?></div>
        </div>
        <span class="ftag"><?=$ta?></span>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section id="how">
  <div class="si">
    <div class="reveal" style="text-align:center;max-width:580px;margin:0 auto">
      <div class="eyebrow"><span class="mi" style="font-size:14px">map</span> How It Works</div>
      <h2>Up and running in <span class="blue">3 steps</span></h2>
      <p class="sdesc" style="margin:0 auto">Getting started takes under 2 minutes. Your full student dashboard is just a few clicks away.</p>
    </div>
    <div class="sgrid">
      <?php
      $steps=[
        ['1','how_to_reg','Create Account','Your college issues login credentials. Sign in securely with your student ID and set your password.'],
        ['2','person_edit','Complete Profile','Fill in your department, year, and roll number. This unlocks your personalised dashboard instantly.'],
        ['3','dashboard','Your Dashboard is Live','Schedule, grades, attendance, and notices â€” all synced and ready for you to use right away.'],
      ];
      foreach($steps as $i=>[$n,$ic,$ti,$de]):
      ?>
      <div class="step reveal d<?=$i+1?>">
        <div class="snum"><?=$n?></div>
        <div class="sicon"><span class="mi"><?=$ic?></span></div>
        <div class="stitle"><?=$ti?></div>
        <div class="sdescr"><?=$de?></div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- STATS BAND -->
<div class="sband">
  <div class="sgrid2">
    <?php
    $stats=[['auto_graph','10,000+','Students Active'],['school','50+','Colleges Onboarded'],['star','4.9','Student Rating'],['bolt','&lt;2 sec','Average Load Time']];
    foreach($stats as $i=>[$ic,$n,$l]):
    ?>
    <div class="reveal d<?=$i+1?>">
      <div class="sicon2"><span class="mi"><?=$ic?></span></div>
      <div class="snum2"><?=$n?></div>
      <div class="slabel"><?=$l?></div>
    </div>
    <?php endforeach;?>
  </div>
</div>

<!-- PROFILE CTA -->
<section class="ctasec" id="start">
  <div class="ctacard reveal">
    <div class="ctacard-bg"></div>
    <div class="ctacard-blob"></div>
    <div>
      <div class="ctaeyebrow"><span class="mi" style="font-size:14px">rocket_launch</span> Get Started Today</div>
      <div class="ctatitle">Complete your profile &amp;<br/>unlock your dashboard</div>
      <p class="ctadesc">Your profile is the key to everything on CollegeConnect. Takes less than 2 minutes and unlocks your complete student experience instantly.</p>
      <div class="checklist">
        <?php foreach(['Access your personalised timetable','View grades the moment they are published','Monitor attendance by subject with alerts','Receive instant college notices and circulars','Download official documents anytime, anywhere'] as $c):?>
        <div class="ccheck">
          <div class="ccheck-icon"><span class="mi" style="font-size:14px">check</span></div>
          <?=$c?>
        </div>
        <?php endforeach;?>
      </div>
      <a href="complete_profile.php" class="btn-p" style="width:fit-content">
        <span class="mi" style="font-size:18px">person_edit</span>
        Complete My Profile
      </a>
    </div>
    <div class="pmock">
      <div class="pframe">
        <div class="pscreen">
          <div class="pbar"></div>
          <div class="pcontent">
            <div class="prow b"></div>
            <div class="prow" style="width:82%"></div>
            <div class="prow" style="width:58%"></div>
            <div class="pirow">
              <div class="pibox"><span class="mi" style="font-size:12px">calendar_month</span></div>
              <div class="pibox"><span class="mi" style="font-size:12px">grade</span></div>
              <div class="pibox"><span class="mi" style="font-size:12px">campaign</span></div>
            </div>
            <div class="prow" style="margin-top:4px"></div>
            <div class="prow" style="width:72%"></div>
            <div class="prow" style="width:46%"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <strong><?=$site_name?></strong> &nbsp;&nbsp; Student Portal &nbsp;&nbsp;
  <a href="complete_profile.php">Complete Profile</a> &nbsp;&nbsp;
   <?=date('Y')?> CollegeConnct. All rights reserved.
</footer>

<script>
(function(){
  const els=document.querySelectorAll('.reveal');
  if(!els.length)return;
  const io=new IntersectionObserver(entries=>{
    entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');io.unobserve(e.target);}});
  },{threshold:.1});
  els.forEach(el=>io.observe(el));
})();
</script>
</body>
</html>