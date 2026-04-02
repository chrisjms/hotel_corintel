/**
 * Hothello Website - Main JavaScript
 * Handles navigation, smooth scrolling, form interactions, and translations
 */

(function() {
    'use strict';

    // ===== Translation System =====
    const i18n = {
        currentLang: 'en',
        translations: {},
        supportedLangs: ['en', 'fr'],

        async init() {
            // Get saved language or detect from browser
            const savedLang = localStorage.getItem('hothello-lang');
            const browserLang = navigator.language.slice(0, 2);

            if (savedLang && this.supportedLangs.includes(savedLang)) {
                this.currentLang = savedLang;
            } else if (this.supportedLangs.includes(browserLang)) {
                this.currentLang = browserLang;
            }

            // Load translation file
            await this.loadTranslations(this.currentLang);

            // Apply translations
            this.applyTranslations();

            // Update language switcher UI
            this.updateSwitcherUI();
        },

        async loadTranslations(lang) {
            try {
                const response = await fetch(`lang/${lang}.json`);
                if (!response.ok) throw new Error('Translation file not found');
                this.translations = await response.json();
            } catch (error) {
                console.warn(`Could not load ${lang} translations, falling back to English`);
                if (lang !== 'en') {
                    await this.loadTranslations('en');
                }
            }
        },

        async setLanguage(lang) {
            if (!this.supportedLangs.includes(lang)) return;

            this.currentLang = lang;
            localStorage.setItem('hothello-lang', lang);

            await this.loadTranslations(lang);
            this.applyTranslations();
            this.updateSwitcherUI();

            // Update HTML lang attribute
            document.documentElement.lang = lang;
        },

        get(key) {
            const keys = key.split('.');
            let value = this.translations;

            for (const k of keys) {
                if (value && typeof value === 'object' && k in value) {
                    value = value[k];
                } else {
                    return key; // Return key if translation not found
                }
            }

            return value;
        },

        applyTranslations() {
            // Update all elements with data-i18n attribute
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                const translation = this.get(key);

                if (translation && translation !== key) {
                    el.textContent = translation;
                }
            });

            // Update all elements with data-i18n-placeholder attribute
            document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
                const key = el.getAttribute('data-i18n-placeholder');
                const translation = this.get(key);

                if (translation && translation !== key) {
                    el.placeholder = translation;
                }
            });

            // Update all elements with data-i18n-title attribute (for page titles)
            const pageTitleEl = document.querySelector('[data-i18n-title]');
            if (pageTitleEl) {
                const key = pageTitleEl.getAttribute('data-i18n-title');
                const translation = this.get(key);
                if (translation && translation !== key) {
                    document.title = translation;
                }
            }

            // Update meta description
            const metaDesc = document.querySelector('meta[name="description"]');
            const metaDescKey = document.querySelector('[data-i18n-meta-description]');
            if (metaDesc && metaDescKey) {
                const key = metaDescKey.getAttribute('data-i18n-meta-description');
                const translation = this.get(key);
                if (translation && translation !== key) {
                    metaDesc.setAttribute('content', translation);
                }
            }
        },

        updateSwitcherUI() {
            const currentLangSpan = document.querySelector('.lang-switcher__current');
            const options = document.querySelectorAll('.lang-switcher__option');

            if (currentLangSpan) {
                currentLangSpan.textContent = this.currentLang.toUpperCase();
            }

            options.forEach(option => {
                const optionLang = option.getAttribute('data-lang');
                option.classList.toggle('active', optionLang === this.currentLang);
            });
        }
    };

    // ===== DOM Elements =====
    const navToggle = document.querySelector('.nav__toggle');
    const navMenu = document.querySelector('.nav--center');
    const header = document.querySelector('.header');
    const contactForm = document.getElementById('contactForm');

    // ===== Language Switcher =====
    const langSwitcherBtn = document.querySelector('.lang-switcher__btn');
    const langDropdown = document.querySelector('.lang-switcher__dropdown');
    const langOptions = document.querySelectorAll('.lang-switcher__option');

    if (langSwitcherBtn && langDropdown) {
        langSwitcherBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            langDropdown.classList.toggle('active');
        });

        langOptions.forEach(option => {
            option.addEventListener('click', function() {
                const lang = this.getAttribute('data-lang');
                i18n.setLanguage(lang);
                langSwitcherBtn.classList.remove('active');
                langDropdown.classList.remove('active');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!langSwitcherBtn.contains(e.target) && !langDropdown.contains(e.target)) {
                langSwitcherBtn.classList.remove('active');
                langDropdown.classList.remove('active');
            }
        });
    }

    // ===== Mobile Navigation Toggle =====
    if (navToggle && navMenu) {
        function closeMobileMenu() {
            navToggle.classList.remove('active');
            navMenu.classList.remove('active');
            if (header) {
                header.classList.remove('header--menu-open');
            }
            document.body.style.overflow = '';
            navToggle.focus();
        }

        navToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            if (header) {
                header.classList.toggle('header--menu-open', navMenu.classList.contains('active'));
            }
            var isOpen = navMenu.classList.contains('active');
            document.body.style.overflow = isOpen ? 'hidden' : '';

            if (isOpen) {
                var firstLink = navMenu.querySelector('.nav__link');
                if (firstLink) firstLink.focus();
            }
        });

        // Close mobile menu when clicking on a link
        var navLinks = navMenu.querySelectorAll('.nav__link');
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navToggle.contains(e.target) && !navMenu.contains(e.target)) {
                if (navMenu.classList.contains('active')) {
                    closeMobileMenu();
                }
            }
        });

        // Focus trap and Escape key
        document.addEventListener('keydown', function(e) {
            if (!navMenu.classList.contains('active')) return;

            if (e.key === 'Escape') {
                closeMobileMenu();
                return;
            }

            if (e.key === 'Tab') {
                var focusableEls = navMenu.querySelectorAll('.nav__link');
                var firstEl = focusableEls[0];
                var lastEl = focusableEls[focusableEls.length - 1];

                if (e.shiftKey) {
                    if (document.activeElement === firstEl) {
                        e.preventDefault();
                        lastEl.focus();
                    }
                } else {
                    if (document.activeElement === lastEl) {
                        e.preventDefault();
                        firstEl.focus();
                    }
                }
            }
        });
    }

    // ===== Header Scroll Effect =====
    if (header) {
        const handleHeaderScroll = function() {
            const currentScrollY = window.scrollY;

            if (currentScrollY > 50) {
                header.classList.add('header--scrolled');
                header.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1)';
            } else {
                header.classList.remove('header--scrolled');
                header.style.boxShadow = 'none';
            }
        };

        // Run on load in case page is already scrolled
        handleHeaderScroll();

        window.addEventListener('scroll', handleHeaderScroll);
    }

    // ===== Smooth Scroll for Anchor Links =====
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');

            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);

            if (targetElement) {
                e.preventDefault();

                const headerHeight = header ? header.offsetHeight : 0;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ===== Contact Form Handling =====
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Collect form data
            const formData = new FormData(contactForm);
            const data = {};
            formData.forEach(function(value, key) {
                data[key] = value;
            });

            // Log form data (in production, this would be sent to a server)
            console.log('Form submission:', data);

            // Show success state
            const formCard = contactForm.closest('.contact-card');
            if (formCard) {
                const successTitle = i18n.get('contact.form.successTitle');
                const successMessage = i18n.get('contact.form.successMessage');
                const backHome = i18n.get('contact.form.backHome');

                formCard.innerHTML = `
                    <div class="form__success">
                        <div class="form__success-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </div>
                        <h3>${successTitle}</h3>
                        <p>${successMessage}</p>
                        <a href="index.html" class="btn btn--primary" style="margin-top: var(--space-6);">${backHome}</a>
                    </div>
                `;
            }
        });
    }

    // ===== Intersection Observer for Scroll Animations =====
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const animateOnScroll = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                // Add staggered delay for grid children
                const parent = entry.target.parentElement;
                if (parent && (parent.classList.contains('grid') || parent.classList.contains('features') || parent.classList.contains('faq-list'))) {
                    const siblings = Array.from(parent.children);
                    const index = siblings.indexOf(entry.target);
                    entry.target.style.transitionDelay = (index * 100) + 'ms';
                }

                entry.target.classList.add('is-visible');
                animateOnScroll.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Apply animation to specific elements
    const animatedElements = document.querySelectorAll(
        '.service-card, .pricing-card, .feature, .card, .faq-item, .split__content, .split__image, .section__header, .contact-info__item, .icon-list__item'
    );

    animatedElements.forEach(function(el) {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94), transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        animateOnScroll.observe(el);
    });

    // Add visible state styles
    const style = document.createElement('style');
    style.textContent = `
        .is-visible {
            opacity: 1 !important;
            transform: translateY(0) !important;
        }

        /* Counter animation for stats */
        .hero__stat-value {
            transition: transform 0.3s ease;
        }

        .hero__stat:hover .hero__stat-value {
            transform: scale(1.1);
        }
    `;
    document.head.appendChild(style);

    // ===== Parallax effect on scroll =====
    const heroBackground = document.querySelector('.hero__background');
    if (heroBackground) {
        window.addEventListener('scroll', function() {
            const scrolled = window.scrollY;
            if (scrolled < window.innerHeight) {
                heroBackground.style.transform = 'translateY(' + (scrolled * 0.4) + 'px)';
            }
        });
    }

    // ===== Magnetic effect on buttons =====
    const magneticButtons = document.querySelectorAll('.btn--primary, .btn--outline');
    magneticButtons.forEach(function(btn) {
        btn.addEventListener('mousemove', function(e) {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;

            btn.style.transform = 'translate(' + (x * 0.1) + 'px, ' + (y * 0.1) + 'px) translateY(-3px)';
        });

        btn.addEventListener('mouseleave', function() {
            btn.style.transform = '';
        });
    });

    // ===== Active Navigation Link =====
    function setActiveNavLink() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        const navLinks = document.querySelectorAll('.nav__link');

        navLinks.forEach(function(link) {
            const href = link.getAttribute('href');
            link.classList.remove('nav__link--active');

            if (href === currentPage || (currentPage === '' && href === 'index.html')) {
                link.classList.add('nav__link--active');
            }
        });
    }

    setActiveNavLink();

    // ===== Form Validation Feedback =====
    const formInputs = document.querySelectorAll('.form__input, .form__textarea');

    formInputs.forEach(function(input) {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.style.borderColor = '#dc2626';
            } else {
                this.style.borderColor = '';
            }
        });

        input.addEventListener('focus', function() {
            this.style.borderColor = '';
        });
    });

    // ===== Pricing Card Hover Effect (Desktop) =====
    const pricingCards = document.querySelectorAll('.pricing-card:not(.pricing-card--featured)');

    pricingCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            const featuredCard = document.querySelector('.pricing-card--featured');
            if (featuredCard && window.innerWidth > 768) {
                featuredCard.style.transform = 'scale(1)';
            }
        });

        card.addEventListener('mouseleave', function() {
            const featuredCard = document.querySelector('.pricing-card--featured');
            if (featuredCard && window.innerWidth > 768) {
                featuredCard.style.transform = 'scale(1.02)';
            }
        });
    });

    // ===== FAQ Accordion =====
    var faqButtons = document.querySelectorAll('.faq-item__question');
    faqButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var item = this.closest('.faq-item');
            var isOpen = item.classList.contains('faq-item--open');

            // Close all other items (true accordion)
            faqButtons.forEach(function(otherBtn) {
                var otherItem = otherBtn.closest('.faq-item');
                otherItem.classList.remove('faq-item--open');
                otherBtn.setAttribute('aria-expanded', 'false');
            });

            // Toggle clicked item
            if (!isOpen) {
                item.classList.add('faq-item--open');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

    // ===== Initialize Translation System =====
    i18n.init();

    // Expose i18n globally for debugging purposes
    window.hothelloI18n = i18n;

})();
