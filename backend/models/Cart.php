<?php
/**
 * SneakX — Modèle Panier
 * /backend/models/Cart.php
 */

class Cart extends Model {
    protected string $table = 'cart';

    /**
     * Récupérer le panier d'un utilisateur ou session
     */
    public function getCart(int $userId = 0, string $sessionId = ''): array {
        $where  = $userId ? 'c.user_id = ?' : 'c.session_id = ?';
        $param  = $userId ?: $sessionId;

        $items = $this->query(
            "SELECT c.*,
                    p.nom, p.slug, p.marque, p.prix, p.prix_promo,
                    p.stock, p.images,
                    COALESCE(p.prix_promo, p.prix) AS prix_effectif
             FROM cart c
             JOIN products p ON p.id = c.product_id
             WHERE {$where} AND p.is_active = 1
             ORDER BY c.created_at DESC",
            [$param]
        );

        // Decode images
        $total = 0;
        foreach ($items as &$item) {
            $imgs = json_decode($item['images'] ?? '[]', true);
            $item['image'] = $imgs[0] ?? '';
            unset($item['images']);
            $item['sous_total'] = $item['prix_effectif'] * $item['quantite'];
            $total += $item['sous_total'];
        }

        $fraisLivraison = $total >= FRAIS_LIVRAISON_GRATUITE ? 0 : FRAIS_LIVRAISON;

        return [
            'items'           => $items,
            'nb_articles'     => array_sum(array_column($items, 'quantite')),
            'sous_total'      => $total,
            'frais_livraison' => $fraisLivraison,
            'total'           => $total + $fraisLivraison,
        ];
    }

    /**
     * Ajouter un article au panier
     */
    public function addItem(int $productId, int $quantite, string $taille, string $couleur, int $userId = 0, string $sessionId = ''): array {
        // Vérifier le stock
        $stmt = $this->db->prepare("SELECT stock, nom FROM products WHERE id = ? AND is_active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if (!$product) return ['success' => false, 'error' => 'Produit introuvable.'];
        if ($product['stock'] < $quantite) return ['success' => false, 'error' => 'Stock insuffisant.'];

        // Chercher si article déjà dans le panier (même produit + taille + couleur)
        $where  = $userId ? 'user_id = ?' : 'session_id = ?';
        $param  = $userId ?: $sessionId;
        $stmt   = $this->db->prepare(
            "SELECT id, quantite FROM cart WHERE product_id = ? AND taille = ? AND couleur = ? AND {$where} LIMIT 1"
        );
        $stmt->execute([$productId, $taille, $couleur, $param]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newQty = $existing['quantite'] + $quantite;
            if ($newQty > $product['stock']) return ['success' => false, 'error' => 'Quantité dépasse le stock disponible.'];
            $this->update($existing['id'], ['quantite' => $newQty]);
        } else {
            $data = [
                'product_id' => $productId,
                'quantite'   => $quantite,
                'taille'     => $taille,
                'couleur'    => $couleur,
            ];
            if ($userId) $data['user_id']    = $userId;
            else         $data['session_id'] = $sessionId;
            $this->insert($data);
        }

        return ['success' => true, 'message' => 'Produit ajouté au panier.'];
    }

    /**
     * Mettre à jour la quantité d'un article
     */
    public function updateItem(int $cartId, int $quantite, int $userId = 0): array {
        $item = $this->find($cartId);
        if (!$item) return ['success' => false, 'error' => 'Article non trouvé.'];
        if ($userId && $item['user_id'] != $userId) return ['success' => false, 'error' => 'Non autorisé.'];

        if ($quantite <= 0) {
            $this->delete($cartId);
            return ['success' => true, 'message' => 'Article supprimé.'];
        }

        $this->update($cartId, ['quantite' => $quantite]);
        return ['success' => true, 'message' => 'Quantité mise à jour.'];
    }

    /**
     * Supprimer un article
     */
    public function removeItem(int $cartId, int $userId = 0): array {
        $item = $this->find($cartId);
        if (!$item) return ['success' => false, 'error' => 'Article non trouvé.'];
        if ($userId && $item['user_id'] != $userId) return ['success' => false, 'error' => 'Non autorisé.'];
        $this->delete($cartId);
        return ['success' => true, 'message' => 'Article supprimé du panier.'];
    }

    /**
     * Vider le panier
     */
    public function clearCart(int $userId = 0, string $sessionId = ''): void {
        if ($userId) {
            $this->execute("DELETE FROM cart WHERE user_id = ?", [$userId]);
        } else {
            $this->execute("DELETE FROM cart WHERE session_id = ?", [$sessionId]);
        }
    }

    /**
     * Transférer le panier session → user après login
     */
    public function mergeSessionToUser(string $sessionId, int $userId): void {
        $sessionItems = $this->query(
            "SELECT * FROM cart WHERE session_id = ?",
            [$sessionId]
        );
        foreach ($sessionItems as $item) {
            $stmt = $this->db->prepare(
                "SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND taille = ? AND couleur = ? LIMIT 1"
            );
            $stmt->execute([$userId, $item['product_id'], $item['taille'], $item['couleur']]);
            if ($stmt->fetch()) {
                $this->db->prepare("DELETE FROM cart WHERE id = ?")->execute([$item['id']]);
            } else {
                $this->update($item['id'], ['user_id' => $userId, 'session_id' => null]);
            }
        }
    }

    /**
     * Appliquer un code promo
     */
    public function applyPromo(string $code, float $total): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM promotions
             WHERE code = ? AND is_active = 1
             AND (date_debut IS NULL OR date_debut <= CURDATE())
             AND (date_fin IS NULL OR date_fin >= CURDATE())
             AND (max_usage IS NULL OR usages < max_usage)
             LIMIT 1"
        );
        $stmt->execute([strtoupper(trim($code))]);
        $promo = $stmt->fetch();

        if (!$promo) return ['success' => false, 'error' => 'Code promo invalide ou expiré.'];
        if ($promo['min_achat'] && $total < $promo['min_achat']) {
            return ['success' => false, 'error' => 'Achat minimum de ' . formatFCFA($promo['min_achat']) . ' requis.'];
        }

        $remise = $promo['type'] === 'pourcentage'
            ? $total * ($promo['valeur'] / 100)
            : min($promo['valeur'], $total);

        return [
            'success'    => true,
            'code'       => $promo['code'],
            'type'       => $promo['type'],
            'valeur'     => $promo['valeur'],
            'remise'     => round($remise, 2),
            'nouveau_total' => round($total - $remise, 2),
        ];
    }

    /**
     * Incrémenter usage promo
     */
    public function usePromo(string $code): void {
        $this->execute("UPDATE promotions SET usages = usages + 1 WHERE code = ?", [strtoupper($code)]);
    }
}
