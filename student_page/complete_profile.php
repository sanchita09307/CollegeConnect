<?php
// â??â?? Session settings â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??
ini_set('session.gc_maxlifetime',  7200);
ini_set('session.cookie_lifetime', 7200);
// â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??â??

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/activity_helper.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit(); }

$user_id = (int) $_SESSION['user_id'];
$getStudent = mysqli_query($conn, "SELECT * FROM students WHERE id='$user_id' LIMIT 1");
$student = mysqli_fetch_assoc($getStudent);
$site_name = htmlspecialchars($settings['site_name'] ?? 'CollegeConnct');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name  = mysqli_real_escape_string($conn, $_POST['full_name'] ?? '');
    $dob        = mysqli_real_escape_string($conn, $_POST['dob'] ?? '');
    $gender     = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $email      = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $phone      = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $department = mysqli_real_escape_string($conn, $_POST['department'] ?? '');
    $batch_year = mysqli_real_escape_string($conn, $_POST['batch_year'] ?? '');
    $semester   = mysqli_real_escape_string($conn, $_POST['semester'] ?? '');
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id'] ?? '');
    $bio        = mysqli_real_escape_string($conn, $_POST['bio'] ?? '');
    $interests  = mysqli_real_escape_string($conn, $_POST['selected_interests'] ?? '');
    $linkedin   = mysqli_real_escape_string($conn, $_POST['linkedin'] ?? '');
    $github     = mysqli_real_escape_string($conn, $_POST['github'] ?? '');
    $portfolio  = mysqli_real_escape_string($conn, $_POST['portfolio'] ?? '');
    $profile_photo_name = $student['profile_photo'] ?? '';

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $upload_dir = __DIR__ . '/../uploads/profile_photos/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_tmp  = $_FILES['profile_photo']['tmp_name'];
        $file_name = time() . '_' . basename($_FILES['profile_photo']['name']);
        if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) $profile_photo_name = $file_name;
    }

    $stmt = $conn->prepare("UPDATE students SET full_name=?,dob=?,gender=?,email=?,phone=?,department=?,batch_year=?,semester=?,student_roll_no=?,bio=?,interests=?,linkedin=?,github=?,portfolio=?,profile_photo=?,profile_completed=1 WHERE id=?");
    $stmt->bind_param("sssssssssssssssi",$full_name,$dob,$gender,$email,$phone,$department,$batch_year,$semester,$student_id,$bio,$interests,$linkedin,$github,$portfolio,$profile_photo_name,$user_id);
    if (!$stmt->execute()) die('Database Error: ' . $stmt->error);
    logActivity($conn, $user_id, 'student', 'profile_update', 'Student completed profile');
    header("Location: student_dashboard.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Complete Your Profile â?? <?= $site_name ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0" rel="stylesheet"/>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased}
body{font-family:'Sora',sans-serif;background:#f0f4ff;color:#0c1a3a;min-height:100dvh;overflow-x:hidden}

:root{
  --blue:#1a6bff;--blue-dark:#0050e6;--blue-mid:#4d8dff;--blue-light:#e8f0ff;--blue-xlight:#f0f4ff;
  --ink:#0c1a3a;--ink-2:#3d5273;--ink-3:#7a8ba8;
  --white:#fff;--border:rgba(26,107,255,.15);
  --r:14px;--r-lg:20px;--r-xl:28px;
  --shadow:0 4px 24px rgba(26,107,255,.1);--shadow-md:0 8px 40px rgba(26,107,255,.16);
}

/* ANIMATIONS */
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes scaleIn{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:scale(1)}}
@keyframes slideRight{from{width:0}to{width:var(--w)}}
@keyframes shimmer{0%{background-position:-300% center}100%{background-position:300% center}}
@keyframes ripple{0%{transform:scale(0);opacity:.5}100%{transform:scale(3.5);opacity:0}}
@keyframes checkPop{0%{transform:scale(0) rotate(-15deg)}70%{transform:scale(1.15) rotate(3deg)}100%{transform:scale(1) rotate(0deg)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.95)}}
@keyframes stepIn{from{opacity:0;transform:translateX(40px)}to{opacity:1;transform:translateX(0)}}
@keyframes stepOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(-40px)}}
@keyframes dotBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-4px)}}

/* LAYOUT */
.shell{
  min-height:100dvh;
  display:grid;
  grid-template-columns:1fr;
}
@media(min-width:900px){
  .shell{grid-template-columns:320px 1fr}
}

