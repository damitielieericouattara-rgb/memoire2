<?php
/**
 * SneakX — Modèle Commande
 * /backend/models/Order.php
 */

class Order extends Model {
    protected string $table = 'orders';

    /**
     * Créer une commande avec ses articles
     */
    public function createWithItems(array $orderData, array $items): int {
        $this->db->beginTransaction();
        try {
            // Insérer la commande
            $orderId = $this->insert([
                'user_id'             => $orderData['user_id'],
                'numero'              => '',   // géré par trigger
                'statut'              => 'en_attente',
                'total_ht'            => $orderData['total_ht'],
                'frais_livraison'     => $orderData['frais_livraison'],
                'remise'              => $orderData['remise'] ?? 0,
                'total_ttc'           => $orderData['total_ttc'],
                'methode_paiement'    => $orderData['methode_paiement'],
                'adresse_livraison'   => $orderData['adresse_livraison'] ?? null,
                'ville'               => $orderData['ville'] ?? null,
                'quartier'            => $orderData['quartier'] ?? null,
                'telephone_livraison' => $orderData['telephone_livraison'] ?? null,
                'latitude'            => $orderData['latitude'] ?? null,
                'longitude'           => $orderData['longitude'] ?? null,
                'code_promo'          => $orderData['code_promo'] ?? null,
                'note_client'         => $orderData['note_client'] ?? null,
            ]);

            // Insérer les articles
            foreach ($items as $item) {
                $stmt = $this->db->prepare(
                    "INSERT INTO order_items (order_id, product_id, nom_produit, prix_unit, quantite, taille, couleur, sous_total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['nom_produit'],
                    $item['prix_unit'],
                    $item['quantite'],
                    $item['taille'] ?? null,
                    $item['couleur'] ?? null,
                    $item['prix_unit'] * $item['quantite'],
                ]);
            }

            // Créer l'entrée livraison
            $stmt = $this->db->prepare(
                "INSERT INTO deliveries (order_id, latitude_dest, longitude_dest, adresse_dest)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $orderId,
                $orderData['latitude'] ?? null,
                $orderData['longitude'] ?? null,
                $orderData['adresse_livraison'] ?? null,
            ]);

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Commandes d'un utilisateur
     */
    public function byUser(int $userId, int $page = 1): array {
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $orders = $this->query(
            "SELECT o.*,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS nb_articles
             FROM orders o
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );

        // Ajouter les articles à chaque commande
        foreach ($orders as &$order) {
            $order['items'] = $this->query(
                "SELECT oi.*, p.images FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 WHERE oi.order_id = ?",
                [$order['id']]
            );
            // Image principale
            foreach ($order['items'] as &$item) {
                $imgs = json_decode($item['images'] ?? '[]', true);
                $item['image'] = $imgs[0] ?? '';
                unset($item['images']);
            }
        }

        $total = $this->count('user_id = ?', [$userId]);

        return [
            'items'        => $orders,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Détail d'une commande avec livraison
     */
    public function detail(int $orderId, ?int $userId = null): ?array {
        $sql    = "SELECT o.*, u.nom, u.prenom, u.email, u.telephone FROM orders o JOIN users u ON u.id = o.user_id WHERE o.id = ?";
        $params = [$orderId];
        if ($userId) { $sql .= ' AND o.user_id = ?'; $params[] = $userId; }

        $order = $this->queryOne($sql, $params);
        if (!$order) return null;

        $order['items'] = $this->query(
            "SELECT oi.*, p.images FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?",
            [$orderId]
        );
        foreach ($order['items'] as &$item) {
            $imgs = json_decode($item['images'] ?? '[]', true);
            $item['image'] = $imgs[0] ?? '';
            unset($item['images']);
        }

        $order['livraison'] = $this->queryOne(
            "SELECT d.*, u.nom AS livreur_nom, u.prenom AS livreur_prenom, u.telephone AS livreur_tel
             FROM deliveries d
             LEFT JOIN users u ON u.id = d.livreur_id
             WHERE d.order_id = ?",
            [$orderId]
        );

        $order['paiements'] = $this->query(
            "SELECT * FROM payments WHERE order_id = ?",
            [$orderId]
        );

        return $order;
    }

    /**
     * Statistiques dashboard admin
     */
    public function statsAdmin(): array {
        $today = date('Y-m-d');

        return [
            'commandes_jour'   => (int) $this->queryValue(
                "SELECT COUNT(*) FROM orders WHERE DATE(created_at) = ?", [$today]
            ),
            'ca_jour'          => (float) $this->queryValue(
                "SELECT COALESCE(SUM(total_ttc),0) FROM orders WHERE DATE(created_at) = ? AND statut != 'annulee'", [$today]
            ),
            'ca_mois'          => (float) $this->queryValue(
                "SELECT COALESCE(SUM(total_ttc),0) FROM orders WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) AND statut != 'annulee'"
            ),
            'nouveaux_users'   => (int) $this->queryValue(
                "SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?", [$today]
            ),
            'produits_alerte'  => (int) $this->queryValue(
                "SELECT COUNT(*) FROM products WHERE stock < 5 AND is_active = 1"
            ),
        ];
    }

    /**
     * Ventes des 30 derniers jours (pour graphique)
     */
    public function ventesParJour(int $jours = 30): array {
        return $this->query(
            "SELECT DATE(created_at) AS date,
                    COUNT(*) AS nb_commandes,
                    COALESCE(SUM(total_ttc), 0) AS total
             FROM orders
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
               AND statut != 'annulee'
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            [$jours]
        );
    }

    /**
     * Top produits vendus
     */
    public function topProduits(int $limit = 5): array {
        return $this->query(
            "SELECT oi.product_id, oi.nom_produit,
                    SUM(oi.quantite) AS total_quantite,
                    SUM(oi.sous_total) AS total_ca,
                    p.images
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             GROUP BY oi.product_id, oi.nom_produit, p.images
             ORDER BY total_quantite DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Mettre à jour le statut
     */
    public function updateStatut(int $orderId, string $statut): bool {
        return $this->update($orderId, ['statut' => $statut]);
    }

    /**
     * Sauvegarder QR code
     */
    public function saveQrCode(int $orderId, string $qrData): bool {
        return $this->update($orderId, ['qr_code' => $qrData]);
    }

    /**
     * Commandes admin (liste paginée)
     */
    public function adminList(array $filters = [], int $page = 1): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['statut'])) {
            $where[]  = 'o.statut = ?';
            $params[] = $filters['statut'];
        }
        if (!empty($filters['q'])) {
            $where[]  = '(o.numero LIKE ? OR CONCAT(u.nom," ",u.prenom) LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $whereStr = implode(' AND ', $where);
        $perPage  = 20;
        $offset   = ($page - 1) * $perPage;

        $orders = $this->query(
            "SELECT o.*, u.nom, u.prenom, u.email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             WHERE {$whereStr}
             ORDER BY o.created_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON u.id = o.user_id WHERE {$whereStr}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        return [
            'items'        => $orders,
            'total'        => $total,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }
}
