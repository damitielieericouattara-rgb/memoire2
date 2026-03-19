/**
 * SneakX — API Statique (localStorage)
 * Remplace les appels PHP par des opérations localStorage
 * Compatible avec tous les imports existants
 */

/* ── HELPERS INTERNES ── */
const DB_KEY = 'sneakx_data';
const USERS_KEY = 'sneakx_users';

function getDB() {
    try { return JSON.parse(localStorage.getItem(DB_KEY) || 'null'); } catch { return null; }
}
function saveDB(db) { localStorage.setItem(DB_KEY, JSON.stringify(db)); }
function getUsers() {
    try {
        const s = localStorage.getItem(USERS_KEY);
        if (s) return JSON.parse(s);
    } catch {}
    return getDefaultUsers();
}
function saveUsers(u) { localStorage.setItem(USERS_KEY, JSON.stringify(u)); }
function getSession() {
    try { return JSON.parse(localStorage.getItem('user') || 'null'); } catch { return null; }
}

function getDefaultUsers() {
    return [
        { id:1, nom:'SneakX', prenom:'Admin', email:'admin@sneakx.ci', password:'Admin@123', role:'admin', telephone:'+225 07 08 09 10', wallet:50000, avatar:null, is_active:1, created_at:'2024-01-01' },
        { id:2, nom:'Martin', prenom:'Jean',  email:'eric@test.ci',    password:'Admin@123', role:'client',telephone:'+225 01 02 03 04', wallet:25000, avatar:null, is_active:1, created_at:'2024-02-15' }
    ];
}

const IMGS = [
    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80',
    'https://images.unsplash.com/photo-1608231387042-66d1773070a5?w=400&q=80',
    'https://images.unsplash.com/photo-1600269452121-4f2416e55c28?w=400&q=80',
    'https://images.unsplash.com/photo-1539185441755-769473a23570?w=400&q=80',
    'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=400&q=80',
    'https://images.unsplash.com/photo-1587563871167-1ee9c731aefb?w=400&q=80',
    'https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=400&q=80',
    'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=400&q=80',
    'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=400&q=80',
    'https://images.unsplash.com/photo-1597248881519-db089b15f9be?w=400&q=80',
    'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=400&q=80',
    'https://images.unsplash.com/photo-1518002171953-a080ee817e1f?w=400&q=80'
];

