<?php
// Fichier: /backend/models/Panier.php

class Panier extends Model {
    protected $table = 'cart_items';
    
    /**
     * Récupère le panier d'un utilisateur avec détails produits
     */
    public function getByUserId($userId) {
        $sql = "SELECT c.*, p.name, p.price, p.promo_price, p.stock, 
                       p.status, pi.url as image_url
                FROM {$this->table} c
                JOIN products p ON c.product_id = p.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1
                WHERE c.user_id = :user_id
                ORDER BY c.added_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Ajoute ou met à jour un produit dans le panier
     */
    public function addOrUpdate($userId, $productId, $quantity) {
        // Vérifie si le produit existe déjà
        $existing = $this->where([
            'user_id' => $userId,
            'product_id' => $productId
        ], 1);
        
        if ($existing) {
            // Met à jour la quantité
            return $this->update($existing['id'], [
                'quantity' => $quantity,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Ajoute le produit
            return $this->create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'added_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Vide le panier d'un utilisateur
     */
    public function clearByUserId($userId) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        return $stmt->execute();
    }
}