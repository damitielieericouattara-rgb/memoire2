<?php
// Fichier: /backend/test.php
// Ouvrez cette page dans votre navigateur pour diagnostiquer l'installation
// URL : http://localhost/memoire-corrige/backend/test.php

header('Content-Type: application/json; charset=utf-8');

$tests = [];

// 1. Version PHP
$tests['php_version'] = [
    'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'OK' : 'ERREUR',
    'value'  => PHP_VERSION,
    'requis' => '>= 7.4'
];

// 2. Extension PDO
$tests['pdo'] = [
    'status' => extension_loaded('pdo') ? 'OK' : 'ERREUR',
    'value'  => extension_loaded('pdo') ? 'activé' : 'manquant'
];

// 3. Extension PDO MySQL
$tests['pdo_mysql'] = [
    'status' => extension_loaded('pdo_mysql') ? 'OK' : 'ERREUR',
    'value'  => extension_loaded('pdo_mysql') ? 'activé' : 'manquant'
];

// 4. Connexion BDD
require_once __DIR__ . '/config/database.php';
try {
    $pdo = new PDO(
        DatabaseConfig::getDSN(),
        DatabaseConfig::getConfig()['user'],
        DatabaseConfig::getConfig()['password']
    );
    $tests['database'] = ['status' => 'OK', 'value' => 'Connexion réussie'];
    
    // 5. Tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tests['tables'] = [
        'status' => count($tables) > 0 ? 'OK' : 'ATTENTION',
        'value'  => count($tables) . ' tables trouvées : ' . implode(', ', $tables)
    ];

    // 6. Compte users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    $tests['users'] = [
        'status' => $count > 0 ? 'OK' : 'ATTENTION',
        'value'  => "$count utilisateur(s) dans la base"
    ];

} catch (PDOException $e) {
    $tests['database'] = [
        'status' => 'ERREUR',
        'value'  => $e->getMessage(),
        'aide'   => 'Vérifiez config/database.php (nom BDD, user, mot de passe)'
    ];
}

// 7. mod_rewrite
$tests['mod_rewrite'] = [
    'status' => function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()) ? 'OK' : 'INCONNU',
    'value'  => function_exists('apache_get_modules') 
        ? (in_array('mod_rewrite', apache_get_modules()) ? 'activé' : 'NON activé — le fallback ?route= sera utilisé')
        : 'Impossible de vérifier (normal en CLI/Nginx)',
    'note'   => 'Non critique : le système fonctionne sans mod_rewrite'
];

// 8. Répertoire courant
$tests['chemin'] = [
    'status' => 'INFO',
    'value'  => __DIR__
];

$allOk = !in_array('ERREUR', array_column($tests, 'status'));

echo json_encode([
    'success' => $allOk,
    'message' => $allOk ? 'Tout est OK, le backend devrait fonctionner !' : 'Des erreurs ont été détectées',
    'tests'   => $tests,
    'prochaine_etape' => $allOk 
        ? 'Ouvrez frontend/pages/connexion.html'
        : 'Corrigez les erreurs ci-dessus'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