/* SIDEBAR */
.sidebar{
  background:var(--blue);
  padding:40px 32px;
  display:flex;flex-direction:column;
  position:relative;overflow:hidden;
}
@media(max-width:899px){.sidebar{padding:24px 20px;flex-direction:row;align-items:center;gap:16px}}
.sidebar-blob{
  position:absolute;width:300px;height:300px;
  background:rgba(255,255,255,.08);border-radius:50%;
  right:-80px;bottom:-80px;
  pointer-events:none;
  animation:float 8s ease-in-out infinite;
}
.sidebar-blob2{
  position:absolute;width:180px;height:180px;
  background:rgba(255,255,255,.05);border-radius:50%;
  left:-40px;top:30%;
  pointer-events:none;
  animation:float 12s ease-in-out infinite reverse;
}
.sidebar-dots{
  position:absolute;inset:0;
  background-image:radial-gradient(rgba(255,255,255,.12) 1px,transparent 1px);
  background-size:22px 22px;
  pointer-events:none;
}
.sidebar-logo{
  display:flex;align-items:center;gap:10px;
  margin-bottom:48px;
  position:relative;z-index:1;
  animation:fadeIn .5s ease both;
}
@media(max-width:899px){.sidebar-logo{margin-bottom:0}}
.logo-icon{
  width:40px;height:40px;
  background:rgba(255,255,255,.2);
  border:1.5px solid rgba(255,255,255,.3);
  border-radius:11px;
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:22px;
}
.logo-text{font-size:16px;font-weight:800;color:#fff;letter-spacing:-.02em}
.logo-text small{display:block;font-size:10px;font-weight:400;opacity:.65;letter-spacing:.03em}

/* SIDEBAR STEPS */
.sidebar-steps{
  display:flex;flex-direction:column;gap:6px;
  flex:1;position:relative;z-index:1;
}
@media(max-width:899px){.sidebar-steps{flex-direction:row;flex:1;justify-content:flex-end}}
.s-step{
  display:flex;align-items:center;gap:14px;
  padding:14px 16px;border-radius:var(--r-lg);
  cursor:default;
  transition:background .25s;
}
.s-step.active{background:rgba(255,255,255,.15)}
.s-step-num{
  width:36px;height:36px;flex-shrink:0;
  border-radius:50%;
  border:2px solid rgba(255,255,255,.4);
  display:flex;align-items:center;justify-content:center;
  font-family:'DM Mono',monospace;
  font-size:13px;font-weight:500;color:rgba(255,255,255,.7);
  transition:background .25s,border-color .25s,color .25s;
}
.s-step.active .s-step-num{background:#fff;border-color:#fff;color:var(--blue);font-weight:700}
.s-step.done .s-step-num{background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.5)}
.s-step-info{flex:1}
@media(max-width:899px){.s-step-info{display:none}}
.s-step-title{font-size:13px;font-weight:700;color:rgba(255,255,255,.9);line-height:1.2}
.s-step.active .s-step-title{color:#fff}
.s-step-sub{font-size:11px;color:rgba(255,255,255,.5);margin-top:2px}
.s-step.active .s-step-sub{color:rgba(255,255,255,.7)}
.s-step-connector{
  width:2px;height:28px;
  background:rgba(255,255,255,.15);
  margin:0 17px 0 33px;
  border-radius:2px;
}
.s-step.done .s-step-connector{background:rgba(255,255,255,.35)}
@media(max-width:899px){.s-step-connector{display:none}}
.check-anim{animation:checkPop .35s cubic-bezier(.34,1.56,.64,1) both}
.mi{font-family:'Material Symbols Rounded';line-height:1;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;user-select:none}

/* MAIN CONTENT */
.main{
  background:var(--white);
  display:flex;flex-direction:column;
  min-height:100dvh;
  position:relative;
}

/* STEP SCREENS */
.step-screen{
  display:none;flex-direction:column;
  flex:1;min-height:0;
  animation:stepIn .4s cubic-bezier(.22,1,.36,1) both;
}
.step-screen.active{display:flex}
.step-screen.exit{animation:stepOut .3s ease forwards}

/* HEADER BAR */
.step-header{
  padding:24px 32px 0;
  display:flex;align-items:center;gap:16px;
  flex-shrink:0;
}
@media(max-width:600px){.step-header{padding:16px 18px 0}}
.back-btn{
  width:40px;height:40px;
  border-radius:12px;
  border:1.5px solid var(--border);
  background:#fff;
  display:flex;align-items:center;justify-content:center;
  cursor:pointer;color:var(--ink-2);font-size:20px;
  transition:background .15s,border-color .15s,transform .15s;
  flex-shrink:0;
}
.back-btn:hover{background:var(--blue-light);border-color:var(--blue);color:var(--blue);transform:translateX(-2px)}
.step-header-text{flex:1}
.step-header-label{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--blue);margin-bottom:2px}
.step-header-title{font-size:18px;font-weight:800;color:var(--ink);letter-spacing:-.02em}

/* PROGRESS */
.progress-wrap{padding:20px 32px 0;flex-shrink:0}
@media(max-width:600px){.progress-wrap{padding:14px 18px 0}}
.progress-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
.progress-label{font-size:12px;font-weight:600;color:var(--ink-3)}
.progress-pct{font-family:'DM Mono',monospace;font-size:12px;font-weight:700;color:var(--blue)}
.progress-track{height:6px;background:var(--blue-light);border-radius:99px;overflow:hidden}
.progress-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,var(--blue-dark),var(--blue-mid));width:0;transition:width .6s cubic-bezier(.4,0,.2,1)}

