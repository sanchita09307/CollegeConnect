<?php
require_once __DIR__ . '/teacher_session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/settings_helper.php';

$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}

$pageTitle  = "Messages";
$activePage = "messages";

/* ---------- Helpers ---------- */
function cc_has_column($conn, $table, $column) {
    $table  = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $q = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return ($q && $q->num_rows > 0);
}

function avatarUrl($name, $bg = '4349cf') {
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=$bg&color=fff&bold=true&size=80";
}

$teacherId   = (int)($teacher['id'] ?? 0);
$myName      = $teacher['name'] ?? 'Teacher';
$myEmail     = $teacher['email'] ?? '';
$myPhoto     = !empty($teacher['profile_photo'])
    ? "../uploads/profile_photos/" . htmlspecialchars($teacher['profile_photo'])
    : avatarUrl($myName, '4349cf');

$view     = $_GET['view'] ?? 'inbox';
$chatWith = (int)($_GET['with'] ?? 0);
$chatRole = $_GET['role'] ?? 'Student';

/* ---------- Check messages table ---------- */
$_hasMsgTable = false;
$_chk = $conn->query("SHOW TABLES LIKE 'messages'");
if ($_chk && $_chk->num_rows > 0) {
    $_hasMsgTable = true;
}

/* ---------- Check optional columns ---------- */
$_hasIsRead = false;
$_hasSenderRole = false;
$_hasReceiverRole = false;
$_hasCreatedAt = false;
$_hasAttachment = false;
$_hasAttachmentType = false;

if ($_hasMsgTable) {
    $_hasIsRead         = cc_has_column($conn, 'messages', 'is_read');
    $_hasSenderRole     = cc_has_column($conn, 'messages', 'sender_role');
    $_hasReceiverRole   = cc_has_column($conn, 'messages', 'receiver_role');
    $_hasCreatedAt      = cc_has_column($conn, 'messages', 'created_at');
    $_hasAttachment     = cc_has_column($conn, 'messages', 'attachment');
    $_hasAttachmentType = cc_has_column($conn, 'messages', 'attachment_type');
}

/* ---------- Send chat message + file upload ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_chat']) && $_hasMsgTable) {
    $toId   = (int)($_POST['to_id'] ?? 0);
    $toRole = $_POST['to_role'] ?? 'Student';
    $msg    = trim($_POST['message'] ?? '');

    $attachmentPath = '';
    $attachmentType = '';

    // Handle file upload
    if (!empty($_FILES['attachment']['name']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/chat_attachments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $origName = $_FILES['attachment']['name'];
        $tmpPath  = $_FILES['attachment']['tmp_name'];
        $fileSize = $_FILES['attachment']['size'];
        $fileExt  = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg','jpeg','png','gif','webp','pdf','doc','docx','xls','xlsx','ppt','pptx','txt','zip'];
        $maxSize = 10 * 1024 * 1024; // 10MB

        if (in_array($fileExt, $allowedExts) && $fileSize <= $maxSize) {
            $newName = 'chat_t' . $teacherId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
            $destPath = $uploadDir . $newName;

            if (move_uploaded_file($tmpPath, $destPath)) {
                $attachmentPath = $newName;
                $imgExts = ['jpg','jpeg','png','gif','webp'];
                $attachmentType = in_array($fileExt, $imgExts) ? 'image' : 'file';
            }
        }
    }

    if ($toId > 0 && ($msg !== '' || $attachmentPath !== '')) {
        if ($_hasSenderRole && $_hasReceiverRole) {
            $attachCol = '';
            if ($_hasAttachment && $attachmentPath)   $attachCol .= ', attachment';
            if ($_hasAttachmentType && $attachmentType) $attachCol .= ', attachment_type';

            $attachPH = '';
            if ($_hasAttachment && $attachmentPath)   $attachPH .= ', ?';
            if ($_hasAttachmentType && $attachmentType) $attachPH .= ', ?';

            $sql = "INSERT INTO messages (sender_id, sender_role, receiver_id, receiver_role, message" . $attachCol;
            if ($_hasIsRead) $sql .= ", is_read";
            if ($_hasCreatedAt) $sql .= ", created_at";
            $sql .= ") VALUES (?, 'Teacher', ?, ?, ?" . $attachPH;
            if ($_hasIsRead) $sql .= ", 0";
            if ($_hasCreatedAt) $sql .= ", NOW()";
            $sql .= ")";

            $s = $conn->prepare($sql);
            if ($s) {
                $params = [$teacherId, $toId, $toRole, $msg];
                $types = "iiss";
                if ($_hasAttachment && $attachmentPath) { $params[] = $attachmentPath; $types .= "s"; }
                if ($_hasAttachmentType && $attachmentType) { $params[] = $attachmentType; $types .= "s"; }
                $s->bind_param($types, ...$params);
                $s->execute();
                $s->close();
            }
        } else {
            $sql = "INSERT INTO messages (sender_id, receiver_id, message";
            if ($_hasAttachment && $attachmentPath)   $sql .= ", attachment";
            if ($_hasAttachmentType && $attachmentType) $sql .= ", attachment_type";
            if ($_hasIsRead) $sql .= ", is_read";
            if ($_hasCreatedAt) $sql .= ", created_at";
            $sql .= ") VALUES (?, ?, ?";
            if ($_hasAttachment && $attachmentPath)   $sql .= ", ?";
            if ($_hasAttachmentType && $attachmentType) $sql .= ", ?";
            if ($_hasIsRead) $sql .= ", 0";
            if ($_hasCreatedAt) $sql .= ", NOW()";
            $sql .= ")";

            $s = $conn->prepare($sql);
            if ($s) {
                $params = [$teacherId, $toId, $msg];
                $types = "iis";
                if ($_hasAttachment && $attachmentPath) { $params[] = $attachmentPath; $types .= "s"; }
                if ($_hasAttachmentType && $attachmentType) { $params[] = $attachmentType; $types .= "s"; }
                $s->bind_param($types, ...$params);
                $s->execute();
                $s->close();
            }
        }
    }

    header("Location: teacher_message.php?view=chat&with=$toId&role=$toRole");
    exit();
}

/* ---------- Load students list ---------- */
$allStudents = [];

