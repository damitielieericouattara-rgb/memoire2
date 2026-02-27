<?php
// Fichier: /backend/controllers/EchangeController.php
// ─── MARKETPLACE INTERNE D'ÉCHANGE/REVENTE ───────────────────

class EchangeController extends Controller {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    /** Liste les annonces */
    public function index() {
        $page    = (int)($_GET['page'] ?? 1);
        $perPage = 12;
        $offset  = ($page - 1) * $perPage;
        $type    = $_GET['type'] ?? null;

        $where  = "ei.status = 'ACTIVE'";
        $params = [];
        if ($type) { $where .= ' AND ei.type = ?'; $params[] = $type; }

        $total = $this->db->prepare("SELECT COUNT(*) FROM exchange_items ei WHERE $where");
        $total->execute($params);
        $total = (int)$total->fetchColumn();

        $p2   = $params;
        $stmt = $this->db->prepare(
            "SELECT ei.*, u.name as seller_name, u.avatar_url
             FROM exchange_items ei
             JOIN users u ON ei.seller_id = u.id
             WHERE $where ORDER BY ei.created_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($p2);
        Response::paginated($stmt->fetchAll(PDO::FETCH_ASSOC), $page, $perPage, $total);
    }

    /** Détail d'une annonce */
    public function show($id) {
        $stmt = $this->db->prepare(
            "SELECT ei.*, u.name as seller_name, u.avatar_url, u.created_at as seller_since
             FROM exchange_items ei
             JOIN users u ON ei.seller_id = u.id
             WHERE ei.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) Response::error('Annonce non trouvée', 404);

        // Incrémente vues
        $this->db->prepare("UPDATE exchange_items SET views_count = views_count + 1 WHERE id = ?")->execute([$id]);

        Response::success($item);
    }

    /** Crée une annonce */
    public function create() {
        $user = $this->requireAuth();
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['title', 'price', 'condition', 'type']);

        // Vérifie que le vendeur a bien acheté le produit si product_id fourni
        if (!empty($data['product_id'])) {
            $bought = $this->db->prepare(
                "SELECT COUNT(*) FROM order_items oi
                 JOIN orders o ON oi.order_id = o.id
                 WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'DELIVERED'"
            );
            $bought->execute([$user['id'], $data['product_id']]);
            if ((int)$bought->fetchColumn() === 0) {
                Response::error('Vous ne pouvez revendre que des articles que vous avez achetés chez SneakX', 403);
            }
        }

        $id = $this->db->prepare(
            "INSERT INTO exchange_items (seller_id, product_id, title, description, price, `condition`, type, images)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $id->execute([
            $user['id'],
            $data['product_id'] ?? null,
            $this->sanitize($data['title']),
            $data['description'] ?? null,
            (float)$data['price'],
            $data['condition'],
            $data['type'],
            isset($data['images']) ? json_encode($data['images']) : null,
        ]);

        Response::success(['id' => (int)$this->db->lastInsertId()], 'Annonce créée', 201);
    }

    /** Supprime une annonce */
    public function delete($id) {
        $user = $this->requireAuth();
        $this->db->prepare(
            "UPDATE exchange_items SET status = 'REMOVED'
             WHERE id = ? AND seller_id = ?"
        )->execute([$id, $user['id']]);
        Response::success(null, 'Annonce supprimée');
    }
}