/* FORM BODY */
.form-body{flex:1;padding:28px 32px;overflow-y:auto}
@media(max-width:600px){.form-body{padding:20px 18px}}

/* SECTION BLOCKS */
.form-section{margin-bottom:28px}
.form-section-title{
  font-size:11px;font-weight:800;
  letter-spacing:.1em;text-transform:uppercase;
  color:var(--ink-3);
  margin-bottom:16px;
  display:flex;align-items:center;gap:8px;
}
.form-section-title::after{content:'';flex:1;height:1px;background:var(--blue-light)}

/* FIELD */
.field{display:flex;flex-direction:column;gap:6px;margin-bottom:16px}
.field-label{font-size:13px;font-weight:700;color:var(--ink-2)}
.field-input-wrap{position:relative}
.field-icon{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  font-size:20px;color:var(--blue-mid);pointer-events:none;
}
.fi{
  width:100%;height:52px;
  background:#fff;
  border:1.5px solid var(--border);
  border-radius:var(--r);
  font-family:'Sora',sans-serif;
  font-size:14px;font-weight:500;
  color:var(--ink);
  padding:0 16px 0 46px;
  outline:none;
  transition:border-color .2s,box-shadow .2s,background .2s;
}
.fi:focus{border-color:var(--blue);box-shadow:0 0 0 4px rgba(26,107,255,.1);background:#fff}
.fi::placeholder{color:var(--ink-3);font-weight:400}
.fi-no-icon{padding-left:16px}
.fi-ta{height:auto;padding:14px 16px;resize:none;min-height:96px;line-height:1.65}
.fi-select{appearance:none;cursor:pointer}
.select-arrow{
  position:absolute;right:14px;top:50%;transform:translateY(-50%);
  font-size:20px;color:var(--ink-3);pointer-events:none;
}

/* GENDER GRID */
.gender-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.gender-opt{position:relative;cursor:pointer}
.gender-opt input{position:absolute;opacity:0;pointer-events:none}
.gender-box{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:6px;padding:16px 10px;
  border-radius:var(--r-lg);
  border:2px solid var(--border);
  background:#fff;
  transition:border-color .2s,background .2s,transform .15s;
}
.gender-box .mi{font-size:26px;color:var(--ink-3);transition:color .2s}
.gender-box span.lbl{font-size:12px;font-weight:700;color:var(--ink-2)}
.gender-opt input:checked ~ .gender-box{
  border-color:var(--blue);
  background:var(--blue-light);
}
.gender-opt input:checked ~ .gender-box .mi{color:var(--blue)}
.gender-opt:hover .gender-box{transform:translateY(-2px);border-color:var(--blue-mid)}

/* PHOTO UPLOAD */
.photo-wrap{
  display:flex;flex-direction:column;align-items:center;gap:16px;
  padding:28px;
  background:var(--blue-xlight);
  border:2px dashed var(--border);
  border-radius:var(--r-xl);
  margin-bottom:20px;
  transition:border-color .2s,background .2s;
  cursor:pointer;
  position:relative;
}
.photo-wrap:hover{border-color:var(--blue);background:var(--blue-light)}
.photo-wrap input{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.photo-ring{
  width:96px;height:96px;
  border-radius:50%;
  background:var(--blue-light);
  border:3px solid rgba(26,107,255,.25);
  overflow:hidden;
  display:flex;align-items:center;justify-content:center;
  position:relative;
}
.photo-ring img{width:100%;height:100%;object-fit:cover;display:none}
.photo-ring .mi{font-size:40px;color:var(--blue-mid)}
.photo-ring.has-photo img{display:block}
.photo-ring.has-photo .mi{display:none}
.photo-text{text-align:center}
.photo-text strong{display:block;font-size:14px;font-weight:700;color:var(--ink);margin-bottom:3px}
.photo-text span{font-size:12px;color:var(--ink-3)}
.photo-btn{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--blue);color:#fff;
  border:none;border-radius:10px;
  padding:8px 18px;
  font-family:'Sora',sans-serif;font-size:12px;font-weight:700;
  pointer-events:none;
}

/* TWO-COL GRID */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:480px){.two-col{grid-template-columns:1fr}}