$studentCols = ['id'];
if (cc_has_column($conn, 'students', 'full_name'))     $studentCols[] = 'full_name';
if (cc_has_column($conn, 'students', 'email'))         $studentCols[] = 'email';
if (cc_has_column($conn, 'students', 'profile_photo')) $studentCols[] = 'profile_photo';
if (cc_has_column($conn, 'students', 'course'))        $studentCols[] = 'course';
if (cc_has_column($conn, 'students', 'batch'))         $studentCols[] = 'batch';
if (cc_has_column($conn, 'students', 'department'))    $studentCols[] = 'department';
if (cc_has_column($conn, 'students', 'semester'))      $studentCols[] = 'semester';

$studentOrder = cc_has_column($conn, 'students', 'full_name') ? 'full_name' : 'id';
$studentSql = "SELECT " . implode(', ', $studentCols) . " FROM students";
if (cc_has_column($conn, 'students', 'status')) {
    $studentSql .= " WHERE status = 'approved'";
}
$studentSql .= " ORDER BY $studentOrder ASC LIMIT 200";

$sq = $conn->query($studentSql);
if ($sq) {
    $allStudents = $sq->fetch_all(MYSQLI_ASSOC);
}

/* ---------- Load other teachers ---------- */
$allTeachers = [];
$teacherCols = ['id'];
if (cc_has_column($conn, 'teachers', 'name'))          $teacherCols[] = 'name';
if (cc_has_column($conn, 'teachers', 'department'))    $teacherCols[] = 'department';
if (cc_has_column($conn, 'teachers', 'profile_photo')) $teacherCols[] = 'profile_photo';

if (count($teacherCols) > 1) {
    $teacherOrder = cc_has_column($conn, 'teachers', 'name') ? 'name' : 'id';
    $teacherSql = "SELECT " . implode(', ', $teacherCols) . " FROM teachers WHERE id != ? ORDER BY $teacherOrder ASC LIMIT 100";
    $tq = $conn->prepare($teacherSql);
    if ($tq) {
        $tq->bind_param("i", $teacherId);
        $tq->execute();
        $res = $tq->get_result();
        if ($res) $allTeachers = $res->fetch_all(MYSQLI_ASSOC);
        $tq->close();
    }
}

/* ---------- Chat thread ---------- */
$chatMsgs       = [];
$chatOtherName  = '';
$chatOtherPhoto = '';

