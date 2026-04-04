# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Multi-hotel platform with four surfaces, each in its own folder:
- **Client site** (`client/`): Public guest-facing pages ([slug].hothello.ovh)
- **Admin panel** (`admin/`): Staff dashboard (admin-[slug].hothello.ovh)
- **Super admin** (`superadmin/`): Global platform management (superadmin.hothello.ovh)
- **Vitrine** (`vitrine/`): Showcase site (hothello.ovh)
- **Shared code** (`shared/`): Config, includes, JS, uploads — never a DocumentRoot

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP (no framework), Apache with mod_rewrite |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Database | PostgreSQL via Supabase (PDO, `pgsql:` driver) |
| File storage | Cloudflare R2 |
| i18n | Custom JS-based system (FR, EN, ES, IT) |
| Hosting | VPS OVH Starter (~6€/mois) |
| SSL | Certbot / Let's Encrypt (wildcard via DNS challenge API OVH) |

**Do NOT introduce** PHP frameworks, JS frameworks, CSS frameworks, Node.js/npm, or Composer dependencies.

## Workflow Rule — Commit Message

**À la fin de chaque réponse impliquant une modification de code**, générer un message de commit git adapté au travail effectué, au format :

```
<type>(<scope>): <résumé en français>

<détail optionnel si nécessaire>
```

Types : `feat`, `fix`, `refactor`, `chore`, `docs`
Scope : surface concernée (`hotel-context`, `superadmin`, `admin`, `client`, `shared`, etc.)

## Infrastructure

### Hébergement actuel — Render.com (staging)
Déploiement temporaire sur Render.com en attendant la migration VPS. Pas de wildcard DNS disponible → hotel détecté via `?hotel=slug` + cookie.

| Service Render | URL | Usage |
|---|---|---|
| `hothello-client` | `hothello-client.onrender.com` | Site client |
| `hothello-admin` | `hothello-admin.onrender.com` | Panel admin |
| `hothello-superadmin` | `hothello-superadmin.onrender.com` | Super admin |

**Accès multi-hotel sur Render** :
- Client : `https://hothello-client.onrender.com/?hotel=<slug>`
- Admin : `https://hothello-admin.onrender.com/?hotel=<slug>` (le slug est persisté en cookie `_hotel_slug`, 30 jours)
- Le slug est automatiquement conservé entre les redirections (ex: `requireAuth()` → `login.php`) grâce au cookie

### Serveur VPS OVH (futur)
- **Offre** : VPS Starter (2 vCPU, 2 Go RAM, 20 Go SSD), OS Ubuntu 24
- **Stack** : Apache + PHP + Certbot
- **Déploiement** : SSH manuel (`git pull`)
- **Virtual hosts** : 1 par site (vitrine, superadmin, et 2 par hôtel : client + admin)

### Noms de domaine (OVH)
- Domaine principal : `hothello.ovh`
- Wildcard DNS : `*.hothello.ovh` → IP du VPS

| Sous-domaine | Usage |
|---|---|
| `hothello.ovh` | Site vitrine |
| `superadmin.hothello.ovh` | Super admin |
| `[hotel].hothello.ovh` | Site client de chaque hôtel |
| `admin-[hotel].hothello.ovh` | Panel admin de chaque hôtel |

