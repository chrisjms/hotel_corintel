<?php
/**
 * Activities Page - Hôtel Corintel
 * Dynamic image loading from database
 */

// Initialize database images
$useDatabase = false;
$images = [];

try {
    require_once __DIR__ . '/includes/images-helper.php';
    $images = sectionImages('activities');
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
$heroImage = $useDatabase ? getImg($images, 1, 'images/acceuil/plan_large.jpg') : 'images/acceuil/plan_large.jpg';
$bordeauxImage = $useDatabase ? getImg($images, 2, 'images/acceuil/plan_large-2.png') : 'images/acceuil/plan_large-2.png';
$saintEmilionImage = $useDatabase ? getImg($images, 3, 'images/resto/21968112.jpg') : 'images/resto/21968112.jpg';
$tastingImage = $useDatabase ? getImg($images, 4, 'images/resto/property-amenity-2.jpg') : 'images/resto/property-amenity-2.jpg';
$cellarsImage = $useDatabase ? getImg($images, 5, 'images/resto/restaurant-hotel-tresses-3.jpg') : 'images/resto/restaurant-hotel-tresses-3.jpg';
$walksImage = $useDatabase ? getImg($images, 6, 'images/resto/barlounge.jpg') : 'images/resto/barlounge.jpg';
$gastronomyImage = $useDatabase ? getImg($images, 7, 'images/acceuil/1759071986_IMG_2108.jpeg') : 'images/acceuil/1759071986_IMG_2108.jpeg';
$countrysideImage = $useDatabase ? getImg($images, 8, 'images/acceuil/bar.jpg') : 'images/acceuil/bar.jpg';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Découvrez les activités et sites à visiter près de l'Hôtel Corintel : Bordeaux, Saint-Émilion, oenotourisme, châteaux viticoles et balades en campagne.">
  <meta name="keywords" content="tourisme bordeaux, saint-émilion, oenotourisme, vignobles bordeaux, activités gironde, visite châteaux">
  <title>À Découvrir | Hôtel Corintel - Bordeaux Est</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body data-i18n-title="activities.pageTitle">
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
        <a href="activites.php" class="nav-link active" data-i18n="nav.activities">À découvrir</a>
        <a href="contact.php" class="nav-link" data-i18n="nav.contact">Contact</a>
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
      <p class="hero-subtitle" data-i18n="activities.heroSubtitle">Explorez la région</p>
      <h1 class="page-hero-title" data-i18n="activities.heroTitle">À Découvrir</h1>
      <p class="page-hero-subtitle" data-i18n="activities.heroDescription">Bordeaux, Saint-Émilion et les vignobles</p>
    </div>
  </section>

  <!-- Introduction -->
  <section class="section section-light">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="activities.introSubtitle">Votre point de départ</p>
        <h2 class="section-title" data-i18n="activities.introTitle">Au cœur d'une région exceptionnelle</h2>
        <p class="section-description" data-i18n="activities.introDescription">
          Idéalement situé entre Bordeaux et Saint-Émilion, l'Hôtel Corintel
          est le point de départ parfait pour explorer les trésors de la Gironde.
          Vignobles prestigieux, patrimoine historique et douceur de vivre vous attendent.
        </p>
      </div>
    </div>
  </section>

  <!-- Bordeaux Section -->
  <section class="section section-cream">
    <div class="container">
      <div class="service-detail">
        <div class="service-detail-image">
          <img src="<?= htmlspecialchars($bordeauxImage) ?>" alt="Vue de Saint-Emilion">
        </div>
        <div class="service-detail-content">
          <p class="section-subtitle" data-i18n="activities.bordeauxSubtitle">Patrimoine mondial UNESCO</p>
          <h3 data-i18n="activities.bordeauxTitle">Bordeaux</h3>
          <p data-i18n="activities.bordeauxDesc1">
            À seulement quelques minutes de l'hôtel, la ville de Bordeaux vous
            ouvre ses portes. Classée au patrimoine mondial de l'UNESCO, elle
            séduit par son architecture du XVIIIe siècle, ses quais animés
            et sa vie culturelle bouillonnante.
          </p>
          <p data-i18n="activities.bordeauxDesc2">
            Flânez sur la place de la Bourse et son miroir d'eau, explorez
            le quartier Saint-Pierre, visitez la Cité du Vin ou déambulez
            dans la rue Sainte-Catherine, plus longue rue commerçante d'Europe.
          </p>
          <div class="service-features">
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="10" r="3"/>
                <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/>
              </svg>
              <span data-i18n="activities.bordeauxDistance">~15 min en voiture</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.bordeauxCite">Cité du Vin</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.bordeauxPlace">Place de la Bourse</span>
            </span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Saint-Émilion Section -->
  <section class="section section-light">
    <div class="container">
      <div class="service-detail">
        <div class="service-detail-content">
          <p class="section-subtitle" data-i18n="activities.saintEmilionSubtitle">Village médiéval</p>
          <h3 data-i18n="activities.saintEmilionTitle">Saint-Émilion</h3>
          <p data-i18n="activities.saintEmilionDesc1">
            Joyau du patrimoine français, Saint-Émilion est un village médiéval
            perché au milieu des vignes. Ses ruelles pavées, son église monolithe
            creusée dans la roche et ses remparts centenaires vous transportent
            dans un autre temps.
          </p>
          <p data-i18n="activities.saintEmilionDesc2">
            Au-delà de son charme historique, Saint-Émilion est le berceau
            de vins parmi les plus réputés au monde. Dégustations dans les
            châteaux, visites de caves et balades dans les vignobles rythmeront
            votre découverte.
          </p>
          <div class="service-features">
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="10" r="3"/>
                <path d="M12 21.7C17.3 17 20 13 20 10a8 8 0 1 0-16 0c0 3 2.7 6.9 8 11.7z"/>
              </svg>
              <span data-i18n="activities.saintEmilionDistance">~25 min en voiture</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.saintEmilionChurch">Église monolithe</span>
            </span>
            <span class="service-feature-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.saintEmilionWines">Grands crus classés</span>
            </span>
          </div>
        </div>
        <div class="service-detail-image">
          <img src="<?= htmlspecialchars($saintEmilionImage) ?>" alt="Vignobles de la région bordelaise">
        </div>
      </div>
    </div>
  </section>

  <!-- Wine Tourism Section -->
  <section class="section section-cream">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="activities.wineSubtitle">Oenotourisme</p>
        <h2 class="section-title" data-i18n="activities.wineTitle">La route des vins</h2>
        <p class="section-description" data-i18n="activities.wineDescription">
          La Gironde compte parmi les plus prestigieuses appellations viticoles
          du monde. Partez à la découverte des châteaux et de leurs secrets.
        </p>
      </div>

      <div class="activities-grid">
        <div class="activity-card">
          <img src="<?= htmlspecialchars($tastingImage) ?>" alt="Dégustation de vins">
          <div class="activity-card-content">
            <h3 data-i18n="activities.tastingTitle">Dégustations</h3>
            <p data-i18n="activities.tastingDesc">
              Les châteaux de la région vous accueillent pour des dégustations
              de leurs meilleurs crus. Découvrez les secrets de la vinification
              et repartez avec vos bouteilles préférées.
            </p>
          </div>
        </div>
        <div class="activity-card">
          <img src="<?= htmlspecialchars($cellarsImage) ?>" alt="Visite de cave">
          <div class="activity-card-content">
            <h3 data-i18n="activities.cellarsTitle">Visites de caves</h3>
            <p data-i18n="activities.cellarsDesc">
              Pénétrez dans les chais séculaires où vieillissent les grands vins
              de Bordeaux. Une expérience sensorielle unique entre tradition
              et savoir-faire.
            </p>
          </div>
        </div>
        <div class="activity-card">
          <img src="<?= htmlspecialchars($walksImage) ?>" alt="Balades dans les vignes">
          <div class="activity-card-content">
            <h3 data-i18n="activities.walksTitle">Balades dans les vignes</h3>
            <p data-i18n="activities.walksDesc">
              À pied, à vélo ou en voiture, parcourez les routes sinueuses
              entre les rangs de vigne. Le paysage viticole de la Gironde
              est inscrit au patrimoine mondial.
            </p>
          </div>
        </div>
        <div class="activity-card">
          <img src="<?= htmlspecialchars($gastronomyImage) ?>" alt="Gastronomie locale">
          <div class="activity-card-content">
            <h3 data-i18n="activities.gastronomyTitle">Gastronomie locale</h3>
            <p data-i18n="activities.gastronomyDesc">
              Accompagnez vos découvertes viticoles de la riche cuisine
              du Sud-Ouest : canard, cèpes, huîtres du bassin d'Arcachon
              et desserts traditionnels.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Countryside Section -->
  <section class="section section-light">
    <div class="container">
      <div class="intro-grid">
        <div class="intro-image">
          <img src="<?= htmlspecialchars($countrysideImage) ?>" alt="Ambiance campagne bordelaise">
        </div>
        <div class="intro-content">
          <p class="section-subtitle" data-i18n="activities.countrysideSubtitle">Nature & détente</p>
          <h2 data-i18n="activities.countrysideTitle">Échappées en campagne</h2>
          <p data-i18n="activities.countrysideDesc1">
            Au-delà des vignobles, la campagne girondine offre mille occasions
            de se ressourcer. Forêts de pins, rivières paisibles et villages
            de caractère ponctuent un paysage préservé.
          </p>
          <p data-i18n="activities.countrysideDesc2">
            Partez en randonnée sur les sentiers balisés, louez un vélo pour
            explorer les petites routes, ou simplement profitez du calme
            environnant depuis notre jardin.
          </p>
          <div class="intro-features">
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.countrysideHiking">Sentiers de randonnée</span>
            </div>
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.countrysideCycling">Pistes cyclables</span>
            </div>
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.countrysideVillages">Villages pittoresques</span>
            </div>
            <div class="intro-feature">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              <span data-i18n="activities.countrysideMarkets">Marchés locaux</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Other Attractions -->
  <section class="section section-cream">
    <div class="container">
      <div class="section-header">
        <p class="section-subtitle" data-i18n="activities.otherSubtitle">Et aussi</p>
        <h2 class="section-title" data-i18n="activities.otherTitle">Autres sites à découvrir</h2>
      </div>
      <div class="services-grid">
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
            </svg>
          </div>
          <h3 data-i18n="activities.arcachonTitle">Bassin d'Arcachon</h3>
          <p data-i18n="activities.arcachonDesc">La Dune du Pilat, les villages ostréicoles et les plages océanes à environ 1h de route.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
            </svg>
          </div>
          <h3 data-i18n="activities.medocTitle">Châteaux du Médoc</h3>
          <p data-i18n="activities.medocDesc">Margaux, Pauillac, Saint-Julien : les plus grands noms du vin vous ouvrent leurs portes.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <path d="M12 6v6l4 2"/>
            </svg>
          </div>
          <h3 data-i18n="activities.libourneTitle">Libourne</h3>
          <p data-i18n="activities.libourneDesc">Bastide médiévale au confluent de la Dordogne et de l'Isle, à proximité immédiate.</p>
        </div>
        <div class="service-card">
          <div class="service-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="9" cy="7" r="4"/>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
          </div>
          <h3 data-i18n="activities.marketsTitle">Marchés locaux</h3>
          <p data-i18n="activities.marketsDesc">Produits du terroir, fromages, charcuteries et spécialités régionales chaque semaine.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA Section -->
  <section class="cta-section">
    <div class="container">
      <h2 data-i18n="activities.ctaTitle">Prêt pour l'aventure ?</h2>
      <p data-i18n="activities.ctaText">Contactez-nous pour découvrir la région bordelaise</p>
      <a href="contact.php" class="btn btn-primary" data-i18n="nav.contact">Nous contacter</a>
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
