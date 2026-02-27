<?php
// Fichier: /backend/models/OrderTracking.php

class OrderTracking extends Model {
    protected $table = 'order_tracking';
    
    /**
     * Ajoute un événement de tracking
     */
    public function addEvent($orderId, $status, $description, $coords = null) {
        $data = [
            'order_id' => $orderId,
            'status' => $status,
            'description' => $description,
            'event_date' => date('Y-m-d H:i:s')
        ];
        
        if ($coords) {
            $data['latitude'] = $coords['latitude'];
            $data['longitude'] = $coords['longitude'];
        }
        
        return $this->create($data);
    }
}