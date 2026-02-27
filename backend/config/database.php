<?php
// Fichier: /backend/config/database.php
// ⚠️ MODIFIEZ selon votre configuration XAMPP

class DatabaseConfig {
    // Si diagnostic.php indique "127.0.0.1:3307" → mettez '127.0.0.1' et 3307
    private static $host    = '127.0.0.1';   // ← essayez '127.0.0.1' si 'localhost' échoue
    private static $port    = 3307;           // ← mettez 3307 si le scan l'indique
    private static $dbname  = 'memoire_ecommerce_intelligent';
    private static $user    = 'root';
    private static $pass    = '';             // ← votre mot de passe MySQL si vous en avez un
    private static $charset = 'utf8mb4';

    public static function getConfig(): array {
        return [
            'host'     => $_ENV['DB_HOST'] ?? self::$host,
            'port'     => (int)($_ENV['DB_PORT'] ?? self::$port),
            'dbname'   => $_ENV['DB_NAME'] ?? self::$dbname,
            'user'     => $_ENV['DB_USER'] ?? self::$user,
            'password' => $_ENV['DB_PASS'] ?? self::$pass,
            'charset'  => self::$charset,
            'options'  => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        ];
    }

    public static function getDSN(): string {
        $c = self::getConfig();
        return "mysql:host={$c['host']};port={$c['port']};dbname={$c['dbname']};charset={$c['charset']}";
    }
}
