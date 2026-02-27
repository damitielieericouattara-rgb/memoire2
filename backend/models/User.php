<?php
// Fichier: /backend/models/User.php

class User extends Model {
    protected $table = 'users';

    /**
     * Trouve un utilisateur par email
     */
    public function findByEmail($email) {
        return $this->where(['email' => $email], 1);
    }

    /**
     * Crée un utilisateur avec hash du mot de passe
     */
    public function createUser($data) {
        // CORRECTION : AppConfig::get() n'existe pas
        // Utiliser la propriété statique $bcryptCost directement
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, [
            'cost' => AppConfig::$bcryptCost
        ]);

        // Génère un token de vérification
        $data['verify_token'] = bin2hex(random_bytes(32));
        $data['created_at']   = date('Y-m-d H:i:s');

        return $this->create($data);
    }

    /**
     * Vérifie le mot de passe
     */
    public function verifyPassword($user, $password) {
        return password_verify($password, $user['password']);
    }

    /**
     * Met à jour la dernière connexion
     */
    public function updateLastLogin($userId) {
        return $this->update($userId, [
            'last_login' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Vérifie l'email
     */
    public function verifyEmail($token) {
        $sql = "UPDATE {$this->table}
                SET email_verified = 1, verify_token = NULL
                WHERE verify_token = :token";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token', $token);

        return $stmt->execute();
    }

    /**
     * Récupère les utilisateurs avec pagination
     */
    public function paginate($page = 1, $perPage = 20, $filters = []) {
        $offset = ($page - 1) * $perPage;

        $sql = "SELECT id, name, first_name, email, role, status, created_at
                FROM {$this->table}";

        $whereClauses = [];
        $params       = [];

        if (!empty($filters['role'])) {
            $whereClauses[] = "role = :role";
            $params[':role'] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $whereClauses[] = "status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit',  (int)$perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset,  PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->fetchAll();
    }
}
