<?php
// Fichier: /backend/controllers/AccessibiliteController.php
// ─── GESTION PROFIL ACCESSIBILITÉ ────────────────────────────

class AccessibiliteController extends Controller {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    /** Récupère le profil accessibilité */
    public function getProfile() {
        $user = $this->requireAuth();
        $stmt = $this->db->prepare(
            "SELECT * FROM accessibility_profiles WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$user['id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$profile) {
            // Crée le profil
            $this->db->prepare(
                "INSERT INTO accessibility_profiles (user_id) VALUES (?)"
            )->execute([$user['id']]);
            $profile = ['user_id' => $user['id'], 'vocal_mode' => 0, 'low_vision_mode' => 0,
                        'sign_language' => 0, 'high_contrast' => 0, 'font_size' => 16,
                        'speech_rate' => 1.0, 'subtitles' => 0];
        }
        Response::success($profile);
    }

    /** Met à jour le profil accessibilité */
    public function updateProfile() {
        $user = $this->requireAuth();
        $data = $this->getJsonInput();

        $allowed = ['low_vision_mode','vocal_mode','sign_language','high_contrast',
                    'font_size','speech_rate','subtitles','screen_reader_active'];
        $update  = array_intersect_key($data, array_flip($allowed));

        if (empty($update)) Response::error('Aucune donnée valide', 400);

        // Upsert
        $check = $this->db->prepare(
            "SELECT id FROM accessibility_profiles WHERE user_id = ?"
        );
        $check->execute([$user['id']]);
        if ($check->fetch()) {
            $sets   = implode(', ', array_map(fn($k) => "$k = ?", array_keys($update)));
            $params = array_values($update);
            $params[] = $user['id'];
            $this->db->prepare(
                "UPDATE accessibility_profiles SET $sets WHERE user_id = ?"
            )->execute($params);
        } else {
            $update['user_id'] = $user['id'];
            $cols  = implode(', ', array_keys($update));
            $marks = implode(', ', array_fill(0, count($update), '?'));
            $this->db->prepare(
                "INSERT INTO accessibility_profiles ($cols) VALUES ($marks)"
            )->execute(array_values($update));
        }

        // Met à jour accessibility_mode dans users
        if (isset($data['mode'])) {
            $this->db->prepare(
                "UPDATE users SET accessibility_mode = ? WHERE id = ?"
            )->execute([$data['mode'], $user['id']]);
        }

        Response::success(null, 'Profil accessibilité mis à jour');
    }

    /** Log usage accessibilité */
    public function logUsage() {
        $data    = $this->getJsonInput();
        $userId  = $this->getUser() ? $this->getUser()['id'] : null;
        $session = session_id() ?: ($data['session_id'] ?? uniqid());

        $this->db->prepare(
            "INSERT INTO accessibility_stats (user_id, session_id, mode_used, feature, duration_sec)
             VALUES (?,?,?,?,?)"
        )->execute([
            $userId, $session,
            $data['mode']    ?? 'STANDARD',
            $data['feature'] ?? 'unknown',
            $data['duration'] ?? null,
        ]);
        Response::success(null, 'Usage enregistré');
    }

    /** Log commande vocale */
    public function logVoice() {
        $data    = $this->getJsonInput();
        $userId  = $this->getUser() ? $this->getUser()['id'] : null;

        $this->db->prepare(
            "INSERT INTO voice_stats (user_id, command, intent, success, lang)
             VALUES (?,?,?,?,?)"
        )->execute([
            $userId,
            $data['command'] ?? '',
            $data['intent']  ?? 'unknown',
            $data['success'] ? 1 : 0,
            $data['lang']    ?? 'fr',
        ]);
        Response::success(null, 'Stat vocale enregistrée');
    }

    /** Taux de change multi-devise */
    public function getCurrencies() {
        $currencies = MultiDeviseService::getSupportedCurrencies();
        $result     = [];
        foreach ($currencies as $code) {
            $result[$code] = [
                'rate'      => MultiDeviseService::convert(1, $code),
                'formatted' => MultiDeviseService::format(1000, $code),
            ];
        }
        Response::success($result);
    }

    /** Convertit un montant */
    public function convertAmount() {
        $data     = $this->getJsonInput();
        $amount   = (float)($data['amount'] ?? 0);
        $currency = $data['currency'] ?? 'EUR';

        $converted = MultiDeviseService::convert($amount, $currency);
        Response::success([
            'original'   => $amount,
            'converted'  => $converted,
            'currency'   => $currency,
            'formatted'  => MultiDeviseService::format($converted, $currency),
            'all'        => MultiDeviseService::allCurrencies($amount),
        ]);
    }
}
