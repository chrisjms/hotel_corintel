<?php
/**
 * Contact Page - Hôtel Corintel
 * Dynamic image loading from database
 */

// Initialize database images
$useDatabase = false;
$images = [];

try {
    require_once __DIR__ . '/includes/images-helper.php';
    $images = sectionImages('contact');
    $useDatabase = !empty($images);
} catch (Exception $e) {
    // Database unavailable, use fallback images
}

/**
 * Get image URL by position, with fallback
 */
function getImg($images, $position, $fallback) {
    foreach ($images as $img) {
        if ($img['position'] == $position) {
            return $img['filename'];
        }
    }
    return $fallback;
}

// Define image variables with fallbacks
$heroImage = $useDatabase ? getImg($images, 1, 'images/acceuil/dehors_nuit.jpg') : 'images/acceuil/dehors_nuit.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Contactez l'Hôtel Corintel près de Bordeaux. Adresse, téléphone, email et formulaire de contact. Situé à Tresses, Bordeaux Est, Gironde.">
  <meta name="keywords" content="contact hôtel bordeaux, adresse hôtel tresses, réservation hôtel gironde, hôtel bordeaux est">
  <title>Contact | Hôtel Corintel - Bordeaux Est</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body data-i18n-title="contact.pageTitle">
  <!-- Header -->
  <header class="header" id="header">
    <div class="container">
      <a href="index.php" class="logo">
        <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>
        </svg>
        <div class="logo-text">
          Hôtel Corintel
          <span>Bordeaux Est</span>
        </div>
      </a>
      <nav class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-link" data-i18n="nav.home">Accueil</a>
        <a href="services.php" class="nav-link" data-i18n="nav.services">Services</a>
        <a href="room-service.php" class="nav-link" data-i18n="nav.roomService">Room Service</a>
        <a href="activites.php" class="nav-link" data-i18n="nav.activities">À découvrir</a>
        <a href="contact.php" class="nav-link active" data-i18n="nav.contact">Contact</a>
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
      <p class="hero-subtitle" data-i18n="contact.heroSubtitle">Nous contacter</p>
      <h1 class="page-hero-title" data-i18n="contact.heroTitle">Contact</h1>
      <p class="page-hero-subtitle" data-i18n="contact.heroDescription">Nous sommes à votre écoute</p>
    </div>
  </section>

  <!-- Contact Section -->
  <section class="section section-light">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="contact.introSubtitle">Restons en contact</p>
        <h2 class="section-title" data-i18n="contact.introTitle">Comment nous joindre</h2>
        <p class="section-description" data-i18n="contact.introDescription">
          Une question, une demande de renseignements ou une réservation ?
          N'hésitez pas à nous contacter. Notre équipe se fera un plaisir
          de vous répondre dans les plus brefs délais.
        </p>
      </div>

      <div class="contact-grid">
        <!-- Contact Information -->
        <div class="contact-info">
          <h3 data-i18n="contact.infoTitle">Nos coordonnées</h3>
          <div class="contact-details">
            <div class="contact-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/>
              </svg>
              <div class="contact-item-text">
                <h4 data-i18n="contact.addressLabel">Adresse</h4>
                <p>
                  Hôtel Corintel<br>
                  14 Avenue du Périgord<br>
                  33370 TRESSES, France
                </p>
              </div>
            </div>
            <div class="contact-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
              </svg>
              <div class="contact-item-text">
                <h4 data-i18n="contact.phoneLabel">Téléphone</h4>
                <p><a href="tel:+33557341395">+33 5 57 34 13 95</a></p>
              </div>
            </div>
            <div class="contact-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
              </svg>
              <div class="contact-item-text">
                <h4>Email</h4>
                <p><a href="mailto:hotel.bordeaux.tresses@gmail.com">hotel.bordeaux.tresses@gmail.com</a></p>
              </div>
            </div>
            <div class="contact-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
              </svg>
              <div class="contact-item-text">
                <h4 data-i18n="contact.receptionLabel">Réception</h4>
                <p data-i18n="contact.receptionHours">
                  Ouverte 7j/7<br>
                  7h00 - 22h00
                </p>
              </div>
            </div>
          </div>

          <!-- Map -->
          <h3 data-i18n="contact.findUs">Nous trouver</h3>
          <div class="contact-map">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2829.8!2d-0.4833!3d44.8556!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDTCsDUxJzIwLjIiTiAwwrAyOScwMC4wIlc!5e0!3m2!1sfr!2sfr!4v1704000000000!5m2!1sfr!2sfr"
              allowfullscreen=""
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              title="Localisation de l'Hôtel Corintel">
            </iframe>
          </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-wrapper">
          <h3 data-i18n="contact.formTitle">Envoyez-nous un message</h3>
          <form class="contact-form" id="contactForm" action="#" method="POST">
            <div class="form-row">
              <div class="form-group">
                <label for="firstName" data-i18n="contact.firstNameLabel">Prénom *</label>
                <input type="text" id="firstName" name="firstName" required data-i18n-placeholder="contact.firstNamePlaceholder" placeholder="Votre prénom">
              </div>
              <div class="form-group">
                <label for="lastName" data-i18n="contact.lastNameLabel">Nom *</label>
                <input type="text" id="lastName" name="lastName" required data-i18n-placeholder="contact.lastNamePlaceholder" placeholder="Votre nom">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="email" data-i18n="contact.emailLabel">Email *</label>
                <input type="email" id="email" name="email" required data-i18n-placeholder="contact.emailPlaceholder" placeholder="votre@email.com">
              </div>
              <div class="form-group">
                <label for="phone" data-i18n="contact.phoneLabelForm">Téléphone</label>
                <input type="tel" id="phone" name="phone" data-i18n-placeholder="contact.phonePlaceholder" placeholder="+33 6 XX XX XX XX">
              </div>
            </div>
            <div class="form-group">
              <label for="subject" data-i18n="contact.subjectLabel">Objet *</label>
              <input type="text" id="subject" name="subject" required data-i18n-placeholder="contact.subjectPlaceholder" placeholder="Objet de votre message">
            </div>
            <div class="form-group">
              <label for="message" data-i18n="contact.messageLabel">Message *</label>
              <textarea id="message" name="message" required data-i18n-placeholder="contact.messagePlaceholder" placeholder="Votre message..." rows="6"></textarea>
            </div>
            <button type="submit" class="btn-submit" data-i18n="contact.submitButton">Envoyer le message</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Access Section -->
  <section class="section section-cream">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="contact.accessSubtitle">Comment venir</p>
        <h2 class="section-title" data-i18n="contact.accessTitle">Accès à l'hôtel</h2>
      </div>
      <div class="services-grid">
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="1" y="3" width="15" height="13" rx="2" ry="2"/>
              <path d="M16 8h4a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-1"/>
            </svg>
          </div>
          <h3 data-i18n="contact.byCarTitle">En voiture</h3>
          <p data-i18n="contact.byCarDesc">
            Depuis Bordeaux, prenez la rocade direction Libourne/Paris.
            Sortie Tresses/Artigues. Parking gratuit sur place.
          </p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
              <line x1="4" y1="22" x2="4" y2="15"/>
            </svg>
          </div>
          <h3 data-i18n="contact.byTrainTitle">En train</h3>
          <p data-i18n="contact.byTrainDesc">
            Gare de Bordeaux Saint-Jean à 15 km. Taxis et VTC disponibles.
            Nous pouvons organiser votre transfert sur demande.
          </p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 2L11 13"/>
              <path d="M22 2l-7 20-4-9-9-4 20-7z"/>
            </svg>
          </div>
          <h3 data-i18n="contact.byPlaneTitle">En avion</h3>
          <p data-i18n="contact.byPlaneDesc">
            Aéroport de Bordeaux-Mérignac à 25 km. Navettes et location
            de voitures disponibles à l'aéroport.
          </p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="5.5" cy="17.5" r="2.5"/>
              <circle cx="18.5" cy="17.5" r="2.5"/>
              <path d="M15 6a1 1 0 1 0 0-2 1 1 0 0 0 0 2zm-3 11.5V14l-3-3 4-3 2 3h3"/>
            </svg>
          </div>
          <h3 data-i18n="contact.byBikeTitle">À vélo</h3>
          <p data-i18n="contact.byBikeDesc">
            Pistes cyclables depuis Bordeaux. Local vélo sécurisé
            disponible pour nos clients cyclotouristes.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <h2 data-i18n="contact.ctaTitle">Des questions ?</h2>
      <p data-i18n="contact.ctaText">N'hésitez pas à nous contacter, notre équipe est à votre écoute</p>
      <a href="tel:+33557341395" class="btn btn-primary" data-i18n="contact.callUs">Appelez-nous</a>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="logo-text">
            Hôtel Corintel
            <span>Bordeaux Est</span>
          </div>
          <p data-i18n="footer.description">Un havre de paix aux portes de Bordeaux, où charme et authenticité vous attendent pour un séjour inoubliable.</p>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title" data-i18n="footer.navigation">Navigation</h4>
          <ul class="footer-links">
            <li><a href="index.php" data-i18n="nav.home">Accueil</a></li>
            <li><a href="services.php" data-i18n="nav.services">Services</a></li>
            <li><a href="activites.php" data-i18n="nav.activities">À découvrir</a></li>
            <li><a href="contact.php" data-i18n="nav.contact">Contact</a></li>
          </ul>
        </div>
        <div class="footer-nav">
          <h4 class="footer-title" data-i18n="footer.services">Services</h4>
          <ul class="footer-links">
            <li><a href="services.php#restaurant" data-i18n="footer.restaurant">Restaurant</a></li>
            <li><a href="services.php#bar" data-i18n="footer.bar">Bar</a></li>
            <li><a href="services.php#boulodrome" data-i18n="footer.boulodrome">Boulodrome</a></li>
            <li><a href="services.php#parking" data-i18n="footer.parking">Parking</a></li>
          </ul>
        </div>
        <div class="footer-contact">
          <h4 class="footer-title" data-i18n="footer.contact">Contact</h4>
          <div class="footer-contact-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
            <span data-i18n="footer.address">14 Avenue du Périgord. 33370 TRESSES<br>Gironde, France</span>
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
  <button class="scroll-top" id="scrollTop" aria-label="Retour en haut" data-i18n-aria="common.backToTop">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <polyline points="18 15 12 9 6 15"/>
    </svg>
  </button>

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

    // Form submission (placeholder - needs backend integration)
    const contactForm = document.getElementById('contactForm');
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      // In production, this would send data to a backend
      alert(window.I18n ? window.I18n.t('contact.formSuccess') : 'Merci pour votre message ! Nous vous répondrons dans les plus brefs délais.');
      contactForm.reset();
    });
  </script>
</body>
</html>
