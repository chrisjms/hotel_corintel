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

## Tech Stack Constraints
- No PHP frameworks, no JS frameworks, no CSS frameworks
- PDO prepared statements only
- Vanilla JS + HTML5 + CSS3
