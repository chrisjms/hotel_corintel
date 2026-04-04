<?php
/**
 * Section Rendering Functions
 * HTML rendering for dynamic sections, translation data for client-side
 */

function renderDynamicSection(array $section, string $currentLang = 'fr'): string {
    $templateType = $section['template_type'] ?? 'services_indicators';

    // Get localized overlay texts
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';

    // Check if section has any content to display
    $hasOverlayContent = !empty($subtitle) || !empty($title) || !empty($description);
    $hasFeatures = !empty($section['features']);
    $hasServices = !empty($section['services']);
    $hasImages = !empty($section['blocks']);

    // If section has no content, use the section's custom name as a fallback title
    // This ensures newly created sections are visible until content is added
    if (!$hasOverlayContent && !$hasFeatures && !$hasServices && !$hasImages) {
        // Use custom_name or name as fallback title
        $fallbackTitle = $section['custom_name'] ?? $section['name'] ?? '';
        if (!empty($fallbackTitle)) {
            // Inject fallback title into overlay so render functions can use it
            if (!isset($section['overlay'])) {
                $section['overlay'] = [];
            }
            $section['overlay']['title'] = $fallbackTitle;
            // Also set it for the current language
            if (!isset($section['overlay']['translations'])) {
                $section['overlay']['translations'] = [];
            }
            if (!isset($section['overlay']['translations'][$currentLang])) {
                $section['overlay']['translations'][$currentLang] = [];
            }
            $section['overlay']['translations'][$currentLang]['title'] = $fallbackTitle;
        } else {
            // No content and no name - truly empty section
            return '';
        }
    }

    // Get CSS class from template
    $cssClass = $section['template_css_class'] ?? 'section-services-indicators';
    $sectionCode = $section['code'];

    // Start output buffering
    ob_start();

    switch ($templateType) {
        case 'services_indicators':
        case 'intro_style': // Legacy support
            renderServicesIndicatorsSection($section, $currentLang, $cssClass);
            break;

        case 'text_style':
            renderTextStyleSection($section, $currentLang, $cssClass);
            break;

        case 'services_style':
            renderServicesStyleSection($section, $currentLang, $cssClass);
            break;

        case 'services_checklist':
        case 'detail_style': // Legacy support
            renderServicesChecklistSection($section, $currentLang, $cssClass);
            break;

        case 'gallery_style':
            renderGalleryStyleSection($section, $currentLang, $cssClass);
            break;

        case 'gallery_cards':
            renderGalleryCardsSection($section, $currentLang, $cssClass);
            break;

        case 'presentation_hero':
            renderPresentationHeroSection($section, $currentLang, $cssClass);
            break;

        default:
            // Fallback to services indicators
            renderServicesIndicatorsSection($section, $currentLang, $cssClass);
    }

    return ob_get_clean();
}

/**
 * Render services section with indicators (icons + labels)
 */
function renderServicesIndicatorsSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $features = $section['features'] ?? [];
    $blocks = $section['blocks'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');
    $imagePos = getImagePositionClass($section['image_position'] ?? 'left');

    // Get first image if available
    $image = !empty($blocks) ? $blocks[0] : null;
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <div class="intro-grid <?= h($imagePos) ?>">
                <?php if ($image && !empty($image['image_filename'])): ?>
                <div class="intro-image">
                    <img src="<?= h($image['image_filename']) ?>" alt="<?= h($image['image_alt'] ?: $title) ?>">
                </div>
                <?php endif; ?>
                <div class="intro-content" <?= (!$image || empty($image['image_filename'])) ? 'style="grid-column: 1 / -1;"' : '' ?>>
                    <?php if ($subtitle): ?>
                    <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                    <?php endif; ?>
                    <?php if ($title): ?>
                    <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                    <?php endif; ?>
                    <?php if ($description): ?>
                    <div class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description">
                        <?php
                        $paragraphs = preg_split('/\n\s*\n/', $description);
                        foreach ($paragraphs as $p):
                            $p = trim($p);
                            if ($p):
                        ?>
                        <p><?= nl2br(h($p)) ?></p>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($features)): ?>
                    <div class="intro-features">
                        <?php foreach ($features as $feature):
                            $featureLabel = $feature['translations'][$currentLang] ?? $feature['label'];
                        ?>
                        <div class="intro-feature">
                            <?= getIconSvg($feature['icon_code']) ?>
                            <span data-dynamic-feature="<?= $feature['id'] ?>"><?= h($featureLabel) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    // Render CTA button if section has a link configured
                    $linkData = getSectionLinkWithTranslations($sectionCode, $currentLang);
                    if (!empty($linkData['url'])):
                        $linkText = $linkData['text'] ?: 'En savoir plus';
                        $linkTarget = $linkData['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : '';
                    ?>
                    <div class="section-cta">
                        <a href="<?= h($linkData['url']) ?>" class="btn btn-primary section-link-btn"<?= $linkTarget ?> data-dynamic-link="<?= h($sectionCode) ?>">
                            <?= h($linkText) ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render text-style section (text only + optional features)
 */
function renderTextStyleSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $features = $section['features'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    if (empty($subtitle) && empty($title) && empty($description) && empty($features)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <div class="text-content" style="max-width: 800px; margin: 0 auto; text-align: center;">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <div class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description">
                    <?php
                    $paragraphs = preg_split('/\n\s*\n/', $description);
                    foreach ($paragraphs as $p):
                        $p = trim($p);
                        if ($p):
                    ?>
                    <p><?= nl2br(h($p)) ?></p>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($features)): ?>
                <div class="intro-features" style="justify-content: center; margin-top: 2rem;">
                    <?php foreach ($features as $feature):
                        $featureLabel = $feature['translations'][$currentLang] ?? $feature['label'];
                    ?>
                    <div class="intro-feature">
                        <?= getIconSvg($feature['icon_code']) ?>
                        <span data-dynamic-feature="<?= $feature['id'] ?>"><?= h($featureLabel) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render services-style section (title + services grid)
 */
function renderServicesStyleSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $services = $section['services'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    // If no overlay content and no services, don't render
    if (empty($subtitle) && empty($title) && empty($description) && empty($services)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <?php if ($subtitle || $title || $description): ?>
            <div class="section-header">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <p class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($services)): ?>
            <div class="services-grid">
                <?php foreach ($services as $service):
                    $serviceLabel = getServiceLabelForLanguage($service, $currentLang);
                    $serviceDescription = getServiceDescriptionForLanguage($service, $currentLang);
                ?>
                <div class="service-card">
                    <div class="service-icon">
                        <?= getIconSvg($service['icon_code']) ?>
                    </div>
                    <h3 data-dynamic-service="<?= $service['id'] ?>:label"><?= h($serviceLabel) ?></h3>
                    <?php if (!empty($serviceDescription)): ?>
                    <p data-dynamic-service="<?= $service['id'] ?>:description"><?= h($serviceDescription) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Render gallery-style section (image cards grid like Wine Tourism)
 */
function renderGalleryStyleSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $galleryItems = $section['gallery_items'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    // If no overlay content and no gallery items, don't render
    if (empty($subtitle) && empty($title) && empty($description) && empty($galleryItems)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <?php if ($subtitle || $title || $description): ?>
            <div class="section-header">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <p class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($galleryItems)): ?>
            <div class="activities-grid">
                <?php foreach ($galleryItems as $item):
                    $itemTitle = getGalleryItemTitleForLanguage($item, $currentLang);
                    $itemDescription = getGalleryItemDescriptionForLanguage($item, $currentLang);
                ?>
                <div class="activity-card">
                    <img src="<?= h($item['image_filename']) ?>" alt="<?= h($item['image_alt'] ?: $itemTitle) ?>">
                    <div class="activity-card-content">
                        <h3 data-dynamic-gallery="<?= $item['id'] ?>:title"><?= h($itemTitle) ?></h3>
                        <?php if (!empty($itemDescription)): ?>
                        <p data-dynamic-gallery="<?= $item['id'] ?>:description"><?= h($itemDescription) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Render gallery cards section (room-card style with overlay)
 * Uses the room-card layout with image and overlay title/description
 */
function renderGalleryCardsSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $galleryItems = $section['gallery_items'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');

    // If no gallery items, don't render the section
    if (empty($galleryItems)) {
        return;
    }
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <?php if ($subtitle || $title || $description): ?>
            <div class="section-header">
                <?php if ($subtitle): ?>
                <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                <?php endif; ?>
                <?php if ($title): ?>
                <h2 class="section-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
                <?php endif; ?>
                <?php if ($description): ?>
                <p class="section-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="rooms-gallery">
                <?php foreach ($galleryItems as $item):
                    $itemTitle = getGalleryItemTitleForLanguage($item, $currentLang);
                    $itemDescription = getGalleryItemDescriptionForLanguage($item, $currentLang);
                ?>
                <div class="room-card">
                    <img src="<?= h($item['image_filename']) ?>" alt="<?= h($item['image_alt'] ?: $itemTitle) ?>">
                    <div class="room-card-overlay">
                        <h4 data-dynamic-gallery="<?= $item['id'] ?>:title"><?= h($itemTitle) ?></h4>
                        <?php if (!empty($itemDescription)): ?>
                        <p data-dynamic-gallery="<?= $item['id'] ?>:description"><?= h($itemDescription) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render services section with checklist (checkmarks + labels)
 * Uses service-detail layout with checkmark feature tags
 */
function renderServicesChecklistSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $features = $section['features'] ?? [];
    $blocks = $section['blocks'] ?? [];
    $sectionCode = $section['code'];
    $bgClass = getSectionBackgroundClass($section['background_color'] ?? 'cream');
    $imagePos = getImagePositionClass($section['image_position'] ?? 'left');

    // Get first image if available
    $image = !empty($blocks) ? $blocks[0] : null;
    $hasImage = $image && !empty($image['image_filename']);
    ?>
    <section class="section <?= h($bgClass) ?> <?= h($cssClass) ?>" data-section="<?= h($sectionCode) ?>">
        <div class="container">
            <div class="service-detail <?= h($imagePos) ?>">
                <?php if ($hasImage): ?>
                <div class="service-detail-image">
                    <img src="<?= h($image['image_filename']) ?>" alt="<?= h($image['image_alt'] ?: $title) ?>">
                </div>
                <?php endif; ?>
                <div class="service-detail-content" <?= !$hasImage ? 'style="grid-column: 1 / -1;"' : '' ?>>
                    <?php if ($subtitle): ?>
                    <p class="section-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
                    <?php endif; ?>
                    <?php if ($title): ?>
                    <h3 data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h3>
                    <?php endif; ?>
                    <?php if ($description): ?>
                    <div data-dynamic-text="<?= h($sectionCode) ?>:description">
                        <?php
                        $paragraphs = preg_split('/\n\s*\n/', $description);
                        foreach ($paragraphs as $p):
                            $p = trim($p);
                            if ($p):
                        ?>
                        <p><?= nl2br(h($p)) ?></p>
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($features)): ?>
                    <div class="service-features">
                        <?php foreach ($features as $feature):
                            $featureLabel = $feature['translations'][$currentLang] ?? $feature['label'];
                        ?>
                        <span class="service-feature-tag" data-dynamic-feature="<?= $feature['id'] ?>">
                            <?= getIconSvg('check') ?>
                            <?= h($featureLabel) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php
                    // Render CTA button if section has a link configured
                    $linkData = getSectionLinkWithTranslations($sectionCode, $currentLang);
                    if (!empty($linkData['url'])):
                        $linkText = $linkData['text'] ?: 'En savoir plus';
                        $linkTarget = $linkData['new_tab'] ? ' target="_blank" rel="noopener noreferrer"' : '';
                    ?>
                    <div class="section-cta">
                        <a href="<?= h($linkData['url']) ?>" class="btn btn-primary section-link-btn"<?= $linkTarget ?> data-dynamic-link="<?= h($sectionCode) ?>">
                            <?= h($linkText) ?>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                                <polyline points="15 3 21 3 21 9"/>
                                <line x1="10" y1="14" x2="21" y2="3"/>
                            </svg>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php
}

/**
 * Render presentation hero section (full-width image with text overlay)
 * Similar to page heroes but as a reusable dynamic section
 */
function renderPresentationHeroSection(array $section, string $currentLang, string $cssClass): void {
    $overlay = $section['overlay'] ?? [];
    $subtitle = $overlay['translations'][$currentLang]['subtitle'] ?? $overlay['subtitle'] ?? '';
    $title = $overlay['translations'][$currentLang]['title'] ?? $overlay['title'] ?? '';
    $description = $overlay['translations'][$currentLang]['description'] ?? $overlay['description'] ?? '';
    $blocks = $section['blocks'] ?? [];
    $sectionCode = $section['code'];

    // Get first image - required for this section type
    $image = !empty($blocks) ? $blocks[0] : null;

    // If no image, don't render the section
    if (!$image || empty($image['image_filename'])) {
        return;
    }
    ?>
    <section class="presentation-hero" data-section="<?= h($sectionCode) ?>" style="background-image: url('<?= h($image['image_filename']) ?>');">
        <div class="presentation-hero-overlay">
            <?php if ($subtitle): ?>
            <p class="presentation-hero-subtitle" data-dynamic-text="<?= h($sectionCode) ?>:subtitle"><?= h($subtitle) ?></p>
            <?php endif; ?>
            <?php if ($title): ?>
            <h2 class="presentation-hero-title" data-dynamic-text="<?= h($sectionCode) ?>:title"><?= h($title) ?></h2>
            <?php endif; ?>
            <?php if ($description): ?>
            <p class="presentation-hero-description" data-dynamic-text="<?= h($sectionCode) ?>:description"><?= h($description) ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php
}

/**
 * Render all dynamic sections for a page
 * Returns HTML string, or empty string if no sections
 *
 * @param string $page Page code (e.g., 'home')
 * @param string $currentLang Current language code
 * @return string HTML output
 */
function renderDynamicSectionsForPage(string $page, string $currentLang = 'fr'): string {
    $sections = getDynamicSectionsWithData($page);

    if (empty($sections)) {
        return '';
    }

    $output = '';
    foreach ($sections as $section) {
        $output .= renderDynamicSection($section, $currentLang);
    }

    return $output;
}

/**
 * Get dynamic sections translations for JavaScript
 * Used for client-side language switching
 *
 * @param string $page Page code
 * @return array Translations keyed by section code
 */
function getDynamicSectionsTranslations(string $page): array {
    $sections = getDynamicSectionsWithData($page);
    $translations = [];

    foreach ($sections as $section) {
        $sectionCode = $section['code'];
        $overlay = $section['overlay'] ?? [];

        // Build translations for overlay texts
        $translations[$sectionCode] = [
            'fr' => [
                'subtitle' => $overlay['subtitle'] ?? '',
                'title' => $overlay['title'] ?? '',
                'description' => $overlay['description'] ?? ''
            ]
        ];

        foreach (['en', 'es', 'it'] as $lang) {
            $translations[$sectionCode][$lang] = [
                'subtitle' => $overlay['translations'][$lang]['subtitle'] ?? '',
                'title' => $overlay['translations'][$lang]['title'] ?? '',
                'description' => $overlay['translations'][$lang]['description'] ?? ''
            ];
        }

        // Build translations for features
        if (!empty($section['features'])) {
            $translations[$sectionCode]['features'] = [];
            foreach ($section['features'] as $feature) {
                $translations[$sectionCode]['features'][$feature['id']] = [
                    'fr' => $feature['label'],
                    'en' => $feature['translations']['en'] ?? $feature['label'],
                    'es' => $feature['translations']['es'] ?? $feature['label'],
                    'it' => $feature['translations']['it'] ?? $feature['label']
                ];
            }
        }

        // Build translations for services
        if (!empty($section['services'])) {
            $translations[$sectionCode]['services'] = [];
            foreach ($section['services'] as $service) {
                $translations[$sectionCode]['services'][$service['id']] = [
                    'fr' => [
                        'label' => $service['label'],
                        'description' => $service['description'] ?? ''
                    ],
                    'en' => [
                        'label' => $service['translations']['en']['label'] ?? $service['label'],
                        'description' => $service['translations']['en']['description'] ?? ($service['description'] ?? '')
                    ],
                    'es' => [
                        'label' => $service['translations']['es']['label'] ?? $service['label'],
                        'description' => $service['translations']['es']['description'] ?? ($service['description'] ?? '')
                    ],
                    'it' => [
                        'label' => $service['translations']['it']['label'] ?? $service['label'],
                        'description' => $service['translations']['it']['description'] ?? ($service['description'] ?? '')
                    ]
                ];
            }
        }

        // Build translations for gallery items
        if (!empty($section['gallery_items'])) {
            $translations[$sectionCode]['gallery'] = [];
            foreach ($section['gallery_items'] as $item) {
                $translations[$sectionCode]['gallery'][$item['id']] = [
                    'fr' => [
                        'title' => $item['title'],
                        'description' => $item['description'] ?? ''
                    ],
                    'en' => [
                        'title' => $item['translations']['en']['title'] ?? $item['title'],
                        'description' => $item['translations']['en']['description'] ?? ($item['description'] ?? '')
                    ],
                    'es' => [
                        'title' => $item['translations']['es']['title'] ?? $item['title'],
                        'description' => $item['translations']['es']['description'] ?? ($item['description'] ?? '')
                    ],
                    'it' => [
                        'title' => $item['translations']['it']['title'] ?? $item['title'],
                        'description' => $item['translations']['it']['description'] ?? ($item['description'] ?? '')
                    ]
                ];
            }
        }

        // Build translations for section links (CTA buttons)
        if (sectionSupportsLinks($section['template_type'] ?? '')) {
            $linkData = getSectionLinkWithTranslations($sectionCode);
            if (!empty($linkData['url'])) {
                $translations[$sectionCode]['link'] = [
                    'url' => $linkData['url'],
                    'new_tab' => $linkData['new_tab'],
                    'fr' => $linkData['text'] ?? 'En savoir plus',
                    'en' => $linkData['translations']['en'] ?? $linkData['text'] ?? 'Learn more',
                    'es' => $linkData['translations']['es'] ?? $linkData['text'] ?? 'Saber más',
                    'it' => $linkData['translations']['it'] ?? $linkData['text'] ?? 'Scopri di più'
                ];
            }
        }
    }

    return $translations;
}
