/**
 * api.js — Version Statique (sans backend)
 * Centralise toutes les opérations de données en localStorage
 */

function getUser() {
    try { return JSON.parse(localStorage.getItem('user') || 'null'); } catch { return null; }
}

function getDB() {
    try {
        const d = localStorage.getItem('sneakx_data');
        return d ? JSON.parse(d) : null;
    } catch { return null; }
}

function saveDB(db) {
    localStorage.setItem('sneakx_data', JSON.stringify(db));
}

function ensureDB() {
    let db = getDB();
    if (!db) {
        db = {
            products: [
                { id:1, name:"Nike Air Max 270", brand:"Nike", category_id:1, price:45000, original_price:55000, image:"https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80", sizes:[40,41,42,43,44,45], colors:["Noir","Blanc","Rouge"], stock:15, rating:4.5, reviews:128, featured:true, discount:18 },
                { id:2, name:"Adidas Ultra Boost 22", brand:"Adidas", category_id:2, price:52000, original_price:60000, image:"https://images.unsplash.com/photo-1556905055-8f358a7a797e?w=400&q=80", sizes:[40,41,42,43,44,45], colors:["Bleu","Noir","Blanc"], stock:8, rating:4.7, reviews:89, featured:true, discount:13 },
                { id:3, name:"Air Jordan 1 Retro High", brand:"Jordan", category_id:3, price:75000, original_price:85000, image:"https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&q=80", sizes:[40,41,42,43,44,45], colors:["Rouge/Noir","Blanc/Noir","Bleu"], stock:5, rating:4.9, reviews:256, featured:true, discount:12 },
                { id:4, name:"New Balance 574", brand:"New Balance", category_id:4, price:38000, original_price:42000, image:"https://images.unsplash.com/photo-1608231387022-66b8c6c0445c?w=400&q=80", sizes:[39,40,41,42,43,44], colors:["Gris","Bleu","Vert"], stock:12, rating:4.3, reviews:67, featured:false, discount:10 },
                { id:5, name:"Puma RS-X³", brand:"Puma", category_id:5, price:35000, original_price:40000, image:"https://images.unsplash.com/photo-1600269452121-4f2416e55c28?w=400&q=80", sizes:[40,41,42,43,44], colors:["Noir","Blanc","Rose"], stock:10, rating:4.1, reviews:34, featured:false, discount:13 },
                { id:6, name:"Nike Dunk Low Panda", brand:"Nike", category_id:1, price:48000, original_price:55000, image:"https://images.unsplash.com/photo-1539185441755-769473a23570?w=400&q=80", sizes:[38,39,40,41,42,43,44,45], colors:["Noir/Blanc"], stock:3, rating:4.8, reviews:189, featured:true, discount:13 }
            ],
            categories: [
                { id:1, name:"Nike", slug:"nike", count:15 },
                { id:2, name:"Adidas", slug:"adidas", count:12 },
                { id:3, name:"Jordan", slug:"jordan", count:8 },
                { id:4, name:"New Balance", slug:"new-balance", count:6 },
                { id:5, name:"Puma", slug:"puma", count:4 }
            ],
            orders: [
                { id:1, user_id:2, status:"DELIVERED", total:97000, payment_method:"WALLET", reference:"SX-001", created_at:"2024-02-20", delivered_at:"2024-02-25", items:[{product_id:1,name:"Nike Air Max 270",price:45000,quantity:1,size:42,color:"Noir"}], tracking:[{status:"PENDING",label:"Commande passée",date:"2024-02-20"},{status:"CONFIRMED",label:"Confirmée",date:"2024-02-21"},{status:"SHIPPED",label:"Expédiée",date:"2024-02-23"},{status:"DELIVERED",label:"Livrée",date:"2024-02-25"}] }
            ],
            cart: [],
            wishlist: [],
            wishlists: [],
            notifications: [
                { id:1, user_id:2, title:"Bienvenue !", message:"Profitez de -10% avec le code BIENVENUE10", type:"INFO", read:false, created_at:"2024-02-15" }
            ]
        };
        saveDB(db);
    }
    return db;
}

