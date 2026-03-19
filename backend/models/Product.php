<?php
/**
 * SneakX — Modèle Produit
 * /backend/models/Product.php
 */

class Product extends Model {
    protected string $table = 'products';

    /**
     * Produits avec catégorie (catalogue)
     */
    public function catalogue(array $filters = [], int $page = 1, int $perPage = 12): array {
        $where  = ['p.is_active = 1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[]  = '(p.nom LIKE ? OR p.marque LIKE ? OR p.description LIKE ?)';
            $like     = '%' . $filters['q'] . '%';
            $params   = array_merge($params, [$like, $like, $like]);
        }
        if (!empty($filters['category_id'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (!empty($filters['marque'])) {
            $where[]  = 'p.marque = ?';
            $params[] = $filters['marque'];
        }
        if (!empty($filters['prix_min'])) {
            $where[]  = 'COALESCE(p.prix_promo, p.prix) >= ?';
            $params[] = (float) $filters['prix_min'];
        }
        if (!empty($filters['prix_max'])) {
            $where[]  = 'COALESCE(p.prix_promo, p.prix) <= ?';
            $params[] = (float) $filters['prix_max'];
        }
        if (!empty($filters['in_stock'])) {
            $where[] = 'p.stock > 0';
        }

        $orderMap = [
            'populaire'   => 'p.ventes DESC, p.vues DESC',
            'prix_asc'    => 'COALESCE(p.prix_promo, p.prix) ASC',
            'prix_desc'   => 'COALESCE(p.prix_promo, p.prix) DESC',
            'nouveau'     => 'p.created_at DESC',
            'note'        => 'p.note_moyenne DESC',
        ];
        $orderBy = $orderMap[$filters['sort'] ?? ''] ?? 'p.created_at DESC';

        $whereStr = implode(' AND ', $where);
        $offset   = ($page - 1) * $perPage;

        $sql = "SELECT p.*,
                       c.nom AS categorie_nom,
                       c.slug AS categorie_slug,
                       COALESCE(p.prix_promo, p.prix) AS prix_effectif
                FROM products p
                JOIN categories c ON c.id = p.category_id
                WHERE {$whereStr}
                ORDER BY {$orderBy}
                LIMIT ? OFFSET ?";

        $items = $this->query($sql, [...$params, $perPage, $offset]);

        // Count total
        $countSql  = "SELECT COUNT(*) FROM products p WHERE {$whereStr}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Decode JSON fields
        $items = array_map([$this, 'decodeJsonFields'], $items);

        return [
            'items'        => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Produit par slug
     */
    public function findBySlug(string $slug): ?array {
        $product = $this->queryOne(
            "SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.is_active = 1",
            [$slug]
        );
        if (!$product) return null;
        return $this->decodeJsonFields($product);
    }

    /**
     * Recherche prédictive AJAX
     */
    public function searchPredictive(string $q, int $limit = 8): array {
        $like = '%' . $q . '%';
        $results = $this->query(
            "SELECT id, nom, slug, marque,
                    COALESCE(prix_promo, prix) AS prix,
                    JSON_UNQUOTE(JSON_EXTRACT(images, '$[0]')) AS image
             FROM products
             WHERE is_active = 1
               AND (nom LIKE ? OR marque LIKE ? OR description LIKE ?)
             ORDER BY ventes DESC, vues DESC
             LIMIT ?",
            [$like, $like, $like, $limit]
        );
        return $results;
    }

    /**
     * Produits tendance de la semaine
     */
    public function tendance(int $limit = 4): array {
        $results = $this->query(
            "SELECT p.*,
                    c.nom AS categorie_nom,
                    COALESCE(p.prix_promo, p.prix) AS prix_effectif,
                    (p.ventes * 3 + p.vues) AS score_tendance,
                    (
                        SELECT COUNT(*) FROM product_views pv
                        WHERE pv.product_id = p.id
                        AND pv.viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ) AS vues_semaine
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1
             ORDER BY score_tendance DESC
             LIMIT ?",
            [$limit]
        );
        return array_map([$this, 'decodeJsonFields'], $results);
    }

    /**
     * Produits vedettes (homepage)
     */
    public function featured(int $limit = 8): array {
        $results = $this->query(
            "SELECT p.*, c.nom AS categorie_nom,
                    COALESCE(p.prix_promo, p.prix) AS prix_effectif
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1 AND p.is_featured = 1
             ORDER BY p.ventes DESC
             LIMIT ?",
            [$limit]
        );
        return array_map([$this, 'decodeJsonFields'], $results);
    }

    /**
     * Incrémenter le compteur de vues
     */
    public function incrementView(int $productId, ?int $userId, string $sessionId, string $ip): void {
        $this->execute(
            "UPDATE products SET vues = vues + 1 WHERE id = ?",
            [$productId]
        );
        $this->execute(
            "INSERT INTO product_views (product_id, user_id, session_id, ip)
             VALUES (?, ?, ?, ?)",
            [$productId, $userId, $sessionId, $ip]
        );
    }

    /**
     * Alerte stock faible
     */
    public function stockAlerte(int $seuil = 5): array {
        return $this->query(
            "SELECT id, nom, stock, marque FROM products
             WHERE is_active = 1 AND stock < ? AND stock > 0
             ORDER BY stock ASC",
            [$seuil]
        );
    }

    /**
     * Produits en rupture de stock
     */
    public function rupture(): array {
        return $this->query(
            "SELECT id, nom, stock, marque FROM products
             WHERE is_active = 1 AND stock = 0"
        );
    }

    // ─── Private ─────────────────────────────────

    private function decodeJsonFields(array $p): array {
        foreach (['images', 'tailles', 'couleurs'] as $field) {
            if (isset($p[$field]) && is_string($p[$field])) {
                $p[$field] = json_decode($p[$field], true) ?? [];
            }
        }
        // Image principale
        $p['image_principale'] = $p['images'][0] ?? '';
        return $p;
    }
}
