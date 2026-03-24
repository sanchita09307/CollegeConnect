<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$pageTitle  = "QR Attendance";
$activePage = "qr_attendance";
$teacherId  = (int)($teacher['id'] ?? 0);
$msg        = '';
$msgType    = 'success';
$qrSession  = null;

/* -------------------------------------------------------
   Ensure required table exists
-------------------------------------------------------- */
$conn->query("
CREATE TABLE IF NOT EXISTS qr_attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(100) NOT NULL UNIQUE,
    teacher_id INT NOT NULL,
    subject_id INT NOT NULL,
    date DATE NOT NULL,
    expires_at DATETIME NOT NULL,
    lat DECIMAL(10,7) NULL,
    lng DECIMAL(10,7) NULL,
    radius_meters INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (teacher_id),
    INDEX (subject_id),
    INDEX (is_active),
    INDEX (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->query("
CREATE TABLE IF NOT EXISTS qr_attendance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status VARCHAR(30) DEFAULT 'present',
    distance_meters DECIMAL(10,2) NULL,
    scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_scan (session_id, student_id),
    INDEX (session_id),
    INDEX (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* -------------------------------------------------------
   Handle form actions
-------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = trim($_POST['action']);

    if ($action === 'generate') {
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $minutes   = max(5, min(60, (int)($_POST['duration'] ?? 15)));
        $radius    = max(0, min(500, (int)($_POST['radius'] ?? 100)));

        $teacherLat = (isset($_POST['teacher_lat']) && $_POST['teacher_lat'] !== '') ? (float)$_POST['teacher_lat'] : null;
        $teacherLng = (isset($_POST['teacher_lng']) && $_POST['teacher_lng'] !== '') ? (float)$_POST['teacher_lng'] : null;

        if ($subjectId <= 0) {
            $msg = "Please select a subject.";
            $msgType = 'error';
        } else {
            // Check subject belongs to this teacher
            $check = $conn->prepare("SELECT id, subject_name FROM subjects WHERE id = ? AND teacher_id = ? LIMIT 1");
            if ($check) {
                $check->bind_param("ii", $subjectId, $teacherId);
                $check->execute();
                $subjectRes = $check->get_result();
                $subjectRow = $subjectRes ? $subjectRes->fetch_assoc() : null;
                $check->close();
            } else {
                $subjectRow = null;
            }

            if (!$subjectRow) {
                $msg = "Selected subject is invalid or not assigned to you.";
                $msgType = 'error';
            } else {
                // Old active session close
                $deactivate = $conn->prepare("UPDATE qr_attendance_sessions SET is_active = 0 WHERE teacher_id = ? AND is_active = 1");
                if ($deactivate) {
                    $deactivate->bind_param("i", $teacherId);
                    $deactivate->execute();
                    $deactivate->close();
                }

                $token   = bin2hex(random_bytes(16));
                $date    = date('Y-m-d');
                $expires = date('Y-m-d H:i:s', time() + ($minutes * 60));

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
                        $msg = "Database prepare failed: " . $conn->error;
                        $msgType = 'error';
                    } else {
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
                            $sessionId = $stmt->insert_id;

                            $qrSession = [
                                'id'      => $sessionId,
                                'token'   => $token,
                                'expires' => $expires,
                                'minutes' => $minutes,
                                'radius'  => $radius,
                                'subject' => $subjectRow['subject_name']
                            ];
                            $msg = "QR session generated successfully.";
                            $msgType = 'success';
                        } else {
                            $msg = "Failed to create QR session: " . $stmt->error;
                            $msgType = 'error';
                        }
                        $stmt->close();
                    }
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO qr_attendance_sessions
                        (token, teacher_id, subject_id, date, expires_at, lat, lng, radius_meters, is_active)
                        VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, 1)
                    ");

                    if (!$stmt) {
                        $msg = "Database prepare failed: " . $conn->error;
                        $msgType = 'error';
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
                            $sessionId = $stmt->insert_id;

                            $qrSession = [
                                'id'      => $sessionId,
                                'token'   => $token,
                                'expires' => $expires,
                                'minutes' => $minutes,
                                'radius'  => $radius,
                                'subject' => $subjectRow['subject_name']
                            ];
                            $msg = "QR session generated successfully.";
                            $msgType = 'success';
                        } else {
                            $msg = "Failed to create QR session: " . $stmt->error;
                            $msgType = 'error';
                        }
                        $stmt->close();
                    }
                }
            }
        }
    }

    if ($action === 'deactivate') {
        $stmt = $conn->prepare("UPDATE qr_attendance_sessions SET is_active = 0 WHERE teacher_id = ? AND is_active = 1");
        if ($stmt) {
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $stmt->close();
            $msg = "QR session stopped.";
            $msgType = 'success';
        } else {
            $msg = "Failed to stop session.";
            $msgType = 'error';
        }
    }
}

