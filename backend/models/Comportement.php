<?php
// Fichier: /backend/models/Comportement.php

class Comportement extends Model {
    protected $table = 'user_behaviors';

    public function logAction($userId, $productId, $actionType, $sessionId, $metadata = []) {
        try {
            return $this->create([
                'user_id'     => $userId,
                'product_id'  => $productId,
                'action_type' => $actionType,
                'session_id'  => $sessionId,
                'metadata'    => json_encode($metadata),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            return null; // non bloquant
        }
    }
}
