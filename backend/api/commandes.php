<?php
/**
 * SneakX — API Commandes
 * /backend/api/commandes.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? 'liste';
$input      = getInput();
$orderModel = new Order();

switch ($action) {

    // ── MES COMMANDES (client) ────────────────────
    case 'liste':
        requireAuth();
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $result = $orderModel->byUser($_SESSION['user_id'], $page);
        jsonSuccess($result);
        break;

    // ── DÉTAIL COMMANDE ───────────────────────────
    case 'detail':
        requireAuth();
        $id     = (int) ($_GET['id'] ?? 0);
        if (!$id) jsonError('ID requis.', 422);

        $userId = isAdmin() ? null : $_SESSION['user_id'];
        $order  = $orderModel->detail($id, $userId);
        if (!$order) jsonError('Commande non trouvée.', 404);
        jsonSuccess(['commande' => $order]);
        break;

    // ── CRÉER UNE COMMANDE ────────────────────────
    case 'create':
        requireAuth();
        if ($method !== 'POST') jsonError('POST requis.', 405);

        // Récupérer le panier
        $cartModel = new Cart();
        $userId    = $_SESSION['user_id'];
        $cart      = $cartModel->getCart($userId);

        if (empty($cart['items'])) jsonError('Votre panier est vide.', 422);

        // Vérifier stocks
        $db = Database::getInstance();
        foreach ($cart['items'] as $item) {
            $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$item['product_id']]);
            $stock = (int) $stmt->fetchColumn();
            if ($stock < $item['quantite']) {
                jsonError("Stock insuffisant pour : {$item['nom']}", 422);
            }
        }

        // Appliquer promo si fournie
        $remise = 0;
        $code   = sanitize($input['code_promo'] ?? '');
        if ($code) {
            $promoResult = $cartModel->applyPromo($code, $cart['sous_total']);
            if ($promoResult['success']) {
                $remise = $promoResult['remise'];
                $cartModel->usePromo($code);
            }
        }

        $totalTTC = max(0, $cart['sous_total'] - $remise + $cart['frais_livraison']);

        // Préparer les données commande
        $orderData = [
            'user_id'              => $userId,
            'total_ht'             => $cart['sous_total'],
            'frais_livraison'      => $cart['frais_livraison'],
            'remise'               => $remise,
            'total_ttc'            => $totalTTC,
            'methode_paiement'     => sanitize($input['methode_paiement'] ?? 'livraison'),
            'adresse_livraison'    => sanitize($input['adresse'] ?? ''),
            'ville'                => sanitize($input['ville'] ?? 'Abidjan'),
            'quartier'             => sanitize($input['quartier'] ?? ''),
            'telephone_livraison'  => sanitize($input['telephone'] ?? ''),
            'latitude'             => !empty($input['latitude']) ? (float) $input['latitude'] : null,
            'longitude'            => !empty($input['longitude']) ? (float) $input['longitude'] : null,
            'code_promo'           => $code ?: null,
            'note_client'          => sanitize($input['note'] ?? ''),
        ];

        // Articles
        $items = array_map(fn($item) => [
            'product_id'  => $item['product_id'],
            'nom_produit' => $item['nom'],
            'prix_unit'   => $item['prix_effectif'],
            'quantite'    => $item['quantite'],
            'taille'      => $item['taille'],
            'couleur'     => $item['couleur'],
        ], $cart['items']);

        // Créer la commande
        try {
            $orderId = $orderModel->createWithItems($orderData, $items);
        } catch (Exception $e) {
            jsonError('Erreur lors de la création de la commande: ' . $e->getMessage(), 500);
        }

        // QR Code
        $order = $orderModel->detail($orderId);
        if ($order) {
            $qrUrl = QRCode::generateForOrder($order);
            $orderModel->saveQrCode($orderId, $qrUrl);
        }

        // Vider le panier
        $cartModel->clearCart($userId);

        // Initier le paiement
        $payCtrl = new PaymentController();
        $extra   = [
            'telephone'   => $input['telephone_paiement'] ?? $input['telephone'] ?? '',
            'card_number' => $input['card_number'] ?? '',
            'expiry'      => $input['expiry'] ?? '',
            'cvv'         => $input['cvv'] ?? '',
        ];
        $payment = $payCtrl->initiate($orderId, $orderData['methode_paiement'], $totalTTC, $extra);

        jsonSuccess([
            'order_id'    => $orderId,
            'numero'      => $order['numero'] ?? '',
            'total'       => $totalTTC,
            'qr_code'     => $qrUrl ?? '',
            'payment'     => $payment,
        ], 'Commande créée avec succès !');
        break;

    // ── ANNULER UNE COMMANDE ──────────────────────
    case 'annuler':
        requireAuth();
        $id    = (int) ($input['id'] ?? $_GET['id'] ?? 0);
        $order = $orderModel->detail($id, $_SESSION['user_id']);
        if (!$order) jsonError('Commande non trouvée.', 404);
        if (!in_array($order['statut'], ['en_attente', 'confirmee'])) {
            jsonError('Cette commande ne peut plus être annulée.', 422);
        }
        $orderModel->updateStatut($id, 'annulee');
        jsonSuccess([], 'Commande annulée.');
        break;

    // ── SUIVI COMMANDE ────────────────────────────
    case 'suivi':
        $id     = (int) ($_GET['id'] ?? 0);
        $numero = sanitize($_GET['numero'] ?? '');

        if ($id) {
            $where = 'o.id = ?';
            $param = $id;
        } elseif ($numero) {
            $where = 'o.numero = ?';
            $param = $numero;
        } else {
            jsonError('ID ou numéro de commande requis.', 422);
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT o.numero, o.statut, o.created_at, o.total_ttc,
                    d.latitude_dest, d.longitude_dest, d.adresse_dest,
                    d.statut AS statut_livraison
             FROM orders o
             LEFT JOIN deliveries d ON d.order_id = o.id
             WHERE {$where} LIMIT 1"
        );
        $stmt->execute([$param]);
        $order = $stmt->fetch();
        if (!$order) jsonError('Commande non trouvée.', 404);

        // Timeline de suivi
        $statutsTimeline = [
            'en_attente'      => ['label' => 'Commande passée',      'icon' => 'shopping-cart'],
            'confirmee'       => ['label' => 'Commande confirmée',    'icon' => 'check-circle'],
            'en_preparation'  => ['label' => 'En préparation',        'icon' => 'box'],
            'expediee'        => ['label' => 'Expédiée',              'icon' => 'truck'],
            'livree'          => ['label' => 'Livrée',                'icon' => 'home'],
        ];
        $ordre = array_keys($statutsTimeline);
        $posActuel = array_search($order['statut'], $ordre);

        $timeline = [];
        foreach ($statutsTimeline as $key => $info) {
            $pos = array_search($key, $ordre);
            $timeline[] = [
                'statut' => $key,
                'label'  => $info['label'],
                'icon'   => $info['icon'],
                'done'   => $pos <= $posActuel,
                'active' => $pos === $posActuel,
            ];
        }

        jsonSuccess(['commande' => $order, 'timeline' => $timeline]);
        break;

    // ── ADMIN : LISTE TOUTES COMMANDES ───────────
    case 'admin_liste':
        requireAdminAuth();
        $filters = [
            'statut' => sanitize($_GET['statut'] ?? ''),
            'q'      => sanitize($_GET['q'] ?? ''),
        ];
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $result = $orderModel->adminList($filters, $page);
        jsonSuccess($result);
        break;

    // ── ADMIN : CHANGER STATUT ────────────────────
    case 'admin_statut':
        requireAdminAuth();
        if ($method !== 'POST' && $method !== 'PUT') jsonError('POST/PUT requis.', 405);

        $id     = (int) ($input['id'] ?? 0);
        $statut = sanitize($input['statut'] ?? '');
        $valid  = ['en_attente','confirmee','en_preparation','expediee','livree','annulee','remboursee'];
        if (!in_array($statut, $valid)) jsonError('Statut invalide.', 422);

        $orderModel->updateStatut($id, $statut);

        // Notifier le client
        $order = $orderModel->detail($id);
        if ($order) {
            $db = Database::getInstance();
            $msgs = [
                'confirmee'       => 'Votre commande #' . $order['numero'] . ' a été confirmée.',
                'en_preparation'  => 'Votre commande est en cours de préparation.',
                'expediee'        => 'Votre commande est en route ! Livraison prévue bientôt.',
                'livree'          => 'Votre commande a été livrée. Merci pour votre confiance !',
                'annulee'         => 'Votre commande a été annulée.',
            ];
            if (isset($msgs[$statut])) {
                $db->prepare(
                    "INSERT INTO notifications (user_id, titre, message, type) VALUES (?, ?, ?, ?)"
                )->execute([
                    $order['user_id'],
                    'Mise à jour commande #' . $order['numero'],
                    $msgs[$statut],
                    in_array($statut, ['livree','confirmee']) ? 'success' : 'info',
                ]);
            }
        }

        jsonSuccess([], 'Statut mis à jour.');
        break;

    // ── ADMIN : STATS DASHBOARD ───────────────────
    case 'stats':
        requireAdminAuth();
        $stats = $orderModel->statsAdmin();
        $stats['ventes_7j'] = $orderModel->ventesParJour(7);
        $stats['ventes_30j'] = $orderModel->ventesParJour(30);
        $stats['top_produits'] = $orderModel->topProduits(5);
        jsonSuccess($stats);
        break;

    default:
        jsonError('Action inconnue.', 404);
}
