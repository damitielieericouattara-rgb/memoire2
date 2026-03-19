/**
 * SneakX — Simulation Engine (Mode hors-ligne)
 * Gère panier, commandes, wallet, notifications sans backend
 * Stockage : localStorage avec clé 'sneakx_state'
 */

const STATE_KEY = 'sneakx_state';

function now() { return new Date().toISOString(); }
function uid() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 6); }
function fmt(v) { return new Intl.NumberFormat('fr-FR').format(v); }

/* ── État initial ── */
function defaultState() {
    return {
        wallet: { balance: 50000, currency: 'XOF', transactions: [] },
        cart: { items: [] },
        orders: [],
        notifications: [
            { id: uid(), type: 'SYSTEM', title: 'Bienvenue sur SneakX !', message: 'Explorez notre catalogue et profitez de la livraison gratuite dès 50 000 XOF.', read: false, created_at: now(), icon: 'fa-star' },
            { id: uid(), type: 'PROMOTION', title: '🔥 Offre Limitée', message: 'Jusqu\'à -30% sur les sneakers Nike ce weekend. Profitez-en avant la fin du stock !', read: false, created_at: now(), icon: 'fa-tag' }
        ]
    };
}

/* ── Persistance ── */
export function getState() {
    try { return JSON.parse(localStorage.getItem(STATE_KEY)) || defaultState(); }
    catch { return defaultState(); }
}
function saveState(s) { localStorage.setItem(STATE_KEY, JSON.stringify(s)); }

/* ══════════════════════════════════════════
   PANIER
══════════════════════════════════════════ */
export const CartSim = {
    getAll() {
        const s = getState();
        const total = s.cart.items.reduce((sum, i) => sum + i.price * i.qty, 0);
        const count = s.cart.items.reduce((sum, i) => sum + i.qty, 0);
        return { items: s.cart.items, total, count };
    },

    add(product, qty = 1) {
        const s = getState();
        const existing = s.cart.items.find(i => i.product_id === product.id);
        if (existing) {
            existing.qty = Math.min(existing.qty + qty, product.stock || 99);
        } else {
            s.cart.items.push({
                id: uid(),
                product_id: product.id,
                name: product.name,
                brand: product.brand || '',
                price: product.promo_price || product.price,
                original_price: product.price,
                image: product.image_url || product.image || '',
                qty,
                stock: product.stock || 99
            });
        }
        saveState(s);
        CartSim.updateBadge();
        return { success: true, count: CartSim.getAll().count };
    },

    updateQty(itemId, qty) {
        const s = getState();
        const item = s.cart.items.find(i => i.id === itemId);
        if (item) {
            if (qty <= 0) s.cart.items = s.cart.items.filter(i => i.id !== itemId);
            else item.qty = qty;
        }
        saveState(s);
        CartSim.updateBadge();
    },

    remove(itemId) {
        const s = getState();
        s.cart.items = s.cart.items.filter(i => i.id !== itemId);
        saveState(s);
        CartSim.updateBadge();
    },

    clear() {
        const s = getState();
        s.cart.items = [];
        saveState(s);
        CartSim.updateBadge();
    },

    updateBadge() {
        const { count } = CartSim.getAll();
        document.querySelectorAll('#cart-badge, .sx-badge[data-cart]').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    }
};

/* ══════════════════════════════════════════
   WALLET
══════════════════════════════════════════ */
export const WalletSim = {
    get() { return getState().wallet; },

    recharge(amount, method) {
        const s = getState();
        s.wallet.balance += amount;
        s.wallet.transactions.unshift({
            id: uid(), type: 'CREDIT', amount,
            description: `Recharge ${method === 'mobile' ? 'Mobile Money' : 'Carte bancaire'}`,
            created_at: now()
        });
        saveState(s);
        NotifSim.push('PAYMENT', 'Recharge effectuée ✓',
            `Votre wallet a été rechargé de ${fmt(amount)} XOF. Nouveau solde : ${fmt(s.wallet.balance)} XOF.`, 'fa-wallet');
        return s.wallet;
    },

    debit(amount, description) {
        const s = getState();
        if (s.wallet.balance < amount) throw new Error('Solde insuffisant');
        s.wallet.balance -= amount;
        s.wallet.transactions.unshift({
            id: uid(), type: 'DEBIT', amount,
            description, created_at: now()
        });
        saveState(s);
        return s.wallet;
    }
};

