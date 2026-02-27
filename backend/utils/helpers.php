<?php
// Fichier: /backend/utils/helpers.php

/**
 * Fonctions utilitaires globales
 */

/**
 * Formate un montant en XOF
 */
function formatCurrency(float $amount, string $currency = 'XOF'): string
{
    return number_format($amount, 0, '.', ' ') . ' ' . $currency;
}

/**
 * Génère un token sécurisé
 */
function generateToken(int $length = 32): string
{
    return bin2hex(random_bytes($length));
}

/**
 * Sanitize une chaîne
 */
function sanitizeString(string $str): string
{
    return htmlspecialchars(strip_tags(trim($str)), ENT_QUOTES, 'UTF-8');
}

/**
 * Anonymise une adresse IP (RGPD)
 */
function anonymizeIp(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return preg_replace('/\.\d+$/', '.0', $ip);
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return preg_replace('/:[0-9a-f]+$/', ':0', $ip);
    }
    return $ip;
}

/**
 * Retourne l'IP réelle du client
 */
function getClientIp(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = trim(explode(',', $_SERVER[$header])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Génère un slug URL-friendly
 */
function slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $chars = [
        'à'=>'a','á'=>'a','â'=>'a','ä'=>'a','ã'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','ö'=>'o','õ'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ñ'=>'n','ç'=>'c','ß'=>'ss'
    ];
    $text = strtr($text, $chars);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Valide un email
 */
function validateEmail(string $email): bool
{
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Valide un numéro de téléphone ivoirien
 */
function validatePhone(string $phone): bool
{
    return (bool)preg_match('/^(\+225|00225)?[0-9]{10}$/', preg_replace('/[\s\-]/', '', $phone));
}

/**
 * Calcule la TVA
 */
function calculateTva(float $amountHt, float $rate = 0.18): float
{
    return round($amountHt * $rate, 2);
}

/**
 * Calcule le montant TTC
 */
function calculateTtc(float $amountHt, float $tva, float $shipping = 0): float
{
    return round($amountHt + $tva + $shipping, 2);
}

/**
 * Log applicatif simple
 */
function appLog(string $level, string $message, array $context = []): void
{
    $logDir  = dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);

    $logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
    $line    = sprintf(
        "[%s] %s: %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Retourne un message d'erreur formaté
 */
function errorResponse(string $message, int $code = 400): array
{
    return ['success' => false, 'message' => $message, 'code' => $code];
}

/**
 * Vérifie si la requête est en HTTPS
 */
function isHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
}