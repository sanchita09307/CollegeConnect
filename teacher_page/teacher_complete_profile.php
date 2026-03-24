<?php
// â??â?? Session settings â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
ini_set('session.gc_maxlifetime',  7200);
ini_set('session.cookie_lifetime', 7200);
// â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== "Teacher") {
    header("Location: ../login.php"); exit();
}

$teacher_id = (int) $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
if (!$teacher) die("Teacher not found");

$staffId = 'TCH-' . str_pad($teacher_id, 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Complete Profile â?? CollegeConnct</title>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet"/>
<style>
/* â??â?? RESET â??â?? */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased}
body{font-family:'Lexend',sans-serif;background:#eef0ff;color:#0d0e1c;min-height:100dvh;overflow-x:hidden}
a{text-decoration:none;color:inherit}
button{font-family:'Lexend',sans-serif;cursor:pointer}
input,select,textarea{font-family:'Lexend',sans-serif}

/* â??â?? ORIGINAL COLOR TOKENS â??â?? */
:root{
  --p:#4349cf;          /* primary */
  --p-d:#3338b0;        /* primary dark */
  --p-m:#6b6ff5;        /* primary mid */
  --p-lt:#eef0ff;       /* primary light bg */
  --p-pale:#f3f4ff;     /* very pale */
  --p-border:#c7d2fe;   /* border */
  --ink:#0d0e1c;
  --ink-2:#374151;
  --ink-3:#6b7280;
  --white:#fff;
  --r:12px;
  --r-lg:18px;
  --r-xl:24px;
  --sh-sm:0 2px 12px rgba(67,73,207,.08);
  --sh-md:0 8px 28px rgba(67,73,207,.14);
  --sh-lg:0 16px 48px rgba(67,73,207,.2);
}

/* â??â?? KEYFRAMES â??â?? */
@keyframes fadeUp{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideDown{from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:translateY(0)}}
@keyframes shimmer{0%{background-position:-300% center}100%{background-position:300% center}}
@keyframes stepIn{from{opacity:0;transform:translateX(36px)}to{opacity:1;transform:translateX(0)}}
@keyframes stepInBack{from{opacity:0;transform:translateX(-36px)}to{opacity:1;transform:translateX(0)}}
@keyframes checkPop{0%{transform:scale(0) rotate(-15deg);opacity:0}70%{transform:scale(1.2)}100%{transform:scale(1);opacity:1}}
@keyframes ripple{to{transform:scale(1);opacity:0}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.55;transform:scale(.94)}}
@keyframes barGrow{from{width:0}to{width:var(--w)}}
@keyframes tagIn{from{opacity:0;transform:scale(.8)}to{opacity:1;transform:scale(1)}}

/* â??â?? UTILS â??â?? */
.mi{font-family:'Material Symbols Rounded';font-variation-settings:'FILL' 1,'wght' 400,'GRAD' 0,'opsz' 24;line-height:1;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;user-select:none}

/* â??â?? SHELL â??â?? */
.shell{
  min-height:100dvh;
  display:grid;
  grid-template-columns:1fr;
}
@media(min-width:860px){
  .shell{grid-template-columns:260px 1fr}
}

/* â??â?? SIDEBAR â??â?? */
.sidebar{
  background:linear-gradient(160deg,var(--p) 0%,var(--p-m) 60%,#a78bfa 100%);
  padding:32px 24px;
  display:flex;flex-direction:column;gap:0;
  position:relative;overflow:hidden;
  animation:fadeIn .5s ease both;
}
@media(max-width:859px){
  .sidebar{
    padding:18px 20px 14px;
    flex-direction:row;align-items:center;gap:14px;
    position:sticky;top:0;z-index:40;
  }
}
.sb-dots{position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,.1) 1px,transparent 1px);background-size:22px 22px;pointer-events:none}
.sb-orb{position:absolute;border-radius:50%;filter:blur(50px);pointer-events:none;background:rgba(255,255,255,.1)}
.sb-orb-1{width:200px;height:200px;top:-50px;right:-50px;animation:float 10s ease-in-out infinite}
.sb-orb-2{width:150px;height:150px;bottom:-40px;left:-30px;animation:float 14s ease-in-out infinite reverse}

