<?php
// Fichier: /backend/models/Notification.php

class Notification extends Model {
    protected $table = 'notifications';
    
    /**
     * Récupère les notifications d'un utilisateur
     */
    public function getByUserId($userId, $unreadOnly = false) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
        
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        
        $sql .= " ORDER BY sent_at DESC LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Marque une notification comme lue
     */
    public function markAsRead($notificationId) {
        return $this->update($notificationId, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Marque toutes les notifications d'un utilisateur comme lues
     */
    public function markAllAsRead($userId) {
        $sql = "UPDATE {$this->table} 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = :user_id AND is_read = 0";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId);
        return $stmt->execute();
    }
}