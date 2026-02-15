/**
 * EduTrack - Effects & Animations Module
 * Provides interactive UI effects and transitions
 */

(function() {
    'use strict';

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
