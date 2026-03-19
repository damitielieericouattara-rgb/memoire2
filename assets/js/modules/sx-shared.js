/**
 * SneakX — Module partagé : Header, Thème, Auth Modal, Toast
 * VERSION CORRIGÉE — Chemins automatiques selon profondeur de la page
 */

/* ── CHEMIN DE BASE (calculé une seule fois) ── */
function getBasePath() {
    const path = window.location.pathname;
    if (path.includes('/pages/admin/')) return '../../pages/';
    if (path.includes('/pages/')) return './';
    return './';
}
const BASE = getBasePath();

/* ── THEME ── */
const THEME_KEY = 'sx_theme';

export function initTheme() {
    const saved = localStorage.getItem(THEME_KEY) || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
    return saved;
}

export function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem(THEME_KEY, next);
    updateThemeIcon(next);
}

export function updateThemeIcon(theme) {
    const btn = document.getElementById('sx-theme-btn');
    if (!btn) return;
    btn.innerHTML = theme === 'dark'
        ? '<i class="fas fa-sun"></i>'
        : '<i class="fas fa-moon"></i>';
    btn.title = theme === 'dark' ? 'Mode clair' : 'Mode sombre';
}

/* ── AUTH CHECK ── */
export function isAuthenticated() {
    try {
        const token = localStorage.getItem('access_token');
        if (!token) return false;
        // Support both JWT (3 parts) and base64 JSON token
        let payload;
        const parts = token.split('.');
        if (parts.length === 3) {
            payload = JSON.parse(atob(parts[1]));
        } else {
            // Our static token: btoa(JSON.stringify({id,email,role,exp}))
            payload = JSON.parse(atob(token));
        }
        if (payload.exp && payload.exp * 1000 < Date.now()) {
            localStorage.removeItem('access_token');
            return false;
        }
        return true;
    } catch {
        return !!localStorage.getItem('access_token');
    }
}

export function getUser() {
    try { return JSON.parse(localStorage.getItem('user') || 'null'); }
    catch { return null; }
}

export function logout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');
    localStorage.removeItem('wallet');
    window.location.href = BASE + 'index.html';
}

/* ── AUTH MODAL ── */
let _authReason = '';

export function requireAuth(action = '', onSuccess = null) {
    if (isAuthenticated()) {
        if (onSuccess) onSuccess();
        return true;
    }
    showAuthModal(action, onSuccess);
    return false;
}

export function showAuthModal(reason = '', onSuccess = null) {
    _authReason = reason;
    const overlay = document.getElementById('sx-auth-overlay');
    if (!overlay) return;
    const subtitle = document.getElementById('sx-auth-subtitle');
    if (subtitle && reason) {
        subtitle.textContent = `Vous devez être connecté pour ${reason}.`;
    }
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    if (onSuccess) {
        overlay.dataset.onSuccess = 'true';
        overlay._onSuccess = onSuccess;
    }
}

export function closeAuthModal() {
    const overlay = document.getElementById('sx-auth-overlay');
    if (!overlay) return;
    overlay.classList.remove('open');
    document.body.style.overflow = '';
}

/* ── TOAST ── */
export function showToast(message, type = 'info') {
    let container = document.getElementById('sx-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'sx-toast-container';
        document.body.appendChild(container);
    }
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
    const toast = document.createElement('div');
    toast.className = `sx-toast ${type}`;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}" style="color:${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : 'var(--orange)'}"></i> ${message}`;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transform = 'translateX(20px)'; toast.style.transition = '0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
}

