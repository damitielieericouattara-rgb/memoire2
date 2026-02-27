<?php
// Fichier: /backend/core/Controller.php

abstract class Controller {
    protected $user;
    
    /**
     * Récupère les données JSON de la requête
     */
    protected function getJsonInput() {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }
    
    /**
     * Récupère les données POST
     */
    protected function getPostData() {
        return $_POST;
    }
    
    /**
     * Récupère les paramètres GET
     */
    protected function getQueryParams() {
        return $_GET;
    }
    
    /**
     * Récupère un paramètre de la requête
     */
    protected function getParam($key, $default = null) {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }
        
        $input = $this->getJsonInput();
        return $input[$key] ?? $default;
    }
    
    /**
     * Valide les données requises
     */
    protected function validateRequired($data, $required) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            Response::error('Champs manquants : ' . implode(', ', $missing), 400);
        }
        
        return true;
    }
    
    /**
     * Nettoie les données d'entrée
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Définit l'utilisateur courant (après auth)
     */
    public function setUser($user) {
        $this->user = $user;
    }
    
    /**
     * Récupère l'utilisateur courant
     */
    protected function getUser() {
        return $this->user;
    }
    
    /**
     * Vérifie si l'utilisateur a un rôle
     */
    protected function hasRole($role) {
        return $this->user && $this->user['role'] === $role;
    }
}