function ensureDB() {
    let db = getDB();
    if (!db || !db.products || db.products.length === 0) {
        db = {
            products: [
                { id:1,  nom:'Nike Air Max 270',        marque:'Nike',        prix:45000,  prix_promo:38000,  stock:15, note_moyenne:4.8, nb_avis:128, slug:'nike-air-max-270',       is_featured:1, images:[IMGS[0]],  tailles:[40,41,42,43,44,45], couleurs:['Noir','Blanc','Rouge'] },
                { id:2,  nom:'Adidas Ultra Boost 22',   marque:'Adidas',      prix:52000,  prix_promo:null,   stock:8,  note_moyenne:4.9, nb_avis:89,  slug:'adidas-ultra-boost-22',  is_featured:1, images:[IMGS[1]],  tailles:[40,41,42,43,44,45], couleurs:['Bleu','Noir','Blanc'] },
                { id:3,  nom:'Air Jordan 1 Retro High', marque:'Jordan',      prix:75000,  prix_promo:null,   stock:5,  note_moyenne:4.9, nb_avis:256, slug:'air-jordan-1-retro',      is_featured:1, images:[IMGS[4]],  tailles:[40,41,42,43,44,45], couleurs:['Rouge/Noir','Blanc/Noir'] },
                { id:4,  nom:'New Balance 574',         marque:'New Balance', prix:38000,  prix_promo:32000,  stock:12, note_moyenne:4.3, nb_avis:67,  slug:'new-balance-574',         is_featured:0, images:[IMGS[2]],  tailles:[39,40,41,42,43,44], couleurs:['Gris','Bleu','Vert'] },
                { id:5,  nom:'Puma RS-X³',              marque:'Puma',        prix:35000,  prix_promo:28000,  stock:10, note_moyenne:4.1, nb_avis:34,  slug:'puma-rs-x3',              is_featured:0, images:[IMGS[5]],  tailles:[40,41,42,43,44],    couleurs:['Noir','Blanc','Rose'] },
                { id:6,  nom:'Nike Dunk Low Panda',     marque:'Nike',        prix:48000,  prix_promo:null,   stock:3,  note_moyenne:4.8, nb_avis:189, slug:'nike-dunk-low-panda',     is_featured:1, images:[IMGS[3]],  tailles:[38,39,40,41,42,43,44,45], couleurs:['Noir/Blanc'] },
                { id:7,  nom:'Adidas Stan Smith',       marque:'Adidas',      prix:32000,  prix_promo:null,   stock:20, note_moyenne:4.4, nb_avis:112, slug:'adidas-stan-smith',        is_featured:0, images:[IMGS[8]],  tailles:[38,39,40,41,42,43,44,45], couleurs:['Blanc/Vert','Blanc/Bleu'] },
                { id:8,  nom:'Reebok Classic Leather',  marque:'Reebok',      prix:29000,  prix_promo:22000,  stock:18, note_moyenne:4.2, nb_avis:55,  slug:'reebok-classic',          is_featured:0, images:[IMGS[9]],  tailles:[39,40,41,42,43,44], couleurs:['Blanc','Noir'] },
                { id:9,  nom:'Asics Gel-Kayano 29',     marque:'ASICS',       prix:68000,  prix_promo:null,   stock:7,  note_moyenne:4.7, nb_avis:93,  slug:'asics-gel-kayano-29',     is_featured:1, images:[IMGS[6]],  tailles:[40,41,42,43,44,45], couleurs:['Bleu','Noir','Gris'] },
                { id:10, nom:'Converse Chuck Taylor',   marque:'Converse',    prix:25000,  prix_promo:null,   stock:25, note_moyenne:4.5, nb_avis:310, slug:'converse-chuck-taylor',   is_featured:0, images:[IMGS[10]], tailles:[38,39,40,41,42,43,44,45], couleurs:['Noir','Blanc','Rouge'] },
                { id:11, nom:'Vans Old Skool',          marque:'Vans',        prix:28000,  prix_promo:24000,  stock:14, note_moyenne:4.6, nb_avis:178, slug:'vans-old-skool',          is_featured:0, images:[IMGS[11]], tailles:[38,39,40,41,42,43,44,45], couleurs:['Noir/Blanc','Marine/Blanc'] },
                { id:12, nom:'Under Armour HOVR Sonic', marque:'Under Armour',prix:55000,  prix_promo:null,   stock:9,  note_moyenne:4.5, nb_avis:67,  slug:'ua-hovr-sonic',           is_featured:0, images:[IMGS[7]],  tailles:[40,41,42,43,44,45], couleurs:['Noir','Blanc','Gris'] },
            ],
            cart: [],
            orders: [
                { id:1, user_id:2, reference:'SX-001', statut:'livré', total:75000, mode_paiement:'wallet', created_at:'2024-02-20', items:[{product_id:3,nom:'Air Jordan 1 Retro High',prix:75000,quantite:1,taille:42,couleur:'Rouge/Noir',image:IMGS[4]}], suivi:[{statut:'en_attente',label:'Commande passée',date:'2024-02-20'},{statut:'confirmé',label:'Confirmée',date:'2024-02-21'},{statut:'expédié',label:'Expédiée',date:'2024-02-23'},{statut:'livré',label:'Livrée',date:'2024-02-25'}] }
            ],
            wishlist: [],
            notifications: [
                { id:1, user_id:2, titre:'Bienvenue sur SneakX !', message:'Profitez de -10% avec le code BIENVENUE10 sur votre première commande.', type:'info', lu:false, created_at:'2024-02-15' },
                { id:2, user_id:2, titre:'Commande confirmée', message:'Votre commande SX-001 a été confirmée et sera livrée dans 3-5 jours.', type:'success', lu:false, created_at:'2024-02-21' }
            ],
            reviews: [],
            fraud_alerts: [
                { id:1, type:'multiple_orders', user_id:3, user_email:'suspect@test.com', detail:'5 commandes en 10 minutes depuis la même IP', statut:'open', created_at:'2024-03-01' },
                { id:2, type:'payment_failed', user_id:4, user_email:'autre@test.com', detail:'Tentatives répétées avec cartes différentes', statut:'reviewing', created_at:'2024-03-02' }
            ]
        };
        saveDB(db);
    }
    // Ensure default users exist
    if (!localStorage.getItem(USERS_KEY)) {
        saveUsers(getDefaultUsers());
    }
    return db;
}

