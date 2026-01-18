<?php
/**
 * Services Page - Hôtel Corintel
 * Dynamic image loading from database
 */

// Initialize database images
$useDatabase = false;
$images = [];

try {
    require_once __DIR__ . '/includes/images-helper.php';
    $images = sectionImages('services');
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
$heroImage = $useDatabase ? getImg($images, 1, 'images/resto/restaurant-hotel-bordeaux-1.jpg') : 'images/resto/restaurant-hotel-bordeaux-1.jpg';
$restaurantImage = $useDatabase ? getImg($images, 2, 'images/resto/petit_dej.jpg') : 'images/resto/petit_dej.jpg';
$galleryImage1 = $useDatabase ? getImg($images, 3, 'images/resto/restaurant-hotel-tresses-1.jpg') : 'images/resto/restaurant-hotel-tresses-1.jpg';
$galleryImage2 = $useDatabase ? getImg($images, 4, 'images/resto/restaurant-hotel-tresses-2.jpg') : 'images/resto/restaurant-hotel-tresses-2.jpg';
$galleryImage3 = $useDatabase ? getImg($images, 5, 'images/resto/property-amenity.jpg') : 'images/resto/property-amenity.jpg';
$barImage = $useDatabase ? getImg($images, 6, 'images/acceuil/bar.jpg') : 'images/acceuil/bar.jpg';
$boulodromeImage = $useDatabase ? getImg($images, 7, 'images/acceuil/boulodrome.jpg') : 'images/acceuil/boulodrome.jpg';
$parkingImage = $useDatabase ? getImg($images, 8, 'images/parking/hotel-bordeaux-parking.jpg') : 'images/parking/hotel-bordeaux-parking.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Découvrez les services de l'Hôtel Corintel : restaurant table d'hôtes avec cuisine régionale, bar, boulodrome et parking gratuit. Près de Bordeaux et Saint-Émilion.">
  <meta name="keywords" content="services hôtel, restaurant bordeaux, table d'hôtes, bar hôtel, pétanque, parking gratuit">
  <title>Nos Services | Hôtel Corintel - Bordeaux Est</title>
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
        <a href="index.php" class="nav-link" data-i18n="nav.home">Accueil</a>
        <a href="services.php" class="nav-link active" data-i18n="nav.services">Services</a>
        <a href="chambres.php" class="nav-link" data-i18n="nav.rooms">Chambres</a>
        <a href="activites.php" class="nav-link" data-i18n="nav.discover">À découvrir</a>
        <a href="contact.php" class="nav-link" data-i18n="nav.contact">Contact</a>
        <a href="https://www.booking.com/hotel/fr/corintel.fr.html?aid=311089&label=corintel-O0VnbWaGZNr8nXbaU172TQS625028973267%3Apl%3Ata%3Ap1%3Ap2%3Aac%3Aap%3Aneg%3Afi%3Atikwd-924823501370%3Alp9055050%3Ali%3Adec%3Adm%3Appccp%3DUmFuZG9tSVYkc2RlIyh9YVujEjbMrKBV7ahOy8HtCLg&sid=2bd2846f5430642ffc2dfefa4e617e28&dest_id=-1473710&dest_type=city&dist=0&group_adults=2&group_children=0&hapos=1&hpos=1&no_rooms=1&req_adults=2&req_children=0&room1=A%2CA&sb_price_type=total&sr_order=popularity&srepoch=1766942847&srpvid=7a705f51b38eb96dd0ea283227969889&type=total&ucfs=1&" class="btn-book" data-i18n="nav.book">Réserver</a>
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
      <p class="hero-subtitle" data-i18n="services.heroSubtitle">L'Hôtel Corintel</p>
      <h1 class="page-hero-title" data-i18n="services.heroTitle">Nos Services</h1>
      <p class="page-hero-subtitle" data-i18n="services.heroDescription">Tout pour un séjour inoubliable</p>
    </div>
  </section>

  <!-- Services Introduction -->
  <section class="section section-light">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="services.introSubtitle">À votre service</p>
        <h2 class="section-title" data-i18n="services.introTitle">Une expérience complète</h2>
        <p class="section-description" data-i18n="services.introDescription">
          L'Hôtel Corintel met à votre disposition une gamme de services pensés
          pour votre confort et votre détente. Découvrez tout ce qui rendra
          votre séjour mémorable.
        </p>
      </div>
    </div>
  </section>

  <!-- Restaurant Section -->
  <section class="section section-cream" id="restaurant">
    <div class="container">
      <div class="service-detail">
        <div class="service-detail-image">
          <img src="<?= htmlspecialchars($restaurantImage) ?>" alt="Petit-déjeuner au restaurant de l'Hôtel Corintel">
        </div>
        <div class="service-detail-content">
          <p class="section-subtitle" data-i18n="services.restaurantSubtitle">Restauration</p>
          <h3 data-i18n="services.restaurantTitle">Table d'hôtes</h3>
          <p data-i18n="services.restaurantText1">
            Notre restaurant vous invite à découvrir une cuisine régionale authentique,
            préparée avec passion à partir de produits locaux soigneusement sélectionnés.
            Dans une ambiance conviviale de table d'hôtes, partagez des repas
            savoureux qui célèbrent les saveurs du terroir bordelais.
          </p>
          <p data-i18n="services.restaurantText2">
            Le petit-déjeuner et le dîner vous sont proposés dans notre salle
            chaleureuse ou en terrasse aux beaux jours, avec vue sur le jardin.
          </p>
          <div class="service-features">
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagLocalProducts">Produits locaux</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagRegionalCuisine">Cuisine régionale</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagBreakfast">Petit-déjeuner</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagDinner">Dîner</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Restaurant Gallery -->
  <section class="section section-light">
    <div class="container">
      <div class="rooms-gallery">
        <div class="room-card">
          <img src="<?= htmlspecialchars($galleryImage1) ?>" alt="Salle du restaurant">
          <div class="room-card-overlay">
            <h4 data-i18n="services.galleryRoom">Salle du restaurant</h4>
            <p data-i18n="services.galleryRoomDesc">Ambiance chaleureuse</p>
          </div>
        </div>
        <div class="room-card">
          <img src="<?= htmlspecialchars($galleryImage2) ?>" alt="Décoration du restaurant">
          <div class="room-card-overlay">
            <h4 data-i18n="services.galleryDecor">Décoration soignée</h4>
            <p data-i18n="services.galleryDecorDesc">Charme authentique</p>
          </div>
        </div>
        <div class="room-card">
          <img src="<?= htmlspecialchars($galleryImage3) ?>" alt="Service du restaurant">
          <div class="room-card-overlay">
            <h4 data-i18n="services.galleryService">Service attentionné</h4>
            <p data-i18n="services.galleryServiceDesc">À votre écoute</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Bar Section -->
  <section class="section section-cream" id="bar">
    <div class="container">
      <div class="service-detail">
        <div class="service-detail-content">
          <p class="section-subtitle" data-i18n="services.barSubtitle">Détente</p>
          <h3 data-i18n="services.barTitle">Le Bar</h3>
          <p data-i18n="services.barText1">
            Prolongez vos soirées dans notre bar chaleureux, véritable lieu de
            convivialité où se croisent les voyageurs du monde entier. Installez-vous
            confortablement et savourez un moment de détente.
          </p>
          <p data-i18n="services.barText2">
            Notre carte met à l'honneur les vins de Bordeaux et de Saint-Émilion,
            accompagnés d'une sélection de spiritueux et de cocktails préparés
            avec soin par notre équipe.
          </p>
          <div class="service-features">
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagBordeauxWines">Vins de Bordeaux</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagCocktails">Cocktails</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagConvivial">Ambiance conviviale</span>
            </span>
          </div>
        </div>
        <div class="service-detail-image">
          <img src="<?= htmlspecialchars($barImage) ?>" alt="Bar de l'Hôtel Corintel">
        </div>
      </div>
    </div>
  </section>

  <!-- Boulodrome Section -->
  <section class="section section-light" id="boulodrome">
    <div class="container">
      <div class="service-detail">
        <div class="service-detail-image">
          <img src="<?= htmlspecialchars($boulodromeImage) ?>" alt="Espace extérieur de l'Hôtel Corintel">
        </div>
        <div class="service-detail-content">
          <p class="section-subtitle" data-i18n="services.boulodromeSubtitle">Loisirs</p>
          <h3 data-i18n="services.boulodromeTitle">Boulodrome</h3>
          <p data-i18n="services.boulodromeText1">
            À l'Hôtel Corintel, nous cultivons l'art de vivre à la française.
            Notre terrain de pétanque vous attend pour des parties mémorables,
            que vous soyez joueur aguerri ou simple amateur de moments conviviaux.
          </p>
          <p data-i18n="services.boulodromeText2">
            Sous le soleil de Gironde, lancez vos boules et profitez de l'esprit
            détendu de la campagne bordelaise. Un apéritif à la main, en famille
            ou entre amis, c'est le bonheur simple des vacances.
          </p>
          <div class="service-features">
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagPetanque">Terrain de pétanque</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagBowlsAvailable">Boules disponibles</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagFreeAccess">Accès libre</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Parking Section -->
  <section class="section section-cream" id="parking">
    <div class="container">
      <div class="service-detail">
        <div class="service-detail-content">
          <p class="section-subtitle" data-i18n="services.parkingSubtitle">Pratique</p>
          <h3 data-i18n="services.parkingTitle">Parking privé gratuit</h3>
          <p data-i18n="services.parkingText1">
            Votre tranquillité commence dès votre arrivée. L'Hôtel Corintel dispose
            d'un parking privé et sécurisé, entièrement gratuit pour tous nos clients.
          </p>
          <p data-i18n="services.parkingText2">
            Idéalement situé à l'est de Bordeaux, notre établissement vous permet
            de rayonner facilement vers les vignobles, Bordeaux ou Saint-Émilion,
            tout en profitant du calme de la campagne pour votre repos.
          </p>
          <div class="service-features">
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagFree">Gratuit</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tagSecure">Privé et sécurisé</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="services.tag24h">Accès 24h/24</span>
            </span>
          </div>
        </div>
        <div class="service-detail-image">
          <img src="<?= htmlspecialchars($parkingImage) ?>" alt="Parking de l'Hôtel Corintel">
        </div>
      </div>
    </div>
  </section>

  <!-- Additional Services -->
  <section class="section section-light">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="services.additionalSubtitle">Et aussi</p>
        <h2 class="section-title" data-i18n="services.additionalTitle">Services complémentaires</h2>
      </div>
      <div class="services-grid">
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <h3 data-i18n="services.garden">Jardin</h3>
          <p data-i18n="services.gardenDesc">Promenez-vous dans notre jardin verdoyant et profitez du calme de la nature environnante.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="5"/>
              <line x1="12" y1="1" x2="12" y2="3"/>
              <line x1="12" y1="21" x2="12" y2="23"/>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
              <line x1="1" y1="12" x2="3" y2="12"/>
              <line x1="21" y1="12" x2="23" y2="12"/>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
          </div>
          <h3 data-i18n="services.terrace">Terrasse</h3>
          <p data-i18n="services.terraceDesc">Détendez-vous sur notre terrasse ombragée, idéale pour les petits-déjeuners ensoleillés.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
          </div>
          <h3 data-i18n="services.lounge">Salon commun</h3>
          <p data-i18n="services.loungeDesc">Espace convivial pour lire, se détendre ou partager un moment avec d'autres voyageurs.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M5 12.55a11 11 0 0 1 14.08 0"/>
              <path d="M1.42 9a16 16 0 0 1 21.16 0"/>
              <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
              <line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
          </div>
          <h3 data-i18n="services.wifi">Wi-Fi gratuit</h3>
          <p data-i18n="services.wifiDesc">Connexion internet haut débit disponible gratuitement dans tout l'établissement.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <h2 data-i18n="services.ctaTitle">Prêt à vivre l'expérience Corintel ?</h2>
      <p data-i18n="services.ctaText">Réservez dès maintenant et profitez de tous nos services</p>
      <a href="https://www.booking.com/hotel/fr/corintel.fr.html?aid=311089&label=corintel-O0VnbWaGZNr8nXbaU172TQS625028973267%3Apl%3Ata%3Ap1%3Ap2%3Aac%3Aap%3Aneg%3Afi%3Atikwd-924823501370%3Alp9055050%3Ali%3Adec%3Adm%3Appccp%3DUmFuZG9tSVYkc2RlIyh9YVujEjbMrKBV7ahOy8HtCLg&sid=2bd2846f5430642ffc2dfefa4e617e28&dest_id=-1473710&dest_type=city&dist=0&group_adults=2&group_children=0&hapos=1&hpos=1&no_rooms=1&req_adults=2&req_children=0&room1=A%2CA&sb_price_type=total&sr_order=popularity&srepoch=1766942847&srpvid=7a705f51b38eb96dd0ea283227969889&type=total&ucfs=1&" class="btn btn-primary" data-i18n="common.bookNow">Réserver maintenant</a>
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
            <li><a href="chambres.php" data-i18n="nav.rooms">Chambres</a></li>
            <li><a href="activites.php" data-i18n="nav.discover">À découvrir</a></li>
            <li><a href="contact.php" data-i18n="nav.contact">Contact</a></li>
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

  <!-- Scripts -->
  <script src="js/translations.js"></script>
  <script src="js/i18n.js"></script>
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
  </script>
</body>
</html>
