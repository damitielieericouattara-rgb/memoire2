<?php
// Fichier: /backend/config/app.php

class AppConfig
{
    // ---- Application ----
    public static string $appName    = 'E-Commerce Intelligent';
    public static string $appEnv     = 'development';
    public static string $appUrl     = 'http://localhost:8000';
    public static bool   $debug      = true;

    // ---- JWT ----
    public static string $jwtSecret          = 'changez_moi_avec_une_vraie_valeur_aleatoire';
    public static int    $jwtExpire          = 900;        // 15 min
    public static int    $jwtRefreshExpire   = 604800;     // 7 jours

    // ---- Email ----
    public static string $mailHost       = 'smtp.mailtrap.io';
    public static int    $mailPort       = 2525;
    public static string $mailUser       = '';
    public static string $mailPass       = '';
    public static string $mailFrom       = 'noreply@ecommerce.ci';
    public static string $mailFromName   = 'E-Commerce Intelligent';

    // ---- Stockage ----
    public static string $storagePath   = '/storage';
    public static int    $maxUploadSize = 5242880; // 5 Mo

    // ---- Sécurité ----
    public static int    $rateLimitRequests = 60;
    public static int    $rateLimitWindow   = 60;
    public static int    $bcryptCost        = 12;
    public static array  $corsOrigins       = [
        'http://localhost',
        'http://localhost:3000',
        'http://localhost:8080',
        'http://127.0.0.1',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        // Ajoutez ici l'URL exacte de votre frontend si différente
    ];

    /**
     * Initialise la config depuis .env si disponible
     */
    public static function load(): void
    {
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (!file_exists($envFile)) return;

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (!$key) continue;
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
        }

        // Application
        self::$appEnv   = $_ENV['APP_ENV']   ?? self::$appEnv;
        self::$appUrl   = $_ENV['APP_URL']   ?? self::$appUrl;
        self::$debug    = ($_ENV['APP_DEBUG'] ?? 'true') === 'true';

        // JWT
        self::$jwtSecret        = $_ENV['JWT_SECRET']         ?? self::$jwtSecret;
        self::$jwtExpire        = (int)($_ENV['JWT_EXPIRE']    ?? self::$jwtExpire);
        self::$jwtRefreshExpire = (int)($_ENV['JWT_REFRESH_EXPIRE'] ?? self::$jwtRefreshExpire);

        // Email
        self::$mailHost     = $_ENV['MAIL_HOST']      ?? self::$mailHost;
        self::$mailPort     = (int)($_ENV['MAIL_PORT'] ?? self::$mailPort);
        self::$mailUser     = $_ENV['MAIL_USER']      ?? self::$mailUser;
        self::$mailPass     = $_ENV['MAIL_PASS']      ?? self::$mailPass;
        self::$mailFrom     = $_ENV['MAIL_FROM']      ?? self::$mailFrom;
        self::$mailFromName = $_ENV['MAIL_FROM_NAME'] ?? self::$mailFromName;

        // Sécurité
        self::$bcryptCost        = (int)($_ENV['BCRYPT_COST']          ?? self::$bcryptCost);
        self::$rateLimitRequests = (int)($_ENV['RATE_LIMIT_REQUESTS']  ?? self::$rateLimitRequests);
        self::$rateLimitWindow   = (int)($_ENV['RATE_LIMIT_WINDOW']    ?? self::$rateLimitWindow);
        self::$corsOrigins       = isset($_ENV['CORS_ORIGINS'])
            ? array_map('trim', explode(',', $_ENV['CORS_ORIGINS']))
            : self::$corsOrigins;
    }
}

// Charge automatiquement au require
AppConfig::load();