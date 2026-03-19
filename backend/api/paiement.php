<?php
/**
 * SneakX — API Paiement
 * /backend/api/paiement.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = getInput();

switch ($action) {

    // ── INITIER UN PAIEMENT ───────────────────────
    case 'initier':
        requireAuth();
        if ($method !== 'POST') jsonError('POST requis.', 405);

        $orderId = (int) ($input['order_id'] ?? 0);
        $method2 = sanitize($input['methode'] ?? '');
        $amount  = (float) ($input['montant'] ?? 0);

        if (!$orderId || !$method2 || !$amount) jsonError('Données incomplètes.', 422);

        // Vérifier que la commande appartient au client
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        if (!$stmt->fetch()) jsonError('Commande non trouvée.', 404);

        $ctrl   = new PaymentController();
        $result = $ctrl->initiate($orderId, $method2, $amount, $input);
        jsonSuccess($result);
        break;

    // ── CONFIRMER (retour depuis passerelle) ──────
    case 'confirmer':
        $txnId  = sanitize($_GET['txn'] ?? $input['transaction_id'] ?? '');
        $method2= sanitize($_GET['method'] ?? $input['methode'] ?? '');
        $orderId= (int) ($_GET['order'] ?? $input['order_id'] ?? 0);

        if (!$txnId) jsonError('Transaction ID requis.', 422);

        $db   = Database::getInstance();
        // Marquer comme succès
        $stmt = $db->prepare("UPDATE payments SET statut = 'success' WHERE transaction_id = ? AND statut = 'pending'");
        $stmt->execute([$txnId]);

        if ($orderId) {
            $db->prepare("UPDATE orders SET statut = 'confirmee' WHERE id = ? AND statut = 'en_attente'")
               ->execute([$orderId]);
        }

        jsonSuccess(['confirmed' => true, 'transaction_id' => $txnId], 'Paiement confirmé.');
        break;

    // ── WEBHOOK PASSERELLES ───────────────────────
    case 'webhook':
        $raw     = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?? [];

        // Wave webhook
        if (!empty($payload['id']) && !empty($payload['client_reference'])) {
            $orderId = (int) str_replace('SNX-', '', $payload['client_reference']);
            if ($payload['payment_status'] === 'succeeded') {
                $db = Database::getInstance();
                $db->prepare("UPDATE orders SET statut = 'confirmee' WHERE id = ?")->execute([$orderId]);
                $db->prepare("UPDATE payments SET statut = 'success', transaction_id = ? WHERE order_id = ? AND methode = 'wave'")
                   ->execute([$payload['id'], $orderId]);
            }
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
        exit;

    // ── STATUT D'UN PAIEMENT ──────────────────────
    case 'statut':
        requireAuth();
        $orderId = (int) ($_GET['order_id'] ?? 0);
        if (!$orderId) jsonError('order_id requis.', 422);

        $db   = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT p.*, o.statut AS statut_commande
             FROM payments p
             JOIN orders o ON o.id = p.order_id
             WHERE p.order_id = ? AND o.user_id = ?
             ORDER BY p.created_at DESC LIMIT 1"
        );
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        $payment = $stmt->fetch();
        if (!$payment) jsonError('Paiement non trouvé.', 404);

        jsonSuccess(['payment' => $payment]);
        break;

    // ── SIMULATION (dev mode) ─────────────────────
    case 'simuler':
        if (APP_ENV !== 'development') jsonError('Simulation désactivée en production.', 403);

        $orderId = (int) ($input['order_id'] ?? 0);
        $txnId   = sanitize($input['txn_id'] ?? '');

        if ($orderId) {
            $db = Database::getInstance();
            $db->prepare("UPDATE orders SET statut = 'confirmee' WHERE id = ?")->execute([$orderId]);
            $db->prepare("UPDATE payments SET statut = 'success' WHERE order_id = ? AND statut = 'pending'")
               ->execute([$orderId]);
        }

        jsonSuccess(['simulated' => true, 'order_id' => $orderId], 'Paiement simulé avec succès !');
        break;

    default:
        jsonError('Action requise : initier, confirmer, webhook, statut, simuler', 404);
}