### Base de données — Supabase (PostgreSQL)
- Connexion via pgBouncer pooler : port `6543`, `sslmode=require`, `ATTR_EMULATE_PREPARES => true`
- Config dans `shared/config/database.php` (DSN `pgsql:`)
- **Booléens** : PostgreSQL retourne `'t'`/`'f'` via PDO. Dans le code PHP, utiliser `filter_var($val, FILTER_VALIDATE_BOOLEAN)` pour tester les valeurs booléennes récupérées de la DB. Dans les requêtes SQL, utiliser `= TRUE` / `= FALSE` (jamais `= 1` / `= 0`).
- **Intervalles de date** : utiliser `NOW() - INTERVAL '15 minutes'` (jamais `DATE_SUB`)
- **Agrégation** : `STRING_AGG()` (jamais `GROUP_CONCAT`)
- **Auto-incrément** : `SERIAL` (jamais `AUTO_INCREMENT`)
- **Schéma global** : `shared/setup/schema.sql` + migrations dans `shared/config/migrations/`
- **Schéma par hotel** : chaque hotel a son propre schema PostgreSQL `hotel_{slug}` créé automatiquement à la création de l'hotel via superadmin. Le `search_path` est positionné sur ce schema dès la résolution du contexte hotel.
- **Multi-tenancy** : toutes les tables per-hotel ont une colonne `hotel_id` (compatibilité) ET sont dans leur schema dédié. La migration `010_add_schema_name_to_hotels.sql` ajoute la colonne `schema_name` à la table `public.hotels`.
- **DDL pgBouncer** : ne jamais grouper plusieurs instructions SQL dans un seul `exec()`. Toujours séparer chaque statement (`CREATE TABLE`, `ALTER TABLE`, `CREATE TRIGGER`, etc.) en appels `exec()` distincts.
- **Feature toggles** : table `public.establishment_features` (migration 012). Feature keys définis dans `AVAILABLE_FEATURES` constant (`super-functions.php`) : `room_service`, `messaging`, `qr_codes`, `multilingual`, `dynamic_pages`, `housekeeping`. Tous activés par défaut si aucune ligne n'existe.

## Development

```bash
# Run locally (PHP built-in server) — each surface separately
php -S localhost:8000 -t client/ client/router.php   # Client site
php -S localhost:8001 -t admin/                        # Admin panel
php -S localhost:8002 -t superadmin/                   # Super admin

# Accès local multi-hotel :
# Client : http://localhost:8000/?hotel=corintel
# Admin  : http://localhost:8001/?hotel=corintel&context=admin
# Superadmin : http://localhost:8002/ (pas de hotel requis)

# First-time setup : exécuter les fichiers SQL dans cet ordre via Supabase SQL Editor
# 1. shared/setup/schema.sql
# 2. shared/setup/003_create_guest_messages_table.sql
# 3. shared/config/migrations/003 à 012 (dans l'ordre numérique)
```

No build step, no bundler, no preprocessor. Edit PHP/JS/CSS files directly.

## Architecture

### Folder Structure

```
hotel/
├── shared/          # Shared code & assets (never a DocumentRoot)
│   ├── bootstrap.php    # Defines HOTEL_ROOT constant, loads hotel context
│   ├── config/          # database.php, hotel-context.php, apache-vhosts.conf, migrations/
│   ├── includes/        # auth.php, functions.php, content-helper.php, images-helper.php, i18n.php
│   ├── js/              # translations.js, i18n.js, animations.js, images.js
│   ├── uploads/         # Per-hotel uploads (hotel_{ID}/)
│   ├── images/          # Legacy static images
│   └── setup/           # schema.sql, install.php
├── client/          # [slug].hothello.ovh — guest-facing pages
├── admin/           # admin-[slug].hothello.ovh — hotel staff dashboard
├── superadmin/      # superadmin.hothello.ovh — platform management
└── vitrine/         # hothello.ovh — static showcase site
```

### Routing

Each surface has its own Apache VHost with separate DocumentRoot. Shared assets (JS, uploads, images) served via Apache `Alias` directives.

**Client site** (`client/.htaccess`):
- Static pages: `index.php`, `services.php`, `activites.php`, `contact.php`, `room-service.php`
- Dynamic pages: URLs like `/my-page` rewrite to `page.php?slug=my-page`
- APIs: `client/api/*` served directly

**Admin panel**: served directly at its own subdomain
**Super admin**: served directly at its own subdomain

### Database Connection

Singleton pattern in `shared/config/database.php` via `getDatabase(): PDO`. Every entry point includes bootstrap first:
```php
require_once __DIR__ . '/../shared/bootstrap.php';  // defines HOTEL_ROOT constant
require_once HOTEL_ROOT . '/shared/includes/functions.php';
$pdo = getDatabase();
```

