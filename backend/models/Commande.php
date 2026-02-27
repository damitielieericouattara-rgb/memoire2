<?php
// Fichier: /backend/models/Commande.php

class Commande extends Model {
    protected $table = 'orders';

    public function generateOrderNumber(): string {
        $year = date('Y');
        $sql  = "SELECT COUNT(*) as cnt FROM {$this->table} WHERE YEAR(created_at) = :year";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':year', $year);
        $stmt->execute();
        $row    = $stmt->fetch();
        $number = str_pad((int)$row['cnt'] + 1, 5, '0', STR_PAD_LEFT);
        return "CMD-{$year}-{$number}";
    }

    public function createWithItems(array $orderData, array $items): int {
        Database::getInstance()->beginTransaction();
        try {
            $orderData['order_number'] = $this->generateOrderNumber();
            $orderData['created_at']   = date('Y-m-d H:i:s');
            $orderId = $this->create($orderData);

            $itemModel    = new OrderItem();
            $produitModel = new Produit();

            foreach ($items as $item) {
                $itemModel->create([
                    'order_id'     => $orderId,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $item['unit_price'],
                    'discount'     => $item['discount'] ?? 0,
                    'subtotal'     => $item['subtotal'],
                ]);
                $produitModel->decrementStock($item['product_id'], $item['quantity']);
            }

            // Premier tracking
            try {
                $trackModel = new OrderTracking();
                $trackModel->create([
                    'order_id'    => $orderId,
                    'status'      => 'RECEIVED',
                    'description' => 'Commande confirmée',
                    'event_date'  => date('Y-m-d H:i:s'),
                ]);
            } catch (Exception $e) {} // non bloquant si table absente

            Database::getInstance()->commit();
            return $orderId;
        } catch (Exception $e) {
            Database::getInstance()->rollBack();
            throw $e;
        }
    }

    /** Commandes d'un user avec les items inclus */
    public function getByUserIdWithItems(int $userId): array {
        $sql  = "SELECT * FROM {$this->table} WHERE user_id = :uid ORDER BY created_at DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchAll();

        // Enrichit avec items
        foreach ($orders as &$order) {
            $sql2  = "SELECT * FROM order_items WHERE order_id = :oid";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bindValue(':oid', $order['id'], PDO::PARAM_INT);
            $stmt2->execute();
            $order['items'] = $stmt2->fetchAll();
        }
        return $orders;
    }

    public function getByUserId($userId, $limit = 50): array {
        return $this->getByUserIdWithItems((int)$userId);
    }

    public function getWithDetails($orderId): ?array {
        $order = $this->find($orderId);
        if (!$order) return null;

        $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = :oid");
        $stmt->bindValue(':oid', $orderId, PDO::PARAM_INT);
        $stmt->execute();
        $order['items'] = $stmt->fetchAll();

        try {
            $stmt2 = $this->db->prepare("SELECT * FROM order_tracking WHERE order_id = :oid ORDER BY event_date ASC");
            $stmt2->bindValue(':oid', $orderId, PDO::PARAM_INT);
            $stmt2->execute();
            $order['tracking'] = $stmt2->fetchAll();
        } catch (Exception $e) {
            $order['tracking'] = [];
        }

        return $order;
    }
}
