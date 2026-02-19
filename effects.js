/**
 * EduTrack - Effects & Animations Module
 * Provides interactive UI effects and transitions
 */

(function() {
    'use strict';

    // ============================================
    // GUIDED TUTORIAL (ONBOARDING TOUR)
    // ============================================
    const Tutorial = (function() {
        let state = {
            steps: [],
            index: 0,
            overlay: null,
            popover: null,
            highlightEl: null,
            startedByUser: false
        };

        function getConfig() {
            return window.EduTrackTutorialConfig || { userId: '', role: '', lang: 'en', t: {} };
        }

        function storageKey() {
            const cfg = getConfig();
            const uid = cfg.userId || 'anon';
            return 'eduTrack:tutorialCompleted:' + uid;
        }

        function isCompleted() {
            try {
                return localStorage.getItem(storageKey()) === '1';
            } catch (e) {
                return false;
            }
        }

        function setCompleted() {
            try {
                localStorage.setItem(storageKey(), '1');
            } catch (e) {
                // ignore
            }
        }

        function clearCompleted() {
            try {
                localStorage.removeItem(storageKey());
            } catch (e) {
                // ignore
            }
        }

        function buildSteps() {
            const cfg = getConfig();
            const T = cfg.t || {};
            const role = (cfg.role || '').toLowerCase();
            const path = (window.location && window.location.pathname) ? window.location.pathname.toLowerCase() : '';
            const page = path.split('/').pop() || '';
            const params = new URLSearchParams((window.location && window.location.search) ? window.location.search : '');
            const tab = (params.get('tab') || '').toLowerCase();

            const steps = [];
            steps.push({
                selector: null,
                title: T.tutorial_intro_title || 'Welcome to eNote',
                body: T.tutorial_intro_body || 'This tour will guide you through the main areas of the application. Use Next/Back to navigate, Skip to stop, and you can restart this tour anytime from the sidebar.'
            });
            steps.push({
                selector: '#navHome',
                title: T.tutorial_step_home_title || 'Home',
                body: T.tutorial_step_home_body || 'Use this to go back to your main dashboard.'
            });

            steps.push({
                selector: '.sidebar',
                title: T.tutorial_step_sidebar_title || 'Sidebar navigation',
                body: T.tutorial_step_sidebar_body || 'Use the sidebar to navigate. The highlighted link shows your current page.'
            });

            if (role === 'admin') {
                steps.push({
                    selector: '#navSearchSessions',
                    title: T.tutorial_step_search_title || 'Search',
                    body: T.tutorial_step_search_body || 'Search study sessions by date and time slot.'
                });
                steps.push({
                    selector: '#navStudentRecords',
                    title: T.tutorial_step_records_title || 'Student Records',
                    body: T.tutorial_step_records_body || 'Find students and view absences and observations.'
                });

                steps.push({
                    selector: '#notificationBell',
                    title: T.tutorial_step_admin_notifications_title || 'Notifications',
                    body: T.tutorial_step_admin_notifications_body || 'When teachers add new observations, you will see them here.'
                });
                steps.push({
                    selector: '.welcome-section',
                    title: T.tutorial_step_admin_welcome_title || 'Dashboard overview',
                    body: T.tutorial_step_admin_welcome_body || 'This page summarizes key activity across the institution.'
                });
                steps.push({
                    selector: '.stats-grid',
                    title: T.tutorial_step_admin_stats_title || 'Statistics',
                    body: T.tutorial_step_admin_stats_body || 'Quickly monitor totals (students, teachers, sessions, absences, observations).'
                });
                steps.push({
                    selector: '.info-section',
                    title: T.tutorial_step_admin_recent_title || 'Recent activity',
                    body: T.tutorial_step_admin_recent_body || 'Review the latest observations and absences to follow up quickly.'
                });
            } else if (role === 'teacher') {
                steps.push({
                    selector: '#navAbsences',
                    title: T.tutorial_step_absences_title || 'Absences',
                    body: T.tutorial_step_absences_body || 'Record attendance for your class sessions.'
                });
                steps.push({
                    selector: '#navObservations',
                    title: T.tutorial_step_observations_title || 'Observations',
                    body: T.tutorial_step_observations_body || 'Create observations about student performance.'
                });

                steps.push({
                    selector: '.hero-section',
                    title: T.tutorial_step_teacher_hero_title || 'Your home',
                    body: T.tutorial_step_teacher_hero_body || 'Your home page shows your day and shortcuts to your main actions.'
                });
                steps.push({
                    selector: '.home-container .info-card:nth-of-type(1)',
                    title: T.tutorial_step_teacher_obs_card_title || 'Observations summary',
                    body: T.tutorial_step_teacher_obs_card_body || 'See how many observations you have recorded and create a new one quickly.'
                });
                steps.push({
                    selector: '.home-container .info-card:nth-of-type(2)',
                    title: T.tutorial_step_teacher_majors_card_title || 'Your majors',
                    body: T.tutorial_step_teacher_majors_card_body || 'The majors assigned to you are displayed here.'
                });
            } else if (role === 'secretary') {
                steps.push({
                    selector: '#navInsertStudent',
                    title: T.tutorial_step_insert_student_title || 'Insert Student',
                    body: T.tutorial_step_insert_student_body || 'Register new students using the multi-step form.'
                });

                steps.push({
                    selector: '.welcome-header',
                    title: T.tutorial_step_secretary_welcome_title || 'Secretary dashboard',
                    body: T.tutorial_step_secretary_welcome_body || 'You can see quick stats and shortcuts for daily registration tasks.'
                });
                steps.push({
                    selector: '.quick-actions',
                    title: T.tutorial_step_secretary_actions_title || 'Quick actions',
                    body: T.tutorial_step_secretary_actions_body || 'Use these shortcuts to quickly register new students.'
                });
            }

            if (page === 'admin_dashboard.php') {
                steps.push({
                    selector: '.filters-section',
                    title: T.tutorial_admin_dashboard_filters_title || 'Filters',
                    body: T.tutorial_admin_dashboard_filters_body || 'Choose a date and (optionally) a time slot, then click Search Sessions.'
                });
                steps.push({
                    selector: '#session_date',
                    title: T.tutorial_admin_dashboard_date_title || 'Select date',
                    body: T.tutorial_admin_dashboard_date_body || 'Pick the day you want to review.'
                });
                steps.push({
                    selector: '#time_slot',
                    title: T.tutorial_admin_dashboard_slot_title || 'Select time slot',
                    body: T.tutorial_admin_dashboard_slot_body || 'Filter sessions by time slot, or keep it on “All”.'
                });
                steps.push({
                    selector: '#adminSearchSessionsBtn',
                    title: T.tutorial_admin_dashboard_search_btn_title || 'Search sessions',
                    body: T.tutorial_admin_dashboard_search_btn_body || 'Loads matching study sessions.'
                });
                steps.push({
                    selector: '#sessions_container',
                    title: T.tutorial_admin_dashboard_results_title || 'Results',
                    body: T.tutorial_admin_dashboard_results_body || 'Sessions will appear here as buttons. Click one to open full details.'
                });
                steps.push({
                    selector: '#session_modal',
                    title: T.tutorial_admin_dashboard_modal_title || 'Session details',
                    body: T.tutorial_admin_dashboard_modal_body || 'This modal shows sections, absences, and observations for the selected session.'
                });
                steps.push({
                    selector: '#absenceSummarySection',
                    title: T.tutorial_admin_dashboard_absence_summary_title || 'Absence summary',
                    body: T.tutorial_admin_dashboard_absence_summary_body || 'After searching, you can view aggregated absence statistics here.'
                });
            }

            if (page === 'admin_search_student.php') {
                steps.push({
                    selector: '#searchInput',
                    title: T.tutorial_admin_search_input_title || 'Search a student',
                    body: T.tutorial_admin_search_input_body || 'Type a student name. Suggestions will appear below.'
                });
                steps.push({
                    selector: '#suggestionsContainer',
                    title: T.tutorial_admin_search_suggestions_title || 'Suggestions',
                    body: T.tutorial_admin_search_suggestions_body || 'Click a student card to load their records.'
                });
                steps.push({
                    selector: '#viewToggles',
                    title: T.tutorial_admin_search_views_title || 'Views',
                    body: T.tutorial_admin_search_views_body || 'Switch between Student Records and Full Information.'
                });
                steps.push({
                    selector: '#dateFilterSection',
                    title: T.tutorial_admin_search_date_filter_title || 'Filter by date',
                    body: T.tutorial_admin_search_date_filter_body || 'Use start/end date to narrow the displayed records.'
                });
                steps.push({
                    selector: '#recordsContainer',
                    title: T.tutorial_admin_search_records_title || 'Records',
                    body: T.tutorial_admin_search_records_body || 'Absences and observations will be listed here for the selected period.'
                });
                steps.push({
                    selector: '#fullInfoContainer',
                    title: T.tutorial_admin_search_fullinfo_title || 'Full information',
                    body: T.tutorial_admin_search_fullinfo_body || 'This view shows the complete student profile.'
                });
            }

            if (page === 'fill_form.php') {
                steps.push({
                    selector: '#fillFormCard',
                    title: T.tutorial_fill_form_title || 'Absences & observations',
                    body: T.tutorial_fill_form_body || 'This page lets you record absences and add observations for the current session.'
                });

                if (tab === 'observations') {
                    steps.push({
                        selector: '#observations_section',
                        title: T.tutorial_fill_obs_section_title || 'Observations section',
                        body: T.tutorial_fill_obs_section_body || 'Record one observation for one student.'
                    });
                } else {
                    steps.push({
                        selector: '#absences_section',
                        title: T.tutorial_fill_abs_section_title || 'Absences section',
                        body: T.tutorial_fill_abs_section_body || 'Select class/category/major/sections, then mark absences and submit.'
                    });
                }

                steps.push({
                    selector: '#sessionStatusBadge',
                    title: T.tutorial_fill_session_status_title || 'Session status',
                    body: T.tutorial_fill_session_status_body || 'If a session already exists for this time slot, you may be limited to recording observations only.'
                });

                steps.push({
                    selector: '#class_select',
                    title: T.tutorial_fill_class_title || 'Select class',
                    body: T.tutorial_fill_class_body || 'Choose the class for the session.'
                });
                steps.push({
                    selector: '#categories',
                    title: T.tutorial_fill_category_title || 'Select category',
                    body: T.tutorial_fill_category_body || 'Choose the category you teach.'
                });
                steps.push({
                    selector: '#major_select',
                    title: T.tutorial_fill_major_title || 'Select major',
                    body: T.tutorial_fill_major_body || 'Pick the major to load available sections and students.'
                });
                steps.push({
                    selector: '#select_sections',
                    title: T.tutorial_fill_sections_title || 'Select sections',
                    body: T.tutorial_fill_sections_body || 'Select one or more sections for this session.'
                });
                steps.push({
                    selector: '#stats_container',
                    title: T.tutorial_fill_stats_title || 'Live stats',
                    body: T.tutorial_fill_stats_body || 'After loading students, you will see totals for students, presentees, and absentees.'
                });
                steps.push({
                    selector: '#student_table',
                    title: T.tutorial_fill_table_title || 'Student table',
                    body: T.tutorial_fill_table_body || 'Mark students absent, add notes, and use edit/delete actions when available.'
                });
                steps.push({
                    selector: '#add_row_container',
                    title: T.tutorial_fill_add_student_title || 'Add student',
                    body: T.tutorial_fill_add_student_body || 'Add a student row if needed (depending on your workflow and loaded data).'
                });
                steps.push({
                    selector: '#submit_button',
                    title: T.tutorial_fill_submit_abs_title || 'Submit absences',
                    body: T.tutorial_fill_submit_abs_body || 'Submit your absences once you are done.'
                });

                steps.push({
                    selector: '#obs_student_input',
                    title: T.tutorial_fill_obs_student_title || 'Pick student',
                    body: T.tutorial_fill_obs_student_body || 'Search and select a student to attach an observation.'
                });
                steps.push({
                    selector: '#obs_motif_id',
                    title: T.tutorial_fill_obs_motif_title || 'Choose motif',
                    body: T.tutorial_fill_obs_motif_body || 'Select the observation motif (reason/category).'
                });
                steps.push({
                    selector: '#obs_note',
                    title: T.tutorial_fill_obs_note_title || 'Add note',
                    body: T.tutorial_fill_obs_note_body || 'Optionally add extra details (kept concise).'
                });
                steps.push({
                    selector: '#obs_submit_btn',
                    title: T.tutorial_fill_obs_submit_title || 'Submit observation',
                    body: T.tutorial_fill_obs_submit_body || 'Submit the observation for the selected student.'
                });
            }

            steps.push({
                selector: '#navProfile',
                title: T.tutorial_step_profile_title || 'Profile',
                body: T.tutorial_step_profile_body || 'View your account information and access details.'
            });
            steps.push({
                selector: '#navOptions',
                title: T.tutorial_step_options_title || 'Options',
                body: T.tutorial_step_options_body || 'Change your password and adjust settings.'
            });
            steps.push({
                selector: '#navLanguage',
                title: T.tutorial_step_language_title || 'Language',
                body: T.tutorial_step_language_body || 'Switch between English and Arabic anytime.'
            });
            steps.push({
                selector: '#restartTutorial',
                title: T.tutorial_step_restart_title || 'Tutorial',
                body: T.tutorial_step_restart_body || 'You can restart this tutorial anytime from here.'
            });
            steps.push({
                selector: '#navLogout',
                title: T.tutorial_step_logout_title || 'Logout',
                body: T.tutorial_step_logout_body || 'Use this to securely log out when you are done.'
            });

            steps.push({
                selector: null,
                title: T.tutorial_outro_title || 'You are ready!',
                body: T.tutorial_outro_body || 'Tip: if you forget something, use “Tutorial” in the sidebar to see this tour again.'
            });

            return steps;
        }

        function ensureElements() {
            if (!state.overlay) {
                state.overlay = document.createElement('div');
                state.overlay.className = 'tutorial-overlay';
                state.overlay.addEventListener('click', function(e) {
                    if (e.target === state.overlay) {
                        end(true);
                    }
                });
                document.body.appendChild(state.overlay);
            }
            if (!state.popover) {
                state.popover = document.createElement('div');
                state.popover.className = 'tutorial-popover';
                state.popover.setAttribute('role', 'dialog');
                document.body.appendChild(state.popover);
            }
        }

        function cleanupHighlight() {
            if (state.highlightEl) {
                state.highlightEl.classList.remove('tutorial-highlight');
                state.highlightEl = null;
            }
        }

        function hide() {
            if (state.overlay) state.overlay.style.display = 'none';
            if (state.popover) state.popover.style.display = 'none';
            cleanupHighlight();
            document.body.classList.remove('tutorial-open');
            window.removeEventListener('resize', positionPopover);
            window.removeEventListener('scroll', positionPopover, true);
        }

        function positionPopover() {
            if (!state.popover || state.popover.style.display === 'none') return;
            const step = state.steps[state.index];
            if (!step) return;
            if (!step.selector) {
                const pop = state.popover;
                const left = Math.max(12, Math.round((window.innerWidth - pop.offsetWidth) / 2));
                const top = Math.max(12, Math.round((window.innerHeight - pop.offsetHeight) / 2));
                pop.style.left = left + 'px';
                pop.style.top = top + 'px';
                return;
            }

            const el = document.querySelector(step.selector);
            if (!el) {
                const pop = state.popover;
                const left = Math.max(12, Math.round((window.innerWidth - pop.offsetWidth) / 2));
                const top = Math.max(12, Math.round((window.innerHeight - pop.offsetHeight) / 2));
                pop.style.left = left + 'px';
                pop.style.top = top + 'px';
                return;
            }

            const rect = el.getBoundingClientRect();
            const pop = state.popover;

            // Default: right side of sidebar element, with fallback to top.
            const gap = 12;
            const maxLeft = window.innerWidth - pop.offsetWidth - 12;
            const maxTop = window.innerHeight - pop.offsetHeight - 12;

            let left = rect.right + gap;
            let top = rect.top;

            if (left > maxLeft) {
                left = rect.left - pop.offsetWidth - gap;
            }
            if (left < 12) {
                left = 12;
            }
            if (top > maxTop) {
                top = maxTop;
            }
            if (top < 12) {
                top = 12;
            }

            pop.style.left = left + 'px';
            pop.style.top = top + 'px';
        }

        function render() {
            ensureElements();

            const cfg = getConfig();
            const T = cfg.t || {};
            const step = state.steps[state.index];
            if (!step) return;

            const el = document.querySelector(step.selector);
            cleanupHighlight();
            if (el) {
                state.highlightEl = el;
                el.classList.add('tutorial-highlight');
                try {
                    el.scrollIntoView({ block: 'center', inline: 'nearest', behavior: 'smooth' });
                } catch (e) {
                    // ignore
                }
            }

            const total = state.steps.length;
            const isLast = state.index === total - 1;

            state.overlay.style.display = 'block';
            state.popover.style.display = 'block';
            document.body.classList.add('tutorial-open');

            state.popover.innerHTML = `
                <div class="tutorial-popover-header">
                    <div class="tutorial-popover-title">${escapeHtml(step.title || '')}</div>
                    <button type="button" class="tutorial-close" aria-label="${escapeHtml(T.tutorial_close || 'Close')}">×</button>
                </div>
                <div class="tutorial-popover-body">${escapeHtml(step.body || '')}</div>
                <div class="tutorial-popover-footer">
                    <div class="tutorial-progress">${state.index + 1} / ${total}</div>
                    <div class="tutorial-actions">
                        <button type="button" class="btn tutorial-skip">${escapeHtml(T.tutorial_skip || 'Skip')}</button>
                        ${state.index > 0 ? `<button type="button" class="btn tutorial-prev">${escapeHtml(T.tutorial_prev || 'Back')}</button>` : ''}
                        <button type="button" class="btn btn-primary tutorial-next">${escapeHtml(isLast ? (T.tutorial_finish || 'Finish') : (T.tutorial_next || 'Next'))}</button>
                    </div>
                </div>
            `;

            const btnClose = state.popover.querySelector('.tutorial-close');
            const btnSkip = state.popover.querySelector('.tutorial-skip');
            const btnPrev = state.popover.querySelector('.tutorial-prev');
            const btnNext = state.popover.querySelector('.tutorial-next');

            if (btnClose) btnClose.addEventListener('click', () => end(true));
            if (btnSkip) btnSkip.addEventListener('click', () => end(true));
            if (btnPrev) btnPrev.addEventListener('click', () => prev());
            if (btnNext) btnNext.addEventListener('click', () => next());

            positionPopover();
            window.addEventListener('resize', positionPopover);
            window.addEventListener('scroll', positionPopover, true);
        }

        function escapeHtml(s) {
            return String(s)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function start(force = false) {
            state.startedByUser = !!force;
            if (!force && isCompleted()) return;

            state.steps = buildSteps().filter(st => {
                // Filter out steps that don't exist on this page
                if (!st.selector) return true;
                try {
                    return !!document.querySelector(st.selector);
                } catch (e) {
                    return false;
                }
            });

            if (state.steps.length === 0) return;
            state.index = 0;
            render();
        }

        function next() {
            if (state.index < state.steps.length - 1) {
                state.index++;
                render();
            } else {
                end(false);
            }
        }

        function prev() {
            if (state.index > 0) {
                state.index--;
                render();
            }
        }

        function end(skipped) {
            hide();
            if (!skipped) {
                setCompleted();
                if (window.EduTrackEffects && window.EduTrackEffects.Toast) {
                    const cfg = getConfig();
                    const T = (cfg && cfg.t) ? cfg.t : {};
                    window.EduTrackEffects.Toast.show(T.tutorial_done_toast || 'Tutorial completed!', 'success', 2500);
                }
            } else {
                setCompleted();
            }
        }

        function restart() {
            clearCompleted();
            start(true);
        }

        return { start, restart, isCompleted };
    })();

    // ============================================
    // BUTTON RIPPLE EFFECT
    // ============================================
    function initRippleEffect() {
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const rect = this.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    background: rgba(255, 255, 255, 0.4);
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
    }

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

    // ============================================
    // TOAST NOTIFICATIONS
    // ============================================
    const Toast = {
        container: null,

        init() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.className = 'toast-container';
                this.container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                `;
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'info', duration = 3000) {
            this.init();

            const toast = document.createElement('div');
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#4f46e5'
            };

            toast.style.cssText = `
                background: white;
                color: ${colors[type] || colors.info};
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                border-left: 4px solid ${colors[type] || colors.info};
                font-weight: 500;
                min-width: 250px;
                animation: slideInRight 0.3s ease-out, fadeIn 0.3s ease-out;
                display: flex;
                align-items: center;
                gap: 10px;
            `;

            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ'
            };

            toast.innerHTML = `
                <span style="font-size: 18px; font-weight: bold;">${icons[type] || icons.info}</span>
                <span>${message}</span>
            `;

            this.container.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideInRight 0.3s ease-out reverse, fadeIn 0.3s ease-out reverse';
                setTimeout(() => toast.remove(), 300);
            }, duration);
        }
    };

    // ============================================
    // LOADING OVERLAY
    // ============================================
    const Loading = {
        overlay: null,

        show(message = 'Loading...') {
            if (!this.overlay) {
                this.overlay = document.createElement('div');
                this.overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255, 255, 255, 0.9);
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: center;
                    z-index: 9999;
                    animation: fadeIn 0.2s ease-out;
                `;
                document.body.appendChild(this.overlay);
            }

            this.overlay.innerHTML = `
                <div class="loading-spinner loading-spinner-lg" style="margin-bottom: 16px;"></div>
                <span style="color: #4f46e5; font-weight: 500;">${message}</span>
            `;

            this.overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        },

        hide() {
            if (this.overlay) {
                this.overlay.style.animation = 'fadeIn 0.2s ease-out reverse';
                setTimeout(() => {
                    this.overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }, 200);
            }
        }
    };

    // ============================================
    // INTERSECTION OBSERVER FOR SCROLL ANIMATIONS
    // ============================================
    function initScrollAnimations() {
        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in-up');
                    entry.target.style.opacity = '1';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-card, .table-container, .data-table tr').forEach(el => {
            el.style.opacity = '0';
            observer.observe(el);
        });
    }

    // ============================================
    // FORM VALIDATION EFFECTS
    // ============================================
    function initFormValidation() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                let hasError = false;

                this.querySelectorAll('.form-input[required]').forEach(input => {
                    if (!input.value.trim()) {
                        hasError = true;
                        input.classList.add('shake');
                        input.style.borderColor = '#ef4444';

                        setTimeout(() => {
                            input.classList.remove('shake');
                            input.style.borderColor = '';
                        }, 400);
                    }
                });

                if (hasError) {
                    e.preventDefault();
                    Toast.show('Please fill in all required fields', 'error');
                }
            });
        });
    }

    // ============================================
    // SMOOTH PAGE TRANSITIONS
    // ============================================
    function initPageTransitions() {
        document.querySelectorAll('a[href]:not([href^="#"]):not([href^="javascript"]):not([target="_blank"])').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript')) {
                    e.preventDefault();

                    document.body.style.animation = 'fadeIn 0.2s ease-out reverse';
                    document.body.style.opacity = '0';

                    setTimeout(() => {
                        window.location.href = href;
                    }, 200);
                }
            });
        });
    }

    // ============================================
    // CONFIRM DIALOG
    // ============================================
    const ConfirmDialog = {
        show(message, onConfirm, onCancel) {
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                animation: fadeIn 0.2s ease-out;
            `;

            const dialog = document.createElement('div');
            dialog.style.cssText = `
                background: white;
                padding: 24px;
                border-radius: 12px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.2);
                max-width: 400px;
                text-align: center;
                animation: scaleIn 0.3s ease-out;
            `;

            dialog.innerHTML = `
                <p style="margin-bottom: 20px; font-size: 16px; color: #1e293b;">${message}</p>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button class="btn confirm-yes" style="background: #ef4444; color: white; padding: 8px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500;">Yes</button>
                    <button class="btn confirm-no" style="background: #e2e8f0; color: #64748b; padding: 8px 20px; border-radius: 6px; border: none; cursor: pointer; font-weight: 500;">No</button>
                </div>
            `;

            overlay.appendChild(dialog);
            document.body.appendChild(overlay);

            dialog.querySelector('.confirm-yes').addEventListener('click', () => {
                overlay.style.animation = 'fadeIn 0.2s ease-out reverse';
                setTimeout(() => {
                    overlay.remove();
                    if (onConfirm) onConfirm();
                }, 200);
            });

            dialog.querySelector('.confirm-no').addEventListener('click', () => {
                overlay.style.animation = 'fadeIn 0.2s ease-out reverse';
                setTimeout(() => {
                    overlay.remove();
                    if (onCancel) onCancel();
                }, 200);
            });

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    overlay.style.animation = 'fadeIn 0.2s ease-out reverse';
                    setTimeout(() => {
                        overlay.remove();
                        if (onCancel) onCancel();
                    }, 200);
                }
            });
        }
    };

    // ============================================
    // TEXT TOOLTIP
    // ============================================
    function initTooltips() {
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', function() {
                const tooltip = document.createElement('div');
                tooltip.className = 'custom-tooltip';
                tooltip.textContent = this.getAttribute('data-tooltip');
                tooltip.style.cssText = `
                    position: fixed;
                    background: #1e293b;
                    color: white;
                    padding: 6px 12px;
                    border-radius: 6px;
                    font-size: 12px;
                    white-space: nowrap;
                    z-index: 10000;
                    animation: fadeIn 0.2s ease-out;
                    pointer-events: none;
                `;

                document.body.appendChild(tooltip);

                const rect = this.getBoundingClientRect();
                tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 8}px`;

                this._tooltip = tooltip;
            });

            el.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    this._tooltip = null;
                }
            });
        });
    }

    // ============================================
    // NUMBER COUNTER ANIMATION
    // ============================================
    function animateCounters() {
        document.querySelectorAll('.stat-value').forEach(counter => {
            const target = parseInt(counter.textContent) || 0;
            if (target === 0) return;

            const duration = 1000;
            const step = target / (duration / 16);
            let current = 0;

            const updateCounter = () => {
                current += step;
                if (current < target) {
                    counter.textContent = Math.floor(current);
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.textContent = target;
                }
            };

            counter.textContent = '0';

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateCounter();
                        observer.unobserve(entry.target);
                    }
                });
            });

            observer.observe(counter);
        });
    }

    // ============================================
    // INITIALIZE ALL EFFECTS
    // ============================================
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initEffects);
        } else {
            initEffects();
        }
    }

    function initEffects() {
        initRippleEffect();
        initScrollAnimations();
        initFormValidation();
        initPageTransitions();
        initTooltips();
        animateCounters();

        // Guided tutorial setup
        window.EduTrackTutorial = Tutorial;
        window.startTutorial = function(force) {
            Tutorial.start(!!force);
        };
        try {
            if (document.body && document.body.classList && document.body.classList.contains('app-layout')) {
                Tutorial.start(false);
            } else {
                // Still attempt to start on any authenticated page using sidebar
                Tutorial.start(false);
            }
        } catch (e) {
            // ignore
        }

        // Make utilities globally available
        window.EduTrackEffects = {
            Toast,
            Loading,
            ConfirmDialog,
            showToast: Toast.show.bind(Toast),
            showLoading: Loading.show.bind(Loading),
            hideLoading: Loading.hide.bind(Loading),
            confirm: ConfirmDialog.show.bind(ConfirmDialog)
        };

        console.log('EduTrack Effects & Animations initialized');
    }

    // Start initialization
    init();
})();
