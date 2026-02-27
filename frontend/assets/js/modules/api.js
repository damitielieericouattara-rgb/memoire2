// Fichier: /frontend/assets/js/modules/api.js

function getApiBase() {
    const scripts = document.querySelectorAll('script[src]');
    for (const s of scripts) {
        if (s.src.includes('modules/api.js')) {
            const url = new URL(s.src);
            const parts = url.pathname.split('/');
            const rootParts = parts.slice(0, parts.length - 4);
            return url.origin + rootParts.join('/') + '/backend/index.php';
        }
    }
    const loc = window.location.pathname;
    const match = loc.match(/^(.*?)\/frontend\//);
    if (match) return window.location.origin + match[1] + '/backend/index.php';
    return window.location.origin + '/backend/index.php';
}

const API_BASE = getApiBase();

// Calcule le chemin vers connexion.html selon la profondeur de la page
function getConnexionUrl() {
    const path = window.location.pathname;
    if (path.includes('/pages/admin/')) return '../connexion.html';
    if (path.includes('/pages/'))       return './connexion.html';
    return './pages/connexion.html';
}

function buildUrl(endpoint, params = {}) {
    const url = new URL(API_BASE);
    url.searchParams.set('route', endpoint);
    Object.entries(params).forEach(([k, v]) => {
        if (v !== '' && v !== null && v !== undefined) url.searchParams.append(k, v);
    });
    return url.toString();
}

class API {
    static async get(endpoint, params = {}) {
        return this.request(buildUrl(endpoint, params), { method: 'GET' });
    }
    static async post(endpoint, data = {}) {
        return this.request(buildUrl(endpoint), { method: 'POST', body: JSON.stringify(data) });
    }
    static async put(endpoint, data = {}) {
        return this.request(buildUrl(endpoint), { method: 'PUT', body: JSON.stringify(data) });
    }
    static async delete(endpoint) {
        return this.request(buildUrl(endpoint), { method: 'DELETE' });
    }

    static async request(url, options = {}) {
        const token = localStorage.getItem('access_token');
        const headers = { 'Content-Type': 'application/json', ...options.headers };
        if (token) headers['Authorization'] = 'Bearer ' + token;

        try {
            const response = await fetch(url, { ...options, headers });

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const html = await response.text();
                console.error('ERREUR : Le serveur a renvoyé du HTML/texte');
                console.error('URL appelée :', url);
                console.error('Statut HTTP :', response.status);
                console.error('Début réponse :', html.substring(0, 300));
                throw new Error(
                    'Le serveur ne répond pas correctement. ' +
                    'Vérifiez : 1) que XAMPP/Apache est démarré ' +
                    '2) que la base de données existe'
                );
            }

            const data = await response.json();

            // Token expiré → refresh automatique
            if (response.status === 401 && !url.includes('auth/login') && !url.includes('auth/register')) {
                const refreshed = await this.refreshToken();
                if (refreshed) {
                    return this.request(url, options);
                } else {
                    localStorage.clear();
                    // ✅ FIX: chemin correct selon la page courante
                    window.location.href = getConnexionUrl();
                    throw new Error('Session expirée, veuillez vous reconnecter');
                }
            }

            if (!response.ok) throw new Error(data.message || 'Erreur ' + response.status);
            return data;

        } catch (error) {
            if (error.message.includes('Failed to fetch') || error.message.includes('NetworkError')) {
                throw new Error("Impossible de joindre le serveur. Vérifiez qu'Apache/XAMPP est démarré.");
            }
            throw error;
        }
    }

    static async refreshToken() {
        const refreshToken = localStorage.getItem('refresh_token');
        if (!refreshToken) return false;
        try {
            const response = await fetch(buildUrl('/api/auth/refresh'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ refresh_token: refreshToken })
            });
            const ct = response.headers.get('content-type') || '';
            if (!ct.includes('application/json')) return false;
            const data = await response.json();
            if (data.success && data.data?.access_token) {
                localStorage.setItem('access_token', data.data.access_token);
                return true;
            }
            return false;
        } catch { return false; }
    }
}

export default API;