/* ── BUILD HEADER HTML ── */
export function buildHeader(activePage = '') {
    const user = getUser();
    const authed = isAuthenticated();
    const theme = localStorage.getItem(THEME_KEY) || 'dark';
    const themeIcon = theme === 'dark' ? 'fa-sun' : 'fa-moon';
    const themeTitle = theme === 'dark' ? 'Mode clair' : 'Mode sombre';
    const p = BASE;
    const isHome = activePage === 'home';

    let navLinks;

    if (!authed && isHome) {
        // Page d'accueil non connecté → ancres vers sections
        navLinks = [
            { label: 'Accueil',    href: '#',            key: 'home' },
            { label: 'Catalogue',  href: '#catalogue',   key: 'catalogue' },
            { label: 'À propos',   href: '#about',       key: 'about' },
            { label: 'Newsletter', href: '#newsletter',  key: 'newsletter' },
        ];
    } else if (authed) {
        // Connecté → liens vers les vraies pages, Accueil remplacé par Dashboard
        navLinks = [
            { label: 'Dashboard',       href: p + 'dashboard.html',  key: 'dashboard' },
            { label: 'Catalogue',       href: p + 'catalogue.html',  key: 'catalogue' },
            { label: 'Mes commandes',   href: p + 'commandes.html',  key: 'commandes' },
            { label: 'Wallet',          href: p + 'wallet.html',     key: 'wallet' },
        ];
    } else {
        // Autres pages non connecté → liens normaux
        navLinks = [
            { label: 'Accueil',    href: p + 'index.html',    key: 'home' },
            { label: 'Catalogue',  href: p + 'catalogue.html', key: 'catalogue' },
            { label: 'Produits',   href: p + 'catalogue.html', key: 'produits' },
            { label: 'À propos',   href: p + 'index.html#about', key: 'about' },
        ];
    }

    const navHtml = navLinks.map(l =>
        `<li><a href="${l.href}" class="${activePage === l.key ? 'active' : ''}">${l.label}</a></li>`
    ).join('');

    const userHtml = authed ? `
        <div class="sx-user-menu">
            <div class="sx-user-trigger" onclick="SX.toggleUserMenu()">
                <div class="sx-user-avatar">${(user?.first_name || user?.name || 'U')[0].toUpperCase()}</div>
                <span class="sx-user-name">${user?.first_name || user?.name || 'Mon compte'}</span>
                <i class="fas fa-chevron-down" style="font-size:10px;color:var(--text2)"></i>
            </div>
            <div class="sx-dropdown" id="sx-user-dropdown">
                <div class="sx-dropdown-header">
                    <div class="sx-dropdown-name">${user?.first_name || ''} ${user?.name || ''}</div>
                    <div class="sx-dropdown-email">${user?.email || ''}</div>
                </div>
                <a href="${p}dashboard.html"><i class="fas fa-th-large"></i> Dashboard</a>
                <a href="${p}profil.html"><i class="fas fa-user"></i> Mon profil</a>
                <a href="${p}wallet.html"><i class="fas fa-wallet"></i> Mon wallet</a>
                <a href="${p}commandes.html"><i class="fas fa-box"></i> Mes commandes</a>
                <a href="${p}wishlist.html"><i class="fas fa-heart"></i> Ma wishlist</a>
                <hr class="sx-dropdown-sep">
                <a href="#" class="logout" onclick="SX.logout()"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    ` : `
        <button class="sx-btn-connect" onclick="SX.showAuthModal('accéder à cette fonctionnalité')">Se connecter</button>
        <button class="sx-btn-primary" onclick="window.location.href='${p}inscription.html'">S'inscrire</button>
    `;

    return `
        <a href="#main-content" class="skip-link">Aller au contenu principal</a>
        <header class="sx-header" id="sx-header">
            <div class="sx-logo" onclick="window.location.href='${p}index.html'">
                <i class="fas fa-shopping-bag logo-icon"></i>
                Sneak<span>X</span>
            </div>
            <nav aria-label="Navigation principale">
                <ul class="sx-nav">${navHtml}</ul>
            </nav>
            <div class="sx-header-actions">
                <button class="sx-theme-btn" id="sx-theme-btn" onclick="SX.toggleTheme()" title="${themeTitle}">
                    <i class="fas ${themeIcon}"></i>
                </button>
                ${authed ? `
                <button class="sx-icon-btn" onclick="window.location.href='${p}notifications.html'" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                </button>
                <button class="sx-icon-btn" onclick="window.location.href='${p}panier.html'" aria-label="Panier">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="sx-badge" id="cart-count" style="display:none">0</span>
                </button>
                ` : `
                <button class="sx-icon-btn" onclick="window.location.href='${p}index.html'" aria-label="Rechercher">
                    <i class="fas fa-search"></i>
                </button>
                `}
                ${userHtml}
            </div>
        </header>
    `;
}

/* ── AUTH MODAL HTML ── */
export function buildAuthModal() {
    const p = BASE;
    return `
        <div class="sx-auth-overlay" id="sx-auth-overlay" onclick="SX.handleOverlayClick(event)">
            <div class="sx-auth-modal">
                <button class="sx-auth-close" onclick="SX.closeAuthModal()" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
                <div class="sx-auth-icon"><i class="fas fa-lock"></i></div>
                <div class="sx-auth-title">Connexion requise</div>
                <p class="sx-auth-subtitle" id="sx-auth-subtitle">Vous devez être connecté pour accéder à cette fonctionnalité.</p>
                <div class="sx-auth-btns">
                    <a href="${p}connexion.html" class="sx-auth-btn-main">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </a>
                    <div class="sx-auth-divider">ou</div>
                    <a href="${p}inscription.html" class="sx-auth-btn-sec">
                        <i class="fas fa-user-plus"></i> Créer un compte gratuitement
                    </a>
                </div>
                <p style="text-align:center;margin-top:20px;font-size:12px;color:var(--text2)">
                    Continuer à explorer → <a href="${p}index.html" style="color:var(--orange)" onclick="SX.closeAuthModal()">Revenir à l'accueil</a>
                </p>
            </div>
        </div>
        <div id="sx-toast-container"></div>
    `;
}

/* ── INIT EVERYTHING ── */
export function init(activePage = '') {
    const theme = initTheme();

    const headerPlaceholder = document.getElementById('sx-header-placeholder');
    if (headerPlaceholder) {
        headerPlaceholder.outerHTML = buildHeader(activePage) + buildAuthModal();
    }

    window.addEventListener('scroll', () => {
        const h = document.getElementById('sx-header');
        if (!h) return;
        h.classList.toggle('shrunk', window.scrollY > 40);
    });

    document.addEventListener('click', (e) => {
        const menu = document.getElementById('sx-user-dropdown');
        if (menu && !e.target.closest('.sx-user-menu')) {
            menu.classList.remove('open');
        }
    });

    const revealObs = new IntersectionObserver(entries => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); revealObs.unobserve(e.target); } });
    }, { threshold: 0.1 });
    document.querySelectorAll('.reveal, .reveal-stagger').forEach(el => revealObs.observe(el));
}

/* ── GLOBAL SX OBJECT ── */
window.SX = {
    toggleTheme,
    showAuthModal,
    closeAuthModal,
    requireAuth,
    logout,
    showToast,
    isAuthenticated,
    toggleUserMenu() {
        const d = document.getElementById('sx-user-dropdown');
        if (d) d.classList.toggle('open');
    },
    handleOverlayClick(e) {
        if (e.target.id === 'sx-auth-overlay') closeAuthModal();
    }
};