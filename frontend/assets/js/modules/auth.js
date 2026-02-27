// Fichier: /frontend/assets/js/modules/auth.js

class Auth {

    static _basePath() {
        const path = window.location.pathname;
        if (path.includes('/pages/admin/')) return '../';
        if (path.includes('/pages/')) return './';
        return './';
    }

    static setTokens(accessToken, refreshToken) {
        localStorage.setItem('access_token', accessToken);
        localStorage.setItem('refresh_token', refreshToken);
    }

    static getAccessToken()  { return localStorage.getItem('access_token'); }
    static getRefreshToken() { return localStorage.getItem('refresh_token'); }

    static setUser(user) { localStorage.setItem('user', JSON.stringify(user)); }
    static getUser() {
        const u = localStorage.getItem('user');
        try { return u ? JSON.parse(u) : null; } catch { return null; }
    }

    // Vérifie simplement qu'un token ET un user sont présents
    static isAuthenticated() {
        const token = this.getAccessToken();
        const user  = this.getUser();
        if (!token || !user) return false;
        // Tente de vérifier l'expiration si c'est un JWT valide
        try {
            const payload = JSON.parse(atob(token.split('.')[1]));
            if (payload.exp && payload.exp * 1000 < Date.now()) return false;
        } catch {
            // Token non-JWT ou corrompu : on accepte quand même si user présent
        }
        return true;
    }

    static hasRole(role) {
        const u = this.getUser();
        if (!u) return false;
        return (u.role || '').toUpperCase() === role.toUpperCase();
    }

    static logout() {
        localStorage.clear();
        window.location.href = this._basePath() + 'connexion.html';
    }

    static requireAuth() {
        if (!this.isAuthenticated()) {
            window.location.href = this._basePath() + 'connexion.html';
            return false;
        }
        return true;
    }

    static requireAdmin() {
        if (!this.isAuthenticated() || !this.hasRole('ADMIN')) {
            // Redirige vers la page de login admin, pas connexion utilisateur
            window.location.href = 'login.html';
            return false;
        }
        return true;
    }
}

export default Auth;