/* SOCIAL INPUTS */
.social-field{
  display:flex;align-items:center;
  gap:0;
  border:1.5px solid var(--border);
  border-radius:var(--r);
  overflow:hidden;
  background:#fff;
  transition:border-color .2s,box-shadow .2s;
  margin-bottom:12px;
}
.social-field:focus-within{border-color:var(--blue);box-shadow:0 0 0 4px rgba(26,107,255,.1)}
.social-prefix{
  padding:0 14px;height:52px;
  background:var(--blue-light);
  display:flex;align-items:center;justify-content:center;
  font-size:20px;color:var(--blue);
  flex-shrink:0;
}
.social-field input{
  flex:1;height:52px;border:none;outline:none;
  background:transparent;
  font-family:'Sora',sans-serif;font-size:13px;font-weight:500;
  color:var(--ink);padding:0 14px;
}
.social-field input::placeholder{color:var(--ink-3);font-weight:400}

/* INTERESTS */
.interests-grid{display:flex;flex-wrap:wrap;gap:9px;margin-top:4px}
.int-chip{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:99px;
  border:1.5px solid var(--border);
  background:#fff;
  font-size:13px;font-weight:600;color:var(--ink-2);
  cursor:pointer;
  transition:background .2s,border-color .2s,color .2s,transform .15s;
  position:relative;overflow:hidden;
}
.int-chip:hover{transform:translateY(-2px);border-color:var(--blue-mid);color:var(--blue)}
.int-chip.sel{
  background:var(--blue);border-color:var(--blue);color:#fff;
}
.int-chip.sel:hover{background:var(--blue-dark)}
.int-chip .mi{font-size:15px;display:none}
.int-chip.sel .mi{display:inline-flex;animation:checkPop .3s cubic-bezier(.34,1.56,.64,1) both}

/* STICKY FOOTER */
.form-footer{
  padding:20px 32px 24px;
  border-top:1px solid var(--blue-light);
  background:#fff;
  flex-shrink:0;
  display:flex;flex-direction:column;gap:10px;
}
@media(max-width:600px){.form-footer{padding:16px 18px 20px}}
.btn-next{
  display:flex;align-items:center;justify-content:center;gap:8px;
  width:100%;height:54px;
  background:var(--blue);color:#fff;
  border:none;border-radius:var(--r-lg);
  font-family:'Sora',sans-serif;font-size:15px;font-weight:700;
  cursor:pointer;
  position:relative;overflow:hidden;
  box-shadow:0 6px 24px rgba(26,107,255,.3);
  transition:transform .15s,box-shadow .15s;
}
.btn-next::before{
  content:'';position:absolute;inset:0;
  background:linear-gradient(90deg,transparent,rgba(255,255,255,.18),transparent);
  background-size:300% auto;
  animation:shimmer 2.5s linear infinite;
}
.btn-next:hover{transform:translateY(-2px);box-shadow:0 10px 32px rgba(26,107,255,.4)}
.btn-next:active{transform:scale(.97)}
.btn-next .mi{font-size:20px}
.btn-skip{
  display:flex;align-items:center;justify-content:center;
  width:100%;height:44px;
  background:transparent;color:var(--ink-3);
  border:none;border-radius:var(--r);
  font-family:'Sora',sans-serif;font-size:13px;font-weight:600;
  cursor:pointer;
  transition:color .15s,background .15s;
}
.btn-skip:hover{color:var(--blue);background:var(--blue-xlight)}

