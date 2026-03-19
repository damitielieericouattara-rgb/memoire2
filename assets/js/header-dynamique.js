// 🧢 HEADER DYNAMIQUE SNEAKX - Navigation unifiée
class SneakXHeader {
    constructor() {
        this.data = window.SneakXData;
        this.currentUser = this.data.getCurrentUser();
        this.init();
    }

    init() {
        this.createHeader();
        this.setupEventListeners();
        this.updateUserUI();
    }

    createHeader() {
        const headerHTML = `
            <nav class="navbar">
                <!-- Logo -->
                <a href="index.html" class="nav-logo">
                    <i class="fas fa-shoe-prints"></i>
                    <span>SneakX</span>
                </a>

                <!-- Navigation Desktop -->
                <div class="nav-menu">
                    <a href="catalogue.html" class="nav-link">
                        <i class="fas fa-th-large"></i>
                        Catalogue
                    </a>
                    <a href="catalogue.html?brand=Nike" class="nav-link">
                        <i class="fas fa-check"></i>
                        Nike
                    </a>
                    <a href="catalogue.html?brand=Adidas" class="nav-link">
                        <i class="fas fa-three-stripes"></i>
                        Adidas
                    </a>
                    <a href="catalogue.html?brand=Jordan" class="nav-link">
                        <i class="fas fa-jordan"></i>
                        Jordan
                    </a>
                    <div class="nav-dropdown">
                        <button class="nav-link dropdown-toggle">
                            <i class="fas fa-ellipsis-h"></i>
                            Plus
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="catalogue.html?brand=New Balance">New Balance</a>
                            <a href="catalogue.html?brand=Puma">Puma</a>
                            <a href="echange.html">Marketplace</a>
                            <a href="accessibilite.html">Accessibilité</a>
                        </div>
                    </div>
                </div>

                <!-- Recherche -->
                <div class="nav-search">
                    <input type="text" class="search-input" placeholder="Rechercher des sneakers..." id="headerSearch">
                    <button class="search-btn" onclick="window.sneakxApp.handleSearch({target: {value: document.getElementById('headerSearch').value}})">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Actions Utilisateur -->
                <div class="nav-actions">
                    <!-- Theme -->
                    <button class="nav-btn" id="btn-theme" title="Changer le thème">
                        <i class="fas fa-moon"></i>
                    </button>

                    <!-- Panier -->
                    <a href="panier.html" class="nav-btn cart-btn" title="Panier">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count" id="header-cart-count">0</span>
                    </a>

                    <!-- Notifications -->
                    <button class="nav-btn notification-btn" id="notification-btn" title="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count" id="notification-count">0</span>
                    </button>

                    <!-- Menu Utilisateur -->
                    <div class="user-menu" id="user-menu">
                        <!-- Non connecté -->
                        <div class="auth-links" id="auth-links">
                            <a href="connexion.html" class="nav-link">
                                <i class="fas fa-sign-in-alt"></i>
                                Connexion
                            </a>
                            <a href="inscription.html" class="nav-link">
                                <i class="fas fa-user-plus"></i>
                                Inscription
                            </a>
                        </div>

                        <!-- Connecté -->
                        <div class="user-links" id="user-links" style="display: none;">
                            <button class="user-avatar" id="user-avatar-btn">
                                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4f4?w=40&q=80" alt="Avatar">
                                <span class="user-name" id="header-user-name">Jean M.</span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu user-dropdown">
                                <a href="profil.html">
                                    <i class="fas fa-user"></i>
                                    Mon Profil
                                </a>
                                <a href="commandes.html">
                                    <i class="fas fa-shopping-bag"></i>
                                    Mes Commandes
                                </a>
                                <a href="wishlist.html">
                                    <i class="fas fa-heart"></i>
                                    Wishlist
                                </a>
                                <a href="wallet.html">
                                    <i class="fas fa-wallet"></i>
                                    Wallet
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="admin.html" id="admin-link" style="display: none;">
                                    <i class="fas fa-cog"></i>
                                    Administration
                                </a>
                                <a href="#" onclick="window.sneakxApp.logout()">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Déconnexion
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </nav>
        `;

        // Insérer le header dans tous les placeholders
        const placeholders = document.querySelectorAll('#sx-header-placeholder');
        placeholders.forEach(placeholder => {
            placeholder.innerHTML = headerHTML;
        });

        // Si pas de placeholder, ajouter au début du body
        if (placeholders.length === 0) {
            const body = document.body;
            const existingHeader = document.querySelector('.navbar');
            if (!existingHeader) {
                body.insertAdjacentHTML('afterbegin', headerHTML);
            }
        }
    }

