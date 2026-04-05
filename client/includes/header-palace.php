<?php
$_headerSolidClass = ($_headerCurrentSlug !== '') ? ' header--solid' : '';
?>
  <!-- Header: Palace -->
  <header class="header header--palace<?= $_headerSolidClass ?>" id="header">
    <div class="header-palace-accent"></div>
    <div class="container">
      <div class="header-palace-inner">
        <div class="palace-logo-row">
          <div class="palace-rule"></div>
          <a href="index.php" class="logo logo--palace">
            <div class="logo-monogram"><?= h(mb_substr($_headerHotelName, 0, 1)) ?></div>
            <div class="logo-text">
              <?= h($_headerHotelName) ?>
              <span><?= h($_headerLogoText) ?></span>
            </div>
          </a>
          <div class="palace-rule"></div>
        </div>
        <div class="palace-nav-row">
          <nav class="nav-menu nav-menu--palace" id="navMenu">
            <?php include __DIR__ . '/header-nav.php'; ?>
          </nav>
          <div class="menu-toggle" id="menuToggle">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </div>
      </div>
    </div>
    <div class="header-palace-border"></div>
  </header>