/* SUCCESS OVERLAY */
.success-overlay{
  position:fixed;inset:0;z-index:999;
  background:rgba(255,255,255,.95);
  display:none;flex-direction:column;align-items:center;justify-content:center;
  gap:20px;
  animation:fadeIn .35s ease;
}
.success-overlay.show{display:flex}
.success-ring{
  width:100px;height:100px;
  background:var(--blue);
  border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  color:#fff;font-size:52px;
  animation:scaleIn .4s .1s cubic-bezier(.34,1.56,.64,1) both;
  box-shadow:0 12px 40px rgba(26,107,255,.4);
}
.success-title{font-size:26px;font-weight:800;color:var(--ink);letter-spacing:-.025em;animation:fadeUp .4s .25s ease both}
.success-sub{font-size:14px;color:var(--ink-3);animation:fadeUp .4s .35s ease both}
.success-dots{display:flex;gap:6px;margin-top:4px;animation:fadeUp .4s .45s ease both}
.success-dot{width:8px;height:8px;background:var(--blue);border-radius:50%}
.success-dot:nth-child(2){animation:dotBounce .8s .1s ease infinite}
.success-dot:nth-child(3){animation:dotBounce .8s .2s ease infinite}
.success-dot:nth-child(4){animation:dotBounce .8s .3s ease infinite}

/* SCROLLBAR */
.form-body::-webkit-scrollbar{width:4px}
.form-body::-webkit-scrollbar-track{background:transparent}
.form-body::-webkit-scrollbar-thumb{background:var(--blue-light);border-radius:99px}
</style>
</head>
<body>

