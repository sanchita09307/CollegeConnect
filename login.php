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
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Login - <?php echo htmlspecialchars($settings['site_name']); ?></title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#444bcf",
                        "background-light": "#f6f6f8",
                        "background-dark": "#13141f",
                    },
                    fontFamily: {
                        display: ["Lexend", "sans-serif"]
                    },
                    borderRadius: {
                        DEFAULT: "0.75rem",
                        lg: "1rem",
                        xl: "1.25rem",
                        full: "9999px"
                    }
                }
            }
        }
    </script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Lexend', sans-serif;
            overflow-x: hidden;
        }

        /* ==================== BACKGROUND ANIMATIONS ==================== */
        @keyframes gradient-shift {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
        }

        @keyframes blob {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(30px, -50px) scale(1.1);
            }
            66% {
                transform: translate(-20px, 20px) scale(0.9);
            }
        }

        @keyframes blob-2 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            33% {
                transform: translate(-30px, 50px) scale(0.9);
            }
            66% {
                transform: translate(20px, -20px) scale(1.1);
            }
        }

        @keyframes slide-in-down {
            from {
                opacity: 0;
                transform: translateY(-40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slide-in-up {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fade-in {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes scale-in {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
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

        @keyframes pulse-ring {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(68, 75, 207, 0.4);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(68, 75, 207, 0);
            }
        }

        @keyframes rotate-icon {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
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

        @keyframes input-focus {
            0% {
                transform: scale(0.98);
            }
            50% {
                transform: scale(1.01);
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes bounce-up {
            0% {
                transform: translateY(10px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* ==================== ANIMATION CLASSES ==================== */
        .animate-gradient {
            background-size: 200% 200%;
            animation: gradient-shift 6s ease infinite;
        }

        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        .animate-blob {
            animation: blob 7s infinite;
        }

        .animate-blob-2 {
            animation: blob-2 7s infinite;
            animation-delay: 2s;
        }

        .animate-slide-in-down {
            animation: slide-in-down 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .animate-slide-in-up {
            animation: slide-in-up 0.8s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .animate-fade-in {
            animation: fade-in 0.6s ease-in;
        }

        .animate-scale-in {
            animation: scale-in 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .animate-pulse-ring {
            animation: pulse-ring 2s infinite;
        }

        .animate-rotate-icon {
            animation: rotate-icon 3s linear infinite;
        }

        .animate-glow-border {
            animation: glow-border 3s ease-in-out infinite;
        }

        .animate-input-focus {
            animation: input-focus 0.4s ease;
        }

        .animate-bounce-up {
            animation: bounce-up 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        /* ==================== STAGGER DELAYS ==================== */
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }
        .delay-600 { animation-delay: 0.6s; }

        /* ==================== BACKGROUND ELEMENTS ==================== */
        .bg-blob {
            position: absolute;
            border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            opacity: 0.1;
        }

        .blob-1 {
            width: 300px;
            height: 300px;
            background: #444bcf;
            top: -50px;
            right: -100px;
            animation: blob 7s infinite;
        }

        .blob-2 {
            width: 200px;
            height: 200px;
            background: #444bcf;
            bottom: -50px;
            left: -50px;
            animation: blob-2 7s infinite;
        }

        /* ==================== CARD STYLES ==================== */
        .login-card {
            position: relative;
            z-index: 10;
            border: 1px solid rgba(226, 232, 240, 0.8);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.9) 100%);
            backdrop-filter: blur(10px);
        }

        .login-card:hover {
            border-color: rgba(68, 75, 207, 0.3);
            box-shadow: 0 20px 50px rgba(68, 75, 207, 0.1);
        }

        /* ==================== INPUT STYLES ==================== */
        .form-input {
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: linear-gradient(to right, #fff 0%, #f9f9fb 100%);
        }

        .form-input::placeholder {
            color: #cbd5e1;
        }

        .form-input:focus {
            background: linear-gradient(to right, #fff 0%, #f5f1ff 100%);
            border-color: #444bcf;
            box-shadow: 0 0 0 3px rgba(68, 75, 207, 0.1), inset 0 1px 3px rgba(68, 75, 207, 0.05);
            animation: input-focus 0.4s ease;
        }

        .form-input:hover:not(:focus) {
            border-color: #cbd5e1;
            background: linear-gradient(to right, #fff 0%, #f9f7ff 100%);
        }

        select.form-input {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23444bcf'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
            padding-right: 40px;
        }

        /* ==================== LABEL STYLES ==================== */
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #1e293b;
            margin-bottom: 8px;
            transition: color 0.2s;
        }

        .form-input:focus ~ .form-label,
        .form-input:not(:placeholder-shown) ~ .form-label {
            color: #444bcf;
        }

        /* ==================== BUTTON STYLES ==================== */
        .btn-login {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #444bcf 0%, #3b3fbb 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 600;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(68, 75, 207, 0.3);
        }

        .btn-login::before {
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

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(68, 75, 207, 0.4);
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-login:active {
            transform: scale(0.98);
        }

        .btn-login:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ==================== LINK STYLES ==================== */
        .register-link {
            position: relative;
            color: #444bcf;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .register-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #444bcf;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .register-link:hover::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .register-link:hover {
            color: #3b3fbb;
        }

        /* ==================== TEXT ANIMATIONS ==================== */
        .text-animate {
            opacity: 0;
            animation: slide-in-up 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .form-group {
            opacity: 0;
            animation: bounce-up 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* ==================== RESPONSIVE ==================== */
        @media (max-width: 640px) {
            .blob-1, .blob-2 {
                width: 150px;
                height: 150px;
                opacity: 0.05;
            }

            .login-card {
                border-radius: 20px;
                padding: 24px 16px;
            }

            .form-input {
                padding: 12px 16px;
                font-size: 16px;
            }

            .btn-login {
                padding: 12px 16px;
                font-size: 15px;
            }
        }

        /* ==================== ICON ANIMATIONS ==================== */
        .icon-lock {
            display: inline-block;
            transition: all 0.3s ease;
        }

        .login-card:hover .icon-lock {
            animation: rotate-icon 0.6s ease-in-out;
        }

        /* ==================== FORM VALIDATION ==================== */
        .form-input.error {
            border-color: #ef4444;
            animation: pulse-ring 0.6s ease;
        }

        .error-message {
            display: none;
            color: #ef4444;
            font-size: 12px;
            margin-top: 4px;
            animation: slide-in-down 0.3s ease;
        }

        .form-input.error ~ .error-message {
            display: block;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-background-light via-white to-blue-50 min-h-screen flex items-center justify-center px-4 py-8">

    <!-- Animated Background Blobs -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="bg-blob blob-1"></div>
        <div class="bg-blob blob-2"></div>
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-md animate-scale-in">
        <!-- Login Card -->
        <div class="login-card shadow-2xl rounded-3xl p-8 md:p-10 animate-slide-in-up">
            
            <!-- Header -->
            <div class="text-center mb-8 animate-slide-in-down">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 mb-4 animate-float">
                    <span class="material-symbols-outlined text-3xl text-primary">lock</span>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-slate-800 text-animate">
                    <?php echo htmlspecialchars($settings['site_name']); ?>
                </h1>
                <p class="text-slate-500 mt-3 text-sm md:text-base text-animate delay-100">
                    Sign in to continue
                </p>
            </div>

            <!-- Form -->
            <form id="loginForm" action="login_process.php" method="POST" class="space-y-5 md:space-y-6">
                
                <!-- Email Input -->
                <div class="form-group delay-200">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">mail</span>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            required
                            class="form-input w-full rounded-xl border border-slate-300 px-4 pl-12 py-3 md:py-4 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
                            placeholder="your@email.com"
                        >
                        <span class="error-message">Please enter a valid email</span>
                    </div>
                </div>

                <!-- Password Input -->
                <div class="form-group delay-300">
                    <label for="password" class="form-label">Password</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">password</span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="form-input w-full rounded-xl border border-slate-300 px-4 pl-12 py-3 md:py-4 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
                            placeholder="Enter your password"
                        >
                        <button
                            type="button"
                            id="togglePassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-primary transition-colors"
                        >
                            <span class="material-symbols-outlined text-xl">visibility_off</span>
                        </button>
                        <span class="error-message">Password is required</span>
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="form-group delay-400">
                    <label for="role" class="form-label">Login As</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xl">person_outline</span>
                        <select
                            id="role"
                            name="role"
                            required
                            class="form-input w-full rounded-xl border border-slate-300 px-4 pl-12 py-3 md:py-4 focus:outline-none focus:ring-2 focus:ring-primary/50 transition-all"
                        >
                            <option value="">Select your role</option>
                            <option value="Student">Student</option>
                            <option value="Teacher">Teacher</option>
                            <option value="Admin">Admin</option>
                        </select>
                        <span class="error-message">Please select a role</span>
                    </div>
                </div>

                <!-- Login Button -->
                <button
                    type="submit"
                    class="btn-login w-full bg-primary text-white py-3 md:py-4 rounded-xl font-semibold hover:shadow-lg transition-all mt-6 md:mt-8 form-group delay-500 relative z-10"
                >
                    <span class="flex items-center justify-center gap-2">
                        <span>Sign In</span>
                        <span class="material-symbols-outlined text-xl">arrow_forward</span>
                    </span>
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6 md:my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-slate-500">New here?</span>
                </div>
            </div>

            <!-- Register Links -->
            <div class="space-y-3 text-center form-group delay-600">
                <p class="text-sm text-slate-600">
                    Don't have a Student account?
                    <a href="student_register.php" class="register-link">Register Now</a>
                </p>
                <p class="text-sm text-slate-600">
                    Don't have a Teacher account?
                    <a href="teacher_register.php" class="register-link">Register Now</a>
                </p>
            </div>

            <!-- Footer Note -->
            <p class="text-center text-xs text-slate-400 mt-6 md:mt-8 form-group delay-700">
                Secure login • Protected by encryption
            </p>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // ==================== PASSWORD VISIBILITY TOGGLE ====================
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

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

        // ==================== FORM VALIDATION ====================
        const loginForm = document.getElementById('loginForm');
        const emailInput = document.getElementById('email');
        const passwordInput2 = document.getElementById('password');
        const roleInput = document.getElementById('role');

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Reset errors
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error');
            });

            let isValid = true;

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailInput.value.trim()) {
                emailInput.classList.add('error');
                isValid = false;
            } else if (!emailRegex.test(emailInput.value)) {
                emailInput.classList.add('error');
                isValid = false;
            }

            // Password validation
            if (!passwordInput2.value.trim()) {
                passwordInput2.classList.add('error');
                isValid = false;
            }

            // Role validation
            if (!roleInput.value) {
                roleInput.classList.add('error');
                isValid = false;
            }

            if (isValid) {
                // Add submit animation
                const btn = loginForm.querySelector('.btn-login');
                btn.style.animation = 'pulse-ring 0.6s ease';
                
                setTimeout(() => {
                    loginForm.submit();
                }, 300);
            }
        });

        // ==================== INPUT FOCUS ANIMATIONS ====================
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.style.transform = 'scale(1.02)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.parentElement.style.transform = 'scale(1)';
            });
        });

        // ==================== REAL-TIME VALIDATION ====================
        emailInput.addEventListener('input', function() {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (this.value.trim() && !emailRegex.test(this.value)) {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });

        passwordInput2.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('error');
            }
        });

        roleInput.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('error');
            }
        });

        // ==================== PAGE LOAD ANIMATION ====================
        window.addEventListener('load', function() {
            console.log('Login page loaded with animations');
        });

        // ==================== HOVER EFFECTS ====================
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('mouseenter', function() {
                if (document.activeElement !== this) {
                    this.style.borderColor = '#cbd5e1';
                }
            });

            input.addEventListener('mouseleave', function() {
                if (document.activeElement !== this) {
                    this.style.borderColor = '#e2e8f0';
                }
            });
        });
    </script>

</body>
</html>
