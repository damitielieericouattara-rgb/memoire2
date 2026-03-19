<?php
/**
 * SneakX — API Dashboard Admin
 * /backend/api/admin.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

requireAdminAuth();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'dashboard';
$input  = getInput();
$db     = Database::getInstance();

switch ($action) {

    // ── DASHBOARD PRINCIPAL ───────────────────────
    case 'dashboard':
        $orderModel = new Order();
        $prodModel  = new Product();

        $kpi = $orderModel->statsAdmin();

        // Ventes 7 derniers jours
        $kpi['ventes_7j'] = $orderModel->ventesParJour(7);

        // Ventes 30 jours
        $kpi['ventes_30j'] = $orderModel->ventesParJour(30);

        // Top 5 produits
        $kpi['top_produits'] = $orderModel->topProduits(5);

        // Commandes récentes
        $stmt = $db->query(
            "SELECT o.id, o.numero, o.statut, o.total_ttc, o.methode_paiement, o.created_at,
                    u.nom, u.prenom, u.email
             FROM orders o
             JOIN users u ON u.id = o.user_id
             ORDER BY o.created_at DESC LIMIT 10"
        );
        $kpi['commandes_recentes'] = $stmt->fetchAll();

        // Répartition statuts
        $stmt = $db->query(
            "SELECT statut, COUNT(*) AS nb FROM orders GROUP BY statut ORDER BY nb DESC"
        );
        $kpi['statuts'] = $stmt->fetchAll();

        // Notifications non lues
        $stmt = $db->query("SELECT * FROM notifications WHERE user_id IS NULL AND is_read = 0 ORDER BY created_at DESC LIMIT 20");
        $kpi['notifications'] = $stmt->fetchAll();

        // Alertes stock
        $kpi['alertes_stock'] = $prodModel->stockAlerte(5);

        // Répartition paiements
        $stmt = $db->query(
            "SELECT methode_paiement AS methode, COUNT(*) AS nb,
                    COALESCE(SUM(total_ttc),0) AS total
             FROM orders WHERE statut != 'annulee'
             GROUP BY methode_paiement"
        );
        $kpi['paiements'] = $stmt->fetchAll();

        jsonSuccess($kpi);
        break;

    // ── UTILISATEURS ─────────────────────────────
    case 'users':
        $userModel = new User();
        $filters   = ['q' => sanitize($_GET['q'] ?? ''), 'role' => sanitize($_GET['role'] ?? '')];
        $page      = max(1, (int) ($_GET['page'] ?? 1));
        jsonSuccess($userModel->adminList($filters, $page));
        break;

    case 'user_toggle':
        if ($method !== 'POST') jsonError('POST requis.', 405);
        $id = (int) ($input['id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        jsonSuccess([], 'Statut utilisateur modifié.');
        break;

    // ── ANALYTIQUES ──────────────────────────────
    case 'analytics':
        $jours = max(7, min(365, (int) ($_GET['jours'] ?? 30)));

        // Ventes par jour
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS date,
                    COUNT(*) AS commandes,
                    COALESCE(SUM(total_ttc),0) AS ca
             FROM orders
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) AND statut != 'annulee'
             GROUP BY DATE(created_at) ORDER BY date ASC"
        );
        $stmt->execute([$jours]);
        $ventesJour = $stmt->fetchAll();

        // Produits les plus vus
        $stmt = $db->query(
            "SELECT p.nom, p.vues, p.ventes, p.marque
             FROM products p ORDER BY p.vues DESC LIMIT 10"
        );
        $topVues = $stmt->fetchAll();

        // Répartition par catégorie
        $stmt = $db->query(
            "SELECT c.nom AS categorie,
                    COUNT(oi.id) AS nb_ventes,
                    COALESCE(SUM(oi.sous_total),0) AS ca
             FROM order_items oi
             JOIN products p ON p.id = oi.product_id
             JOIN categories c ON c.id = p.category_id
             JOIN orders o ON o.id = oi.order_id
             WHERE o.statut != 'annulee'
             GROUP BY c.id, c.nom ORDER BY ca DESC"
        );
        $parCategorie = $stmt->fetchAll();

        // Ventes par heure (comportement clients)
        $stmt = $db->query(
            "SELECT HOUR(created_at) AS heure, COUNT(*) AS nb
             FROM orders WHERE statut != 'annulee'
             GROUP BY HOUR(created_at) ORDER BY heure"
        );
        $parHeure = $stmt->fetchAll();

        jsonSuccess(compact('ventesJour','topVues','parCategorie','parHeure'));
        break;

    // ── RAPPORT PDF ───────────────────────────────
    case 'rapport':
        $year  = (int) ($_GET['year']  ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('m'));

        $generator = new ReportGenerator();
        $result    = $generator->generateMonthly($year, $month);
        jsonSuccess($result, 'Rapport généré.');
        break;

    // ── NOTIFICATIONS ────────────────────────────
    case 'notifs':
        $stmt = $db->query(
            "SELECT * FROM notifications WHERE user_id IS NULL ORDER BY created_at DESC LIMIT 50"
        );
        jsonSuccess(['notifications' => $stmt->fetchAll()]);
        break;

    case 'notif_read':
        $id = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
        } else {
            $db->query("UPDATE notifications SET is_read = 1 WHERE user_id IS NULL");
        }
        jsonSuccess([], 'Notification(s) marquée(s) comme lue(s).');
        break;

    // ── UPLOAD IMAGE PRODUIT ──────────────────────
    case 'upload':
        if ($method !== 'POST') jsonError('POST requis.', 405);
        if (empty($_FILES['image'])) jsonError('Aucun fichier envoyé.', 422);

        $file = $_FILES['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ALLOWED_IMG)) jsonError('Format non autorisé. Utilisez: jpg, png, webp', 422);
        if ($file['size'] > MAX_FILE_SIZE)  jsonError('Fichier trop volumineux (max 5 Mo).', 422);

        $dir = UPLOAD_PATH . '/products/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = uniqid('prod_', true) . '.' . $ext;
        $dest     = $dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            jsonError("Erreur lors de l'upload.", 500);
        }

        // Compression basique si GD disponible
        if (function_exists('imagecreatefromjpeg') && in_array($ext, ['jpg','jpeg'])) {
            $img = imagecreatefromjpeg($dest);
            if ($img) { imagejpeg($img, $dest, 80); imagedestroy($img); }
        }

        jsonSuccess(['url' => UPLOAD_URL . '/products/' . $filename, 'filename' => $filename]);
        break;

    default:
        jsonError('Action inconnue.', 404);
}
