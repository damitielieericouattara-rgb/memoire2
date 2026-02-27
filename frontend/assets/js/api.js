/**
 * api.js — Module de communication avec le backend REST
 * Centralise tous les appels AJAX (fetch) vers /backend/index.php
 */

const API_BASE = '../backend/index.php';

// ─── Utilitaires ─────────────────────────────────────────────────────────────

function getToken() {
    return localStorage.getItem('access_token');
}

function getHeaders(withAuth = true) {
    const headers = { 'Content-Type': 'application/json' };
    if (withAuth) {
        const token = getToken();
        if (token) headers['Authorization'] = `Bearer ${token}`;
    }
    return headers;
}

async function handleResponse(res) {
    const data = await res.json();
    if (!res.ok) throw new Error(data.message || 'Erreur serveur');
    return data;
}

async function apiFetch(route, options = {}) {
    const url = `${API_BASE}?route=${route}`;
    const res = await fetch(url, {
        ...options,
        headers: { ...getHeaders(options.auth !== false), ...(options.headers || {}) }
    });
    // Tentative de rafraîchissement du token si 401
    if (res.status === 401 && options.retry !== false) {
        const refreshed = await tryRefreshToken();
        if (refreshed) {
            return apiFetch(route, { ...options, retry: false });
        } else {
            logout();
            window.location.href = '/frontend/pages/login.html';
            return;
        }
    }
    return handleResponse(res);
}

async function tryRefreshToken() {
    try {
        const refresh = localStorage.getItem('refresh_token');
        if (!refresh) return false;
        const res = await fetch(`${API_BASE}?route=/api/auth/refresh`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ refresh_token: refresh })
        });
        const data = await res.json();
        if (data.success && data.data.access_token) {
            localStorage.setItem('access_token', data.data.access_token);
            return true;
        }
        return false;
    } catch {
        return false;
    }
}

export function logout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('user');
    localStorage.removeItem('wallet');
}

// ─── Auth ─────────────────────────────────────────────────────────────────────

export const Auth = {
    register: (data) => apiFetch('/api/auth/register', {
        method: 'POST', auth: false, body: JSON.stringify(data)
    }),
    login: (email, password) => apiFetch('/api/auth/login', {
        method: 'POST', auth: false, body: JSON.stringify({ email, password })
    }),
    profile: () => apiFetch('/api/auth/profile'),
};

// ─── Produits ─────────────────────────────────────────────────────────────────

export const Produits = {
    list: (params = {}) => {
        const qs = new URLSearchParams(params).toString();
        return apiFetch(`/api/produits${qs ? '?' + qs : ''}`);
    },
    search: (q, params = {}) => {
        const qs = new URLSearchParams({ q, ...params }).toString();
        return apiFetch(`/api/produits/search?${qs}`);
    },
    popular: (limit = 8) => apiFetch(`/api/produits/popular?limit=${limit}`),
    get: (id) => apiFetch(`/api/produits/${id}`),
    recommendations: () => apiFetch('/api/recommandations'),
};

// ─── Panier ───────────────────────────────────────────────────────────────────

export const Panier = {
    get: () => apiFetch('/api/panier'),
    add: (product_id, quantity = 1) => apiFetch('/api/panier', {
        method: 'POST', body: JSON.stringify({ product_id, quantity })
    }),
    update: (id, quantity) => apiFetch(`/api/panier/${id}`, {
        method: 'PUT', body: JSON.stringify({ quantity })
    }),
    remove: (id) => apiFetch(`/api/panier/${id}`, { method: 'DELETE' }),
    clear: () => apiFetch('/api/panier', { method: 'DELETE' }),
};

// ─── Commandes ────────────────────────────────────────────────────────────────

export const Commandes = {
    list: () => apiFetch('/api/commandes'),
    get: (id) => apiFetch(`/api/commandes/${id}`),
    create: (data) => apiFetch('/api/commandes', {
        method: 'POST', body: JSON.stringify(data)
    }),
    tracking: (id) => apiFetch(`/api/commandes/${id}/tracking`),
};

// ─── Wallet ───────────────────────────────────────────────────────────────────

export const Wallet = {
    get: () => apiFetch('/api/wallet'),
    recharge: (amount) => apiFetch('/api/wallet/recharge', {
        method: 'POST', body: JSON.stringify({ amount })
    }),
    transactions: () => apiFetch('/api/wallet/transactions'),
};

// ─── Notifications ────────────────────────────────────────────────────────────

export const Notifications = {
    list: () => apiFetch('/api/notifications'),
    poll: () => apiFetch('/api/notifications/poll'),
    markRead: (id) => apiFetch(`/api/notifications/${id}/read`, { method: 'PUT' }),
    markAllRead: () => apiFetch('/api/notifications/read-all', { method: 'PUT' }),
};

// ─── Admin ────────────────────────────────────────────────────────────────────

export const Admin = {
    dashboard: () => apiFetch('/api/admin/dashboard'),
    users: (page = 1) => apiFetch(`/api/admin/users?page=${page}`),
    products: () => apiFetch('/api/admin/products'),
    createProduct: (data) => apiFetch('/api/admin/products', {
        method: 'POST', body: JSON.stringify(data)
    }),
    updateProduct: (id, data) => apiFetch(`/api/admin/products/${id}`, {
        method: 'PUT', body: JSON.stringify(data)
    }),
    deleteProduct: (id) => apiFetch(`/api/admin/products/${id}`, { method: 'DELETE' }),
    orders: (params = {}) => {
        const qs = new URLSearchParams(params).toString();
        return apiFetch(`/api/admin/orders${qs ? '?' + qs : ''}`);
    },
    updateOrderStatus: (id, status) => apiFetch(`/api/admin/orders/${id}/status`, {
        method: 'PUT', body: JSON.stringify({ status })
    }),
    analytics: () => apiFetch('/api/admin/analytics'),
    fraudAlerts: () => apiFetch('/api/admin/fraud-alerts'),
};
