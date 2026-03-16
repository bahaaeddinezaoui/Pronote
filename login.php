<?php
session_start();
require_once __DIR__ . '/lang/i18n.php';

// Prevent cached copy of the login page so back button reloads and respects session
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// If already authenticated, send user to their home
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    $role = trim((string)($_SESSION['role'] ?? ''));
    if ($role === 'Admin') {
        header('Location: admin_home.php');
    } elseif ($role === 'Superuser') {
        header('Location: superuser_dashboard.php');
    } elseif ($role === 'Secretary') {
        header('Location: secretary_home.php');
    } elseif ($role === 'Teacher') {
        if (!empty($_SESSION['needs_onboarding']) && empty($_SESSION['last_login_at'])) {
            header('Location: teacher_onboarding.php');
        } else {
            header('Location: teacher_home.php');
        }
    } else {
        header('Location: fill_form.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG === 'ar' ? 'ar' : 'en'; ?>" dir="<?php echo $LANG === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css" />
    <title><?php echo t('login'); ?> - <?php echo t('app_name'); ?></title>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - Login Form -->
        <div class="login-left">
            <div class="login-lang-fix"><?php include __DIR__ . '/lang/switcher.php'; ?></div>
            
            <div class="login-card">
                <div class="login-header">
                    <div class="login-logo">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <h1 class="login-title"><?php echo t('login_welcome'); ?></h1>
                    <p class="login-subtitle"><?php echo t('app_name'); ?></p>
                </div>

                <form action="index.php" method="post" id="login">
                    <div class="form-group">
                        <label class="form-label" for="username"><?php echo t('username'); ?></label>
                        <input class="form-input" type="text" id="username" name="username" placeholder="<?php echo t('username_placeholder'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password"><?php echo t('password'); ?></label>
                        <div style="position: relative;">
                            <input class="form-input" type="password" id="password" name="password" placeholder="<?php echo t('password_placeholder'); ?>" required style="padding-right: 40px;">
                            <span id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: var(--text-secondary); user-select: none; font-size: 1.2rem;">
                                👁️
                            </span>
                        </div>
                    </div>

                    <button type="submit" id="login_button" class="btn btn-primary"><?php echo t('login_button'); ?></button>
                    <div id="login_error" class="alert alert-error mt-4" style="display: none; text-align: center;"></div>
                </form>
            </div>
        </div>

        <!-- Right Side - Feature Carousel -->
        <div class="login-right">
            <div class="carousel-container">
                <div class="carousel-slides">
                    <div class="carousel-slide active">
                        <div class="carousel-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </div>
                        <h2 class="carousel-title"><?php echo t('carousel_feature_students'); ?></h2>
                        <p class="carousel-description"><?php echo t('carousel_feature_students_desc'); ?></p>
                    </div>
                    <div class="carousel-slide">
                        <div class="carousel-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <h2 class="carousel-title"><?php echo t('carousel_feature_attendance'); ?></h2>
                        <p class="carousel-description"><?php echo t('carousel_feature_attendance_desc'); ?></p>
                    </div>
                    <div class="carousel-slide">
                        <div class="carousel-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </div>
                        <h2 class="carousel-title"><?php echo t('carousel_feature_reports'); ?></h2>
                        <p class="carousel-description"><?php echo t('carousel_feature_reports_desc'); ?></p>
                    </div>
                    <div class="carousel-slide">
                        <div class="carousel-icon">
                            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                        </div>
                        <h2 class="carousel-title"><?php echo t('carousel_feature_notifications'); ?></h2>
                        <p class="carousel-description"><?php echo t('carousel_feature_notifications_desc'); ?></p>
                    </div>
                </div>
                <div class="carousel-indicators">
                    <button class="carousel-dot active" data-slide="0"></button>
                    <button class="carousel-dot" data-slide="1"></button>
                    <button class="carousel-dot" data-slide="2"></button>
                    <button class="carousel-dot" data-slide="3"></button>
                </div>
                <div class="carousel-nav">
                    <button class="carousel-prev">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15 18 9 12 15 6"></polyline>
                        </svg>
                    </button>
                    <button class="carousel-next">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"></polyline>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        var T = <?php echo json_encode($T); ?>;
        const loginForm = document.getElementById('login');
        const errorDiv = document.getElementById('login_error');

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            errorDiv.style.display = 'none';
            
            const formData = new FormData(loginForm);
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                }
            })
            .catch(err => {
                errorDiv.textContent = (T && T.error_unexpected) ? T.error_unexpected : 'An unexpected error occurred.';
                errorDiv.style.display = 'block';
            });
        });

        // Password toggle logic
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🔒';
        });

        // If the user navigates back to this page while still logged in, bounce them home.
        window.addEventListener('pageshow', function () {
            fetch('session_status.php', { credentials: 'same-origin' })
                .then(res => res.json())
                .then(data => {
                    if (data.loggedIn && data.home) {
                        window.location.replace(data.home);
                    }
                })
                .catch(() => { /* ignore */ });
        });

        // Carousel functionality
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        const prevBtn = document.querySelector('.carousel-prev');
        const nextBtn = document.querySelector('.carousel-next');
        let currentSlide = 0;
        let autoPlayInterval;

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            if (index >= slides.length) index = 0;
            if (index < 0) index = slides.length - 1;
            
            currentSlide = index;
            slides[currentSlide].classList.add('active');
            dots[currentSlide].classList.add('active');
        }

        function nextSlide() {
            showSlide(currentSlide + 1);
        }

        function prevSlide() {
            showSlide(currentSlide - 1);
        }

        function startAutoPlay() {
            autoPlayInterval = setInterval(nextSlide, 5000);
        }

        function stopAutoPlay() {
            clearInterval(autoPlayInterval);
        }

        prevBtn.addEventListener('click', () => {
            stopAutoPlay();
            prevSlide();
            startAutoPlay();
        });

        nextBtn.addEventListener('click', () => {
            stopAutoPlay();
            nextSlide();
            startAutoPlay();
        });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                stopAutoPlay();
                showSlide(index);
                startAutoPlay();
            });
        });

        // Start autoplay
        startAutoPlay();
    </script>
</body>
</html>

