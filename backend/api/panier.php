<?php
/**
 * SneakX — API Panier
 * /backend/api/panier.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method    = $_SERVER['REQUEST_METHOD'];
$action    = $_GET['action'] ?? 'get';
$input     = getInput();
$cartModel = new Cart();

// Identifiants panier
$userId    = $_SESSION['user_id'] ?? 0;
$sessionId = session_id();

switch ($action) {

    // ── VOIR LE PANIER ────────────────────────────
    case 'get':
        $cart = $cartModel->getCart($userId, $sessionId);
        jsonSuccess($cart);
        break;

    // ── AJOUTER ───────────────────────────────────
    case 'add':
        if ($method !== 'POST') jsonError('POST requis.', 405);

        $productId = (int) ($input['product_id'] ?? 0);
        $quantite  = max(1, (int) ($input['quantite'] ?? 1));
        $taille    = sanitize($input['taille'] ?? '');
        $couleur   = sanitize($input['couleur'] ?? '');

        if (!$productId) jsonError('product_id requis.', 422);

        $result = $cartModel->addItem($productId, $quantite, $taille, $couleur, $userId, $sessionId);
        if (!$result['success']) jsonError($result['error'], 422);

        $cart = $cartModel->getCart($userId, $sessionId);
        jsonSuccess(['cart' => $cart, 'nb_articles' => $cart['nb_articles']], $result['message']);
        break;

    // ── METTRE À JOUR ─────────────────────────────
    case 'update':
        if ($method !== 'POST' && $method !== 'PUT') jsonError('POST/PUT requis.', 405);

        $cartId   = (int) ($input['cart_id'] ?? 0);
        $quantite = (int) ($input['quantite'] ?? 1);
        if (!$cartId) jsonError('cart_id requis.', 422);

        $result = $cartModel->updateItem($cartId, $quantite, $userId);
        if (!$result['success']) jsonError($result['error'], 422);

        $cart = $cartModel->getCart($userId, $sessionId);
        jsonSuccess(['cart' => $cart]);
        break;

    // ── SUPPRIMER UN ARTICLE ──────────────────────
    case 'remove':
        $cartId = (int) ($input['cart_id'] ?? $_GET['cart_id'] ?? 0);
        if (!$cartId) jsonError('cart_id requis.', 422);

        $result = $cartModel->removeItem($cartId, $userId);
        if (!$result['success']) jsonError($result['error'], 422);

        $cart = $cartModel->getCart($userId, $sessionId);
        jsonSuccess(['cart' => $cart], $result['message']);
        break;

    // ── VIDER ─────────────────────────────────────
    case 'clear':
        $cartModel->clearCart($userId, $sessionId);
        jsonSuccess([], 'Panier vidé.');
        break;

    // ── APPLIQUER PROMO ───────────────────────────
    case 'promo':
        if ($method !== 'POST') jsonError('POST requis.', 405);

        $code  = sanitize($input['code'] ?? '');
        $total = (float) ($input['total'] ?? 0);
        if (empty($code)) jsonError('Code promo requis.', 422);

        $result = $cartModel->applyPromo($code, $total);
        if (!$result['success']) jsonError($result['error'], 422);
        jsonSuccess($result, 'Code promo appliqué !');
        break;

    // ── COMPTER (badge header) ────────────────────
    case 'count':
        $cart = $cartModel->getCart($userId, $sessionId);
        jsonSuccess(['count' => $cart['nb_articles']]);
        break;

    default:
        jsonError('Action inconnue.', 404);
}
