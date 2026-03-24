<?php
require_once __DIR__ . '/student_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$activeNav   = 'qr_scan';
$studentId   = (int)($student['id'] ?? 0);
$studentName = $student['full_name'] ?? 'Student';

$result    = null;
$resultMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token      = trim($_POST['token'] ?? '');
    $studentLat = (isset($_POST['student_lat']) && $_POST['student_lat'] !== '') ? (float)$_POST['student_lat'] : null;
    $studentLng = (isset($_POST['student_lng']) && $_POST['student_lng'] !== '') ? (float)$_POST['student_lng'] : null;

    if ($token !== '') {
        $stmt = $conn->prepare("
            SELECT * 
            FROM qr_attendance_sessions 
            WHERE token = ? AND is_active = 1 AND expires_at > NOW() 
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $session = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $session = null;
        }

        if (!$session) {
            $result = 'expired';
            $resultMsg = 'This QR code has expired or is invalid. Ask your teacher to generate a new one.';
        } else {
            $sid = (int)$session['id'];

            $checkStmt = $conn->prepare("
                SELECT id 
                FROM qr_attendance_logs 
                WHERE session_id = ? AND student_id = ? 
                LIMIT 1
            ");
            if ($checkStmt) {
                $checkStmt->bind_param('ii', $sid, $studentId);
                $checkStmt->execute();
                $already = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
            } else {
                $already = null;
            }

            if ($already) {
                $result = 'already';
                $resultMsg = 'You have already marked attendance for this session.';
            } else {
                $distanceM  = null;
                $locationOk = true;

                $sessionLat    = isset($session['lat']) ? (float)$session['lat'] : null;
                $sessionLng    = isset($session['lng']) ? (float)$session['lng'] : null;
                $sessionRadius = (int)($session['radius_meters'] ?? 0);

                if (
                    $sessionLat !== null && $sessionLng !== null &&
                    $sessionRadius > 0 &&
                    $studentLat !== null && $studentLng !== null
                ) {
                    $R    = 6371000;
                    $phi1 = deg2rad($sessionLat);
                    $phi2 = deg2rad($studentLat);
                    $dphi = deg2rad($studentLat - $sessionLat);
                    $dlam = deg2rad($studentLng - $sessionLng);

                    $a = sin($dphi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dlam / 2) ** 2;
                    $distanceM = (int) round(2 * $R * asin(min(1, sqrt($a))));
                    $locationOk = ($distanceM <= $sessionRadius);
                }

                if (!$locationOk) {
                    $result = 'far';
                    $resultMsg = "You are too far from class. You are {$distanceM}m away (max allowed: {$sessionRadius}m). Proxy attendance is not allowed.";

                    $rejStmt = $conn->prepare("
                        INSERT INTO qr_attendance_logs
                        (session_id, student_id, student_lat, student_lng, distance_meters, status)
                        VALUES (?, ?, ?, ?, ?, 'rejected')
                    ");
                    if ($rejStmt) {
                        $rejStmt->bind_param('iiddi', $sid, $studentId, $studentLat, $studentLng, $distanceM);
                        $rejStmt->execute();
                        $rejStmt->close();
                    }
                } else {
                    $subjectId = (int)$session['subject_id'];
                    $date      = $session['date'];

                    $markStmt = $conn->prepare("
                        INSERT INTO attendance (student_id, subject_id, date, status)
                        VALUES (?, ?, ?, 'present')
                        ON DUPLICATE KEY UPDATE status = 'present'
                    ");
                    if ($markStmt) {
                        $markStmt->bind_param('iis', $studentId, $subjectId, $date);
                        $markStmt->execute();
                        $markStmt->close();
                    }

                    $logStmt = $conn->prepare("
                        INSERT INTO qr_attendance_logs
                        (session_id, student_id, student_lat, student_lng, distance_meters, status)
                        VALUES (?, ?, ?, ?, ?, 'present')
                    ");
                    if ($logStmt) {
                        $logStmt->bind_param('iiddi', $sid, $studentId, $studentLat, $studentLng, $distanceM);
                        $logStmt->execute();
                        $logStmt->close();
                    }

                    $subName = '';
                    $subStmt = $conn->prepare("SELECT subject_name FROM subjects WHERE id = ? LIMIT 1");
                    if ($subStmt) {
                        $subStmt->bind_param('i', $subjectId);
                        $subStmt->execute();
                        $subRes  = $subStmt->get_result()->fetch_assoc();
                        $subName = $subRes['subject_name'] ?? '';
                        $subStmt->close();
                    }

                    $result = 'success';
                    $resultMsg = "Attendance marked for <strong>" . htmlspecialchars($subName) . "</strong> on " . date('d M Y', strtotime($date)) . ".";
                }
            }
        }
    } else {
        $result = 'invalid';
        $resultMsg = 'QR token not found.';
    }
}

$tokenFromUrl = trim($_GET['token'] ?? '');

include __DIR__ . '/topbar.php';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo (isset($_COOKIE['cc_dark']) && $_COOKIE['cc_dark'] === '1') ? 'dark' : ''; ?>">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Scan QR Attendance - CollegeConnect</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>

<script>
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                primary: '#4349cf'
            }
        }
    }
};
</script>

