<?php
/**
 * Services Page - Hôtel Corintel
 * Dynamic image loading from database
 */

require_once __DIR__ . '/includes/functions.php';

// Check for active room session (set by scanning QR code via scan.php)
$roomSession = getRoomServiceSession();

// Get message categories for the contact reception modal
$messageCategories = getGuestMessageCategories();

// Load content helper for dynamic content
require_once __DIR__ . '/includes/content-helper.php';

// Get dynamic sections for the services page
$dynamicSections = getDynamicSectionsWithData('services');
$dynamicSectionsTranslations = !empty($dynamicSections) ? getDynamicSectionsTranslations('services') : [];

// Get hero image from content system
$heroImage = contentImage('services_hero', 1, 'images/resto/restaurant-hotel-bordeaux-1.jpg');

$hotelName = getHotelName();
$logoText = getLogoText();
$contactInfo = getContactInfo();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Découvrez les services de <?= h($hotelName) ?> : restaurant table d'hôtes avec cuisine régionale, bar, boulodrome et parking gratuit. Près de Bordeaux et Saint-Émilion.">
  <meta name="keywords" content="services hôtel, restaurant bordeaux, table d'hôtes, bar hôtel, pétanque, parking gratuit">
  <title>Nos Services | <?= h($hotelName) ?> - <?= h($logoText) ?></title>
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
        <a href="index.php" class="nav-link" data-i18n="nav.home">Accueil</a>
        <a href="services.php" class="nav-link active" data-i18n="nav.services">Services</a>
        <a href="activites.php" class="nav-link" data-i18n="nav.discover">À découvrir</a>
        <a href="room-service.php" class="nav-link nav-link-room-service" data-i18n="nav.roomService">Room Service <?php if ($roomSession): ?><span class="nav-room-badge">Ch. <?= h($roomSession['room_number']) ?></span><?php else: ?><span class="nav-qr-badge" data-i18n="footer.qrOnly">QR</span><?php endif; ?></a>
        <a href="contact.php" class="nav-link" data-i18n="nav.contact">Contact</a>
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

  <!-- Page Hero -->
  <section class="page-hero" style="background-image: url('<?= htmlspecialchars($heroImage) ?>');">
    <div class="page-hero-content">
      <p class="hero-subtitle" data-i18n="services.heroSubtitle"><?= h($hotelName) ?></p>
      <h1 class="page-hero-title" data-i18n="services.heroTitle">Nos Services</h1>
      <p class="page-hero-subtitle" data-i18n="services.heroDescription">Tout pour un séjour inoubliable</p>
    </div>
  </section>

  <?php
  // Render dynamic sections for the services page
  // All content sections are now managed through the admin panel
  echo renderDynamicSectionsForPage('services', 'fr');
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
            <li><a href="index.php" data-i18n="nav.home">Accueil</a></li>
            <li><a href="services.php" data-i18n="nav.services">Services</a></li>
            <li><a href="activites.php" data-i18n="nav.discover">À découvrir</a></li>
          </ul>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title" data-i18n="footer.services">Services</h4>
          <ul class="footer-links">
            <li><a href="services.php" data-i18n="footer.restaurant">Restaurant</a></li>
            <li><a href="services.php" data-i18n="footer.bar">Bar</a></li>
            <li><a href="services.php" data-i18n="footer.boulodrome">Boulodrome</a></li>
            <li><a href="services.php" data-i18n="footer.parking">Parking</a></li>
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
        <?php if ($roomSession): ?>
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
                <input type="text" id="modal_room_number" name="msg_room_number"
                    value="<?= h($roomSession['room_number']) ?>" readonly>
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
        <?php else: ?>
        <div class="modal-locked">
          <div class="modal-locked-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </div>
          <h3 data-i18n="modal.lockedTitle">Fonctionnalité réservée aux clients</h3>
          <p data-i18n="modal.lockedMessage">Scannez le QR code présent dans votre chambre pour contacter la réception.</p>
        </div>
        <?php endif; ?>
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

    function openModal() {
      modal.classList.add('active');
      document.body.classList.add('modal-open');
      menuToggle.classList.remove('active');
      navMenu.classList.remove('active');
    }

    function closeModal() {
      modal.classList.remove('active');
      document.body.classList.remove('modal-open');
    }

    btnOpenModal.addEventListener('click', openModal);
    btnCloseModal.addEventListener('click', closeModal);

    modal.addEventListener('click', (e) => {
      if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
    });

    // Form interactions — only present when room session is active
    const modalForm = document.getElementById('modalMessageForm');
    if (modalForm) {
      const modalSuccess = document.getElementById('modalSuccess');
      const modalFormContainer = document.getElementById('modalFormContainer');
      const modalError = document.getElementById('modalError');
      const btnNewMessage = document.getElementById('btnNewMessage');

      btnNewMessage.addEventListener('click', () => {
        modalForm.reset();
        modalError.style.display = 'none';
        modalSuccess.style.display = 'none';
        modalFormContainer.style.display = 'block';
      });

      modalForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(modalForm);
        modalError.style.display = 'none';

        try {
          const response = await fetch('contact.php', { method: 'POST', body: formData });
          const data = await response.json();

          if (data.success) {
            modalFormContainer.style.display = 'none';
            modalSuccess.style.display = 'block';
          } else {
            modalError.textContent = data.error || (window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.');
            modalError.style.display = 'block';
          }
        } catch (error) {
          modalError.textContent = window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.';
          modalError.style.display = 'block';
        }
      });
    }
  </script>
</body>
</html>