function getUserById(id) {
    return getUsers().find(u => u.id == id) || null;
}
function getCurrentUser() {
    const s = getSession();
    if (!s) return null;
    return getUserById(s.id) || s;
}

/* ── AUTH ── */
const auth = {
    login: async (email, password) => {
        const users = getUsers();
        const user = users.find(u => u.email.toLowerCase() === email.toLowerCase() && u.password === password);
        if (!user) return { success: false, error: 'Email ou mot de passe incorrect.' };
        const token = btoa(JSON.stringify({ id: user.id, email: user.email, role: user.role, exp: Math.floor(Date.now()/1000) + 86400*30 }));
        const { password: _pw, ...safeUser } = user;
        localStorage.setItem('access_token', token);
        localStorage.setItem('user', JSON.stringify(safeUser));
        localStorage.setItem('wallet', JSON.stringify({ balance: user.wallet || 5000, transactions: [] }));
        return { success: true, token, user: safeUser };
    },
    register: async (data) => {
        const users = getUsers();
        if (users.find(u => u.email.toLowerCase() === (data.email||data.prenom+'@test.ci').toLowerCase())) {
            return { success: false, error: 'Un compte existe déjà avec cet email.' };
        }
        const newUser = { id: Date.now(), nom: data.nom||'', prenom: data.prenom||'', email: data.email||'', password: data.password||'', telephone: data.telephone||null, role:'client', wallet:5000, avatar:null, is_active:1, created_at: new Date().toISOString().split('T')[0] };
        users.push(newUser);
        saveUsers(users);
        return auth.login(newUser.email, newUser.password);
    },
    me: async () => {
        const user = getCurrentUser();
        if (!user) return { success: false, error: 'Non connecté' };
        const db = ensureDB();
        const orders = (db.orders || []).filter(o => o.user_id == user.id);
        const wishlist = (db.wishlist || []).filter(w => w.user_id == user.id);
        return { success: true, user, stats: { commandes: orders.length, wishlist: wishlist.length } };
    },
    updateProfile: async (data) => {
        const user = getCurrentUser();
        if (!user) return { success: false };
        const users = getUsers();
        const idx = users.findIndex(u => u.id == user.id);
        if (idx < 0) return { success: false };
        Object.assign(users[idx], data);
        saveUsers(users);
        const { password: _, ...safe } = users[idx];
        localStorage.setItem('user', JSON.stringify(safe));
        return { success: true, user: safe };
    },
    logout: async () => {
        localStorage.removeItem('access_token');
        localStorage.removeItem('user');
        localStorage.removeItem('wallet');
        return { success: true };
    }
};