/* -------------------------------------------------------
   Load active session
-------------------------------------------------------- */
if (!$qrSession) {
    $stmt = $conn->prepare("
        SELECT s.*, sub.subject_name
        FROM qr_attendance_sessions s
        JOIN subjects sub ON sub.id = s.subject_id
        WHERE s.teacher_id = ?
          AND s.is_active = 1
          AND s.expires_at > NOW()
        ORDER BY s.id DESC
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $res = $stmt->get_result();
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
        $stmt->close();
    }
}

/* -------------------------------------------------------
   Load subjects of teacher
-------------------------------------------------------- */
$subjects = [];
$stmt = $conn->prepare("
    SELECT id, subject_name, subject_code, branch_code, semester
    FROM subjects
    WHERE teacher_id = ?
    ORDER BY semester, subject_name
");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $subRes = $stmt->get_result();
    while ($subRes && $r = $subRes->fetch_assoc()) {
        $subjects[] = $r;
    }
    $stmt->close();
}

/* -------------------------------------------------------
   Load scanned students
-------------------------------------------------------- */
$scannedStudents = [];
if ($qrSession) {
    $sid = (int)$qrSession['id'];

    $stmt = $conn->prepare("
        SELECT l.*, st.full_name, st.student_roll_no, st.profile_photo
        FROM qr_attendance_logs l
        JOIN students st ON st.id = l.student_id
        WHERE l.session_id = ?
        ORDER BY l.scanned_at ASC
    ");
    if ($stmt) {
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && $r = $res->fetch_assoc()) {
            $scannedStudents[] = $r;
        }
        $stmt->close();
    }
}

