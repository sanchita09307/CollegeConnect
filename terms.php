<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/settings_helper.php';
$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service – <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Lexend',sans-serif;background:linear-gradient(135deg,#f8fafc 0%,#f0f9ff 100%);min-height:100vh;}
        .navbar{backdrop-filter:blur(8px);background:rgba(255,255,255,0.95);box-shadow:0 2px 15px rgba(0,0,0,0.05);position:fixed;top:0;left:0;right:0;z-index:50;}
        .logo{background:linear-gradient(135deg,#2563eb,#1d4ed8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-weight:700;}
        .container{width:100%;padding:0 16px;margin:0 auto;max-width:820px;}
        @media(min-width:640px){.container{padding:0 24px;}}

        .page-hero{background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);padding:100px 0 60px;text-align:center;color:white;}
        .page-hero h1{font-size:32px;font-weight:700;margin-bottom:12px;}
        @media(min-width:768px){.page-hero h1{font-size:44px;}}
        .page-hero p{font-size:15px;opacity:0.85;}

        .section{padding:60px 0;}
        .doc-card{background:white;border-radius:20px;border:1px solid #e0e7ff;padding:36px 40px;}
        .doc-card h2{font-size:19px;font-weight:700;color:#1e293b;margin:30px 0 10px;padding-top:10px;border-top:1px solid #f1f5f9;}
        .doc-card h2:first-of-type{margin-top:0;border-top:none;padding-top:0;}
        .doc-card p{font-size:14px;color:#475569;line-height:1.8;margin-bottom:12px;}
        .doc-card ul{margin:8px 0 14px 20px;list-style:disc;}
        .doc-card ul li{font-size:14px;color:#475569;line-height:1.8;}
        .last-updated{font-size:13px;color:#94a3b8;margin-bottom:28px;}
        .highlight-box{background:#fef9ec;border-left:4px solid #f59e0b;border-radius:0 10px 10px 0;padding:14px 18px;margin:16px 0;}
        .highlight-box p{font-size:13px;color:#92400e;margin:0;font-weight:500;}

        footer{background:linear-gradient(135deg,#0f172a,#1e293b);color:#cbd5e1;padding:30px 0;text-align:center;font-size:13px;}
        footer a{color:#60a5fa;text-decoration:none;margin:0 8px;}
    </style>
</head>
<body>
<header class="navbar">
    <div class="container flex items-center justify-between h-16" style="max-width:1024px;">
        <a href="index.php" class="logo text-xl"><?php echo htmlspecialchars($settings['site_name']); ?></a>
        <nav class="flex items-center gap-5">
            <a href="index.php" class="text-slate-600 hover:text-blue-600 text-sm font-medium transition">← Home</a>
            <a href="login.php" class="text-white text-sm font-semibold bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition">Login</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Terms of Service</h1>
    <p>Please read these terms carefully before using <?php echo htmlspecialchars($settings['site_name']); ?></p>
</div>

<main class="section">
    <div class="container">
        <div class="doc-card">
            <p class="last-updated">Last updated: January 1, 2024</p>

            <div class="highlight-box">
                <p>By registering or using <?php echo htmlspecialchars($settings['site_name']); ?>, you agree to these Terms of Service. If you do not agree, please do not use the platform.</p>
            </div>

            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using <?php echo htmlspecialchars($settings['site_name']); ?> ("the Platform"), you agree to be bound by these Terms of Service and our Privacy Policy. These terms apply to all users including students, teachers, and administrators.</p>

            <h2>2. Eligibility</h2>
            <p>To use this platform, you must:</p>
            <ul>
                <li>Be enrolled as a student, employed as a faculty member, or be an authorized administrator of a registered institution</li>
                <li>Provide accurate and truthful information during registration</li>
                <li>Have your account approved by the platform administrator</li>
            </ul>

            <h2>3. User Responsibilities</h2>
            <p>As a user of <?php echo htmlspecialchars($settings['site_name']); ?>, you agree to:</p>
            <ul>
                <li>Keep your login credentials confidential and not share them with others</li>
                <li>Use the platform only for legitimate academic purposes</li>
                <li>Not attempt to access another user's account without authorization</li>
                <li>Not upload harmful, offensive, or copyrighted content without permission</li>
                <li>Report any security vulnerabilities or misuse to the administrator</li>
            </ul>

            <h2>4. Academic Integrity</h2>
            <p>Users must maintain academic integrity at all times. Sharing exam questions, submitting others' work as your own, or misrepresenting academic records is strictly prohibited and may result in immediate account suspension.</p>

            <h2>5. Content Ownership</h2>
            <p>Course materials, notes, and resources uploaded by teachers remain the intellectual property of the respective teacher or institution. Students may not reproduce or distribute such content outside the platform without written permission.</p>

            <h2>6. Account Suspension</h2>
            <p>The platform administrators reserve the right to suspend or permanently deactivate any account that:</p>
            <ul>
                <li>Violates these Terms of Service</li>
                <li>Engages in abusive, fraudulent, or illegal activities</li>
                <li>Provides false information during registration</li>
            </ul>

            <h2>7. Limitation of Liability</h2>
            <p><?php echo htmlspecialchars($settings['site_name']); ?> is provided "as is". We are not liable for any academic outcomes, data loss due to technical failures, or interruptions in service. We make reasonable efforts to maintain platform availability but do not guarantee uninterrupted access.</p>

            <h2>8. Modifications</h2>
            <p>We reserve the right to modify these Terms at any time. Users will be notified of significant changes through the platform. Continued use of the platform after any modification constitutes acceptance of the new terms.</p>

            <h2>9. Governing Law</h2>
            <p>These Terms are governed by the laws of India. Any disputes arising from the use of this platform shall be subject to the jurisdiction of the courts in Pune, Maharashtra.</p>

            <h2>10. Contact</h2>
            <p>For questions about these Terms, contact us at <strong>support@collegeconnect.edu</strong> or visit our <a href="contact.php" style="color:#2563eb;font-weight:600;">Contact Us</a> page.</p>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved. &nbsp;|&nbsp;
    <a href="privacy.php">Privacy Policy</a><a href="contact.php">Contact</a><a href="help_center.php">Help Center</a></p>
</footer>
</body>
</html>