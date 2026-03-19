// 📊 DONNÉES DYNAMIQUES SNEAKX - Frontend 100% autonome
// Simule une base de données en JavaScript localStorage

class SneakXData {
    constructor() {
        this.initializeData();
        this.loadFromStorage();
    }

    // ── INITIALISATION DES DONNÉES ───────────────────────────────────────
    initializeData() {
        this.defaultData = {
            // Utilisateurs
            users: [
                {
                    id: 1,
                    first_name: "Admin",
                    name: "SneakX",
                    email: "admin@sneakx.com",
                    password: "Admin1234!",
                    phone: "+225 07 08 09 10",
                    role: "ADMIN",
                    created_at: "2024-01-01",
                    wallet: 50000,
                    addresses: [
                        {
                            id: 1,
                            street: "123 rue de la République",
                            city: "Abidjan",
                            postal: "00225",
                            country: "Côte d'Ivoire",
                            default: true
                        }
                    ]
                },
                {
                    id: 2,
                    first_name: "Jean",
                    name: "Martin",
                    email: "jean.martin@email.com",
                    password: "password123",
                    phone: "+225 01 02 03 04",
                    role: "USER",
                    created_at: "2024-02-15",
                    wallet: 25000,
                    addresses: [
                        {
                            id: 2,
                            street: "45 avenue des Champs",
                            city: "Abidjan",
                            postal: "00225",
                            country: "Côte d'Ivoire",
                            default: true
                        }
                    ]
                }
            ],

            // Catégories
            categories: [
                { id: 1, name: "Nike", slug: "nike", icon: "fa-check", count: 15 },
                { id: 2, name: "Adidas", slug: "adidas", icon: "fa-three-stripes", count: 12 },
                { id: 3, name: "Jordan", slug: "jordan", icon: "fa-jordan", count: 8 },
                { id: 4, name: "New Balance", slug: "new-balance", icon: "fa-nb", count: 6 },
                { id: 5, name: "Puma", slug: "puma", icon: "fa-puma", count: 4 }
            ],

            // Produits
            products: [
                {
                    id: 1,
                    name: "Nike Air Max 270",
                    slug: "nike-air-max-270",
                    brand: "Nike",
                    category_id: 1,
                    price: 45000,
                    original_price: 55000,
                    description: "La Nike Air Max 270 offre un confort exceptionnel avec son unité Air Max visible.",
                    image: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80",
                    images: [
                        "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80",
                        "https://images.unsplash.com/photo-1556905055-8f358a7a797e?w=400&q=80",
                        "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=400&q=80"
                    ],
                    sizes: [40, 41, 42, 43, 44, 45],
                    colors: ["Noir", "Blanc", "Rouge"],
                    stock: 15,
                    rating: 4.5,
                    reviews: 128,
                    featured: true,
                    new: false,
                    discount: 18,
                    tags: ["running", "comfort", "air-max"]
                },
                {
                    id: 2,
                    name: "Adidas Ultra Boost 22",
                    slug: "adidas-ultra-boost-22",
                    brand: "Adidas",
                    category_id: 2,
                    price: 52000,
                    original_price: 60000,
                    description: "L'Ultra Boost 22 combine technologie Boost et design moderne.",
                    image: "https://images.unsplash.com/photo-1556905055-8f358a7a797e?w=400&q=80",
                    images: [
                        "https://images.unsplash.com/photo-1556905055-8f358a7a797e?w=400&q=80",
                        "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=400&q=80"
                    ],
                    sizes: [40, 41, 42, 43, 44, 45],
                    colors: ["Bleu marine", "Noir", "Blanc"],
                    stock: 8,
                    rating: 4.7,
                    reviews: 89,
                    featured: true,
                    new: true,
                    discount: 13,
                    tags: ["running", "boost", "comfort"]
                },
                {
                    id: 3,
                    name: "Air Jordan 1 Retro High",
                    slug: "air-jordan-1-retro-high",
                    brand: "Jordan",
                    category_id: 3,
                    price: 75000,
                    original_price: 85000,
                    description: "La Jordan 1 Retro High est un classique intemporel du basketball.",
                    image: "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&q=80",
                    images: [
                        "https://images.unsplash.com/photo-1549298916-b41d501d3772?w=400&q=80",
                        "https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=400&q=80"
                    ],
                    sizes: [40, 41, 42, 43, 44, 45],
                    colors: ["Rouge/Noir", "Blanc/Noir", "Bleu"],
                    stock: 5,
                    rating: 4.9,
                    reviews: 256,
                    featured: true,
                    new: false,
                    discount: 12,
                    tags: ["jordan", "retro", "basketball", "classic"]
                },
                {
                    id: 4,
                    name: "New Balance 574",
                    slug: "new-balance-574",
                    brand: "New Balance",
                    category_id: 4,
                    price: 38000,
                    original_price: 42000,
                    description: "La 574 combine style rétro et confort moderne.",
                    image: "https://images.unsplash.com/photo-1608231387022-66b8c6c0445c?w=400&q=80",
                    images: [
                        "https://images.unsplash.com/photo-1608231387022-66b8c6c0445c?w=400&q=80"
                    ],
                    sizes: [39, 40, 41, 42, 43, 44],
                    colors: ["Gris", "Bleu", "Vert"],
                    stock: 12,
                    rating: 4.3,
                    reviews: 67,
                    featured: false,
                    new: false,
                    discount: 10,
                    tags: ["lifestyle", "retro", "comfort"]
                },
                {
                    id: 5,
                    name: "Puma RS-X³",
                    slug: "puma-rs-x3",
                    brand: "Puma",
                    category_id: 5,
                    price: 35000,
                    original_price: 40000,
                    description: "La RS-X³ offre un style audacieux et un confort optimal.",
                    image: "https://images.unsplash.com/photo-1595955886196-6a3a2b5c6a5b?w=400&q=80",
                    images: [
                        "https://images.unsplash.com/photo-1595955886196-6a3a2b5c6a5b?w=400&q=80"
                    ],
                    sizes: [40, 41, 42, 43, 44],
                    colors: ["Noir", "Blanc", "Rose"],
                    stock: 10,
                    rating: 4.1,
                    reviews: 34,
                    featured: false,
                    new: true,
                    discount: 13,
                    tags: ["lifestyle", "rs-x", "comfort"]
                },
                {
                    id: 6,
                    name: "Nike Dunk Low Panda",
                    slug: "nike-dunk-low-panda",
                    brand: "Nike",
                    category_id: 1,
                    price: 48000,
                    original_price: 55000,
                    description: "La Dunk Low Panda est un phénomène dans la culture streetwear.",
                    image: "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80",
                    images: [
                        "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&q=80",
                        "https://images.unsplash.com/photo-1556905055-8f358a7a797e?w=400&q=80"
                    ],
                    sizes: [38, 39, 40, 41, 42, 43, 44, 45],
                    colors: ["Noir/Blanc"],
                    stock: 3,
                    rating: 4.8,
                    reviews: 189,
                    featured: true,
                    new: false,
                    discount: 13,
                    tags: ["dunk", "panda", "streetwear", "hype"]
                }
            ],

            // Commandes
            orders: [
                {
                    id: 1,
                    user_id: 2,
                    status: "DELIVERED",
                    total: 97000,
                    payment_method: "WALLET",
                    delivery_address: "123 rue de la République, Abidjan",
                    created_at: "2024-02-20",
                    delivered_at: "2024-02-25",
                    items: [
                        {
                            product_id: 1,
                            name: "Nike Air Max 270",
                            price: 45000,
                            quantity: 1,
                            size: 42,
                            color: "Noir"
                        },
                        {
                            product_id: 2,
                            name: "Adidas Ultra Boost 22",
                            price: 52000,
                            quantity: 1,
                            size: 43,
                            color: "Bleu marine"
                        }
                    ]
                }
            ],

            // Panier
            cart: [],

            // Wishlist
            wishlist: [],

            // Notifications
            notifications: [
                {
                    id: 1,
                    user_id: 2,
                    title: "Bienvenue sur SneakX !",
                    message: "Profitez de -10% sur votre première commande avec le code BIENVENUE10",
                    type: "INFO",
                    read: false,
                    created_at: "2024-02-15"
                },
                {
                    id: 2,
                    user_id: 2,
                    title: "Nouveaux arrivages !",
                    message: "Découvrez les dernières Nike Air Max et Jordan",
                    type: "PROMO",
                    read: false,
                    created_at: "2024-02-20"
                }
            ]
        };
    }

