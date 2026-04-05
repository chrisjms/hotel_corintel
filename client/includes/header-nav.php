<?php
/**
 * Shared Navigation Fragment
 * Used by all header templates (classic, centered, minimal)
 *
 * Expects these variables from the dispatcher (header.php):
 *   $_headerNavPages, $_headerCurrentSlug, $_headerRoomSession
 */
foreach ($_headerNavPages as $navPage):
    $navUrl = $navPage['slug'] === '' ? 'index.php' : '/' . $navPage['slug'];
    $isActive = ($navPage['slug'] === $_headerCurrentSlug) ||
                ($navPage['page_type'] === 'home' && $_headerCurrentSlug === '');
    $navI18nKey = $navPage['i18n_nav_key'] ?: '';

    // Insert Room Service link before Contact (if feature enabled)
    if (($navPage['slug'] === 'contact' || $navPage['page_type'] === 'contact') && featureEnabled('room_service')):
?>
        <a href="room-service.php" class="nav-link nav-link-room-service" data-i18n="nav.roomService">
          <?= h(establishmentLabel('nav_link')) ?>
          <?php if ($_headerRoomSession): ?>
          <span class="nav-room-badge">Ch. <?= h($_headerRoomSession['room_number']) ?></span>
          <?php else: ?>
          <span class="nav-qr-badge" data-i18n="footer.qrOnly">QR</span>
          <?php endif; ?>
        </a>
<?php endif; ?>
        <a href="<?= h($navUrl) ?>" class="nav-link<?= $isActive ? ' active' : '' ?>"<?= $navI18nKey ? ' data-i18n="' . h($navI18nKey) . '"' : '' ?>><?= h($navPage['nav_title'] ?: $navPage['title']) ?></a>
<?php endforeach; ?>
<?php if (featureEnabled('messaging')): ?>
        <button type="button" class="btn-contact-reception" id="btnContactReception" data-i18n="header.contactReception">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          Contacter la réception
        </button>
<?php endif; ?>