<div class="shell">

  <!-- â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ SIDEBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <aside class="sidebar">
    <div class="sidebar-dots"></div>
    <div class="sidebar-blob"></div>
    <div class="sidebar-blob2"></div>

    <div class="sidebar-logo">
      <div class="logo-icon"><span class="mi">school</span></div>
      <div class="logo-text">CollegeConnct<small>Student Onboarding</small></div>
    </div>

    <nav class="sidebar-steps" id="sidebarSteps">
      <div class="s-step active" data-step="1">
        <div class="s-step-num" id="sn1">1</div>
        <div class="s-step-info">
          <div class="s-step-title">Personal Details</div>
          <div class="s-step-sub">Name, DOB, contact info</div>
        </div>
      </div>
      <div class="s-step-connector" id="sc1"></div>

      <div class="s-step" data-step="2">
        <div class="s-step-num" id="sn2">2</div>
        <div class="s-step-info">
          <div class="s-step-title">Academic Info</div>
          <div class="s-step-sub">Department, semester, photo</div>
        </div>
      </div>
      <div class="s-step-connector" id="sc2"></div>

      <div class="s-step" data-step="3">
        <div class="s-step-num" id="sn3">3</div>
        <div class="s-step-info">
          <div class="s-step-title">Interests & Socials</div>
          <div class="s-step-sub">Clubs, links, final submit</div>
        </div>
      </div>
    </nav>
  </aside>

  <!-- â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ MAIN â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <main class="main">
  <form id="profileForm" action="complete_profile.php" method="POST" enctype="multipart/form-data" style="display:contents">

    <!-- ===== STEP 1 ===== -->
    <div class="step-screen active" id="step1">
      <div class="step-header" style="animation:fadeUp .5s .05s ease both">
        <button type="button" class="back-btn" onclick="history.back()" title="Go back">
          <span class="mi">arrow_back</span>
        </button>
        <div class="step-header-text">
          <div class="step-header-label">Step 1 of 3</div>
          <div class="step-header-title">Tell us about yourself</div>
        </div>
      </div>

      <div class="progress-wrap" style="animation:fadeUp .5s .1s ease both">
        <div class="progress-top">
          <span class="progress-label">Personal Details</span>
          <span class="progress-pct" id="pct1">33%</span>
        </div>
        <div class="progress-track"><div class="progress-fill" id="pf1" style="--w:33%;width:33%"></div></div>
      </div>

      <div class="form-body" style="animation:fadeUp .5s .15s ease both">
        <div class="form-section">
          <div class="form-section-title"><span class="mi" style="font-size:15px;color:var(--blue)">person</span> Basic Information</div>

          <div class="field">
            <label class="field-label" for="full_name">Full Name <span style="color:var(--blue)">*</span></label>
            <div class="field-input-wrap">
              <span class="mi field-icon">person</span>
              <input class="fi" id="full_name" name="full_name" type="text" placeholder="e.g. Arjun Sharma" required value="<?= htmlspecialchars($student['full_name'] ?? '') ?>"/>
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="dob">Date of Birth <span style="color:var(--blue)">*</span></label>
            <div class="field-input-wrap">
              <span class="mi field-icon">calendar_month</span>
              <input class="fi" id="dob" name="dob" type="date" required value="<?= htmlspecialchars($student['dob'] ?? '') ?>"/>
            </div>
          </div>

          <div class="field">
            <div class="field-label">Gender <span style="color:var(--blue)">*</span></div>
            <div class="gender-grid">
              <?php foreach([['male','Male','male'],['female','Female','female'],['other','Other','more_horiz']] as [$v,$l,$ic]): ?>
              <label class="gender-opt">
                <input type="radio" name="gender" value="<?=$v?>" <?= ($student['gender']??'male')===$v?'checked':'' ?>/>
                <div class="gender-box">
                  <span class="mi"><?=$ic?></span>
                  <span class="lbl"><?=$l?></span>
                </div>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="email">Email Address <span style="color:var(--blue)">*</span></label>
            <div class="field-input-wrap">
              <span class="mi field-icon">mail</span>
              <input class="fi" id="email" name="email" type="email" placeholder="you@college.edu" required value="<?= htmlspecialchars($student['email'] ?? '') ?>"/>
            </div>
          </div>
        </div>
      </div>

      <div class="form-footer" style="animation:fadeUp .5s .2s ease both">
        <button type="button" class="btn-next" onclick="goNext(1)">
          Continue to Academic Info
          <span class="mi">arrow_forward</span>
        </button>
      </div>
    </div>

    <!-- ===== STEP 2 ===== -->
    <div class="step-screen" id="step2">
      <div class="step-header">
        <button type="button" class="back-btn" onclick="goPrev(2)" title="Back">
          <span class="mi">arrow_back</span>
        </button>
        <div class="step-header-text">
          <div class="step-header-label">Step 2 of 3</div>
          <div class="step-header-title">Academic Information</div>
        </div>
      </div>

      <div class="progress-wrap">
        <div class="progress-top">
          <span class="progress-label">Photo, Department & Details</span>
          <span class="progress-pct">66%</span>
        </div>
        <div class="progress-track"><div class="progress-fill" style="width:66%"></div></div>
      </div>

      <div class="form-body">
        <!-- PHOTO -->
        <div class="form-section">
          <div class="form-section-title"><span class="mi" style="font-size:15px;color:var(--blue)">photo_camera</span> Profile Photo</div>
          <label class="photo-wrap" id="photoWrap">
            <input type="file" name="profile_photo" id="profile_photo" accept="image/png,image/jpeg" onchange="previewPhoto(event)"/>
            <div class="photo-ring" id="photoRing">
              <img id="photoImg" src="" alt=""/>
              <span class="mi">person</span>
            </div>
            <div class="photo-text">
              <strong>Upload your photo</strong>
              <span>JPG or PNG Â· Max 2 MB</span>
            </div>
            <div class="photo-btn">
              <span class="mi" style="font-size:16px">upload</span>
              Choose File
            </div>
          </label>
        </div>

        <!-- CONTACT -->
        <div class="form-section">
          <div class="form-section-title"><span class="mi" style="font-size:15px;color:var(--blue)">phone</span> Contact</div>
          <div class="field">
            <label class="field-label" for="phone">Phone Number</label>
            <div class="field-input-wrap">
              <span class="mi field-icon">phone</span>
              <input class="fi" id="phone" name="phone" type="tel" placeholder="+91 98765 43210" value="<?= htmlspecialchars($student['phone'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <!-- ACADEMICS -->
        <div class="form-section">
          <div class="form-section-title"><span class="mi" style="font-size:15px;color:var(--blue)">school</span> Academic Details</div>

          <div class="field">
            <label class="field-label" for="department">Department</label>
            <div class="field-input-wrap">
              <span class="mi field-icon">domain</span>
              <select class="fi fi-select" id="department" name="department">
                <?php foreach(['CO'=>'Computer','IT'=>'Information Technology','EE'=>'Electrical','ME'=>'Mechanical','AE'=>'Automobile'] as $v=>$l): ?>
                <option value="<?=$v?>" <?= ($student['department']??'')===$v?'selected':'' ?>><?=$l?></option>
                <?php endforeach; ?>
              </select>
              <span class="mi select-arrow">expand_more</span>
            </div>
          </div>

          <div class="two-col">
            <div class="field">
              <label class="field-label" for="batch_year">Batch Year</label>
              <input class="fi fi-no-icon" id="batch_year" name="batch_year" type="text" placeholder="e.g. 2024" value="<?= htmlspecialchars($student['batch_year'] ?? '') ?>"/>
            </div>
            <div class="field">
              <label class="field-label" for="student_id">Roll Number</label>
              <input class="fi fi-no-icon" id="student_id" name="student_id" type="text" placeholder="e.g. CO-2401" value="<?= htmlspecialchars($student['student_roll_no'] ?? '') ?>"/>
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="semester">Semester</label>
            <div class="field-input-wrap">
              <span class="mi field-icon">menu_book</span>
              <select class="fi fi-select" id="semester" name="semester">
                <?php for($i=1;$i<=6;$i++): ?>
                <option value="<?=$i?>" <?= ($student['semester']??'1')==$i?'selected':'' ?>>Semester <?=$i?></option>
                <?php endfor; ?>
              </select>
              <span class="mi select-arrow">expand_more</span>
            </div>
          </div>
        </div>

        <!-- BIO -->
        <div class="form-section">
          <div class="form-section-title"><span class="mi" style="font-size:15px;color:var(--blue)">edit_note</span> About You</div>
          <div class="field">
            <label class="field-label" for="bio">Short Bio</label>
            <textarea class="fi fi-ta fi-no-icon" id="bio" name="bio" placeholder="Tell your peers a bit about yourselfâ€¦"><?= htmlspecialchars($student['bio'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="form-footer">
        <button type="button" class="btn-next" onclick="goNext(2)">
          Continue to Interests
          <span class="mi">arrow_forward</span>
        </button>
        <button type="button" class="btn-skip" onclick="goNext(2)">Skip for now</button>
      </div>
    </div>

    <!-- ===== STEP 3 ===== -->
    <div class="step-screen" id="step3">
      <div class="step-header">
        <button type="button" class="back-btn" onclick="goPrev(3)" title="Back">
          <span class="mi">arrow_back</span>
        </button>
        <div class="step-header-text">
          <div class="step-header-label">Step 3 of 3 Â· Final Step</div>
          <div class="step-header-title">Interests & Socials</div>
        </div>
      </div>

      <div class="progress-wrap">
        <div class="progress-top">
          <span class="progress-label">Almost done!</span>
          <span class="progress-pct">99%</span>
        </div>
        <div class="progress-track"><div class="progress-fill" style="width:99%"></div></div>
      </div>

      <div class="form-body">
        <!-- INTERESTS -->
        <div class="form-section">
          <div class="form-section-title"><span class="mi" style="font-size:15px;color:var(--blue)">interests</span> Your Interests</div>
          <p style="font-size:13px;color:var(--ink-3);margin-bottom:14px;line-height:1.6">Pick what you love â€” we'll personalize your campus experience.</p>
          <div class="interests-grid" id="interestGrid">
            <?php
            $chips = ['Coding','Robotics','Sports','Music','Art','Gaming','Photography','Literature','Travel','Science','Design','Finance','Drama','Dance','Debate'];
            $saved = explode(',', $student['interests'] ?? 'Coding,Sports');
            foreach($chips as $c):
              $sel = in_array($c,$saved);
            ?>
            <button type="button" class="int-chip <?= $sel?'sel':'' ?>" data-value="<?=$c?>">
              <span class="mi">check</span><?=$c?>
            </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="selected_interests" name="selected_interests" value="<?= htmlspecialchars($student['interests'] ?? 'Coding,Sports') ?>"/>
        </div>

        <!-- SOCIALS -->
        <div class="form-section">
          <div class="form-section-title">
            <span class="mi" style="font-size:15px;color:var(--blue)">link</span> Social Links
            <span style="font-size:10px;font-weight:600;color:var(--ink-3);letter-spacing:.05em;margin-left:4px;text-transform:none">(optional)</span>
          </div>

          <div>
            <label class="field-label" style="margin-bottom:8px;display:block">LinkedIn</label>
            <div class="social-field">
              <div class="social-prefix"><span class="mi">work</span></div>
              <input id="linkedin" name="linkedin" type="text" placeholder="linkedin.com/in/your-name" value="<?= htmlspecialchars($student['linkedin'] ?? '') ?>"/>
            </div>
          </div>

          <div>
            <label class="field-label" style="margin-bottom:8px;display:block">GitHub</label>
            <div class="social-field">
              <div class="social-prefix"><span class="mi">code</span></div>
              <input id="github" name="github" type="text" placeholder="github.com/username" value="<?= htmlspecialchars($student['github'] ?? '') ?>"/>
            </div>
          </div>

          <div>
            <label class="field-label" style="margin-bottom:8px;display:block">Portfolio</label>
            <div class="social-field">
              <div class="social-prefix"><span class="mi">language</span></div>
              <input id="portfolio" name="portfolio" type="text" placeholder="https://yoursite.com" value="<?= htmlspecialchars($student['portfolio'] ?? '') ?>"/>
            </div>
          </div>
        </div>

        <p style="font-size:12px;color:var(--ink-3);text-align:center;margin-top:8px;line-height:1.6">By finishing setup, you agree to our community guidelines.</p>
      </div>

      <div class="form-footer">
        <button type="submit" class="btn-next" onclick="showSuccess()">
          <span class="mi" style="font-size:20px">rocket_launch</span>
          Finish Setup & Go to Dashboard
        </button>
      </div>
    </div>

  </form>
  </main>
</div>

<!-- SUCCESS OVERLAY -->
<div class="success-overlay" id="successOverlay">
  <div class="success-ring"><span class="mi">check</span></div>
  <div class="success-title">Profile Complete!</div>
  <div class="success-sub">Setting up your dashboardâ€¦</div>
  <div class="success-dots">
    <div class="success-dot"></div>
    <div class="success-dot"></div>
    <div class="success-dot"></div>
    <div class="success-dot"></div>
  </div>
</div>

<script>
let cur = 1;

function showStep(n) {
  document.querySelectorAll('.step-screen').forEach(s => s.classList.remove('active'));
  document.getElementById('step' + n).classList.add('active');
  window.scrollTo({top:0,behavior:'smooth'});
  cur = n;
  updateSidebar(n);
}

function updateSidebar(n) {
  document.querySelectorAll('.s-step').forEach(s => {
    const sn = parseInt(s.dataset.step);
    s.classList.remove('active','done');
    const numEl = s.querySelector('.s-step-num');
    if (sn === n) {
      s.classList.add('active');
      numEl.innerHTML = sn;
    } else if (sn < n) {
      s.classList.add('done');
      numEl.innerHTML = '<span class="mi check-anim" style="font-size:18px;color:#fff">check</span>';
      numEl.style.background = 'rgba(255,255,255,.3)';
      numEl.style.borderColor = 'rgba(255,255,255,.6)';
    } else {
      numEl.innerHTML = sn;
      numEl.style.background = '';
      numEl.style.borderColor = '';
    }
    const conn = document.getElementById('sc' + sn);
    if (conn) conn.style.background = sn < n ? 'rgba(255,255,255,.4)' : '';
  });
}

function goNext(step) {
  if (step === 1) {
    const fn = document.getElementById('full_name').value.trim();
    const db = document.getElementById('dob').value.trim();
    const em = document.getElementById('email').value.trim();
    if (!fn || !db || !em) {
      shakeField(!fn ? 'full_name' : !db ? 'dob' : 'email');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { shakeField('email'); return; }
  }
  if (step < 3) showStep(step + 1);
}

function goPrev(step) {
  if (step > 1) showStep(step - 1);
}

function shakeField(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.style.borderColor = '#ef4444';
  el.style.boxShadow = '0 0 0 4px rgba(239,68,68,.1)';
  el.animate([{transform:'translateX(-6px)'},{transform:'translateX(6px)'},{transform:'translateX(-4px)'},{transform:'translateX(4px)'},{transform:'translateX(0)'}],{duration:350,easing:'ease-in-out'});
  el.focus();
  setTimeout(()=>{el.style.borderColor='';el.style.boxShadow='';},1600);
}

function previewPhoto(e) {
  const f = e.target.files[0];
  if (!f) return;
  const url = URL.createObjectURL(f);
  const img = document.getElementById('photoImg');
  const ring = document.getElementById('photoRing');
  img.src = url;
  ring.classList.add('has-photo');
}

// Interests
document.querySelectorAll('.int-chip').forEach(chip => {
  chip.addEventListener('click', function() {
    this.classList.toggle('sel');
    updateInterests();
  });
});

function updateInterests() {
  const vals = [...document.querySelectorAll('.int-chip.sel')].map(c => c.dataset.value);
  document.getElementById('selected_interests').value = vals.join(',');
}

function showSuccess() {
  // Show overlay â€” form will still submit
  document.getElementById('successOverlay').classList.add('show');
}
</script>
</body>
</html>