<style>
* { font-family: 'Inter', sans-serif; }
body { min-height: 100dvh; background: #f0f1ff; }
.dark body { background: #0d0e1c; }

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
@keyframes checkBounce {
    0%   { transform: scale(0); }
    70%  { transform: scale(1.2); }
    100% { transform: scale(1); }
}
@keyframes scan {
    0%   { top: 0; }
    100% { top: 100%; }
}

.fu  { animation: fadeUp .4s ease both; }
.fu1 { animation: fadeUp .4s .1s ease both; }
.check-bounce { animation: checkBounce .5s ease both; }

.card {
    background: white;
    border-radius: 20px;
    border: 1px solid #eef0ff;
    box-shadow: 0 2px 16px rgba(67,73,207,.07);
}
.dark .card {
    background: #1a1b2e;
    border-color: #2a2b45;
}

#qr-video {
    width: 100%;
    height: 280px;
    object-fit: cover;
    border-radius: 16px;
    background: #1a1a2e;
}
#scan-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 190px;
    height: 190px;
    border: 3px solid #4349cf;
    border-radius: 16px;
    box-shadow: 0 0 0 9999px rgba(0,0,0,.5);
}
.scan-line {
    position: absolute;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #4349cf, transparent);
    animation: scan 2s linear infinite;
}
.hidden-canvas { display: none; }

