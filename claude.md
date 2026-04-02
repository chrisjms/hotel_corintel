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

## Infrastructure

### Serveur VPS OVH
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
- **Schéma** : `shared/setup/schema.sql` + migrations dans `shared/config/migrations/`

## Development

```bash
# Run locally (PHP built-in server) — each surface separately
php -S localhost:8000 -t client/ client/router.php   # Client site
php -S localhost:8001 -t admin/                        # Admin panel
php -S localhost:8002 -t superadmin/                   # Super admin

# First-time setup : exécuter les fichiers SQL dans cet ordre via Supabase SQL Editor
# 1. shared/setup/schema.sql
# 2. shared/setup/003_create_guest_messages_table.sql
# 3. shared/config/migrations/003 à 008 (dans l'ordre numérique)
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

- `shared/bootstrap.php` — Entry point bootstrap, defines `HOTEL_ROOT` constant, loads hotel context
- `shared/includes/functions.php` — All helper functions (images, room service, orders, messages, content, pages, QR tokens)
- `shared/includes/auth.php` — Admin authentication (session + persistent token cookies)
- `shared/includes/images-helper.php` — `img()`, `imgTag()` shorthand for image rendering with fallbacks
- `shared/includes/content-helper.php` — `content()`, `contentFirst()`, `contentImage()` for dynamic content blocks
- `superadmin/includes/super-auth.php` — Super admin authentication (separate from hotel admin)

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
- Rate limiting: 5 login attempts per IP per 15 minutes
- Persistent tokens: SHA256-hashed in `persistent_tokens` table

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