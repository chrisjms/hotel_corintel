  <!-- Header: Centered -->
  <header class="header header--centered" id="header">
    <div class="container">
      <div class="header-centered-top">
        <a href="index.php" class="logo logo--centered">
          <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>
          </svg>
          <div class="logo-text">
            <?= h($_headerHotelName) ?>
            <span><?= h($_headerLogoText) ?></span>
          </div>
        </a>
      </div>
      <nav class="nav-menu nav-menu--centered" id="navMenu">
        <?php include __DIR__ . '/header-nav.php'; ?>
      </nav>
      <div class="menu-toggle" id="menuToggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
  </header>
