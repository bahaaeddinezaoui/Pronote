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
    <h2 class="section-title"><?php echo t('section_main_features'); ?></h2>
    <p class="section-subtitle"><?php echo t('section_main_features_sub'); ?></p>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">👨‍🎓</div>
            <h3><?php echo t('feature_student_mgmt'); ?></h3>
            <p><?php echo t('feature_student_mgmt_desc'); ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📝</div>
            <h3><?php echo t('feature_observations'); ?></h3>
            <p><?php echo t('feature_observations_desc'); ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">❌</div>
            <h3><?php echo t('feature_absences'); ?></h3>
            <p><?php echo t('feature_absences_desc'); ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">⏰</div>
            <h3><?php echo t('feature_sessions'); ?></h3>
            <p><?php echo t('feature_sessions_desc'); ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📚</div>
            <h3><?php echo t('feature_categories'); ?></h3>
            <p><?php echo t('feature_categories_desc'); ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔔</div>
            <h3><?php echo t('feature_notifications'); ?></h3>
            <p><?php echo t('feature_notifications_desc'); ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🏛️</div>
            <h3><?php echo t('feature_sections'); ?></h3>
            <p><?php echo t('feature_sections_desc'); ?></p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3><?php echo t('feature_dashboard'); ?></h3>
            <p><?php echo t('feature_dashboard_desc'); ?></p>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="tutorial" id="how-it-works">
    <div class="tutorial-container">
        <h2 class="section-title"><?php echo t('section_how_it_works'); ?></h2>
        
        <div class="tutorial-content">
            <div class="tutorial-text">
                <h3><?php echo t('quick_start_guide'); ?></h3>
                
                <ul class="tutorial-steps">
                    <li>
                        <strong><?php echo t('step1_title'); ?></strong>
                        <span><?php echo t('step1_desc'); ?></span>
                    </li>
                    <li>
                        <strong><?php echo t('step2_title'); ?></strong>
                        <span><?php echo t('step2_desc'); ?></span>
                    </li>
                    <li>
                        <strong><?php echo t('step3_title'); ?></strong>
                        <span><?php echo t('step3_desc'); ?></span>
                    </li>
                    <li>
                        <strong><?php echo t('step4_title'); ?></strong>
                        <span><?php echo t('step4_desc'); ?></span>
                    </li>
                    <li>
                        <strong><?php echo t('step5_title'); ?></strong>
                        <span><?php echo t('step5_desc'); ?></span>
                    </li>
                </ul>
            </div>

            <div class="tutorial-visual">
                <div class="tutorial-visual-icon">🚀</div>
                <h4><?php echo t('ready_to_start'); ?></h4>
                <p><?php echo t('ready_to_start_desc'); ?></p>
            </div>
        </div>
    </div>
</section>

<!-- Use Cases Section -->
<section class="use-cases" id="use-cases">
    <h2 class="section-title"><?php echo t('section_for_roles'); ?></h2>
    <p class="section-subtitle"><?php echo t('section_for_roles_sub'); ?></p>
    
    <div class="use-cases-grid">
        <div class="use-case-card">
            <h4><?php echo t('for_teachers'); ?></h4>
            <p><?php echo t('for_teachers_desc'); ?></p>
        </div>

        <div class="use-case-card">
            <h4><?php echo t('for_admins'); ?></h4>
            <p><?php echo t('for_admins_desc'); ?></p>
        </div>

        <div class="use-case-card">
            <h4><?php echo t('analytics_insights'); ?></h4>
            <p><?php echo t('analytics_insights_desc'); ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="cta-content">
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

</body>
</html>