    // ── GESTION LOCALSTORAGE ─────────────────────────────────────────────
    loadFromStorage() {
        const stored = localStorage.getItem('sneakx_data');
        if (stored) {
            this.data = { ...this.defaultData, ...JSON.parse(stored) };
        } else {
            this.data = { ...this.defaultData };
            this.saveToStorage();
        }
    }

    saveToStorage() {
        localStorage.setItem('sneakx_data', JSON.stringify(this.data));
    }

    // ── UTILITAIRES ───────────────────────────────────────────────────────
    getProducts(filters = {}) {
        let products = [...this.data.products];
        
        if (filters.category) {
            products = products.filter(p => p.category_id == filters.category);
        }
        
        if (filters.brand) {
            products = products.filter(p => p.brand.toLowerCase() === filters.brand.toLowerCase());
        }
        
        if (filters.search) {
            const search = filters.search.toLowerCase();
            products = products.filter(p => 
                p.name.toLowerCase().includes(search) ||
                p.description.toLowerCase().includes(search) ||
                p.brand.toLowerCase().includes(search)
            );
        }
        
        if (filters.featured) {
            products = products.filter(p => p.featured);
        }
        
        if (filters.new) {
            products = products.filter(p => p.new);
        }
        
        return products;
    }

    getProduct(id) {
        return this.data.products.find(p => p.id == id);
    }

