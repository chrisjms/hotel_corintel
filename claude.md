# Hôtel Corintel - Project Context

## Project Overview

A hotel website for Hôtel Corintel (Bordeaux, France) with two main parts:
- **Client-facing site**: Public pages for guests (home, services, room service, activities, contact)
- **Admin panel**: Dashboard for hotel staff to manage orders, messages, and content

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP (no framework) |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Database | MySQL with PDO |
| i18n | Custom JS-based system (4 languages: FR, EN, ES, IT) |

**Do NOT introduce:**
- PHP frameworks (Laravel, Symfony, etc.)
- JavaScript frameworks (React, Vue, Angular, etc.)
- CSS frameworks (Bootstrap, Tailwind, etc.)
- Node.js or npm-based build tools
- Composer dependencies (unless absolutely necessary)

## Project Structure

```
/
├── index.php, services.php, contact.php, etc.  # Client pages
├── style.css                                    # Main stylesheet
├── js/
│   ├── translations.js                          # i18n strings
│   ├── i18n.js                                  # i18n system
│   └── animations.js                            # Scroll animations
├── includes/
│   ├── functions.php                            # Helper functions
│   ├── auth.php                                 # Authentication
│   └── images-helper.php                        # Image utilities
├── admin/
│   ├── index.php                                # Admin dashboard
│   ├── admin-style.css                          # Admin styles
│   ├── room-service-*.php                       # Order/message management
│   └── api/                                     # Admin API endpoints
├── config/
│   └── database.php                             # DB configuration
└── uploads/                                     # User uploads
```

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

### CSS Variables (Client Site)
```css
--color-primary: #8B6F47       /* Warm brown */
--color-primary-dark: #6B5635  /* Darker brown */
--color-accent: #5C7C5E        /* Sage green */
--color-cream: #FAF6F0         /* Background */
--font-heading: 'Cormorant Garamond', serif
--font-body: 'Lato', sans-serif
```

### i18n
- Add translations to `js/translations.js` for all 4 languages
- Use `data-i18n="key.subkey"` attributes in HTML
- Use `data-i18n-placeholder` for input placeholders

### Forms
- POST to same page or dedicated handler
- Server-side validation always required
- Return JSON for AJAX requests
- Use `htmlspecialchars()` for all output

## Database Tables

Key tables:
- `room_service_items` - Menu items
- `room_service_orders` - Orders with items (JSON)
- `guest_messages` - Messages to reception
- `images` - Dynamic images by section
- `settings` - Admin settings

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