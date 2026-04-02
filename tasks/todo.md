# Option C: Hybrid Translation System — Implementation

## Phase 1: PHP Language Files
- [x] Create `lang/` directory
- [x] Extract 244 keys × 4 languages from `js/translations.js` into `lang/fr.php`, `lang/en.php`, `lang/es.php`, `lang/it.php`
- [x] PHP syntax check: all 4 files passed

## Phase 2: PHP i18n Helper
- [x] Create `includes/i18n.php` with `t()`, `loadTranslations()`, `buildNestedTranslations()`, `getAllTranslationsNested()`
- [x] Verify `t('nav.home', 'en')` → 'Home'
- [x] Verify French fallback: `t('nav.home', 'xx')` → 'Accueil'
- [x] Verify missing key: `t('nonexistent')` → 'nonexistent'

## Phase 3: API Endpoint
- [x] Create `api/translations.php` with file-based cache
- [x] Create `cache/.gitignore`
- [x] JSON diff: API output identical to original `translations.js` structure (0 differences)

## Phase 4: Update `js/i18n.js`
- [x] Add `translations: null` property
- [x] Add `async loadTranslations()` — fetches from `api/translations.php`
- [x] Make `init()` async
- [x] Replace 7 `window.translations` references with `this.translations`
- [x] Keep `window.translations = this.translations` for backward compat

## Phase 5: Update Client Pages
- [x] Remove `<script src="js/translations.js">` from `index.php`
- [x] Remove from `services.php`
- [x] Remove from `activites.php`
- [x] Remove from `contact.php`
- [x] Remove from `room-service.php`
- [x] Remove from `page.php`

## Phase 6: Service Worker
- [x] Bump `CACHE_VERSION` to `v1.1.0`
- [x] Remove `/js/translations.js` from `STATIC_ASSETS`
- [x] Add `/api/translations.php` to `API_ENDPOINTS`

## Phase 7: DeepL Auto-Translation Tool
- [x] Create `tools/auto-translate.php` (CLI-only, batch support, placeholder protection)

## Security
- [x] `.htaccess`: block access to `lang/`, `tools/`, `cache/`

## Verification
- [x] PHP syntax check: all new + modified files passed
- [x] JSON diff: PHP API output vs original JS — 0 differences
- [x] `js/translations.js` still present as rollback safety net (delete after live testing)
