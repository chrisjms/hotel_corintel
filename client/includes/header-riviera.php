<?php
$_headerSolidClass = ($_headerCurrentSlug !== '') ? ' header--solid' : '';
?>
  <!-- Header: Riviera -->
  <header class="header header--riviera<?= $_headerSolidClass ?>" id="header">
    <div class="container">
      <div class="header-riviera-inner">
        <div class="riviera-left">
          <a href="index.php" class="logo logo--riviera">
            <div class="logo-text">
              <?= h($_headerHotelName) ?>
            </div>
          </a>
        </div>
        <nav class="nav-menu nav-menu--riviera" id="navMenu">
          <?php include __DIR__ . '/header-nav.php'; ?>
        </nav>
        <div class="riviera-right">
          <div class="riviera-accent-dot"></div>
          <span class="riviera-tagline"><?= h($_headerLogoText) ?></span>
        </div>
        <div class="menu-toggle" id="menuToggle">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
    <div class="header-riviera-border"></div>
  </header>
