// Fichier: /frontend/service-worker.js

const CACHE_NAME = 'ecommerce-v1';
const STATIC_CACHE = 'static-v1';
const DYNAMIC_CACHE = 'dynamic-v1';

// Ressources à mettre en cache au démarrage
const STATIC_ASSETS = [
    '/frontend/pages/index.html',
    '/frontend/pages/dashboard.html',
    '/frontend/pages/catalogue.html',
    '/frontend/pages/connexion.html',
    '/frontend/pages/inscription.html',
    '/frontend/assets/css/main.css',
    '/frontend/assets/css/accessibility.css',
    '/frontend/assets/js/modules/api.js',
    '/frontend/assets/js/modules/auth.js',
    '/frontend/assets/js/modules/cart.js',
    '/frontend/assets/js/modules/utils.js',
    '/frontend/assets/js/modules/wallet.js',
    '/frontend/assets/js/modules/voice.js',
    '/frontend/assets/js/modules/notifications.js',
    '/frontend/assets/js/modules/accessibility.js',
    '/frontend/assets/js/modules/websocket.js',
    '/frontend/assets/images/placeholder.png',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

// Routes qui ne doivent JAMAIS être en cache
const NEVER_CACHE = [
    '/api/wallet',
    '/api/commandes',
    '/api/auth/login',
    '/api/auth/register',
    '/api/wallet/recharge'
];

// ============================================================
// INSTALLATION
// ============================================================
self.addEventListener('install', (event) => {
    console.log('Service Worker : Installation');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('Service Worker : Mise en cache des ressources statiques');
                return cache.addAll(STATIC_ASSETS);
            })
            .catch((error) => {
                console.error('Erreur lors du cache initial:', error);
            })
    );

    // Force l'activation immédiate
    self.skipWaiting();
});

// ============================================================
// ACTIVATION
// ============================================================
self.addEventListener('activate', (event) => {
    console.log('Service Worker : Activation');

    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter(key => key !== STATIC_CACHE && key !== DYNAMIC_CACHE)
                    .map(key => {
                        console.log('Service Worker : Suppression ancien cache', key);
                        return caches.delete(key);
                    })
            );
        })
    );

    // Prend le contrôle immédiatement
    self.clients.claim();
});

// ============================================================
// STRATÉGIES DE CACHE
// ============================================================
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Ne jamais cacher les routes sensibles
    if (NEVER_CACHE.some(route => url.pathname.includes(route))) {
        event.respondWith(fetch(event.request));
        return;
    }

    // Ressources statiques : Cache First
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(event.request));
        return;
    }

    // API Catalogue : Network First avec fallback cache
    if (url.pathname.includes('/api/produits')) {
        event.respondWith(networkFirstWithCache(event.request, 15 * 60 * 1000)); // 15min
        return;
    }

    // Pages HTML : Stale While Revalidate
    if (event.request.destination === 'document') {
        event.respondWith(staleWhileRevalidate(event.request));
        return;
    }

    // Autres requêtes : Network First
    event.respondWith(networkFirst(event.request));
});

// ============================================================
// STRATÉGIE : CACHE FIRST
// ============================================================
async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        return new Response('Ressource non disponible hors ligne', {
            status: 503,
            headers: { 'Content-Type': 'text/plain' }
        });
    }
}

// ============================================================
// STRATÉGIE : NETWORK FIRST
// ============================================================
async function networkFirst(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        const cached = await caches.match(request);
        if (cached) return cached;

        return offlineFallback(request);
    }
}

// ============================================================
// STRATÉGIE : NETWORK FIRST AVEC EXPIRATION
// ============================================================
async function networkFirstWithCache(request, maxAge) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cached = await cache.match(request);

    // Vérifie si le cache est encore valide
    if (cached) {
        const cachedDate = new Date(cached.headers.get('sw-cached-at') || 0);
        const isExpired = Date.now() - cachedDate.getTime() > maxAge;

        if (!isExpired) return cached;
    }

    try {
        const response = await fetch(request);
        if (response.ok) {
            // Ajoute l'heure de cache dans les headers
            const headers = new Headers(response.headers);
            headers.set('sw-cached-at', new Date().toISOString());

            const cachedResponse = new Response(await response.blob(), {
                status: response.status,
                statusText: response.statusText,
                headers
            });

            cache.put(request, cachedResponse.clone());
            return cachedResponse;
        }
        return response;
    } catch (error) {
        if (cached) return cached;
        return offlineFallback(request);
    }
}

// ============================================================
// STRATÉGIE : STALE WHILE REVALIDATE
// ============================================================
async function staleWhileRevalidate(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cached = await cache.match(request);

    // Revalide en arrière-plan
    const fetchPromise = fetch(request).then((response) => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    }).catch(() => null);

    return cached || fetchPromise || offlineFallback(request);
}

// ============================================================
// PAGE HORS LIGNE
// ============================================================
async function offlineFallback(request) {
    if (request.destination === 'document') {
        const offlinePage = await caches.match('/frontend/pages/offline.html');
        if (offlinePage) return offlinePage;
    }

    return new Response(
        JSON.stringify({
            success: false,
            message: 'Vous êtes hors ligne. Cette fonctionnalité nécessite une connexion internet.',
            offline: true
        }),
        {
            status: 503,
            headers: {
                'Content-Type': 'application/json',
                'Cache-Control': 'no-cache'
            }
        }
    );
}

// ============================================================
// HELPERS
// ============================================================
function isStaticAsset(url) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.webp', '.woff', '.woff2', '.svg', '.ico'];
    return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

// ============================================================
// NOTIFICATIONS PUSH
// ============================================================
self.addEventListener('push', (event) => {
    if (!event.data) return;

    const data = event.data.json();

    const options = {
        body: data.message,
        icon: '/frontend/assets/images/icons/icon-192x192.png',
        badge: '/frontend/assets/images/icons/icon-72x72.png',
        vibrate: [100, 50, 100],
        data: { url: data.url || '/frontend/pages/dashboard.html' },
        actions: [
            { action: 'open', title: 'Voir', icon: '/frontend/assets/images/icons/icon-72x72.png' },
            { action: 'close', title: 'Fermer' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Clic sur notification
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    if (event.action === 'close') return;

    const url = event.notification.data?.url || '/frontend/pages/dashboard.html';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Ouvre ou focus la fenêtre existante
                for (const client of clientList) {
                    if (client.url === url && 'focus' in client) {
                        return client.focus();
                    }
                }
                if (clients.openWindow) {
                    return clients.openWindow(url);
                }
            })
    );
});

// ============================================================
// SYNCHRONISATION EN ARRIÈRE-PLAN
// ============================================================
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-cart') {
        event.waitUntil(syncCart());
    }
    if (event.tag === 'sync-notifications') {
        event.waitUntil(syncNotifications());
    }
});

async function syncCart() {
    const pendingActions = await getPendingActions('cart');
    for (const action of pendingActions) {
        try {
            await fetch('/api/panier', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(action.data)
            });
        } catch (error) {
            console.error('Sync panier échouée:', error);
        }
    }
}

async function syncNotifications() {
    console.log('Synchronisation des notifications...');
}

async function getPendingActions(type) {
    return [];
}