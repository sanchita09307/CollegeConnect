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
    <title>FAQ – <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Lexend',sans-serif;background:linear-gradient(135deg,#f8fafc 0%,#f0f9ff 100%);min-height:100vh;}
        .navbar{backdrop-filter:blur(8px);background:rgba(255,255,255,0.95);box-shadow:0 2px 15px rgba(0,0,0,0.05);position:fixed;top:0;left:0;right:0;z-index:50;}
        .logo{background:linear-gradient(135deg,#2563eb,#1d4ed8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-weight:700;}
        .container{width:100%;padding:0 16px;margin:0 auto;max-width:780px;}
        @media(min-width:640px){.container{padding:0 24px;}}

        .page-hero{background:linear-gradient(135deg,#2563eb 0%,#1d4ed8 100%);padding:100px 0 60px;text-align:center;color:white;}
        .page-hero h1{font-size:32px;font-weight:700;margin-bottom:12px;}
        @media(min-width:768px){.page-hero h1{font-size:44px;}}
        .page-hero p{font-size:15px;opacity:0.85;max-width:500px;margin:0 auto;}

        .section{padding:60px 0;}

        .tab-bar{display:flex;gap:10px;margin-bottom:32px;flex-wrap:wrap;}
        .tab-btn{padding:8px 18px;border-radius:20px;border:2px solid #e0e7ff;background:white;font-family:'Lexend',sans-serif;font-size:13px;font-weight:600;color:#475569;cursor:pointer;transition:all 0.2s;}
        .tab-btn.active,.tab-btn:hover{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:white;border-color:transparent;}

        .faq-group{display:none;}
        .faq-group.active{display:block;}

        .faq-item{background:white;border:1px solid #e0e7ff;border-radius:14px;margin-bottom:12px;overflow:hidden;transition:all 0.2s;}
        .faq-item:hover{border-color:#93c5fd;}
        .faq-question{width:100%;background:none;border:none;padding:18px 20px;text-align:left;display:flex;justify-content:space-between;align-items:center;cursor:pointer;font-family:'Lexend',sans-serif;font-size:15px;font-weight:600;color:#1e293b;}
        .faq-question:hover{color:#2563eb;}
        .faq-arrow{width:20px;height:20px;color:#2563eb;transition:transform 0.3s;flex-shrink:0;}
        .faq-answer{max-height:0;overflow:hidden;transition:max-height 0.35s ease,padding 0.3s ease;}
        .faq-answer.open{max-height:300px;padding:0 20px 18px;}
        .faq-answer p{font-size:14px;color:#64748b;line-height:1.75;}
        .faq-item.open .faq-arrow{transform:rotate(180deg);}

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
    <h1>Frequently Asked Questions</h1>
    <p>Quick answers to common questions about <?php echo htmlspecialchars($settings['site_name']); ?></p>
</div>

<main class="section">
    <div class="container">

        <!-- Tabs -->
        <div class="tab-bar">
            <button class="tab-btn active" onclick="switchTab('general')">General</button>
            <button class="tab-btn" onclick="switchTab('student')">Students</button>
            <button class="tab-btn" onclick="switchTab('teacher')">Teachers</button>
            <button class="tab-btn" onclick="switchTab('account')">Account</button>
        </div>

        <!-- General -->
        <div class="faq-group active" id="tab-general">
            <?php
            $generalFAQs = [
                ["What is CollegeConnect?", "CollegeConnect is a smart college management platform that helps students, teachers, and administrators manage academic activities like courses, grades, materials, and announcements in one place."],
                ["Is CollegeConnect free to use?", "Access to CollegeConnect is provided by your institution. Please contact your college administration for access credentials or registration details."],
                ["Which browsers are supported?", "CollegeConnect works best on Google Chrome, Mozilla Firefox, Microsoft Edge, and Safari (latest versions). We recommend keeping your browser updated."],
                ["Is my data safe?", "Yes. We take data privacy seriously. All data is stored securely and we follow strict privacy practices. Read our Privacy Policy for full details."],
            ];
            foreach($generalFAQs as $i => $faq): ?>
            <div class="faq-item" id="faq-g<?=$i?>">
                <button class="faq-question" onclick="toggleFAQ('g<?=$i?>')">
                    <?php echo htmlspecialchars($faq[0]); ?>
                    <svg class="faq-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-answer" id="ans-g<?=$i?>"><p><?php echo htmlspecialchars($faq[1]); ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Student -->
        <div class="faq-group" id="tab-student">
            <?php
            $studentFAQs = [
                ["How do I register as a student?", "Click 'Student Register' on the homepage, fill in your details including name, roll number, department, and email, then submit the form. Your registration will be reviewed and approved by the admin."],
                ["How do I view my grades?", "After logging in, navigate to the 'Grades' or 'Academic Records' section in your student dashboard to view all your course grades and performance."],
                ["How do I access course materials?", "Go to 'My Courses' in your dashboard. Click on any course to access uploaded study materials, notes, and resources shared by your teacher."],
                ["Can I update my profile photo?", "Yes! Go to 'My Profile' in your dashboard and click 'Edit Profile' to update your photo, contact information, and other details."],
                ["I forgot my password. What should I do?", "Click 'Forgot Password' on the login page and enter your registered email. A password reset link will be sent to your inbox."],
            ];
            foreach($studentFAQs as $i => $faq): ?>
            <div class="faq-item" id="faq-s<?=$i?>">
                <button class="faq-question" onclick="toggleFAQ('s<?=$i?>')">
                    <?php echo htmlspecialchars($faq[0]); ?>
                    <svg class="faq-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-answer" id="ans-s<?=$i?>"><p><?php echo htmlspecialchars($faq[1]); ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Teacher -->
        <div class="faq-group" id="tab-teacher">
            <?php
            $teacherFAQs = [
                ["How do I register as a teacher?", "Click 'Teacher Register' on the homepage and complete the registration form. Admin approval is required before you can log in."],
                ["How do I upload course materials?", "After logging in, go to your course in the 'My Courses' section and click 'Upload Material' to add PDFs, notes, or any study resources."],
                ["How do I post an announcement?", "Navigate to 'Announcements' in your teacher dashboard and click 'New Announcement'. Your message will be visible to all enrolled students."],
                ["Can I track student attendance?", "Yes, the attendance module in your dashboard allows you to mark, edit, and view attendance records for each class session."],
            ];
            foreach($teacherFAQs as $i => $faq): ?>
            <div class="faq-item" id="faq-t<?=$i?>">
                <button class="faq-question" onclick="toggleFAQ('t<?=$i?>')">
                    <?php echo htmlspecialchars($faq[0]); ?>
                    <svg class="faq-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-answer" id="ans-t<?=$i?>"><p><?php echo htmlspecialchars($faq[1]); ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Account -->
        <div class="faq-group" id="tab-account">
            <?php
            $accountFAQs = [
                ["How do I reset my password?", "On the login page, click 'Forgot Password' and enter your registered email address. You'll receive a reset link within a few minutes."],
                ["Can I change my registered email?", "Yes, go to 'My Profile' > 'Account Settings' and update your email address. An OTP verification may be required."],
                ["How do I log out?", "Click on your profile picture or name in the top-right corner of the dashboard and select 'Logout' from the dropdown menu."],
                ["My account is not approved yet. What should I do?", "New registrations require admin approval. If it's been more than 48 hours, please contact support at support@collegeconnect.edu."],
            ];
            foreach($accountFAQs as $i => $faq): ?>
            <div class="faq-item" id="faq-a<?=$i?>">
                <button class="faq-question" onclick="toggleFAQ('a<?=$i?>')">
                    <?php echo htmlspecialchars($faq[0]); ?>
                    <svg class="faq-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="faq-answer" id="ans-a<?=$i?>"><p><?php echo htmlspecialchars($faq[1]); ?></p></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top:40px;text-align:center;padding:28px;background:white;border-radius:16px;border:1px solid #e0e7ff;">
            <p style="font-size:15px;color:#475569;font-weight:500;">Still have a question?</p>
            <p style="font-size:13px;color:#94a3b8;margin:6px 0 16px;">Our support team is happy to help.</p>
            <a href="contact.php" style="display:inline-block;background:linear-gradient(135deg,#2563eb,#1d4ed8);color:white;padding:10px 28px;border-radius:10px;font-weight:600;font-size:14px;text-decoration:none;">Contact Support</a>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved. &nbsp;|&nbsp;
    <a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="contact.php">Contact</a></p>
</footer>

<script>
function switchTab(tab) {
    document.querySelectorAll('.faq-group').forEach(g => g.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.target.classList.add('active');
}
function toggleFAQ(id) {
    const item = document.getElementById('faq-' + id);
    const ans  = document.getElementById('ans-' + id);
    const isOpen = item.classList.contains('open');
    // Close all
    document.querySelectorAll('.faq-item.open').forEach(i => {
        i.classList.remove('open');
        i.querySelector('.faq-answer').classList.remove('open');
    });
    if (!isOpen) {
        item.classList.add('open');
        ans.classList.add('open');
    }
}
</script>
</body>
</html>