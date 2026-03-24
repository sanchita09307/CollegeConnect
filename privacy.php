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
    <title>Privacy Policy – <?php echo htmlspecialchars($settings['site_name']); ?></title>
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
        .highlight-box{background:#f0f9ff;border-left:4px solid #2563eb;border-radius:0 10px 10px 0;padding:14px 18px;margin:16px 0;}
        .highlight-box p{font-size:13px;color:#1d4ed8;margin:0;font-weight:500;}

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
    <h1>Privacy Policy</h1>
    <p>How we collect, use, and protect your information</p>
</div>

<main class="section">
    <div class="container">
        <div class="doc-card">
            <p class="last-updated">Last updated: January 1, 2024</p>

            <div class="highlight-box">
                <p>Your privacy matters to us. This policy explains what data we collect and how it is used at <?php echo htmlspecialchars($settings['site_name']); ?>.</p>
            </div>

            <h2>1. Information We Collect</h2>
            <p>When you register or use <?php echo htmlspecialchars($settings['site_name']); ?>, we may collect the following information:</p>
            <ul>
                <li>Name, email address, and contact information</li>
                <li>Academic details such as roll number, department, and course information</li>
                <li>Profile photo (optional)</li>
                <li>Login activity and usage data</li>
                <li>Messages submitted through the contact form</li>
            </ul>

            <h2>2. How We Use Your Information</h2>
            <p>We use the information collected to:</p>
            <ul>
                <li>Create and manage your account on the platform</li>
                <li>Provide access to courses, grades, and academic materials</li>
                <li>Send important announcements and notifications</li>
                <li>Improve platform features and user experience</li>
                <li>Respond to support requests and inquiries</li>
            </ul>

            <h2>3. Data Sharing</h2>
            <p>We do not sell or share your personal data with third parties. Your information is only accessible to:</p>
            <ul>
                <li>Platform administrators at your institution</li>
                <li>Teachers assigned to your courses</li>
                <li>Our internal technical support team when necessary</li>
            </ul>

            <h2>4. Data Security</h2>
            <p>We take reasonable technical and organisational measures to protect your personal data from unauthorized access, loss, or misuse. Passwords are stored in encrypted form and all connections are secured via HTTPS.</p>

            <h2>5. Cookies</h2>
            <p>We use session cookies to keep you logged in during your visit. These cookies do not track you across other websites and are deleted when you close your browser or log out.</p>

            <h2>6. Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access the personal data we hold about you</li>
                <li>Request correction of inaccurate information</li>
                <li>Request deletion of your account and data</li>
            </ul>
            <p>To exercise any of these rights, contact us at <strong>support@collegeconnect.edu</strong>.</p>

            <h2>7. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. Any significant changes will be communicated via platform announcements. Continued use of the platform after changes constitutes acceptance of the updated policy.</p>

            <h2>8. Contact</h2>
            <p>For privacy-related questions, please reach us at <strong>support@collegeconnect.edu</strong> or use our <a href="contact.php" style="color:#2563eb;font-weight:600;">Contact Us</a> page.</p>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved. &nbsp;|&nbsp;
    <a href="terms.php">Terms of Service</a><a href="contact.php">Contact</a><a href="help_center.php">Help Center</a></p>
</footer>
</body>
</html>