include __DIR__ . '/teacher_topbar.php';
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo (isset($_COOKIE['cc_dark']) && $_COOKIE['cc_dark'] === '1') ? 'dark' : ''; ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QR Attendance - CollegeConnect</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

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
*{font-family:'Inter',sans-serif;}
body{min-height:100dvh;background:#f5f7ff;}
.dark body{background:#0d0e1c;}
.card{background:#fff;border-radius:22px;border:1px solid #eef0ff;box-shadow:0 6px 20px rgba(67,73,207,.08);}
.dark .card{background:#1a1b2e;border-color:#2a2b45;}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fu{animation:fadeUp .4s ease both;}
.fu1{animation:fadeUp .4s .07s ease both;}
.fu2{animation:fadeUp .4s .14s ease both;}
@keyframes pulse-ring{0%{transform:scale(1);opacity:.8}100%{transform:scale(1.45);opacity:0}}
.pulse-ring{animation:pulse-ring 1.5s ease-out infinite;}
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
            <p class="text-xs text-slate-400">Generate QR · Students scan · Attendance marked automatically</p>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="mb-4 px-4 py-3 rounded-xl text-sm font-medium fu <?php echo $msgType === 'error' ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <?php if ($qrSession): ?>
        <div class="card p-6 mb-6 fu text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-1 rounded-t-xl" style="background:linear-gradient(90deg,#4349cf,#7479f5,#a78bfa)"></div>

            <div class="flex items-center justify-center gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold text-green-700 bg-green-100">
                    <span class="relative flex h-2 w-2">
                        <span class="pulse-ring absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                    </span>
                    Session Active
                </span>
            </div>

            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4">
                <?php echo htmlspecialchars($qrSession['subject']); ?>
            </p>

            <div class="flex justify-center mb-4">
                <div class="bg-white p-4 rounded-2xl shadow-lg inline-block border-4 border-indigo-100">
                    <div id="qrcode"></div>
                </div>
            </div>

            <p class="text-xs text-slate-400 mb-1">
                Students open <strong>CollegeConnect → Scan QR</strong>
            </p>

            <p class="text-xs text-slate-400 mb-3">
                Expires:
                <strong id="expiryDisplay" class="text-red-500">
                    <?php echo date('h:i A', strtotime($qrSession['expires'])); ?>
                </strong>
                ·
                <span id="countdown" class="font-mono font-bold text-indigo-600"></span> remaining
            </p>

            <p class="text-xs text-slate-400 mb-4">
                Location radius: <?php echo (int)$qrSession['radius']; ?> meters
            </p>

            <div class="mx-auto max-w-sm mb-4 p-3 rounded-xl text-left bg-indigo-50 border-2 border-dashed border-indigo-300">
                <p class="text-xs font-bold text-indigo-700 mb-1 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">key</span>
                    Token (manual entry)
                </p>
                <div class="flex items-center gap-2">
                    <code id="tokenDisplay" class="flex-1 text-xs font-mono bg-white dark:bg-slate-900 px-2 py-1.5 rounded-lg border border-indigo-100 break-all select-all text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($qrSession['token']); ?></code>
                    <button type="button" onclick="copyToken()" class="shrink-0 px-2 py-1.5 rounded-lg text-xs font-bold text-white bg-indigo-600">
                        <span class="material-symbols-outlined text-sm">content_copy</span>
                    </button>
                </div>
                <p class="text-[10px] text-slate-400 mt-1">Students can enter this token manually if camera does not work.</p>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="deactivate">
                <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-bold text-white hover:opacity-90" style="background:linear-gradient(135deg,#ef4444,#dc2626)">
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
                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold text-indigo-700 bg-indigo-100">
                        <?php echo count($scannedStudents); ?>
                    </span>
                </h2>
                <button type="button" onclick="location.reload()" class="text-xs text-indigo-600 hover:underline flex items-center gap-1">
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
                        <?php
                        $photo = !empty($s['profile_photo'])
                            ? '../uploads/profile_photos/' . htmlspecialchars($s['profile_photo'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($s['full_name']) . '&background=4349cf&color=fff&size=60';
                        ?>
                        <div class="flex items-center gap-3 p-3 rounded-xl <?php echo ($s['status'] === 'present') ? 'bg-green-50' : 'bg-red-50'; ?>">
                            <img src="<?php echo $photo; ?>" class="w-9 h-9 rounded-full object-cover border-2 border-white shadow" alt="student">
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                    <?php echo htmlspecialchars($s['full_name']); ?>
                                </p>
                                <p class="text-xs text-slate-400">
                                    <?php echo htmlspecialchars($s['student_roll_no'] ?? ''); ?> ·
                                    <?php echo date('h:i A', strtotime($s['scanned_at'])); ?>
                                </p>
                            </div>
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full <?php echo ($s['status'] === 'present') ? 'text-green-700 bg-green-100' : 'text-red-700 bg-red-100'; ?>">
                                <?php
                                if (!empty($s['distance_meters'])) {
                                    echo htmlspecialchars($s['distance_meters']) . 'm away';
                                } else {
                                    echo ucfirst($s['status']);
                                }
                                ?>
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
                <input type="hidden" name="action" value="generate">
                <input type="hidden" name="teacher_lat" id="teacherLat">
                <input type="hidden" name="teacher_lng" id="teacherLng">

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Select Subject</label>
                    <?php if (empty($subjects)): ?>
                        <p class="text-sm text-red-500 p-3 rounded-xl bg-red-50">No subjects assigned. Contact admin.</p>
                    <?php else: ?>
                        <select name="subject_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-sm font-medium dark:bg-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?php echo (int)$sub['id']; ?>">
                                    [Sem <?php echo htmlspecialchars($sub['semester']); ?>]
                                    <?php echo htmlspecialchars($sub['subject_name']); ?>
                                    <?php if (!empty($sub['subject_code'])): ?>
                                        (<?php echo htmlspecialchars($sub['subject_code']); ?>)
                                    <?php endif; ?>
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
                            <option value="60">60 minutes</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Location Radius</label>
                        <select name="radius" id="radiusSelect" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-sm dark:bg-slate-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="50">50 meters (strict)</option>
                            <option value="100" selected>100 meters</option>
                            <option value="200">200 meters</option>
                            <option value="500">500 meters (campus)</option>
                            <option value="0">No location check</option>
                        </select>
                    </div>
                </div>

                <div id="locationStatus" class="mb-4 p-3 rounded-xl text-sm bg-indigo-50 text-indigo-700">
                    <span class="material-symbols-outlined text-sm align-middle mr-1">my_location</span>
                    <span id="locationText">Detecting your location for proxy prevention...</span>
                </div>

                <button type="submit" id="generateBtn" class="w-full py-3 rounded-xl text-white font-bold text-sm hover:opacity-90 flex items-center justify-center gap-2" style="background:linear-gradient(135deg,#4349cf,#7479f5)">
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
        navigator.clipboard.writeText(text).then(() => {
            alert('Token copied successfully!');
        }).catch(() => {
            fallbackCopyText(text);
        });
    } else {
        fallbackCopyText(text);
    }
}

function fallbackCopyText(text) {
    const temp = document.createElement('textarea');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    alert('Token copied successfully!');
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('generateForm');
    const radiusSelect = document.getElementById('radiusSelect');
    const locationStatus = document.getElementById('locationStatus');
    const locationText = document.getElementById('locationText');
    const latInput = document.getElementById('teacherLat');
    const lngInput = document.getElementById('teacherLng');

    if (form && radiusSelect) {
        function handleLocationRequirement() {
            const radius = parseInt(radiusSelect.value || '100', 10);

            if (radius === 0) {
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';
                if (locationStatus && locationText) {
                    locationStatus.className = 'mb-4 p-3 rounded-xl text-sm bg-slate-100 text-slate-600';
                    locationText.textContent = 'Location check disabled. QR will generate without GPS.';
                }
                return;
            }

            if (!navigator.geolocation) {
                if (locationStatus && locationText) {
                    locationStatus.className = 'mb-4 p-3 rounded-xl text-sm bg-yellow-50 text-yellow-700';
                    locationText.textContent = 'Geolocation not supported. QR will still generate.';
                }
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    if (latInput) latInput.value = pos.coords.latitude;
                    if (lngInput) lngInput.value = pos.coords.longitude;

                    if (locationStatus && locationText) {
                        locationStatus.className = 'mb-4 p-3 rounded-xl text-sm bg-green-50 text-green-700';
                        locationText.textContent = 'Location detected successfully. Proxy prevention active.';
                    }
                },
                function () {
                    if (locationStatus && locationText) {
                        locationStatus.className = 'mb-4 p-3 rounded-xl text-sm bg-yellow-50 text-yellow-700';
                        locationText.textContent = 'Location permission denied or unavailable. QR will still generate.';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        radiusSelect.addEventListener('change', handleLocationRequirement);
        handleLocationRequirement();
    }

    <?php if ($qrSession): ?>
    const qrContainer = document.getElementById('qrcode');

    if (qrContainer) {
        const qrUrl = "<?php
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $currentDir = rtrim(dirname($_SERVER['REQUEST_URI'] ?? ''), '/\\');
            echo $protocol . '://' . $host . $currentDir . '/../student_page/student_qr_scan.php?token=' . urlencode($qrSession['token']);
        ?>";

        new QRCode(qrContainer, {
            text: qrUrl,
            width: 220,
            height: 220,
            colorDark: "#4349cf",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    }

    const expiresAt = new Date("<?php echo date('c', strtotime($qrSession['expires'])); ?>").getTime();

    function updateCountdown() {
        const now = Date.now();
        let diff = Math.floor((expiresAt - now) / 1000);
        const countdown = document.getElementById('countdown');

        if (!countdown) return;

        if (diff <= 0) {
            countdown.textContent = 'Expired';
            return;
        }

        const m = Math.floor(diff / 60);
        const s = diff % 60;
        countdown.textContent = m + ':' + String(s).padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
    setInterval(function () {
        location.reload();
    }, 10000);
    <?php endif; ?>
});
</script>
</body>
</html>