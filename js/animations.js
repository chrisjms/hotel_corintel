/**
 * Animations - Client Site
 * Hôtel Corintel
 *
 * Scroll-triggered animations and micro-interactions
 */

(function() {
  'use strict';

  // Configuration
  const config = {
    revealThreshold: 0.15, // 15% of element must be visible
    staggerDelay: 100,     // ms between staggered children
    rootMargin: '0px 0px -50px 0px'
  };

  /**
   * Initialize Intersection Observer for reveal animations
   */
  function initRevealAnimations() {
    // Select all elements with reveal classes
    const revealElements = document.querySelectorAll(
      '.reveal, .reveal-left, .reveal-right, .reveal-scale, .stagger-children'
    );

    if (!revealElements.length) return;

    // Check for Intersection Observer support
    if (!('IntersectionObserver' in window)) {
      // Fallback: show all elements immediately
      revealElements.forEach(el => el.classList.add('active'));
      return;
    }

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          // Optionally unobserve after reveal (better performance)
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: config.revealThreshold,
      rootMargin: config.rootMargin
    });

    revealElements.forEach(el => observer.observe(el));
  }

  /**
   * Add reveal classes to common elements automatically
   */
  function autoAddRevealClasses() {
    // Section headers
    document.querySelectorAll('.section-header').forEach(el => {
      if (!el.classList.contains('reveal')) {
        el.classList.add('reveal');
      }
    });

    // Service cards with stagger
    document.querySelectorAll('.services-grid').forEach(grid => {
      if (!grid.classList.contains('stagger-children')) {
        grid.classList.add('stagger-children');
      }
    });

    // Activity cards
    document.querySelectorAll('.activities-grid').forEach(grid => {
      if (!grid.classList.contains('stagger-children')) {
        grid.classList.add('stagger-children');
      }
    });

    // Room features
    document.querySelectorAll('.rooms-features').forEach(grid => {
      if (!grid.classList.contains('stagger-children')) {
        grid.classList.add('stagger-children');
      }
    });

    // Intro grid - alternate directions
    document.querySelectorAll('.intro-grid').forEach((grid, index) => {
      const imageEl = grid.querySelector('.intro-image');
      const contentEl = grid.querySelector('.intro-content');

      if (imageEl && !imageEl.classList.contains('reveal-left') && !imageEl.classList.contains('reveal-right')) {
        imageEl.classList.add(index % 2 === 0 ? 'reveal-left' : 'reveal-right');
      }
      if (contentEl && !contentEl.classList.contains('reveal-left') && !contentEl.classList.contains('reveal-right')) {
        contentEl.classList.add(index % 2 === 0 ? 'reveal-right' : 'reveal-left');
      }
    });

    // Service detail sections - alternate directions
    document.querySelectorAll('.service-detail').forEach((detail, index) => {
      const imageEl = detail.querySelector('.service-detail-image');
      const contentEl = detail.querySelector('.service-detail-content');

      if (imageEl && !imageEl.classList.contains('reveal-left') && !imageEl.classList.contains('reveal-right')) {
        imageEl.classList.add(index % 2 === 0 ? 'reveal-left' : 'reveal-right');
      }
      if (contentEl && !contentEl.classList.contains('reveal-left') && !contentEl.classList.contains('reveal-right')) {
        contentEl.classList.add(index % 2 === 0 ? 'reveal-right' : 'reveal-left');
      }
    });

    // Contact grid
    document.querySelectorAll('.contact-grid').forEach(grid => {
      const infoEl = grid.querySelector('.contact-info');
      const formEl = grid.querySelector('.contact-form-wrapper');

      if (infoEl && !infoEl.classList.contains('reveal-left')) {
        infoEl.classList.add('reveal-left');
      }
      if (formEl && !formEl.classList.contains('reveal-right')) {
        formEl.classList.add('reveal-right');
      }
    });

    // Cards with scale effect
    document.querySelectorAll('.room-card, .activity-card').forEach(card => {
      if (!card.classList.contains('reveal-scale')) {
        card.classList.add('reveal-scale');
      }
    });

    // CTA section
    document.querySelectorAll('.cta-section').forEach(el => {
      if (!el.classList.contains('reveal')) {
        el.classList.add('reveal');
      }
    });

    // Footer
    document.querySelectorAll('.footer-grid').forEach(el => {
      if (!el.classList.contains('stagger-children')) {
        el.classList.add('stagger-children');
      }
    });
  }

  /**
   * Parallax effect (disabled for hero)
   */
  function initParallax() {
    // Parallax disabled for hero section
  }

  /**
   * Smooth counter animation for statistics
   */
  function animateCounters() {
    const counters = document.querySelectorAll('[data-counter]');

    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const counter = entry.target;
          const target = parseInt(counter.getAttribute('data-counter'), 10);
          const duration = 2000;
          const start = 0;
          const startTime = performance.now();

          function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function (ease-out)
            const easeOut = 1 - Math.pow(1 - progress, 3);
            const current = Math.floor(start + (target - start) * easeOut);

            counter.textContent = current.toLocaleString();

            if (progress < 1) {
              requestAnimationFrame(updateCounter);
            }
          }

          requestAnimationFrame(updateCounter);
          observer.unobserve(counter);
        }
      });
    }, { threshold: 0.5 });

    counters.forEach(counter => observer.observe(counter));
  }

  /**
   * Enhanced hover effects with touch support
   */
  function initTouchHoverEffects() {
    // Check for touch device
    const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    if (isTouchDevice) {
      document.body.classList.add('touch-device');
    }
  }

  /**
   * Magnetic button effect
   */
  function initMagneticButtons() {
    const buttons = document.querySelectorAll('.btn-primary, .btn-book');

    buttons.forEach(btn => {
      btn.addEventListener('mousemove', (e) => {
        const rect = btn.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;

        btn.style.transform = `translate(${x * 0.1}px, ${y * 0.1}px)`;
      });

      btn.addEventListener('mouseleave', () => {
        btn.style.transform = '';
      });
    });
  }

  /**
   * Cursor follower for images (optional, disabled by default)
   */
  function initCursorEffect() {
    // Disabled by default - uncomment to enable
    /*
    const cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    document.body.appendChild(cursor);

    document.addEventListener('mousemove', (e) => {
      cursor.style.left = e.clientX + 'px';
      cursor.style.top = e.clientY + 'px';
    });

    document.querySelectorAll('a, button, .service-card, .activity-card').forEach(el => {
      el.addEventListener('mouseenter', () => cursor.classList.add('cursor-hover'));
      el.addEventListener('mouseleave', () => cursor.classList.remove('cursor-hover'));
    });
    */
  }

  /**
   * Typing effect for hero text (optional)
   */
  function initTypingEffect() {
    const typingElements = document.querySelectorAll('[data-typing]');

    typingElements.forEach(el => {
      const text = el.textContent;
      el.textContent = '';
      el.style.visibility = 'visible';

      let i = 0;
      const typing = setInterval(() => {
        if (i < text.length) {
          el.textContent += text.charAt(i);
          i++;
        } else {
          clearInterval(typing);
        }
      }, 50);
    });
  }

  /**
   * Image lazy loading with fade-in
   */
  function initLazyImages() {
    const images = document.querySelectorAll('img[data-src]');

    if (!images.length) return;

    const imageObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          img.classList.add('loaded');
          imageObserver.unobserve(img);
        }
      });
    }, { rootMargin: '50px' });

    images.forEach(img => imageObserver.observe(img));
  }

  /**
   * Initialize all animations
   */
  function init() {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initAnimations);
    } else {
      initAnimations();
    }
  }

  function initAnimations() {
    // Auto-add reveal classes to elements
    autoAddRevealClasses();

    // Initialize reveal animations
    initRevealAnimations();

    // Initialize parallax (subtle)
    initParallax();

    // Initialize counters
    animateCounters();

    // Touch device handling
    initTouchHoverEffects();

    // Magnetic buttons (desktop only)
    if (window.matchMedia('(min-width: 1024px)').matches) {
      initMagneticButtons();
    }

    // Lazy images
    initLazyImages();

    // Log initialization
    console.log('🎨 Animations initialized');
  }

  // Start
  init();

})();
