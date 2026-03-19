<?php
/**
 * SneakX — API Livraisons & Géolocalisation
 * /backend/api/livraison.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = getInput();
$db     = Database::getInstance();

switch ($action) {

    // ── ENREGISTRER POSITION GPS ──────────────────
    case 'position':
        requireAuth();
        if ($method !== 'POST') jsonError('POST requis.', 405);

        $orderId = (int) ($input['order_id'] ?? 0);
        $lat     = (float) ($input['latitude'] ?? 0);
        $lng     = (float) ($input['longitude'] ?? 0);
        $adresse = sanitize($input['adresse'] ?? '');

        if (!$orderId || !$lat || !$lng) jsonError('Données incomplètes.', 422);

        // Mettre à jour la commande
        $db->prepare("UPDATE orders SET latitude = ?, longitude = ?, adresse_livraison = ? WHERE id = ? AND user_id = ?")
           ->execute([$lat, $lng, $adresse, $orderId, $_SESSION['user_id']]);

        // Mettre à jour la livraison
        $db->prepare("UPDATE deliveries SET latitude_dest = ?, longitude_dest = ?, adresse_dest = ? WHERE order_id = ?")
           ->execute([$lat, $lng, $adresse, $orderId]);

        jsonSuccess([
            'latitude'  => $lat,
            'longitude' => $lng,
            'adresse'   => $adresse,
        ], 'Position de livraison enregistrée.');
        break;

    // ── SUIVI EN TEMPS RÉEL (position livreur) ────
    case 'track':
        $orderId = (int) ($_GET['order_id'] ?? 0);
        if (!$orderId) jsonError('order_id requis.', 422);

        $stmt = $db->prepare(
            "SELECT d.statut, d.latitude_dest, d.longitude_dest, d.adresse_dest,
                    d.updated_at,
                    u.nom AS livreur_nom, u.prenom AS livreur_prenom, u.telephone AS livreur_tel
             FROM deliveries d
             LEFT JOIN users u ON u.id = d.livreur_id
             WHERE d.order_id = ? LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $delivery = $stmt->fetch();
        if (!$delivery) jsonError('Livraison non trouvée.', 404);

        // Position du dépôt Abidjan (point de départ)
        $delivery['depot'] = [
            'latitude'  => DEPOT_ABIDJAN_LAT,
            'longitude' => DEPOT_ABIDJAN_LNG,
            'nom'       => 'Dépôt SneakX — Plateau, Abidjan',
        ];

        jsonSuccess(['livraison' => $delivery]);
        break;

    // ── ASSIGNER UN LIVREUR (admin) ───────────────
    case 'assigner':
        requireAdminAuth();
        if ($method !== 'POST') jsonError('POST requis.', 405);

        $orderId   = (int) ($input['order_id'] ?? 0);
        $livreurId = (int) ($input['livreur_id'] ?? 0);
        if (!$orderId || !$livreurId) jsonError('order_id et livreur_id requis.', 422);

        $db->prepare(
            "UPDATE deliveries SET livreur_id = ?, statut = 'assigne' WHERE order_id = ?"
        )->execute([$livreurId, $orderId]);

        $db->prepare("UPDATE orders SET statut = 'expediee' WHERE id = ?")->execute([$orderId]);

        jsonSuccess([], 'Livreur assigné.');
        break;

    // ── LISTE DES LIVREURS ────────────────────────
    case 'livreurs':
        requireAdminAuth();
        $stmt = $db->query(
            "SELECT id, nom, prenom, telephone FROM users WHERE role = 'livreur' AND is_active = 1"
        );
        jsonSuccess(['livreurs' => $stmt->fetchAll()]);
        break;

    // ── CALCUL FRAIS DE LIVRAISON ─────────────────
    case 'frais':
        $total = (float) ($_GET['total'] ?? 0);
        $frais = $total >= FRAIS_LIVRAISON_GRATUITE ? 0 : FRAIS_LIVRAISON;
        jsonSuccess([
            'frais_livraison'     => $frais,
            'gratuite_a_partir'   => FRAIS_LIVRAISON_GRATUITE,
            'livraison_gratuite'  => $frais === 0,
        ]);
        break;

    // ── GÉOCODAGE ADRESSE (OpenStreetMap Nominatim) ─
    case 'geocode':
        $adresse = sanitize($_GET['adresse'] ?? '');
        if (empty($adresse)) jsonError('Adresse requise.', 422);

        $url = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($adresse . ', Abidjan, Côte d\'Ivoire') . '&limit=3';
        $ch  = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_HTTPHEADER     => ['User-Agent: SneakX-App/2.0'],
        ]);
        $result = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($result, true) ?? [];
        $formatted = array_map(fn($r) => [
            'latitude'  => (float) $r['lat'],
            'longitude' => (float) $r['lon'],
            'label'     => $r['display_name'],
        ], $data);

        jsonSuccess(['resultats' => $formatted]);
        break;

    // ── TOUTES LES LIVRAISONS CARTE (admin) ───────
    case 'carte_admin':
        requireAdminAuth();
        $stmt = $db->query(
            "SELECT o.id, o.numero, o.statut, o.latitude, o.longitude, o.adresse_livraison,
                    u.nom, u.prenom, u.telephone,
                    d.statut AS statut_livraison
             FROM orders o
             JOIN users u ON u.id = o.user_id
             LEFT JOIN deliveries d ON d.order_id = o.id
             WHERE o.latitude IS NOT NULL AND o.longitude IS NOT NULL
               AND o.statut NOT IN ('livree', 'annulee')
             ORDER BY o.created_at DESC LIMIT 100"
        );
        jsonSuccess(['positions' => $stmt->fetchAll()]);
        break;

    default:
        jsonError('Action inconnue. Utilisez: position, track, assigner, livreurs, frais, geocode, carte_admin', 404);
}