.token-box {
    background: linear-gradient(135deg, #eef0ff, #f5f0ff);
    border: 2px dashed #7479f5;
    border-radius: 16px;
    padding: 16px;
}
.dark .token-box {
    background: linear-gradient(135deg, #1e1f3a, #231e3a);
    border-color: #4349cf;
}

.step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #4349cf;
    color: white;
    font-size: 11px;
    font-weight: 700;
    flex-shrink: 0;
}
</style>
</head>
<body class="pb-24">

<div class="max-w-lg mx-auto px-4 pt-6">

    <div class="flex items-center gap-3 mb-6 fu">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-white shadow-lg" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
            <span class="material-symbols-outlined text-2xl" style="font-variation-settings:'FILL' 1">qr_code_scanner</span>
        </div>
        <div>
            <h1 class="text-xl font-bold text-slate-800 dark:text-white">QR Attendance</h1>
            <p class="text-xs text-slate-400">Mark attendance by scanning teacher's QR code</p>
        </div>
    </div>

    <?php if ($result): ?>
        <div class="card p-8 text-center fu">
            <?php if ($result === 'success'): ?>
                <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center check-bounce" style="background:#dcfce7">
                    <span class="material-symbols-outlined text-4xl text-green-600" style="font-variation-settings:'FILL' 1">check_circle</span>
                </div>
                <h2 class="text-xl font-bold text-green-700 mb-2">Attendance Marked!</h2>
                <p class="text-sm text-slate-500 mb-6"><?php echo $resultMsg; ?></p>
                <p class="text-xs text-slate-400">Hi <?php echo htmlspecialchars($studentName); ?>, your attendance has been recorded.</p>

            <?php elseif ($result === 'already'): ?>
                <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center" style="background:#dbeafe">
                    <span class="material-symbols-outlined text-4xl text-blue-600" style="font-variation-settings:'FILL' 1">info</span>
                </div>
                <h2 class="text-xl font-bold text-blue-700 mb-2">Already Marked</h2>
                <p class="text-sm text-slate-500 mb-6"><?php echo htmlspecialchars($resultMsg); ?></p>

            <?php elseif ($result === 'far'): ?>
                <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center" style="background:#fee2e2">
                    <span class="material-symbols-outlined text-4xl text-red-600" style="font-variation-settings:'FILL' 1">location_off</span>
                </div>
                <h2 class="text-xl font-bold text-red-700 mb-2">Too Far Away</h2>
                <p class="text-sm text-slate-500 mb-6"><?php echo htmlspecialchars($resultMsg); ?></p>

            <?php else: ?>
                <div class="w-20 h-20 rounded-full mx-auto mb-4 flex items-center justify-center" style="background:#fef9c3">
                    <span class="material-symbols-outlined text-4xl text-yellow-600" style="font-variation-settings:'FILL' 1">warning</span>
                </div>
                <h2 class="text-xl font-bold text-yellow-700 mb-2">QR Invalid / Expired</h2>
                <p class="text-sm text-slate-500 mb-6"><?php echo htmlspecialchars($resultMsg); ?></p>
            <?php endif; ?>

            <a href="student_qr_scan.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white text-sm font-bold" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
                <span class="material-symbols-outlined text-sm">qr_code_scanner</span>
                Scan Again
            </a>
        </div>

    <?php elseif ($tokenFromUrl): ?>
        <div class="card p-5 fu mb-4">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-green-600 mt-0.5" style="font-variation-settings:'FILL' 1">link</span>
                <div>
                    <h2 class="font-bold text-slate-800 dark:text-white">QR Link Detected!</h2>
                    <p class="text-sm text-slate-500 mt-1">Token ready. Allow location and tap Confirm Attendance.</p>
                </div>
            </div>
        </div>

        <form method="post" id="scanForm" class="card p-5 fu1">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenFromUrl); ?>">
            <input type="hidden" name="student_lat" id="sLat">
            <input type="hidden" name="student_lng" id="sLng">

            <div id="locStatus" class="text-xs text-slate-400 mb-4 flex items-center gap-1 p-3 rounded-xl" style="background:#eef0ff;color:#4349cf">
                <span class="material-symbols-outlined text-sm">my_location</span>
                Getting your location...
            </div>

            <button type="submit" class="w-full py-3 rounded-xl text-white font-bold text-sm flex items-center justify-center gap-2" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
                <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">assignment_turned_in</span>
                Confirm Attendance
            </button>
        </form>

    <?php else: ?>
        <div class="card overflow-hidden fu mb-4">
            <div class="p-4 pb-2">
                <div class="flex items-center gap-2 mb-3">
                    <span class="step-badge">1</span>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Scan QR with Camera</p>
                </div>
            </div>

            <div class="relative px-4 pb-4">
                <video id="qr-video" autoplay playsinline muted></video>
                <div id="scan-overlay"><div class="scan-line"></div></div>

                <div class="absolute top-6 left-7 px-2 py-1 rounded-lg text-xs font-bold text-white" style="background:rgba(67,73,207,0.85)">
                    <span class="material-symbols-outlined text-xs align-middle mr-0.5">videocam</span>
                    Point at QR code
                </div>
            </div>

            <div class="px-4 pb-4 space-y-2">
                <div id="cameraStatus" class="text-xs text-slate-500 flex items-center gap-1 p-2 rounded-lg bg-slate-50 dark:bg-slate-800">
                    <span class="material-symbols-outlined text-sm">camera</span>
                    <span id="cameraStatusText">Starting camera...</span>
                </div>

                <div id="secureWarning" class="hidden p-3 rounded-xl text-xs font-medium" style="background:#fef3c7;color:#92400e;">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">lock_open</span>
                    <strong>Camera blocked!</strong> Phone requires <strong>HTTPS</strong> to allow camera.<br>
                    <span class="mt-1 block">Use <strong>Method 2 or 3 below</strong> instead.</span>
                </div>

                <div id="cameraFailedBox" class="hidden p-3 rounded-xl text-xs font-medium" style="background:#fee2e2;color:#991b1b;">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">videocam_off</span>
                    Camera not available on this browser or phone.<br>
                    <span class="mt-1 block">Use <strong>Method 2 or 3 below</strong> to mark attendance.</span>
                </div>

                <button type="button" id="startCameraBtn" class="w-full py-2.5 rounded-xl text-white text-sm font-bold" style="background:#4349cf">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">refresh</span>
                    Retry Camera
                </button>
            </div>
        </div>

        <div class="card p-4 fu1 mb-4">
            <div class="flex items-center gap-2 mb-3">
                <span class="step-badge">2</span>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Take Photo of QR Code</p>
            </div>

            <p class="text-xs text-slate-400 mb-3">If camera does not open above, take a photo of teacher's QR and upload it here.</p>

            <label class="block cursor-pointer">
                <div class="flex items-center gap-3 p-3 rounded-xl border-2 border-dashed border-indigo-200 dark:border-indigo-800 hover:border-indigo-400 transition-colors" style="background:#f8f9ff">
                    <span class="material-symbols-outlined text-indigo-500 text-3xl">add_a_photo</span>
                    <div>
                        <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">Tap to take photo / choose file</p>
                        <p class="text-xs text-slate-400">Photo of teacher's QR code screen</p>
                    </div>
                </div>
                <input type="file" id="qrImageInput" accept="image/*" capture="environment" class="sr-only">
            </label>

            <div id="imageStatus" class="hidden mt-3 text-xs font-medium p-2 rounded-lg text-center"></div>
        </div>

        <div class="card p-4 fu1">
            <div class="flex items-center gap-2 mb-3">
                <span class="step-badge">3</span>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">Enter Token Manually</p>
            </div>

            <div class="token-box mb-3">
                <p class="text-xs text-slate-500 mb-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm text-indigo-500">info</span>
                    Teacher will show a token code on their screen. Copy and paste it here.
                </p>
                <p class="text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400">Example: a1b2c3d4e5f6... (long code)</p>
            </div>

            <form method="post" id="manualForm">
                <input type="hidden" name="student_lat" id="sLatManual">
                <input type="hidden" name="student_lng" id="sLngManual">

                <textarea
                    name="token"
                    id="tokenInput"
                    rows="2"
                    placeholder="Paste the token here..."
                    class="w-full px-3 py-2.5 rounded-xl border-2 border-indigo-200 dark:border-slate-600 text-sm dark:bg-slate-800 dark:text-white focus:outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 mb-3 resize-none font-mono"
                ></textarea>

                <div id="locStatusManual" class="text-xs mb-3 flex items-center gap-1 p-2 rounded-lg" style="background:#eef0ff;color:#4349cf">
                    <span class="material-symbols-outlined text-sm">my_location</span>
                    Getting location...
                </div>

                <button type="submit" onclick="return validateToken()" class="w-full py-3 rounded-xl text-white font-bold text-sm flex items-center justify-center gap-2" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
                    <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1">assignment_turned_in</span>
                    Submit Token & Mark Attendance
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<canvas id="qrCanvas" class="hidden-canvas"></canvas>