Connexion PostgreSQL via Supabase pgBouncer (port 6543). `ATTR_EMULATE_PREPARES => true` obligatoire en mode transaction pgBouncer.

### Key Include Files

- `shared/bootstrap.php` — Entry point bootstrap, defines `HOTEL_ROOT`, résout le contexte hotel, appelle `requireHotel()` pour les surfaces client/admin
- `shared/config/hotel-context.php` — `HotelContext` singleton : résolution hotel (sous-domaine → `?hotel=slug` → cookie `_hotel_slug`), `SET search_path` automatique
- `shared/includes/functions.php` — Module aggregator that loads 18 themed modules from `shared/includes/modules/`
- `shared/includes/auth.php` — Admin authentication (session + persistent token cookies)
- `shared/includes/images-helper.php` — `img()`, `imgTag()` shorthand for image rendering with fallbacks
- `shared/includes/content-helper.php` — `content()`, `contentFirst()`, `contentImage()` for dynamic content blocks
- `superadmin/includes/super-auth.php` — Super admin authentication (separate from hotel admin)
- `superadmin/includes/super-functions.php` — CRUD hotels, création schema PostgreSQL, provisioning données par défaut, feature toggles, monitoring, analytics, DB health, filtered audit log, bulk actions

### QR Code → Room Service Flow

1. Admin generates QR codes per room in `admin/rooms.php` using HMAC-SHA256 tokens
2. Guest scans QR → `client/scan.php?room=X&token=TOKEN` validates token, sets `$_SESSION['room_service_access']`
3. All 5 client pages check `getRoomServiceSession()` to show room badge in nav + unlock contact modal
4. `room-service.php` uses `checkRoomServiceAccess()` (checks session OR URL params)
5. Cart stored client-side in localStorage; order submitted via POST

### Content System (Three Layers)

1. **Legacy images** (`images` table): Section + position slots, accessed via `img($section, $position)`
2. **Content blocks** (`content_sections` + `content_blocks` tables): Hero sections and dynamic blocks with translations
3. **Dynamic sections** (`section_templates`, `section_features`, etc.): Full page sections with multilingual support, loaded via `getDynamicSectionsWithData($pageCode)`

### i18n System

- Static strings: `shared/js/translations.js` (nested objects per language) + `shared/js/i18n.js` (applies translations)
- HTML attributes: `data-i18n="key.subkey"` (innerHTML), `data-i18n-placeholder` (inputs), `data-i18n-aria`, `data-i18n-title`
- DB-driven translations: Hero overlays and dynamic sections stored in translation tables, injected as `window.heroOverlayTranslations` / `window.dynamicSectionsTranslations`
- Language detection: Browser language → localStorage (`hotel_corintel_lang`) → defaults to French

### Admin Real-Time Updates

No WebSockets. Polling via `setInterval` (every 5s) to:
- `admin/api/dashboard-updates.php` — order/message counts
- `admin/api/orders-updates.php` — order status changes
- `admin/api/messages-updates.php` — message status changes

### Auth System

- Session-based (`hotel_admin_session`) with persistent token cookies (`hotel_admin_auth`)
- All admin pages call `requireAuth()` at top
- Rate limiting: 5 login attempts per IP per 15 minutes (désactivé sur le superadmin)
- Persistent tokens: SHA256-hashed in `persistent_tokens` table
- **Cross-login superadmin → admin** : token HMAC-SHA256, 60s d'expiration, nonce anti-replay. L'URL générée inclut `?hotel=slug` pour la compatibilité Render.
- **Safari popup fix** : `window.open()` appelé synchroniquement au clic, URL assignée après le fetch (évite le blocage popup Safari)

## Code Conventions

### CSS Variables (Client)
```css
--color-primary: #8B6F47;       /* Warm brown */
--color-primary-dark: #6B5635;
--color-accent: #5C7C5E;        /* Sage green */
--color-cream: #FAF6F0;         /* Background */
--font-heading: 'Cormorant Garamond', serif;
--font-body: 'Lato', sans-serif;
```

