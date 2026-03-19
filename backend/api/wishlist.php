<?php
/**
 * SneakX — API Wishlist
 * /backend/api/wishlist.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireAuth();

$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? 'liste';
$input   = getInput();
$userId  = $_SESSION['user_id'];
$db      = Database::getInstance();

switch ($action) {

    case 'liste':
        $stmt = $db->prepare(
            "SELECT w.id, w.created_at, p.id AS product_id, p.nom, p.slug, p.marque,
                    p.prix, p.prix_promo, p.images, p.stock,
                    COALESCE(p.prix_promo, p.prix) AS prix_effectif
             FROM wishlists w
             JOIN products p ON p.id = w.product_id
             WHERE w.user_id = ? AND p.is_active = 1
             ORDER BY w.created_at DESC"
        );
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $imgs = json_decode($item['images'] ?? '[]', true);
            $item['image'] = $imgs[0] ?? '';
            unset($item['images']);
        }
        jsonSuccess(['items' => $items, 'total' => count($items)]);
        break;

    case 'toggle':
        if ($method !== 'POST') jsonError('POST requis.', 405);
        $productId = (int) ($input['product_id'] ?? 0);
        if (!$productId) jsonError('product_id requis.', 422);

        $stmt = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        $exists = $stmt->fetch();

        if ($exists) {
            $db->prepare("DELETE FROM wishlists WHERE id = ?")->execute([$exists['id']]);
            jsonSuccess(['action' => 'removed'], 'Retiré de la wishlist.');
        } else {
            $db->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)")->execute([$userId, $productId]);
            jsonSuccess(['action' => 'added'], 'Ajouté à la wishlist !');
        }
        break;

    default:
        jsonError('Action inconnue.', 404);
}
