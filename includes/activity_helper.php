<?php
function logActivity($conn, $user_id, $role, $type, $text)
{
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, user_role, activity_type, activity_text) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $role, $type, $text);
        $stmt->execute();
        $stmt->close();
    }
}
?>