<script>
var studentLat = '';
var studentLng = '';

function setLocStatus(ok) {
    var msg = ok
        ? '<span class="material-symbols-outlined text-sm" style="color:#16a34a">check_circle</span> Location detected ✓ proxy prevention active'
        : '<span class="material-symbols-outlined text-sm" style="color:#ca8a04">warning</span> Location unavailable – attendance may be checked manually';

    var bg  = ok ? '#dcfce7' : '#fef9c3';
    var col = ok ? '#15803d' : '#854d0e';

    ['locStatus', 'locStatusManual'].forEach(function(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.innerHTML = msg;
        el.style.background = bg;
        el.style.color = col;
    });
}

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            studentLat = pos.coords.latitude;
            studentLng = pos.coords.longitude;

            document.querySelectorAll('input[name="student_lat"]').forEach(function(e) {
                e.value = studentLat;
            });
            document.querySelectorAll('input[name="student_lng"]').forEach(function(e) {
                e.value = studentLng;
            });

            setLocStatus(true);
        },
        function() {
            setLocStatus(false);
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
} else {
    setLocStatus(false);
}

function submitToken(token) {
    if (!token || !token.trim()) return;

    var finalToken = token.trim();

    try {
        var u = new URL(finalToken);
        var t = u.searchParams.get('token');
        if (t) finalToken = t;
    } catch (e) {}

    var f = document.createElement('form');
    f.method = 'post';

    [
        ['token', finalToken],
        ['student_lat', studentLat],
        ['student_lng', studentLng]
    ].forEach(function(pair) {
        var i = document.createElement('input');
        i.type = 'hidden';
        i.name = pair[0];
        i.value = pair[1];
        f.appendChild(i);
    });

    document.body.appendChild(f);
    f.submit();
}

