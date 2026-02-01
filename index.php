<?php
/**
 * Home Page - Hôtel Corintel
 * Dynamically loads images from database
 */

require_once __DIR__ . '/includes/functions.php';

// Get message categories for the contact reception modal
$messageCategories = getGuestMessageCategories();

// Try to load images from database, fallback to defaults if unavailable
$useDatabase = false;
$images = [];

try {
    require_once __DIR__ . '/includes/images-helper.php';
    $images = sectionImages('home');
    $useDatabase = !empty($images);
} catch (Exception $e) {
    // Database not available, use default images
}

// Helper function to get image path
function getImg($images, $position, $fallback) {
    foreach ($images as $img) {
        if ($img['position'] == $position) {
            return $img['filename'];
        }
    }
    return $fallback;
}

// Define image paths (database or fallback)
$heroSlide1 = $useDatabase ? getImg($images, 1, 'images/acceuil/plan-large3.png') : 'images/acceuil/plan-large3.png';
$heroSlide2 = $useDatabase ? getImg($images, 2, 'images/resto/restaurant-hotel-bordeaux-1.jpg') : 'images/resto/restaurant-hotel-bordeaux-1.jpg';
$heroSlide3 = $useDatabase ? getImg($images, 3, 'images/acceuil/bar.jpg') : 'images/acceuil/bar.jpg';
$introImage = $useDatabase ? getImg($images, 4, 'images/acceuil/entree-hotel.jpeg') : 'images/acceuil/entree-hotel.jpeg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Hôtel Corintel - Hôtel 3 étoiles de charme près de Bordeaux et Saint-Émilion. Atmosphère chaleureuse, jardin, terrasse et cuisine régionale au cœur de la campagne bordelaise.">
  <meta name="keywords" content="hôtel bordeaux, hôtel saint-émilion, hôtel campagne, hôtel 3 étoiles, oenotourisme, bordeaux est">
  <title>Hôtel Corintel | Hôtel de charme près de Bordeaux et Saint-Émilion</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
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
          Hôtel Corintel
          <span data-i18n="header.logoSubtitle">Bordeaux Est</span>
        </div>
      </a>
      <nav class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-link active" data-i18n="nav.home">Accueil</a>
        <a href="services.php" class="nav-link" data-i18n="nav.services">Services</a>
        <a href="room-service.php" class="nav-link" data-i18n="nav.roomService">Room Service</a>
        <a href="activites.php" class="nav-link" data-i18n="nav.discover">À découvrir</a>
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

  <!-- Hero Section -->
  <section class="hero">
    <div class="hero-carousel" id="heroCarousel">
      <div class="hero-slide active">
        <img src="<?= htmlspecialchars($heroSlide1) ?>" alt="Vue panoramique de l'Hôtel Corintel">
        <div class="hero-overlay"></div>
      </div>
      <div class="hero-slide">
        <img src="<?= htmlspecialchars($heroSlide2) ?>" alt="Vue du restaurant">
        <div class="hero-overlay"></div>
      </div>
      <div class="hero-slide">
        <img src="<?= htmlspecialchars($heroSlide3) ?>" alt="Bar de l'Hôtel Corintel">
        <div class="hero-overlay"></div>
      </div>
    </div>
    <div class="hero-content">
      <p class="hero-subtitle" data-i18n="home.heroSubtitle">Bienvenue à l'Hôtel Corintel</p>
      <h1 class="hero-title" data-i18n="home.heroTitle">Un havre de paix<br>aux portes de Bordeaux</h1>
      <p class="hero-description" data-i18n="home.heroDescription">
        Découvrez notre hôtel de charme 3 étoiles, niché dans la campagne bordelaise,
        à quelques minutes de Bordeaux et Saint-Émilion.
      </p>
      <div class="hero-buttons">
        <a href="services.php" class="btn btn-primary" data-i18n="home.discoverServices">Découvrir nos services</a>
      </div>
    </div>
    <div class="carousel-nav" id="carouselNav">
      <span class="carousel-dot active" data-index="0"></span>
      <span class="carousel-dot" data-index="1"></span>
      <span class="carousel-dot" data-index="2"></span>
    </div>
  </section>

  <!-- Introduction Section -->
  <section class="section section-light">
    <div class="container">
      <div class="intro-grid">
        <div class="intro-image">
          <img src="<?= htmlspecialchars($introImage) ?>" alt="L'entrée accueillante de l'Hôtel Corintel">
        </div>
        <div class="intro-content">
          <p class="section-subtitle" data-i18n="home.introSubtitle">Notre philosophie</p>
          <h2 data-i18n="home.introTitle">Une atmosphère chaleureuse et conviviale</h2>
          <p data-i18n="home.introText1">
            L'Hôtel Corintel vous accueille dans un cadre paisible et verdoyant,
            où se mêlent le charme de la campagne bordelaise et le confort d'un
            établissement 3 étoiles.
          </p>
          <p data-i18n="home.introText2">
            Entouré de nature, notre hôtel offre une expérience de détente authentique.
            Profitez de notre jardin, de notre terrasse ombragée et de notre salon commun
            pour des moments de quiétude loin du tumulte de la ville.
          </p>
          <div class="intro-features">
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
              <span data-i18n="home.featureGarden">Jardin paisible</span>
            </div>
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 11h1a3 3 0 0 1 0 6h-1M2 11h14v7a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-7zM6 7v4M10 7v4M14 7v4M8 3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2 0z"/>
              </svg>
              <span data-i18n="home.featureTerrace">Terrasse ombragée</span>
            </div>
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
              </svg>
              <span data-i18n="home.featureLounge">Salon commun</span>
            </div>
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="1" y="3" width="15" height="13" rx="2" ry="2"/>
                <path d="M16 8h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1"/>
              </svg>
              <span data-i18n="home.featureParking">Parking gratuit</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Services Preview Section -->
  <section class="section section-cream">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="home.servicesSubtitle">Nos services</p>
        <h2 class="section-title" data-i18n="home.servicesTitle">Tout pour votre confort</h2>
        <p class="section-description" data-i18n="home.servicesDescription">
          De la table d'hôtes au boulodrome, découvrez tous les services
          qui rendront votre séjour inoubliable.
        </p>
      </div>
      <div class="services-grid">
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M18 8h1a4 4 0 0 1 0 8h-1M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
              <line x1="6" y1="1" x2="6" y2="4"/>
              <line x1="10" y1="1" x2="10" y2="4"/>
              <line x1="14" y1="1" x2="14" y2="4"/>
            </svg>
          </div>
          <h3 data-i18n="home.serviceRestaurant">Table d'hôtes</h3>
          <p data-i18n="home.serviceRestaurantDesc">Savourez une cuisine régionale authentique pour le petit-déjeuner et le dîner, préparée avec des produits locaux.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M8 21h8M12 17v4M17 5a7 7 0 1 1-10 0"/>
              <path d="M12 8v4"/>
            </svg>
          </div>
          <h3 data-i18n="home.serviceBar">Bar</h3>
          <p data-i18n="home.serviceBarDesc">Détendez-vous dans notre bar chaleureux et dégustez une sélection de vins de Bordeaux et de cocktails.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </div>
          <h3 data-i18n="home.serviceBoulodrome">Boulodrome</h3>
          <p data-i18n="home.serviceBoulodromeDesc">Profitez de notre terrain de pétanque pour des moments conviviaux entre amis ou en famille.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="1" y="3" width="15" height="13" rx="2" ry="2"/>
              <path d="M16 8h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1"/>
            </svg>
          </div>
          <h3 data-i18n="home.serviceParkingTitle">Parking gratuit</h3>
          <p data-i18n="home.serviceParkingDesc">Stationnement privé et sécurisé offert à tous nos clients, pour un séjour en toute tranquillité.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <h2 data-i18n="home.ctaTitle">Découvrez notre hôtel</h2>
      <p data-i18n="home.ctaText">Offrez-vous un séjour ressourçant au cœur de la campagne bordelaise</p>
      <a href="services.php" class="btn btn-primary" data-i18n="home.discoverServices">Découvrir nos services</a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo-text">
            Hôtel Corintel
            <span data-i18n="header.logoSubtitle">Bordeaux Est</span>
          </div>
          <p data-i18n="footer.description">Un havre de paix aux portes de Bordeaux, où charme et authenticité vous attendent pour un séjour inoubliable.</p>
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
            <li><a href="services.php#restaurant">Restaurant</a></li>
            <li><a href="services.php#bar">Bar</a></li>
            <li><a href="services.php#boulodrome">Boulodrome</a></li>
            <li><a href="services.php#parking">Parking</a></li>
          </ul>
        </div>
        <div class="footer-contact">
          <h4 class="footer-title" data-i18n="footer.contactTitle">Contact</h4>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span>14 Avenue du Périgord. 33370 TRESSES<br>Gironde, France</span>
          </div>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
            </svg>
            <span>+33 5 57 34 13 95</span>
          </div>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span>hotel.bordeaux.tresses@gmail.com</span>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <p data-i18n="footer.copyright">&copy; 2024 Hôtel Corintel. Tous droits réservés.</p>
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

    // Hero carousel
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    let currentSlide = 0;

    function showSlide(index) {
      slides.forEach((slide, i) => {
        slide.classList.remove('active');
        dots[i].classList.remove('active');
      });
      slides[index].classList.add('active');
      dots[index].classList.add('active');
    }

    function nextSlide() {
      currentSlide = (currentSlide + 1) % slides.length;
      showSlide(currentSlide);
    }

    // Auto-advance carousel
    let slideInterval = setInterval(nextSlide, 5000);

    // Dot navigation
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        currentSlide = index;
        showSlide(currentSlide);
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 5000);
      });
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
