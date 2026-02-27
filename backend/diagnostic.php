<?php
header('Content-Type: application/json; charset=utf-8');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");

$results = [];

// ─── 1. PHP
$results['php_version'] = [
    'ok'    => version_compare(PHP_VERSION, '8.0', '>='),
    'value' => PHP_VERSION,
];

// ─── 2. PDO MySQL
$results['pdo_mysql'] = [
    'ok'      => extension_loaded('pdo_mysql'),
    'message' => extension_loaded('pdo_mysql') ? 'OK' : 'MANQUANT',
];

// ─── 3. Scan ports MySQL
$candidates = [
    ['host' => '127.0.0.1', 'port' => 3306],
    ['host' => '127.0.0.1', 'port' => 3307],
    ['host' => 'localhost',  'port' => 3306],
    ['host' => 'localhost',  'port' => 3307],
];

$workingHost = null;
$workingPort = null;
$scanErrors  = [];

foreach ($candidates as $c) {
    try {
        $testDsn = "mysql:host={$c['host']};port={$c['port']};charset=utf8mb4";
        new PDO($testDsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $workingHost = $c['host'];
        $workingPort = $c['port'];
        break;
    } catch (PDOException $e) {
        $scanErrors["{$c['host']}:{$c['port']}"] = substr($e->getMessage(), 0, 80);
    }
}

$results['mysql_scan'] = [
    'ok'      => $workingHost !== null,
    'working' => $workingHost ? "{$workingHost}:{$workingPort}" : null,
    'errors'  => $scanErrors,
];

// ─── 4. Connexion BDD
$dbname = 'memoire_ecommerce_intelligent';
$pdo = null;

if ($workingHost) {
    try {
        $dsn = "mysql:host={$workingHost};port={$workingPort};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $results['database'] = ['ok' => true, 'message' => "Connexion OK a '{$dbname}'"];
    } catch (PDOException $e) {
        $results['database'] = [
            'ok'  => false,
            'raw' => $e->getMessage(),
            'fix' => str_contains($e->getMessage(), 'Unknown database')
                ? "Creez la base '{$dbname}' dans phpMyAdmin puis importez database/schema.sql"
                : "Verifiez vos identifiants MySQL",
        ];
    }
}

// ─── 5. Tables (noms réels utilisés par le code)
if ($pdo) {
    $required = ['users', 'products', 'orders', 'order_items', 'cart_items', 'wallets', 'transactions', 'notifications', 'order_tracking'];
    $existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $missing  = array_values(array_diff($required, $existing));
    $results['tables'] = [
        'ok'      => empty($missing),
        'found'   => $existing,
        'missing' => $missing,
        'fix'     => empty($missing) ? 'OK' : "Tables manquantes — importez database/schema.sql",
    ];
}

// ─── 6. Test login réel
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        $results['users_count'] = ['ok' => true, 'count' => (int)$count, 'message' => "$count utilisateur(s) en base"];
    } catch (PDOException $e) {
        $results['users_count'] = ['ok' => false, 'message' => $e->getMessage()];
    }
}

// ─── 7. Config recommandée
$results['config_a_utiliser'] = [
    'host'   => $workingHost ?? '127.0.0.1',
    'port'   => $workingPort ?? 3307,
    'dbname' => $dbname,
    'user'   => 'root',
    'pass'   => '',
    'note'   => 'Ces valeurs sont dans config/database.php',
];

$allOk = ($results['php_version']['ok'])
      && ($results['pdo_mysql']['ok'])
      && ($results['mysql_scan']['ok'])
      && ($results['database']['ok'] ?? false)
      && ($results['tables']['ok'] ?? false);

echo json_encode([
    'status'  => $allOk ? 'OK - pret a fonctionner' : 'ERREURS DETECTEES',
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
