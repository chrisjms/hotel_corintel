# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hotel website. Two main surfaces:
- **Client site**: Public guest-facing pages (home, services, activities, contact, room service)
- **Admin panel**: Staff dashboard for managing orders, messages, rooms, content, and settings

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP (no framework), Apache with mod_rewrite |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Database | MySQL/MariaDB with PDO (utf8mb4) |
| i18n | Custom JS-based system (FR, EN, ES, IT) |
| Hosting | OVH shared hosting |

**Do NOT introduce** PHP frameworks, JS frameworks, CSS frameworks, Node.js/npm, or Composer dependencies.

## Development

```bash
# Run locally (PHP built-in server)
php -S localhost:8000

# First-time setup (creates all DB tables + default admin account)
# Via browser: visit setup/install.php?confirm=1
# Via CLI: php setup/install.php
```

No build step, no bundler, no preprocessor. Edit PHP/JS/CSS files directly.

## Architecture

### Routing

File-based routing with `.htaccess` rewrites:
- Static pages: `index.php`, `services.php`, `activites.php`, `contact.php`, `room-service.php`
- Dynamic pages: URLs like `/my-page` rewrite to `page.php?slug=my-page` (looked up in `pages` DB table)
- Admin: `/admin/*` served directly (no rewrite)
- APIs: `/api/*` and `/admin/api/*` served directly

### Database Connection

Singleton pattern in `config/database.php` via `getDatabase(): PDO`. Every file that needs DB access does:
```php
require_once __DIR__ . '/../config/database.php';  // or via includes/functions.php
$pdo = getDatabase();
```

### Key Include Files

- `includes/functions.php` — All helper functions (images, room service, orders, messages, content, pages, QR tokens). This is the main utility file.
- `includes/auth.php` — Admin authentication (session + persistent token cookies)
- `includes/images-helper.php` — `img()`, `imgTag()` shorthand for image rendering with fallbacks
- `includes/content-helper.php` — `content()`, `contentFirst()`, `contentImage()` for dynamic content blocks

### QR Code → Room Service Flow

1. Admin generates QR codes per room in `admin/rooms.php` using HMAC-SHA256 tokens
2. Guest scans QR → `scan.php?room=X&token=TOKEN` validates token, sets `$_SESSION['room_service_access']`
3. All 5 client pages check `getRoomServiceSession()` to show room badge in nav + unlock contact modal
4. `room-service.php` uses `checkRoomServiceAccess()` (checks session OR URL params)
5. Cart stored client-side in localStorage; order submitted via POST

### Content System (Three Layers)

1. **Legacy images** (`images` table): Section + position slots, accessed via `img($section, $position)`
2. **Content blocks** (`content_sections` + `content_blocks` tables): Hero sections and dynamic blocks with translations
3. **Dynamic sections** (`section_templates`, `section_features`, etc.): Full page sections with multilingual support, loaded via `getDynamicSectionsWithData($pageCode)`

### i18n System

- Static strings: `js/translations.js` (nested objects per language) + `js/i18n.js` (applies translations)
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

Each client page is self-contained: includes its own dependencies, inline JS, and calls `getRoomServiceSession()` at top. The contact modal and mobile nav JS are duplicated across all 5 pages (not extracted to shared file). `page.php` has fully inline scripts since it has no shared JS file.

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
- Use existing CSS variables (defined in `:root` in style.css)
- Follow existing naming conventions (kebab-case for CSS, camelCase for JS)
- Keep PHP files self-contained (each page includes its own dependencies)
- Use PDO prepared statements for all database queries

### i18n
- Add translations to `js/translations.js` for all 4 languages
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