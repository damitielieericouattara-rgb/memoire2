<?php
// Fichier: /backend/middleware/CorsMiddleware.php

class CorsMiddleware extends Middleware {
    public function handle() {
        // ─── CORRECTION BUG CORS ──────────────────────────────────────────────
        // On ne peut PAS combiner "Allow-Origin: *" avec "Allow-Credentials: true"
        // → Le navigateur bloque la requête (erreur CORS)
        // Solution : renvoyer l'origine exacte du demandeur si elle est autorisée

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (AppConfig::$appEnv === 'development') {
            // En développement : accepte toutes les origines localhost/127.0.0.1
            if (empty($origin) || preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin)) {
                $allowedOrigin = empty($origin) ? '*' : $origin;
            } else {
                $allowedOrigin = $origin; // accepte tout en dev
            }
        } else {
            // En production : liste blanche stricte
            $whitelist = AppConfig::$corsOrigins;
            $allowedOrigin = in_array($origin, $whitelist) ? $origin : ($whitelist[0] ?? '');
        }

        header("Access-Control-Allow-Origin: $allowedOrigin");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");

        // Traite les requêtes OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}
