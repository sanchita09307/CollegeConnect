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
                        "primary": "#444bcf",
                        "background-light": "#f6f6f8",
                        "background-dark": "#13141f",
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
<style type="text/tailwindcss">body {
    font-family: "Lexend", sans-serif
    }
.ios-shadow {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)
    }
.illustration-container {
    background: linear-gradient(135deg, rgba(68, 75, 207, 0.1) 0%, rgba(68, 75, 207, 0.02) 100%)
    }
select {
    appearance: none;
    background-image: url(https://lh3.googleusercontent.com/aida-public/AB6AXuAP5F2P2FUp5LBx7rdVX0ijPniyNZjGqbSyfgmi_3QwjshskFY_hh29WQlsroRm3gCB_qEnG9ydApFsIUy3ADWyBpON6F1D0ou6uymsIdgiWid7oDOPDIUXnN1qteG69PGawtno2CP47KKGgS60Fwh4lO1wtWrJbr3-e4BxIrC9mZ6dcFrGu8WrX9kw1fLvDL_zzSp2Lpxuxo7NS9WbkM-iryoOykMF9pVGz99RMkPvIiT3mAqlsd-5U1QHsDQvOr_43xfgX4MmI6PC);
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1.25em
    }</style>

<title>Student Registration - <?php echo htmlspecialchars($settings['site_name']); ?></title>

<style>
    body {
      min-height: max(884px, 100dvh);
    }

    /* ==================== ANIMATIONS ==================== */
    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInScale {
        from {
            opacity: 0;
            transform: scale(0.9);
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

    @keyframes shimmer {
        0% {
            background-position: -1000px 0;
        }
        100% {
            background-position: 1000px 0;
        }
    }

    @keyframes rotate-icon {
        0% {
            transform: rotate(0deg) scale(1);
        }
        50% {
            transform: rotate(5deg) scale(1.05);
        }
        100% {
            transform: rotate(0deg) scale(1);
        }
    }

    @keyframes glow-border {
        0%, 100% {
            border-color: #d3d4e4;
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

    /* ==================== ANIMATION CLASSES ==================== */
    .animate-slide-in-down {
        animation: slideInDown 0.5s ease-out forwards;
    }

    .animate-slide-in-up {
        animation: slideInUp 0.6s ease-out forwards;
    }

    .animate-fade-in-scale {
        animation: fadeInScale 0.6s ease-out forwards;
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

    .animate-slide-in-left {
        animation: slide-in-left 0.6s ease-out forwards;
    }

    /* ==================== STAGGER DELAYS ==================== */
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
    .delay-500 { animation-delay: 0.5s; }
    .delay-600 { animation-delay: 0.6s; }
    .delay-700 { animation-delay: 0.7s; }

    /* ==================== INPUT FOCUS ANIMATIONS ==================== */
    .form-input,
    .form-select {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .form-input:focus,
    .form-select:focus {
        animation: glow-border 0.4s ease-out;
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
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(68, 75, 207, 0.4);
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

    /* ==================== ICON CONTAINER ANIMATION ==================== */
    .icon-container {
        animation: fadeInScale 0.7s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }

    .icon-container:hover .material-symbols-outlined {
        animation: rotate-icon 0.6s ease-in-out;
    }

    /* ==================== HEADING ANIMATIONS ==================== */
    .heading-main {
        animation: slideInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .heading-sub {
        animation: slideInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    /* ==================== LOGIN LINK ANIMATION ==================== */
    .login-link-container {
        animation: slideInUp 0.7s ease-out forwards;
        opacity: 0;
    }

    .login-link-container a:hover {
        animation: pulse-soft 0.6s ease-in-out;
    }

    /* ==================== FOOTER ANIMATION ==================== */
    .footer-text {
        animation: slideInUp 0.8s ease-out forwards;
        opacity: 0;
    }

    /* ==================== RESPONSIVE ANIMATIONS ==================== */
    @media (max-width: 640px) {
        .form-input,
        .form-select {
            height: 52px;
        }

        .btn-register {
            padding: 12px 16px;
            font-size: 16px;
        }
    }

    /* ==================== ICON ANIMATIONS IN INPUTS ==================== */
    .input-icon {
        transition: all 0.3s ease;
    }

    .form-input:focus ~ .input-icon,
    .form-select:focus ~ .input-icon {
        color: #444bcf;
        transform: scale(1.1);
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
</style>

</head>
<body class="bg-background-light dark:bg-background-dark min-h-screen flex flex-col">

<!-- Header -->
<div class="header-container flex items-center bg-background-light dark:bg-background-dark p-4 pb-2 justify-between sticky top-0 z-10">
<div class="text-primary flex size-12 shrink-0 items-center justify-start cursor-pointer animate-slide-in-left">
<a href="login.php">
<span class="material-symbols-outlined text-2xl transition-transform duration-300 hover:scale-110 hover:-translate-x-1">arrow_back_ios</span>
</a>
</div>
<h2 class="animate-slide-in-down text-[#101019] dark:text-white text-lg font-bold leading-tight tracking-[-0.015em] flex-1 text-center pr-12">Student Registration</h2>
</div>

<!-- Main Content -->
<div class="flex-1 max-w-[480px] mx-auto w-full flex flex-col">

<!-- Icon & Title Section -->
<div class="px-6 pt-4 pb-4 flex flex-col items-center">
<div class="icon-container illustration-container w-24 h-24 rounded-full flex items-center justify-center mb-4 cursor-pointer hover:animate-float-soft">
<span class="material-symbols-outlined text-primary text-5xl animate-rotate-icon" style="font-variation-settings: 'FILL' 1">person_add</span>
</div>
<h1 class="heading-main delay-100 text-[#101019] dark:text-white text-[24px] font-bold leading-tight tracking-[-0.015em] text-center">Create Account</h1>
<p class="heading-sub delay-200 text-[#575a8e] dark:text-gray-400 text-sm font-normal leading-normal pt-1 text-center px-4">Join CollegeConnect to manage your academic journey.</p>
</div>

<!-- Registration Form -->
<form action="register_process.php" method="POST" class="flex flex-col gap-1 pb-10">

<!-- Full Name Field -->
<div class="form-group delay-100 flex flex-col gap-2 px-6 py-2">
<label class="flex flex-col w-full">
<p class="text-[#101019] dark:text-white text-sm font-semibold leading-normal pb-2 px-1">Full Name</p>
<div class="relative group">
<span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#575a8e] text-xl">person</span>
<input name="full_name" type="text" class="form-input flex w-full rounded-xl text-[#101019] dark:text-white border border-[#d3d4e4] dark:border-gray-700 bg-white dark:bg-[#1e1f2b] h-14 placeholder:text-[#575a8e] pl-12 pr-4 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" placeholder="Enter your full name" required/>
</div>
</label>
</div>

<!-- Email Field -->
<div class="form-group delay-200 flex flex-col gap-2 px-6 py-2">
<label class="flex flex-col w-full">
<p class="text-[#101019] dark:text-white text-sm font-semibold leading-normal pb-2 px-1">Student Email</p>
<div class="relative group">
<span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#575a8e] text-xl">mail</span>
<input name="email" type="email" class="form-input flex w-full rounded-xl text-[#101019] dark:text-white border border-[#d3d4e4] dark:border-gray-700 bg-white dark:bg-[#1e1f2b] h-14 placeholder:text-[#575a8e] pl-12 pr-4 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" placeholder="name@university.edu" required/>
</div>
</label>
</div>

<!-- Department & Batch Year Fields -->
<div class="grid grid-cols-2 gap-0">
<div class="form-group delay-300 flex flex-col gap-2 pl-6 pr-2 py-2">
<label class="flex flex-col w-full">
<p class="text-[#101019] dark:text-white text-sm font-semibold leading-normal pb-2 px-1">Department</p>
<div class="relative group">
<span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#575a8e] text-xl">account_balance</span>
<select name="department" class="form-select flex w-full rounded-xl text-[#101019] dark:text-white border border-[#d3d4e4] dark:border-gray-700 bg-white dark:bg-[#1e1f2b] h-14 pl-12 pr-10 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" required>
<option disabled="" selected="" value="">Select</option>
<option value="CO">Computer</option>
<option value="IT">Information Technology</option>
<option value="EE">Electrical</option>
<option value="ME">Mechanical</option>
<option value="AE">Automobile</option>
<option value="CE">Civil</option>
</select>
</div>
</label>
</div>

<div class="form-group delay-400 flex flex-col gap-2 pr-6 pl-2 py-2">
<label class="flex flex-col w-full">
<p class="text-[#101019] dark:text-white text-sm font-semibold leading-normal pb-2 px-1">Batch/Year</p>
<div class="relative group">
<span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#575a8e] text-xl">event</span>
<input name="batch_year" type="text" placeholder="2026" class="form-input flex w-full rounded-xl text-[#101019] dark:text-white border border-[#d3d4e4] dark:border-gray-700 bg-white dark:bg-[#1e1f2b] h-14 placeholder:text-[#575a8e] pl-12 pr-4 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" required/>
</div>
</label>
</div>
</div>

<!-- Phone Number Field -->
<div class="form-group delay-500 flex flex-col gap-2 px-6 py-2">
<label class="flex flex-col w-full">
<p class="text-[#101019] dark:text-white text-sm font-semibold leading-normal pb-2 px-1">Phone Number</p>
<div class="relative group">
<span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#575a8e] text-xl">call</span>
<input name="phone" type="tel" class="form-input flex w-full rounded-xl text-[#101019] dark:text-white border border-[#d3d4e4] dark:border-gray-700 bg-white dark:bg-[#1e1f2b] h-14 placeholder:text-[#575a8e] pl-12 pr-4 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" placeholder="+1 (555) 000-0000" required/>
</div>
</label>
</div>

<!-- Password Field -->
<div class="form-group delay-600 flex flex-col gap-2 px-6 py-2">
<label class="flex flex-col w-full">
<p class="text-[#101019] dark:text-white text-sm font-semibold leading-normal pb-2 px-1">Create Password</p>
<div class="relative group">
<span class="material-symbols-outlined input-icon absolute left-4 top-1/2 -translate-y-1/2 text-[#575a8e] text-xl">lock</span>
<input name="password" id="password-input" type="password" class="form-input flex w-full rounded-xl text-[#101019] dark:text-white border border-[#d3d4e4] dark:border-gray-700 bg-white dark:bg-[#1e1f2b] h-14 placeholder:text-[#575a8e] pl-12 pr-12 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary outline-none transition-all" placeholder="Min. 8 characters" required/>
<div class="absolute inset-y-0 right-0 flex items-center px-4 text-[#575a8e] cursor-pointer hover:text-primary transition-colors" id="toggle-password">
<span class="material-symbols-outlined">visibility_off</span>
</div>
</div>
</label>
</div>

<!-- Register Button -->
<div class="form-group delay-700 px-6 pt-6 pb-4">
<button class="btn-register w-full bg-primary text-white font-bold py-4 rounded-xl text-lg ios-shadow active:scale-[0.98] transition-transform" type="submit">
                    Register as Student
                </button>
</div>

<!-- Login Link -->
<div class="login-link-container delay-700 text-center py-4">
<p class="text-[#575a8e] dark:text-gray-400 text-sm">
                    Already have an account? 
                    <a class="text-primary font-bold hover:underline hover:text-opacity-80 transition-all" href="login.php">Login</a>
</p>
</div>

</form>

</div>

<!-- Footer -->
<div class="mt-auto px-6 pb-8 text-center opacity-50">
<p class="footer-text delay-700 text-[10px] text-[#575a8e] dark:text-gray-500 uppercase tracking-widest font-bold">Authenticated Student Portal</p>
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
        
        // Add pulse animation on toggle
        this.style.animation = 'pulse-soft 0.6s ease-in-out';
        setTimeout(() => {
            this.style.animation = '';
        }, 600);
    });

    // ==================== FORM VALIDATION WITH ANIMATION ====================
    const form = document.querySelector('form');
    
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

        if (!isValid) {
            e.preventDefault();
        }
    });

    // ==================== INPUT FOCUS ANIMATIONS ====================
    const inputs = document.querySelectorAll('.form-input, .form-select');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.01)';
        });

        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });

    // ==================== SMOOTH PAGE TRANSITIONS ====================
    document.addEventListener('DOMContentLoaded', function() {
        // Elements are already animated via CSS
        console.log('Form loaded and ready');
    });

    // ==================== PARALLAX EFFECT ON SCROLL ====================
    window.addEventListener('scroll', function() {
        const iconContainer = document.querySelector('.icon-container');
        const scrollPosition = window.scrollY;
        
        if (iconContainer) {
            iconContainer.style.transform = `translateY(${scrollPosition * 0.3}px)`;
        }
    });
</script>

</body></html>
