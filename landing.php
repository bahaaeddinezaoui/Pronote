<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

// If already authenticated, send user to their home
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        header('Location: admin_home.php');
    } else {
        header('Location: fill_form.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app_name'); ?> - <?php echo t('title_suffix'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            color: #333;
            line-height: 1.6;
        }

        /* Navigation Bar */
        .navbar {
            background: #fff;
            padding: 1rem 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: 700;
            color: #6f42c1;
            text-decoration: none;
        }

        .navbar-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .navbar-links a {
            text-decoration: none;
            color: #6b7280;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .navbar-links a:hover {
            color: #6f42c1;
        }

        .btn-login {
            background: #6f42c1;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-login:hover {
            background: #5a34a1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.3);
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #6f42c1 0%, #8c63d9 100%);
            color: white;
            padding: 80px 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(30px); }
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .hero p {
            font-size: 18px;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .hero-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: white;
            color: #6f42c1;
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 12px 30px;
            border: 2px solid white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .btn-secondary:hover {
            background: white;
            color: #6f42c1;
            transform: translateY(-3px);
        }

        /* Features Section */
        .features {
            max-width: 1200px;
            margin: 80px auto;
            padding: 0 2rem;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 50px;
            color: #1f1f1f;
        }

        .section-subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 30px;
            font-size: 16px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 80px;
        }

        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-align: center;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 24px rgba(111, 66, 193, 0.15);
            border-color: #6f42c1;
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .feature-card h3 {
            font-size: 20px;
            margin-bottom: 12px;
            color: #1f1f1f;
        }

        .feature-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }

        /* Tutorial Section */
        .tutorial {
            background: #fff;
            padding: 80px 2rem;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
        }

        .tutorial-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .tutorial-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .tutorial-text h3 {
            font-size: 28px;
            margin-bottom: 20px;
            color: #1f1f1f;
        }

        .tutorial-steps {
            list-style: none;
            counter-reset: step-counter;
        }

        .tutorial-steps li {
            margin-bottom: 25px;
            padding-left: 60px;
            position: relative;
            counter-increment: step-counter;
        }

        .tutorial-steps li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            background: #6f42c1;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }

        .tutorial-steps li strong {
            display: block;
            color: #1f1f1f;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .tutorial-steps li span {
            color: #6b7280;
            font-size: 14px;
        }

        .tutorial-visual {
            background: linear-gradient(135deg, #f0e6ff 0%, #e6d9ff 100%);
            border-radius: 12px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 400px;
            text-align: center;
        }

        .tutorial-visual-icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .tutorial-visual h4 {
            color: #6f42c1;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .tutorial-visual p {
            color: #6b7280;
            font-size: 14px;
        }

        /* Use Cases Section */
        .use-cases {
            max-width: 1200px;
            margin: 80px auto;
            padding: 0 2rem;
        }

        .use-cases-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .use-case-card {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 30px;
            border-radius: 12px;
            border-left: 4px solid #6f42c1;
            transition: all 0.3s ease;
        }

        .use-case-card:hover {
            transform: translateX(10px);
            box-shadow: 0 8px 16px rgba(111, 66, 193, 0.1);
        }

        .use-case-card h4 {
            color: #6f42c1;
            font-size: 18px;
            margin-bottom: 12px;
        }

        .use-case-card p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
        }

        /* CTA Section */
        .cta {
            background: linear-gradient(135deg, #6f42c1 0%, #8c63d9 100%);
            color: white;
            padding: 60px 2rem;
            text-align: center;
        }

        .cta-content {
            max-width: 600px;
            margin: 0 auto;
        }

        .cta h2 {
            font-size: 32px;
            margin-bottom: 20px;
        }

        .cta p {
            font-size: 16px;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        /* Footer */
        .footer {
            background: #1f1f1f;
            color: #fff;
            padding: 40px 2rem;
            text-align: center;
        }

        .footer p {
            margin-bottom: 10px;
            font-size: 14px;
            color: #9ca3af;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .footer-links a {
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #6f42c1;
        }

        .lang-switcher{display:inline-flex;align-items:center;gap:.25rem;font-size:.875rem}.lang-switcher a{color:#6b7280;text-decoration:none;padding:.25rem .5rem}.lang-switcher a:hover{color:#6f42c1}.lang-switcher a.active{color:#6f42c1;font-weight:600}.lang-sep{color:#9ca3af}

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }

            .section-title {
                font-size: 28px;
            }

            .tutorial-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .navbar-links {
                gap: 1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary, .btn-secondary {
                width: 100%;
                max-width: 250px;
            }
        }
        /* ============================================
           EFFECTS & TRANSITIONS
           ============================================ */
        
        /* Page Load Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        /* Scroll Reveal Base State */
        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-left {
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal-left.active {
            opacity: 1;
            transform: translateX(0);
        }

        .reveal-right {
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal-right.active {
            opacity: 1;
            transform: translateX(0);
        }

        .reveal-scale {
            opacity: 0;
            transform: scale(0.8);
            transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .reveal-scale.active {
            opacity: 1;
            transform: scale(1);
        }

        /* Stagger Delays */
        .stagger-1 { transition-delay: 0.1s; }
        .stagger-2 { transition-delay: 0.2s; }
        .stagger-3 { transition-delay: 0.3s; }
        .stagger-4 { transition-delay: 0.4s; }
        .stagger-5 { transition-delay: 0.5s; }
        .stagger-6 { transition-delay: 0.6s; }
        .stagger-7 { transition-delay: 0.7s; }
        .stagger-8 { transition-delay: 0.8s; }

        /* Enhanced Card Hover Effects */
        .feature-card {
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(111, 66, 193, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .feature-card:hover::before {
            left: 100%;
        }

        .feature-card .feature-icon {
            transition: transform 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.2) rotate(5deg);
        }

        /* Enhanced Button Effects */
        .btn-primary, .btn-secondary, .btn-login {
            position: relative;
            overflow: hidden;
        }

        .btn-primary::after, .btn-secondary::after, .btn-login::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.4s, height 0.4s;
        }

        .btn-primary:active::after, .btn-secondary:active::after, .btn-login:active::after {
            width: 200%;
            height: 200%;
        }

        /* Interactive Tutorial Styles */
        .tutorial-step {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .tutorial-step:hover {
            border-color: #6f42c1;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(111, 66, 193, 0.1);
        }

        .tutorial-step.active {
            border-color: #6f42c1;
            background: #fff;
            box-shadow: 0 8px 24px rgba(111, 66, 193, 0.15);
        }

        .tutorial-step.completed {
            border-color: #10b981;
            background: #ecfdf5;
        }

        .tutorial-step.completed::after {
            content: '✓';
            position: absolute;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }

        .tutorial-step-header {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .tutorial-step-number {
            background: #6f42c1;
            color: white;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
            transition: all 0.3s ease;
        }

        .tutorial-step.completed .tutorial-step-number {
            background: #10b981;
        }

        .tutorial-step-title {
            font-weight: 700;
            color: #1f1f1f;
            font-size: 16px;
        }

        .tutorial-step-preview {
            color: #6b7280;
            font-size: 14px;
            margin-top: 4px;
        }

        .tutorial-step-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease, padding 0.4s ease;
            margin-left: 60px;
        }

        .tutorial-step.active .tutorial-step-content {
            max-height: 500px;
            padding-top: 16px;
        }

        .tutorial-step-details {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .tutorial-step-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #6f42c1;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.3s ease;
        }

        .tutorial-step-action:hover {
            background: #5a34a1;
            transform: translateY(-2px);
        }

        /* Progress Bar */
        .tutorial-progress {
            background: #f3f4f6;
            border-radius: 10px;
            height: 8px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .tutorial-progress-bar {
            background: linear-gradient(90deg, #6f42c1, #8c63d9);
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
            width: 0%;
        }

        .tutorial-progress-text {
            text-align: center;
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        /* Demo Simulator */
        .demo-simulator {
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 16px;
            padding: 24px;
            margin-top: 20px;
            display: none;
        }

        .demo-simulator.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }

        .demo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .demo-title {
            font-weight: 700;
            color: #1f1f1f;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .demo-close {
            background: #f3f4f6;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .demo-close:hover {
            background: #e5e7eb;
        }

        .demo-screen {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
            min-height: 200px;
        }

        .demo-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 12px;
            transition: border-color 0.3s ease;
        }

        .demo-input:focus {
            outline: none;
            border-color: #6f42c1;
        }

        .demo-button {
            background: #6f42c1;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .demo-button:hover {
            background: #5a34a1;
        }

        .demo-feedback {
            margin-top: 16px;
            padding: 16px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
        }

        .demo-feedback.success {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #10b981;
            display: block;
        }

        .demo-feedback.info {
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #3b82f6;
            display: block;
        }

        /* Interactive Checklist */
        .tutorial-checklist {
            list-style: none;
            margin: 16px 0;
        }

        .tutorial-checklist li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            font-size: 14px;
            color: #4b5563;
        }

        .tutorial-checklist input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #6f42c1;
            cursor: pointer;
        }

        /* Try Demo Button */
        .try-demo-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #6f42c1, #8c63d9);
            color: white;
            padding: 14px 28px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            text-decoration: none;
            margin-bottom: 24px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .try-demo-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(111, 66, 193, 0.3);
        }

        .tutorial-steps li.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .tutorial-steps li::before {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .tutorial-steps li:hover::before {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(111, 66, 193, 0.4);
        }

        /* Use Case Cards Enhanced */
        .use-case-card {
            position: relative;
            overflow: hidden;
        }

        .use-case-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #6f42c1, #8c63d9);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .use-case-card:hover::after {
            transform: scaleX(1);
        }

        /* Navbar Link Hover Effect */
        .navbar-links a:not(.btn-login) {
            position: relative;
        }

        .navbar-links a:not(.btn-login)::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: #6f42c1;
            transition: width 0.3s ease;
        }

        .navbar-links a:not(.btn-login):hover::after {
            width: 100%;
        }

        /* Hero Content Animation on Load */
        .hero-content > * {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .hero-content h1 { animation-delay: 0.1s; }
        .hero-content p { animation-delay: 0.2s; }
        .hero-content .hero-buttons { animation-delay: 0.3s; }

        /* CTA Section Pulse */
        .cta .btn-primary {
            animation: pulse 2s infinite;
        }

        .cta .btn-primary:hover {
            animation: none;
        }

        /* Smooth Scroll */
        html {
            scroll-behavior: smooth;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #6f42c1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #5a34a1;
        }

        /* Reduced Motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            html {
                scroll-behavior: auto;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <a href="#" class="navbar-brand">📚 <?php echo t('app_name'); ?></a>
    <div class="navbar-links">
        <a href="#features"><?php echo t('nav_features'); ?></a>
        <a href="#how-it-works"><?php echo t('nav_how_it_works'); ?></a>
        <a href="#use-cases"><?php echo t('nav_for_teams'); ?></a>
        <?php include __DIR__ . '/lang/switcher.php'; ?>
        <a href="login.php" class="btn-login"><?php echo t('login'); ?></a>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1><?php echo t('hero_title'); ?></h1>
        <p><?php echo t('hero_subtitle'); ?></p>
        <p style="font-size: 16px; opacity: 0.9;"><?php echo t('hero_subtitle2'); ?></p>
        <div class="hero-buttons">
            <a href="login.php" class="btn-primary"><?php echo t('get_started'); ?></a>
            <a href="#features" class="btn-secondary"><?php echo t('learn_more'); ?></a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features" id="features">
    <h2 class="section-title reveal"><?php echo t('section_main_features'); ?></h2>
    <p class="section-subtitle reveal stagger-1"><?php echo t('section_main_features_sub'); ?></p>
    
    <div class="features-grid">
        <div class="feature-card reveal stagger-1">
            <div class="feature-icon">👨‍🎓</div>
            <h3><?php echo t('feature_student_mgmt'); ?></h3>
            <p><?php echo t('feature_student_mgmt_desc'); ?></p>
        </div>

        <div class="feature-card reveal stagger-2">
            <div class="feature-icon">📝</div>
            <h3><?php echo t('feature_observations'); ?></h3>
            <p><?php echo t('feature_observations_desc'); ?></p>
        </div>

        <div class="feature-card reveal stagger-3">
            <div class="feature-icon">❌</div>
            <h3><?php echo t('feature_absences'); ?></h3>
            <p><?php echo t('feature_absences_desc'); ?></p>
        </div>

        <div class="feature-card reveal stagger-4">
            <div class="feature-icon">⏰</div>
            <h3><?php echo t('feature_sessions'); ?></h3>
            <p><?php echo t('feature_sessions_desc'); ?></p>
        </div>

        <div class="feature-card reveal stagger-5">
            <div class="feature-icon">📷</div>
            <h3><?php echo t('feature_photo_mgmt'); ?></h3>
            <p><?php echo t('feature_photo_mgmt_desc'); ?></p>
        </div>

        <div class="feature-card reveal stagger-6">
            <div class="feature-icon">🔔</div>
            <h3><?php echo t('feature_notifications'); ?></h3>
            <p><?php echo t('feature_notifications_desc'); ?></p>
        </div>

        <div class="feature-card reveal stagger-7">
            <div class="feature-icon">🏛️</div>
            <h3><?php echo t('feature_sections'); ?></h3>
            <p><?php echo t('feature_sections_desc'); ?></p>
        </div>

        <div class="feature-card reveal stagger-8">
            <div class="feature-icon">📊</div>
            <h3><?php echo t('feature_dashboard'); ?></h3>
            <p><?php echo t('feature_dashboard_desc'); ?></p>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="tutorial" id="how-it-works">
    <div class="tutorial-container">
        <h2 class="section-title reveal"><?php echo t('section_how_it_works'); ?></h2>
        
        <div class="tutorial-content">
            <div class="tutorial-text reveal-left">
                <h3><?php echo t('quick_start_guide'); ?></h3>
                
                <button class="try-demo-btn" id="tryDemoBtn">
                    <span>🎮</span>
                    <?php echo t('try_demo') ?: 'Try Interactive Demo'; ?>
                </button>

                <div class="demo-simulator" id="demoSimulator">
                    <div class="demo-header">
                        <div class="demo-title">
                            <span>🎯</span>
                            <span id="demoTitle"><?php echo t('demo_login_title') ?: 'Login Demo'; ?></span>
                        </div>
                        <button class="demo-close" id="closeDemo">×</button>
                    </div>
                    <div class="demo-screen" id="demoScreen">
                        <!-- Demo content will be dynamically inserted here -->
                    </div>
                    <div class="demo-feedback" id="demoFeedback"></div>
                </div>

                <div class="tutorial-progress-text" id="progressText">
                    <?php echo t('tutorial_progress') ?: 'Complete all 5 steps to finish the tutorial'; ?>
                </div>
                <div class="tutorial-progress">
                    <div class="tutorial-progress-bar" id="progressBar"></div>
                </div>
                
                <div class="interactive-tutorial" id="interactiveTutorial">
                    <div class="tutorial-step" data-step="1">
                        <div class="tutorial-step-header">
                            <div class="tutorial-step-number">1</div>
                            <div>
                                <div class="tutorial-step-title"><?php echo t('step1_title'); ?></div>
                                <div class="tutorial-step-preview"><?php echo t('step1_preview') ?: 'Click to learn about logging in'; ?></div>
                            </div>
                        </div>
                        <div class="tutorial-step-content">
                            <div class="tutorial-step-details"><?php echo t('step1_desc'); ?></div>
                            <ul class="tutorial-checklist">
                                <li><input type="checkbox" class="tutorial-check" data-check="1"> <?php echo t('check_username') ?: 'Enter your username'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="2"> <?php echo t('check_password') ?: 'Enter your secure password'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="3"> <?php echo t('check_role') ?: 'Select your role (Teacher/Admin)'; ?></li>
                            </ul>
                            <button class="tutorial-step-action" onclick="showDemo('login')">
                                <span>▶</span> <?php echo t('try_it_now') ?: 'Try it now'; ?>
                            </button>
                        </div>
                    </div>

                    <div class="tutorial-step" data-step="2">
                        <div class="tutorial-step-header">
                            <div class="tutorial-step-number">2</div>
                            <div>
                                <div class="tutorial-step-title"><?php echo t('step2_title'); ?></div>
                                <div class="tutorial-step-preview"><?php echo t('step2_preview') ?: 'Explore your personalized dashboard'; ?></div>
                            </div>
                        </div>
                        <div class="tutorial-step-content">
                            <div class="tutorial-step-details"><?php echo t('step2_desc'); ?></div>
                            <ul class="tutorial-checklist">
                                <li><input type="checkbox" class="tutorial-check" data-check="1"> <?php echo t('check_stats') ?: 'View key statistics'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="2"> <?php echo t('check_activities') ?: 'Review recent activities'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="3"> <?php echo t('check_navigation') ?: 'Navigate using the sidebar'; ?></li>
                            </ul>
                            <button class="tutorial-step-action" onclick="showDemo('dashboard')">
                                <span>▶</span> <?php echo t('explore_demo') ?: 'Explore demo'; ?>
                            </button>
                        </div>
                    </div>

                    <div class="tutorial-step" data-step="3">
                        <div class="tutorial-step-header">
                            <div class="tutorial-step-number">3</div>
                            <div>
                                <div class="tutorial-step-title"><?php echo t('step3_title'); ?></div>
                                <div class="tutorial-step-preview"><?php echo t('step3_preview') ?: 'Manage student records'; ?></div>
                            </div>
                        </div>
                        <div class="tutorial-step-content">
                            <div class="tutorial-step-details"><?php echo t('step3_desc'); ?></div>
                            <ul class="tutorial-checklist">
                                <li><input type="checkbox" class="tutorial-check" data-check="1"> <?php echo t('check_observations') ?: 'Create observations'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="2"> <?php echo t('check_absences') ?: 'Record absences'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="3"> <?php echo t('check_student_info') ?: 'Update student information'; ?></li>
                            </ul>
                            <button class="tutorial-step-action" onclick="showDemo('records')">
                                <span>▶</span> <?php echo t('see_how') ?: 'See how it works'; ?>
                            </button>
                        </div>
                    </div>

                    <div class="tutorial-step" data-step="4">
                        <div class="tutorial-step-header">
                            <div class="tutorial-step-number">4</div>
                            <div>
                                <div class="tutorial-step-title"><?php echo t('step4_title'); ?></div>
                                <div class="tutorial-step-preview"><?php echo t('step4_preview') ?: 'Find what you need quickly'; ?></div>
                            </div>
                        </div>
                        <div class="tutorial-step-content">
                            <div class="tutorial-step-details"><?php echo t('step4_desc'); ?></div>
                            <ul class="tutorial-checklist">
                                <li><input type="checkbox" class="tutorial-check" data-check="1"> <?php echo t('check_search_student') ?: 'Search by student name'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="2"> <?php echo t('check_filter_date') ?: 'Filter by date range'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="3"> <?php echo t('check_filter_section') ?: 'Filter by section/major'; ?></li>
                            </ul>
                            <button class="tutorial-step-action" onclick="showDemo('search')">
                                <span>▶</span> <?php echo t('try_search') ?: 'Try search demo'; ?>
                            </button>
                        </div>
                    </div>

                    <div class="tutorial-step" data-step="5">
                        <div class="tutorial-step-header">
                            <div class="tutorial-step-number">5</div>
                            <div>
                                <div class="tutorial-step-title"><?php echo t('step5_title'); ?></div>
                                <div class="tutorial-step-preview"><?php echo t('step5_preview') ?: 'Stay updated with notifications'; ?></div>
                            </div>
                        </div>
                        <div class="tutorial-step-content">
                            <div class="tutorial-step-details"><?php echo t('step5_desc'); ?></div>
                            <ul class="tutorial-checklist">
                                <li><input type="checkbox" class="tutorial-check" data-check="1"> <?php echo t('check_notifications') ?: 'View real-time notifications'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="2"> <?php echo t('check_profile') ?: 'Update your profile'; ?></li>
                                <li><input type="checkbox" class="tutorial-check" data-check="3"> <?php echo t('check_ready') ?: 'Ready to get started!'; ?></li>
                            </ul>
                            <a href="login.php" class="tutorial-step-action" style="text-decoration: none;">
                                <span>🚀</span> <?php echo t('get_started_now') ?: 'Get Started Now'; ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Hidden original list for fallback -->
                <ul class="tutorial-steps" style="display: none;">
                    <li><strong><?php echo t('step1_title'); ?></strong><span><?php echo t('step1_desc'); ?></span></li>
                    <li><strong><?php echo t('step2_title'); ?></strong><span><?php echo t('step2_desc'); ?></span></li>
                    <li><strong><?php echo t('step3_title'); ?></strong><span><?php echo t('step3_desc'); ?></span></li>
                    <li><strong><?php echo t('step4_title'); ?></strong><span><?php echo t('step4_desc'); ?></span></li>
                    <li><strong><?php echo t('step5_title'); ?></strong><span><?php echo t('step5_desc'); ?></span></li>
                </ul>
            </div>

            <div class="tutorial-visual reveal-right">
                <div class="tutorial-visual-icon" id="tutorialIcon">🚀</div>
                <h4 id="tutorialStatus"><?php echo t('ready_to_start'); ?></h4>
                <p id="tutorialStatusDesc"><?php echo t('ready_to_start_desc'); ?></p>
                <div id="completionBadge" style="display: none; margin-top: 20px;">
                    <div style="background: #10b981; color: white; padding: 12px 24px; border-radius: 50px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                        <span>🎉</span> <?php echo t('tutorial_complete') ?: 'Tutorial Complete!'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Use Cases Section -->
<section class="use-cases" id="use-cases">
    <h2 class="section-title reveal"><?php echo t('section_for_roles'); ?></h2>
    <p class="section-subtitle reveal stagger-1"><?php echo t('section_for_roles_sub'); ?></p>
    
    <div class="use-cases-grid">
        <div class="use-case-card reveal stagger-1">
            <h4><?php echo t('for_teachers'); ?></h4>
            <p><?php echo t('for_teachers_desc'); ?></p>
        </div>

        <div class="use-case-card reveal stagger-2">
            <h4><?php echo t('for_admins'); ?></h4>
            <p><?php echo t('for_admins_desc'); ?></p>
        </div>

        <div class="use-case-card reveal stagger-3">
            <h4><?php echo t('for_secretaries'); ?></h4>
            <p><?php echo t('for_secretaries_desc'); ?></p>
        </div>

        <div class="use-case-card reveal stagger-4">
            <h4><?php echo t('analytics_insights'); ?></h4>
            <p><?php echo t('analytics_insights_desc'); ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="cta-content reveal-scale">
        <h2><?php echo t('cta_title'); ?></h2>
        <p><?php echo t('cta_sub'); ?></p>
        <a href="login.php" class="btn-primary" style="background: white; color: #6f42c1;"><?php echo t('login_now'); ?></a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <p><?php echo t('footer_copyright'); ?></p>
    <div class="footer-links">
        <a href="#"><?php echo t('footer_privacy'); ?></a>
        <span>•</span>
        <a href="#"><?php echo t('footer_terms'); ?></a>
        <span>•</span>
        <a href="#"><?php echo t('footer_contact'); ?></a>
    </div>
    <p><?php echo t('footer_tagline'); ?></p>
</footer>

<script>
    // Intersection Observer for Scroll Animations
    (function() {
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -50px 0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe all reveal elements
        document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale').forEach(el => {
            observer.observe(el);
        });

        // Tutorial steps animation
        const stepsObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, index * 150);
                    stepsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.2 });

        document.querySelectorAll('.tutorial-steps li').forEach(li => {
            stepsObserver.observe(li);
        });

        // Button ripple effect
        document.querySelectorAll('.btn-primary, .btn-secondary, .btn-login').forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    background: rgba(255, 255, 255, 0.5);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                    left: ${x}px;
                    top: ${y}px;
                    width: 20px;
                    height: 20px;
                    margin-left: -10px;
                    margin-top: -10px;
                `;

                this.appendChild(ripple);
                setTimeout(() => ripple.remove(), 600);
            });
        });

        // Add ripple keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(15);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Interactive Tutorial System
        (function() {
            const steps = document.querySelectorAll('.tutorial-step');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');
            const completionBadge = document.getElementById('completionBadge');
            const tutorialIcon = document.getElementById('tutorialIcon');
            const tutorialStatus = document.getElementById('tutorialStatus');
            const tutorialStatusDesc = document.getElementById('tutorialStatusDesc');
            const demoSimulator = document.getElementById('demoSimulator');
            const demoScreen = document.getElementById('demoScreen');
            const demoTitle = document.getElementById('demoTitle');
            const demoFeedback = document.getElementById('demoFeedback');
            const tryDemoBtn = document.getElementById('tryDemoBtn');
            const closeDemoBtn = document.getElementById('closeDemo');
            
            let completedSteps = new Set();
            const STORAGE_KEY = 'enote_tutorial_progress';
            
            // Load saved progress
            function loadProgress() {
                try {
                    const saved = localStorage.getItem(STORAGE_KEY);
                    if (saved) {
                        const data = JSON.parse(saved);
                        completedSteps = new Set(data.completedSteps || []);
                        
                        // Restore checkboxes
                        data.checkboxes?.forEach(check => {
                            const checkbox = document.querySelector(`.tutorial-check[data-check="${check.check}"]`);
                            if (checkbox && checkbox.closest('.tutorial-step').dataset.step === check.step) {
                                checkbox.checked = check.checked;
                            }
                        });
                        
                        updateProgress();
                        markCompletedSteps();
                    }
                } catch (e) {
                    console.log('Could not load tutorial progress');
                }
            }
            
            // Save progress
            function saveProgress() {
                try {
                    const checkboxes = [];
                    document.querySelectorAll('.tutorial-check').forEach(cb => {
                        checkboxes.push({
                            step: cb.closest('.tutorial-step').dataset.step,
                            check: cb.dataset.check,
                            checked: cb.checked
                        });
                    });
                    
                    localStorage.setItem(STORAGE_KEY, JSON.stringify({
                        completedSteps: Array.from(completedSteps),
                        checkboxes: checkboxes
                    }));
                } catch (e) {
                    // Ignore storage errors
                }
            }
            
            // Update progress bar
            function updateProgress() {
                const percent = (completedSteps.size / steps.length) * 100;
                progressBar.style.width = percent + '%';
                progressText.textContent = `Step ${completedSteps.size} of ${steps.length} completed`;
                
                if (completedSteps.size === steps.length) {
                    completionBadge.style.display = 'block';
                    tutorialIcon.textContent = '🎉';
                    tutorialStatus.textContent = 'Congratulations!';
                    tutorialStatusDesc.textContent = 'You\'ve completed the tutorial. You\'re ready to use eNote!';
                    progressText.textContent = 'Tutorial Complete! 🎉';
                }
            }
            
            // Mark completed steps visually
            function markCompletedSteps() {
                steps.forEach(step => {
                    const stepNum = step.dataset.step;
                    if (completedSteps.has(stepNum)) {
                        step.classList.add('completed');
                    }
                });
            }
            
            // Check if step is complete (all checkboxes checked)
            function checkStepCompletion(step) {
                const checkboxes = step.querySelectorAll('.tutorial-check');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                
                if (allChecked && checkboxes.length > 0) {
                    step.classList.add('completed');
                    completedSteps.add(step.dataset.step);
                    saveProgress();
                    updateProgress();
                    
                    // Auto-expand next step
                    const nextStep = document.querySelector(`.tutorial-step[data-step="${parseInt(step.dataset.step) + 1}"]`);
                    if (nextStep && !completedSteps.has(nextStep.dataset.step)) {
                        setTimeout(() => {
                            steps.forEach(s => s.classList.remove('active'));
                            nextStep.classList.add('active');
                            nextStep.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 500);
                    }
                }
            }
            
            // Step click handlers
            steps.forEach(step => {
                step.addEventListener('click', function(e) {
                    // Don't collapse if clicking inside content
                    if (e.target.closest('.tutorial-step-content') && !e.target.closest('.tutorial-step-action')) {
                        return;
                    }
                    
                    // Toggle active state
                    const isActive = this.classList.contains('active');
                    steps.forEach(s => s.classList.remove('active'));
                    
                    if (!isActive) {
                        this.classList.add('active');
                    }
                });
            });
            
            // Checkbox change handlers
            document.querySelectorAll('.tutorial-check').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const step = this.closest('.tutorial-step');
                    checkStepCompletion(step);
                    saveProgress();
                });
            });
            
            // Demo Simulator
            window.showDemo = function(type) {
                demoSimulator.classList.add('active');
                demoFeedback.className = 'demo-feedback';
                demoFeedback.style.display = 'none';
                
                switch(type) {
                    case 'login':
                        demoTitle.textContent = 'Login Demo';
                        demoScreen.innerHTML = `
                            <div style="max-width: 300px; margin: 0 auto;">
                                <h4 style="margin-bottom: 20px; color: #6f42c1;">eNote Login</h4>
                                <input type="text" class="demo-input" placeholder="Username" id="demoUsername">
                                <input type="password" class="demo-input" placeholder="Password" id="demoPassword">
                                <select class="demo-input" id="demoRole">
                                    <option value="">Select Role</option>
                                    <option value="teacher">Teacher</option>
                                    <option value="admin">Administrator</option>
                                </select>
                                <button class="demo-button" onclick="simulateLogin()">Login</button>
                            </div>
                        `;
                        break;
                        
                    case 'dashboard':
                        demoTitle.textContent = 'Dashboard Demo';
                        demoScreen.innerHTML = `
                            <div style="text-align: left;">
                                <h4 style="margin-bottom: 16px; color: #6f42c1;">Welcome, Teacher! 👋</h4>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px;">
                                    <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                                        <div style="font-size: 24px; font-weight: 700; color: #6f42c1;">156</div>
                                        <div style="font-size: 12px; color: #6b7280;">Students</div>
                                    </div>
                                    <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                                        <div style="font-size: 24px; font-weight: 700; color: #10b981;">12</div>
                                        <div style="font-size: 12px; color: #6b7280;">Observations</div>
                                    </div>
                                    <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                                        <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">8</div>
                                        <div style="font-size: 12px; color: #6b7280;">Absences</div>
                                    </div>
                                    <div style="background: white; padding: 16px; border-radius: 8px; text-align: center;">
                                        <div style="font-size: 24px; font-weight: 700; color: #3b82f6;">3</div>
                                        <div style="font-size: 12px; color: #6b7280;">Majors</div>
                                    </div>
                                </div>
                                <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 12px;">
                                    <div style="font-size: 12px; color: #6b7280; margin-bottom: 4px;">Recent Activity</div>
                                    <div style="font-size: 14px;">📋 New observation added for Student #1024</div>
                                </div>
                                <p style="font-size: 13px; color: #6b7280; text-align: center;">This is how your personalized dashboard will look!</p>
                            </div>
                        `;
                        break;
                        
                    case 'records':
                        demoTitle.textContent = 'Records Demo';
                        demoScreen.innerHTML = `
                            <div style="text-align: left;">
                                <h4 style="margin-bottom: 16px; color: #6f42c1;">Student Records</h4>
                                <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600;">Ahmed Benali</div>
                                        <div style="font-size: 12px; color: #6b7280;">EOA 1 - Section A</div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="demo-button" style="padding: 6px 12px; font-size: 12px;" onclick="showDemoFeedback('Observation recorded!')">📝</button>
                                        <button class="demo-button" style="padding: 6px 12px; font-size: 12px; background: #f59e0b;" onclick="showDemoFeedback('Absence marked!')">❌</button>
                                    </div>
                                </div>
                                <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <div style="font-weight: 600;">Sarah Mansouri</div>
                                        <div style="font-size: 12px; color: #6b7280;">EOA 1 - Section A</div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <button class="demo-button" style="padding: 6px 12px; font-size: 12px;" onclick="showDemoFeedback('Observation recorded!')">📝</button>
                                        <button class="demo-button" style="padding: 6px 12px; font-size: 12px; background: #f59e0b;" onclick="showDemoFeedback('Absence marked!')">❌</button>
                                    </div>
                                </div>
                                <p style="font-size: 13px; color: #6b7280; text-align: center; margin-top: 16px;">Click the buttons to see how recording works!</p>
                            </div>
                        `;
                        break;
                        
                    case 'search':
                        demoTitle.textContent = 'Search Demo';
                        demoScreen.innerHTML = `
                            <div style="text-align: left;">
                                <h4 style="margin-bottom: 16px; color: #6f42c1;">Search Students</h4>
                                <div style="display: flex; gap: 8px; margin-bottom: 16px;">
                                    <input type="text" class="demo-input" style="margin-bottom: 0;" placeholder="Search by name..." id="demoSearchInput">
                                    <button class="demo-button" onclick="simulateSearch()">🔍</button>
                                </div>
                                <div style="display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap;">
                                    <select class="demo-input" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                                        <option>All Categories</option>
                                        <option>EOA 1</option>
                                        <option>EOA 2</option>
                                    </select>
                                    <select class="demo-input" style="flex: 1; min-width: 120px; margin-bottom: 0;">
                                        <option>All Sections</option>
                                        <option>Section A</option>
                                        <option>Section B</option>
                                    </select>
                                </div>
                                <div id="demoSearchResults"></div>
                            </div>
                        `;
                        break;
                }
                
                demoSimulator.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            };
            
            // Demo simulation functions
            window.simulateLogin = function() {
                const username = document.getElementById('demoUsername')?.value;
                const password = document.getElementById('demoPassword')?.value;
                const role = document.getElementById('demoRole')?.value;
                
                if (username && password && role) {
                    showDemoFeedback('✅ Login successful! Redirecting to dashboard...', 'success');
                    setTimeout(() => {
                        demoScreen.innerHTML = `
                            <div style="text-align: center; padding: 40px;">
                                <div style="font-size: 48px; margin-bottom: 16px;">✅</div>
                                <h4 style="color: #10b981;">Welcome back!</h4>
                                <p style="color: #6b7280; font-size: 14px;">This is a demo. Click "Get Started" to use the real app!</p>
                            </div>
                        `;
                    }, 1500);
                } else {
                    showDemoFeedback('⚠️ Please fill in all fields', 'info');
                }
            };
            
            window.simulateSearch = function() {
                const input = document.getElementById('demoSearchInput');
                const results = document.getElementById('demoSearchResults');
                
                if (input && results) {
                    results.innerHTML = `
                        <div style="background: white; padding: 12px; border-radius: 8px; margin-bottom: 8px;">
                            <div style="font-weight: 600;">Ahmed Benali</div>
                            <div style="font-size: 12px; color: #6b7280;">EOA 1 - Section A • 3 absences • 2 observations</div>
                        </div>
                        <div style="background: white; padding: 12px; border-radius: 8px;">
                            <div style="font-weight: 600;">Ahmed Smith</div>
                            <div style="font-size: 12px; color: #6b7280;">EOA 2 - Section B • 1 absence • 0 observations</div>
                        </div>
                    `;
                    showDemoFeedback('✅ Found 2 students matching your search', 'success');
                }
            };
            
            window.showDemoFeedback = function(message, type = 'info') {
                demoFeedback.textContent = message;
                demoFeedback.className = 'demo-feedback ' + type;
            };
            
            // Demo button handlers
            if (tryDemoBtn) {
                tryDemoBtn.addEventListener('click', () => {
                    showDemo('login');
                    // Expand first step
                    steps.forEach(s => s.classList.remove('active'));
                    document.querySelector('.tutorial-step[data-step="1"]').classList.add('active');
                });
            }
            
            if (closeDemoBtn) {
                closeDemoBtn.addEventListener('click', () => {
                    demoSimulator.classList.remove('active');
                });
            }
            
            // Initialize
            loadProgress();
        })();

        // Smooth scroll for anchor links
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
    })();
</script>

</body>
</html>