/* ══════════════════════════════════════════
   COMMANDES
══════════════════════════════════════════ */

// Abidjan coordinates for realistic simulation
const ABIDJAN_ZONES = [
    { name: 'Marcory', lat: 5.3084, lng: -4.0083 },
    { name: 'Plateau', lat: 5.3221, lng: -4.0176 },
    { name: 'Cocody', lat: 5.3599, lng: -3.9892 },
    { name: 'Yopougon', lat: 5.3457, lng: -4.0746 },
    { name: 'Adjamé', lat: 5.3667, lng: -4.0167 },
    { name: 'Treichville', lat: 5.2969, lng: -4.0144 },
];

const WAREHOUSE = { lat: 5.3484, lng: -4.0232, name: 'Entrepôt SneakX' };

export const OrderSim = {
    getAll(filters = {}) {
        let orders = getState().orders;
        if (filters.status) orders = orders.filter(o => o.status === filters.status);
        if (filters.q) orders = orders.filter(o => o.order_number.includes(filters.q));
        return orders.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    },

    getById(id) {
        return getState().orders.find(o => o.id === id || o.order_number === id);
    },

    create(cartItems, address, paymentMethod) {
        const s = getState();
        const subtotal = cartItems.reduce((sum, i) => sum + i.price * i.qty, 0);
        const delivery = subtotal >= 50000 ? 0 : 2500;
        const total = subtotal + delivery;

        if (paymentMethod === 'wallet') {
            WalletSim.debit(total, `Commande SneakX #${uid().slice(0, 6).toUpperCase()}`);
        }

        const orderId = uid();
        const orderNum = 'SX-' + Date.now().toString().slice(-6);
        const deliveryZone = ABIDJAN_ZONES[Math.floor(Math.random() * ABIDJAN_ZONES.length)];

        const order = {
            id: orderId,
            order_number: orderNum,
            status: 'RECEIVED',
            created_at: now(),
            estimated_delivery: new Date(Date.now() + 2 * 24 * 3600 * 1000).toISOString(),
            items: cartItems.map(i => ({ ...i, product_name: i.name, product_image: i.image })),
            amount_ttc: total,
            subtotal, delivery_fee: delivery,
            address, payment_method: paymentMethod,
            // Tracking data
            tracking: {
                history: [
                    { status: 'RECEIVED', label: 'Commande reçue', time: now(), done: true }
                ],
                driver: null,
                deliveryZone,
                route: []
            }
        };

        s.orders.unshift(order);
        saveState(s);

        // Notification
        NotifSim.push('ORDER', `Commande ${orderNum} confirmée !`,
            `Votre commande de ${fmt(total)} XOF a été confirmée. Livraison prévue dans 2 jours.`, 'fa-box');

        // Start simulation
        OrderSim._simulateProgress(orderId);

        return order;
    },

    cancel(id, reason) {
        const s = getState();
        const order = s.orders.find(o => o.id === id);
        if (!order) throw new Error('Commande introuvable');
        if (!['RECEIVED', 'PREPARING'].includes(order.status)) throw new Error('Cette commande ne peut plus être annulée');
        order.status = 'CANCELLED';
        order.cancellation_reason = reason;
        // Refund if wallet
        if (order.payment_method === 'wallet') {
            WalletSim.recharge(order.amount_ttc, 'Remboursement ' + order.order_number);
        }
        NotifSim.push('ORDER', 'Commande annulée', `Votre commande ${order.order_number} a été annulée. Remboursement en cours.`, 'fa-times-circle');
        saveState(s);
    },

    // Simulate order progression over time
    _simulateProgress(orderId) {
        const STEPS = [
            { status: 'PREPARING', label: 'En cours de préparation', delay: 8000 },
            { status: 'SHIPPED', label: 'Colis expédié', delay: 20000 },
            { status: 'IN_DELIVERY', label: 'Livreur en route', delay: 35000 },
            { status: 'DELIVERED', label: 'Livré avec succès', delay: 90000 },
        ];

        STEPS.forEach(({ status, label, delay }) => {
            setTimeout(() => {
                const s = getState();
                const order = s.orders.find(o => o.id === orderId);
                if (!order || ['CANCELLED', 'DELIVERED'].includes(order.status)) return;
                order.status = status;
                order.tracking.history.push({ status, label, time: now(), done: true });

                if (status === 'IN_DELIVERY') {
                    // Assign a simulated driver
                    order.tracking.driver = {
                        name: ['Konan Koffi', 'Aya Traoré', 'Moussa Diallo'][Math.floor(Math.random() * 3)],
                        phone: '+225 07 ' + Math.floor(10000000 + Math.random() * 89999999),
                        rating: (4.2 + Math.random() * 0.8).toFixed(1),
                        lat: WAREHOUSE.lat,
                        lng: WAREHOUSE.lng
                    };
                    // Build route from warehouse to delivery zone
                    order.tracking.route = OrderSim._buildRoute(WAREHOUSE, order.tracking.deliveryZone);
                    order.tracking.routeIndex = 0;
                }

                saveState(s);

                // Fire notification
                const notifMessages = {
                    PREPARING: `Votre commande ${order.order_number} est en cours de préparation.`,
                    SHIPPED: `Votre colis ${order.order_number} a été expédié !`,
                    IN_DELIVERY: `Votre livreur est en route ! Suivez-le en temps réel.`,
                    DELIVERED: `Commande ${order.order_number} livrée ! Merci de votre confiance.`
                };
                NotifSim.push('ORDER', 'Mise à jour commande', notifMessages[status] || label, 'fa-truck');

                // Dispatch event so live pages can update
                window.dispatchEvent(new CustomEvent('sx-order-update', { detail: { orderId, status, order } }));
            }, delay);
        });
    },

    _buildRoute(from, to) {
        const steps = 30;
        const route = [];
        for (let i = 0; i <= steps; i++) {
            const t = i / steps;
            // Add slight randomness for realistic path
            const jitter = (Math.random() - 0.5) * 0.004;
            route.push({
                lat: from.lat + (to.lat - from.lat) * t + jitter,
                lng: from.lng + (to.lng - from.lng) * t + jitter
            });
        }
        return route;
    },

    // Move driver along route (called by tracking page)
    advanceDriver(orderId) {
        const s = getState();
        const order = s.orders.find(o => o.id === orderId);
        if (!order?.tracking?.driver || !order.tracking.route?.length) return null;
        const idx = Math.min((order.tracking.routeIndex || 0) + 1, order.tracking.route.length - 1);
        order.tracking.routeIndex = idx;
        const pos = order.tracking.route[idx];
        order.tracking.driver.lat = pos.lat;
        order.tracking.driver.lng = pos.lng;
        saveState(s);
        return order.tracking.driver;
    }
};

