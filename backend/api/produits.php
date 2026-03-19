<?php
/**
 * SneakX — API Produits + Recherche Prédictive
 * /backend/api/produits.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'liste';
$input  = getInput();

$productModel = new Product();

switch ($action) {

    // ── LISTE / CATALOGUE ────────────────────────
    case 'liste':
    case 'catalogue':
        $filters = [
            'q'           => sanitize($_GET['q'] ?? ''),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'marque'      => sanitize($_GET['marque'] ?? ''),
            'prix_min'    => (float) ($_GET['prix_min'] ?? 0),
            'prix_max'    => (float) ($_GET['prix_max'] ?? 0),
            'in_stock'    => !empty($_GET['in_stock']),
            'sort'        => sanitize($_GET['sort'] ?? 'populaire'),
        ];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $result = $productModel->catalogue($filters, $page, ITEMS_PER_PAGE);
        jsonSuccess($result);
        break;

    // ── RECHERCHE PRÉDICTIVE (AJAX) ───────────────
    case 'search':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { jsonSuccess(['results' => []]); break; }

        $results = $productModel->searchPredictive($q, 8);

        // Suggestions de catégories aussi
        $db   = Database::getInstance();
        $like = '%' . $q . '%';
        $stmt = $db->prepare("SELECT id, nom, slug FROM categories WHERE nom LIKE ? AND is_active = 1 LIMIT 3");
        $stmt->execute([$like]);
        $categories = $stmt->fetchAll();

        jsonSuccess([
            'query'      => $q,
            'produits'   => $results,
            'categories' => $categories,
            'total'      => count($results),
        ]);
        break;

    // ── PRODUIT UNIQUE ────────────────────────────
    case 'detail':
        $id   = (int) ($_GET['id'] ?? 0);
        $slug = sanitize($_GET['slug'] ?? '');

        if ($id) {
            $product = $productModel->find($id);
        } elseif ($slug) {
            $product = $productModel->findBySlug($slug);
        } else {
            jsonError('ID ou slug requis.', 422);
        }

        if (!$product) jsonError('Produit non trouvé.', 404);

        // Enregistrer la vue
        $userId    = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        $ip        = $_SERVER['REMOTE_ADDR'] ?? '';
        $productModel->incrementView($product['id'], $userId, $sessionId, $ip);

        // Avis du produit
        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT r.*, CONCAT(u.prenom, ' ', LEFT(u.nom,1), '.') AS auteur
             FROM product_reviews r
             JOIN users u ON u.id = r.user_id
             WHERE r.product_id = ? AND r.is_approved = 1
             ORDER BY r.created_at DESC LIMIT 10"
        );
        $stmt->execute([$product['id']]);
        $product['avis'] = $stmt->fetchAll();

        // Produits similaires
        $stmt = $db->prepare(
            "SELECT id, nom, slug, prix, prix_promo, images, marque
             FROM products
             WHERE category_id = ? AND id != ? AND is_active = 1
             ORDER BY ventes DESC LIMIT 4"
        );
        $stmt->execute([$product['category_id'], $product['id']]);
        $similaires = $stmt->fetchAll();
        foreach ($similaires as &$s) {
            $imgs = json_decode($s['images'] ?? '[]', true);
            $s['image'] = $imgs[0] ?? '';
            unset($s['images']);
        }
        $product['similaires'] = $similaires;

        jsonSuccess(['produit' => $product]);
        break;

    // ── TENDANCES ────────────────────────────────
    case 'tendances':
        $limit = min(12, (int) ($_GET['limit'] ?? 6));
        jsonSuccess(['produits' => $productModel->tendance($limit)]);
        break;

    // ── PRODUITS VEDETTES ────────────────────────
    case 'featured':
        $limit = min(12, (int) ($_GET['limit'] ?? 8));
        jsonSuccess(['produits' => $productModel->featured($limit)]);
        break;

    // ── CATÉGORIES ────────────────────────────────
    case 'categories':
        $db   = Database::getInstance();
        $stmt = $db->query(
            "SELECT c.*, COUNT(p.id) AS nb_produits
             FROM categories c
             LEFT JOIN products p ON p.category_id = c.id AND p.is_active = 1
             WHERE c.is_active = 1
             GROUP BY c.id
             ORDER BY c.ordre ASC"
        );
        jsonSuccess(['categories' => $stmt->fetchAll()]);
        break;

    // ── MARQUES ───────────────────────────────────
    case 'marques':
        $db   = Database::getInstance();
        $stmt = $db->query(
            "SELECT marque, COUNT(*) AS nb FROM products WHERE is_active = 1 AND marque IS NOT NULL GROUP BY marque ORDER BY nb DESC"
        );
        jsonSuccess(['marques' => $stmt->fetchAll()]);
        break;

    // ── ADMIN : CRÉER / MODIFIER ──────────────────
    case 'create':
        requireAdminAuth();
        if ($method !== 'POST') jsonError('POST requis.', 405);

        $slug = slugify($input['nom'] ?? '');
        // Unicité slug
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
        $stmt->execute([$slug]);
        if ((int) $stmt->fetchColumn() > 0) $slug .= '-' . time();

        $id = $productModel->insert([
            'category_id'  => (int) ($input['category_id'] ?? 1),
            'nom'          => sanitize($input['nom'] ?? ''),
            'slug'         => $slug,
            'description'  => sanitize($input['description'] ?? ''),
            'prix'         => (float) ($input['prix'] ?? 0),
            'prix_promo'   => !empty($input['prix_promo']) ? (float) $input['prix_promo'] : null,
            'stock'        => (int) ($input['stock'] ?? 0),
            'marque'       => sanitize($input['marque'] ?? ''),
            'images'       => json_encode($input['images'] ?? []),
            'tailles'      => json_encode($input['tailles'] ?? []),
            'couleurs'     => json_encode($input['couleurs'] ?? []),
            'is_featured'  => (int) ($input['is_featured'] ?? 0),
            'is_new'       => (int) ($input['is_new'] ?? 0),
        ]);
        jsonSuccess(['id' => $id], 'Produit créé avec succès.');
        break;

    case 'update':
        requireAdminAuth();
        if ($method !== 'POST' && $method !== 'PUT') jsonError('POST/PUT requis.', 405);

        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        if (!$id) jsonError('ID requis.', 422);

        $updateData = [];
        $fields = ['nom','description','prix','prix_promo','stock','marque','is_featured','is_new','is_active','category_id'];
        foreach ($fields as $f) {
            if (isset($input[$f])) $updateData[$f] = $input[$f];
        }
        if (!empty($input['images']))  $updateData['images']  = json_encode($input['images']);
        if (!empty($input['tailles'])) $updateData['tailles'] = json_encode($input['tailles']);
        if (!empty($input['couleurs'])) $updateData['couleurs'] = json_encode($input['couleurs']);

        $productModel->update($id, $updateData);
        jsonSuccess([], 'Produit mis à jour.');
        break;

    case 'delete':
        requireAdminAuth();
        $id = (int) ($_GET['id'] ?? $input['id'] ?? 0);
        if (!$id) jsonError('ID requis.', 422);
        $productModel->update($id, ['is_active' => 0]);
        jsonSuccess([], 'Produit désactivé.');
        break;

    // ── STOCK ALERT ADMIN ─────────────────────────
    case 'stock_alert':
        requireAdminAuth();
        jsonSuccess([
            'alerte'  => $productModel->stockAlerte(),
            'rupture' => $productModel->rupture(),
        ]);
        break;

    default:
        jsonError('Action inconnue.', 404);
}
