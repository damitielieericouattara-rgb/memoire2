// 🚀 SNEAKX FRONTEND DYNAMIQUE - Logique complète
// Gère toute l'interactivité du site sans backend

class SneakXApp {
    constructor() {
        this.data = window.SneakXData;
        this.currentUser = this.data.getCurrentUser();
        this.init();
    }

    // ── INITIALISATION ───────────────────────────────────────────────────
    init() {
        this.setupEventListeners();
        this.updateUI();
        this.setupTheme();
        this.setupCart();
        this.setupNotifications();
        this.checkAuthPages();
    }

    setupEventListeners() {
        // Thème
        const themeBtn = document.getElementById('btn-theme');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => this.toggleTheme());
        }

        // Navigation mobile
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => this.toggleMobileMenu());
        }

        // Recherche
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.handleSearch(e));
        }

        // Formulaire d'inscription
        const registerForm = document.getElementById('register-form');
        if (registerForm) {
            registerForm.addEventListener('submit', (e) => this.handleRegister(e));
        }

        // Formulaire de connexion
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }

        // Ajout au panier
        document.addEventListener('click', (e) => {
            if (e.target.matches('.add-to-cart-btn')) {
                this.handleAddToCart(e);
            }
        });

        // Wishlist
        document.addEventListener('click', (e) => {
            if (e.target.matches('.wishlist-btn')) {
                this.handleWishlist(e);
            }
        });
    }

    // ── THÈME ─────────────────────────────────────────────────────────────
    setupTheme() {
        const savedTheme = localStorage.getItem('sneakx_theme') || 'dark';
        document.body.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(savedTheme);
    }

    toggleTheme() {
        const currentTheme = document.body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.body.setAttribute('data-theme', newTheme);
        localStorage.setItem('sneakx_theme', newTheme);
        this.updateThemeIcon(newTheme);
    }

    updateThemeIcon(theme) {
        const themeBtn = document.getElementById('btn-theme');
        if (themeBtn) {
            themeBtn.textContent = theme === 'dark' ? '🌙' : '☀️';
        }
    }

    // ── PANIER ─────────────────────────────────────────────────────────────
    setupCart() {
        this.updateCartUI();
    }

    handleAddToCart(e) {
        const btn = e.target;
        const productId = parseInt(btn.dataset.productId);
        const size = btn.dataset.size || null;
        const color = btn.dataset.color || null;

        const success = this.data.addToCart(productId, 1, size, color);
        
        if (success) {
            this.showNotification('Produit ajouté au panier', 'success');
            this.updateCartUI();
            this.animateButton(btn);
        } else {
            this.showNotification('Erreur lors de l\'ajout au panier', 'error');
        }
    }

    updateCartUI() {
        const cartCount = document.querySelector('.cart-count');
        const cartTotal = document.querySelector('.cart-total');
        
        if (cartCount) {
            cartCount.textContent = this.data.getCartCount();
        }
        
        if (cartTotal) {
            cartTotal.textContent = this.formatPrice(this.data.getCartTotal());
        }

        // Mise à jour de la page panier
        this.updateCartPage();
    }

    updateCartPage() {
        const cartContainer = document.querySelector('.cart-items');
        if (!cartContainer) return;

        const cartItems = this.data.getCart();
        
        if (cartItems.length === 0) {
            cartContainer.innerHTML = `
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Votre panier est vide</h3>
                    <p>Découvrez nos dernières nouveautés</p>
                    <a href="catalogue.html" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i>
                        Voir les produits
                    </a>
                </div>
            `;
            return;
        }

        cartContainer.innerHTML = cartItems.map(item => `
            <div class="cart-item" data-item-id="${item.id}">
                <img src="${item.image}" alt="${item.name}" class="cart-img">
                <div class="item-details">
                    <div class="item-name">${item.name}</div>
                    <div class="item-variant">
                        ${item.size ? `Taille: ${item.size}` : ''}
                        ${item.color ? ` - ${item.color}` : ''}
                    </div>
                    <div class="item-price">${this.formatPrice(item.price)}</div>
                </div>
                <div class="item-actions">
                    <div class="qty-ctrl">
                        <button class="qty-btn" onclick="sneakxApp.updateQuantity(${item.id}, -1)">-</button>
                        <span class="qty-val">${item.quantity}</span>
                        <button class="qty-btn" onclick="sneakxApp.updateQuantity(${item.id}, 1)">+</button>
                    </div>
                    <button class="remove-btn" onclick="sneakxApp.removeFromCart(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    updateQuantity(itemId, change) {
        const item = this.data.getCart().find(i => i.id === itemId);
        if (item) {
            const newQuantity = item.quantity + change;
            if (newQuantity > 0) {
                this.data.updateCartQuantity(itemId, newQuantity);
                this.updateCartUI();
            }
        }
    }

    removeFromCart(itemId) {
        this.data.removeFromCart(itemId);
        this.updateCartUI();
        this.showNotification('Produit retiré du panier', 'info');
    }

    // ── WISHLIST ───────────────────────────────────────────────────────────
    handleWishlist(e) {
        const btn = e.target;
        const productId = parseInt(btn.dataset.productId);
        
        if (this.data.isInWishlist(productId)) {
            this.data.removeFromWishlist(productId);
            btn.classList.remove('active');
            this.showNotification('Retiré de la wishlist', 'info');
        } else {
            this.data.addToWishlist(productId);
            btn.classList.add('active');
            this.showNotification('Ajouté à la wishlist', 'success');
        }
    }

    // ── AUTHENTIFICATION ─────────────────────────────────────────────────────
    handleLogin(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const email = formData.get('email');
        const password = formData.get('password');

        const result = this.data.login(email, password);
        
        if (result.success) {
            this.currentUser = result.user;
            this.showNotification('Connexion réussie !', 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        } else {
            this.showNotification(result.message, 'error');
        }
    }

    handleRegister(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const userData = {
            first_name: formData.get('first_name'),
            name: formData.get('name'),
            email: formData.get('email'),
            password: formData.get('password'),
            phone: formData.get('phone')
        };

        const result = this.data.register(userData);
        
        if (result.success) {
            this.currentUser = result.user;
            this.showNotification('Inscription réussie !', 'success');
            setTimeout(() => {
                window.location.href = 'index.html';
            }, 1000);
        } else {
            this.showNotification(result.message, 'error');
        }
    }

    logout() {
        this.data.logout();
        this.currentUser = null;
        this.showNotification('Déconnexion réussie', 'info');
        setTimeout(() => {
            window.location.href = 'index.html';
        }, 1000);
    }

    // ── PRODUITS ───────────────────────────────────────────────────────────
    handleSearch(e) {
        const query = e.target.value.toLowerCase();
        const products = this.data.getProducts({ search: query });
        this.updateProductGrid(products);
    }

    updateProductGrid(products) {
        const grid = document.querySelector('.products-grid');
        if (!grid) return;

        if (products.length === 0) {
            grid.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>Aucun produit trouvé</h3>
                    <p>Essayez avec d'autres mots-clés</p>
                </div>
            `;
            return;
        }

        grid.innerHTML = products.map(product => this.createProductCard(product)).join('');
    }

    createProductCard(product) {
        const isInWishlist = this.data.isInWishlist(product.id);
        const discount = product.discount ? 
            `<span class="discount-badge">-${product.discount}%</span>` : '';
        
        const originalPrice = product.original_price ? 
            `<span class="original-price">${this.formatPrice(product.original_price)}</span>` : '';

        return `
            <div class="product-card" data-product-id="${product.id}">
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}">
                    ${discount}
                    <div class="product-actions">
                        <button class="wishlist-btn ${isInWishlist ? 'active' : ''}" 
                                data-product-id="${product.id}" title="Wishlist">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
                <div class="product-info">
                    <div class="product-brand">${product.brand}</div>
                    <h3 class="product-name">${product.name}</h3>
                    <div class="product-rating">
                        ${this.createStars(product.rating)}
                        <span>(${product.reviews})</span>
                    </div>
                    <div class="product-price">
                        <span class="current-price">${this.formatPrice(product.price)}</span>
                        ${originalPrice}
                    </div>
                    <button class="add-to-cart-btn btn btn-primary btn-full" 
                            data-product-id="${product.id}">
                        <i class="fas fa-shopping-cart"></i>
                        Ajouter au panier
                    </button>
                </div>
            </div>
        `;
    }

    // ── PAGE PRODUIT ─────────────────────────────────────────────────────
    setupProductPage() {
        const urlParams = new URLSearchParams(window.location.search);
        const productId = urlParams.get('id');
        
        if (!productId) return;
        
        const product = this.data.getProduct(parseInt(productId));
        if (!product) return;

        this.updateProductPage(product);
    }

    updateProductPage(product) {
        // Mise à jour des informations du produit
        const elements = {
            name: document.querySelector('.product-name'),
            brand: document.querySelector('.product-brand'),
            price: document.querySelector('.product-price'),
            description: document.querySelector('.product-description'),
            image: document.querySelector('.product-main-image'),
            rating: document.querySelector('.product-rating'),
            stock: document.querySelector('.product-stock')
        };

        if (elements.name) elements.name.textContent = product.name;
        if (elements.brand) elements.brand.textContent = product.brand;
        if (elements.price) elements.price.textContent = this.formatPrice(product.price);
        if (elements.description) elements.description.textContent = product.description;
        if (elements.image) elements.image.src = product.image;
        if (elements.rating) elements.rating.innerHTML = this.createStars(product.rating);
        if (elements.stock) elements.stock.textContent = `${product.stock} en stock`;

        // Galerie d'images
        const gallery = document.querySelector('.product-gallery');
        if (gallery && product.images) {
            gallery.innerHTML = product.images.map(img => `
                <img src="${img}" alt="${product.name}" class="gallery-thumb" 
                     onclick="sneakxApp.changeMainImage('${img}')">
            `).join('');
        }

        // Options de taille
        const sizeOptions = document.querySelector('.size-options');
        if (sizeOptions && product.sizes) {
            sizeOptions.innerHTML = product.sizes.map(size => `
                <button class="size-option" data-size="${size}">${size}</button>
            `).join('');
        }

        // Options de couleur
        const colorOptions = document.querySelector('.color-options');
        if (colorOptions && product.colors) {
            colorOptions.innerHTML = product.colors.map(color => `
                <button class="color-option" data-color="${color}" 
                        style="background: ${this.getColorCode(color)}" 
                        title="${color}"></button>
            `).join('');
        }
    }

    // ── CHECKOUT ───────────────────────────────────────────────────────────
    setupCheckout() {
        this.updateCheckoutSummary();
        this.setupPaymentMethods();
        this.setupDeliveryOptions();
    }

    updateCheckoutSummary() {
        const cartItems = this.data.getCart();
        const subtotal = this.data.getCartTotal();
        const delivery = 2000; // Fixe pour l'exemple
        const total = subtotal + delivery;

        const summaryItems = document.querySelector('.summary-items');
        if (summaryItems) {
            summaryItems.innerHTML = cartItems.map(item => `
                <div class="summary-item">
                    <img src="${item.image}" alt="${item.name}" class="item-thumb">
                    <div class="item-details">
                        <div class="item-name">${item.name}</div>
                        <div class="item-variant">
                            ${item.size ? `Taille: ${item.size}` : ''}
                            ${item.color ? ` - ${item.color}` : ''}
                        </div>
                    </div>
                    <div class="item-quantity">x${item.quantity}</div>
                    <div class="item-price">${this.formatPrice(item.price * item.quantity)}</div>
                </div>
            `).join('');
        }

        // Mise à jour des totaux
        this.updateElement('.subtotal', this.formatPrice(subtotal));
        this.updateElement('.delivery-fee', this.formatPrice(delivery));
        this.updateElement('.total-amount', this.formatPrice(total));
    }

    setupPaymentMethods() {
        const paymentOptions = document.querySelectorAll('input[name="payment"]');
        paymentOptions.forEach(option => {
            option.addEventListener('change', (e) => {
                document.querySelectorAll('.payment-card').forEach(card => {
                    card.classList.remove('selected');
                });
                e.target.nextElementSibling.classList.add('selected');
            });
        });
    }

    setupDeliveryOptions() {
        const deliveryOptions = document.querySelectorAll('input[name="delivery"]');
        deliveryOptions.forEach(option => {
            option.addEventListener('change', (e) => {
                document.querySelectorAll('.delivery-card').forEach(card => {
                    card.classList.remove('selected');
                });
                e.target.nextElementSibling.classList.add('selected');
                this.updateCheckoutSummary(); // Recalculer avec les frais
            });
        });
    }

    handleCheckout(e) {
        e.preventDefault();
        
        if (this.data.getCart().length === 0) {
            this.showNotification('Votre panier est vide', 'error');
            return;
        }

        const formData = new FormData(e.target);
        const orderData = {
            total: this.data.getCartTotal(),
            payment_method: formData.get('payment'),
            delivery_address: this.formatAddress(formData),
            delivery_method: formData.get('delivery')
        };

        const result = this.data.createOrder(orderData);
        
        if (result.success) {
            this.showNotification('Commande créée avec succès !', 'success');
            setTimeout(() => {
                window.location.href = `commande.html?id=${result.order.id}`;
            }, 1500);
        } else {
            this.showNotification(result.message, 'error');
        }
    }

    // ── UTILITAIRES ───────────────────────────────────────────────────────
    formatPrice(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'XOF'
        }).format(price);
    }

    createStars(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 !== 0;
        let stars = '';
        
        for (let i = 0; i < fullStars; i++) {
            stars += '<i class="fas fa-star"></i>';
        }
        
        if (hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        }
        
        const emptyStars = 5 - Math.ceil(rating);
        for (let i = 0; i < emptyStars; i++) {
            stars += '<i class="far fa-star"></i>';
        }
        
        return stars;
    }

    getColorCode(color) {
        const colors = {
            'Noir': '#000000',
            'Blanc': '#FFFFFF',
            'Rouge': '#FF0000',
            'Bleu': '#0000FF',
            'Bleu marine': '#000080',
            'Vert': '#00FF00',
            'Rose': '#FFC0CB',
            'Gris': '#808080'
        };
        return colors[color] || '#CCCCCC';
    }

    formatAddress(formData) {
        return `${formData.get('street')}, ${formData.get('city')} ${formData.get('postal')}`;
    }

    updateElement(selector, content) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = content;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${this.getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            info: 'info-circle',
            warning: 'exclamation-triangle'
        };
        return icons[type] || 'info-circle';
    }

    animateButton(btn) {
        btn.classList.add('added');
        btn.innerHTML = '<i class="fas fa-check"></i> Ajouté';
        
        setTimeout(() => {
            btn.classList.remove('added');
            btn.innerHTML = '<i class="fas fa-shopping-cart"></i> Ajouter au panier';
        }, 2000);
    }

    // ── VÉRIFICATIONS PAGES ───────────────────────────────────────────────
    checkAuthPages() {
        const currentPage = window.location.pathname.split('/').pop();
        
        // Pages protégées
        const protectedPages = ['profil.html', 'commandes.html', 'panier.html'];
        const isProtected = protectedPages.includes(currentPage);
        
        if (isProtected && !this.currentUser) {
            this.showNotification('Veuillez vous connecter', 'warning');
            setTimeout(() => {
                window.location.href = 'connexion.html';
            }, 1500);
        }
        
        // Pages admin
        const adminPages = ['admin.html'];
        const isAdmin = adminPages.includes(currentPage);
        
        if (isAdmin && (!this.currentUser || this.currentUser.role !== 'ADMIN')) {
            window.location.href = 'index.html';
        }
    }

    updateUI() {
        // Mise à jour de l'interface utilisateur
        const userLinks = document.querySelectorAll('.user-link');
        const authLinks = document.querySelectorAll('.auth-link');
        
        if (this.currentUser) {
            // Utilisateur connecté
            userLinks.forEach(link => link.style.display = 'block');
            authLinks.forEach(link => link.style.display = 'none');
            
            const userName = document.querySelector('.user-name');
            if (userName) {
                userName.textContent = `${this.currentUser.first_name} ${this.currentUser.name}`;
            }
        } else {
            // Utilisateur non connecté
            userLinks.forEach(link => link.style.display = 'none');
            authLinks.forEach(link => link.style.display = 'block');
        }
    }
}

// ── INITIALISATION GLOBALE ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    window.sneakxApp = new SneakXApp();
});