### CSS Variables (Admin)
```css
--admin-primary: #8B5A2B;
--admin-sidebar: #1A202C;
--admin-bg: #F7FAFC;
```

### Naming
- CSS: kebab-case
- JavaScript: camelCase
- PHP functions: camelCase
- Database columns: snake_case

### Security
- PDO prepared statements for all queries
- `htmlspecialchars()` for all output
- Server-side validation required on all forms
- Return JSON for AJAX requests (`X-Requested-With: XMLHttpRequest`)

### Backend Architecture Pattern

The project follows a **"fat page" with modular helpers** pattern — no MVC framework, but well-structured:

```
Page PHP (index.php, rooms.php, etc.)
  = POST handler (business logic) + HTML rendering (view) in one file
  ↓ calls
Shared modules (shared/includes/modules/*.php)
  = Business logic layer (CRUD functions, stats, helpers)
  ↓ calls
PDO / Supabase (shared/config/database.php)
  = Data layer (singleton connection, pgBouncer)
```

- **Pages** handle routing, auth, POST actions, and HTML output
- **Modules** (`shared/includes/modules/`) contain reusable business functions organized by domain (18 modules)
- **API endpoints** (`*/api/`) are thin JSON wrappers around module functions
- Each surface (client, admin, superadmin) has its own `includes/` for surface-specific logic

### Page Pattern

Each client page (in `client/`) is self-contained: starts with `require_once __DIR__ . '/../shared/bootstrap.php'`, includes its own dependencies via `HOTEL_ROOT`, inline JS, and calls `getRoomServiceSession()` at top. The contact modal and mobile nav JS are duplicated across all 5 pages.

## Status Workflows

- **Orders**: pending → confirmed → preparing → delivered (or cancelled)
- **Messages**: new → read → in_progress → resolved

## Key Features

### Client Site
- Room service ordering system with cart
- Guest messaging to reception (modal in header)
- Multi-language support (FR default)
- Dynamic image loading from database
- Responsive design

### Admin Panel
- Real-time dashboard with statistics
- Room service order management
- Guest message management with status tracking
- Image management per section
- CSV/PDF export capabilities

### Super Admin Panel
- **Hotel/Pizzeria CRUD** with onboarding wizard (4-step creation flow)
- **Global Analytics** — cross-schema aggregation of orders, revenue, messages, QR scans with period selector (7/30/90j) and CSS bar chart
- **Server Monitoring** — real DB metrics (latency, size, connections) + simulated CPU/RAM/uptime, auto-refresh 30s
- **Database Health** — pg_stat_user_tables, per-schema sizes, dead tuples, connection pool visualization, auto-refresh 60s
- **Feature Toggles** — enable/disable features per establishment (room_service, messaging, qr_codes, multilingual, dynamic_pages, housekeeping) via AJAX toggles. Table: `public.establishment_features` (migration 012)
- **Enriched Audit Log** — filters by hotel, action type, date range, search text
- **Bulk Actions** — select multiple hotels, activate/deactivate in bulk, export CSV
- **Per-Hotel Performance Cards** — mini-stats (orders 7d, unread messages, last admin login, QR scans) lazy-loaded via AJAX with skeleton placeholders
- **Cross-login** — HMAC-SHA256 signed tokens, 60s expiry, nonce anti-replay

### Super Admin Architecture Pattern
```
superadmin/
├── index.php              # Hotel list + mini-stats + bulk actions
├── analytics.php          # Global analytics dashboard
├── monitoring.php         # Server monitoring dashboard
├── db-health.php          # Database health panel
├── feature-toggles.php    # Feature toggles per hotel
├── onboarding.php         # Multi-step creation wizard
├── audit-log.php          # Enriched audit log with filters
├── hotel-form.php         # Edit hotel form
├── settings.php           # Super admin settings
├── api/                   # JSON API endpoints
│   ├── analytics-data.php
│   ├── monitoring-data.php
│   ├── db-health-data.php
│   ├── hotel-stats.php
│   ├── toggle-feature.php
│   ├── bulk-action.php
│   └── generate-cross-login.php
└── includes/
    ├── super-auth.php     # Authentication + CSRF
    ├── super-functions.php # All business logic (~15 functions)
    └── sidebar.php        # Navigation component
```