.sb-logo{display:flex;align-items:center;gap:10px;margin-bottom:36px;position:relative;z-index:1}
@media(max-width:859px){.sb-logo{margin-bottom:0}}
.sb-logo-icon{width:36px;height:36px;background:rgba(255,255,255,.2);border:1.5px solid rgba(255,255,255,.3);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px}
.sb-brand{font-size:15px;font-weight:800;color:#fff;letter-spacing:-.015em}
.sb-brand small{display:block;font-size:10px;font-weight:400;opacity:.6;letter-spacing:.03em}

/* sidebar step list */
.sb-steps{display:flex;flex-direction:column;gap:4px;flex:1;position:relative;z-index:1}
@media(max-width:859px){.sb-steps{flex-direction:row;justify-content:flex-end}}
.sb-step{display:flex;align-items:center;gap:12px;padding:12px 14px;border-radius:var(--r-lg);transition:background .2s}
.sb-step.active{background:rgba(255,255,255,.18)}
.sb-step-num{
  width:34px;height:34px;flex-shrink:0;
  border-radius:50%;border:2px solid rgba(255,255,255,.4);
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:700;color:rgba(255,255,255,.7);
  transition:all .25s;
}
.sb-step.active .sb-step-num{background:#fff;border-color:#fff;color:var(--p);font-weight:800}
.sb-step.done .sb-step-num{background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.5);color:#fff}
.sb-step-info{flex:1}
@media(max-width:859px){.sb-step-info{display:none}}
.sb-step-title{font-size:13px;font-weight:700;color:rgba(255,255,255,.9);line-height:1.2}
.sb-step.active .sb-step-title{color:#fff}
.sb-step-sub{font-size:10.5px;color:rgba(255,255,255,.5);margin-top:1px}
.sb-step.active .sb-step-sub{color:rgba(255,255,255,.7)}
.sb-connector{width:2px;height:24px;background:rgba(255,255,255,.15);margin:0 16px 0 31px;border-radius:2px;transition:background .25s}
.sb-step.done + .sb-connector,.sb-connector.done{background:rgba(255,255,255,.4)}
@media(max-width:859px){.sb-connector{display:none}}

/* â•â• MAIN â•â• */
.main{background:var(--white);display:flex;flex-direction:column;min-height:100dvh;position:relative}

/* step screens */
.step-screen{display:none;flex-direction:column;flex:1;animation:stepIn .4s cubic-bezier(.22,1,.36,1) both}
.step-screen.active{display:flex}
.step-screen.back-anim{animation:stepInBack .4s cubic-bezier(.22,1,.36,1) both}

/* â•â• STEP HEADER â•â• */
.step-hdr{padding:22px 28px 0;display:flex;align-items:center;gap:14px;flex-shrink:0}
@media(max-width:540px){.step-hdr{padding:14px 16px 0}}
.back-btn{
  width:38px;height:38px;border-radius:11px;border:1.5px solid var(--p-border);
  background:var(--white);display:flex;align-items:center;justify-content:center;
  color:var(--ink-2);font-size:20px;flex-shrink:0;
  transition:background .15s,border-color .15s,transform .15s;
}
.back-btn:hover{background:var(--p-lt);border-color:var(--p);color:var(--p);transform:translateX(-2px)}
.hdr-text{flex:1}
.hdr-eyebrow{font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--p);margin-bottom:2px}
.hdr-title{font-size:clamp(16px,3vw,20px);font-weight:800;color:var(--ink);letter-spacing:-.02em}

/* â•â• PROGRESS â•â• */
.prog-wrap{padding:16px 28px 0;flex-shrink:0}
@media(max-width:540px){.prog-wrap{padding:12px 16px 0}}
.prog-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.prog-label{font-size:11.5px;font-weight:600;color:var(--ink-3)}
.prog-pct{font-size:11.5px;font-weight:800;color:var(--p)}
.prog-track{height:7px;background:var(--p-lt);border-radius:99px;overflow:hidden}
.prog-fill{
  height:100%;border-radius:99px;
  background:linear-gradient(90deg,var(--p) 0%,var(--p-m) 35%,#c7d2fe 50%,var(--p-m) 65%,var(--p) 100%);
  background-size:300% 100%;
  animation:shimmer 2.5s linear infinite;
  width:0;transition:width .7s cubic-bezier(.4,0,.2,1);
}

/* â•â• FORM BODY â•â• */
.form-body{flex:1;padding:24px 28px;overflow-y:auto}
@media(max-width:540px){.form-body{padding:18px 16px}}
.form-body::-webkit-scrollbar{width:4px}
.form-body::-webkit-scrollbar-thumb{background:var(--p-lt);border-radius:99px}

/* section label */
.fsec-title{
  display:flex;align-items:center;gap:8px;
  font-size:10.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
  color:var(--ink-3);margin-bottom:16px;
}
.fsec-title::after{content:'';flex:1;height:1px;background:var(--p-lt)}

/* â•â• FIELD â•â• */
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:18px}
.field-label{font-size:12.5px;font-weight:700;color:var(--ink-2)}
.field-req{color:var(--p)}
.fi-wrap{position:relative}
.fi-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);font-size:19px;color:var(--p-m);pointer-events:none}
.fi{
  width:100%;height:50px;
  background:var(--white);border:1.5px solid var(--p-border);
  border-radius:var(--r);
  font-family:'Lexend',sans-serif;font-size:13.5px;font-weight:500;color:var(--ink);
  padding:0 14px 0 44px;outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.fi:focus{border-color:var(--p);box-shadow:0 0 0 4px rgba(67,73,207,.1)}
.fi::placeholder{color:var(--ink-3);font-weight:400}
.fi-no-icon{padding-left:14px}
.fi-ro{background:var(--p-pale);color:var(--ink-3);cursor:not-allowed;border-color:var(--p-lt)}
.fi-select{appearance:none;cursor:pointer}
.fi-sel-arrow{position:absolute;right:13px;top:50%;transform:translateY(-50%);font-size:18px;color:var(--ink-3);pointer-events:none}
.fi-ta{height:auto;padding:13px 14px;resize:none;min-height:110px;line-height:1.65}

.two-col{display:grid;grid-template-columns:1fr 1fr;gap:16px}
@media(max-width:500px){.two-col{grid-template-columns:1fr}}

/* â•â• GENDER â•â• */
.gender-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.gender-opt{position:relative;cursor:pointer}
.gender-opt input{position:absolute;opacity:0;pointer-events:none}
.gender-box{
  display:flex;flex-direction:column;align-items:center;gap:6px;
  padding:14px 10px;border-radius:var(--r-lg);
  border:2px solid var(--p-border);background:var(--white);
  transition:border-color .2s,background .2s,transform .15s;
}
.gender-box .mi{font-size:24px;color:var(--ink-3);transition:color .2s}
.gender-box .lbl{font-size:11.5px;font-weight:700;color:var(--ink-2)}
.gender-opt input:checked ~ .gender-box{border-color:var(--p);background:var(--p-lt)}
.gender-opt input:checked ~ .gender-box .mi{color:var(--p)}
.gender-opt:hover .gender-box{transform:translateY(-2px);border-color:var(--p-m)}

/* â•â• SUBJECT TAGS â•â• */
.tags-area{display:flex;flex-wrap:wrap;gap:8px;min-height:44px;align-items:flex-start}
.sub-tag{
  display:inline-flex;align-items:center;gap:5px;
  background:var(--p-lt);color:var(--p);
  border:1.5px solid var(--p-border);
  border-radius:99px;padding:5px 12px;
  font-size:12px;font-weight:700;
  animation:tagIn .25s cubic-bezier(.34,1.56,.64,1) both;
  transition:background .15s,transform .12s;
}
.sub-tag:hover{background:#e0e4ff;transform:scale(1.03)}
.sub-tag .mi{font-size:14px;transition:color .15s}
.sub-tag:hover .mi{color:#3338b0}
.add-tag-btn{
  display:inline-flex;align-items:center;gap:5px;
  background:transparent;color:var(--p);
  border:2px dashed var(--p-border);
  border-radius:99px;padding:5px 14px;
  font-size:12px;font-weight:700;
  transition:background .15s,border-color .15s,transform .12s;
}
.add-tag-btn:hover{background:var(--p-lt);border-color:var(--p);transform:scale(1.03)}

/* â•â• DAY PICKER â•â• */
.day-grid{display:flex;flex-wrap:wrap;gap:8px}
.day-btn{
  width:46px;height:46px;border-radius:var(--r);
  border:2px solid var(--p-border);background:var(--white);
  font-size:11.5px;font-weight:700;color:var(--ink-3);
  display:flex;align-items:center;justify-content:center;
  transition:all .18s;
  flex-shrink:0;
}
.day-btn:hover{border-color:var(--p-m);color:var(--p);background:var(--p-pale);transform:translateY(-2px)}
.day-btn.active{background:var(--p);border-color:var(--p);color:#fff;box-shadow:0 4px 12px rgba(67,73,207,.3)}

/* â•â• OFFICE HOURS â•â• */
.oh-wrap{
  background:var(--p-pale);border:1.5px solid var(--p-border);
  border-radius:var(--r-lg);padding:16px;
  display:flex;align-items:center;gap:12px;
  margin-bottom:14px;flex-wrap:wrap;
}
.oh-sep{font-size:12px;font-weight:600;color:var(--ink-3)}
.oh-input{
  flex:1;min-width:100px;height:44px;
  background:var(--white);border:1.5px solid var(--p-border);
  border-radius:var(--r);
  font-family:'Lexend',sans-serif;font-size:13px;font-weight:600;color:var(--ink);
  padding:0 12px;outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.oh-input:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(67,73,207,.1)}
.oh-icon{color:var(--p);font-size:22px;flex-shrink:0}

/* â•â• FOOTER â•â• */
.step-footer{
  padding:16px 28px 22px;
  border-top:1px solid var(--p-lt);
  background:rgba(255,255,255,.92);
  backdrop-filter:blur(12px);
  flex-shrink:0;
  display:flex;flex-direction:column;gap:9px;
}
@media(max-width:540px){.step-footer{padding:14px 16px 18px}}
@media(min-width:440px){
  .step-footer{flex-direction:row}
  .step-footer .btn-p{flex:1}
  .step-footer .btn-o{flex:0 0 auto;min-width:110px}
}

/* â”€â”€ PRIMARY BUTTON â”€â”€ */
.btn-p{
  display:flex;align-items:center;justify-content:center;gap:7px;
  height:50px;
  background:linear-gradient(135deg,var(--p),var(--p-m));
  color:#fff;border:none;border-radius:var(--r-lg);
  font-family:'Lexend',sans-serif;font-size:14px;font-weight:700;
  cursor:pointer;position:relative;overflow:hidden;
  box-shadow:0 4px 18px rgba(67,73,207,.32);
  transition:transform .15s,box-shadow .15s;
  text-decoration:none;
}
.btn-p::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent);
  background-size:300% auto;animation:shimmer 2.5s linear infinite;
}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 8px 26px rgba(67,73,207,.42)}
.btn-p:active{transform:scale(.97)}
.btn-p .mi{font-size:18px}

/* â”€â”€ OUTLINE BUTTON â”€â”€ */
.btn-o{
  display:flex;align-items:center;justify-content:center;gap:6px;
  height:50px;
  background:transparent;color:var(--p);
  border:1.5px solid var(--p-border);border-radius:var(--r-lg);
  font-family:'Lexend',sans-serif;font-size:13.5px;font-weight:700;
  cursor:pointer;transition:background .15s,border-color .15s,transform .15s;
}
.btn-o:hover{background:var(--p-lt);border-color:var(--p);transform:translateY(-1px)}
.btn-o:active{transform:scale(.97)}

/* â•â• SUBJECT MODAL â•â• */
.modal-overlay{
  position:fixed;inset:0;z-index:200;
  background:rgba(13,14,28,.5);backdrop-filter:blur(6px);
  display:none;align-items:center;justify-content:center;padding:20px;
  animation:fadeIn .2s ease;
}
.modal-overlay.show{display:flex}
.modal-box{
  background:var(--white);border-radius:var(--r-xl);
  padding:28px 24px;width:100%;max-width:380px;
  box-shadow:var(--sh-lg);
  animation:fadeUp .3s cubic-bezier(.22,1,.36,1) both;
}
.modal-title{font-size:18px;font-weight:800;color:var(--ink);margin-bottom:6px;letter-spacing:-.02em}
.modal-sub{font-size:12.5px;color:var(--ink-3);margin-bottom:20px}
.modal-input{
  width:100%;height:50px;
  background:var(--p-pale);border:1.5px solid var(--p-border);
  border-radius:var(--r);
  font-family:'Lexend',sans-serif;font-size:14px;font-weight:500;color:var(--ink);
  padding:0 14px;outline:none;margin-bottom:14px;
  transition:border-color .2s,box-shadow .2s;
}
.modal-input:focus{border-color:var(--p);box-shadow:0 0 0 4px rgba(67,73,207,.1)}
.modal-btns{display:flex;gap:10px}

/* â•â• SUCCESS OVERLAY â•â• */
.success-overlay{
  position:fixed;inset:0;z-index:300;
  background:rgba(255,255,255,.96);
  display:none;flex-direction:column;align-items:center;justify-content:center;gap:18px;
  animation:fadeIn .3s ease;
}
.success-overlay.show{display:flex}
.success-ring{
  width:96px;height:96px;
  background:linear-gradient(135deg,var(--p),var(--p-m));
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:50px;
  box-shadow:0 12px 36px rgba(67,73,207,.4);
  animation:checkPop .5s .1s cubic-bezier(.34,1.56,.64,1) both;
}
.success-title{font-size:24px;font-weight:800;color:var(--ink);letter-spacing:-.025em;animation:fadeUp .4s .25s ease both}
.success-sub{font-size:13.5px;color:var(--ink-3);animation:fadeUp .4s .35s ease both}
.success-dots{display:flex;gap:5px;margin-top:4px;animation:fadeUp .4s .45s ease both}
.sdot{width:7px;height:7px;background:var(--p);border-radius:50%}
.sdot:nth-child(1){animation:pulse .9s 0s ease-in-out infinite}
.sdot:nth-child(2){animation:pulse .9s .15s ease-in-out infinite}
.sdot:nth-child(3){animation:pulse .9s .3s ease-in-out infinite}

/* â•â• RESPONSIVE â•â• */
@media(max-width:859px){.shell{grid-template-columns:1fr}}
@media(max-width:400px){.gender-grid{grid-template-columns:repeat(3,1fr)}}
</style>
</head>
<body>

<div class="shell">

  <!-- â•â• SIDEBAR â•â• -->
  <aside class="sidebar">
    <div class="sb-dots"></div>
    <div class="sb-orb sb-orb-1"></div>
    <div class="sb-orb sb-orb-2"></div>

    <div class="sb-logo">
      <div class="sb-logo-icon"><span class="mi">school</span></div>
      <div class="sb-brand">CollegeConnct<small>Teacher Onboarding</small></div>
    </div>

    <nav class="sb-steps" id="sbSteps">
      <div class="sb-step active" data-step="1">
        <div class="sb-step-num" id="sbn1">1</div>
        <div class="sb-step-info">
          <div class="sb-step-title">Personal Details</div>
          <div class="sb-step-sub">Name, DOB, contact</div>
        </div>
      </div>
      <div class="sb-connector" id="sc1"></div>

      <div class="sb-step" data-step="2">
        <div class="sb-step-num" id="sbn2">2</div>
        <div class="sb-step-info">
          <div class="sb-step-title">Professional Info</div>
          <div class="sb-step-sub">Department, designation</div>
        </div>
      </div>
      <div class="sb-connector" id="sc2"></div>

      <div class="sb-step" data-step="3">
        <div class="sb-step-num" id="sbn3">3</div>
        <div class="sb-step-info">
          <div class="sb-step-title">Teaching Preferences</div>
          <div class="sb-step-sub">Bio, subjects, hours</div>
        </div>
      </div>
    </nav>
  </aside>

  <!-- â•â• MAIN â•â• -->
  <main class="main">
  <form id="teacherProfileForm" action="teacher_complete_profile_process.php" method="POST">

    <!-- â•â•â•â•â•â• STEP 1 â•â•â•â•â•â• -->
    <div class="step-screen active" id="step1">
      <div class="step-hdr" style="animation:fadeUp .5s .06s ease both">
        <button type="button" class="back-btn" onclick="history.back()" title="Go back">
          <span class="mi">arrow_back</span>
        </button>
        <div class="hdr-text">
          <div class="hdr-eyebrow">Step 1 of 3</div>
          <div class="hdr-title">Let's get to know you</div>
        </div>
      </div>

      <div class="prog-wrap" style="animation:fadeUp .5s .12s ease both">
        <div class="prog-top"><span class="prog-label">Personal Details</span><span class="prog-pct">33%</span></div>
        <div class="prog-track"><div class="prog-fill" style="width:33%"></div></div>
      </div>

      <div class="form-body" style="animation:fadeUp .5s .18s ease both">
        <div class="fsec-title"><span class="mi" style="font-size:14px;color:var(--p)">person</span> Basic Information</div>

        <div class="field">
          <label class="field-label">Full Name</label>
          <div class="fi-wrap">
            <span class="mi fi-icon">badge</span>
            <input class="fi fi-ro" name="full_name" type="text" readonly value="<?= htmlspecialchars($teacher['name'] ?? '') ?>"/>
          </div>
        </div>

        <div class="field">
          <label class="field-label">Date of Birth <span class="field-req">*</span></label>
          <div class="fi-wrap">
            <span class="mi fi-icon">calendar_month</span>
            <input class="fi" id="dob" name="dob" type="date" value="<?= htmlspecialchars($teacher['dob'] ?? '') ?>"/>
          </div>
        </div>

        <div class="field">
          <div class="field-label">Gender <span class="field-req">*</span></div>
          <div class="gender-grid">
            <?php foreach([['Female','female'],['Male','male'],['Other','more_horiz']] as [$v,$ic]): ?>
            <label class="gender-opt">
              <input type="radio" name="gender" value="<?=$v?>" <?= ($teacher['gender']??'Female')===$v?'checked':'' ?>/>
              <div class="gender-box">
                <span class="mi"><?=$ic?></span>
                <span class="lbl"><?=$v?></span>
              </div>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field">
          <label class="field-label">Contact Number</label>
          <div class="fi-wrap">
            <span class="mi fi-icon">phone</span>
            <input class="fi" name="contact_number" type="tel" placeholder="+91 98765 43210" value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>"/>
          </div>
        </div>
      </div>

      <div class="step-footer" style="animation:fadeUp .5s .22s ease both">
        <button type="button" class="btn-p" onclick="goNext(1)">
          Continue to Step 2
          <span class="mi">arrow_forward</span>
        </button>
      </div>
    </div>

    <!-- â•â•â•â•â•â• STEP 2 â•â•â•â•â•â• -->
    <div class="step-screen" id="step2">
      <div class="step-hdr">
        <button type="button" class="back-btn" onclick="goPrev(2)" title="Back">
          <span class="mi">arrow_back</span>
        </button>
        <div class="hdr-text">
          <div class="hdr-eyebrow">Step 2 of 3</div>
          <div class="hdr-title">Professional Details</div>
        </div>
      </div>

      <div class="prog-wrap">
        <div class="prog-top"><span class="prog-label">Department & Designation</span><span class="prog-pct">66%</span></div>
        <div class="prog-track"><div class="prog-fill" style="width:66%"></div></div>
      </div>

      <div class="form-body">
        <div class="fsec-title"><span class="mi" style="font-size:14px;color:var(--p)">apartment</span> Institution Details</div>

        <div class="field">
          <label class="field-label">Staff ID</label>
          <div class="fi-wrap">
            <span class="mi fi-icon">badge</span>
            <input class="fi fi-ro" type="text" readonly value="<?= $staffId ?>"/>
          </div>
        </div>

        <div class="field">
          <label class="field-label">Department <span class="field-req">*</span></label>
          <div class="fi-wrap">
            <span class="mi fi-icon">school</span>
            <select class="fi fi-select" name="department">
              <option value="">Select your department</option>
              <?php foreach(['CO'=>'Computer','IT'=>'Information Technology','EE'=>'Electrical','ME'=>'Mechanical','AE'=>'Automobile'] as $v=>$l): ?>
              <option value="<?=$v?>" <?= ($teacher['department']??'')===$v?'selected':'' ?>><?=$l?></option>
              <?php endforeach; ?>
            </select>
            <span class="mi fi-sel-arrow">expand_more</span>
          </div>
        </div>

        <div class="two-col">
          <div class="field">
            <label class="field-label">Designation <span class="field-req">*</span></label>
            <div class="fi-wrap">
              <span class="mi fi-icon">stars</span>
              <input class="fi" name="designation" type="text" placeholder="e.g. Asst. Professor" value="<?= htmlspecialchars($teacher['designation'] ?? '') ?>"/>
            </div>
          </div>
          <div class="field">
            <label class="field-label">Qualification</label>
            <div class="fi-wrap">
              <span class="mi fi-icon">workspace_premium</span>
              <input class="fi" name="qualification" type="text" placeholder="e.g. M.Tech, PhD" value="<?= htmlspecialchars($teacher['qualification'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <div class="field">
          <label class="field-label">Years of Experience</label>
          <div class="fi-wrap">
            <span class="mi fi-icon">history_edu</span>
            <input class="fi" name="experience" type="number" min="0" placeholder="0" value="<?= htmlspecialchars($teacher['experience'] ?? '') ?>"/>
          </div>
        </div>
      </div>

      <div class="step-footer">
        <button type="button" class="btn-p" onclick="goNext(2)">
          Continue to Step 3
          <span class="mi">arrow_forward</span>
        </button>
        <button type="button" class="btn-o" onclick="goPrev(2)">
          <span class="mi" style="font-size:16px">arrow_back</span> Back
        </button>
      </div>
    </div>

    <!-- â•â•â•â•â•â• STEP 3 â•â•â•â•â•â• -->
    <div class="step-screen" id="step3">
      <div class="step-hdr">
        <button type="button" class="back-btn" onclick="goPrev(3)" title="Back">
          <span class="mi">arrow_back</span>
        </button>
        <div class="hdr-text">
          <div class="hdr-eyebrow">Step 3 of 3 Â· Final</div>
          <div class="hdr-title">Teaching Preferences</div>
        </div>
      </div>

      <div class="prog-wrap">
        <div class="prog-top"><span class="prog-label">Almost done!</span><span class="prog-pct">99%</span></div>
        <div class="prog-track"><div class="prog-fill" style="width:99%"></div></div>
      </div>

      <div class="form-body">
        <!-- BIO -->
        <div class="fsec-title"><span class="mi" style="font-size:14px;color:var(--p)">edit_note</span> About You</div>
        <div class="field">
          <label class="field-label">Short Bio</label>
          <textarea class="fi fi-ta fi-no-icon" name="bio" placeholder="Tell students about your background, teaching style, and interestsâ€¦"><?= htmlspecialchars($teacher['bio'] ?? '') ?></textarea>
        </div>

        <!-- SUBJECTS -->
        <div class="fsec-title"><span class="mi" style="font-size:14px;color:var(--p)">menu_book</span> Primary Subjects</div>
        <div style="margin-bottom:20px">
          <div class="tags-area" id="tagsArea"></div>
          <input type="hidden" name="primary_subjects" id="primarySubjectsInput" value="<?= htmlspecialchars($teacher['primary_subjects'] ?? '') ?>"/>
        </div>

        <!-- OFFICE HOURS -->
        <div class="fsec-title"><span class="mi" style="font-size:14px;color:var(--p)">schedule</span> Office Hours</div>

        <div class="oh-wrap">
          <span class="mi oh-icon">schedule</span>
          <input class="oh-input" name="office_start" type="time" value="<?= htmlspecialchars($teacher['office_start'] ?? '09:00') ?>"/>
          <span class="oh-sep">to</span>
          <input class="oh-input" name="office_end" type="time" value="<?= htmlspecialchars($teacher['office_end'] ?? '11:00') ?>"/>
        </div>

        <div class="field">
          <div class="field-label" style="margin-bottom:10px">Available Days</div>
          <div class="day-grid" id="dayGrid">
            <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
            <button type="button" class="day-btn" data-day="<?=$d?>"><?=$d?></button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="office_days" id="officeDaysInput" value="<?= htmlspecialchars($teacher['office_days'] ?? 'Mon,Wed,Fri') ?>"/>
        </div>

        <p style="font-size:11.5px;color:var(--ink-3);text-align:center;margin-top:8px">By finishing setup, you agree to the faculty community guidelines.</p>
      </div>

      <div class="step-footer">
        <button type="submit" class="btn-p" onclick="showSuccess()">
          <span class="mi" style="font-size:18px">rocket_launch</span>
          Finish Setup
        </button>
        <button type="button" class="btn-o" onclick="goPrev(3)">
          <span class="mi" style="font-size:16px">arrow_back</span> Back
        </button>
      </div>
    </div>

  </form>
  </main>
</div>

<!-- â•â• SUBJECT MODAL â•â• -->
<div class="modal-overlay" id="subModal">
  <div class="modal-box">
    <div class="modal-title">Add Subject</div>
    <div class="modal-sub">Type the subject name and press Add.</div>
    <input class="modal-input" id="subInput" type="text" placeholder="e.g. Data Structures" maxlength="40"/>
    <div class="modal-btns">
      <button type="button" class="btn-p" style="flex:1;height:44px;font-size:13px" onclick="confirmSubject()">
        <span class="mi" style="font-size:16px">add</span> Add
      </button>
      <button type="button" class="btn-o" style="flex:0 0 80px;height:44px;font-size:13px" onclick="closeModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- â•â• SUCCESS OVERLAY â•â• -->
<div class="success-overlay" id="successOverlay">
  <div class="success-ring"><span class="mi">check</span></div>
  <div class="success-title">Setup Complete!</div>
  <div class="success-sub">Redirecting to your dashboardâ€¦</div>
  <div class="success-dots"><div class="sdot"></div><div class="sdot"></div><div class="sdot"></div></div>
</div>

<script>
/* â”€â”€ STEP NAVIGATION â”€â”€ */
let cur = 1, goingBack = false;

function showStep(n) {
  const screens = document.querySelectorAll('.step-screen');
  screens.forEach(s => { s.classList.remove('active','back-anim'); });
  const next = document.getElementById('step' + n);
  if (goingBack) next.classList.add('back-anim');
  next.classList.add('active');
  window.scrollTo({top:0,behavior:'smooth'});
  cur = n;
  updateSidebar(n);
  goingBack = false;
}

function goNext(step) {
  if (step === 1) {
    const dob = document.getElementById('dob').value;
    if (!dob) { shakeField('dob'); return; }
  }
  if (step < 3) showStep(step + 1);
}

function goPrev(step) {
  if (step > 1) { goingBack = true; showStep(step - 1); }
}

function updateSidebar(n) {
  document.querySelectorAll('.sb-step').forEach(s => {
    const sn = parseInt(s.dataset.step);
    s.classList.remove('active','done');
    const numEl = s.querySelector('.sb-step-num');
    if (sn === n) {
      s.classList.add('active');
      numEl.innerHTML = sn;
      numEl.style.cssText = '';
    } else if (sn < n) {
      s.classList.add('done');
      numEl.innerHTML = '<span class="mi" style="font-size:17px;color:#fff">check</span>';
      numEl.style.background = 'rgba(255,255,255,.3)';
      numEl.style.borderColor = 'rgba(255,255,255,.6)';
    } else {
      numEl.innerHTML = sn;
      numEl.style.cssText = '';
    }
    const conn = document.getElementById('sc' + sn);
    if (conn) conn.style.background = sn < n ? 'rgba(255,255,255,.45)' : '';
  });
}

/* â”€â”€ FIELD SHAKE â”€â”€ */
function shakeField(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.style.borderColor = '#ef4444';
  el.style.boxShadow = '0 0 0 4px rgba(239,68,68,.1)';
  el.animate([{transform:'translateX(-6px)'},{transform:'translateX(6px)'},{transform:'translateX(-4px)'},{transform:'translateX(4px)'},{transform:'translateX(0)'}],{duration:360,easing:'ease-in-out'});
  el.focus();
  setTimeout(()=>{ el.style.borderColor=''; el.style.boxShadow=''; }, 1600);
}

/* â”€â”€ DAYS â”€â”€ */
const officeDaysInput = document.getElementById('officeDaysInput');
let savedDays = officeDaysInput.value ? officeDaysInput.value.split(',') : [];

document.querySelectorAll('.day-btn').forEach(btn => {
  if (savedDays.includes(btn.dataset.day)) btn.classList.add('active');
  btn.addEventListener('click', function() {
    this.classList.toggle('active');
    officeDaysInput.value = [...document.querySelectorAll('.day-btn.active')].map(b=>b.dataset.day).join(',');
  });
});

/* â”€â”€ SUBJECTS â”€â”€ */
const tagsArea = document.getElementById('tagsArea');
const subInput = document.getElementById('primarySubjectsInput');
const modal = document.getElementById('subModal');
const modalInput = document.getElementById('subInput');

function renderSubjects() {
  tagsArea.innerHTML = '';
  const saved = subInput.value.trim();
  if (saved) saved.split(',').forEach(s => { if(s.trim()) addTagEl(s.trim()); });
  addAddBtn();
}

function addTagEl(name) {
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.className = 'sub-tag';
  btn.dataset.subject = name;
  btn.innerHTML = `${name} <span class="mi">close</span>`;
  btn.addEventListener('click', () => { btn.remove(); syncSubjects(); });
  tagsArea.appendChild(btn);
}

function addAddBtn() {
  const b = document.createElement('button');
  b.type = 'button';
  b.className = 'add-tag-btn';
  b.id = 'addTagBtn';
  b.innerHTML = '<span class="mi" style="font-size:16px">add</span> Add Subject';
  b.addEventListener('click', openModal);
  tagsArea.appendChild(b);
}

function syncSubjects() {
  subInput.value = [...tagsArea.querySelectorAll('.sub-tag')].map(t=>t.dataset.subject).join(',');
}

function openModal() {
  modalInput.value = '';
  modal.classList.add('show');
  setTimeout(() => modalInput.focus(), 120);
}

function closeModal() { modal.classList.remove('show'); }

function confirmSubject() {
  const v = modalInput.value.trim();
  if (!v) { modalInput.focus(); return; }
  const addBtn = document.getElementById('addTagBtn');
  if (addBtn) tagsArea.removeChild(addBtn);
  addTagEl(v);
  addAddBtn();
  syncSubjects();
  closeModal();
}

modalInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); confirmSubject(); } });
modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });

renderSubjects();

/* â”€â”€ SUCCESS â”€â”€ */
function showSuccess() {
  document.getElementById('successOverlay').classList.add('show');
}

/* â”€â”€ RIPPLE â”€â”€ */
document.querySelectorAll('.btn-p').forEach(btn => {
  btn.addEventListener('click', function(e) {
    const r = btn.getBoundingClientRect();
    const sz = Math.max(r.width,r.height)*2;
    const rpl = document.createElement('span');
    rpl.style.cssText=`position:absolute;border-radius:50%;pointer-events:none;width:${sz}px;height:${sz}px;left:${e.clientX-r.left-sz/2}px;top:${e.clientY-r.top-sz/2}px;background:rgba(255,255,255,.2);transform:scale(0);animation:ripple .5s linear forwards;`;
    btn.appendChild(rpl);
    setTimeout(()=>rpl.remove(),520);
  });
});
</script>
</body>
</html>