function validateToken() {
    var tokenInput = document.getElementById('tokenInput');
    if (!tokenInput) return true;

    var val = tokenInput.value.trim();

    if (!val) {
        tokenInput.style.borderColor = '#ef4444';
        tokenInput.placeholder = 'Please paste the token from teacher!';
        tokenInput.focus();
        return false;
    }

    try {
        var u = new URL(val);
        var t = u.searchParams.get('token');
        if (t) tokenInput.value = t;
    } catch (e) {}

    var manualForm = document.getElementById('manualForm');
    if (manualForm) {
        manualForm.querySelectorAll('input[name="student_lat"]').forEach(function(e) {
            e.value = studentLat;
        });
        manualForm.querySelectorAll('input[name="student_lng"]').forEach(function(e) {
            e.value = studentLng;
        });
    }

    return true;
}

<?php if (!$result && !$tokenFromUrl): ?>
var qrStream = null;
var scanInterval = null;
var alreadySubmitted = false;

var video           = document.getElementById('qr-video');
var cameraTextEl    = document.getElementById('cameraStatusText');
var secureWarning   = document.getElementById('secureWarning');
var cameraFailedBox = document.getElementById('cameraFailedBox');
var startCameraBtn  = document.getElementById('startCameraBtn');
var qrImageInput    = document.getElementById('qrImageInput');
var imageStatus     = document.getElementById('imageStatus');
var canvas          = document.getElementById('qrCanvas');
var ctx             = canvas.getContext('2d', { willReadFrequently: true });

function isSecureCameraContext() {
    return window.isSecureContext ||
        location.protocol === 'https:' ||
        location.hostname === 'localhost' ||
        location.hostname === '127.0.0.1';
}

function stopCamera() {
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }

    if (qrStream) {
        qrStream.getTracks().forEach(function(track) {
            track.stop();
        });
        qrStream = null;
    }
}

function showCameraError(msg, isHttpsIssue) {
    if (cameraTextEl) {
        cameraTextEl.textContent = msg;
    }

    if (isHttpsIssue && secureWarning) {
        secureWarning.classList.remove('hidden');
    }

    if (cameraFailedBox) {
        cameraFailedBox.classList.remove('hidden');
    }

    if (startCameraBtn) {
        startCameraBtn.classList.remove('hidden');
    }
}

