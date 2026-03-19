<?php
/**
 * SneakX — Contrôleur de Paiement
 * Gère : Wave, Orange Money, MTN MoMo, Moov Money, Carte Bancaire, Livraison
 * /backend/controllers/PaymentController.php
 */

class PaymentController {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Initier un paiement selon la méthode
     */
    public function initiate(int $orderId, string $method, float $amount, array $extra = []): array {
        return match ($method) {
            'wave'           => $this->initiateWave($orderId, $amount),
            'orange_money'   => $this->initiateOrangeMoney($orderId, $amount, $extra),
            'mtn_momo'       => $this->initiateMtnMomo($orderId, $amount, $extra),
            'moov_money'     => $this->initiateMoovMoney($orderId, $amount, $extra),
            'carte_bancaire' => $this->initiateCarte($orderId, $amount, $extra),
            'livraison'      => $this->initiateLivraison($orderId, $amount),
            default          => ['success' => false, 'error' => 'Méthode de paiement non supportée.'],
        };
    }

    // ─── WAVE CI ──────────────────────────────────
    private function initiateWave(int $orderId, float $amount): array {
        $order = $this->getOrder($orderId);
        if (!$order) return ['success' => false, 'error' => 'Commande introuvable.'];

        $payload = [
            'currency'    => 'XOF',
            'amount'      => (int) $amount,
            'success_url' => APP_URL . '/pages/commande-succes.html?order=' . $orderId . '&method=wave',
            'error_url'   => APP_URL . '/pages/paiement-echec.html?order=' . $orderId,
            'client_reference' => 'SNX-' . $orderId,
        ];

        // Appel API Wave réel
        $response = $this->httpPost(WAVE_API_URL, $payload, [
            'Authorization: Bearer ' . WAVE_API_KEY,
            'Content-Type: application/json',
        ]);

        if ($response['http_code'] === 200 && !empty($response['data']['wave_launch_url'])) {
            $this->savePayment($orderId, 'wave', $amount, 'pending', $response['data']['id'] ?? null, $payload);
            return [
                'success'      => true,
                'redirect_url' => $response['data']['wave_launch_url'],
                'payment_id'   => $response['data']['id'] ?? null,
            ];
        }

        // Mode simulation (si pas de clé API configurée)
        $simulatedId = 'WAVE-' . strtoupper(bin2hex(random_bytes(8)));
        $this->savePayment($orderId, 'wave', $amount, 'pending', $simulatedId, $payload);

        return [
            'success'      => true,
            'redirect_url' => APP_URL . '/pages/paiement-simulation.html?order=' . $orderId . '&method=wave&txn=' . $simulatedId,
            'payment_id'   => $simulatedId,
            'simulated'    => true,
        ];
    }

    // ─── ORANGE MONEY CI ──────────────────────────
    private function initiateOrangeMoney(int $orderId, float $amount, array $extra): array {
        $phone = $extra['telephone'] ?? '';
        if (empty($phone)) return ['success' => false, 'error' => 'Numéro de téléphone requis pour Orange Money.'];

        $payload = [
            'merchant_key'  => OM_MERCHANT_KEY,
            'currency'      => 'OUV',
            'order_id'      => 'SNX-' . $orderId,
            'amount'        => (int) $amount,
            'return_url'    => APP_URL . '/backend/api/paiement_callback.php?method=orange_money&order=' . $orderId,
            'cancel_url'    => APP_URL . '/pages/paiement-echec.html?order=' . $orderId,
            'notif_url'     => APP_URL . '/backend/api/paiement_webhook.php',
            'lang'          => 'fr',
            'reference'     => 'SNX-' . $orderId,
        ];

        $response = $this->httpPost(OM_API_URL . '/webpayment', $payload, [
            'Authorization: Bearer ' . OM_TOKEN,
            'Content-Type: application/json',
        ]);

        if ($response['http_code'] === 200 && !empty($response['data']['payment_url'])) {
            $this->savePayment($orderId, 'orange_money', $amount, 'pending', null, $payload);
            return [
                'success'      => true,
                'redirect_url' => $response['data']['payment_url'],
            ];
        }

        // Simulation
        $simulatedId = 'OM-' . strtoupper(bin2hex(random_bytes(6)));
        $this->savePayment($orderId, 'orange_money', $amount, 'pending', $simulatedId, $payload);

        return [
            'success'      => true,
            'redirect_url' => APP_URL . '/pages/paiement-simulation.html?order=' . $orderId . '&method=orange_money&txn=' . $simulatedId,
            'payment_id'   => $simulatedId,
            'simulated'    => true,
            'instructions' => "Composez *144*82*{$phone}*{$amount}# sur votre téléphone Orange.",
        ];
    }

