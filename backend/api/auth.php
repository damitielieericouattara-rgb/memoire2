<?php
/**
 * SneakX — API Authentification
 * /backend/api/auth.php
 * Routes : POST /register | POST /login | POST /logout | GET /me
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = getInput();

switch ($action) {

    // ── INSCRIPTION ──────────────────────────────
    case 'register':
        if ($method !== 'POST') jsonError('Méthode non autorisée', 405);

        $auth   = new Auth();
        $result = $auth->register($input);
        if (!$result['success']) {
            jsonError($result['error'] ?? implode(', ', $result['errors'] ?? []), 422);
        }
        jsonSuccess(['token' => $result['token'], 'user' => $result['user']], 'Inscription réussie');
        break;

    // ── CONNEXION ────────────────────────────────
    case 'login':
        if ($method !== 'POST') jsonError('Méthode non autorisée', 405);

        if (empty($input['email']) || empty($input['password'])) {
            jsonError('Email et mot de passe requis.', 422);
        }

        $auth   = new Auth();
        $result = $auth->login($input['email'], $input['password']);
        if (!$result['success']) jsonError($result['error'], 401);

        // Fusionner panier session
        if (!empty($input['session_id'])) {
            $cart = new Cart();
            $cart->mergeSessionToUser($input['session_id'], $result['user']['id']);
        }

        jsonSuccess(['token' => $result['token'], 'user' => $result['user']], 'Connexion réussie');
        break;

    // ── DÉCONNEXION ──────────────────────────────
    case 'logout':
        (new Auth())->logout();
        jsonSuccess([], 'Déconnexion réussie');
        break;

    // ── PROFIL CONNECTÉ ──────────────────────────
    case 'me':
        requireAuth();
        $user = new User();
        $profile = $user->publicProfile($_SESSION['user_id']);
        $stats   = $user->stats($_SESSION['user_id']);
        jsonSuccess(['user' => $profile, 'stats' => $stats]);
        break;

    // ── CHANGER MOT DE PASSE ─────────────────────
    case 'password':
        requireAuth();
        if ($method !== 'POST') jsonError('Méthode non autorisée', 405);

        $user   = new User();
        $result = $user->changePassword(
            $_SESSION['user_id'],
            $input['current_password'] ?? '',
            $input['new_password'] ?? ''
        );
        if (!$result['success']) jsonError($result['error'], 422);
        jsonSuccess([], $result['message']);
        break;

    // ── METTRE À JOUR PROFIL ─────────────────────
    case 'update':
        requireAuth();
        if ($method !== 'POST' && $method !== 'PUT') jsonError('Méthode non autorisée', 405);

        $user   = new User();
        $result = $user->updateProfile($_SESSION['user_id'], $input);
        if (!$result['success']) jsonError($result['error'], 422);
        jsonSuccess($result);
        break;

    default:
        jsonError('Action non trouvée. Utilisez: register, login, logout, me, password, update', 404);
}
