<?php
echo "<b>Teacher hash:</b> " . password_hash('teacher@123', PASSWORD_BCRYPT) . "<br><br>";
echo "<b>Student hash:</b> " . password_hash('student@123', PASSWORD_BCRYPT) . "<br>";
?>