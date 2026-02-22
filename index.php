<?php
/**
 * Home Page - Hôtel Corintel
 * Dynamically loads images from database
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/content-helper.php';

// Initialize pages table if needed (for dynamic navigation)
initPagesTable();

// Get navigation pages for dynamic menu
$navPages = getNavigationPages();

// Get message categories for the contact reception modal
$messageCategories = getGuestMessageCategories();

// Get hero carousel images from content system (all images, no limit)
$heroSlides = content('home_hero');

// Get hero overlay texts with all translations
$heroOverlay = getSectionOverlayWithTranslations('home_hero');

// Get dynamic sections for the home page
$dynamicSections = getDynamicSectionsWithData('home');
$dynamicSectionsTranslations = !empty($dynamicSections) ? getDynamicSectionsTranslations('home') : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php $hotelName = getHotelName(); $logoText = getLogoText(); $contactInfo = getContactInfo(); ?>
  <meta name="description" content="<?= h($hotelName) ?> - Hôtel 3 étoiles de charme près de Bordeaux et Saint-Émilion. Atmosphère chaleureuse, jardin, terrasse et cuisine régionale au cœur de la campagne bordelaise.">
  <meta name="keywords" content="hôtel bordeaux, hôtel saint-émilion, hôtel campagne, hôtel 3 étoiles, oenotourisme, bordeaux est">
  <title><?= h($hotelName) ?> | Hôtel de charme près de Bordeaux et Saint-Émilion</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <?= getThemeCSS() ?>
  <?= getHotelNameJS() ?>
</head>
<body>
  <!-- Header -->
  <header class="header" id="header">
    <div class="container">
      <a href="index.php" class="logo">
        <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>
        </svg>
        <div class="logo-text">
          <?= h($hotelName) ?>
          <span><?= h($logoText) ?></span>
        </div>
      </a>
      <nav class="nav-menu" id="navMenu">
        <?php foreach ($navPages as $navPage): ?>
        <?php
            $navUrl = $navPage['slug'] === '' ? 'index.php' : '/' . $navPage['slug'];
            $isActive = $navPage['page_type'] === 'home';
            $navI18nKey = $navPage['i18n_nav_key'] ?: '';
            // Insert Room Service link before Contact
            if ($navPage['slug'] === 'contact' || $navPage['page_type'] === 'contact'):
        ?>
        <a href="room-service.php" class="nav-link nav-link-room-service" data-i18n="nav.roomService">
          Room Service
          <span class="nav-qr-badge" data-i18n="footer.qrOnly">QR</span>
        </a>
        <?php endif; ?>
        <a href="<?= h($navUrl) ?>" class="nav-link<?= $isActive ? ' active' : '' ?>"<?= $navI18nKey ? ' data-i18n="' . h($navI18nKey) . '"' : '' ?>><?= h($navPage['nav_title'] ?: $navPage['title']) ?></a>
        <?php endforeach; ?>
        <button type="button" class="btn-contact-reception" id="btnContactReception" data-i18n="header.contactReception">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          Contacter la réception
        </button>
      </nav>
      <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </header>

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-carousel" id="heroCarousel">
      <?php if (empty($heroSlides)): ?>
        <!-- Placeholder when no images are configured -->
        <div class="hero-slide active hero-placeholder">
          <div class="hero-placeholder-content">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
              <circle cx="8.5" cy="8.5" r="1.5"/>
              <polyline points="21 15 16 10 5 21"/>
            </svg>
          </div>
          <div class="hero-overlay"></div>
        </div>
      <?php else: ?>
        <?php foreach ($heroSlides as $index => $slide): ?>
          <?php if (!empty($slide['image_filename'])): ?>
            <div class="hero-slide<?= $index === 0 ? ' active' : '' ?>">
              <img src="<?= htmlspecialchars($slide['image_filename']) ?>" alt="<?= htmlspecialchars($slide['image_alt'] ?? 'Image de ' . $hotelName) ?>">
              <div class="hero-overlay"></div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="hero-content">
      <?php
      // Default fallback texts (used if not configured in admin)
      $defaultSubtitle = 'Bienvenue à ' . $hotelName;
      $defaultTitle = 'Un havre de paix<br>aux portes de Bordeaux';
      $defaultDescription = 'Découvrez notre hôtel de charme 3 étoiles, niché dans la campagne bordelaise, à quelques minutes de Bordeaux et Saint-Émilion.';

      // Use database values if available, otherwise use defaults
      $displaySubtitle = !empty($heroOverlay['subtitle']) ? $heroOverlay['subtitle'] : $defaultSubtitle;
      $displayTitle = !empty($heroOverlay['title']) ? $heroOverlay['title'] : $defaultTitle;
      $displayDescription = !empty($heroOverlay['description']) ? $heroOverlay['description'] : $defaultDescription;

      // Check if database has overlay configured
      $hasDbOverlay = !empty($heroOverlay['subtitle']) || !empty($heroOverlay['title']) || !empty($heroOverlay['description']);

      // Prepare translations JSON for JavaScript i18n system
      $overlayTranslations = [];
      if ($hasDbOverlay) {
          $overlayTranslations = [
              'fr' => [
                  'subtitle' => $displaySubtitle,
                  'title' => $displayTitle,
                  'description' => $displayDescription
              ]
          ];
          foreach (['en', 'es', 'it'] as $lang) {
              if (isset($heroOverlay['translations'][$lang])) {
                  $trans = $heroOverlay['translations'][$lang];
                  $overlayTranslations[$lang] = [
                      'subtitle' => !empty($trans['subtitle']) ? $trans['subtitle'] : $displaySubtitle,
                      'title' => !empty($trans['title']) ? $trans['title'] : $displayTitle,
                      'description' => !empty($trans['description']) ? $trans['description'] : $displayDescription
                  ];
              } else {
                  // Fallback to French
                  $overlayTranslations[$lang] = $overlayTranslations['fr'];
              }
          }
      }
      ?>
      <?php if ($hasDbOverlay): ?>
      <p class="hero-subtitle" data-overlay-text="subtitle"><?= htmlspecialchars($displaySubtitle) ?></p>
      <h1 class="hero-title" data-overlay-text="title"><?= $displayTitle ?></h1>
      <p class="hero-description" data-overlay-text="description"><?= htmlspecialchars($displayDescription) ?></p>
      <?php else: ?>
      <p class="hero-subtitle" data-i18n="home.heroSubtitle"><?= $displaySubtitle ?></p>
      <h1 class="hero-title" data-i18n="home.heroTitle"><?= $displayTitle ?></h1>
      <p class="hero-description" data-i18n="home.heroDescription"><?= htmlspecialchars($displayDescription) ?></p>
      <?php endif; ?>
      <div class="hero-buttons">
        <a href="services.php" class="btn btn-primary" data-i18n="home.discoverServices">Découvrir nos services</a>
      </div>
    </div>
    <?php if ($hasDbOverlay): ?>
    <script>
      // Hero overlay translations from database
      window.heroOverlayTranslations = <?= json_encode($overlayTranslations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>
    <?php endif; ?>
    <?php
    // Count actual slides with images
    $slideCount = 0;
    if (!empty($heroSlides)) {
        foreach ($heroSlides as $slide) {
            if (!empty($slide['image_filename'])) {
                $slideCount++;
            }
        }
    }
    ?>
    <?php if ($slideCount > 1): ?>
    <div class="carousel-nav" id="carouselNav">
      <?php for ($i = 0; $i < $slideCount; $i++): ?>
        <span class="carousel-dot<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>"></span>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </section>

  <?php
  // Render dynamic sections for the home page
  // All content sections are now managed through the admin panel
  echo renderDynamicSectionsForPage('home', 'fr');
  if (!empty($dynamicSections)):
  ?>
  <script>
    window.dynamicSectionsTranslations = <?= json_encode($dynamicSectionsTranslations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  </script>
  <?php endif; ?>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo-text">
            <?= h($hotelName) ?>
            <span><?= h($logoText) ?></span>
          </div>
<?php $footerDescription = getHotelDescription(); if ($footerDescription): ?>
          <p><?= h($footerDescription) ?></p>
          <?php endif; ?>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title" data-i18n="footer.navigation">Navigation</h4>
          <ul class="footer-links">
            <?php foreach ($navPages as $navPage): ?>
            <?php $navUrl = $navPage['slug'] === '' ? 'index.php' : '/' . $navPage['slug']; ?>
            <li><a href="<?= h($navUrl) ?>"<?= $navPage['i18n_nav_key'] ? ' data-i18n="' . h($navPage['i18n_nav_key']) . '"' : '' ?>><?= h($navPage['nav_title'] ?: $navPage['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title" data-i18n="footer.services">Services</h4>
          <ul class="footer-links">
            <li><a href="/services" data-i18n="footer.restaurant">Restaurant</a></li>
            <li><a href="/services" data-i18n="footer.bar">Bar</a></li>
            <li class="room-service-item">
              <a href="room-service.php" data-i18n="footer.roomService">Room Service</a>
              <span class="qr-badge" data-i18n="footer.qrOnly">QR code</span>
            </li>
          </ul>
        </div>
        <div class="footer-contact">
          <h4 class="footer-title" data-i18n="footer.contactTitle">Contact</h4>
          <?php if (hasContactInfo()): ?>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span><?= getFormattedAddress() ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($contactInfo['phone'])): ?>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <span><?= h($contactInfo['phone']) ?></span>
          </div>
          <?php endif; ?>
          <?php if (!empty($contactInfo['email'])): ?>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span><?= h($contactInfo['email']) ?></span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="footer-bottom">
        <p data-i18n="footer.copyright">&copy; <?= date('Y') ?> <?= h($hotelName) ?>. Tous droits réservés.</p>
      </div>
    </div>
  </footer>

  <!-- Scroll to Top Button -->
  <button class="scroll-top" id="scrollTop" data-i18n-aria="common.backToTop" aria-label="Retour en haut">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="18 15 12 9 6 15"/>
    </svg>
  </button>

  <!-- Contact Reception Modal -->
  <div class="modal-overlay" id="contactReceptionModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 data-i18n="modal.contactReceptionTitle">Contacter la réception</h3>
        <button type="button" class="modal-close" id="modalClose" aria-label="Fermer">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="modal-body">
        <div class="modal-success" id="modalSuccess" style="display: none;">
          <div class="modal-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <h3 data-i18n="modal.successTitle">Message envoyé</h3>
          <p data-i18n="modal.successMessage">Votre message a bien été transmis à la réception. Nous vous répondrons dans les meilleurs délais.</p>
          <button type="button" class="btn-new-message" id="btnNewMessage" data-i18n="modal.newMessage">Envoyer un autre message</button>
        </div>
        <div id="modalFormContainer">
          <div class="modal-alert-error" id="modalError" style="display: none;"></div>
          <form method="POST" class="modal-form" id="modalMessageForm" action="contact.php">
            <input type="hidden" name="action" value="send_message">
            <input type="hidden" name="redirect_back" value="1">
            <div class="form-row">
              <div class="form-group">
                <label for="modal_room_number" data-i18n="modal.roomNumber">Numéro de chambre *</label>
                <input type="text" id="modal_room_number" name="msg_room_number" required placeholder="Ex: 101" data-i18n-placeholder="modal.roomNumberPlaceholder">
              </div>
              <div class="form-group">
                <label for="modal_guest_name" data-i18n="modal.guestName">Votre nom</label>
                <input type="text" id="modal_guest_name" name="msg_guest_name" placeholder="Optionnel" data-i18n-placeholder="modal.guestNamePlaceholder">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="modal_category" data-i18n="modal.category">Catégorie</label>
                <select id="modal_category" name="msg_category">
                  <?php foreach ($messageCategories as $key => $label): ?>
                    <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="modal_subject" data-i18n="modal.subject">Objet</label>
                <input type="text" id="modal_subject" name="msg_subject" placeholder="Résumé du problème" data-i18n-placeholder="modal.subjectPlaceholder">
              </div>
            </div>
            <div class="form-group">
              <label for="modal_message" data-i18n="modal.message">Votre message *</label>
              <textarea id="modal_message" name="msg_message" required placeholder="Décrivez votre demande ou problème..." data-i18n-placeholder="modal.messagePlaceholder"></textarea>
            </div>
            <button type="submit" class="btn-submit-modal" data-i18n="modal.sendMessage">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"/>
                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
              </svg>
              Envoyer le message
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="js/translations.js"></script>
  <script src="js/i18n.js"></script>
  <script src="js/animations.js"></script>
  <script>
    // Mobile menu toggle
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');

    menuToggle.addEventListener('click', () => {
      menuToggle.classList.toggle('active');
      navMenu.classList.toggle('active');
    });

    // Header scroll effect
    const header = document.getElementById('header');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 100) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });

    // Hero carousel (dynamic - handles any number of slides)
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;
    let slideInterval = null;

    // Only run carousel if there are multiple slides
    if (slides.length > 1) {
      function showSlide(index) {
        slides.forEach((slide, i) => {
          slide.classList.remove('active');
        });
        dots.forEach((dot, i) => {
          dot.classList.remove('active');
        });
        slides[index].classList.add('active');
        if (dots[index]) {
          dots[index].classList.add('active');
        }
      }

      function nextSlide() {
        currentSlide = (currentSlide + 1) % slides.length;
        showSlide(currentSlide);
      }

      // Auto-advance carousel
      slideInterval = setInterval(nextSlide, 5000);

      // Dot navigation
      dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
          currentSlide = index;
          showSlide(currentSlide);
          clearInterval(slideInterval);
          slideInterval = setInterval(nextSlide, 5000);
        });
      });
    }

    // Scroll to top button
    const scrollTop = document.getElementById('scrollTop');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 500) {
        scrollTop.classList.add('visible');
      } else {
        scrollTop.classList.remove('visible');
      }
    });

    scrollTop.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // Contact Reception Modal
    const modal = document.getElementById('contactReceptionModal');
    const btnOpenModal = document.getElementById('btnContactReception');
    const btnCloseModal = document.getElementById('modalClose');
    const modalForm = document.getElementById('modalMessageForm');
    const modalSuccess = document.getElementById('modalSuccess');
    const modalFormContainer = document.getElementById('modalFormContainer');
    const modalError = document.getElementById('modalError');
    const btnNewMessage = document.getElementById('btnNewMessage');

    function openModal() {
      modal.classList.add('active');
      document.body.classList.add('modal-open');
      // Close mobile menu if open
      menuToggle.classList.remove('active');
      navMenu.classList.remove('active');
    }

    function closeModal() {
      modal.classList.remove('active');
      document.body.classList.remove('modal-open');
    }

    function resetModalForm() {
      modalForm.reset();
      modalError.style.display = 'none';
      modalSuccess.style.display = 'none';
      modalFormContainer.style.display = 'block';
    }

    btnOpenModal.addEventListener('click', openModal);
    btnCloseModal.addEventListener('click', closeModal);
    btnNewMessage.addEventListener('click', resetModalForm);

    // Close on overlay click
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal();
      }
    });

    // Close on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) {
        closeModal();
      }
    });

    // Handle form submission via AJAX
    modalForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(modalForm);
      modalError.style.display = 'none';

      try {
        const response = await fetch('contact.php', {
          method: 'POST',
          body: formData
        });

        const text = await response.text();

        // Check if the response contains success indicators
        if (text.includes('message_sent=1') || text.includes('Message envoyé') || response.ok) {
          // Show success state
          modalFormContainer.style.display = 'none';
          modalSuccess.style.display = 'block';
        } else {
          // Extract error message if present
          const errorMatch = text.match(/alert-message-error[^>]*>([^<]+)/);
          if (errorMatch) {
            modalError.textContent = errorMatch[1];
          } else {
            modalError.textContent = window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.';
          }
          modalError.style.display = 'block';
        }
      } catch (error) {
        modalError.textContent = window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.';
        modalError.style.display = 'block';
      }
    });
  </script>
</body>
</html>
