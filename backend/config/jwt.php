<?php
// Fichier: /backend/config/jwt.php

class JWTConfig {
    // IMPORTANT: Changer ces clés en production
    private const SECRET_KEY = 'votre_cle_secrete_super_longue_et_complexe_12345';
    private const REFRESH_SECRET_KEY = 'votre_cle_refresh_secrete_encore_plus_longue_67890';
    
    // Durées de vie des tokens
    private const ACCESS_TOKEN_LIFETIME = 900; // 15 minutes
    private const REFRESH_TOKEN_LIFETIME = 604800; // 7 jours
    
    // Algorithme
    private const ALGORITHM = 'HS256';
    
    public static function getSecretKey() {
        return self::SECRET_KEY;
    }
    
    public static function getRefreshSecretKey() {
        return self::REFRESH_SECRET_KEY;
    }
    
    public static function getAccessTokenLifetime() {
        return self::ACCESS_TOKEN_LIFETIME;
    }
    
    public static function getRefreshTokenLifetime() {
        return self::REFRESH_TOKEN_LIFETIME;
    }
    
    public static function getAlgorithm() {
        return self::ALGORITHM;
    }
}