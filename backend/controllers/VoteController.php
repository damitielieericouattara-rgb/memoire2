<?php
// Fichier: /backend/controllers/VoteController.php
// ─── VOTE COMMUNAUTAIRE PROMOTIONS ───────────────────────────

class VoteController extends Controller {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    /** Liste les promotions votables */
    public function index() {
        $user = $this->requireAuth();
        $stmt = $this->db->prepare(
            "SELECT p.*,
                    COUNT(vp.id) as current_votes,
                    MAX(CASE WHEN vp.user_id = ? THEN 1 ELSE 0 END) as user_voted
             FROM promotions p
             LEFT JOIN votes_promotions vp ON vp.promotion_id = p.id
             WHERE p.community_promo = 1
             AND (p.end_date IS NULL OR p.end_date > NOW())
             GROUP BY p.id ORDER BY current_votes DESC"
        );
        $stmt->execute([$user['id']]);
        $promos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ajoute le % progress
        foreach ($promos as &$p) {
            $p['progress_pct'] = $p['activation_threshold'] > 0
                ? min(100, round($p['current_votes'] / $p['activation_threshold'] * 100))
                : 100;
        }
        Response::success($promos);
    }

    /** Vote pour une promo */
    public function vote($id) {
        $user = $this->requireAuth();

        // Vérifie que la promo existe et est votable
        $promo = $this->db->prepare(
            "SELECT * FROM promotions WHERE id = ? AND community_promo = 1 LIMIT 1"
        );
        $promo->execute([$id]);
        $p = $promo->fetch(PDO::FETCH_ASSOC);
        if (!$p) Response::error('Promotion non trouvée', 404);
        if ($p['active']) Response::error('Cette promo est déjà active !', 400);

        // Vérifie si déjà voté
        $existing = $this->db->prepare(
            "SELECT id FROM votes_promotions WHERE user_id = ? AND promotion_id = ?"
        );
        $existing->execute([$user['id'], $id]);
        if ($existing->fetch()) Response::error('Vous avez déjà voté pour cette promo', 409);

        try {
            $this->db->beginTransaction();

            // Enregistre le vote
            $this->db->prepare(
                "INSERT INTO votes_promotions (user_id, promotion_id) VALUES (?,?)"
            )->execute([$user['id'], $id]);

            // Met à jour le compteur
            $this->db->prepare(
                "UPDATE promotions SET votes_count = votes_count + 1 WHERE id = ?"
            )->execute([$id]);

            // Vérifie si seuil atteint
            $newVotes = (int)$p['votes_count'] + 1;
            if ($newVotes >= $p['activation_threshold']) {
                // Active la promo !
                $this->db->prepare(
                    "UPDATE promotions SET active = 1, start_date = NOW() WHERE id = ?"
                )->execute([$id]);

                // Notifie toute la communauté
                $notif = new NotificationService();
                $notif->notifyPromoActivated($p['code'], $p['value']);

                $this->db->commit();
                Response::success([
                    'votes'     => $newVotes,
                    'activated' => true,
                    'code'      => $p['code'],
                    'discount'  => $p['value'],
                    'message'   => "🎉 La promo a été débloquée ! Code: {$p['code']}",
                ], 'Promo activée par la communauté !');
            }

            $this->db->commit();
            Response::success([
                'votes'     => $newVotes,
                'threshold' => $p['activation_threshold'],
                'remaining' => max(0, $p['activation_threshold'] - $newVotes),
                'activated' => false,
            ], 'Vote enregistré !');

        } catch (Exception $e) {
            $this->db->rollBack();
            Response::error($e->getMessage(), 500);
        }
    }
}
