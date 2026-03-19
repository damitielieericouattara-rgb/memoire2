/**
 * SneakX — Auth Statique (sans backend)
 * Gestion des utilisateurs en localStorage
 */

const USERS_KEY = 'sneakx_users';
const SESSION_KEY = 'sneakx_session';

/* ── DONNÉES PAR DÉFAUT ── */
function getDefaultUsers() {
    return [
        {
            id: 1,
            first_name: 'Admin',
            name: 'SneakX',
            email: 'admin@sneakx.com',
            password: 'Admin1234!',
            role: 'ADMIN',
            created_at: '2024-01-01',
            wallet: 50000,
            phone: '+225 07 08 09 10',
            avatar: null
        },
        {
            id: 2,
            first_name: 'Jean',
            name: 'Martin',
            email: 'jean.martin@email.com',
            password: 'password123',
            role: 'USER',
            created_at: '2024-02-15',
            wallet: 25000,
            phone: '+225 01 02 03 04',
            avatar: null
        }
    ];
}

function getUsers() {
    try {
        const stored = localStorage.getItem(USERS_KEY);
        return stored ? JSON.parse(stored) : getDefaultUsers();
    } catch { return getDefaultUsers(); }
}

function saveUsers(users) {
    localStorage.setItem(USERS_KEY, JSON.stringify(users));
}

/* ── CONNEXION ── */
export function login(email, password) {
    const users = getUsers();
    const user = users.find(u => u.email.toLowerCase() === email.toLowerCase() && u.password === password);
    if (!user) return { success: false, message: 'Email ou mot de passe incorrect.' };

    // Générer un token factice
    const token = btoa(JSON.stringify({ id: user.id, email: user.email, role: user.role, exp: Math.floor(Date.now()/1000) + 86400 * 30 }));
    const session = { token, userId: user.id, loggedAt: new Date().toISOString() };

    localStorage.setItem('access_token', token);
    localStorage.setItem(SESSION_KEY, JSON.stringify(session));

    const { password: _pw, ...safeUser } = user;
    localStorage.setItem('user', JSON.stringify(safeUser));
    localStorage.setItem('wallet', JSON.stringify({ balance: user.wallet }));

    return { success: true, user: safeUser };
}

/* ── INSCRIPTION ── */
export function register({ first_name, name, email, password, phone = null }) {
    const users = getUsers();

    if (users.find(u => u.email.toLowerCase() === email.toLowerCase())) {
        return { success: false, message: 'Un compte existe déjà avec cet email.' };
    }

    const newUser = {
        id: Date.now(),
        first_name,
        name,
        email,
        password,
        phone,
        role: 'USER',
        created_at: new Date().toISOString().split('T')[0],
        wallet: 5000,
        avatar: null
    };

    users.push(newUser);
    saveUsers(users);

    // Connexion automatique
    return login(email, password);
}

/* ── DÉCONNEXION ── */
export function logout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem(SESSION_KEY);
    localStorage.removeItem('user');
    localStorage.removeItem('wallet');
}

/* ── VÉRIFICATION AUTH ── */
export function isAuthenticated() {
    try {
        const token = localStorage.getItem('access_token');
        if (!token) return false;
        const payload = JSON.parse(atob(token));
        return payload.exp * 1000 > Date.now();
    } catch {
        return !!localStorage.getItem('access_token');
    }
}

export function getUser() {
    try { return JSON.parse(localStorage.getItem('user') || 'null'); }
    catch { return null; }
}