/* ── PRODUITS ── */
const produits = {
    catalogue: async (filters = {}, page = 1) => {
        const db = ensureDB();
        let items = db.products.filter(p => p.stock > 0 || !filters.in_stock);
        if (filters.q) { const q = filters.q.toLowerCase(); items = items.filter(p => (p.nom||'').toLowerCase().includes(q) || (p.marque||'').toLowerCase().includes(q)); }
        if (filters.marque) items = items.filter(p => (p.marque||'').toLowerCase() === filters.marque.toLowerCase());
        if (filters.category_id) items = items.filter(p => p.category_id == filters.category_id);
        if (filters.promo_only || filters.promo) items = items.filter(p => p.prix_promo && p.prix_promo < p.prix);
        if (filters.sort === 'prix_asc' || (filters.sort === 'prix' && filters.order === 'ASC')) items.sort((a,b)=>(a.prix_promo||a.prix)-(b.prix_promo||b.prix));
        else if (filters.sort === 'prix_desc' || (filters.sort === 'prix' && filters.order === 'DESC')) items.sort((a,b)=>(b.prix_promo||b.prix)-(a.prix_promo||a.prix));
        else if (filters.sort === 'note' || filters.sort === 'average_rating') items.sort((a,b)=>(b.note_moyenne||0)-(a.note_moyenne||0));
        else items.sort((a,b) => b.id - a.id);
        const perPage = filters.per_page || 20;
        const total = items.length;
        const paged = items.slice((page-1)*perPage, page*perPage);
        // Normalize fields
        const normalized = paged.map(p => ({
            ...p, name: p.nom, brand: p.marque, price: p.prix, promo_price: p.prix_promo,
            average_rating: p.note_moyenne, reviews_count: p.nb_avis,
            image_url: (p.images||[])[0] || IMGS[p.id % IMGS.length],
            image_principale: (p.images||[])[0] || IMGS[p.id % IMGS.length]
        }));
        return { success:true, items: normalized, produits: normalized, total, current_page: page, last_page: Math.ceil(total/perPage) };
    },
    search: async (q) => {
        const db = ensureDB();
        const r = db.products.filter(p => (p.nom||'').toLowerCase().includes(q.toLowerCase()) || (p.marque||'').toLowerCase().includes(q.toLowerCase()));
        const norm = r.map(p => ({ ...p, nom: p.nom, prix: p.prix, image: (p.images||[])[0]||IMGS[p.id%IMGS.length], slug: p.slug }));
        return { success:true, produits: norm };
    },
    detail: async (slugOrId) => {
        const db = ensureDB();
        const p = db.products.find(p => p.slug === slugOrId || p.id == slugOrId);
        if (!p) return { success:false, error:'Produit introuvable' };
        const prod = { ...p, name:p.nom, brand:p.marque, price:p.prix, promo_price:p.prix_promo, images: p.images||[IMGS[p.id%IMGS.length]], image_url:(p.images||[])[0]||IMGS[p.id%IMGS.length] };
        return { success:true, produit: prod, product: prod };
    },
    tendances: async () => {
        const db = ensureDB();
        const top = db.products.sort((a,b)=>(b.note_moyenne||0)-(a.note_moyenne||0)).slice(0,6);
        const norm = top.map(p => ({ ...p, name:p.nom, brand:p.marque, price:p.prix, promo_price:p.prix_promo, image_url:(p.images||[])[0]||IMGS[p.id%IMGS.length] }));
        return { success:true, produits: norm };
    },
    featured: async () => {
        const db = ensureDB();
        const feat = db.products.filter(p => p.is_featured).slice(0,8);
        const norm = feat.map(p => ({ ...p, name:p.nom, brand:p.marque, price:p.prix, promo_price:p.prix_promo, image_url:(p.images||[])[0]||IMGS[p.id%IMGS.length] }));
        return { success:true, produits: norm };
    },
    categories: async () => {
        return { success:true, categories:[
            {id:1, nom:'Nike', slug:'nike'}, {id:2, nom:'Adidas', slug:'adidas'},
            {id:3, nom:'Jordan', slug:'jordan'}, {id:4, nom:'New Balance', slug:'new-balance'},
            {id:5, nom:'Puma', slug:'puma'}, {id:6, nom:'Converse', slug:'converse'},
            {id:7, nom:'Vans', slug:'vans'}, {id:8, nom:'Reebok', slug:'reebok'}
        ]};
    },
    marques: async () => {
        const db = ensureDB();
        const marques = [...new Set(db.products.map(p => p.marque))].filter(Boolean);
        return { success:true, marques };
    },
    stockAlert: async () => {
        const db = ensureDB();
        const low = db.products.filter(p => p.stock <= 5);
        return { success:true, products: low };
    }
};