### CSS Variables (Super Admin)
```css
--sa-primary: #4299E1;     /* Blue */
--sa-success: #48BB78;     /* Green */
--sa-error: #F56565;       /* Red */
--sa-warning: #ED8936;     /* Orange */
--sa-sidebar: #1A202C;     /* Dark sidebar */
--sa-bg: #F7FAFC;          /* Light background */
```
Light/dark theme via `[data-theme="dark"]` + `localStorage('sa_theme')`.

## Development Rules

### Code Style
- Use existing CSS variables (defined in `:root` in `client/style.css`)
- Follow existing naming conventions (kebab-case for CSS, camelCase for JS)
- Keep PHP files self-contained (each page includes its own dependencies)
- Use PDO prepared statements for all database queries

### i18n
- Add translations to `shared/js/translations.js` for all 4 languages
- Use `data-i18n="key.subkey"` attributes in HTML
- Use `data-i18n-placeholder` for input placeholders

### Forms
- POST to same page or dedicated handler
- Server-side validation always required
- Return JSON for AJAX requests
- Use `htmlspecialchars()` for all output

## UX Constraints

### Client Site
- Maintain warm, elegant hotel aesthetic
- Mobile-first responsive design
- Smooth scroll animations (respect `prefers-reduced-motion`)
- Modal dialogs for interactive features
- Form validation with clear error messages

### Admin Panel
- Clean, functional interface
- Real-time updates where applicable
- Status-based workflows (new → read → in_progress → resolved)
- Confirmation dialogs for destructive actions
- Accessible from any device

## Security

- Sanitize all user inputs
- Use prepared statements (PDO)
- Session-based admin authentication
- No sensitive data in client-side code
- Validate file uploads (images only, size limits)

## Workflow Orchestration

### 1. Plan Mode Default
Enter plan mode for ANY non-trivial task (3+ steps or architectural decisions)
- If something goes sideways, STOP and re-plan immediately - don't keep pushing
- Use plan mode for verification steps, not just building
- Write detailed specs upfront to reduce ambiguity

### 2. Subagent Strategy
- Use subagents liberally to keep main context window clean
- Offload research, exploration, and parallel analysis to subagents
- For complex problems, throw more compute at it via subagents
- One tack per subagent for focused execution

### 3. Self-Improvement Loop
- After ANY correction from the user: update 'tasks/lessons.md' with the pattern
- Write rules for yourself that prevent the same mistake
- Ruthlessly iterate on these lessons until mistake rate drops
- Review lessons at session start for relevant project

### 4. Verification Before Done
- Never mark a task complete without proving it works
- Diff behavior between main and your changes, when relevant
- Ask yourself: "Would a staff engineer approve this?"
- Run tests, check logs, demonstrate correctness

### 5. Demand Elegance (Balanced)
- For non-trivial changes: pause and ask "Is there a more elegant way?"
- If a fix feels hacky: "Knowing everything I know now, implement the elegant solution"
- Skip this for simple, obvious fixes - don't over-engineer
- Challenge your own work before presenting it

### 6. Autonomous Bug Fixing
- When given a bug report: just fix it. Don't ask for hand-holding
- Point at logs, errors, failing tests - then resolve them
- Zero context switching required from the user
- Go fix failing CI tests without being told how

## Task Management
1. *Plan First*: Write plan to 'tasks/todo.md' with checkable items
2. *Verify Plan*: Check in before starting implementation
3. *Track Progress*: Mark items complete as you go
4. *Explain Changes*: High-level summary at each step
5. *Document Results*: Add review section to 'tasks/todo.md'
6. *Capture Lessons*: Update 'tasks/lessons.md' after corrections

## Core Principles
- *Simplicity First*: Make every change as simple as possible. Impact minimal code.
- *No Laziness*: Find root causes. No temporary fixes. Senior developer standards.
- *Minimal Impact*: Changes should only touch what's necessary. Avoid introducing bugs.