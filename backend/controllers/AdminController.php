<?php
// Fichier: /backend/controllers/AdminController.php

class AdminController extends Controller {
    /**
     * Dashboard admin
     */
    public function dashboard() {
        $user = $this->getUser();
        
        if (!$user || $user['role'] !== 'ADMIN') {
            Response::error('Accès refusé', 403);
        }
        
        $db = Database::getInstance()->getConnection();
        
        // CA du jour
        $stmt = $db->query("SELECT SUM(amount_ttc) as ca FROM orders WHERE DATE(created_at) = CURDATE()");
        $caJour = $stmt->fetch()['ca'] ?? 0;
        
        // Commandes du jour
        $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
        $commandesJour = $stmt->fetch()['count'];
        
        // Alertes fraude ouvertes
        $stmt = $db->query("SELECT COUNT(*) as count FROM fraud_alerts WHERE status = 'OPEN'");
        $alertesFraude = $stmt->fetch()['count'];
        
        // Produits en alerte stock
        $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE stock <= stock_alert AND status = 'ACTIVE'");
        $produitsAlerte = $stmt->fetch()['count'];
        
        // Commandes récentes
        $stmt = $db->query("SELECT o.*, u.name, u.first_name FROM orders o 
                            JOIN users u ON o.user_id = u.id 
                            ORDER BY o.created_at DESC LIMIT 10");
        $commandesRecentes = $stmt->fetchAll();
        
        // Stats ventes 7 derniers jours
        $stmt = $db->query("SELECT DATE(created_at) as date, SUM(amount_ttc) as total 
                            FROM orders 
                            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                            GROUP BY DATE(created_at)
                            ORDER BY date ASC");
        $statsVentes = $stmt->fetchAll();
        
        Response::success([
            'kpi' => [
                'ca_jour' => $caJour,
                'commandes_jour' => $commandesJour,
                'alertes_fraude' => $alertesFraude,
                'produits_alerte' => $produitsAlerte
            ],
            'commandes_recentes' => $commandesRecentes,
            'stats_ventes' => $statsVentes
        ], 'Dashboard récupéré');
    }
    
    /**
     * Liste des utilisateurs
     */
    public function users() {
        $user = $this->getUser();
        
        if (!$user || $user['role'] !== 'ADMIN') {
            Response::error('Accès refusé', 403);
        }
        
        $page = $this->getParam('page', 1);
        $perPage = $this->getParam('per_page', 20);
        
        $userModel = new User();
        $users = $userModel->paginate($page, $perPage);
        $total = $userModel->count();
        
        Response::paginated($users, $page, $perPage, $total);
    }
    
    /**
     * Gestion des produits
     */
    public function products() {
        $user = $this->getUser();
        
        if (!$user || $user['role'] !== 'ADMIN') {
            Response::error('Accès refusé', 403);
        }
        
        $produitModel = new Produit();
        $products = $produitModel->all(100);
        
        Response::success($products, 'Produits récupérés');
    }
    
    /**
     * Alertes fraude
     */
    public function fraudAlerts() {
        $user = $this->getUser();
        
        if (!$user || $user['role'] !== 'ADMIN') {
            Response::error('Accès refusé', 403);
        }
        
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->query("SELECT f.*, u.email, u.name 
                            FROM fraud_alerts f
                            LEFT JOIN users u ON f.user_id = u.id
                            ORDER BY f.detected_at DESC
                            LIMIT 50");
        
        $alerts = $stmt->fetchAll();
        
        Response::success($alerts, 'Alertes récupérées');
    }
    
    /**
     * Analytics comportementales
     */
    public function analytics() {
        $user = $this->getUser();
        
        if (!$user || $user['role'] !== 'ADMIN') {
            Response::error('Accès refusé', 403);
        }
        
        $db = Database::getInstance()->getConnection();
        
        // Produits les plus vus
        $stmt = $db->query("SELECT p.name, COUNT(*) as views
                            FROM product_views pv
                            JOIN products p ON pv.product_id = p.id
                            WHERE pv.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                            GROUP BY pv.product_id
                            ORDER BY views DESC
                            LIMIT 10");
        
        $mostViewed = $stmt->fetchAll();
        
        // Taux d'abandon panier
        $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as users_with_cart FROM cart_items");
        $usersWithCart = $stmt->fetch()['users_with_cart'];
        
        $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as users_ordered 
                            FROM orders 
                            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $usersOrdered = $stmt->fetch()['users_ordered'];
        
        $abandonRate = $usersWithCart > 0 ? (($usersWithCart - $usersOrdered) / $usersWithCart) * 100 : 0;
        
        Response::success([
            'most_viewed' => $mostViewed,
            'cart_abandon_rate' => round($abandonRate, 2)
        ], 'Analytics récupérées');
    }
}

<?php
// Fichier: /backend/controllers/AdminController.php
// COMPLÉMENT — méthodes manquantes à ajouter

// Dans la classe AdminController existante, ajouter ces méthodes :

    /**
     * Liste commandes (admin)
     * GET /api/admin/orders
     */
    public function orders(): void
    {
        $limit  = (int)($this->getQueryParams()['limit'] ?? 100);
        $status = $this->getQueryParams()['status'] ?? null;
        $page   = (int)($this->getQueryParams()['page'] ?? 1);

        $sql = "SELECT o.*, u.name, u.first_name, u.email
                FROM orders o
                JOIN users u ON u.id = o.user_id";
        $params = [];

        if ($status) {
            $sql .= " WHERE o.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY o.created_at DESC LIMIT :limit";
        $params[':limit'] = $limit;

        $orders = Database::getInstance()->query($sql, $params);

        // Ajouter les items de chaque commande
        foreach ($orders as &$order) {
            $order['items'] = Database::getInstance()->query(
                "SELECT oi.*, p.name, pi.url AS image_url
                 FROM order_items oi
                 LEFT JOIN products p ON p.id = oi.product_id
                 LEFT JOIN product_images pi ON pi.product_id = oi.product_id AND pi.is_primary = 1
                 WHERE oi.order_id = :oid",
                [':oid' => $order['id']]
            );
        }

        Response::success($orders, 'Commandes récupérées');
    }

    /**
     * Mise à jour statut commande
     * PUT /api/admin/orders/{id}/status
     */
    public function updateOrderStatus(int $id): void
    {
        $data   = $this->getJsonInput();
        $status = $data['status'] ?? null;

        $validStatuses = ['RECEIVED','PREPARING','SHIPPED','IN_DELIVERY','DELIVERED','CANCELLED','REFUNDED'];
        if (!$status || !in_array($status, $validStatuses)) {
            Response::error('Statut invalide', 400);
            return;
        }

        $db = Database::getInstance();

        // Mise à jour commande
        $db->query(
            "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id",
            [':status' => $status, ':id' => $id]
        );

        // Ajout événement tracking
        $db->query(
            "INSERT INTO order_tracking (order_id, status, description, event_date)
             VALUES (:oid, :status, :desc, NOW())",
            [
                ':oid'    => $id,
                ':status' => $status,
                ':desc'   => "Statut mis à jour par l'administrateur"
            ]
        );

        // Récupère le user_id pour la notification
        $order = $db->query("SELECT user_id, order_number FROM orders WHERE id = :id", [':id' => $id]);
        if ($order) {
            $notifService = new NotificationService();
            $notifService->notifyOrderStatus($order[0]['user_id'], $id, $status, $order[0]['order_number']);
        }

        Response::success(['id' => $id, 'status' => $status], 'Statut mis à jour');
    }

    /**
     * Créer un produit (admin)
     * POST /api/admin/products
     */
    public function createProduct(): void
    {
        $data = $this->getJsonInput();

        $required = ['name', 'sku', 'price', 'stock'];
        if (!$this->validateRequired($data, $required)) {
            Response::error('Champs obligatoires manquants', 400);
            return;
        }

        // Génère le slug
        $slug = $this->generateSlug($data['name']);

        $db = Database::getInstance();

        // Vérifie unicité SKU
        $exists = $db->query("SELECT id FROM products WHERE sku = :sku", [':sku' => $data['sku']]);
        if ($exists) {
            Response::error('Ce SKU existe déjà', 409);
            return;
        }

        // Crée le produit
        $productId = $db->query(
            "INSERT INTO products (category_id, name, slug, sku, short_description, long_description,
             price, promo_price, stock, status, created_at)
             VALUES (:cat, :name, :slug, :sku, :sdesc, :ldesc, :price, :promo, :stock, :status, NOW())",
            [
                ':cat'    => $data['category_id'] ?? null,
                ':name'   => $this->sanitize($data['name']),
                ':slug'   => $slug,
                ':sku'    => $this->sanitize($data['sku']),
                ':sdesc'  => $data['short_description'] ?? null,
                ':ldesc'  => $data['long_description'] ?? null,
                ':price'  => (float)$data['price'],
                ':promo'  => !empty($data['promo_price']) ? (float)$data['promo_price'] : null,
                ':stock'  => (int)$data['stock'],
                ':status' => $data['status'] ?? 'ACTIVE'
            ],
            true // retourne lastInsertId
        );

        // Image principale
        if (!empty($data['image_url'])) {
            $db->query(
                "INSERT INTO product_images (product_id, url, alt_text, is_primary)
                 VALUES (:pid, :url, :alt, 1)",
                [':pid' => $productId, ':url' => $data['image_url'], ':alt' => $data['name']]
            );
        }

        Response::success(['id' => $productId], 'Produit créé', 201);
    }

    /**
     * Modifier un produit (admin)
     * PUT /api/admin/products/{id}
     */
    public function updateProduct(int $id): void
    {
        $data = $this->getJsonInput();
        $db   = Database::getInstance();

        $db->query(
            "UPDATE products SET
                category_id       = :cat,
                name              = :name,
                short_description = :sdesc,
                long_description  = :ldesc,
                price             = :price,
                promo_price       = :promo,
                stock             = :stock,
                status            = :status,
                updated_at        = NOW()
             WHERE id = :id",
            [
                ':cat'    => $data['category_id'] ?? null,
                ':name'   => $this->sanitize($data['name']),
                ':sdesc'  => $data['short_description'] ?? null,
                ':ldesc'  => $data['long_description'] ?? null,
                ':price'  => (float)$data['price'],
                ':promo'  => !empty($data['promo_price']) ? (float)$data['promo_price'] : null,
                ':stock'  => (int)$data['stock'],
                ':status' => $data['status'] ?? 'ACTIVE',
                ':id'     => $id
            ]
        );

        // Mise à jour image si fournie
        if (!empty($data['image_url'])) {
            $exists = $db->query(
                "SELECT id FROM product_images WHERE product_id = :pid AND is_primary = 1",
                [':pid' => $id]
            );
            if ($exists) {
                $db->query(
                    "UPDATE product_images SET url = :url WHERE product_id = :pid AND is_primary = 1",
                    [':url' => $data['image_url'], ':pid' => $id]
                );
            } else {
                $db->query(
                    "INSERT INTO product_images (product_id, url, alt_text, is_primary) VALUES (:pid, :url, :alt, 1)",
                    [':pid' => $id, ':url' => $data['image_url'], ':alt' => $data['name'] ?? '']
                );
            }
        }

        Response::success(['id' => $id], 'Produit mis à jour');
    }

    /**
     * Supprimer un produit (admin)
     * DELETE /api/admin/products/{id}
     */
    public function deleteProduct(int $id): void
    {
        $db = Database::getInstance();

        // Vérifie s'il y a des commandes liées
        $linked = $db->query(
            "SELECT COUNT(*) as cnt FROM order_items WHERE product_id = :pid",
            [':pid' => $id]
        );
        if ($linked && $linked[0]['cnt'] > 0) {
            // Désactive plutôt que supprimer
            $db->query(
                "UPDATE products SET status = 'INACTIVE' WHERE id = :id",
                [':id' => $id]
            );
            Response::success(null, 'Produit désactivé (commandes existantes)');
            return;
        }

        $db->query("DELETE FROM product_images WHERE product_id = :pid", [':pid' => $id]);
        $db->query("DELETE FROM products WHERE id = :id", [':id' => $id]);

        Response::success(null, 'Produit supprimé');
    }

    /**
     * Modifier un utilisateur (admin)
     * PUT /api/admin/users/{id}
     */
    public function updateUser(int $id): void
    {
        $data = $this->getJsonInput();
        $db   = Database::getInstance();

        $allowedStatus = ['ACTIVE','INACTIVE','BANNED'];
        $allowedRoles  = ['CLIENT','ADMIN','LIVREUR'];

        if (isset($data['status']) && !in_array($data['status'], $allowedStatus)) {
            Response::error('Statut invalide', 400);
            return;
        }
        if (isset($data['role']) && !in_array($data['role'], $allowedRoles)) {
            Response::error('Rôle invalide', 400);
            return;
        }

        $db->query(
            "UPDATE users SET
                name       = COALESCE(:name, name),
                first_name = COALESCE(:fname, first_name),
                email      = COALESCE(:email, email),
                phone      = COALESCE(:phone, phone),
                role       = COALESCE(:role, role),
                status     = COALESCE(:status, status),
                updated_at = NOW()
             WHERE id = :id",
            [
                ':name'   => $data['name'] ?? null,
                ':fname'  => $data['first_name'] ?? null,
                ':email'  => $data['email'] ?? null,
                ':phone'  => $data['phone'] ?? null,
                ':role'   => $data['role'] ?? null,
                ':status' => $data['status'] ?? null,
                ':id'     => $id
            ]
        );

        Response::success(['id' => $id], 'Utilisateur mis à jour');
    }

    /**
     * Résoudre une alerte fraude
     * PUT /api/admin/fraud-alerts/{id}
     */
    public function updateFraudAlert(int $id): void
    {
        $data   = $this->getJsonInput();
        $status = $data['status'] ?? null;

        $validStatuses = ['OPEN','REVIEWING','RESOLVED','FALSE_POSITIVE'];
        if (!in_array($status, $validStatuses)) {
            Response::error('Statut invalide', 400);
            return;
        }

        $user = $this->getUser();
        $db   = Database::getInstance();

        $db->query(
            "UPDATE fraud_alerts SET
                status      = :status,
                resolved_by = :uid,
                resolved_at = CASE WHEN :status2 IN ('RESOLVED','FALSE_POSITIVE') THEN NOW() ELSE NULL END
             WHERE id = :id",
            [':status' => $status, ':status2' => $status, ':uid' => $user['id'], ':id' => $id]
        );

        Response::success(['id' => $id, 'status' => $status], 'Alerte mise à jour');
    }

    /**
     * Génère un slug URL-friendly
     */
    private function generateSlug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[àáâäãåæ]/u', 'a', $slug);
        $slug = preg_replace('/[èéêë]/u', 'e', $slug);
        $slug = preg_replace('/[ìíîï]/u', 'i', $slug);
        $slug = preg_replace('/[òóôöõø]/u', 'o', $slug);
        $slug = preg_replace('/[ùúûü]/u', 'u', $slug);
        $slug = preg_replace('/[ñ]/u', 'n', $slug);
        $slug = preg_replace('/[ç]/u', 'c', $slug);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return trim($slug, '-') . '-' . substr(uniqid(), -4);
    }