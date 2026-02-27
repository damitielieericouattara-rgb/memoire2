<?php
// Fichier: /backend/controllers/CreditController.php
// ─── SIMULATEUR PAIEMENT EN CRÉDIT 3X ────────────────────────

class CreditController extends Controller {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }

    /** Simule un plan de crédit sans créer la commande */
    public function simulate() {
        $data = $this->getJsonInput();
        $this->validateRequired($data, ['amount']);

        $amount      = (float)$data['amount'];
        $installments = (int)($data['installments'] ?? 3);
        $interest    = CREDIT_3X_INTEREST;
        $total       = round($amount * (1 + $interest / 100), 2);
        $monthly     = round($total / $installments, 2);

        $schedule = [];
        for ($i = 1; $i <= $installments; $i++) {
            $schedule[] = [
                'installment'  => $i,
                'amount'       => $monthly,
                'due_date'     => date('d/m/Y', strtotime("+{$i} month")),
                'due_date_iso' => date('Y-m-d', strtotime("+{$i} month")),
            ];
        }

        Response::success([
            'original_amount' => $amount,
            'interest_rate'   => $interest,
            'interest_amount' => round($total - $amount, 2),
            'total_amount'    => $total,
            'installments'    => $installments,
            'monthly_amount'  => $monthly,
            'schedule'        => $schedule,
        ]);
    }

    /** Plans de crédit actifs de l'utilisateur */
    public function myPlans() {
        $user = $this->requireAuth();
        $stmt = $this->db->prepare(
            "SELECT cp.*, o.order_number,
                    (SELECT COUNT(*) FROM credit_payments WHERE plan_id = cp.id AND status = 'PAID') as paid_count
             FROM credit_plans cp
             JOIN orders o ON cp.order_id = o.id
             WHERE cp.user_id = ? ORDER BY cp.created_at DESC"
        );
        $stmt->execute([$user['id']]);
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($plans as &$plan) {
            $stmt2 = $this->db->prepare(
                "SELECT * FROM credit_payments WHERE plan_id = ? ORDER BY installment ASC"
            );
            $stmt2->execute([$plan['id']]);
            $plan['payments'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        Response::success($plans);
    }
}
