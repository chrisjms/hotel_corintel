<?php
// On non-home pages, the minimal header starts solid (no full-bleed hero behind it)
$_headerSolidClass = ($_headerCurrentSlug !== '') ? ' header--solid' : '';
?>
  <!-- Header: Minimal -->
  <header class="header header--minimal<?= $_headerSolidClass ?>" id="header">
    <div class="container">
      <a href="index.php" class="logo">
        <div class="logo-text">
          <?= h($_headerHotelName) ?>
        </div>
      </a>
      <nav class="nav-menu" id="navMenu">
        <?php include __DIR__ . '/header-nav.php'; ?>
      </nav>
      <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </header>
