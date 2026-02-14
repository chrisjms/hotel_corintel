<?php
/**
 * Contact Page - Hôtel Corintel
 * Dynamic image loading from database
 */

require_once __DIR__ . '/includes/functions.php';

// Handle message submission
$messageSuccess = false;
$messageError = '';
$messageCategories = getGuestMessageCategories();
$isAjaxRequest = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_message') {
    $msgRoomNumber = trim($_POST['msg_room_number'] ?? '');
    $msgGuestName = trim($_POST['msg_guest_name'] ?? '');
    $msgCategory = $_POST['msg_category'] ?? 'general';
    $msgSubject = trim($_POST['msg_subject'] ?? '');
    $msgMessage = trim($_POST['msg_message'] ?? '');

    // Validation
    if (empty($msgRoomNumber)) {
        $messageError = 'Veuillez indiquer votre numéro de chambre.';
    } elseif (empty($msgMessage)) {
        $messageError = 'Veuillez écrire votre message.';
    } elseif (strlen($msgMessage) > 2000) {
        $messageError = 'Le message est trop long (max. 2000 caractères).';
    } elseif (!array_key_exists($msgCategory, $messageCategories)) {
        $messageError = 'Catégorie invalide.';
    } else {
        $msgId = createGuestMessage([
            'room_number' => $msgRoomNumber,
            'guest_name' => $msgGuestName,
            'category' => $msgCategory,
            'subject' => $msgSubject,
            'message' => $msgMessage
        ]);

        if ($msgId) {
            $messageSuccess = true;
        } else {
            $messageError = 'Une erreur est survenue. Veuillez réessayer.';
        }
    }

    // Return JSON response for AJAX requests
    if ($isAjaxRequest || isset($_POST['redirect_back'])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $messageSuccess,
            'error' => $messageError,
            'message_sent' => $messageSuccess ? 1 : 0
        ]);
        exit;
    }
}

// Load content helper for dynamic content
require_once __DIR__ . '/includes/content-helper.php';

// Get dynamic sections for the contact page
$dynamicSections = getDynamicSectionsWithData('contact');
$dynamicSectionsTranslations = !empty($dynamicSections) ? getDynamicSectionsTranslations('contact') : [];

// Get hero image from content system with fallback
$heroImage = contentImage('contact_hero', 1, 'images/acceuil/dehors_nuit.jpg');

$hotelName = getHotelName();
$logoText = getLogoText();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Contactez <?= h($hotelName) ?> près de Bordeaux. Adresse, téléphone, email et formulaire de contact. Situé à Tresses, <?= h($logoText) ?>, Gironde.">
  <meta name="keywords" content="contact hôtel bordeaux, adresse hôtel tresses, réservation hôtel gironde, hôtel bordeaux est">
  <title>Contact | <?= h($hotelName) ?> - <?= h($logoText) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <?= getThemeCSS() ?>
  <?= getHotelNameJS() ?>
  <style>
    /* Guest Message Section */
    .guest-message-section {
      padding: 4rem 0;
      background: var(--color-white);
    }
    .message-form-container {
      max-width: 600px;
      margin: 0 auto;
      background: var(--color-cream);
      padding: 2rem;
      border-radius: 12px;
      box-shadow: var(--shadow-soft);
    }
    .message-form .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }
    @media (max-width: 600px) {
      .message-form .form-row {
        grid-template-columns: 1fr;
      }
    }
    .message-form .form-group {
      margin-bottom: 1rem;
    }
    .message-form label {
      display: block;
      font-weight: 500;
      margin-bottom: 0.375rem;
      font-size: 0.9rem;
    }
    .message-form input,
    .message-form select,
    .message-form textarea {
      width: 100%;
      padding: 0.625rem 0.875rem;
      border: 1px solid var(--color-beige);
      border-radius: 6px;
      font-size: 0.9rem;
      font-family: var(--font-body);
      transition: var(--transition);
      background: var(--color-white);
    }
    .message-form input:focus,
    .message-form select:focus,
    .message-form textarea:focus {
      outline: none;
      border-color: var(--color-primary);
      box-shadow: 0 0 0 3px rgba(139, 111, 71, 0.1);
    }
    .message-form textarea {
      min-height: 120px;
      resize: vertical;
    }
    .btn-message {
      width: 100%;
      padding: 1rem;
      background: var(--color-primary);
      color: var(--color-white);
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
    }
    .btn-message:hover {
      background: var(--color-primary-dark);
    }
    .btn-message svg {
      width: 20px;
      height: 20px;
    }
    .message-success {
      text-align: center;
      padding: 2rem;
    }
    .message-success-icon {
      width: 60px;
      height: 60px;
      background: rgba(72, 187, 120, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
    }
    .message-success-icon svg {
      width: 30px;
      height: 30px;
      color: #48BB78;
    }
    .message-success h3 {
      margin-bottom: 0.5rem;
    }
    .message-success p {
      color: var(--color-text-light);
      margin-bottom: 1rem;
    }
    .alert-message-error {
      background: rgba(245, 101, 101, 0.1);
      color: #C53030;
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      border: 1px solid rgba(245, 101, 101, 0.3);
    }
  </style>
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
          <?= h($hotelName) ?>
          <span><?= h($logoText) ?></span>
        </div>
      </a>
      <nav class="nav-menu" id="navMenu">
        <a href="index.php" class="nav-link" data-i18n="nav.home">Accueil</a>
        <a href="services.php" class="nav-link" data-i18n="nav.services">Services</a>
        <a href="room-service.php" class="nav-link" data-i18n="nav.roomService">Room Service</a>
        <a href="activites.php" class="nav-link" data-i18n="nav.activities">À découvrir</a>
        <a href="contact.php" class="nav-link active" data-i18n="nav.contact">Contact</a>
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
                  <?= h($hotelName) ?><br>
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
              title="Localisation de <?= h($hotelName) ?>">
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

  <?php
  // Render dynamic sections (if any exist)
  if (!empty($dynamicSections)):
      echo renderDynamicSectionsForPage('contact', 'fr');
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
            <li><a href="services.php" data-i18n="footer.restaurant">Restaurant</a></li>
            <li><a href="services.php" data-i18n="footer.bar">Bar</a></li>
            <li><a href="services.php" data-i18n="footer.boulodrome">Boulodrome</a></li>
            <li><a href="services.php" data-i18n="footer.parking">Parking</a></li>
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
        <p data-i18n="footer.copyright">&copy; <?= date('Y') ?> <?= h($hotelName) ?>. Tous droits réservés.</p>
      </div>
    </div>
  </footer>

  <!-- Scroll to Top Button -->
  <button class="scroll-top" id="scrollTop" aria-label="Retour en haut" data-i18n-aria="common.backToTop">
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

        const data = await response.json();

        if (data.success) {
          // Show success state
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
  </script>
</body>
</html>