if ($view === 'chat' && $chatWith > 0 && $_hasMsgTable) {

    if ($_hasIsRead) {
        if ($_hasReceiverRole && $_hasSenderRole) {
            $conn->query("UPDATE messages 
                          SET is_read = 1 
                          WHERE receiver_id = $teacherId 
                            AND receiver_role = 'Teacher' 
                            AND sender_id = $chatWith");
        } else {
            $conn->query("UPDATE messages 
                          SET is_read = 1 
                          WHERE receiver_id = $teacherId 
                            AND sender_id = $chatWith");
        }
    }

    if ($_hasSenderRole && $_hasReceiverRole) {
        $orderCol = $_hasCreatedAt ? 'created_at' : 'id';
        $chs = $conn->prepare("
            SELECT *, " . ($_hasIsRead ? "is_read" : "0 AS is_read") . "
            FROM messages
            WHERE (sender_id = ? AND sender_role = 'Teacher' AND receiver_id = ? AND receiver_role = ?)
               OR (sender_id = ? AND sender_role = ? AND receiver_id = ? AND receiver_role = 'Teacher')
            ORDER BY $orderCol ASC
        ");
        if ($chs) {
            $chs->bind_param("iisisi", $teacherId, $chatWith, $chatRole, $chatWith, $chatRole, $teacherId);
            $chs->execute();
            $res = $chs->get_result();
            if ($res) $chatMsgs = $res->fetch_all(MYSQLI_ASSOC);
            $chs->close();
        }
    } else {
        $orderCol = $_hasCreatedAt ? 'created_at' : 'id';
        $chs = $conn->prepare("
            SELECT *, " . ($_hasIsRead ? "is_read" : "0 AS is_read") . "
            FROM messages
            WHERE (sender_id = ? AND receiver_id = ?)
               OR (sender_id = ? AND receiver_id = ?)
            ORDER BY $orderCol ASC
        ");
        if ($chs) {
            $chs->bind_param("iiii", $teacherId, $chatWith, $chatWith, $teacherId);
            $chs->execute();
            $res = $chs->get_result();
            if ($res) $chatMsgs = $res->fetch_all(MYSQLI_ASSOC);
            $chs->close();
        }
    }

    if ($chatRole === 'Student') {
        $studentPhotoCol = cc_has_column($conn, 'students', 'profile_photo') ? ', profile_photo' : '';
        $sn = $conn->query("SELECT full_name $studentPhotoCol FROM students WHERE id = $chatWith LIMIT 1");
        if ($sn && $r = $sn->fetch_assoc()) {
            $chatOtherName  = $r['full_name'] ?? 'Student';
            $chatOtherPhoto = !empty($r['profile_photo']) ? "../uploads/profile_photos/" . htmlspecialchars($r['profile_photo']) : '';
        } else {
            $chatOtherName = 'Student';
        }
    } else {
        $teacherNameCol  = cc_has_column($conn, 'teachers', 'name') ? 'name' : 'id';
        $teacherPhotoCol = cc_has_column($conn, 'teachers', 'profile_photo') ? ', profile_photo' : '';
        $tn = $conn->query("SELECT $teacherNameCol AS name $teacherPhotoCol FROM teachers WHERE id = $chatWith LIMIT 1");
        if ($tn && $r = $tn->fetch_assoc()) {
            $chatOtherName  = $r['name'] ?? 'Teacher';
            $chatOtherPhoto = !empty($r['profile_photo']) ? "../uploads/profile_photos/" . htmlspecialchars($r['profile_photo']) : '';
        } else {
            $chatOtherName = 'Teacher';
        }
    }
}

/* ---------- Conversations list ---------- */
$conversations = [];
$totalUnread   = 0;

if ($_hasMsgTable) {
    $orderCol = $_hasCreatedAt ? 'created_at' : 'id';

    if ($_hasSenderRole && $_hasReceiverRole) {
        $baseQuery = "
            SELECT
                CASE WHEN m.sender_id = $teacherId AND m.sender_role = 'Teacher' THEN m.receiver_id ELSE m.sender_id END AS oid,
                CASE WHEN m.sender_id = $teacherId AND m.sender_role = 'Teacher' THEN m.receiver_role ELSE m.sender_role END AS orole,
                m.message AS last_msg,
                m.$orderCol AS sort_time
                " . ($_hasIsRead ? ",
                (
                    SELECT COUNT(*)
                    FROM messages mu
                    WHERE mu.sender_id = CASE WHEN m.sender_id = $teacherId AND m.sender_role='Teacher' THEN m.receiver_id ELSE m.sender_id END
                      AND mu.receiver_id = $teacherId
                      AND mu.receiver_role = 'Teacher'
                      AND mu.is_read = 0
                ) AS unread" : ",
                0 AS unread") . "
            FROM messages m
            INNER JOIN (
                SELECT
                    CASE WHEN sender_id = $teacherId AND sender_role='Teacher' THEN receiver_id ELSE sender_id END AS other_id,
                    CASE WHEN sender_id = $teacherId AND sender_role='Teacher' THEN receiver_role ELSE sender_role END AS other_role,
                    MAX($orderCol) AS last_created
                FROM messages
                WHERE (sender_id = $teacherId AND sender_role = 'Teacher') OR (receiver_id = $teacherId AND receiver_role = 'Teacher')
                GROUP BY other_id, other_role
            ) latest
            ON (
                CASE WHEN m.sender_id = $teacherId AND m.sender_role='Teacher' THEN m.receiver_id ELSE m.sender_id END = latest.other_id
                AND CASE WHEN m.sender_id = $teacherId AND m.sender_role='Teacher' THEN m.receiver_role ELSE m.sender_role END = latest.other_role
                AND m.$orderCol = latest.last_created
            )
            WHERE (m.sender_id = $teacherId AND m.sender_role = 'Teacher') OR (m.receiver_id = $teacherId AND m.receiver_role = 'Teacher')
            ORDER BY m.$orderCol DESC
        ";
    } else {
        $baseQuery = "
            SELECT
                CASE WHEN m.sender_id = $teacherId THEN m.receiver_id ELSE m.sender_id END AS oid,
                'Student' AS orole,
                m.message AS last_msg,
                m.$orderCol AS sort_time
                " . ($_hasIsRead ? ",
                (
                    SELECT COUNT(*)
                    FROM messages mu
                    WHERE mu.sender_id = CASE WHEN m.sender_id = $teacherId THEN m.receiver_id ELSE m.sender_id END
                      AND mu.receiver_id = $teacherId
                      AND mu.is_read = 0
                ) AS unread" : ",
                0 AS unread") . "
            FROM messages m
            INNER JOIN (
                SELECT
                    CASE WHEN sender_id = $teacherId THEN receiver_id ELSE sender_id END AS other_id,
                    MAX($orderCol) AS last_created
                FROM messages
                WHERE sender_id = $teacherId OR receiver_id = $teacherId
                GROUP BY other_id
            ) latest
            ON (
                CASE WHEN m.sender_id = $teacherId THEN m.receiver_id ELSE m.sender_id END = latest.other_id
                AND m.$orderCol = latest.last_created
            )
            WHERE m.sender_id = $teacherId OR m.receiver_id = $teacherId
            ORDER BY m.$orderCol DESC
        ";
    }

    $cq = $conn->query($baseQuery);
    if ($cq) {
        while ($row = $cq->fetch_assoc()) {
            $oid   = (int)$row['oid'];
            $orole = $row['orole'];

            if ($orole === 'Student') {
                $sn2 = $conn->query("SELECT full_name FROM students WHERE id = $oid LIMIT 1");
                $row['oname'] = ($sn2 && $nr = $sn2->fetch_assoc()) ? ($nr['full_name'] ?? "Student #$oid") : "Student #$oid";
            } else {
                $tn2 = $conn->query("SELECT name FROM teachers WHERE id = $oid LIMIT 1");
                $row['oname'] = ($tn2 && $nr = $tn2->fetch_assoc()) ? ($nr['name'] ?? "Teacher #$oid") : "Teacher #$oid";
            }

            $conversations[] = $row;
        }

        $totalUnread = array_sum(array_map(function ($x) {
            return (int)($x['unread'] ?? 0);
        }, $conversations));
    }
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Messages – CollegeConnect</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>tailwind.config={darkMode:"class",theme:{extend:{colors:{primary:"#4349cf"}}}}</script>
<style>
*{font-family:'Lexend',sans-serif}
:root{--primary:#4349cf;--grad:linear-gradient(135deg,#4349cf,#7479f5);}
body{min-height:100dvh;background:#f0f1ff;}
.dark body{background:#0d0e1c;}

@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes popIn{0%{opacity:0;transform:scale(0.6)}70%{transform:scale(1.05)}100%{opacity:1;transform:scale(1)}}
@keyframes msgPopMe{from{opacity:0;transform:translateX(16px) scale(.92)}to{opacity:1;transform:translateX(0) scale(1)}}
@keyframes msgPopThem{from{opacity:0;transform:translateX(-16px) scale(.92)}to{opacity:1;transform:translateX(0) scale(1)}}
@keyframes badgeBounce{0%,100%{transform:scale(1)}50%{transform:scale(1.3)}}
@keyframes glow{0%,100%{box-shadow:0 0 0 0 rgba(67,73,207,.3)}50%{box-shadow:0 0 0 8px rgba(67,73,207,0)}}
@keyframes checkIn{from{opacity:0;transform:scale(.5)}to{opacity:1;transform:scale(1)}}
@keyframes slideInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}

.fu{animation:fadeUp .4s ease both}.fu1{animation:fadeUp .4s .07s ease both}.fu2{animation:fadeUp .4s .14s ease both}

.hero-grad{background:var(--grad);}
.card{background:white;border-radius:1rem;border:1px solid #e8eaf6;box-shadow:0 1px 5px rgba(0,0,0,.06)}
.dark .card{background:#1a1b2e;border-color:#2a2b45}

.tab-pill{display:flex;background:#eef0ff;border-radius:.875rem;padding:4px;gap:3px}
.dark .tab-pill{background:#1a1b2e}
.tab-pill button{flex:1;padding:8px 6px;font-size:11px;font-weight:700;border-radius:.625rem;cursor:pointer;transition:all .18s;border:none;display:flex;align-items:center;justify-content:center;gap:4px;color:#64748b;background:transparent}
.tab-pill button.active{background:#4349cf;color:white;box-shadow:0 3px 10px rgba(67,73,207,.3)}
.dark .tab-pill button{color:#94a3b8}

.search-box{background:white;border:2px solid #e2e8f0;border-radius:.875rem;display:flex;align-items:center;gap:8px;padding:8px 12px;transition:border-color .2s}
.search-box:focus-within{border-color:#4349cf;box-shadow:0 0 0 3px rgba(67,73,207,.1)}
.dark .search-box{background:#1a1b2e;border-color:#2a2b45}

.conv-row{background:white;border-radius:1rem;border:1px solid #e8eaf6;transition:all .18s;cursor:pointer;display:flex;align-items:center;gap:12px;padding:12px 14px;text-decoration:none;color:inherit}
.conv-row:hover{transform:translateY(-1px);box-shadow:0 5px 18px rgba(67,73,207,.12);border-color:#c7caf0}
.dark .conv-row{background:#1a1b2e;border-color:#2a2b45}
.conv-row.unread-row{border-left:3px solid #4349cf}

.person-card{border-radius:.875rem;padding:10px 12px;display:flex;align-items:center;gap:10px;cursor:pointer;transition:all .18s;border:2px solid transparent;background:white;text-decoration:none;color:inherit}
.person-card:hover{background:#f0f1ff;border-color:#c7caf0;transform:translateY(-1px)}
.dark .person-card{background:#1a1b2e}.dark .person-card:hover{background:#1e1f35;border-color:#4349cf}

.bubble-me{background:var(--grad);color:white;border-radius:1.25rem 1.25rem .25rem 1.25rem;padding:10px 14px;font-size:13px;line-height:1.5;max-width:78%;word-break:break-word;box-shadow:0 4px 14px rgba(67,73,207,.3)}
.bubble-them{background:white;color:#1e293b;border:1px solid #e8eaf6;border-radius:1.25rem 1.25rem 1.25rem .25rem;padding:10px 14px;font-size:13px;line-height:1.5;max-width:78%;word-break:break-word;box-shadow:0 2px 8px rgba(0,0,0,.05)}
.dark .bubble-them{background:#252640;color:#f1f5f9;border-color:#2a2b45}

.msg-input-box{background:#f8f9ff;border:2px solid #e2e8f0;border-radius:1.25rem;padding:10px 14px;flex:1;display:flex;align-items:flex-end;gap:8px;transition:border-color .2s}
.msg-input-box:focus-within{border-color:#4349cf;box-shadow:0 0 0 3px rgba(67,73,207,.1)}
.dark .msg-input-box{background:#1a1b2e;border-color:#2a2b45}
.btn-send{background:var(--grad);color:white;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(67,73,207,.4);transition:all .2s;animation:glow 2.5s ease-in-out infinite;flex-shrink:0}
.btn-send:hover{transform:scale(1.1)}.btn-send:active{transform:scale(.92)}

.btn-attach{background:#f1f2ff;color:#4349cf;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;border:2px solid #e2e8f0;cursor:pointer}
.btn-attach:hover{background:#4349cf;color:white;border-color:#4349cf}
.dark .btn-attach{background:#1e1f35;border-color:#2a2b45}

.file-preview-strip{background:#f8f9ff;border:2px solid #c7caf0;border-radius:1rem;padding:10px 12px;margin-bottom:8px;display:none;align-items:center;gap:10px;animation:slideInUp .2s ease}
.dark .file-preview-strip{background:#1e1f35;border-color:#2a2b45}

.attach-img{max-width:220px;max-height:180px;border-radius:.875rem;object-fit:cover;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,.15);transition:transform .2s}
.attach-img:hover{transform:scale(1.03)}
.attach-file-pill{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.2);border-radius:.75rem;padding:8px 12px;text-decoration:none;transition:background .2s}
.attach-file-pill:hover{background:rgba(255,255,255,.3)}
.attach-file-pill-them{display:inline-flex;align-items:center;gap:8px;background:#f1f2ff;border-radius:.75rem;padding:8px 12px;text-decoration:none;color:#4349cf;transition:background .2s}
.attach-file-pill-them:hover{background:#e2e5ff}

.lightbox{position:fixed;inset:0;background:rgba(0,0,0,.92);z-index:9999;display:flex;align-items:center;justify-content:center;display:none}
.lightbox img{max-width:90vw;max-height:90vh;border-radius:1rem;box-shadow:0 20px 60px rgba(0,0,0,.5)}

.unread-badge{background:#ef4444;color:white;font-size:9px;font-weight:800;border-radius:9999px;min-width:16px;height:16px;display:flex;align-items:center;justify-content:center;padding:0 4px;animation:badgeBounce .4s ease}
.date-divider{display:flex;align-items:center;gap:8px;margin:8px 0}
.date-divider::before,.date-divider::after{content:'';flex:1;height:1px;background:#e8eaf6}
.dark .date-divider::before,.dark .date-divider::after{background:#2a2b45}

.role-badge-s{background:#f0fdf4;color:#16a34a;font-size:9px;font-weight:800;padding:2px 7px;border-radius:99px}
.role-badge-t{background:#eef0ff;color:#4349cf;font-size:9px;font-weight:800;padding:2px 7px;border-radius:99px}

.tscroll::-webkit-scrollbar{width:3px}.tscroll::-webkit-scrollbar-thumb{background:#c7d0ff;border-radius:4px}

.emoji-picker{position:absolute;bottom:75px;left:0;background:white;border:1px solid #e8eaf6;border-radius:1rem;padding:10px;box-shadow:0 8px 30px rgba(0,0,0,.15);display:none;z-index:50;flex-wrap:wrap;gap:4px;width:280px;max-height:200px;overflow-y:auto;animation:slideInUp .15s ease}
.dark .emoji-picker{background:#1a1b2e;border-color:#2a2b45}
.emoji-btn{font-size:22px;cursor:pointer;padding:4px;border-radius:.5rem;transition:background .15s;line-height:1}
.emoji-btn:hover{background:#f1f2ff}

.sql-hint{font-family:monospace;font-size:11px;background:#1e293b;color:#7dd3fc;padding:10px 14px;border-radius:.75rem;overflow-x:auto}
</style>
</head>
<body class="bg-[#f0f1ff] dark:bg-[#0d0e1c] text-slate-900 dark:text-slate-100">

<!-- Lightbox -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <img id="lightboxImg" src="" alt=""/>
  <button onclick="closeLightbox()" style="position:absolute;top:20px;right:24px;background:rgba(255,255,255,.1);border:none;color:white;border-radius:50%;width:44px;height:44px;font-size:22px;cursor:pointer">✕</button>
</div>

<?php if ($view === 'chat' && $chatWith > 0 && $_hasMsgTable):
    $otherPhoto = !empty($chatOtherPhoto) ? $chatOtherPhoto : avatarUrl($chatOtherName, $chatRole === 'Student' ? '059669' : '4349cf');
?>
<div class="flex flex-col h-dvh">
  <div class="sticky top-0 z-50 bg-white/95 dark:bg-slate-950/95 backdrop-blur-md px-4 py-3 flex items-center gap-3 border-b border-slate-200 dark:border-slate-800 shadow-sm">
    <a href="teacher_message.php" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors shrink-0">
      <span class="material-symbols-outlined text-slate-600 dark:text-slate-300">arrow_back</span>
    </a>
    <div class="relative shrink-0">
      <img src="<?php echo $otherPhoto; ?>" class="w-10 h-10 rounded-full border-2 border-primary object-cover shadow-sm"/>
      <div style="width:9px;height:9px;background:#22c55e;border-radius:50%;border:2px solid white;position:absolute;bottom:1px;right:1px" class="dark:border-slate-950"></div>
    </div>
    <div class="flex-1 min-w-0">
      <p class="font-bold text-sm truncate"><?php echo htmlspecialchars($chatOtherName); ?></p>
      <p class="text-[10px] font-semibold <?php echo $chatRole === 'Student' ? 'text-green-500' : 'text-indigo-500'; ?>">
        <?php echo $chatRole === 'Student' ? '🎒 Student' : '🎓 Teacher'; ?>
      </p>
    </div>
    <button onclick="toggleDark()" class="p-2 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
      <span class="material-symbols-outlined text-slate-500 dark:text-slate-300 text-xl" id="darkIconChat">dark_mode</span>
    </button>
  </div>

  <div id="chatArea" class="flex-1 overflow-y-auto tscroll px-4 py-4 space-y-3" style="background:radial-gradient(ellipse at top,#eef0ff,#f8f9ff)">
    <?php if (empty($chatMsgs)): ?>
      <div class="flex flex-col items-center justify-center h-full py-16 text-center" style="animation:fadeUp .5s ease">
        <div class="w-20 h-20 rounded-3xl bg-primary/10 flex items-center justify-center mb-4" style="animation:popIn .6s .1s ease both">
          <span class="material-symbols-outlined text-4xl text-primary/60" style="font-variation-settings:'FILL' 1;">chat_bubble</span>
        </div>
        <p class="font-bold text-slate-500">No messages yet</p>
        <p class="text-xs text-slate-400 mt-1">Start the conversation with <?php echo htmlspecialchars($chatOtherName); ?>!</p>
        <p class="text-xs text-slate-400 mt-1">📎 Share photos, assignments & documents!</p>
      </div>
    <?php else:
      $prevDate = '';
      foreach ($chatMsgs as $i => $m):
        $isMe = ((int)$m['sender_id'] === $teacherId);
        $msgTs = !empty($m['created_at']) ? strtotime($m['created_at']) : time();
        $timeStr = date('h:i A', $msgTs);
        $dateStr = date('d M Y', $msgTs);
        $showD   = ($dateStr !== $prevDate);
        $prevDate = $dateStr;
        $delay   = min($i * .04, .5);
        $hasAttach = !empty($m['attachment']);
        $attachIsImg = isset($m['attachment_type']) ? ($m['attachment_type'] === 'image') : false;
        if (!isset($m['attachment_type']) && $hasAttach) {
            $ext = strtolower(pathinfo($m['attachment'], PATHINFO_EXTENSION));
            $attachIsImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
        }
    ?>
      <?php if ($showD): ?>
      <div class="date-divider">
        <span class="text-[10px] font-bold text-slate-400 bg-white dark:bg-slate-800 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700">
          <?php echo $dateStr === date('d M Y') ? 'Today' : ($dateStr === date('d M Y', strtotime('-1 day')) ? 'Yesterday' : $dateStr); ?>
        </span>
      </div>
      <?php endif; ?>

      <div class="flex <?php echo $isMe ? 'justify-end' : 'justify-start'; ?> items-end gap-2"
           style="animation:<?php echo $isMe ? 'msgPopMe' : 'msgPopThem'; ?> .3s <?php echo $delay; ?>s ease both">
        <?php if (!$isMe): ?>
          <img src="<?php echo $otherPhoto; ?>" class="w-7 h-7 rounded-full object-cover shrink-0 border border-slate-200"/>
        <?php endif; ?>

        <div class="flex flex-col <?php echo $isMe ? 'items-end' : 'items-start'; ?> gap-1">
          <?php if ($hasAttach): ?>
            <?php if ($attachIsImg): ?>
              <div class="<?php echo $isMe ? 'bubble-me' : 'bubble-them'; ?>" style="padding:6px">
                <img src="../uploads/chat_attachments/<?php echo htmlspecialchars($m['attachment']); ?>"
                     class="attach-img" onclick="openLightbox(this.src)" alt="Image"/>
                <?php if (!empty($m['message'])): ?>
                  <p class="mt-2 text-sm"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="<?php echo $isMe ? 'bubble-me' : 'bubble-them'; ?>">
                <?php if (!empty($m['message'])): ?>
                  <p class="mb-2"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                <?php endif; ?>
                <a href="../uploads/chat_attachments/<?php echo htmlspecialchars($m['attachment']); ?>"
                   target="_blank" download
                   class="<?php echo $isMe ? 'attach-file-pill' : 'attach-file-pill-them'; ?>">
                  <span class="material-symbols-outlined text-lg" style="font-variation-settings:'FILL' 1;">description</span>
                  <span class="text-xs font-semibold truncate max-w-[150px]"><?php echo htmlspecialchars(basename($m['attachment'])); ?></span>
                  <span class="material-symbols-outlined text-base">download</span>
                </a>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="<?php echo $isMe ? 'bubble-me' : 'bubble-them'; ?>"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
          <?php endif; ?>

          <div class="flex items-center gap-1 px-1">
            <span class="text-[10px] text-slate-400"><?php echo $timeStr; ?></span>
            <?php if ($isMe): ?>
              <span class="material-symbols-outlined text-xs <?php echo !empty($m['is_read']) ? 'text-blue-400' : 'text-slate-300'; ?>" style="animation:checkIn .3s ease">done_all</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($isMe): ?>
          <img src="<?php echo $myPhoto; ?>" class="w-7 h-7 rounded-full object-cover shrink-0 border-2 border-primary"/>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 px-4 py-3 relative">
    <!-- File preview strip -->
    <div class="file-preview-strip" id="filePreviewStrip">
      <div id="filePreviewIcon" class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
        <span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings:'FILL' 1;">attach_file</span>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-xs font-bold text-slate-700 dark:text-slate-200 truncate" id="filePreviewName">No file</p>
        <p class="text-[10px] text-slate-400" id="filePreviewSize"></p>
      </div>
      <button onclick="clearAttachment()" class="p-1 rounded-lg hover:bg-red-50 text-red-400 transition-colors">
        <span class="material-symbols-outlined text-lg">close</span>
      </button>
    </div>

    <!-- Emoji picker -->
    <div class="emoji-picker" id="emojiPicker">
      <?php
      $emojis = ['😊','😂','❤️','👍','🙏','😍','🎉','🔥','✅','💯','📚','✏️','🏫','💡','⭐','🎓','📝','🤔','😅','👋','💪','🙌','😎','🤩','💬','📎','📄','🖊️','📋','🗂️','📊','📈','🔔','⏰','🏆','🎯'];
      foreach ($emojis as $e) echo "<span class='emoji-btn' onclick=\"insertEmoji('$e')\">$e</span>";
      ?>
    </div>

    <form method="POST" id="chatForm" class="flex items-end gap-2" enctype="multipart/form-data">
      <input type="hidden" name="send_chat" value="1"/>
      <input type="hidden" name="to_id" value="<?php echo $chatWith; ?>"/>
      <input type="hidden" name="to_role" value="<?php echo htmlspecialchars($chatRole); ?>"/>

      <input type="file" name="attachment" id="fileInput" class="hidden"
             accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
             onchange="previewFile(this)"/>

      <button type="button" class="btn-attach" onclick="document.getElementById('fileInput').click()" title="Attach file">
        <span class="material-symbols-outlined text-[20px]">attach_file</span>
      </button>

      <button type="button" class="btn-attach" onclick="toggleEmoji()" title="Emoji">
        <span class="material-symbols-outlined text-[20px]">sentiment_satisfied</span>
      </button>

      <div class="msg-input-box">
        <textarea name="message" id="msgInput" rows="1" placeholder="Type a message..."
                  class="flex-1 bg-transparent outline-none text-sm resize-none max-h-28 placeholder:text-slate-400"
                  oninput="autoResize(this)"></textarea>
      </div>
      <button type="submit" class="btn-send">
        <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">send</span>
      </button>
    </form>
    <p class="text-[10px] text-slate-400 text-center mt-1">📎 Images, PDF, Word, Excel, PPT, ZIP up to 10MB</p>
  </div>
</div>

<?php else: ?>
<?php include __DIR__ . '/teacher_topbar.php'; ?>

<div class="px-4 pt-4 fu">
  <div class="hero-grad rounded-2xl p-5 text-white shadow-lg shadow-indigo-300/30 relative overflow-hidden">
    <div class="absolute -right-5 -top-5 opacity-10 pointer-events-none">
      <span class="material-symbols-outlined" style="font-size:110px;font-variation-settings:'FILL' 1;">forum</span>
    </div>
    <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest mb-1">Communication Hub</p>
    <h1 class="text-xl font-bold">Messages 💬</h1>
    <p class="text-white/70 text-xs mt-1">
      <?php echo count($allStudents); ?> students •
      <?php echo count($allTeachers); ?> faculty •
      <?php if ($_hasMsgTable): ?>
        <?php echo count($conversations); ?> conversation<?php echo count($conversations) !== 1 ? 's' : ''; ?> • 📎 File sharing
      <?php endif; ?>
    </p>
    <?php if ($totalUnread > 0): ?>
    <div class="mt-3 inline-flex items-center gap-2 bg-white/20 rounded-full px-3 py-1.5" style="animation:popIn .5s .3s ease both">
      <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">mark_email_unread</span>
      <span class="text-xs font-bold"><?php echo $totalUnread; ?> unread</span>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!$_hasMsgTable): ?>
<div class="mx-4 mt-3 fu1">
  <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl">
    <div class="flex items-center gap-2 mb-2">
      <span class="material-symbols-outlined text-amber-500" style="font-variation-settings:'FILL' 1;">info</span>
      <p class="text-xs font-bold text-amber-700">Messages table not found. Run this SQL to enable chat + file sharing:</p>
    </div>
    <pre class="sql-hint">CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sender_id INT NOT NULL,
  sender_role ENUM('Student','Teacher') NOT NULL,
  receiver_id INT NOT NULL,
  receiver_role ENUM('Student','Teacher') NOT NULL,
  message TEXT,
  attachment VARCHAR(255),
  attachment_type ENUM('image','file'),
  reply_to_id INT DEFAULT NULL,
  is_read TINYINT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);</pre>
  </div>
</div>
<?php endif; ?>

<div class="px-4 pt-3 fu1">
  <div class="tab-pill">
    <?php if ($_hasMsgTable): ?>
    <button onclick="switchTab('chats')" id="tab-btn-chats" class="active">
      <span class="material-symbols-outlined text-[16px]" style="font-variation-settings:'FILL' 1;">chat</span>
      Chats<?php if ($totalUnread > 0): ?> <span class="unread-badge"><?php echo $totalUnread; ?></span><?php endif; ?>
    </button>
    <?php endif; ?>
    <button onclick="switchTab('students')" id="tab-btn-students" class="<?php echo !$_hasMsgTable ? 'active' : ''; ?>">
      <span class="material-symbols-outlined text-[16px]">school</span>
      Students (<?php echo count($allStudents); ?>)
    </button>
    <button onclick="switchTab('teachers')" id="tab-btn-teachers" class="">
      <span class="material-symbols-outlined text-[16px]">groups</span>
      Faculty (<?php echo count($allTeachers); ?>)
    </button>
  </div>
</div>

<?php if ($_hasMsgTable): ?>
<div id="panel-chats" class="px-4 pt-3 pb-28 space-y-2">
  <div class="search-box mb-2">
    <span class="material-symbols-outlined text-slate-400">search</span>
    <input type="text" placeholder="Search conversations..." oninput="searchConv(this.value)"
           class="flex-1 bg-transparent outline-none text-sm font-medium placeholder:text-slate-400"/>
  </div>
  <?php if (empty($conversations)): ?>
  <div class="card p-10 text-center border-dashed border-2 border-slate-200 dark:border-slate-700 mt-2">
    <span class="material-symbols-outlined text-5xl text-slate-300" style="font-variation-settings:'FILL' 1;">forum</span>
    <h3 class="font-bold text-slate-500 mt-3">No Conversations Yet</h3>
    <p class="text-xs text-slate-400 mt-1">Pick a student or faculty member to start chatting!</p>
  </div>
  <?php else: foreach ($conversations as $i => $c):
    $unread = (int)($c['unread'] ?? 0);
    $cRole  = $c['orole'];
    $cPhoto = avatarUrl($c['oname'], $cRole === 'Student' ? '059669' : '4349cf');
    $ts     = !empty($c['sort_time']) ? strtotime($c['sort_time']) : time();
    $tStr   = $ts > strtotime('-1 day') ? date('h:i A', $ts) : date('d M', $ts);
    $lastMsg = $c['last_msg'] ?? '';
    if (empty($lastMsg)) $lastMsg = '📎 Attachment';
  ?>
  <a href="teacher_message.php?view=chat&with=<?php echo (int)$c['oid']; ?>&role=<?php echo urlencode($cRole); ?>"
     class="conv-row <?php echo $unread > 0 ? 'unread-row' : ''; ?>"
     style="animation:fadeUp .4s <?php echo .05 + $i * .06; ?>s ease both">
    <div class="relative shrink-0">
      <img src="<?php echo $cPhoto; ?>" class="w-12 h-12 rounded-full border-2 <?php echo $unread > 0 ? 'border-primary' : 'border-slate-200 dark:border-slate-700'; ?> object-cover"/>
      <?php if ($unread > 0): ?>
        <div class="unread-badge absolute -top-1 -right-1"><?php echo $unread; ?></div>
      <?php endif; ?>
    </div>
    <div class="flex-1 min-w-0">
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
          <p class="font-bold text-sm truncate <?php echo $unread > 0 ? 'text-primary' : ''; ?>"><?php echo htmlspecialchars($c['oname']); ?></p>
          <span class="<?php echo $cRole === 'Student' ? 'role-badge-s' : 'role-badge-t'; ?>"><?php echo htmlspecialchars($cRole); ?></span>
        </div>
        <span class="text-[10px] text-slate-400 shrink-0"><?php echo $tStr; ?></span>
      </div>
      <p class="text-xs text-slate-500 truncate mt-0.5 <?php echo $unread > 0 ? 'font-semibold text-slate-700 dark:text-slate-200' : ''; ?>"><?php echo htmlspecialchars($lastMsg); ?></p>
    </div>
    <span class="material-symbols-outlined text-slate-300 shrink-0">chevron_right</span>
  </a>
  <?php endforeach; endif; ?>
</div>
<?php endif; ?>

<div id="panel-students" class="hidden px-4 pt-3 pb-28 space-y-2">
  <div class="search-box mb-2">
    <span class="material-symbols-outlined text-slate-400">search</span>
    <input type="text" placeholder="Search students..." oninput="filterList('student-list', this.value)"
           class="flex-1 bg-transparent outline-none text-sm font-medium placeholder:text-slate-400"/>
  </div>
  <div id="student-list" class="space-y-2">
    <?php if (empty($allStudents)): ?>
    <div class="card p-8 text-center border-dashed border-2 border-slate-200 mt-1">
      <span class="material-symbols-outlined text-4xl text-slate-300">school</span>
      <p class="text-sm font-semibold text-slate-400 mt-2">No students registered yet</p>
    </div>
    <?php else: foreach ($allStudents as $i => $st):
      $stName  = $st['full_name'] ?? 'Student';
      $stPhoto = !empty($st['profile_photo'])
          ? "../uploads/profile_photos/" . htmlspecialchars($st['profile_photo'])
          : avatarUrl($stName, '059669');
      $subText = 'Student';
      if (!empty($st['course'])) $subText = $st['course'];
      elseif (!empty($st['batch'])) $subText = $st['batch'];
      elseif (!empty($st['department']) && !empty($st['semester'])) $subText = $st['department'] . ' • Sem ' . $st['semester'];
      elseif (!empty($st['department'])) $subText = $st['department'];
    ?>
    <a href="teacher_message.php?view=chat&with=<?php echo (int)$st['id']; ?>&role=Student"
       class="person-card" data-name="<?php echo strtolower($stName); ?>"
       style="animation:fadeUp .35s <?php echo .02 * min($i,15); ?>s ease both">
      <img src="<?php echo $stPhoto; ?>" class="w-10 h-10 rounded-full border-2 border-green-200 object-cover shrink-0"/>
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($stName); ?></p>
        <p class="text-[10px] text-slate-400"><?php echo htmlspecialchars($subText); ?></p>
      </div>
      <span class="material-symbols-outlined text-[18px] text-primary">chat_bubble</span>
    </a>
    <?php endforeach; endif; ?>
  </div>
</div>

<div id="panel-teachers" class="hidden px-4 pt-3 pb-28 space-y-2">
  <div class="search-box mb-2">
    <span class="material-symbols-outlined text-slate-400">search</span>
    <input type="text" placeholder="Search faculty..." oninput="filterList('teacher-list', this.value)"
           class="flex-1 bg-transparent outline-none text-sm font-medium placeholder:text-slate-400"/>
  </div>
  <div id="teacher-list" class="space-y-2">
    <?php if (empty($allTeachers)): ?>
    <div class="card p-8 text-center border-dashed border-2 border-slate-200 mt-1">
      <span class="material-symbols-outlined text-4xl text-slate-300">groups</span>
      <p class="text-sm font-semibold text-slate-400 mt-2">No other faculty found</p>
    </div>
    <?php else: foreach ($allTeachers as $i => $t):
      $tName = $t['name'] ?? 'Teacher';
      $tPhoto = !empty($t['profile_photo'])
          ? "../uploads/profile_photos/" . htmlspecialchars($t['profile_photo'])
          : avatarUrl($tName, '4349cf');
    ?>
    <a href="teacher_message.php?view=chat&with=<?php echo (int)$t['id']; ?>&role=Teacher"
       class="person-card" data-name="<?php echo strtolower($tName); ?>"
       style="animation:fadeUp .35s <?php echo .02 * min($i,15); ?>s ease both">
      <img src="<?php echo $tPhoto; ?>" class="w-10 h-10 rounded-full border-2 border-indigo-200 object-cover shrink-0"/>
      <div class="flex-1 min-w-0">
        <p class="font-semibold text-sm truncate"><?php echo htmlspecialchars($tName); ?></p>
        <p class="text-[10px] text-slate-400"><?php echo htmlspecialchars($t['department'] ?? 'Faculty'); ?></p>
      </div>
      <span class="material-symbols-outlined text-[18px] text-primary">chat_bubble</span>
    </a>
    <?php endforeach; endif; ?>
  </div>
</div>

<nav class="fixed bottom-0 left-0 right-0 z-40 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 px-2 py-2">
  <div class="max-w-xl mx-auto flex justify-around">
    <a href="teacher_dashboard.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">home</span><span class="text-[10px]">Home</span></a>
    <a href="teacher_classes.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">menu_book</span><span class="text-[10px]">Classes</span></a>
    <a href="teacher_attendence.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">assignment_turned_in</span><span class="text-[10px]">Attend.</span></a>
    <a href="teacher_message.php" class="text-primary flex flex-col items-center gap-0.5 px-3 py-1 relative">
      <span class="material-symbols-outlined text-xl" style="font-variation-settings:'FILL' 1;">message</span>
      <span class="text-[10px] font-bold">Messages</span>
      <?php if ($totalUnread > 0): ?><span class="unread-badge absolute top-0.5 right-2"><?php echo $totalUnread; ?></span><?php endif; ?>
    </a>
    <a href="teacher_profile.php" class="text-slate-400 hover:text-primary flex flex-col items-center gap-0.5 px-3 py-1 transition"><span class="material-symbols-outlined text-xl">person</span><span class="text-[10px]">Profile</span></a>
  </div>
</nav>
<?php endif; ?>

<script>
const ca = document.getElementById('chatArea');
if (ca) ca.scrollTop = ca.scrollHeight;

function autoResize(el){
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 112) + 'px';
}

const mi = document.getElementById('msgInput');
if (mi) {
    mi.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').submit();
        }
    });
}

function previewFile(input) {
    const file = input.files[0];
    if (!file) return;
    const strip = document.getElementById('filePreviewStrip');
    const name  = document.getElementById('filePreviewName');
    const size  = document.getElementById('filePreviewSize');
    const icon  = document.getElementById('filePreviewIcon');
    name.textContent = file.name;
    size.textContent = formatBytes(file.size);
    strip.style.display = 'flex';
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => { icon.innerHTML = `<img src="${e.target.result}" class="w-10 h-10 rounded-xl object-cover"/>`; };
        reader.readAsDataURL(file);
    } else {
        icon.innerHTML = `<span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings:'FILL' 1;">description</span>`;
    }
}

function clearAttachment() {
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreviewStrip').style.display = 'none';
    document.getElementById('filePreviewIcon').innerHTML = `<span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings:'FILL' 1;">attach_file</span>`;
}

function formatBytes(b) {
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
    return (b/1048576).toFixed(1) + ' MB';
}

function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

function toggleEmoji() {
    const p = document.getElementById('emojiPicker');
    p.style.display = p.style.display === 'flex' ? 'none' : 'flex';
}
function insertEmoji(emoji) {
    const ta = document.getElementById('msgInput');
    if (ta) {
        const s = ta.selectionStart, e = ta.selectionEnd;
        ta.value = ta.value.substring(0,s) + emoji + ta.value.substring(e);
        ta.selectionStart = ta.selectionEnd = s + emoji.length;
        ta.focus(); autoResize(ta);
    }
    document.getElementById('emojiPicker').style.display = 'none';
}
document.addEventListener('click', e => {
    const p = document.getElementById('emojiPicker');
    if (p && !e.target.closest('#emojiPicker') && !e.target.closest('.btn-attach')) p.style.display = 'none';
});

const _allTabs = ['chats','students','teachers'];
function switchTab(tab){
    _allTabs.forEach(t => {
        const p = document.getElementById('panel-' + t);
        const b = document.getElementById('tab-btn-' + t);
        if (p) p.classList.toggle('hidden', t !== tab);
        if (b) b.classList.toggle('active', t === tab);
    });
}

function searchConv(q){
    q = q.toLowerCase();
    document.querySelectorAll('.conv-row').forEach(r => {
        r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
}

function filterList(listId, q){
    q = q.toLowerCase();
    document.querySelectorAll('#' + listId + ' [data-name]').forEach(r => {
        r.style.display = (r.dataset.name || '').includes(q) ? '' : 'none';
    });
}

function toggleDark(){
    document.documentElement.classList.toggle('dark');
    ['darkIcon','darkIconChat'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = document.documentElement.classList.contains('dark') ? 'light_mode' : 'dark_mode';
    });
    localStorage.setItem('cc_dark', document.documentElement.classList.contains('dark') ? '1' : '0');
}

(function(){
    if (localStorage.getItem('cc_dark') === '1') {
        document.documentElement.classList.add('dark');
        document.addEventListener('DOMContentLoaded', () => {
            ['darkIcon','darkIconChat'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.textContent = 'light_mode';
            });
        });
    }
})();

<?php if ($_hasMsgTable): ?>
switchTab('chats');
<?php else: ?>
switchTab('students');
<?php endif; ?>
</script>
</body>
</html>