export function logout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');
    localStorage.removeItem('wallet');
}

export const Auth = {
    login: async (email, password) => {
        const db = ensureDB();
        // Simply store the user session
        const fakeToken = btoa(JSON.stringify({ email, exp: Math.floor(Date.now()/1000) + 86400 * 30 }));
        localStorage.setItem('access_token', fakeToken);
        localStorage.setItem('user', JSON.stringify({ first_name: 'Utilisateur', name: '', email, role: 'USER', wallet: 10000 }));
        localStorage.setItem('wallet', JSON.stringify({ balance: 10000 }));
        return { success: true, data: { access_token: fakeToken, user: { email, first_name: 'Utilisateur' } } };
    },
    register: async (data) => {
        const fakeToken = btoa(JSON.stringify({ email: data.email, exp: Math.floor(Date.now()/1000) + 86400 * 30 }));
        localStorage.setItem('access_token', fakeToken);
        localStorage.setItem('user', JSON.stringify({ ...data, role: 'USER', wallet: 5000 }));
        localStorage.setItem('wallet', JSON.stringify({ balance: 5000 }));
        return { success: true, data: { access_token: fakeToken, user: data } };
    },
    profile: async () => {
        const user = getUser();
        return user ? { success: true, data: { user } } : { success: false };
    }
};

export const Produits = {
    list: async (params = {}) => {
        const db = ensureDB();
        let prods = [...db.products];
        if (params.brand) prods = prods.filter(p => p.brand.toLowerCase() === params.brand.toLowerCase());
        if (params.q) { const q = params.q.toLowerCase(); prods = prods.filter(p => p.name.toLowerCase().includes(q) || p.brand.toLowerCase().includes(q)); }
        return { success: true, data: { produits: prods, total: prods.length } };
    },
    get: async (id) => {
        const db = ensureDB();
        const product = db.products.find(p => p.id == id);
        return product ? { success: true, data: { produit: product } } : { success: false, message: 'Non trouvé' };
    },
    popular: async (limit = 8) => {
        const db = ensureDB();
        return { success: true, data: { produits: db.products.filter(p => p.featured).slice(0, limit) } };
    },
    recommendations: async () => {
        const db = ensureDB();
        return { success: true, data: { produits: db.products.slice(0, 4) } };
    }
};

export const Panier = {
    get: async () => {
        const db = ensureDB();
        const user = getUser();
        const items = user ? (db.cart || []).filter(i => i.user_id == user?.id) : db.cart || [];
        return { success: true, data: { items, total: items.reduce((t, i) => t + i.price * i.quantity, 0) } };
    },
    add: async (product_id, quantity = 1) => {
        const db = ensureDB();
        const user = getUser();
        const product = db.products.find(p => p.id == product_id);
        if (!product) return { success: false };
        db.cart = db.cart || [];
        const existing = db.cart.find(i => i.product_id == product_id);
        if (existing) existing.quantity += quantity;
        else db.cart.push({ id: Date.now(), product_id, user_id: user?.id, name: product.name, price: product.price, image: product.image, quantity });
        saveDB(db);
        return { success: true };
    },
    update: async (id, quantity) => {
        const db = ensureDB();
        const item = (db.cart || []).find(i => i.id == id);
        if (item) { item.quantity = quantity; saveDB(db); }
        return { success: true };
    },
    remove: async (id) => {
        const db = ensureDB();
        db.cart = (db.cart || []).filter(i => i.id != id);
        saveDB(db);
        return { success: true };
    },
    clear: async () => {
        const db = ensureDB();
        db.cart = [];
        saveDB(db);
        return { success: true };
    }
};

