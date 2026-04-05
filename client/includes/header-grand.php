<?php
$_headerSolidClass = ($_headerCurrentSlug !== '') ? ' header--solid' : '';
?>
  <!-- Header: Grand -->
  <header class="header header--grand<?= $_headerSolidClass ?>" id="header">
    <div class="container">
      <div class="header-grand-inner">
        <a href="index.php" class="logo logo--grand">
          <div class="logo-monogram"><?= h(mb_substr($_headerHotelName, 0, 1)) ?></div>
          <div class="logo-text">
            <?= h($_headerHotelName) ?>
            <span><?= h($_headerLogoText) ?></span>
          </div>
        </a>
        <nav class="nav-menu nav-menu--grand" id="navMenu">
          <?php include __DIR__ . '/header-nav.php'; ?>
        </nav>
        <div class="menu-toggle" id="menuToggle">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
    </div>
  </header>
