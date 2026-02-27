<?php
// Fichier: /backend/middleware/RateLimitMiddleware.php

class RateLimitMiddleware extends Middleware {
    private $cacheFile = __DIR__ . '/../../storage/rate_limit_cache.json';
    
    public function handle() {
        $ip = $_SERVER['REMOTE_ADDR'];
        $now = time();
        
        // Charge le cache
        $cache = $this->loadCache();
        
        // Nettoie les anciennes entrées
        $cache = array_filter($cache, function($entry) use ($now) {
            return ($now - $entry['timestamp']) < 60; // Fenêtre de 1 minute
        });
        
        // Compte les requêtes de cette IP
        $ipRequests = array_filter($cache, function($entry) use ($ip) {
            return $entry['ip'] === $ip;
        });
        
        if (count($ipRequests) >= AppConfig::get('RATE_LIMIT_REQUESTS')) {
            Response::error('Trop de requêtes. Réessayez dans 1 minute.', 429);
        }
        
        // Ajoute cette requête
        $cache[] = [
            'ip' => $ip,
            'timestamp' => $now
        ];
        
        // Sauvegarde le cache
        $this->saveCache($cache);
    }
    
    private function loadCache() {
        if (!file_exists($this->cacheFile)) {
            return [];
        }
        
        $content = file_get_contents($this->cacheFile);
        return json_decode($content, true) ?: [];
    }
    
    private function saveCache($cache) {
        $dir = dirname($this->cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($this->cacheFile, json_encode($cache));
    }
}