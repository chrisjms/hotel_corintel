# Hôtel Corintel - Session Memory

## QR Code / Room Service System (Feb 2026)

### Architecture
- QR codes → `scan.php?room=X&token=TOKEN` (entry point)
- `scan.php` validates HMAC token, sets `$_SESSION['room_service_access']`, redirects to `index.php`
- All client pages read `$roomSession = getRoomServiceSession()` at the top
- Room Service nav link shows green "Ch. X" badge when session active, else "QR" badge
- `room-service.php` still validates via `checkRoomServiceAccess()` (session OR URL params)

### Key functions (all in includes/functions.php)
- `generateRoomServiceToken(roomId, roomNumber)` — HMAC-SHA256, 32-char hex
- `generateRoomServiceUrl(roomId, roomNumber)` — produces `scan.php?room=X&token=TOKEN`
- `validateRoomServiceAccess(roomId, token)` — checks DB + HMAC
- `setRoomServiceSession(room)` — writes `$_SESSION['room_service_access']`
- `getRoomServiceSession()` — reads session, returns null if none
- `checkRoomServiceAccess()` — used by room-service.php, checks URL params then session

### CSS classes (style.css)
- `.nav-qr-badge` — brown badge "QR" shown without session
- `.nav-room-badge` — sage green badge "Ch. X" shown with active session
- `.modal-locked` / `.modal-locked-icon` — locked state styles for contact modal

### "Contacter la réception" modal pattern (all 5 pages)
Button: always rendered (no PHP guard)
Modal: always rendered; `<?php if ($roomSession): ?>` inside modal-body shows form vs locked state
JS: modal open/close always wired; `if (modalForm)` guard for form-specific AJAX listeners
Backend: `requireRoomSession($isAjaxRequest)` in contact.php POST handler
Room number: pre-filled readonly when session active

### Pages modified for room session awareness
index.php, services.php, activites.php, contact.php, page.php
Note: page.php has no shared JS file — full inline script added (menu toggle, header scroll, scroll-top, modal)

### Admin QR generation
admin/rooms.php → `generateRoomServiceUrl()` → auto-produces correct scan.php URLs

## Mobile Nav Pattern (all 5 client pages)
All 5 pages (index.php, services.php, activites.php, contact.php, page.php) have:
- Outside-tap dismiss: `document.addEventListener('click', ...)` checking `navMenu.contains(e.target)`
- Link-tap close: `document.querySelectorAll('.nav-link').forEach(...)`
Active nav indicator: each page adds `active` to its own link (static HTML or PHP-driven dynamic nav)

## Public Contact Form (contact.php)
- Handler: `send_public_message` (no session required), calls `createGuestMessage()` with `room_number: null`
- Email included in message body: `[Email : ...]` appended
- JS: fetch with `X-Requested-With: XMLHttpRequest`, JSON response → in-page success/error divs
- Success div: `#contactFormSuccess` (`.message-success`), error div: `#contactFormError` (`.alert-message-error`)

## Room Service UX (room-service.php)
- M1: Banner hides on scroll-down via `.room-banner.hidden { transform: translateY(-100%) }`, rs-header/tabs shift via `.no-banner` class (only on ≥768px)
- R1: Category tabs overflow fade via `.category-tabs.show-overflow-fade { box-shadow: inset -48px 0 24px -16px #fff }` toggled by JS checking `scrollWidth > clientWidth + scrollLeft + 4`

## Tech Stack Constraints
- No PHP frameworks, no JS frameworks, no CSS frameworks
- PDO prepared statements only
- Vanilla JS + HTML5 + CSS3
