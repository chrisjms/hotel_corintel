<?php
$_headerSolidClass = ($_headerCurrentSlug !== '') ? ' header--solid' : '';
?>
  <!-- Header: Prestige -->
  <header class="header header--prestige<?= $_headerSolidClass ?>" id="header">
    <div class="header-prestige-topbar">
      <div class="container">
        <div class="prestige-topbar-inner">
          <span class="prestige-tagline"><?= h($_headerLogoText) ?></span>
          <a href="index.php" class="logo logo--prestige">
            <div class="logo-monogram"><?= h(mb_substr($_headerHotelName, 0, 1)) ?></div>
            <div class="logo-text"><?= h($_headerHotelName) ?></div>
          </a>
          <?php if (featureEnabled('messaging')): ?>
          <button type="button" class="btn-contact-reception prestige-contact" id="btnContactReception" data-i18n="header.contactReception">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            Contacter la réception
          </button>
          <?php else: ?>
          <span class="prestige-tagline">&nbsp;</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="header-prestige-navbar">
      <div class="container">
        <nav class="nav-menu nav-menu--prestige" id="navMenu">
          <?php include __DIR__ . '/header-nav-prestige.php'; ?>
        </nav>
        <div class="menu-toggle" id="menuToggle">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
  </header>
