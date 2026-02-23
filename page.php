<?php
/**
 * Dynamic Page Template
 * Hotel Corintel - Renders any page based on slug
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/content-helper.php';

// Get slug from URL
$slug = $_GET['slug'] ?? '';

// Handle empty slug (should go to index.php)
if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Initialize pages table if needed
initPagesTable();

// Look up page by slug
$page = getPageBySlug($slug);

// 404 if page not found or inactive
if (!$page) {
    http_response_code(404);
    $hotelName = getHotelName();
    $logoText = getLogoText();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Page non trouvée | <?= h($hotelName) ?></title>
        <link rel="stylesheet" href="style.css">
        <?= getThemeCSS() ?>
    </head>
    <body>
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 2rem;">
            <h1 style="font-family: var(--font-heading); font-size: 3rem; color: var(--color-primary);">404</h1>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">Page non trouvée</p>
            <a href="index.php" style="color: var(--color-primary);">Retour à l'accueil</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Get message categories for the contact reception modal
$messageCategories = getGuestMessageCategories();

// Get page data
$pageCode = $page['code'];
$pageTitle = $page['title'];
$navTitle = $page['nav_title'] ?: $page['title'];
$metaTitle = $page['meta_title'] ?: $page['title'];
$metaDescription = $page['meta_description'] ?: '';
$heroSectionCode = $page['hero_section_code'];

// Get dynamic sections for this page
$dynamicSections = getDynamicSectionsWithData($pageCode);
$dynamicSectionsTranslations = !empty($dynamicSections) ? getDynamicSectionsTranslations($pageCode) : [];

// Get hero image from content system
$defaultHeroImage = 'images/default-hero.jpg';
$heroImage = $heroSectionCode ? contentImage($heroSectionCode, 1, $defaultHeroImage) : $defaultHeroImage;

// Get general site info
$hotelName = getHotelName();
$logoText = getLogoText();
$contactInfo = getContactInfo();

// Check for active room session (set by scanning QR code via scan.php)
$roomSession = getRoomServiceSession();

// Get navigation pages for the menu
$navPages = getNavigationPages();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($metaDescription): ?>
    <meta name="description" content="<?= h($metaDescription) ?>">
    <?php endif; ?>
    <title><?= h($metaTitle) ?> | <?= h($hotelName) ?> - <?= h($logoText) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Lato:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <?= getThemeCSS() ?>
    <?= getHotelNameJS() ?>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <a href="index.php" class="logo">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01M9 13h.01M15 13h.01"/>
                </svg>
                <div class="logo-text">
                    <?= h($hotelName) ?>
                    <span><?= h($logoText) ?></span>
                </div>
            </a>
            <nav class="nav-menu" id="navMenu">
                <?php foreach ($navPages as $navPage): ?>
                <?php
                    $navUrl = $navPage['slug'] === '' ? 'index.php' : '/' . $navPage['slug'];
                    $isActive = $navPage['code'] === $pageCode;
                    $navI18nKey = $navPage['i18n_nav_key'] ?: '';
                    // Insert Room Service link before Contact
                    if ($navPage['slug'] === 'contact' || $navPage['page_type'] === 'contact'):
                ?>
                <a href="room-service.php" class="nav-link nav-link-room-service" data-i18n="nav.roomService">Room Service <?php if ($roomSession): ?><span class="nav-room-badge">Ch. <?= h($roomSession['room_number']) ?></span><?php else: ?><span class="nav-qr-badge" data-i18n="footer.qrOnly">QR</span><?php endif; ?></a>
                <?php endif; ?>
                <a href="<?= h($navUrl) ?>" class="nav-link<?= $isActive ? ' active' : '' ?>"<?= $navI18nKey ? ' data-i18n="' . h($navI18nKey) . '"' : '' ?>><?= h($navPage['nav_title'] ?: $navPage['title']) ?></a>
                <?php endforeach; ?>
                <button type="button" class="btn-contact-reception" id="btnContactReception" data-i18n="header.contactReception">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    Contacter la réception
                </button>
            </nav>
            <div class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <!-- Page Hero -->
    <section class="page-hero" style="background-image: url('<?= h($heroImage) ?>');">
        <div class="page-hero-content">
            <p class="hero-subtitle"><?= h($hotelName) ?></p>
            <h1 class="page-hero-title"<?= $page['i18n_hero_title_key'] ? ' data-i18n="' . h($page['i18n_hero_title_key']) . '"' : '' ?>><?= h($pageTitle) ?></h1>
            <?php if ($page['i18n_hero_subtitle_key']): ?>
            <p class="page-hero-subtitle" data-i18n="<?= h($page['i18n_hero_subtitle_key']) ?>"></p>
            <?php endif; ?>
        </div>
    </section>

    <?php
    // Render dynamic sections for this page
    echo renderDynamicSectionsForPage($pageCode, 'fr');

    if (!empty($dynamicSections)):
    ?>
    <script>
        window.dynamicSectionsTranslations = <?= json_encode($dynamicSectionsTranslations, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    </script>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="logo-text">
                        <?= h($hotelName) ?>
                        <span><?= h($logoText) ?></span>
                    </div>
                    <?php $footerDescription = getHotelDescription(); if ($footerDescription): ?>
                    <p><?= h($footerDescription) ?></p>
                    <?php endif; ?>
                </div>
                <div class="footer-nav">
                    <h4 class="footer-title" data-i18n="footer.navigation">Navigation</h4>
                    <ul class="footer-links">
                        <?php foreach ($navPages as $navPage): ?>
                        <?php $navUrl = $navPage['slug'] === '' ? 'index.php' : '/' . $navPage['slug']; ?>
                        <li><a href="<?= h($navUrl) ?>"<?= $navPage['i18n_nav_key'] ? ' data-i18n="' . h($navPage['i18n_nav_key']) . '"' : '' ?>><?= h($navPage['nav_title'] ?: $navPage['title']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="footer-nav">
                    <h4 class="footer-title" data-i18n="footer.services">Services</h4>
                    <ul class="footer-links">
                        <li><a href="services.php" data-i18n="footer.restaurant">Restaurant</a></li>
                        <li><a href="services.php" data-i18n="footer.bar">Bar</a></li>
                        <li class="room-service-item">
                            <a href="room-service.php" data-i18n="footer.roomService">Room Service</a>
                            <span class="qr-badge" data-i18n="footer.qrOnly">QR code</span>
                        </li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4 class="footer-title" data-i18n="footer.contactTitle">Contact</h4>
                    <?php if (hasContactInfo()): ?>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span><?= getFormattedAddress() ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($contactInfo['phone'])): ?>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                        <span><?= h($contactInfo['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($contactInfo['email'])): ?>
                    <div class="footer-contact-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <span><?= h($contactInfo['email']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-bottom">
                <p data-i18n="footer.copyright">&copy; <?= date('Y') ?> <?= h($hotelName) ?>. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button class="scroll-top" id="scrollTop" data-i18n-aria="common.backToTop" aria-label="Retour en haut">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="18 15 12 9 6 15"/>
        </svg>
    </button>

    <!-- Contact Reception Modal -->
    <div class="modal-overlay" id="contactReceptionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 data-i18n="modal.contactReceptionTitle">Contacter la réception</h3>
                <button type="button" class="modal-close" id="modalClose" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body">
                <?php if ($roomSession): ?>
                <div class="modal-success" id="modalSuccess" style="display: none;">
                    <div class="modal-success-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3 data-i18n="modal.successTitle">Message envoyé</h3>
                    <p data-i18n="modal.successMessage">Votre message a bien été transmis à la réception. Nous vous répondrons dans les meilleurs délais.</p>
                    <button type="button" class="btn-new-message" id="btnNewMessage" data-i18n="modal.newMessage">Envoyer un autre message</button>
                </div>
                <div id="modalFormContainer">
                    <div class="modal-alert-error" id="modalError" style="display: none;"></div>
                    <form method="POST" class="modal-form" id="modalMessageForm" action="contact.php">
                        <input type="hidden" name="action" value="send_message">
                        <input type="hidden" name="redirect_back" value="1">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_room_number" data-i18n="modal.roomNumber">Numéro de chambre *</label>
                                <input type="text" id="modal_room_number" name="msg_room_number"
                                    value="<?= h($roomSession['room_number']) ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="modal_guest_name" data-i18n="modal.guestName">Votre nom</label>
                                <input type="text" id="modal_guest_name" name="msg_guest_name" placeholder="Optionnel" data-i18n-placeholder="modal.guestNamePlaceholder">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_category" data-i18n="modal.category">Catégorie</label>
                                <select id="modal_category" name="msg_category">
                                    <?php foreach ($messageCategories as $key => $label): ?>
                                    <option value="<?= h($key) ?>"><?= h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal_subject" data-i18n="modal.subject">Objet</label>
                                <input type="text" id="modal_subject" name="msg_subject" placeholder="Résumé du problème" data-i18n-placeholder="modal.subjectPlaceholder">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="modal_message" data-i18n="modal.message">Votre message *</label>
                            <textarea id="modal_message" name="msg_message" required placeholder="Décrivez votre demande ou problème..." data-i18n-placeholder="modal.messagePlaceholder"></textarea>
                        </div>
                        <button type="submit" class="btn-submit-modal" data-i18n="modal.sendMessage">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="22" y1="2" x2="11" y2="13"/>
                                <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                            </svg>
                            Envoyer le message
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div class="modal-locked">
                    <div class="modal-locked-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <h3 data-i18n="modal.lockedTitle">Fonctionnalité réservée aux clients</h3>
                    <p data-i18n="modal.lockedMessage">Scannez le QR code présent dans votre chambre pour contacter la réception.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/translations.js"></script>
    <script src="js/i18n.js"></script>
    <script src="js/animations.js"></script>
    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');

        menuToggle.addEventListener('click', () => {
            menuToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
        });

        // Close mobile nav on outside tap
        document.addEventListener('click', (e) => {
            if (navMenu.classList.contains('active') &&
                !navMenu.contains(e.target) &&
                !menuToggle.contains(e.target)) {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            }
        });

        // Close mobile nav when a link is tapped
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                navMenu.classList.remove('active');
                menuToggle.classList.remove('active');
            });
        });

        // Header scroll effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Scroll to top button
        const scrollTop = document.getElementById('scrollTop');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 500) {
                scrollTop.classList.add('visible');
            } else {
                scrollTop.classList.remove('visible');
            }
        });

        scrollTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // Contact Reception Modal
        const modal = document.getElementById('contactReceptionModal');
        const btnOpenModal = document.getElementById('btnContactReception');
        const btnCloseModal = document.getElementById('modalClose');

        let modalOpener = null;

        function openModal() {
            modalOpener = document.activeElement;
            modal.classList.add('active');
            document.body.classList.add('modal-open');
            menuToggle.classList.remove('active');
            navMenu.classList.remove('active');
            const firstFocusable = modal.querySelector('button:not([disabled]), input, textarea');
            if (firstFocusable) firstFocusable.focus();
        }

        function closeModal() {
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
            if (modalOpener) { modalOpener.focus(); modalOpener = null; }
        }

        btnOpenModal.addEventListener('click', openModal);
        btnCloseModal.addEventListener('click', closeModal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) closeModal();
        });

        modal.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('active') || e.key !== 'Tab') return;
            const focusable = Array.from(modal.querySelectorAll(
                'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), a[href]'
            )).filter(el => el.offsetParent !== null);
            if (focusable.length < 2) return;
            const first = focusable[0];
            const last  = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault(); last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault(); first.focus();
            }
        });

        // Form interactions — only present when room session is active
        const modalForm = document.getElementById('modalMessageForm');
        if (modalForm) {
            const modalSuccess = document.getElementById('modalSuccess');
            const modalFormContainer = document.getElementById('modalFormContainer');
            const modalError = document.getElementById('modalError');
            const btnNewMessage = document.getElementById('btnNewMessage');

            btnNewMessage.addEventListener('click', () => {
                modalForm.reset();
                modalError.style.display = 'none';
                modalSuccess.style.display = 'none';
                modalFormContainer.style.display = 'block';
            });

            modalForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(modalForm);
                modalError.style.display = 'none';

                try {
                    const response = await fetch('contact.php', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.success) {
                        modalFormContainer.style.display = 'none';
                        modalSuccess.style.display = 'block';
                    } else {
                        modalError.textContent = data.error || (window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.');
                        modalError.style.display = 'block';
                    }
                } catch (error) {
                    modalError.textContent = window.I18n ? window.I18n.t('modal.errorGeneric') : 'Une erreur est survenue. Veuillez réessayer.';
                    modalError.style.display = 'block';
                }
            });
        }
    </script>
</body>
</html>