    setupEventListeners() {
        // Theme toggle
        const themeBtn = document.getElementById('btn-theme');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => this.toggleTheme());
        }

        // Mobile menu
        const mobileBtn = document.getElementById('mobile-menu-btn');
        if (mobileBtn) {
            mobileBtn.addEventListener('click', () => this.toggleMobileMenu());
        }

        // User dropdown
        const userAvatarBtn = document.getElementById('user-avatar-btn');
        if (userAvatarBtn) {
            userAvatarBtn.addEventListener('click', () => this.toggleUserDropdown());
        }

        // Notifications
        const notificationBtn = document.getElementById('notification-btn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', () => this.toggleNotifications());
        }

        // Recherche
        const searchInput = document.getElementById('headerSearch');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const query = e.target.value;
                    if (query.trim()) {
                        window.location.href = `catalogue.html?search=${encodeURIComponent(query)}`;
                    }
                }
            });
        }

        // Fermer les dropdowns au clic extérieur
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.user-menu')) {
                this.closeAllDropdowns();
            }
        });
    }

    toggleTheme() {
        const currentTheme = document.body.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.body.setAttribute('data-theme', newTheme);
        localStorage.setItem('sneakx_theme', newTheme);
        
        const themeIcon = document.querySelector('#btn-theme i');
        if (themeIcon) {
            themeIcon.className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }

    toggleMobileMenu() {
        const navMenu = document.querySelector('.nav-menu');
        const mobileBtn = document.getElementById('mobile-menu-btn');
        
        if (navMenu && mobileBtn) {
            navMenu.classList.toggle('mobile-open');
            mobileBtn.classList.toggle('active');
        }
    }

    toggleUserDropdown() {
        const dropdown = document.querySelector('.user-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('show');
        }
    }

    toggleNotifications() {
        // Ici vous pouvez ajouter la logique pour afficher les notifications
        const count = this.data.getUnreadNotificationsCount();
        this.updateNotificationCount(count);
    }

    closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }

    updateUserUI() {
        const authLinks = document.getElementById('auth-links');
        const userLinks = document.getElementById('user-links');
        const adminLink = document.getElementById('admin-link');
        const userName = document.getElementById('header-user-name');
        const cartCount = document.getElementById('header-cart-count');
        const notificationCount = document.getElementById('notification-count');

        if (this.currentUser) {
            // Utilisateur connecté
            if (authLinks) authLinks.style.display = 'none';
            if (userLinks) userLinks.style.display = 'block';
            if (adminLink && this.currentUser.role === 'ADMIN') {
                adminLink.style.display = 'block';
            }
            if (userName) {
                userName.textContent = `${this.currentUser.first_name} ${this.currentUser.name.charAt(0)}.`;
            }
        } else {
            // Utilisateur non connecté
            if (authLinks) authLinks.style.display = 'block';
            if (userLinks) userLinks.style.display = 'none';
            if (adminLink) adminLink.style.display = 'none';
        }

        // Mettre à jour le compteur du panier
        if (cartCount) {
            cartCount.textContent = this.data.getCartCount();
        }

        // Mettre à jour le compteur de notifications
        if (notificationCount) {
            notificationCount.textContent = this.data.getUnreadNotificationsCount();
        }
    }

    updateNotificationCount(count) {
        const notificationBadge = document.getElementById('notification-count');
        if (notificationBadge) {
            notificationBadge.textContent = count;
            notificationBadge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    updateCartCount() {
        const cartBadge = document.getElementById('header-cart-count');
        if (cartBadge) {
            cartBadge.textContent = this.data.getCartCount();
        }
    }
}

// Initialiser le header quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    // Attendre que les données soient chargées
    setTimeout(() => {
        if (window.SneakXData) {
            window.sneakxHeader = new SneakXHeader();
        }
    }, 50);
});
