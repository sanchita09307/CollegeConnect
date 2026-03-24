<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings   = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$pageTitle  = "QR Attendance";
$activePage = "qr_attendance";
$teacherId  = (int)($teacher['id'] ?? 0);

// Ã?â??â??Ã?â??â?? Handle Generate QR Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??Ã?â??â??
$msg = '';
$qrSession = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'generate') {
        $subjectId  = (int)($_POST['subject_id'] ?? 0);
        $minutes    = max(5, min(60, (int)($_POST['duration'] ?? 15)));
        $radius     = max(0, min(500, (int)($_POST['radius'] ?? 100)));

        $teacherLat = isset($_POST['teacher_lat']) && $_POST['teacher_lat'] !== '' ? (float)$_POST['teacher_lat'] : null;
        $teacherLng = isset($_POST['teacher_lng']) && $_POST['teacher_lng'] !== '' ? (float)$_POST['teacher_lng'] : null;

        if ($subjectId > 0) {
            // Deactivate old active sessions for this teacher
            $conn->query("UPDATE qr_attendance_sessions SET is_active = 0 WHERE teacher_id = $teacherId AND is_active = 1");

            $token   = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + ($minutes * 60));
            $date    = date('Y-m-d');

            // If radius is 0, no location check
            if ($radius === 0) {
                $teacherLat = null;
                $teacherLng = null;
            }

            if ($teacherLat !== null && $teacherLng !== null) {
                $stmt = $conn->prepare("
                    INSERT INTO qr_attendance_sessions
                    (token, teacher_id, subject_id, date, expires_at, lat, lng, radius_meters, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                ");

                if (!$stmt) {
                    $msg = 'Database prepare failed: ' . $conn->error;
                } else {
                    // 8 placeholders = 8 bind variables
                    $stmt->bind_param(
                        "siissddi",
                        $token,
                        $teacherId,
                        $subjectId,
                        $date,
                        $expires,
                        $teacherLat,
                        $teacherLng,
                        $radius
                    );

                    if ($stmt->execute()) {
                        $sessionId = $conn->insert_id;

                        $qrSession = [
                            'id'      => $sessionId,
                            'token'   => $token,
                            'expires' => $expires,
                            'minutes' => $minutes,
                            'radius'  => $radius,
                        ];

                        $subRes = $conn->query("SELECT subject_name FROM subjects WHERE id = $subjectId");
                        $qrSession['subject'] = ($subRes && $subRes->num_rows > 0) ? $subRes->fetch_assoc()['subject_name'] : '';
                    } else {
                        $msg = 'Failed to create QR session: ' . $stmt->error;
                    }

                    $stmt->close();
                }
            } else {
                // Insert without location
                $stmt = $conn->prepare("
                    INSERT INTO qr_attendance_sessions
                    (token, teacher_id, subject_id, date, expires_at, lat, lng, radius_meters, is_active)
                    VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, 1)
                ");

                if (!$stmt) {
                    $msg = 'Database prepare failed: ' . $conn->error;
                } else {
                    $stmt->bind_param(
                        "siissi",
                        $token,
                        $teacherId,
                        $subjectId,
                        $date,
                        $expires,
                        $radius
                    );

                    if ($stmt->execute()) {
                        $sessionId = $conn->insert_id;

                        $qrSession = [
                            'id'      => $sessionId,
                            'token'   => $token,
                            'expires' => $expires,
                            'minutes' => $minutes,
                            'radius'  => $radius,
                        ];

                        $subRes = $conn->query("SELECT subject_name FROM subjects WHERE id = $subjectId");
                        $qrSession['subject'] = ($subRes && $subRes->num_rows > 0) ? $subRes->fetch_assoc()['subject_name'] : '';
                    } else {
                        $msg = 'Failed to create QR session: ' . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        } else {
            $msg = 'Please select a subject.';
        }
    }

    if ($_POST['action'] === 'deactivate') {
        $conn->query("UPDATE qr_attendance_sessions SET is_active = 0 WHERE teacher_id = $teacherId AND is_active = 1");
        $msg = 'QR session stopped.';
    }
}

// Ã¢??Ã¢?? Load teacher's active session Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??
if (!$qrSession) {
    $res = $conn->query("
        SELECT s.*, sub.subject_name
        FROM qr_attendance_sessions s
        JOIN subjects sub ON sub.id = s.subject_id
        WHERE s.teacher_id = $teacherId
          AND s.is_active = 1
          AND s.expires_at > NOW()
        ORDER BY s.id DESC
        LIMIT 1
    ");

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $qrSession = [
            'id'      => $row['id'],
            'token'   => $row['token'],
            'expires' => $row['expires_at'],
            'subject' => $row['subject_name'],
            'radius'  => $row['radius_meters'],
        ];
    }
}

// Ã¢??Ã¢?? Load teacher's subjects Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??
$subjects = [];
$subRes = $conn->query("
    SELECT id, subject_name, subject_code, branch_code, semester
    FROM subjects
    WHERE teacher_id = $teacherId
    ORDER BY semester, subject_name
");
if ($subRes) {
    while ($r = $subRes->fetch_assoc()) {
        $subjects[] = $r;
    }
}

// Ã¢??Ã¢?? Load scanned students for active session Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??Ã¢??
$scannedStudents = [];
if ($qrSession) {
    $sid = (int)$qrSession['id'];
    $res = $conn->query("
        SELECT l.*, st.full_name, st.student_roll_no, st.profile_photo, l.scanned_at
        FROM qr_attendance_logs l
        JOIN students st ON st.id = l.student_id
        WHERE l.session_id = $sid
        ORDER BY l.scanned_at ASC
    ");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $scannedStudents[] = $r;
        }
    }
}

include __DIR__ . '/teacher_topbar.php';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo (isset($_COOKIE['cc_dark']) && $_COOKIE['cc_dark'] === '1') ? 'dark' : ''; ?>">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>QR Attendance Ã¢?? CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
tailwind.config = {
    darkMode:'class',
    theme:{
        extend:{
            colors:{ primary:'#4349cf' }
        }
    }
}
</script>
<style>
*{font-family:'Inter',sans-serif;}
:root{--primary:#4349cf;}
body{min-height:100dvh;background:#f0f1ff;}
.dark body{background:#0d0e1c;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes pulse-ring{0%{transform:scale(1);opacity:0.8}100%{transform:scale(1.5);opacity:0}}
.fu{animation:fadeUp .4s ease both}
.fu1{animation:fadeUp .4s .07s ease both}
.fu2{animation:fadeUp .4s .14s ease both}
.pulse-ring{animation:pulse-ring 1.5s ease-out infinite;}
.card{background:white;border-radius:20px;border:1px solid #eef0ff;box-shadow:0 2px 16px rgba(67,73,207,.07);}
.dark .card{background:#1a1b2e;border-color:#2a2b45;}
</style>
</head>
<body class="pb-8">

<div class="max-w-4xl mx-auto px-4 pt-6">

  <div class="flex items-center gap-3 mb-6 fu">
    <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
      <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">qr_code_scanner</span>
    </div>
    <div>
      <h1 class="text-xl font-bold text-slate-800 dark:text-white">QR Attendance</h1>
      <p class="text-xs text-slate-400">Generate QR Ã‚Â· Students scan Ã‚Â· Attendance marked automatically</p>
    </div>
  </div>

  <?php if ($msg): ?>
  <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium fu" style="background:#dcfce7;color:#15803d">
    <span class="material-symbols-outlined text-sm align-middle mr-1">check_circle</span>
    <?php echo htmlspecialchars($msg); ?>
  </div>
  <?php endif; ?>

  <?php if ($qrSession): ?>
  <div class="card p-6 mb-6 fu text-center relative overflow-hidden">
    <div class="absolute top-0 left-0 right-0 h-1 rounded-t-xl" style="background:linear-gradient(90deg,#4349cf,#7479f5,#a78bfa)"></div>

    <div class="flex items-center justify-center gap-2 mb-1">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold text-green-700" style="background:#dcfce7">
        <span class="relative flex h-2 w-2">
          <span class="pulse-ring absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
        </span>
        Session Active
      </span>
    </div>

    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4"><?php echo htmlspecialchars($qrSession['subject']); ?></p>

    <div class="flex justify-center mb-4">
      <div class="bg-white p-4 rounded-2xl shadow-lg inline-block border-4 border-indigo-100">
        <div id="qrcode"></div>
      </div>
    </div>

    <p class="text-xs text-slate-400 mb-1">Students go to <strong>CollegeConnect Ã¢â€ â€™ Scan QR</strong> in menu</p>
    <p class="text-xs text-slate-400 mb-3">
      Expires: <strong id="expiryDisplay" class="text-red-500"><?php echo date('h:i A', strtotime($qrSession['expires'])); ?></strong>
      &nbsp;Ã‚Â·&nbsp; <span id="countdown" class="font-mono font-bold text-indigo-600"></span> remaining
    </p>
    <p class="text-xs text-slate-400 mb-4">Location radius: <?php echo (int)$qrSession['radius']; ?> meters</p>

    <!-- TOKEN BOX Ã¢â‚¬â€ for students who can't scan camera -->
    <div class="mx-auto max-w-sm mb-4 p-3 rounded-xl text-left" style="background:#f0f4ff;border:2px dashed #7479f5;">
      <p class="text-xs font-bold text-indigo-700 mb-1 flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">key</span>
        Token (for manual entry / if camera fails)
      </p>
      <div class="flex items-center gap-2">
        <code id="tokenDisplay" class="flex-1 text-xs font-mono bg-white dark:bg-slate-900 px-2 py-1.5 rounded-lg border border-indigo-100 break-all select-all text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($qrSession['token']); ?></code>
        <button onclick="copyToken()" class="shrink-0 px-2 py-1.5 rounded-lg text-xs font-bold text-white" style="background:#4349cf" title="Copy token">
          <span class="material-symbols-outlined text-sm">content_copy</span>
        </button>
      </div>
      <p class="text-[10px] text-slate-400 mt-1">Students can paste this in "Method 3 Ã¢â‚¬â€ Enter Token Manually"</p>
    </div>

    <form method="post">
      <input type="hidden" name="action" value="deactivate"/>
      <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-white transition hover:opacity-90"
              style="background:linear-gradient(135deg,#ef4444,#dc2626)">
        <span class="material-symbols-outlined text-sm align-middle mr-1">stop_circle</span>
        Stop QR Session
      </button>
    </form>
  </div>

  <div class="card p-5 fu1">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-bold text-slate-800 dark:text-white flex items-center gap-2">
        <span class="material-symbols-outlined text-indigo-500" style="font-variation-settings:'FILL' 1">how_to_reg</span>
        Scanned Students
        <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold text-indigo-700" style="background:#e0e3ff"><?php echo count($scannedStudents); ?></span>
      </h2>
      <button onclick="location.reload()" class="text-xs text-indigo-600 hover:underline flex items-center gap-1">
        <span class="material-symbols-outlined text-sm">refresh</span>Refresh
      </button>
    </div>

    <?php if (empty($scannedStudents)): ?>
    <div class="text-center py-10 text-slate-400">
      <span class="material-symbols-outlined text-4xl block mb-2">person_search</span>
      <p class="text-sm">Waiting for students to scan...</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($scannedStudents as $s): ?>
      <div class="flex items-center gap-3 p-3 rounded-xl" style="background:<?php echo ($s['status'] === 'present') ? '#f0fdf4' : '#fef2f2'; ?>;">
        <?php
          $photo = !empty($s['profile_photo'])
              ? '../uploads/profile_photos/' . htmlspecialchars($s['profile_photo'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($s['full_name']) . '&background=4349cf&color=fff&size=60';
        ?>
        <img src="<?php echo $photo; ?>" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow"/>
        <div class="flex-1">
          <p class="text-sm font-semibold text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($s['full_name']); ?></p>
          <p class="text-xs text-slate-400">
            <?php echo htmlspecialchars($s['student_roll_no']); ?> Ã‚Â· <?php echo date('h:i A', strtotime($s['scanned_at'])); ?>
          </p>
        </div>
        <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo ($s['status'] === 'present') ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'; ?>">
          <?php echo !empty($s['distance_meters']) ? htmlspecialchars($s['distance_meters']) . 'm away' : ucfirst($s['status']); ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <div class="card p-6 fu">
    <h2 class="font-bold text-slate-800 dark:text-white text-lg mb-5 flex items-center gap-2">
      <span class="material-symbols-outlined text-indigo-500">add_circle</span>
      Start New QR Session
    </h2>

    <form method="post" id="generateForm">
      <input type="hidden" name="action" value="generate"/>
      <input type="hidden" name="teacher_lat" id="teacherLat"/>
      <input type="hidden" name="teacher_lng" id="teacherLng"/>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Select Subject</label>
        <?php if (empty($subjects)): ?>
          <p class="text-sm text-red-500 p-3 rounded-xl bg-red-50">No subjects assigned. Contact admin.</p>
        <?php else: ?>
        <select name="subject_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-sm font-medium dark:bg-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400">
          <option value="">-- Choose Subject --</option>
          <?php foreach ($subjects as $sub): ?>
          <option value="<?php echo (int)$sub['id']; ?>">
            [Sem <?php echo htmlspecialchars($sub['semester']); ?>] <?php echo htmlspecialchars($sub['subject_name']); ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php endif; ?>
      </div>

      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">QR Active Duration</label>
          <select name="duration" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-sm dark:bg-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="5">5 minutes</option>
            <option value="10">10 minutes</option>
            <option value="15" selected>15 minutes</option>
            <option value="30">30 minutes</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Location Radius</label>
          <select name="radius" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-sm dark:bg-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400">
            <option value="50">50 meters (strict)</option>
            <option value="100" selected>100 meters</option>
            <option value="200">200 meters</option>
            <option value="500">500 meters (campus)</option>
            <option value="0">No location check</option>
          </select>
        </div>
      </div>

      <div id="locationStatus" class="mb-4 p-3 rounded-xl text-sm" style="background:#eef0ff;color:#4349cf">
        <span class="material-symbols-outlined text-sm align-middle mr-1">my_location</span>
        <span id="locationText">Detecting your location for proxy prevention...</span>
      </div>

      <button type="submit" id="generateBtn" class="w-full py-3 rounded-xl text-white font-bold text-sm transition hover:opacity-90 flex items-center justify-center gap-2"
              style="background:linear-gradient(135deg,#4349cf,#7479f5)">
        <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1">qr_code</span>
        Generate QR Code
      </button>
    </form>
  </div>

  <div class="grid grid-cols-3 gap-3 mt-4 fu2">
    <div class="card p-4 text-center">
      <span class="material-symbols-outlined text-2xl text-indigo-500 mb-1 block" style="font-variation-settings:'FILL' 1">qr_code_scanner</span>
      <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Student Scans</p>
      <p class="text-[10px] text-slate-400 mt-0.5">From their menu</p>
    </div>
    <div class="card p-4 text-center">
      <span class="material-symbols-outlined text-2xl text-green-500 mb-1 block" style="font-variation-settings:'FILL' 1">my_location</span>
      <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Location Check</p>
      <p class="text-[10px] text-slate-400 mt-0.5">Proxy prevented</p>
    </div>
    <div class="card p-4 text-center">
      <span class="material-symbols-outlined text-2xl text-purple-500 mb-1 block" style="font-variation-settings:'FILL' 1">assignment_turned_in</span>
      <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">Auto Marked</p>
      <p class="text-[10px] text-slate-400 mt-0.5">In attendance DB</p>
    </div>
  </div>
  <?php endif; ?>
</div>

<script>
function copyToken() {
    const el = document.getElementById('tokenDisplay');
    if (!el) return;
    const text = el.textContent.trim();
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => alert('Token copied! Share with students.'));
    } else {
        const r = document.createRange();
        r.selectNode(el);
        window.getSelection().removeAllRanges();
        window.getSelection().addRange(r);
        document.execCommand('copy');
        window.getSelection().removeAllRanges();
        alert('Token copied!');
    }
}


if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            const latInput = document.getElementById('teacherLat');
            const lngInput = document.getElementById('teacherLng');
            if (latInput) latInput.value = pos.coords.latitude;
            if (lngInput) lngInput.value = pos.coords.longitude;

            const el = document.getElementById('locationStatus');
            const tx = document.getElementById('locationText');
            if (el && tx) {
                el.style.background = '#dcfce7';
                el.style.color = '#15803d';
                tx.textContent = 'Location detected! Proxy prevention active.';
            }
        },
        function() {
            const el = document.getElementById('locationStatus');
            const tx = document.getElementById('locationText');
            if (el && tx) {
                el.style.background = '#fef9c3';
                el.style.color = '#854d0e';
                tx.textContent = 'Location not available. QR will work without location check.';
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
} else {
    const el = document.getElementById('locationStatus');
    const tx = document.getElementById('locationText');
    if (el && tx) {
        el.style.background = '#fef9c3';
        el.style.color = '#854d0e';
        tx.textContent = 'Geolocation not supported. QR will work without location check.';
    }
}

<?php if ($qrSession): ?>
const qrUrl = "<?php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(dirname($_SERVER['REQUEST_URI'] ?? ''), '/\\');
    echo $protocol . '://' . $host . $basePath . '/../student_page/student_qr_scan.php?token=' . urlencode($qrSession['token']);
?>";

new QRCode(document.getElementById("qrcode"), {
    text: qrUrl,
    width: 220,
    height: 220,
    colorDark: "#4349cf",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

const expiresAt = new Date("<?php echo $qrSession['expires']; ?>").getTime();
function updateCountdown() {
    const now = Date.now();
    const diff = Math.max(0, Math.floor((expiresAt - now) / 1000));
    const m = Math.floor(diff / 60);
    const s = diff % 60;
    const el = document.getElementById('countdown');
    if (el) el.textContent = m + ':' + String(s).padStart(2, '0');
    if (diff <= 0) {
        if (el) el.textContent = 'Expired';
        clearInterval(timer);
    }
}
const timer = setInterval(updateCountdown, 1000);
updateCountdown();

setInterval(() => location.reload(), 10000);
<?php endif; ?>
</script>
</body>
</html>