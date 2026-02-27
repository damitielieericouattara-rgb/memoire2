<?php
// Fichier: /backend/models/Transaction.php

class Transaction extends Model {
    protected $table = 'transactions';
    
    /**
     * Récupère les transactions d'un wallet
     */
    public function getByWalletId($walletId, $limit = 50) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE wallet_id = :wallet_id 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':wallet_id', $walletId);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}