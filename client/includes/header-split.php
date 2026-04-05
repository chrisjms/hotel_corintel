  <!-- Header: Split -->
  <header class="header header--split" id="header">
    <div class="header-accent-line"></div>
    <div class="container">
      <a href="index.php" class="logo">
        <div class="logo-text">
          <?= h($_headerHotelName) ?>
          <span><?= h($_headerLogoText) ?></span>
        </div>
      </a>
      <div class="header-divider"></div>
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
