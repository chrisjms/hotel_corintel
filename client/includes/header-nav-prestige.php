<?php
/**
 * Navigation Fragment for Prestige header
 * Same as header-nav.php but WITHOUT the contact button
 * (contact button is placed in the Prestige top bar instead)
 */
foreach ($_headerNavPages as $navPage):
    $navUrl = $navPage['slug'] === '' ? 'index.php' : '/' . $navPage['slug'];
    $isActive = ($navPage['slug'] === $_headerCurrentSlug) ||
                ($navPage['page_type'] === 'home' && $_headerCurrentSlug === '');
    $navI18nKey = $navPage['i18n_nav_key'] ?: '';

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