export const Commandes = {
    list: async () => {
        const db = ensureDB();
        const user = getUser();
        const orders = user ? (db.orders || []).filter(o => o.user_id == user?.id) : [];
        return { success: true, data: { commandes: orders, total: orders.length } };
    },
    get: async (id) => {
        const db = ensureDB();
        const order = (db.orders || []).find(o => o.id == id);
        return order ? { success: true, data: { commande: order } } : { success: false };
    },
    create: async (data) => {
        const db = ensureDB();
        const user = getUser();
        const order = {
            id: Date.now(),
            user_id: user?.id,
            reference: 'SX-' + String((db.orders || []).length + 1).padStart(3, '0'),
            status: 'PENDING',
            total: data.total || 0,
            payment_method: data.payment_method || 'WALLET',
            created_at: new Date().toISOString().split('T')[0],
            items: data.items || db.cart || [],
            tracking: [{ status: 'PENDING', label: 'Commande passée', date: new Date().toISOString().split('T')[0] }]
        };
        db.orders = db.orders || [];
        db.orders.push(order);
        db.cart = [];
        saveDB(db);
        return { success: true, data: { commande: order } };
    },
    tracking: async (id) => {
        const db = ensureDB();
        const order = (db.orders || []).find(o => o.id == id);
        return { success: true, data: { tracking: order?.tracking || [] } };
    }
};

export const Wallet = {
    get: async () => {
        const user = getUser();
        const wallet = JSON.parse(localStorage.getItem('wallet') || '{"balance": 10000}');
        return { success: true, data: { wallet: { balance: wallet.balance || 10000, transactions: [] } } };
    },
    recharge: async (amount) => {
        const wallet = JSON.parse(localStorage.getItem('wallet') || '{"balance": 0}');
        wallet.balance = (wallet.balance || 0) + amount;
        localStorage.setItem('wallet', JSON.stringify(wallet));
        return { success: true, data: { wallet } };
    },
    transactions: async () => {
        return { success: true, data: { transactions: [] } };
    }
};

export const Notifications = {
    list: async () => {
        const db = ensureDB();
        const user = getUser();
        const notifs = user ? (db.notifications || []).filter(n => n.user_id == user?.id) : [];
        return { success: true, data: { notifications: notifs } };
    },
    poll: async () => {
        const db = ensureDB();
        const user = getUser();
        const unread = user ? (db.notifications || []).filter(n => n.user_id == user?.id && !n.read).length : 0;
        return { success: true, data: { unread } };
    },
    markRead: async (id) => {
        const db = ensureDB();
        const n = (db.notifications || []).find(n => n.id == id);
        if (n) { n.read = true; saveDB(db); }
        return { success: true };
    },
    markAllRead: async () => {
        const db = ensureDB();
        const user = getUser();
        (db.notifications || []).filter(n => n.user_id == user?.id).forEach(n => n.read = true);
        saveDB(db);
        return { success: true };
    }
};

export const Admin = {
    dashboard: async () => {
        const db = ensureDB();
        return { success: true, data: { stats: { users: 2, products: db.products.length, orders: db.orders.length, revenue: 125000 } } };
    },
    users: async () => {
        return { success: true, data: { users: [] } };
    },
    products: async () => {
        const db = ensureDB();
        return { success: true, data: { products: db.products } };
    },
    createProduct: async (data) => {
        const db = ensureDB();
        const product = { id: Date.now(), ...data };
        db.products.push(product);
        saveDB(db);
        return { success: true, data: { product } };
    },
    updateProduct: async (id, data) => {
        const db = ensureDB();
        const idx = db.products.findIndex(p => p.id == id);
        if (idx > -1) { db.products[idx] = { ...db.products[idx], ...data }; saveDB(db); }
        return { success: true };
    },
    deleteProduct: async (id) => {
        const db = ensureDB();
        db.products = db.products.filter(p => p.id != id);
        saveDB(db);
        return { success: true };
    },
    orders: async () => {
        const db = ensureDB();
        return { success: true, data: { orders: db.orders || [] } };
    },
    updateOrderStatus: async (id, status) => {
        const db = ensureDB();
        const order = (db.orders || []).find(o => o.id == id);
        if (order) { order.status = status; saveDB(db); }
        return { success: true };
    },
    analytics: async () => {
        const db = ensureDB();
        return { success: true, data: { revenue: { total: 125000, monthly: [10000, 12000, 15000, 18000, 22000, 25000, 23000] }, orders: { total: db.orders.length, pending: 0, delivered: 1 } } };
    },
    fraudAlerts: async () => {
        return { success: true, data: { alerts: [] } };
    }
};