/* ── PANIER ── */
const panier = {
    get: async () => {
        const db = ensureDB();
        const user = getCurrentUser();
        const items = (db.cart||[]).filter(i => !user || i.user_id == user.id || !i.user_id);
        const total = items.reduce((t,i) => t + (i.prix||i.price||0) * (i.quantite||i.quantity||1), 0);
        return { success:true, items, total, count: items.length };
    },
    add: async (product_id, quantite=1, taille='', couleur='') => {
        const db = ensureDB();
        const user = getCurrentUser();
        const product = db.products.find(p => p.id == product_id);
        if (!product) return { success:false, error:'Produit introuvable' };
        db.cart = db.cart||[];
        const existing = db.cart.find(i => i.product_id == product_id && i.taille === taille && (!user || i.user_id == user?.id));
        if (existing) existing.quantite = (existing.quantite||1) + (quantite||1);
        else db.cart.push({ id:Date.now(), product_id, user_id:user?.id||null, nom:product.nom, prix:(product.prix_promo||product.prix), image:(product.images||[])[0]||IMGS[product.id%IMGS.length], quantite:quantite||1, taille:taille||'', couleur:couleur||'', stock:product.stock });
        saveDB(db);
        return { success:true };
    },
    update: async (cart_id, quantite) => {
        const db = ensureDB();
        const item = (db.cart||[]).find(i => i.id == cart_id);
        if (item) { item.quantite = quantite; saveDB(db); }
        return { success:true };
    },
    remove: async (cart_id) => {
        const db = ensureDB();
        db.cart = (db.cart||[]).filter(i => i.id != cart_id);
        saveDB(db);
        return { success:true };
    },
    clear: async () => {
        const db = ensureDB();
        const user = getCurrentUser();
        db.cart = user ? (db.cart||[]).filter(i => i.user_id != user.id) : [];
        saveDB(db);
        return { success:true };
    },
    promo: async (code, total) => {
        const promos = { 'BIENVENUE10':0.10, 'SNEAKX15':0.15, 'PROMO20':0.20 };
        const discount = promos[code.toUpperCase()];
        if (!discount) return { success:false, error:'Code promo invalide' };
        return { success:true, discount: Math.round(total * discount), taux: discount, code };
    },
    count: async () => {
        const db = ensureDB();
        const user = getCurrentUser();
        const items = (db.cart||[]).filter(i => !user || i.user_id == user.id || !i.user_id);
        return items.reduce((t,i) => t + (i.quantite||1), 0);
    }
};

/* ── COMMANDES ── */
const commandes = {
    liste: async (page=1) => {
        const db = ensureDB();
        const user = getCurrentUser();
        const orders = (db.orders||[]).filter(o => !user || o.user_id == user.id).sort((a,b) => b.id - a.id);
        return { success:true, commandes: orders, total: orders.length };
    },
    detail: async (id) => {
        const db = ensureDB();
        const order = (db.orders||[]).find(o => o.id == id);
        return order ? { success:true, commande: order } : { success:false, error:'Commande introuvable' };
    },
    create: async (data) => {
        const db = ensureDB();
        const user = getCurrentUser();
        const userCart = (db.cart||[]).filter(i => !user || i.user_id == user?.id || !i.user_id);
        const items = data.items || userCart;
        const order = {
            id: Date.now(),
            user_id: user?.id || null,
            reference: 'SX-' + String((db.orders||[]).length + 1).padStart(3,'0'),
            statut: 'en_attente',
            total: data.total || items.reduce((t,i) => t + (i.prix||i.price||0)*(i.quantite||i.quantity||1), 0),
            mode_paiement: data.mode_paiement || data.payment_method || 'wallet',
            adresse_livraison: data.adresse || data.adresse_livraison || '',
            created_at: new Date().toISOString().split('T')[0],
            items,
            suivi: [{ statut:'en_attente', label:'Commande passée', date: new Date().toISOString().split('T')[0] }]
        };
        db.orders = db.orders || [];
        db.orders.push(order);
        // Clear cart
        if (user) db.cart = (db.cart||[]).filter(i => i.user_id != user.id);
        else db.cart = [];
        // Deduct wallet if paid by wallet
        if (order.mode_paiement === 'wallet') {
            const wallet = JSON.parse(localStorage.getItem('wallet')||'{"balance":0}');
            wallet.balance = Math.max(0, (wallet.balance||0) - order.total);
            wallet.transactions = wallet.transactions || [];
            wallet.transactions.unshift({ id:Date.now(), type:'debit', montant:order.total, description:'Commande '+order.reference, date:order.created_at });
            localStorage.setItem('wallet', JSON.stringify(wallet));
        }
        saveDB(db);
        return { success:true, commande: order };
    },
    annuler: async (id) => {
        const db = ensureDB();
        const order = (db.orders||[]).find(o => o.id == id);
        if (order) { order.statut = 'annulé'; saveDB(db); }
        return { success:true };
    },
    suivi: async (id) => {
        const db = ensureDB();
        const order = (db.orders||[]).find(o => o.id == id);
        return { success:true, suivi: order?.suivi || [], commande: order };
    }
};

