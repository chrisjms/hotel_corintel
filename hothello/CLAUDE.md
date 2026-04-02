# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Hothello is a static marketing website for a hospitality digital transformation company. No build tools, bundlers, or package managers are used — it's plain HTML, CSS, and vanilla JavaScript.

## Development

To preview the site locally, serve the root directory with any static file server:

```bash
python3 -m http.server 8000
# or
npx serve .
```

A local server is required (not `file://`) because the i18n system fetches JSON translation files via `fetch()`.

There are no build, lint, or test commands.

## Architecture

### Pages
Four static HTML pages: `index.html`, `services.html`, `pricing.html`, `contact.html`. Each page duplicates the header/footer markup (no templating system).

### CSS (`css/styles.css`)
Single stylesheet using CSS custom properties (`:root` variables) for theming. Follows BEM naming convention (e.g., `.service-card__title`, `.hero__background-overlay`). Architecture is organized as: Variables → Base → Components → Layouts → Pages. Color palette uses sage green primary (`#6B8F71`) and soft gold accent (`#C2A14D`).

### JavaScript (`js/main.js`)
Single IIFE containing all site behavior:
- **i18n system**: Custom translation engine using `data-i18n` attributes on HTML elements. Translations are loaded from `lang/en.json` and `lang/fr.json` via fetch. Language preference is persisted in `localStorage` under `hothello-lang`. Also supports `data-i18n-placeholder`, `data-i18n-title`, and `data-i18n-meta-description` attributes.
- **Navigation**: Mobile hamburger toggle, header scroll effect, active link highlighting
- **Animations**: IntersectionObserver-based scroll reveal, hero parallax, magnetic button hover effects
- **Contact form**: Client-side only (logs to console, shows success UI)

### Internationalization (`lang/`)
Translation keys are nested JSON objects (e.g., `services.communication.features.portals`). Both `en.json` and `fr.json` must stay in sync — every key in one file must exist in the other. When adding translatable text to HTML, use `data-i18n="key.path"` and add the corresponding entries to both language files.

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
