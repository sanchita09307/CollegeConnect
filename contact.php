<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/settings_helper.php';
$settings = getSiteSettings($conn);
if (!empty($settings['maintenance_mode'])) { echo $settings['maintenance_message']; exit(); }

$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $subject && $message) {
        // Save to DB or send email — adjust as needed
        // For now, just show success
        $success = 'Thank you! Your message has been received. We will get back to you within 24 hours.';
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us – <?php echo htmlspecialchars($settings['site_name']); ?></title>
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
        .form-card{background:white;border-radius:20px;border:1px solid #e0e7ff;padding:36px;max-width:620px;}
        label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
        input,textarea,select{width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-family:'Lexend',sans-serif;font-size:14px;color:#1e293b;transition:border 0.2s,box-shadow 0.2s;outline:none;}
        input:focus,textarea:focus,select:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1);}
        textarea{resize:vertical;min-height:120px;}
        .btn-submit{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:white;padding:12px 32px;border-radius:10px;font-weight:600;font-size:15px;border:none;cursor:pointer;width:100%;transition:all 0.3s;}
        .btn-submit:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(37,99,235,0.35);}
        .alert-success{background:#f0fdf4;border:1px solid #86efac;color:#166534;padding:14px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;}
        .alert-error{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b;padding:14px 18px;border-radius:10px;font-size:14px;margin-bottom:20px;}

        .info-card{background:white;border-radius:16px;border:1px solid #e0e7ff;padding:24px;margin-bottom:16px;}
        .info-card h4{font-size:15px;font-weight:700;color:#1e293b;margin-bottom:6px;}
        .info-card p{font-size:13px;color:#64748b;line-height:1.6;}

        .two-col{display:grid;grid-template-columns:1fr;gap:40px;align-items:start;}
        @media(min-width:768px){.two-col{grid-template-columns:1fr 1fr;}}

        footer{background:linear-gradient(135deg,#0f172a,#1e293b);color:#cbd5e1;padding:30px 0;text-align:center;font-size:13px;}
        footer a{color:#60a5fa;text-decoration:none;margin:0 8px;}
        .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        @media(max-width:640px){.form-row{grid-template-columns:1fr;}}
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
    <h1>Contact Us</h1>
    <p>Have a question or need help? We're here for you.</p>
</div>

<main class="section">
    <div class="container">
        <div class="two-col">
            <!-- Form -->
            <div class="form-card">
                <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:22px;">Send us a Message</h2>

                <?php if($success): ?>
                    <div class="alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php elseif($error): ?>
                    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row" style="margin-bottom:16px;">
                        <div>
                            <label>Your Name</label>
                            <input type="text" name="name" placeholder="Rahul Sharma" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                        </div>
                        <div>
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="rahul@example.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div style="margin-bottom:16px;">
                        <label>Subject</label>
                        <select name="subject">
                            <option value="">Select a topic...</option>
                            <option value="Student Support" <?php echo (($_POST['subject']??'')=='Student Support')?'selected':''; ?>>Student Support</option>
                            <option value="Teacher Support" <?php echo (($_POST['subject']??'')=='Teacher Support')?'selected':''; ?>>Teacher Support</option>
                            <option value="Account Issue" <?php echo (($_POST['subject']??'')=='Account Issue')?'selected':''; ?>>Account Issue</option>
                            <option value="Technical Problem" <?php echo (($_POST['subject']??'')=='Technical Problem')?'selected':''; ?>>Technical Problem</option>
                            <option value="Other" <?php echo (($_POST['subject']??'')=='Other')?'selected':''; ?>>Other</option>
                        </select>
                    </div>
                    <div style="margin-bottom:22px;">
                        <label>Message</label>
                        <textarea name="message" placeholder="Describe your issue or question in detail..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn-submit">Send Message</button>
                </form>
            </div>

            <!-- Info -->
            <div>
                <div class="info-card">
                    <h4>📧 Email Support</h4>
                    <p>support@collegeconnect.edu<br>We respond within 24 hours on working days.</p>
                </div>
                <div class="info-card">
                    <h4>🕐 Support Hours</h4>
                    <p>Monday – Friday: 9:00 AM – 6:00 PM<br>Saturday: 10:00 AM – 2:00 PM<br>Sunday: Closed</p>
                </div>
                <div class="info-card">
                    <h4>📚 Self-Help Resources</h4>
                    <p>Check our <a href="faq.php" style="color:#2563eb;font-weight:600;">FAQ page</a> for quick answers, or visit the <a href="help_center.php" style="color:#2563eb;font-weight:600;">Help Center</a> for guides.</p>
                </div>
                <div class="info-card">
                    <h4>🏫 About <?php echo htmlspecialchars($settings['site_name']); ?></h4>
                    <p>CollegeConnect is a smart academic management platform serving students, teachers, and administrators across colleges.</p>
                </div>
            </div>
        </div>
    </div>
</main>

<footer>
    <p>&copy; 2024 <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved. &nbsp;|&nbsp;
    <a href="privacy.php">Privacy</a><a href="terms.php">Terms</a><a href="help_center.php">Help Center</a></p>
</footer>
</body>
</html>