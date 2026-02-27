<?php
// Fichier: /backend/services/NotificationService.php

class NotificationService {
    public function send($userId, $data) {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "INSERT INTO notifications (user_id, type, title, message, channel, is_read, sent_at)
                 VALUES (?, ?, ?, ?, ?, 0, NOW())"
            );
            $stmt->execute([
                $userId,
                $data['type'] ?? 'SYSTEM',
                $data['title'] ?? 'Notification',
                $data['message'] ?? '',
                $data['channel'] ?? 'WEBSOCKET',
            ]);
            return $db->lastInsertId();
        } catch (Exception $e) {
            return null; // non bloquant
        }
    }

    public function notifyOrderStatus($userId, $orderNumber, $status) {
        $msgs = [
            'RECEIVED'    => 'Votre commande a été confirmée',
            'PREPARING'   => 'Votre commande est en cours de préparation',
            'SHIPPED'     => 'Votre commande a été expédiée',
            'IN_DELIVERY' => 'Votre commande est en cours de livraison',
            'DELIVERED'   => 'Votre commande a été livrée',
        ];
        return $this->send($userId, [
            'type'    => 'ORDER',
            'title'   => "Commande $orderNumber",
            'message' => $msgs[$status] ?? 'Mise à jour de votre commande',
            'channel' => 'WEBSOCKET',
        ]);
    }
}