    getCategories() {
        return this.data.categories;
    }

    getCategory(id) {
        return this.data.categories.find(c => c.id == id);
    }

    getUser(id) {
        return this.data.users.find(u => u.id == id);
    }

    getCurrentUser() {
        const userId = localStorage.getItem('sneakx_user_id');
        return userId ? this.getUser(userId) : null;
    }

    setCurrentUser(userId) {
        localStorage.setItem('sneakx_user_id', userId);
    }

    logout() {
        localStorage.removeItem('sneakx_user_id');
    }

    // ── PANIER ───────────────────────────────────────────────────────────
    addToCart(productId, quantity = 1, size = null, color = null) {
        const product = this.getProduct(productId);
        if (!product) return false;

        const existingItem = this.data.cart.find(item => 
            item.product_id === productId && item.size === size && item.color === color
        );

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            this.data.cart.push({
                id: Date.now(),
                product_id: productId,
                name: product.name,
                price: product.price,
                image: product.image,
                quantity,
                size,
                color
            });
        }

        this.saveToStorage();
        return true;
    }

    removeFromCart(itemId) {
        this.data.cart = this.data.cart.filter(item => item.id !== itemId);
        this.saveToStorage();
    }

    updateCartQuantity(itemId, quantity) {
        const item = this.data.cart.find(item => item.id === itemId);
        if (item) {
            item.quantity = Math.max(1, quantity);
            this.saveToStorage();
        }
    }

    getCart() {
        return this.data.cart;
    }

    getCartTotal() {
        return this.data.cart.reduce((total, item) => total + (item.price * item.quantity), 0);
    }

    getCartCount() {
        return this.data.cart.reduce((count, item) => count + item.quantity, 0);
    }

    clearCart() {
        this.data.cart = [];
        this.saveToStorage();
    }

    // ── WISHLIST ───────────────────────────────────────────────────────
    addToWishlist(productId) {
        if (!this.data.wishlist.find(id => id === productId)) {
            this.data.wishlist.push(productId);
            this.saveToStorage();
            return true;
        }
        return false;
    }

    removeFromWishlist(productId) {
        this.data.wishlist = this.data.wishlist.filter(id => id !== productId);
        this.saveToStorage();
    }

    getWishlist() {
        return this.data.wishlist;
    }

    isInWishlist(productId) {
        return this.data.wishlist.includes(productId);
    }

    // ── AUTHENTIFICATION ───────────────────────────────────────────────────
    login(email, password) {
        const user = this.data.users.find(u => u.email === email && u.password === password);
        if (user) {
            this.setCurrentUser(user.id);
            return { success: true, user };
        }
        return { success: false, message: "Email ou mot de passe incorrect" };
    }

    register(userData) {
        const existingUser = this.data.users.find(u => u.email === userData.email);
        if (existingUser) {
            return { success: false, message: "Cet email est déjà utilisé" };
        }

        const newUser = {
            id: this.data.users.length + 1,
            ...userData,
            role: "USER",
            created_at: new Date().toISOString().split('T')[0],
            wallet: 0,
            addresses: []
        };

        this.data.users.push(newUser);
        this.saveToStorage();
        this.setCurrentUser(newUser.id);

        return { success: true, user: newUser };
    }

    // ── COMMANDES ───────────────────────────────────────────────────────
    createOrder(orderData) {
        const user = this.getCurrentUser();
        if (!user) return { success: false, message: "Utilisateur non connecté" };

        const order = {
            id: this.data.orders.length + 1,
            user_id: user.id,
            ...orderData,
            status: "PENDING",
            created_at: new Date().toISOString().split('T')[0],
            items: [...this.data.cart]
        };

        this.data.orders.push(order);
        this.clearCart();
        this.saveToStorage();

        return { success: true, order };
    }

    getUserOrders() {
        const user = this.getCurrentUser();
        if (!user) return [];
        
        return this.data.orders.filter(order => order.user_id === user.id);
    }

    // ── NOTIFICATIONS ───────────────────────────────────────────────────
    getNotifications() {
        const user = this.getCurrentUser();
        if (!user) return [];
        
        return this.data.notifications.filter(notif => notif.user_id === user.id);
    }

    markNotificationAsRead(notificationId) {
        const notification = this.data.notifications.find(n => n.id === notificationId);
        if (notification) {
            notification.read = true;
            this.saveToStorage();
        }
    }

    getUnreadNotificationsCount() {
        const user = this.getCurrentUser();
        if (!user) return 0;
        
        return this.data.notifications.filter(n => 
            n.user_id === user.id && !n.read
        ).length;
    }
}

// ── INITIALISATION GLOBALE ─────────────────────────────────────────────
window.SneakXData = new SneakXData();
window.sneakxData = () => window.SneakXData;
