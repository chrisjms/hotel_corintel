/**
 * Hotel Corintel - Internationalization (i18n) System
 * Handles language switching and content translation
 *
 * To add a new language:
 * 1. Add the language to translations.js (in the languages object and as a full translation object)
 * 2. The system will automatically detect and include it in the language selector
 */

(function() {
  'use strict';

  const I18n = {
    currentLang: 'fr',
    defaultLang: 'fr',
    storageKey: 'hotel_corintel_lang',

    /**
     * Initialize the i18n system
     */
    init() {
      // Get saved language or detect from browser
      this.currentLang = this.getSavedLanguage() || this.detectLanguage();

      // Create language selector
      this.createLanguageSelector();

      // Apply translations
      this.applyTranslations();

      // Update HTML lang attribute
      document.documentElement.lang = this.currentLang;
    },

    /**
     * Get saved language from localStorage
     */
    getSavedLanguage() {
      try {
        const saved = localStorage.getItem(this.storageKey);
        if (saved && window.translations && window.translations[saved]) {
          return saved;
        }
      } catch (e) {
        console.warn('localStorage not available');
      }
      return null;
    },

    /**
     * Detect language from browser settings
     */
    detectLanguage() {
      const browserLang = navigator.language || navigator.userLanguage;
      const langCode = browserLang.split('-')[0].toLowerCase();

      // Check if we support this language
      if (window.translations && window.translations[langCode]) {
        return langCode;
      }

      return this.defaultLang;
    },

    /**
     * Save language preference
     */
    saveLanguage(lang) {
      try {
        localStorage.setItem(this.storageKey, lang);
      } catch (e) {
        console.warn('Could not save language preference');
      }
    },

    /**
     * Create the language selector dropdown
     */
    createLanguageSelector() {
      const languages = window.translations.languages;
      const nav = document.querySelector('.nav-menu');

      if (!nav) return;

      // Create language selector container
      const selector = document.createElement('div');
      selector.className = 'lang-selector';
      selector.setAttribute('role', 'listbox');
      selector.setAttribute('aria-label', 'Select language');

      // Current language button
      const currentLangData = languages[this.currentLang];
      const button = document.createElement('button');
      button.className = 'lang-current';
      button.setAttribute('aria-expanded', 'false');
      button.setAttribute('aria-haspopup', 'listbox');
      button.innerHTML = `
        <span class="lang-flag">${currentLangData.flag}</span>
        <span class="lang-code">${this.currentLang.toUpperCase()}</span>
        <svg class="lang-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="6 9 12 15 18 9"/>
        </svg>
      `;

      // Dropdown menu
      const dropdown = document.createElement('div');
      dropdown.className = 'lang-dropdown';
      dropdown.setAttribute('role', 'listbox');

      // Add language options
      Object.keys(languages).forEach(langCode => {
        const lang = languages[langCode];
        const option = document.createElement('button');
        option.className = 'lang-option' + (langCode === this.currentLang ? ' active' : '');
        option.setAttribute('role', 'option');
        option.setAttribute('aria-selected', langCode === this.currentLang);
        option.dataset.lang = langCode;
        option.innerHTML = `
          <span class="lang-flag">${lang.flag}</span>
          <span class="lang-name">${lang.name}</span>
        `;

        option.addEventListener('click', (e) => {
          e.preventDefault();
          this.switchLanguage(langCode);
        });

        dropdown.appendChild(option);
      });

      selector.appendChild(button);
      selector.appendChild(dropdown);

      // Insert before the book button
      const bookBtn = nav.querySelector('.btn-book');
      if (bookBtn) {
        nav.insertBefore(selector, bookBtn);
      } else {
        nav.appendChild(selector);
      }

      // Toggle dropdown
      button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isOpen = selector.classList.contains('open');
        selector.classList.toggle('open');
        button.setAttribute('aria-expanded', !isOpen);
      });

      // Close on outside click
      document.addEventListener('click', (e) => {
        if (!selector.contains(e.target)) {
          selector.classList.remove('open');
          button.setAttribute('aria-expanded', 'false');
        }
      });

      // Close on escape key
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          selector.classList.remove('open');
          button.setAttribute('aria-expanded', 'false');
        }
      });
    },

    /**
     * Switch to a different language
     */
    switchLanguage(lang) {
      if (lang === this.currentLang) return;
      if (!window.translations[lang]) return;

      this.currentLang = lang;
      this.saveLanguage(lang);
      this.applyTranslations();
      this.updateLanguageSelector();

      // Update HTML lang attribute
      document.documentElement.lang = lang;
    },

    /**
     * Update the language selector UI
     */
    updateLanguageSelector() {
      const languages = window.translations.languages;
      const currentLangData = languages[this.currentLang];

      // Update current button
      const button = document.querySelector('.lang-current');
      if (button) {
        button.querySelector('.lang-flag').textContent = currentLangData.flag;
        button.querySelector('.lang-code').textContent = this.currentLang.toUpperCase();
      }

      // Update active state in dropdown
      document.querySelectorAll('.lang-option').forEach(option => {
        const isActive = option.dataset.lang === this.currentLang;
        option.classList.toggle('active', isActive);
        option.setAttribute('aria-selected', isActive);
      });

      // Close dropdown
      const selector = document.querySelector('.lang-selector');
      if (selector) {
        selector.classList.remove('open');
      }
    },

    /**
     * Apply translations to all elements with data-i18n attribute
     */
    applyTranslations() {
      const t = window.translations[this.currentLang];
      if (!t) return;

      // Translate elements with data-i18n attribute
      document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.dataset.i18n;
        const value = this.getNestedValue(t, key);

        if (value !== undefined) {
          // Check if it's an input placeholder
          if (el.hasAttribute('placeholder') && el.dataset.i18nAttr === 'placeholder') {
            el.placeholder = value;
          }
          // Check for other attribute translations
          else if (el.dataset.i18nAttr) {
            el.setAttribute(el.dataset.i18nAttr, value);
          }
          // Default: set innerHTML (allows for HTML in translations)
          else {
            el.innerHTML = value;
          }
        }
      });

      // Translate placeholders separately
      document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.dataset.i18nPlaceholder;
        const value = this.getNestedValue(t, key);
        if (value !== undefined) {
          el.placeholder = value;
        }
      });

      // Translate aria-labels
      document.querySelectorAll('[data-i18n-aria]').forEach(el => {
        const key = el.dataset.i18nAria;
        const value = this.getNestedValue(t, key);
        if (value !== undefined) {
          el.setAttribute('aria-label', value);
        }
      });

      // Translate title attributes
      document.querySelectorAll('[data-i18n-title]').forEach(el => {
        const key = el.dataset.i18nTitle;
        const value = this.getNestedValue(t, key);
        if (value !== undefined) {
          el.title = value;
        }
      });

      // Update page title if data attribute exists on title element or body
      const pageTitleKey = document.body.dataset.i18nTitle;
      if (pageTitleKey) {
        const pageTitle = this.getNestedValue(t, pageTitleKey);
        if (pageTitle) {
          document.title = pageTitle + ' | Hôtel Corintel';
        }
      }

      // Handle database-driven overlay translations
      this.applyOverlayTranslations();
    },

    /**
     * Apply overlay translations from database for dynamic content sections
     */
    applyOverlayTranslations() {
      // Hero section overlay (data-overlay-text)
      if (window.heroOverlayTranslations) {
        const heroTrans = window.heroOverlayTranslations[this.currentLang] || window.heroOverlayTranslations['fr'];
        if (heroTrans) {
          document.querySelectorAll('[data-overlay-text]').forEach(el => {
            const field = el.dataset.overlayText;
            if (heroTrans[field]) {
              el.innerHTML = heroTrans[field];
            }
          });
        }
      }

      // Intro section overlay (data-overlay-intro)
      if (window.introOverlayTranslations) {
        const introTrans = window.introOverlayTranslations[this.currentLang] || window.introOverlayTranslations['fr'];
        if (introTrans) {
          document.querySelectorAll('[data-overlay-intro]').forEach(el => {
            const field = el.dataset.overlayIntro;
            if (introTrans[field]) {
              if (field === 'description') {
                // Split description into paragraphs and render
                const paragraphs = introTrans[field].split(/\n\s*\n/).filter(p => p.trim());
                el.innerHTML = paragraphs.map(p => '<p>' + p.replace(/\n/g, '<br>') + '</p>').join('');
              } else {
                el.innerHTML = introTrans[field];
              }
            }
          });
        }
      }

      // Feature translations (data-feature-id)
      if (window.introFeatureTranslations) {
        document.querySelectorAll('[data-feature-id]').forEach(el => {
          const featureId = el.dataset.featureId;
          const featureTrans = window.introFeatureTranslations[featureId];
          if (featureTrans) {
            const label = featureTrans[this.currentLang] || featureTrans['fr'];
            if (label) {
              el.textContent = label;
            }
          }
        });
      }

      // Dynamic sections translations (data-dynamic-text and data-dynamic-feature)
      if (window.dynamicSectionsTranslations) {
        // Handle dynamic section texts (data-dynamic-text="sectionCode:field")
        document.querySelectorAll('[data-dynamic-text]').forEach(el => {
          const [sectionCode, field] = el.dataset.dynamicText.split(':');
          const sectionTrans = window.dynamicSectionsTranslations[sectionCode];
          if (sectionTrans) {
            const langTrans = sectionTrans[this.currentLang] || sectionTrans['fr'];
            if (langTrans && langTrans[field]) {
              if (field === 'description') {
                // Split description into paragraphs
                const paragraphs = langTrans[field].split(/\n\s*\n/).filter(p => p.trim());
                el.innerHTML = paragraphs.map(p => '<p>' + p.replace(/\n/g, '<br>') + '</p>').join('');
              } else {
                el.textContent = langTrans[field];
              }
            }
          }
        });

        // Handle dynamic section features (data-dynamic-feature="featureId")
        document.querySelectorAll('[data-dynamic-feature]').forEach(el => {
          const featureId = el.dataset.dynamicFeature;
          // Find the section this feature belongs to
          for (const sectionCode in window.dynamicSectionsTranslations) {
            const section = window.dynamicSectionsTranslations[sectionCode];
            if (section.features && section.features[featureId]) {
              const featureTrans = section.features[featureId];
              const label = featureTrans[this.currentLang] || featureTrans['fr'];
              if (label) {
                el.textContent = label;
              }
              break;
            }
          }
        });

        // Handle dynamic section services (data-dynamic-service="serviceId:field")
        document.querySelectorAll('[data-dynamic-service]').forEach(el => {
          const [serviceId, field] = el.dataset.dynamicService.split(':');
          // Find the section this service belongs to
          for (const sectionCode in window.dynamicSectionsTranslations) {
            const section = window.dynamicSectionsTranslations[sectionCode];
            if (section.services && section.services[serviceId]) {
              const serviceTrans = section.services[serviceId];
              const langData = serviceTrans[this.currentLang] || serviceTrans['fr'];
              if (langData && langData[field]) {
                el.textContent = langData[field];
              }
              break;
            }
          }
        });

        // Handle dynamic section gallery items (data-dynamic-gallery="itemId:field")
        document.querySelectorAll('[data-dynamic-gallery]').forEach(el => {
          const [itemId, field] = el.dataset.dynamicGallery.split(':');
          // Find the section this gallery item belongs to
          for (const sectionCode in window.dynamicSectionsTranslations) {
            const section = window.dynamicSectionsTranslations[sectionCode];
            if (section.gallery && section.gallery[itemId]) {
              const itemTrans = section.gallery[itemId];
              const langData = itemTrans[this.currentLang] || itemTrans['fr'];
              if (langData && langData[field]) {
                el.textContent = langData[field];
              }
              break;
            }
          }
        });
      }
    },

    /**
     * Get nested value from object using dot notation
     * e.g., getNestedValue(obj, 'home.heroTitle') returns obj.home.heroTitle
     */
    getNestedValue(obj, path) {
      return path.split('.').reduce((current, key) => {
        return current && current[key] !== undefined ? current[key] : undefined;
      }, obj);
    },

    /**
     * Get a translation value programmatically
     */
    t(key) {
      const t = window.translations[this.currentLang];
      return this.getNestedValue(t, key) || key;
    }
  };

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => I18n.init());
  } else {
    I18n.init();
  }

  // Expose I18n globally for programmatic access
  window.I18n = I18n;
})();
