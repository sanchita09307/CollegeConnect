<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/settings_helper.php';

$settings = getSiteSettings($conn);

if (!empty($settings['maintenance_mode'])) {
    echo $settings['maintenance_message'];
    exit();
}
?>
<!DOCTYPE html>
<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#2d3436",
                        "accent": "#444bcf",
                        "background-light": "#f8f9fa",
                        "background-dark": "#0f172a",
                    },
                    fontFamily: {
                        "display": ["Lexend", "sans-serif"]
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
<style type="text/tailwindcss">
        body {
            font-family: 'Lexend', sans-serif;
        }
        .ios-shadow {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .form-input-container {
            @apply flex flex-col gap-1.5 px-6 py-2;
        }
        .form-label {
            @apply text-[#1e293b] dark:text-gray-200 text-sm font-semibold leading-normal px-1;
        }
    </style>

<title>Teacher Registration - <?php echo htmlspecialchars($settings['site_name']); ?></title>

<style>
    body {
      min-height: max(884px, 100dvh);
    }

    /* ==================== ANIMATIONS ==================== */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-25px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(25px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-25px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.92);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes pulse-soft {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(68, 75, 207, 0.3);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(68, 75, 207, 0);
        }
    }

    @keyframes float-soft {
        0%, 100% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-5px);
        }
    }

    @keyframes rotate-icon {
        0% {
            transform: rotate(0deg) scale(1);
        }
        50% {
            transform: rotate(10deg) scale(1.05);
        }
        100% {
            transform: rotate(0deg) scale(1);
        }
    }

    @keyframes glow-border {
        0%, 100% {
            border-color: #e2e8f0;
            box-shadow: 0 0 0 0 rgba(68, 75, 207, 0.1);
        }
        50% {
            border-color: #444bcf;
            box-shadow: 0 0 0 3px rgba(68, 75, 207, 0.1);
        }
    }

    @keyframes slide-in-left {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes bounce-up {
        0% {
            transform: translateY(15px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }

    @keyframes glow-pulse {
        0%, 100% {
            box-shadow: 0 0 20px rgba(68, 75, 207, 0.2);
        }
        50% {
            box-shadow: 0 0 30px rgba(68, 75, 207, 0.4);
        }
    }

    /* ==================== ANIMATION CLASSES ==================== */
    .animate-slide-in-down {
        animation: slideInDown 0.6s ease-out forwards;
    }

    .animate-slide-in-up {
        animation: slideInUp 0.6s ease-out forwards;
    }

    .animate-slide-in-left {
        animation: slideInLeft 0.6s ease-out forwards;
    }

    .animate-fade-in-scale {
        animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .animate-pulse-soft {
        animation: pulse-soft 2s ease-in-out infinite;
    }

    .animate-float-soft {
        animation: float-soft 3s ease-in-out infinite;
    }

    .animate-rotate-icon {
        animation: rotate-icon 3s ease-in-out infinite;
    }

    .animate-glow-border {
        animation: glow-border 2s ease-in-out infinite;
    }

    .animate-bounce-up {
        animation: bounce-up 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .animate-glow-pulse {
        animation: glow-pulse 3s ease-in-out infinite;
    }

    /* ==================== STAGGER DELAYS ==================== */
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }
    .delay-600 { animation-delay: 0.6s; }
    .delay-700 { animation-delay: 0.7s; }
    .delay-800 { animation-delay: 0.8s; }

    /* ==================== INPUT ANIMATIONS ==================== */
    .form-input,
    .form-select {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .form-input:focus,
    .form-select:focus {
        animation: glow-border 0.4s ease-out;
    }

    .form-input::placeholder {
        color: #94a3b8;
    }

    .form-input:focus {
        background: linear-gradient(to right, #ffffff 0%, #f5f1ff 100%);
    }

    /* ==================== BUTTON ANIMATIONS ==================== */
    .btn-register {
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-register::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .btn-register:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-register:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(45, 52, 54, 0.3);
    }

    .btn-register:active {
        transform: scale(0.98);
    }

    /* ==================== FORM GROUP ANIMATIONS ==================== */
    .form-group {
        opacity: 0;
        animation: slideInUp 0.6s ease-out forwards;
    }

    /* ==================== HEADER ANIMATION ==================== */
    .header-container {
        animation: slideInDown 0.5s ease-out forwards;
    }

    /* ==================== ICON ANIMATIONS ==================== */
    .icon-container {
        animation: fadeInScale 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .icon-container:hover .material-symbols-outlined {
        animation: rotate-icon 0.6s ease-in-out;
    }

    .input-icon {
        transition: all 0.3s ease;
    }

    .form-input:focus ~ .input-icon,
    .form-select:focus ~ .input-icon {
        color: #444bcf;
        transform: scale(1.15);
    }

    /* ==================== TEXT ANIMATIONS ==================== */
    .heading-main {
        animation: slideInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .heading-sub {
        animation: slideInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .back-link-container {
        animation: slideInUp 0.7s ease-out forwards;
        opacity: 0;
    }

    .back-link-container a {
        position: relative;
        transition: all 0.3s ease;
    }

    .back-link-container a::before {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 0;
        height: 2px;
        background: #444bcf;
        transition: width 0.3s ease;
    }

    .back-link-container a:hover::before {
        width: 100%;
    }

    /* ==================== FOOTER ANIMATION ==================== */
    .footer-text {
        animation: slideInUp 0.8s ease-out forwards;
        opacity: 0;
    }

    .footer-badge {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        animation: glow-pulse 3s ease-in-out infinite;
    }

    /* ==================== RESPONSIVE ANIMATIONS ==================== */
    @media (max-width: 640px) {
        .form-input,
        .form-select {
            height: 48px;
            font-size: 16px;
        }

        .btn-register {
            padding: 12px 16px;
            font-size: 15px;
        }
    }

    /* ==================== SMOOTH TRANSITIONS ==================== */
    * {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }

    input[type="text"]:focus,
    input[type="email"]:focus,
    input[type="tel"]:focus,
    input[type="password"]:focus,
    select:focus {
        background-color: rgba(68, 75, 207, 0.02);
    }

    /* ==================== SELECT DROPDOWN STYLING ==================== */
    .form-select {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23444bcf'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 20px;
        padding-right: 40px;
    }

    /* ==================== BACK BUTTON ICON ANIMATION ==================== */
    .back-icon {
        transition: all 0.3s ease;
    }

    .back-link-container a:hover .back-icon {
        transform: translateX(-4px);
    }
</style>

</head>
<body class="bg-gradient-to-br from-background-light via-white to-blue-50 dark:from-background-dark dark:to-slate-900 min-h-screen flex flex-col">

<!-- Header/Navigation -->
<div class="header-container flex items-center bg-white/80 dark:bg-background-dark/80 backdrop-blur-md p-4 pb-2 justify-between sticky top-0 z-10 border-b border-gray-100 dark:border-gray-800">
    <div class="text-accent flex size-10 shrink-0 items-center justify-start cursor-pointer animate-slide-in-left">
        <a href="login.php" class="hover:scale-110 transition-transform duration-300">
            <span class="material-symbols-outlined text-2xl">arrow_back_ios</span>
        </a>
    </div>
    <h2 class="animate-slide-in-down text-primary dark:text-white text-lg font-bold leading-tight tracking-tight flex-1 text-center pr-10">Faculty Portal</h2>
</div>

<!-- Main Content -->
<div class="flex-1 max-w-[480px] mx-auto w-full flex flex-col">

<!-- Icon & Title Section -->
<div class="px-6 pt-8 pb-4 flex flex-col items-center">
    <div class="icon-container w-20 h-20 rounded-full bg-gradient-to-br from-accent/10 to-accent/5 flex items-center justify-center mb-6 cursor-pointer hover:animate-float-soft">
        <span class="material-symbols-outlined text-accent text-4xl animate-rotate-icon" style="font-variation-settings: 'FILL' 1">school</span>
    </div>
    <h1 class="heading-main delay-100 text-primary dark:text-white text-2xl md:text-3xl font-bold leading-tight tracking-tight text-center">Faculty Registration</h1>
    <p class="heading-sub delay-200 text-[#64748b] dark:text-gray-400 text-sm font-normal leading-relaxed pt-3 text-center px-4">Create your official educator account to manage courses and monitor student progress effectively.</p>
</div>

<!-- Registration Form -->
<form id="teacherForm" action="teacher_register_process.php" method="POST" class="flex flex-col pb-10">

    <!-- Full Name Field -->
    <div class="form-group form-input-container delay-100">
        <label class="flex flex-col w-full">
            <p class="form-label">Full Name</p>
            <div class="relative group">
                <span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#94a3b8] text-xl">person</span>
                <input name="name" type="text" class="form-input flex w-full rounded-xl text-primary dark:text-white border border-[#e2e8f0] dark:border-gray-700 bg-white dark:bg-[#1e293b] h-12 md:h-14 placeholder:text-[#94a3b8] pl-12 pr-4 text-base focus:ring-2 focus:ring-accent/50 focus:border-accent outline-none transition-all" placeholder="e.g. Dr. Sarah Jenkins" required/>
            </div>
        </label>
    </div>

    <!-- Email Field -->
    <div class="form-group form-input-container delay-200">
        <label class="flex flex-col w-full">
            <p class="form-label">Faculty Email</p>
            <div class="relative group">
                <span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#94a3b8] text-xl">mail</span>
                <input name="email" type="email" class="form-input flex w-full rounded-xl text-primary dark:text-white border border-[#e2e8f0] dark:border-gray-700 bg-white dark:bg-[#1e293b] h-12 md:h-14 placeholder:text-[#94a3b8] pl-12 pr-4 text-base focus:ring-2 focus:ring-accent/50 focus:border-accent outline-none transition-all" placeholder="s.jenkins@college.edu" required/>
            </div>
        </label>
    </div>

    <!-- Department Field -->
    <div class="form-group form-input-container delay-300">
        <label class="flex flex-col w-full">
            <p class="form-label">Department</p>
            <div class="relative group">
                <span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#94a3b8] text-xl">account_tree</span>
                <select name="department" class="form-select flex w-full rounded-xl text-primary dark:text-white border border-[#e2e8f0] dark:border-gray-700 bg-white dark:bg-[#1e293b] h-12 md:h-14 pl-12 pr-10 text-base focus:ring-2 focus:ring-accent/50 focus:border-accent outline-none transition-all appearance-none" required>
                    <option disabled="" selected="" value="">Select Department</option>
                    <option value="CO">Computer</option>
                    <option value="IT">Information Technology</option>
                    <option value="EE">Electrical</option>
                    <option value="ME">Mechanical</option>
                    <option value="AE">Automobile</option>
                    <option value="CE">Civil</option>
                </select>
                <span class="material-symbols-outlined absolute right-4 top-1/2 -translate-y-1/2 text-[#94a3b8] pointer-events-none">expand_more</span>
            </div>
        </label>
    </div>

    <!-- Designation Field -->
    <div class="form-group form-input-container delay-400">
        <label class="flex flex-col w-full">
            <p class="form-label">Designation</p>
            <div class="relative group">
                <span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#94a3b8] text-xl">workspace_premium</span>
                <input name="designation" type="text" class="form-input flex w-full rounded-xl text-primary dark:text-white border border-[#e2e8f0] dark:border-gray-700 bg-white dark:bg-[#1e293b] h-12 md:h-14 placeholder:text-[#94a3b8] pl-12 pr-4 text-base focus:ring-2 focus:ring-accent/50 focus:border-accent outline-none transition-all" placeholder="e.g. Professor, Lecturer, Assistant Professor" required/>
            </div>
        </label>
    </div>

    <!-- Phone Number Field -->
    <div class="form-group form-input-container delay-500">
        <label class="flex flex-col w-full">
            <p class="form-label">Phone Number</p>
            <div class="relative group">
                <span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#94a3b8] text-xl">call</span>
                <input name="phone" type="tel" class="form-input flex w-full rounded-xl text-primary dark:text-white border border-[#e2e8f0] dark:border-gray-700 bg-white dark:bg-[#1e293b] h-12 md:h-14 placeholder:text-[#94a3b8] pl-12 pr-4 text-base focus:ring-2 focus:ring-accent/50 focus:border-accent outline-none transition-all" placeholder="+1 (555) 000-0000" required/>
            </div>
        </label>
    </div>

    <!-- Password Field -->
    <div class="form-group form-input-container delay-600">
        <label class="flex flex-col w-full">
            <p class="form-label">Create Password</p>
            <div class="relative group">
                <span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#94a3b8] text-xl">lock</span>
                <input name="password" id="password-input" type="password" class="form-input flex w-full rounded-xl text-primary dark:text-white border border-[#e2e8f0] dark:border-gray-700 bg-white dark:bg-[#1e293b] h-12 md:h-14 placeholder:text-[#94a3b8] pl-12 pr-12 text-base focus:ring-2 focus:ring-accent/50 focus:border-accent outline-none transition-all" placeholder="Set a strong password (min. 8 characters)" required/>
                <div class="absolute inset-y-0 right-0 flex items-center px-4 text-[#94a3b8] cursor-pointer hover:text-accent transition-colors" id="toggle-password">
                    <span class="material-symbols-outlined text-xl">visibility_off</span>
                </div>
            </div>
        </label>
    </div>

    <!-- Register Button -->
    <div class="form-group delay-700 px-6 pt-6 pb-4">
        <button class="btn-register w-full bg-primary text-white font-bold py-4 rounded-xl text-lg ios-shadow active:scale-[0.98] transition-transform relative z-10 flex items-center justify-center gap-2" type="submit">
            <span>Register as Teacher</span>
            <span class="material-symbols-outlined text-xl">arrow_forward</span>
        </button>
    </div>

</form>

<!-- Back to Login Link -->
<div class="back-link-container delay-700 text-center py-4 px-6">
    <a class="text-accent font-semibold hover:text-opacity-80 transition-all flex items-center justify-center gap-2 group" href="login.php">
        <span class="material-symbols-outlined text-lg back-icon">arrow_back</span>
        <span>Back to Login</span>
    </a>
</div>

</div>

<!-- Footer -->
<div class="mt-auto px-6 pb-12 text-center opacity-70">
    <div class="footer-badge">
        <span class="material-symbols-outlined text-sm">verified_user</span>
        <p class="footer-text delay-700 text-[10px] text-primary dark:text-gray-400 uppercase tracking-widest font-bold">Official Faculty Registration System</p>
    </div>
</div>

<div class="h-8 bg-background-light dark:bg-background-dark"></div>

<!-- JavaScript for Interactions -->
<script>
    // ==================== PASSWORD VISIBILITY TOGGLE ====================
    const togglePasswordBtn = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password-input');

    togglePasswordBtn.addEventListener('click', function() {
        const icon = this.querySelector('.material-symbols-outlined');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.textContent = 'visibility';
        } else {
            passwordInput.type = 'password';
            icon.textContent = 'visibility_off';
        }

        // Add animation
        this.style.animation = 'rotate-icon 0.6s ease-in-out';
        setTimeout(() => {
            this.style.animation = '';
        }, 600);
    });

    // ==================== FORM VALIDATION WITH ANIMATION ====================
    const form = document.getElementById('teacherForm');
    
    form.addEventListener('submit', function(e) {
        const inputs = form.querySelectorAll('.form-input, .form-select');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('border-red-500');
                input.parentElement.style.animation = 'pulse-soft 0.6s ease-in-out';
                
                setTimeout(() => {
                    input.classList.remove('border-red-500');
                }, 1500);
            }
        });

        // Email validation
        const emailInput = form.querySelector('input[type="email"]');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailInput.value && !emailRegex.test(emailInput.value)) {
            isValid = false;
            emailInput.classList.add('border-red-500');
        }

        // Password validation (min 8 characters)
        const passwordInput2 = form.querySelector('input[type="password"]');
        if (passwordInput2.value && passwordInput2.value.length < 8) {
            isValid = false;
            passwordInput2.classList.add('border-red-500');
        }

        if (!isValid) {
            e.preventDefault();
        }
    });

    // ==================== INPUT FOCUS ANIMATIONS ====================
    const inputs = document.querySelectorAll('.form-input, .form-select');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.parentElement.style.transform = 'scale(1.01)';
        });

        input.addEventListener('blur', function() {
            this.parentElement.parentElement.style.transform = 'scale(1)';
        });
    });

    // ==================== REAL-TIME VALIDATION ====================
    document.querySelector('input[type="email"]').addEventListener('input', function() {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (this.value.trim() && !emailRegex.test(this.value)) {
            this.classList.add('border-yellow-400');
        } else {
            this.classList.remove('border-yellow-400');
        }
    });

    document.querySelector('input[type="password"]').addEventListener('input', function() {
        if (this.value.length < 8 && this.value.length > 0) {
            this.classList.add('border-yellow-400');
        } else {
            this.classList.remove('border-yellow-400');
        }
    });

    // ==================== SMOOTH PAGE TRANSITIONS ====================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Teacher registration form loaded with animations');
    });

    // ==================== PARALLAX EFFECT ON SCROLL ====================
    window.addEventListener('scroll', function() {
        const iconContainer = document.querySelector('.icon-container');
        const scrollPosition = window.scrollY;
        
        if (iconContainer) {
            iconContainer.style.transform = `translateY(${scrollPosition * 0.3}px)`;
        }
    });

    // ==================== FOCUS MANAGEMENT ====================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            const focusedElement = document.activeElement;
            if (focusedElement && focusedElement.classList.contains('form-input')) {
                focusedElement.parentElement.style.animation = 'glow-border 0.3s ease';
            }
        }
    });
</script>

</body></html>
