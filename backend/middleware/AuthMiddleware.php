<?php
// Fichier: /backend/middleware/AuthMiddleware.php

require_once __DIR__ . '/../services/AuthService.php';

class AuthMiddleware extends Middleware {
    public function handle() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            Response::error('Token manquant', 401);
        }
        
        // Extrait le token (Bearer xxxxx)
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            Response::error('Format de token invalide', 401);
        }
        
        $token = $matches[1];
        
        // Vérifie le token
        $authService = new AuthService();
        $payload = $authService->verifyToken($token);
        
        if (!$payload) {
            Response::error('Token invalide ou expiré', 401);
        }
        
        // Charge l'utilisateur
        $userModel = new User();
        $user = $userModel->find($payload['user_id']);
        
        if (!$user || $user['status'] !== 'ACTIVE') {
            Response::error('Utilisateur non trouvé ou inactif', 401);
        }
        
        // Retourne l'utilisateur pour le contrôleur
        return $user;
    }
}