<?php
// Fichier: /backend/models/Produit.php

class Produit extends Model {
    protected $table = 'products';
    
    /**
     * Recherche de produits avec filtres
     */
    public function search($params) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        
        $bindings = [];
        
        // Recherche textuelle
        if (!empty($params['q'])) {
            $sql .= " AND (p.name LIKE :search OR p.short_description LIKE :search)";
            $bindings[':search'] = '%' . $params['q'] . '%';
        }
        
        // Filtre par catégorie
        if (!empty($params['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $bindings[':category_id'] = $params['category_id'];
        }
        
        // Filtre par prix
        if (isset($params['min_price'])) {
            $sql .= " AND p.price >= :min_price";
            $bindings[':min_price'] = $params['min_price'];
        }
        
        if (isset($params['max_price'])) {
            $sql .= " AND p.price <= :max_price";
            $bindings[':max_price'] = $params['max_price'];
        }
        
        // Filtre par statut
        if (!empty($params['status'])) {
            $sql .= " AND p.status = :status";
            $bindings[':status'] = $params['status'];
        } else {
            $sql .= " AND p.status = 'ACTIVE'";
        }
        
        // Tri
        $orderBy = $params['sort'] ?? 'created_at';
        $orderDir = $params['order'] ?? 'DESC';
        $sql .= " ORDER BY p.$orderBy $orderDir";
        
        // Pagination
        $page = $params['page'] ?? 1;
        $perPage = $params['per_page'] ?? 20;
        $offset = ($page - 1) * $perPage;
        
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère un produit avec ses images
     */
    public function findWithImages($id) {
        $product = $this->find($id);
        
        if (!$product) {
            return null;
        }
        
        // Récupère les images
        $sql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY display_order, is_main DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':product_id', $id);
        $stmt->execute();
        
        $product['images'] = $stmt->fetchAll();
        
        return $product;
    }
    
    /**
     * Décrémente le stock
     */
    public function decrementStock($productId, $quantity) {
        $sql = "UPDATE {$this->table} 
                SET stock = stock - :quantity 
                WHERE id = :id AND stock >= :quantity";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $productId);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        
        return $stmt->execute() && $stmt->rowCount() > 0;
    }
    
    /**
     * Incrémente le score de popularité
     */
    public function incrementPopularity($productId) {
        $sql = "UPDATE {$this->table} SET popularity_score = popularity_score + 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $productId);
        return $stmt->execute();
    }
    
    /**
     * Produits les plus populaires
     */
    public function getMostPopular($limit = 10) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'ACTIVE' 
                ORDER BY popularity_score DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}