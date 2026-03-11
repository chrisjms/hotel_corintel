# Mobile UX Fixes - Implementation

## style.css
- [x] `100vh` → `100dvh` with fallback on .hero, .page-hero, .nav-menu
- [x] Hero min-height: 600→500px base, 500→400px at 768px
- [x] Page hero min-height: 400→350px
- [x] Modal input font-size: 0.95rem → 1rem (prevents iOS zoom)
- [x] Touch target: .modal-close 36→44px
- [x] Touch target: .carousel-dot padding+background-clip for 28px touch area
- [x] Heading clamp() floors raised (h1: 2rem, h2: 1.5rem, h3: 1.25rem)
- [x] Added :active states on .btn-primary, .btn-outline, .modal-close

## room-service.php
- [x] Touch target: .btn-close-cart 40→44px
- [x] Touch target: .btn-remove-item 36→44px
- [x] Touch target: .quick-reorder-btn padding 0.375→0.5rem, font 0.75→0.8125rem
- [x] Small text: .order-date 0.7→0.75rem, .order-status 0.65→0.75rem
- [x] Added .checkout-error CSS class
- [x] Added #checkoutError div before checkout button
- [x] Replaced 3 alert() calls with inline showCheckoutError()
- [x] PHP syntax check: passed
