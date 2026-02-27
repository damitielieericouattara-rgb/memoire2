<?php
// Fichier: /backend/middleware/RoleMiddleware.php

class RoleMiddleware extends Middleware {
    private $allowedRoles;
    
    public function __construct($allowedRoles = []) {
        $this->allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    }
    
    public function handle() {
        // Récupère l'utilisateur depuis le header ou la session
        // (Dans un système réel, il faudrait le récupérer depuis le token JWT)
        
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (empty($authHeader)) {
            Response::error('Non autorisé', 403);
        }
        
        preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches);
        $token = $matches[1] ?? '';
        
        $authService = new AuthService();
        $payload = $authService->verifyToken($token);
        
        $userModel = new User();
        $user = $userModel->find($payload['user_id']);
        
        if (!in_array($user['role'], $this->allowedRoles)) {
            Response::error('Accès refusé : privilèges insuffisants', 403);
        }
    }
}