/* ── WALLET ── */
const wallet_api = {
    get: async () => {
        const w = JSON.parse(localStorage.getItem('wallet')||'{"balance":5000,"transactions":[]}');
        return { success:true, wallet: { balance: w.balance||5000, transactions: w.transactions||[] } };
    },
    recharger: async (montant, methode='wave') => {
        const w = JSON.parse(localStorage.getItem('wallet')||'{"balance":0,"transactions":[]}');
        w.balance = (w.balance||0) + montant;
        w.transactions = w.transactions || [];
        w.transactions.unshift({ id:Date.now(), type:'credit', montant, description:'Recharge '+methode.toUpperCase(), date:new Date().toISOString().split('T')[0] });
        localStorage.setItem('wallet', JSON.stringify(w));
        return { success:true, wallet: w };
    },
    transactions: async () => {
        const w = JSON.parse(localStorage.getItem('wallet')||'{"balance":0,"transactions":[]}');
        return { success:true, transactions: w.transactions||[] };
    }
};

/* ── WISHLIST ── */
const wishlist = {
    liste: async () => {
        const db = ensureDB();
        const user = getCurrentUser();
        const items = (db.wishlist||[]).filter(w => w.user_id == user?.id);
        const products = items.map(w => {
            const p = db.products.find(p => p.id == w.product_id);
            return p ? { ...p, name:p.nom, brand:p.marque, price:p.prix, promo_price:p.prix_promo, image_url:(p.images||[])[0]||IMGS[p.id%IMGS.length] } : null;
        }).filter(Boolean);
        return { success:true, wishlist: products, items: products };
    },
    toggle: async (product_id) => {
        const db = ensureDB();
        const user = getCurrentUser();
        if (!user) return { success:false, error:'Connexion requise' };
        db.wishlist = db.wishlist||[];
        const idx = db.wishlist.findIndex(w => w.product_id == product_id && w.user_id == user.id);
        let action;
        if (idx > -1) { db.wishlist.splice(idx,1); action='removed'; }
        else { db.wishlist.push({ id:Date.now(), user_id:user.id, product_id }); action='added'; }
        saveDB(db);
        return { success:true, action };
    }
};

/* ── NOTIFICATIONS ── */
const notifications = {
    liste: async () => {
        const db = ensureDB();
        const user = getCurrentUser();
        const notifs = (db.notifications||[]).filter(n => n.user_id == user?.id||n.user_id==2).sort((a,b)=>b.id-a.id);
        return { success:true, notifications: notifs };
    },
    read: async (id) => {
        const db = ensureDB();
        if (id) { const n=(db.notifications||[]).find(n=>n.id==id); if(n) n.lu=true; }
        else (db.notifications||[]).forEach(n=>n.lu=true);
        saveDB(db);
        return { success:true };
    },
    count: async () => {
        const db = ensureDB();
        const user = getCurrentUser();
        const unread = (db.notifications||[]).filter(n => (n.user_id==user?.id||n.user_id==2) && !n.lu).length;
        return { success:true, count: unread };
    }
};

