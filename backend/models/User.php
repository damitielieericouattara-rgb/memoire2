<?php
/**
 * SneakX — Modèle Utilisateur
 * /backend/models/User.php
 */

class User extends Model {
    protected string $table = 'users';

    /**
     * Profil public (sans mot de passe)
     */
    public function publicProfile(int $userId): ?array {
        $user = $this->find($userId);
        if (!$user) return null;
        unset($user['password_hash'], $user['token_verif'], $user['reset_token']);
        return $user;
    }

    /**
     * Mettre à jour le profil
     */
    public function updateProfile(int $userId, array $data): array {
        $allowed = ['nom', 'prenom', 'telephone', 'avatar'];
        $update  = [];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $update[$field] = sanitize($data[$field]);
            }
        }
        if (empty($update)) return ['success' => false, 'error' => 'Aucune donnée à mettre à jour.'];

        $this->update($userId, $update);
        return ['success' => true, 'message' => 'Profil mis à jour.', 'data' => $update];
    }

    /**
     * Changer le mot de passe
     */
    public function changePassword(int $userId, string $current, string $newPass): array {
        $user = $this->find($userId);
        if (!$user) return ['success' => false, 'error' => 'Utilisateur introuvable.'];
        if (!password_verify($current, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Mot de passe actuel incorrect.'];
        }
        if (strlen($newPass) < 8) {
            return ['success' => false, 'error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'];
        }
        $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $this->update($userId, ['password_hash' => $hash]);
        return ['success' => true, 'message' => 'Mot de passe modifié avec succès.'];
    }

    /**
     * Liste admin des utilisateurs
     */
    public function adminList(array $filters = [], int $page = 1): array {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[]  = "(nom LIKE ? OR prenom LIKE ? OR email LIKE ?)";
            $like     = '%' . $filters['q'] . '%';
            $params   = [$like, $like, $like];
        }
        if (!empty($filters['role'])) {
            $where[]  = 'role = ?';
            $params[] = $filters['role'];
        }

        $whereStr = implode(' AND ', $where);
        $perPage  = 20;
        $offset   = ($page - 1) * $perPage;

        $users = $this->query(
            "SELECT id, nom, prenom, email, telephone, role, is_active, created_at
             FROM users
             WHERE {$whereStr}
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [...$params, $perPage, $offset]
        );

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE {$whereStr}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        return ['items' => $users, 'total' => $total, 'last_page' => ceil($total / $perPage)];
    }

    /**
     * Statistiques utilisateur
     */
    public function stats(int $userId): array {
        return [
            'nb_commandes' => (int) $this->queryValue(
                "SELECT COUNT(*) FROM orders WHERE user_id = ?", [$userId]
            ),
            'total_depense' => (float) $this->queryValue(
                "SELECT COALESCE(SUM(total_ttc),0) FROM orders WHERE user_id = ? AND statut NOT IN ('annulee','remboursee')", [$userId]
            ),
            'nb_wishlist'  => (int) $this->queryValue(
                "SELECT COUNT(*) FROM wishlists WHERE user_id = ?", [$userId]
            ),
            'nb_avis'      => (int) $this->queryValue(
                "SELECT COUNT(*) FROM product_reviews WHERE user_id = ?", [$userId]
            ),
        ];
    }
}
