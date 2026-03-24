<?php
function getSiteSettings($conn)
{
    $result = $conn->query("SELECT * FROM site_settings ORDER BY id ASC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return [
        'site_name' => 'CollegeConnect',
        'site_logo' => null,
        'maintenance_mode' => 0,
        'maintenance_message' => 'Website is under maintenance'
    ];
}
?>