    // ─── MTN MOMO CI ──────────────────────────────
    private function initiateMtnMomo(int $orderId, float $amount, array $extra): array {
        $phone = $extra['telephone'] ?? '';
        if (empty($phone)) return ['success' => false, 'error' => 'Numéro MTN requis.'];

        $simulatedId = 'MTN-' . strtoupper(bin2hex(random_bytes(6)));
        $this->savePayment($orderId, 'mtn_momo', $amount, 'pending', $simulatedId, ['phone' => $phone]);

        return [
            'success'      => true,
            'redirect_url' => APP_URL . '/pages/paiement-simulation.html?order=' . $orderId . '&method=mtn_momo&txn=' . $simulatedId,
            'payment_id'   => $simulatedId,
            'simulated'    => true,
            'instructions' => "Vous allez recevoir un message MTN pour confirmer le paiement de " . number_format($amount, 0, ',', ' ') . " FCFA.",
        ];
    }

    // ─── MOOV MONEY ───────────────────────────────
    private function initiateMoovMoney(int $orderId, float $amount, array $extra): array {
        $phone = $extra['telephone'] ?? '';
        $simulatedId = 'MOOV-' . strtoupper(bin2hex(random_bytes(6)));
        $this->savePayment($orderId, 'moov_money', $amount, 'pending', $simulatedId, ['phone' => $phone]);

        return [
            'success'      => true,
            'redirect_url' => APP_URL . '/pages/paiement-simulation.html?order=' . $orderId . '&method=moov_money&txn=' . $simulatedId,
            'payment_id'   => $simulatedId,
            'simulated'    => true,
            'instructions' => "Composez *155# sur votre téléphone Moov pour confirmer le paiement.",
        ];
    }

    // ─── CARTE BANCAIRE ───────────────────────────
    private function initiateCarte(int $orderId, float $amount, array $extra): array {
        // Validation basique de la carte
        $cardNumber = preg_replace('/\s/', '', $extra['card_number'] ?? '');
        $expiry     = $extra['expiry'] ?? '';
        $cvv        = $extra['cvv'] ?? '';

        if (strlen($cardNumber) < 16) return ['success' => false, 'error' => 'Numéro de carte invalide.'];
        if (empty($expiry))           return ['success' => false, 'error' => 'Date d\'expiration requise.'];
        if (empty($cvv))              return ['success' => false, 'error' => 'CVV requis.'];

        // En production : intégrer CinetPay, Flutterwave, etc.
        $simulatedId = 'CARD-' . strtoupper(bin2hex(random_bytes(8)));
        $this->savePayment($orderId, 'carte_bancaire', $amount, 'pending', $simulatedId, [
            'card_last4' => substr($cardNumber, -4),
        ]);

        return [
            'success'    => true,
            'payment_id' => $simulatedId,
            'message'    => 'Paiement en cours de traitement...',
            'simulated'  => true,
        ];
    }

    // ─── PAIEMENT À LA LIVRAISON ──────────────────
    private function initiateLivraison(int $orderId, float $amount): array {
        $simulatedId = 'COD-' . $orderId;
        $this->savePayment($orderId, 'livraison', $amount, 'pending', $simulatedId, []);

        // Confirmer directement la commande
        $this->db->prepare("UPDATE orders SET statut = 'confirmee' WHERE id = ?")->execute([$orderId]);

        return [
            'success'    => true,
            'payment_id' => $simulatedId,
            'message'    => 'Commande confirmée. Vous paierez à la livraison.',
        ];
    }

    /**
     * Confirmer un paiement (webhook/callback)
     */
    public function confirm(string $transactionId, string $method): bool {
        $stmt = $this->db->prepare(
            "UPDATE payments SET statut = 'success', transaction_id = ?
             WHERE transaction_id = ? AND statut = 'pending'"
        );
        $stmt->execute([$transactionId, $transactionId]);

        if ($stmt->rowCount() > 0) {
            // Mettre à jour le statut de la commande
            $payment = $this->queryPayment($transactionId);
            if ($payment) {
                $this->db->prepare("UPDATE orders SET statut = 'confirmee' WHERE id = ?")->execute([$payment['order_id']]);
            }
            return true;
        }
        return false;
    }

    // ─── Helpers ──────────────────────────────────

    private function savePayment(int $orderId, string $method, float $amount, string $statut, ?string $txnId, array $payload): void {
        $stmt = $this->db->prepare(
            "INSERT INTO payments (order_id, methode, montant, statut, transaction_id, payload)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$orderId, $method, $amount, $statut, $txnId, json_encode($payload)]);
    }

    private function getOrder(int $orderId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        return $stmt->fetch() ?: null;
    }

    private function queryPayment(string $txnId): ?array {
        $stmt = $this->db->prepare("SELECT * FROM payments WHERE transaction_id = ? LIMIT 1");
        $stmt->execute([$txnId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Requête HTTP POST
     */
    private function httpPost(string $url, array $data, array $headers = []): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $body      = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'data'      => json_decode($body, true) ?? [],
        ];
    }
}
