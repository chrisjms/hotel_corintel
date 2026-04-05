<?php
$_headerSolidClass = ($_headerCurrentSlug !== '') ? ' header--solid' : '';
?>
  <!-- Header: Côtier -->
  <header class="header header--cotier<?= $_headerSolidClass ?>" id="header">
    <div class="container">
      <div class="header-cotier-inner">
        <a href="index.php" class="logo logo--cotier">
          <div class="logo-text"><?= h($_headerHotelName) ?></div>
        </a>
        <div class="cotier-nav-glass">
          <nav class="nav-menu nav-menu--cotier" id="navMenu">
            <?php include __DIR__ . '/header-nav.php'; ?>
          </nav>
        </div>
        <div class="cotier-right">
          <span class="cotier-tagline"><?= h($_headerLogoText) ?></span>
        </div>
        <div class="menu-toggle" id="menuToggle">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
    <div class="header-cotier-line"></div>
  </header>
