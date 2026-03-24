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
    <title>Help Center – <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Lexend',sans-serif;background:linear-gradient(135deg,#f8fafc 0%,#f0f9ff 100%);min-height:100vh;}
        .navbar{backdrop-filter:blur(8px);background:rgba(255,255,255,0.95);box-shadow:0 2px 15px rgba(0,0,0,0.05);position:fixed;top:0;left:0;right:0;z-index:50;}
        .logo{background:linear-gradient(135deg,#2563eb,#1d4ed8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-weight:700;}
        .container{width:100%;padding:0 16px;margin:0 auto;max-width:1024px;}
        @media(min-width:640px){.container{padding:0 24px;}}
        @media(min-width:1024px){.container{padding:0 32px;}}

        .page-hero{background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);padding:100px 0 60px;text-align:center;color:white;}
        .page-hero h1{font-size:32px;font-weight:700;margin-bottom:12px;}
        @media(min-width:768px){.page-hero h1{font-size:44px;}}
        .page-hero p{font-size:15px;opacity:0.85;max-width:500px;margin:0 auto;}

        .section{padding:60px 0;}
        .card{background:white;border-radius:16px;border:1px solid #e0e7ff;padding:28px;transition:all 0.3s;}
        .card:hover{box-shadow:0 12px 30px rgba(37,99,235,0.12);border-color:#93c5fd;transform:translateY(-4px);}
        .card-icon{width:48px;height:48px;background:linear-gradient(135deg,#2563eb,#1d4ed8);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;}
        .card-icon svg{width:24px;height:24px;color:white;}
        .card h3{font-size:17px;font-weight:700;color:#1e293b;margin-bottom:8px;}
        .card p{font-size:13px;color:#64748b;line-height:1.7;}
        .card a{display:inline-block;margin-top:14px;color:#2563eb;font-size:13px;font-weight:600;text-decoration:none;}
        .card a:hover{text-decoration:underline;}

        .grid-3{display:grid;grid-template-columns:1fr;gap:20px;}
        @media(min-width:768px){.grid-3{grid-template-columns:repeat(3,1fr);}}

        .section-title{font-size:26px;font-weight:700;color:#1e293b;margin-bottom:8px;}
        .section-sub{font-size:14px;color:#64748b;margin-bottom:36px;}

        footer{background:linear-gradient(135deg,#0f172a,#1e293b);color:#cbd5e1;padding:30px 0;text-align:center;font-size:13px;}
        footer a{color:#60a5fa;text-decoration:none;margin:0 8px;}
    </style>
</head>
<body>
<header class="navbar">
    <div class="container flex items-center justify-between h-16">
        <a href="index.php" class="logo text-xl"><?php echo htmlspecialchars($settings['site_name']); ?></a>
        <nav class="flex items-center gap-5">
            <a href="index.php" class="text-slate-600 hover:text-blue-600 text-sm font-medium transition">← Home</a>
            <a href="login.php" class="text-white text-sm font-semibold bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg transition">Login</a>
        </nav>
    </div>
</header>

<div class="page-hero">
    <h1>Help Center</h1>
    <p>Find answers, guides, and support for using <?php echo htmlspecialchars($settings['site_name']); ?></p>
</div>

<main class="section">
    <div class="container">
        <h2 class="section-title">How can we help?</h2>
        <p class="section-sub">Choose a category below to get started</p>

        <div class="grid-3">
            <div class="card">
                <div class="card-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"/></svg>
                </div>
                <h3>Student Guide</h3>
                <p>Learn how to register, log in, view grades, access materials, and manage your profile as a student.</p>
                <a href="faq.php">View FAQs →</a>
            </div>
            <div class="card">
                <div class="card-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                </div>
                <h3>Teacher Guide</h3>
                <p>Understand how to create courses, upload materials, manage students, post announcements, and more.</p>
                <a href="faq.php">View FAQs →</a>
            </div>
            <div class="card">
                <div class="card-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 17v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd"/></svg>
                </div>
                <h3>Admin Guide</h3>
                <p>Manage users, configure settings, approve registrations, and monitor platform activity from the admin panel.</p>
                <a href="faq.php">View FAQs →</a>
            </div>
            <div class="card">
                <div class="card-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                </div>
                <h3>Account & Security</h3>
                <p>Reset your password, update your email, manage login sessions, and keep your account secure.</p>
                <a href="faq.php">View FAQs →</a>
            </div>
            <div class="card">
                <div class="card-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/><path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/></svg>
                </div>
                <h3>Contact Support</h3>
                <p>Still have questions? Our support team is ready to assist you through our contact form.</p>
                <a href="contact.php">Contact Us →</a>
            </div>
            <div class="card">
                <div class="card-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                </div>
                <h3>FAQs</h3>
                <p>Browse frequently asked questions about registration, grades, materials, and platform features.</p>
                <a href="faq.php">Browse FAQs →</a>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved. &nbsp;|&nbsp;
    <a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="contact.php">Contact</a></p>
</footer>
</body>
</html>