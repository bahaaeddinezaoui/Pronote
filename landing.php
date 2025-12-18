<?php
session_start();

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pronote - Educational Management System</title>
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
    <a href="#" class="navbar-brand">📚 Pronote</a>
    <div class="navbar-links">
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#use-cases">For Teams</a>
        <a href="login.php" class="btn-login">Login</a>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1>Welcome to Pronote</h1>
        <p>A Modern Educational Management System for Teachers and Administrators</p>
        <p style="font-size: 16px; opacity: 0.9;">Manage absences, observations, student records, and class sessions with ease</p>
        <div class="hero-buttons">
            <a href="login.php" class="btn-primary">Get Started</a>
            <a href="#features" class="btn-secondary">Learn More</a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features" id="features">
    <h2 class="section-title">✨ Main Features</h2>
    <p class="section-subtitle">Everything you need to manage your educational institution</p>
    
    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">👨‍🎓</div>
            <h3>Student Management</h3>
            <p>Complete student database with categories, sections, and academic grades. Track all student information in one centralized location.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📝</div>
            <h3>Observation Tracking</h3>
            <p>Teachers can make detailed observations about student performance. Admins receive real-time notifications for new observations.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">❌</div>
            <h3>Absence Management</h3>
            <p>Record and track student absences with dates, times, and motifs. Generate reports and monitor attendance patterns.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">⏰</div>
            <h3>Study Session Scheduling</h3>
            <p>Schedule classes and study sessions with specific time slots. Associate teachers, sections, and majors with each session.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📚</div>
            <h3>Multi-Category Support</h3>
            <p>Organize students into multiple categories (EOA 1, EOA 2, Master, etc.) and track different educational programs.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🔔</div>
            <h3>Real-Time Notifications</h3>
            <p>Stay updated with instant notifications for new observations and important events in your institution.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">🏛️</div>
            <h3>Section Organization</h3>
            <p>Organize students into sections within each category for better classroom management and tracking.</p>
        </div>

        <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Admin Dashboard</h3>
            <p>Comprehensive dashboard with statistics, recent activities, and quick search for study sessions and records.</p>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="tutorial" id="how-it-works">
    <div class="tutorial-container">
        <h2 class="section-title">🎯 How It Works</h2>
        
        <div class="tutorial-content">
            <div class="tutorial-text">
                <h3>Quick Start Guide</h3>
                
                <ul class="tutorial-steps">
                    <li>
                        <strong>1. Login to Your Account</strong>
                        <span>Use your credentials to access the system as a Teacher or Administrator</span>
                    </li>
                    <li>
                        <strong>2. Navigate Your Dashboard</strong>
                        <span>View your personalized dashboard with key statistics and recent activities</span>
                    </li>
                    <li>
                        <strong>3. Manage Records</strong>
                        <span>Create observations, record absences, and manage student information</span>
                    </li>
                    <li>
                        <strong>4. Search & Filter</strong>
                        <span>Use advanced search to find specific study sessions, students, or records</span>
                    </li>
                    <li>
                        <strong>5. Stay Informed</strong>
                        <span>Receive notifications for important events and maintain your profile</span>
                    </li>
                </ul>
            </div>

            <div class="tutorial-visual">
                <div class="tutorial-visual-icon">🚀</div>
                <h4>Ready to Get Started?</h4>
                <p>Login to access the full power of Pronote and streamline your educational management</p>
            </div>
        </div>
    </div>
</section>

<!-- Use Cases Section -->
<section class="use-cases" id="use-cases">
    <h2 class="section-title">👥 For Different Roles</h2>
    <p class="section-subtitle">Designed with different user roles in mind</p>
    
    <div class="use-cases-grid">
        <div class="use-case-card">
            <h4>👨‍🏫 Teachers</h4>
            <p>Record observations about student performance, track absences during your classes, and view your schedule of study sessions. Keep detailed notes on each student's progress.</p>
        </div>

        <div class="use-case-card">
            <h4>👔 Administrators</h4>
            <p>Oversee all educational operations with a comprehensive dashboard. Manage students, teachers, classes, and receive real-time notifications about observations and absences.</p>
        </div>

        <div class="use-case-card">
            <h4>📊 Analytics & Insights</h4>
            <p>Track institution-wide metrics including total students, teachers, classes, and sessions. Monitor trends in observations and absences to improve educational outcomes.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="cta-content">
        <h2>Ready to Transform Your Educational Management?</h2>
        <p>Join educators and administrators using Pronote to streamline their operations and improve student outcomes.</p>
        <a href="login.php" class="btn-primary" style="background: white; color: #6f42c1;">Login Now</a>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <p>&copy; 2025 Pronote - Educational Management System. All rights reserved.</p>
    <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <span>•</span>
        <a href="#">Terms of Service</a>
        <span>•</span>
        <a href="#">Contact Support</a>
    </div>
    <p>Empowering Education Through Technology</p>
</footer>

</body>
</html>