/* ══════════════════════════════════════════
   NOTIFICATIONS
══════════════════════════════════════════ */
export const NotifSim = {
    getAll(type = null) {
        const notifs = getState().notifications;
        return type ? notifs.filter(n => n.type === type) : notifs;
    },

    unreadCount() {
        return getState().notifications.filter(n => !n.read).length;
    },

    push(type, title, message, icon = 'fa-bell') {
        const s = getState();
        const notif = { id: uid(), type, title, message, icon, read: false, created_at: now() };
        s.notifications.unshift(notif);
        if (s.notifications.length > 50) s.notifications = s.notifications.slice(0, 50);
        saveState(s);
        NotifSim._updateBadge();
        window.dispatchEvent(new CustomEvent('sx-notification', { detail: notif }));
        NotifSim._showToast(title, message);
        return notif;
    },

    markRead(id) {
        const s = getState();
        const n = s.notifications.find(n => n.id === id);
        if (n) n.read = true;
        saveState(s);
        NotifSim._updateBadge();
    },

    markAllRead() {
        const s = getState();
        s.notifications.forEach(n => n.read = true);
        saveState(s);
        NotifSim._updateBadge();
    },

    dismiss(id) {
        const s = getState();
        s.notifications = s.notifications.filter(n => n.id !== id);
        saveState(s);
        NotifSim._updateBadge();
    },

    _updateBadge() {
        const count = NotifSim.unreadCount();
        document.querySelectorAll('#notif-badge, [data-notif-badge]').forEach(el => {
            el.textContent = count;
            el.style.display = count > 0 ? 'flex' : 'none';
        });
    },

    _showToast(title, message) {
        const t = document.createElement('div');
        t.style.cssText = `
            position:fixed;bottom:24px;right:24px;z-index:9999;
            background:var(--bg3);border:1px solid var(--orange-border);
            color:var(--text);padding:14px 18px;border-radius:12px;
            font-size:13px;box-shadow:0 8px 32px rgba(0,0,0,.5);
            max-width:320px;animation:slideInRight .3s ease;
            border-left:3px solid var(--orange);
        `;
        t.innerHTML = `<div style="font-weight:700;margin-bottom:3px;color:var(--orange)">${title}</div><div style="color:var(--text2);font-size:12px">${message}</div>`;
        document.body.appendChild(t);
        setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .4s'; setTimeout(()=>t.remove(),400); }, 4500);
    }
};

