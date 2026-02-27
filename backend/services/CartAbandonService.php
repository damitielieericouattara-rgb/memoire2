<?php
// Fichier: /backend/services/CartAbandonService.php
// ─── SYSTÈME ANTI-ABANDON PANIER ─────────────────────────────

class CartAbandonService {
    private $db;
    private $emailService;
    private $notifService;

    public function __construct() {
        $this->db           = Database::getInstance()->getConnection();
        $this->emailService = new EmailService();
        $this->notifService = new NotificationService();
    }

    /**
     * Détecte et traite les paniers abandonnés
     * À appeler via un cron job : */30 * * * * php cron.php abandonedCarts
     */
    public function processAbandonedCarts() {
        $delayMinutes = CART_ABANDON_DELAY;

        // Trouve les utilisateurs avec panier non commandé depuis X minutes
        $stmt = $this->db->prepare(
            "SELECT ci.user_id, u.email, u.name,
                    SUM(ci.quantity * p.price) as total,
                    MIN(ci.updated_at) as last_activity,
                    COUNT(ci.id) as item_count
             FROM cart_items ci
             JOIN users u ON ci.user_id = u.id
             JOIN products p ON ci.product_id = p.id
             WHERE ci.updated_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)
             AND ci.user_id NOT IN (
                SELECT user_id FROM cart_abandonment
                WHERE abandoned_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                AND email_sent_at IS NOT NULL
             )
             GROUP BY ci.user_id
             HAVING total > 0"
        );
        $stmt->execute([$delayMinutes]);
        $abandonedCarts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($abandonedCarts as $cart) {
            $this->handleAbandon($cart);
        }

        return count($abandonedCarts);
    }

    /**
     * Traite un abandon spécifique
     */
    private function handleAbandon($cart) {
        $userId = $cart['user_id'];

        // Snapshot du panier
        $items   = $this->getCartItems($userId);
        $snap    = json_encode($items);
        $total   = (float)$cart['total'];

        // Calcule remise si retour
        $discount = CART_RECOVER_DISCOUNT;

        // Enregistre l'abandon
        $aId = $this->db->prepare(
            "INSERT INTO cart_abandonment (user_id, cart_snapshot, total_amount, discount_offered)
             VALUES (?,?,?,?)"
        );
        $aId->execute([$userId, $snap, $total, $discount]);
        $abandonId = (int)$this->db->lastInsertId();

        // Notification in-app
        $this->notifService->send([
            'user_id' => $userId,
            'type'    => 'CART_ABANDON',
            'title'   => '🛒 Votre panier vous attend !',
            'message' => "Vous avez des articles dans votre panier. Revenez et profitez de -{$discount}% !",
            'channel' => 'WEBSOCKET',
        ]);

        // Email avec remise
        $this->emailService->sendCartAbandon([
            'email'    => $cart['email'],
            'name'     => $cart['name'],
            'items'    => $items,
            'total'    => $total,
            'discount' => $discount,
        ]);

        // Met à jour email_sent_at
        $this->db->prepare(
            "UPDATE cart_abandonment SET email_sent_at = NOW() WHERE id = ?"
        )->execute([$abandonId]);
    }

    /**
     * Récupère les articles du panier avec détails
     */
    private function getCartItems($userId) {
        $stmt = $this->db->prepare(
            "SELECT ci.*, p.name, p.price, p.promo_price, pi.url as image
             FROM cart_items ci
             JOIN products p ON ci.product_id = p.id
             LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
             WHERE ci.user_id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Marque un panier comme récupéré (quand l'utilisateur revient)
     */
    public function markRecovered($userId) {
        $this->db->prepare(
            "UPDATE cart_abandonment SET recovered = 1, recovered_at = NOW()
             WHERE user_id = ? AND recovered = 0"
        )->execute([$userId]);
    }
}
