<?php
// Fichier: /backend/index.php

// ─── 1. GESTIONNAIRES D'ERREURS — absolument en premier ──────────────────────
// Sans ça, PHP renvoie du HTML quand il y a une erreur → "Unexpected token '<'"

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug'   => [
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'type' => get_class($e),
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    if (ob_get_level()) ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => "Erreur PHP : $errstr",
        'debug'   => ['file' => basename($errfile), 'line' => $errline, 'code' => $errno]
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_end_clean();
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Erreur fatale : ' . $error['message'],
            'debug'   => ['file' => basename($error['file']), 'line' => $error['line']]
        ], JSON_UNESCAPED_UNICODE);
    }
});

// ─── 2. BUFFER DE SORTIE — capture tout output parasite ──────────────────────
ob_start();

// ─── 3. CONFIGS ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/jwt.php';

// ─── 4. AUTOLOADER ────────────────────────────────────────────────────────────
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/core/',
        __DIR__ . '/models/',
        __DIR__ . '/controllers/',
        __DIR__ . '/middleware/',
        __DIR__ . '/services/',
        __DIR__ . '/utils/',
    ];
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ─── 5. HEADERS DE SÉCURITÉ ───────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// ─── 6. SESSION ───────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── 7. VIDE LE BUFFER (supprime whitespace des require) ─────────────────────
ob_end_clean();

// ─── 8. ROUTEUR ───────────────────────────────────────────────────────────────
$router = new Router();
$router->addMiddleware('CorsMiddleware');

// Routes publiques
$router->post('/api/auth/register',    [AuthController::class, 'register']);
$router->post('/api/auth/login',       [AuthController::class, 'login']);
$router->post('/api/auth/refresh',     [AuthController::class, 'refresh']);
$router->get('/api/auth/verify-email', [AuthController::class, 'verifyEmail']);

// Routes protégées — Profil
$router->get('/api/auth/profile', [AuthController::class, 'profile'], ['AuthMiddleware']);

// Produits (routes statiques AVANT les dynamiques)
$router->get('/api/produits/search',  [ProduitController::class, 'search'],          ['AuthMiddleware']);
$router->get('/api/produits/popular', [ProduitController::class, 'popular'],         ['AuthMiddleware']);
$router->get('/api/produits',         [ProduitController::class, 'index'],           ['AuthMiddleware']);
$router->get('/api/produits/{id}',    [ProduitController::class, 'show'],            ['AuthMiddleware']);
$router->get('/api/recommandations',  [ProduitController::class, 'recommendations'], ['AuthMiddleware']);

// Panier
$router->get('/api/panier',         [PanierController::class, 'index'],  ['AuthMiddleware']);
$router->post('/api/panier',        [PanierController::class, 'add'],    ['AuthMiddleware']);
$router->put('/api/panier/{id}',    [PanierController::class, 'update'], ['AuthMiddleware']);
$router->delete('/api/panier/{id}', [PanierController::class, 'remove'], ['AuthMiddleware']);
$router->delete('/api/panier',      [PanierController::class, 'clear'],  ['AuthMiddleware']);

// Commandes
$router->get('/api/commandes',               [CommandeController::class, 'index'],    ['AuthMiddleware']);
$router->post('/api/commandes',              [CommandeController::class, 'create'],   ['AuthMiddleware']);
$router->get('/api/commandes/{id}/tracking', [CommandeController::class, 'tracking'], ['AuthMiddleware']);
$router->get('/api/commandes/{id}',          [CommandeController::class, 'show'],     ['AuthMiddleware']);

// Wallet
$router->get('/api/wallet/transactions', [WalletController::class, 'transactions'], ['AuthMiddleware']);
$router->get('/api/wallet',              [WalletController::class, 'index'],        ['AuthMiddleware']);
$router->post('/api/wallet/recharge',    [WalletController::class, 'recharge'],     ['AuthMiddleware']);

// Notifications
$router->get('/api/notifications/poll',      [NotificationController::class, 'poll'],          ['AuthMiddleware']);
$router->put('/api/notifications/read-all',  [NotificationController::class, 'markAllAsRead'], ['AuthMiddleware']);
$router->get('/api/notifications',           [NotificationController::class, 'index'],          ['AuthMiddleware']);
$router->put('/api/notifications/{id}/read', [NotificationController::class, 'markAsRead'],     ['AuthMiddleware']);

// Admin
$router->get('/api/admin/dashboard',          [AdminController::class, 'dashboard'],        ['AuthMiddleware']);
$router->get('/api/admin/users',              [AdminController::class, 'users'],             ['AuthMiddleware']);
$router->put('/api/admin/users/{id}',         [AdminController::class, 'updateUser'],        ['AuthMiddleware']);
$router->get('/api/admin/products',           [AdminController::class, 'products'],          ['AuthMiddleware']);
$router->post('/api/admin/products',          [AdminController::class, 'createProduct'],     ['AuthMiddleware']);
$router->put('/api/admin/products/{id}',      [AdminController::class, 'updateProduct'],     ['AuthMiddleware']);
$router->delete('/api/admin/products/{id}',   [AdminController::class, 'deleteProduct'],     ['AuthMiddleware']);
$router->get('/api/admin/orders',             [AdminController::class, 'orders'],            ['AuthMiddleware']);
$router->put('/api/admin/orders/{id}/status', [AdminController::class, 'updateOrderStatus'], ['AuthMiddleware']);
$router->get('/api/admin/fraud-alerts',       [AdminController::class, 'fraudAlerts'],       ['AuthMiddleware']);
$router->put('/api/admin/fraud-alerts/{id}',  [AdminController::class, 'updateFraudAlert'],  ['AuthMiddleware']);
$router->get('/api/admin/analytics',          [AdminController::class, 'analytics'],         ['AuthMiddleware']);

$router->dispatch();
