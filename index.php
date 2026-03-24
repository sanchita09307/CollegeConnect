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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($settings['site_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Lexend', sans-serif;
            overflow-x: hidden;
        }

        /* ==================== ANIMATIONS ==================== */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        @keyframes bounce-soft {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(37, 99, 235, 0.2);
            }
            50% {
                box-shadow: 0 0 30px rgba(37, 99, 235, 0.4);
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

        .animate-fade-in-down {
            animation: fadeInDown 0.6s ease-out forwards;
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out forwards;
        }

        .animate-slide-in-left {
            animation: slideInLeft 0.6s ease-out forwards;
        }

        .animate-slide-in-right {
            animation: slideInRight 0.6s ease-out forwards;
        }

        .animate-float {
            animation: float 4s ease-in-out infinite;
        }

        .animate-bounce-soft {
            animation: bounce-soft 2s ease-in-out infinite;
        }

        .animate-glow {
            animation: glow 3s ease-in-out infinite;
        }

        /* Stagger delays */
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }
        .delay-500 { animation-delay: 0.5s; }

        /* ==================== HEADER STYLES ==================== */
        .navbar {
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        }

        .navbar.scrolled {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .logo {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }

        /* ==================== BUTTON STYLES ==================== */
        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary::before {
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

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }

        .btn-primary:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-secondary {
            background: white;
            color: #2563eb;
            padding: 10px 24px;
            border-radius: 10px;
            font-weight: 600;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            border-color: #2563eb;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.15);
            background: #f0f9ff;
        }

        /* ==================== MOBILE MENU ==================== */
        .mobile-menu {
            position: fixed;
            left: 0;
            top: 64px;
            width: 100%;
            background: white;
            border-top: 1px solid #e2e8f0;
            max-height: calc(100vh - 64px);
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 40;
        }

        .mobile-menu.hidden {
            display: none;
        }

        .mobile-menu.show {
            display: block;
            animation: slideInDown 0.3s ease;
        }

        .mobile-menu a {
            display: block;
            padding: 12px 16px;
            color: #475569;
            text-decoration: none;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 500;
            transition: all 0.2s;
        }

        .mobile-menu a:hover {
            background: #f0f9ff;
            color: #2563eb;
            padding-left: 20px;
        }

        /* ==================== HERO SECTION ==================== */
        .hero {
            background: linear-gradient(135deg, #ffffff 0%, #f0f9ff 100%);
            padding-top: 80px;
        }

        .hero-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        @media (min-width: 768px) {
            .hero-content {
                flex-direction: row;
                align-items: center;
                gap: 40px;
            }
        }

        .hero-text {
            flex: 1;
        }

        .hero-text h1 {
            font-size: 28px;
            line-height: 1.3;
            color: #1e293b;
            margin-bottom: 20px;
            font-weight: 700;
        }

        @media (min-width: 768px) {
            .hero-text h1 {
                font-size: 40px;
            }
        }

        @media (min-width: 1024px) {
            .hero-text h1 {
                font-size: 48px;
            }
        }

        .hero-text p {
            font-size: 15px;
            line-height: 1.7;
            color: #64748b;
            margin-bottom: 25px;
        }

        @media (min-width: 768px) {
            .hero-text p {
                font-size: 16px;
            }
        }

        .hero-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 25px;
        }

        @media (min-width: 640px) {
            .hero-buttons {
                flex-direction: row;
                gap: 15px;
            }
        }

        .hero-buttons .btn-primary,
        .hero-buttons .btn-secondary {
            width: 100%;
            padding: 12px 20px;
            font-size: 15px;
        }

        @media (min-width: 640px) {
            .hero-buttons .btn-primary,
            .hero-buttons .btn-secondary {
                width: auto;
            }
        }

        .hero-badges {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        @media (min-width: 640px) {
            .hero-badges {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 15px;
            }
        }

        .badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #475569;
            font-weight: 500;
        }

        .badge svg {
            width: 18px;
            height: 18px;
            color: #2563eb;
            flex-shrink: 0;
        }

        .hero-visual {
            flex: 1;
            position: relative;
            min-height: 300px;
            display: none;
        }

        @media (min-width: 768px) {
            .hero-visual {
                display: block;
            }
        }

        .floating-card {
            position: absolute;
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid #e0e7ff;
        }

        .floating-card-1 {
            top: 20px;
            right: 0;
            width: 280px;
            animation: float 4s ease-in-out infinite;
        }

        .floating-card-2 {
            bottom: 40px;
            left: 0;
            width: 280px;
            animation: float 4s ease-in-out infinite;
            animation-delay: 1s;
        }

        .floating-card h4 {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
        }

        .floating-card p {
            font-size: 13px;
            color: #64748b;
            line-height: 1.5;
        }

        .floating-card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .floating-card-icon svg {
            width: 20px;
            height: 20px;
            color: #2563eb;
        }

        /* ==================== FEATURES SECTION ==================== */
        .features {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.5) 0%, rgba(240, 249, 255, 0.5) 100%);
            padding: 40px 0;
            border-top: 1px solid #e0e7ff;
            border-bottom: 1px solid #e0e7ff;
        }

        @media (min-width: 768px) {
            .features {
                padding: 60px 0;
            }
        }

        .features-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .features-header h2 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        @media (min-width: 768px) {
            .features-header h2 {
                font-size: 36px;
            }
        }

        .features-header p {
            font-size: 14px;
            color: #64748b;
            max-width: 500px;
            margin: 0 auto;
        }

        @media (min-width: 768px) {
            .features-header p {
                font-size: 16px;
            }
        }

        .features-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        @media (min-width: 768px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 24px;
            }
        }

        @media (min-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 28px;
            }
        }

        .feature-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #e0e7ff;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @media (min-width: 768px) {
            .feature-card {
                padding: 32px;
            }
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(37, 99, 235, 0.15);
            border-color: #2563eb;
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 16px rgba(37, 99, 235, 0.3);
        }

        .feature-icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }

        .feature-card h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
            transition: color 0.3s ease;
        }

        .feature-card:hover h3 {
            color: #2563eb;
        }

        .feature-card p {
            font-size: 14px;
            color: #64748b;
            line-height: 1.7;
        }

        /* ==================== CTA SECTION ==================== */
        .cta {
            padding: 40px 0;
        }

        @media (min-width: 768px) {
            .cta {
                padding: 60px 0;
            }
        }

        .cta-content {
            text-align: center;
        }

        .cta-content h2 {
            font-size: 26px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
        }

        @media (min-width: 768px) {
            .cta-content h2 {
                font-size: 36px;
            }
        }

        .cta-content p {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        @media (min-width: 768px) {
            .cta-content p {
                font-size: 16px;
            }
        }

        .cta-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            justify-content: center;
        }

        @media (min-width: 640px) {
            .cta-buttons {
                flex-direction: row;
                gap: 15px;
            }
        }

        .cta-buttons .btn-primary,
        .cta-buttons .btn-secondary {
            width: 100%;
            padding: 12px 24px;
            font-size: 15px;
        }

        @media (min-width: 640px) {
            .cta-buttons .btn-primary,
            .cta-buttons .btn-secondary {
                width: auto;
            }
        }

        /* ==================== FOOTER ==================== */
        footer {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #cbd5e1;
            padding: 40px 0 20px;
        }

        @media (min-width: 768px) {
            footer {
                padding: 60px 0 30px;
            }
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (min-width: 768px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 40px;
            }
        }

        @media (min-width: 1024px) {
            .footer-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .footer-section h4 {
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
            font-size: 15px;
        }

        .footer-section p {
            font-size: 13px;
            line-height: 1.6;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 8px;
        }

        .footer-section a {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
        }

        .footer-section a:hover {
            color: #60a5fa;
        }

        .footer-bottom {
            border-top: 1px solid #334155;
            padding-top: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: center;
            font-size: 13px;
        }

        @media (min-width: 768px) {
            .footer-bottom {
                flex-direction: row;
                justify-content: space-between;
                text-align: left;
            }
        }

        .footer-social {
            display: flex;
            gap: 16px;
            justify-content: center;
        }

        @media (min-width: 768px) {
            .footer-social {
                justify-content: flex-end;
            }
        }

        .footer-social a {
            transition: all 0.3s;
        }

        .footer-social a:hover {
            color: #60a5fa;
            transform: translateY(-3px);
        }

        /* ==================== UTILITY ==================== */
        .container {
            width: 100%;
            padding: 0 16px;
            margin: 0 auto;
        }

        @media (min-width: 640px) {
            .container {
                padding: 0 24px;
            }
        }

        @media (min-width: 768px) {
            .container {
                max-width: 768px;
            }
        }

        @media (min-width: 1024px) {
            .container {
                max-width: 1024px;
                padding: 0 32px;
            }
        }

        @media (min-width: 1280px) {
            .container {
                max-width: 1280px;
            }
        }

        .text-gradient {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ==================== SCROLL ANIMATIONS ==================== */
        .scroll-animate {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .scroll-animate.in-view {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="bg-gradient-to-b from-slate-50 via-white to-blue-50">

    <!-- Header Navigation -->
    <header class="navbar fixed top-0 left-0 right-0 z-50 w-full">
        <div class="container flex items-center justify-between h-16">
            <!-- Logo -->
            <a href="index.php" class="logo text-lg md:text-2xl font-bold animate-fade-in-down">
                <?php echo htmlspecialchars($settings['site_name']); ?>
            </a>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center gap-6">
                <a href="login.php" class="text-slate-600 hover:text-blue-600 font-medium text-sm transition duration-300 animate-fade-in-down delay-100">
                    Login
                </a>
                <a href="student_register.php" class="text-slate-600 hover:text-blue-600 font-medium text-sm transition duration-300 animate-fade-in-down delay-200">
                    Student Register
                </a>
                <a href="teacher_register.php" class="text-slate-600 hover:text-blue-600 font-medium text-sm transition duration-300 animate-fade-in-down delay-300">
                    Teacher Register
                </a>
            </nav>

            <!-- Mobile Menu Button -->
            <button id="mobile-menu-btn" class="md:hidden focus:outline-none p-2" aria-label="Toggle menu">
                <svg class="w-6 h-6 text-slate-700 transition-transform duration-300" id="menu-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation -->
        <nav id="mobile-menu" class="mobile-menu hidden">
            <a href="login.php" class="animate-fade-in-up delay-100">Login</a>
            <a href="student_register.php" class="animate-fade-in-up delay-200">Student Register</a>
            <a href="teacher_register.php" class="animate-fade-in-up delay-300">Teacher Register</a>
        </nav>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container py-12 md:py-20 lg:py-28">
                <div class="hero-content">
                    <!-- Text Content -->
                    <div class="hero-text animate-slide-in-left">
                        <h1>
                            Welcome to <span class="text-gradient"><?php echo htmlspecialchars($settings['site_name']); ?></span>
                        </h1>
                        <p>
                            A smart college management platform designed for students, teachers, and administrators. 
                            Seamlessly manage courses, profiles, materials, academics, and announcements all in one powerful place.
                        </p>

                        <!-- Buttons -->
                        <div class="hero-buttons animate-fade-in-up delay-100">
                            <a href="login.php" class="btn-primary">Login Now</a>
                            <a href="student_register.php" class="btn-secondary">Student Signup</a>
                        </div>

                        <!-- Badges -->
                        <div class="hero-badges animate-fade-in-up delay-200">
                            <div class="badge">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Secure & Reliable</span>
                            </div>
                            <div class="badge">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>24/7 Support</span>
                            </div>
                            <div class="badge">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span>Easy to Use</span>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Cards -->
                    <div class="hero-visual animate-fade-in-up delay-300">
                        <!-- Card 1 -->
                        <div class="floating-card floating-card-1">
                            <div class="floating-card-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path>
                                </svg>
                            </div>
                            <h4>500+ Active Users</h4>
                            <p>Join our growing community of students and educators</p>
                        </div>

                        <!-- Card 2 -->
                        <div class="floating-card floating-card-2">
                            <div class="floating-card-icon">
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                </svg>
                            </div>
                            <h4>50+ Courses</h4>
                            <p>Explore diverse academic programs and materials</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="container">
                <!-- Section Header -->
                <div class="features-header animate-fade-in-down">
                    <h2>Portal Features</h2>
                    <p>Everything you need to succeed in your academic journey</p>
                </div>

                <!-- Features Grid -->
                <div class="features-grid">
                    <!-- Feature 1 -->
                    <div class="feature-card scroll-animate animate-fade-in-up delay-100">
                        <div class="feature-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                        </div>
                        <h3>Student Dashboard</h3>
                        <p>Manage your profile, access course materials, view grades, track assignments, and stay updated with important announcements.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="feature-card scroll-animate animate-fade-in-up delay-200">
                        <div class="feature-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.5 1.5H5.75A2.25 2.25 0 003.5 3.75v12.5A2.25 2.25 0 005.75 18.5h8.5a2.25 2.25 0 002.25-2.25V6.5m-11-4v4m8-4v4M3.5 9.5h13"></path>
                            </svg>
                        </div>
                        <h3>Teacher Portal</h3>
                        <p>Create and manage classes, upload course materials, post assignments, track student progress, and communicate efficiently.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="feature-card scroll-animate animate-fade-in-up delay-300">
                        <div class="feature-icon">
                            <svg fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 17v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3>Admin Panel</h3>
                        <p>Approve users, manage settings, monitor platform activities, handle academic records, and generate detailed reports.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="container">
                <div class="cta-content animate-fade-in-up">
                    <h2>Ready to Get Started?</h2>
                    <p>Join thousands of students and teachers using <?php echo htmlspecialchars($settings['site_name']); ?> to enhance their academic experience.</p>
                    <div class="cta-buttons">
                        <a href="student_register.php" class="btn-primary">Register as Student</a>
                        <a href="teacher_register.php" class="btn-secondary">Register as Teacher</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1 -->
                <div class="footer-section scroll-animate">
                    <h4><?php echo htmlspecialchars($settings['site_name']); ?></h4>
                    <p>Smart college management platform for modern education. Empowering students, teachers, and administrators.</p>
                </div>

                <!-- Column 2 -->
                <div class="footer-section scroll-animate">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="student_register.php">Student Register</a></li>
                        <li><a href="teacher_register.php">Teacher Register</a></li>
                    </ul>
                </div>

                <!-- Column 3 -->
                <div class="footer-section scroll-animate">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="help_center.php">Help Center</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>

                <!-- Column 4 -->
                <div class="footer-section scroll-animate">
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; 2026 <?php echo htmlspecialchars($settings['site_name']); ?>. All rights reserved.</p>
                <div class="footer-social">
                    <a href="#" title="Twitter">Twitter</a>
                    <a href="#" title="Facebook">Facebook</a>
                    <a href="#" title="LinkedIn">LinkedIn</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // ==================== MOBILE MENU TOGGLE ====================
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');

        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('show');
            menuIcon.style.transform = mobileMenu.classList.contains('show') ? 'rotate(90deg)' : 'rotate(0)';
        });

        // Close menu when a link is clicked
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', function() {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('show');
                menuIcon.style.transform = 'rotate(0)';
            });
        });

        // ==================== NAVBAR SCROLL EFFECT ====================
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // ==================== SCROLL ANIMATIONS ====================
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('in-view');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-animate').forEach(el => {
            observer.observe(el);
        });

        // ==================== SMOOTH SCROLL ====================
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // ==================== PREVENT BODY SCROLL WHEN MENU OPEN ====================
        function toggleBodyScroll(disable) {
            if (disable) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        mobileMenuBtn.addEventListener('click', function() {
            const isMenuOpen = !mobileMenu.classList.contains('hidden');
            toggleBodyScroll(isMenuOpen);
        });
    </script>
</body>
</html>