async function startCamera() {
    alreadySubmitted = false;

    if (secureWarning) secureWarning.classList.add('hidden');
    if (cameraFailedBox) cameraFailedBox.classList.add('hidden');

    stopCamera();

    if (!isSecureCameraContext()) {
        showCameraError('Camera requires HTTPS on mobile. Use Method 2 or Method 3 below.', true);
        return;
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        showCameraError('Camera API is not supported in this browser.', false);
        return;
    }

    if (cameraTextEl) {
        cameraTextEl.textContent = 'Requesting camera permission...';
    }

    try {
        let stream;

        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: { ideal: 'environment' },
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            });
        } catch (e) {
            stream = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });
        }

        qrStream = stream;
        video.srcObject = stream;
        video.setAttribute('playsinline', 'true');
        video.muted = true;
        await video.play();

        if (cameraTextEl) {
            cameraTextEl.textContent = 'Camera active - point at QR code';
        }

        scanInterval = setInterval(scanFrame, 300);

    } catch (err) {
        console.error('Camera error:', err);

        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            showCameraError('Camera permission denied. Allow it in browser settings and tap Retry.', false);
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            showCameraError('No camera found on this device.', false);
        } else if (err.name === 'NotReadableError') {
            showCameraError('Camera is already being used by another app.', false);
        } else {
            showCameraError('Unable to open camera on this phone/browser.', false);
        }
    }
}

function detectWithJsQR() {
    if (!video || !video.videoWidth || !video.videoHeight) return null;
    if (video.readyState < 2) return null;

    try {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        var imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        var code = jsQR(imgData.data, imgData.width, imgData.height, {
            inversionAttempts: 'dontInvert'
        });

        return code ? code.data : null;
    } catch (e) {
        return null;
    }
}

function foundQR(token) {
    if (alreadySubmitted) return;

    alreadySubmitted = true;
    stopCamera();

    if (cameraTextEl) {
        cameraTextEl.textContent = 'QR detected! Submitting...';
    }

    submitToken(token);
}

async function scanFrame() {
    if (alreadySubmitted) return;
    if (!video || video.paused || video.ended) return;

    if ('BarcodeDetector' in window) {
        try {
            const detector = new BarcodeDetector({ formats: ['qr_code'] });
            const barcodes = await detector.detect(video);

            if (barcodes && barcodes.length > 0) {
                foundQR(barcodes[0].rawValue);
                return;
            }
        } catch (e) {}
    }

    var token = detectWithJsQR();
    if (token) {
        foundQR(token);
    }
}

if (startCameraBtn) {
    startCameraBtn.addEventListener('click', function() {
        startCamera();
    });
}

if (qrImageInput) {
    qrImageInput.addEventListener('change', function(e) {
        var file = e.target.files && e.target.files[0];
        if (!file) return;

        imageStatus.classList.remove('hidden');
        imageStatus.style.background = '#eef0ff';
        imageStatus.style.color = '#4349cf';
        imageStatus.textContent = 'Reading QR from image...';

        var img = new Image();

        img.onload = function() {
            canvas.width = img.naturalWidth || img.width;
            canvas.height = img.naturalHeight || img.height;
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

            var imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            var code = jsQR(imgData.data, imgData.width, imgData.height, {
                inversionAttempts: 'attemptBoth'
            });

            if (code && code.data) {
                imageStatus.style.background = '#dcfce7';
                imageStatus.style.color = '#15803d';
                imageStatus.textContent = 'QR detected! Submitting attendance...';

                setTimeout(function() {
                    submitToken(code.data);
                }, 400);
            } else {
                imageStatus.style.background = '#fee2e2';
                imageStatus.style.color = '#991b1b';
                imageStatus.textContent = 'Could not read QR. Try a clearer photo.';
            }

            URL.revokeObjectURL(img.src);
        };

        img.onerror = function() {
            imageStatus.style.background = '#fee2e2';
            imageStatus.style.color = '#991b1b';
            imageStatus.textContent = 'Could not load image. Try another photo.';
        };

        img.src = URL.createObjectURL(file);
    });
}

if (isSecureCameraContext()) {
    startCamera();
} else {
    showCameraError('Phone camera is blocked because this page is not running on HTTPS.', true);
}
<?php endif; ?>
</script>
</body>
</html>