/**
 * Service Worker for Room Service - Hôtel Corintel
 * Enables offline access to room service menu
 */

const CACHE_VERSION = 'v1.0.0';
const CACHE_NAME = `hotel-room-service-${CACHE_VERSION}`;

// Assets to cache on install
const STATIC_ASSETS = [
    '/style.css',
    '/js/translations.js',
    '/js/i18n.js',
    '/js/animations.js'
];

// API endpoints to cache
const API_ENDPOINTS = [
    '/api/room-service-menu.php'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name.startsWith('hotel-room-service-') && name !== CACHE_NAME)
                    .map((name) => {
                        console.log('[SW] Deleting old cache:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache with network fallback
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip admin pages
    if (url.pathname.startsWith('/admin')) {
        return;
    }

    // Handle API requests (network-first with cache fallback)
    if (url.pathname.includes('/api/') || url.pathname.includes('room-service-menu')) {
        event.respondWith(networkFirstStrategy(event.request));
        return;
    }

    // Handle room-service page (network-first)
    if (url.pathname.includes('room-service.php')) {
        event.respondWith(networkFirstStrategy(event.request));
        return;
    }

    // Handle static assets (cache-first)
    if (isStaticAsset(url.pathname)) {
        event.respondWith(cacheFirstStrategy(event.request));
        return;
    }

    // Default: network-first
    event.respondWith(networkFirstStrategy(event.request));
});

// Check if URL is a static asset
function isStaticAsset(pathname) {
    return pathname.match(/\.(css|js|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico)$/i);
}

// Cache-first strategy (for static assets)
async function cacheFirstStrategy(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('[SW] Cache-first fetch failed:', error);
        return new Response('Offline', { status: 503 });
    }
}

// Network-first strategy (for dynamic content)
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('[SW] Network failed, trying cache:', request.url);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline fallback for HTML pages
        if (request.headers.get('Accept')?.includes('text/html')) {
            return new Response(getOfflineHTML(), {
                status: 503,
                headers: { 'Content-Type': 'text/html; charset=utf-8' }
            });
        }

        return new Response(JSON.stringify({ error: 'offline', message: 'Connexion indisponible' }), {
            status: 503,
            headers: { 'Content-Type': 'application/json' }
        });
    }
}

// Offline HTML fallback
function getOfflineHTML() {
    return `
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hors ligne | Room Service</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Lato', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #FAF6F0 0%, #fff 100%);
            padding: 2rem;
        }
        .offline-card {
            max-width: 400px;
            background: white;
            border-radius: 16px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 10px 40px rgba(139, 111, 71, 0.15);
        }
        .offline-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: #FAF6F0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .offline-icon svg {
            width: 40px;
            height: 40px;
            color: #8B6F47;
        }
        h1 {
            font-family: 'Cormorant Garamond', Georgia, serif;
            font-size: 1.75rem;
            color: #8B6F47;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .retry-btn {
            display: inline-block;
            padding: 0.875rem 2rem;
            background: #8B6F47;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
        }
        .retry-btn:hover {
            background: #6B5635;
        }
    </style>
</head>
<body>
    <div class="offline-card">
        <div class="offline-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="1" y1="1" x2="23" y2="23"/>
                <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
                <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
                <path d="M10.71 5.05A16 16 0 0 1 22.58 9"/>
                <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                <line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
        </div>
        <h1>Connexion perdue</h1>
        <p>Impossible de charger le Room Service. Veuillez vérifier votre connexion internet et réessayer.</p>
        <button class="retry-btn" onclick="location.reload()">Réessayer</button>
    </div>
</body>
</html>`;
}

// Listen for messages from the main thread
self.addEventListener('message', (event) => {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }

    // Cache menu data when received
    if (event.data.type === 'CACHE_MENU') {
        cacheMenuData(event.data.data);
    }
});

// Cache menu data
async function cacheMenuData(menuData) {
    const cache = await caches.open(CACHE_NAME);
    const response = new Response(JSON.stringify(menuData), {
        headers: { 'Content-Type': 'application/json' }
    });
    await cache.put('/api/room-service-menu-cached', response);
    console.log('[SW] Menu data cached');
}

// Push notification handling
self.addEventListener('push', (event) => {
    console.log('[SW] Push received:', event);

    let data = { title: 'Room Service', body: 'Nouvelle notification' };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: '/images/icon-192.png',
        badge: '/images/badge-72.png',
        vibrate: [100, 50, 100],
        data: data.data || {},
        actions: data.actions || []
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event);
    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/room-service.php';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Try to focus existing window
                for (const client of clientList) {
                    if (client.url.includes('room-service') && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});
