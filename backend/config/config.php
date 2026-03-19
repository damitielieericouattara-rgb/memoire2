<?php
/**
 * SneakX — Configuration principale
 * /backend/config/config.php
 */

// ── ENVIRONNEMENT ──────────────────────────────
define('APP_ENV',      'development');   // 'production' en prod
define('APP_NAME',     'SneakX');
define('APP_VERSION',  '2.0.0');
define('APP_URL',      'http://localhost/memoire2');
define('APP_LOCALE',   'fr_CI');
define('APP_TIMEZONE', 'Africa/Abidjan');

date_default_timezone_set(APP_TIMEZONE);

// ── CHEMINS ────────────────────────────────────
define('ROOT_PATH',     dirname(__DIR__, 2));
define('BACKEND_PATH',  dirname(__DIR__));
define('FRONTEND_PATH', ROOT_PATH . '/assets');
define('UPLOAD_PATH',   ROOT_PATH . '/uploads');
define('UPLOAD_URL',    APP_URL . '/uploads');

// ── SESSION ─────────────────────────────────────
define('SESSION_NAME',     'sneakx_sess');
define('SESSION_LIFETIME', 7200);

// ── SÉCURITÉ ────────────────────────────────────
define('CSRF_TOKEN_KEY',   '_csrf');
define('JWT_SECRET',       'SneakX_JWT_S3cret_2024_!@#$%');
define('JWT_EXPIRY',       3600 * 2);  // 2h
define('BCRYPT_COST',      12);

// ── PAIEMENTS ───────────────────────────────────
// Wave CI
define('WAVE_API_URL',        'https://api.wave.com/v1/checkout/sessions');
define('WAVE_API_KEY',        getenv('WAVE_API_KEY') ?: 'wave_skey_test_xxxxx');
define('WAVE_WEBHOOK_SECRET', getenv('WAVE_WEBHOOK_SECRET') ?: '');

// Orange Money CI
define('OM_API_URL',    'https://api.orange.com/orange-money-webpay/ci/v1');
define('OM_TOKEN',      getenv('ORANGE_MONEY_TOKEN') ?: '');
define('OM_MERCHANT_KEY', getenv('ORANGE_MERCHANT_KEY') ?: '');

// MTN MoMo CI
define('MTN_API_URL', 'https://sandbox.momodeveloper.mtn.com/collection');
define('MTN_API_KEY', getenv('MTN_MOMO_KEY') ?: '');

// Moov Money
define('MOOV_API_URL', 'https://api.moov-africa.ci/v1');
define('MOOV_API_KEY', getenv('MOOV_API_KEY') ?: '');

// ── LIVRAISON ───────────────────────────────────
define('FRAIS_LIVRAISON',           2500);    // FCFA
define('FRAIS_LIVRAISON_GRATUITE',  100000);  // Gratuite au-delà de 100k FCFA
define('DEPOT_ABIDJAN_LAT',         5.3599);
define('DEPOT_ABIDJAN_LNG',        -4.0082);

// ── PAGINATION ──────────────────────────────────
define('ITEMS_PER_PAGE', 12);

// ── UPLOADS ────────────────────────────────────
define('MAX_FILE_SIZE',  5 * 1024 * 1024);  // 5 MB
define('ALLOWED_IMG',    ['jpg','jpeg','png','webp','gif']);

// ── DÉMARRAGE SESSION ───────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_name(SESSION_NAME);
    session_start();
}

// ── CORS (API) ──────────────────────────────────
if (!function_exists('setCorsHeaders')) {
    function setCorsHeaders(): void {
        $allowed = APP_ENV === 'development'
            ? 'http://localhost http://127.0.0.1 http://localhost:5500'
            : APP_URL;
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
    }
}
setCorsHeaders();

// ── AUTOLOAD ────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $dirs = [
        BACKEND_PATH . '/classes/',
        BACKEND_PATH . '/models/',
        BACKEND_PATH . '/controllers/',
        BACKEND_PATH . '/middlewares/',
    ];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});

// ── HELPERS ─────────────────────────────────────
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function jsonError(string $message, int $code = 400, array $extra = []): void {
    jsonResponse(array_merge(['success' => false, 'error' => $message], $extra), $code);
}

function jsonSuccess(array $data = [], string $message = 'OK'): void {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function formatFCFA(float $amount): string {
    return number_format($amount, 0, ',', ' ') . ' FCFA';
}

function slugify(string $text): string {
    $text = transliterator_transliterate('Any-Latin; Latin-ASCII', $text);
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9-]+/', '-', $text);
    return trim($text, '-');
}

function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function requireAuth(): void {
    if (!isLoggedIn()) jsonError('Authentification requise', 401);
}

function requireAdminAuth(): void {
    if (!isAdmin()) jsonError('Accès administrateur requis', 403);
}

function getInput(): array {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if ($json !== null) return $json;
    return $_POST;
}

function sanitize(mixed $value): string {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}
