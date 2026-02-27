<?php
// Fichier: /backend/services/RecommandationService.php

class RecommandationService {
    private $comportementModel;
    private $produitModel;
    
    public function __construct() {
        $this->comportementModel = new Comportement();
        $this->produitModel = new Produit();
    }
    
    /**
     * Recommande des produits pour un utilisateur
     */
    public function getRecommendations($userId, $limit = 10) {
        // Stratégie 1: Basée sur l'historique de vues
        $viewedProducts = $this->comportementModel->getMostViewedByUser($userId, 5);
        
        if (empty($viewedProducts)) {
            // Fallback: Produits populaires
            return $this->produitModel->getMostPopular($limit);
        }
        
        // Récupère les catégories des produits vus
        $categoryIds = [];
        foreach ($viewedProducts as $viewed) {
            $product = $this->produitModel->find($viewed['product_id']);
            if ($product) {
                $categoryIds[] = $product['category_id'];
            }
        }
        
        $categoryIds = array_unique($categoryIds);
        
        // Recommande des produits des mêmes catégories
        $db = Database::getInstance()->getConnection();
        
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        
        $sql = "SELECT * FROM products 
                WHERE category_id IN ($placeholders) 
                AND status = 'ACTIVE'
                AND id NOT IN (SELECT product_id FROM product_views WHERE user_id = ?)
                ORDER BY popularity_score DESC, average_rating DESC
                LIMIT ?";
        
        $stmt = $db->prepare($sql);
        
        $params = array_merge($categoryIds, [$userId, $limit]);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Enregistre une action utilisateur
     */
    public function logUserAction($userId, $productId, $actionType, $metadata = []) {
        $sessionId = session_id() ?: bin2hex(random_bytes(16));
        
        return $this->comportementModel->logAction(
            $userId,
            $productId,
            $actionType,
            $sessionId,
            $metadata
        );
    }
}