// Init badge on load
if (typeof window !== 'undefined') {
    window.addEventListener('DOMContentLoaded', () => {
        CartSim.updateBadge();
        NotifSim._updateBadge();
    });
}

/* ── DEMO PRODUCTS (same as catalogue) ── */
export const DEMO_PRODUCTS = [
    { id:1,  name:'Air Jordan Retro 100',   brand:'Nike',         price:189990, promo_price:149990, stock:15, average_rating:4.8, reviews_count:342, image_url:'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80' },
    { id:2,  name:'Ultra Boost Cloud',       brand:'Adidas',       price:210000, promo_price:null,   stock:8,  average_rating:4.9, reviews_count:218, image_url:'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=400&q=80' },
    { id:3,  name:'Shadow X Runner',         brand:'New Balance',  price:155000, promo_price:null,   stock:22, average_rating:4.6, reviews_count:97,  image_url:'https://images.unsplash.com/photo-1600269452121-4f2416e55c28?w=400&q=80' },
    { id:4,  name:'Trail Blazer Pro',        brand:'ASICS',        price:175000, promo_price:140000, stock:5,  average_rating:4.7, reviews_count:154, image_url:'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=400&q=80' },
    { id:5,  name:'Velvet Classic High',     brand:'Jordan',       price:220000, promo_price:null,   stock:12, average_rating:4.9, reviews_count:423, image_url:'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=400&q=80' },
    { id:6,  name:'Summer Pulse',            brand:'Puma',         price:130000, promo_price:99000,  stock:30, average_rating:4.5, reviews_count:88,  image_url:'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=400&q=80' },
    { id:7,  name:'Coastal Runner',          brand:'Nike',         price:165000, promo_price:null,   stock:18, average_rating:4.7, reviews_count:201, image_url:'https://images.unsplash.com/photo-1539185441755-769473a23570?w=400&q=80' },
    { id:8,  name:'Force Alpha Blanc',       brand:'Nike',         price:195000, promo_price:165000, stock:7,  average_rating:4.8, reviews_count:319, image_url:'https://images.unsplash.com/photo-1575537302964-96cd47c06b1b?w=400&q=80' },
    { id:9,  name:'Neon Street Kid',         brand:'Adidas',       price:140000, promo_price:null,   stock:25, average_rating:4.6, reviews_count:133, image_url:'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=400&q=80' },
    { id:10, name:'Terra Vibe',              brand:'Reebok',       price:120000, promo_price:89000,  stock:14, average_rating:4.4, reviews_count:76,  image_url:'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=400&q=80' },
    { id:11, name:'Phantom Rush',            brand:'Under Armour', price:180000, promo_price:null,   stock:9,  average_rating:4.7, reviews_count:187, image_url:'https://images.unsplash.com/photo-1552346154-21d32810ade7?w=400&q=80' },
    { id:12, name:'Glow Trainer Orange',     brand:'New Balance',  price:160000, promo_price:128000, stock:20, average_rating:4.8, reviews_count:264, image_url:'https://images.unsplash.com/photo-1515955656352-a1fa3ffcd111?w=400&q=80' },
];

export default { CartSim, WalletSim, OrderSim, NotifSim, DEMO_PRODUCTS, getState };