/* ── LIVRAISON ── */
const livraison = {
    frais: async (total) => {
        const frais = total >= 100000 ? 0 : 2500;
        return { success:true, frais, gratuit: frais === 0 };
    },
    track: async (order_id) => {
        const db = ensureDB();
        const order = (db.orders||[]).find(o=>o.id==order_id);
        return { success:true, suivi: order?.suivi||[], statut: order?.statut||'en_attente' };
    },
    geocode: async (adresse) => {
        return { success:true, lat:5.3599, lng:-4.0082, adresse };
    },
    carteAdmin: async () => {
        return { success:true, livraisons:[] };
    }
};

/* ── PAIEMENT ── */
const paiement = {
    initier: async (order_id, methode, montant) => {
        return { success:true, order_id, methode, montant, status:'pending', reference:'PAY-'+Date.now() };
    },
    simuler: async (order_id) => {
        const db = ensureDB();
        const order = (db.orders||[]).find(o=>o.id==order_id);
        if (order) {
            order.statut = 'confirmé';
            order.suivi = order.suivi||[];
            order.suivi.push({ statut:'confirmé', label:'Paiement confirmé', date:new Date().toISOString().split('T')[0] });
            saveDB(db);
        }
        return { success:true, statut:'confirmé' };
    }
};

/* ── ADMIN ── */
const admin = {
    dashboard: async () => {
        const db = ensureDB();
        const users = getUsers();
        const revenue = (db.orders||[]).reduce((t,o)=>t+(o.total||0),0);
        return { success:true,
            stats: { utilisateurs: users.length, produits: db.products.length, commandes: (db.orders||[]).length, revenus: revenue },
            recent_orders: (db.orders||[]).slice(-5).reverse(),
            stock_alert: db.products.filter(p=>p.stock<=5)
        };
    },
    analytics: async (jours=30) => {
        const db = ensureDB();
        const labels = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul'];
        const revenue = [10000,12000,15000,18000,22000,25000,23000].map(v=>v+Math.floor(Math.random()*3000));
        const orders = [12,15,18,22,28,32,30];
        return { success:true, labels, revenue, orders, total_revenue: revenue.reduce((a,b)=>a+b,0), total_orders: (db.orders||[]).length };
    },
    users: async (filters={}, page=1) => {
        let users = getUsers();
        if (filters.q) { const q=filters.q.toLowerCase(); users=users.filter(u=>(u.nom+u.prenom+u.email).toLowerCase().includes(q)); }
        if (filters.role) users=users.filter(u=>u.role===filters.role);
        return { success:true, users, total:users.length };
    },
    rapport: async () => {
        return { success:true, rapport:{} };
    },
    updateOrderStatus: async (id, statut) => {
        const db = ensureDB();
        const order = (db.orders||[]).find(o=>o.id==id);
        if (order) { order.statut=statut; saveDB(db); }
        return { success:true };
    },
    stockAlert: async () => {
        const db = ensureDB();
        return { success:true, products: db.products.filter(p=>p.stock<=5) };
    },
    fraudAlerts: async () => {
        const db = ensureDB();
        return { success:true, alerts: db.fraud_alerts||[] };
    },
    updateFraud: async (id, statut) => {
        const db = ensureDB();
        const alert = (db.fraud_alerts||[]).find(a=>a.id==id);
        if (alert) { alert.statut=statut; saveDB(db); }
        return { success:true };
    }
};

/* ── get / post / put / delete helpers (compat cart.js) ── */
async function get(url, params={}) {
    // Route requests to static functions
    return { success:false, data:{} };
}
async function post(url, body={}) {
    return { success:false, data:{} };
}
async function put(url, body={}) { return { success:false }; }
async function del(url) { return { success:false }; }

const API = {
    get, post, put, delete: del,
    auth,
    produits,
    panier,
    commandes,
    wallet: wallet_api,
    wishlist,
    notifications,
    livraison,
    paiement,
    admin
};

// Initialize DB on load
ensureDB();

export default API;
