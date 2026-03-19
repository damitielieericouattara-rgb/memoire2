<?php
/**
 * SneakX — Classe Authentification
 * /backend/classes/Auth.php
 */

class Auth {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(array $data): array {
        // Validation
        $errors = $this->validateRegister($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Vérifier email unique
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([strtolower($data['email'])]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Cet email est déjà utilisé.'];
        }

        // Hachage mot de passe
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);

        $stmt = $this->db->prepare(
            "INSERT INTO users (nom, prenom, email, telephone, password_hash, role)
             VALUES (?, ?, ?, ?, ?, 'client')"
        );
        $stmt->execute([
            sanitize($data['nom']),
            sanitize($data['prenom']),
            strtolower(trim($data['email'])),
            $data['telephone'] ?? null,
            $hash,
        ]);

        $userId = (int) $this->db->lastInsertId();
        $user   = $this->getUserById($userId);

        // Démarrer session
        $this->startSession($user);
        $token = $this->generateJWT($user);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => $this->publicUser($user),
        ];
    }

    /**
     * Connexion
     */
    public function login(string $email, string $password): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Email ou mot de passe incorrect.'];
        }

        // Rehash si nécessaire
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
        }

        $this->startSession($user);
        $token = $this->generateJWT($user);

        return [
            'success' => true,
            'token'   => $token,
            'user'    => $this->publicUser($user),
        ];
    }

    /**
     * Déconnexion
     */
    public function logout(): void {
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Vérifier le token JWT dans les headers
     */
    public function verifyToken(): ?array {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
        if (preg_match('/Bearer\s+(.+)/', $header, $m)) {
            return $this->decodeJWT($m[1]);
        }
        return null;
    }

    /**
     * Générer JWT simple (HMAC-SHA256)
     */
    public function generateJWT(array $user): string {
        $header  = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'sub'   => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role'],
            'iat'   => time(),
            'exp'   => time() + JWT_EXPIRY,
        ]));
        $sig = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", JWT_SECRET, true));
        return "{$header}.{$payload}.{$sig}";
    }

    /**
     * Décoder et valider JWT
     */
    public function decodeJWT(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $payload, $sig] = $parts;
        $expected = base64_encode(hash_hmac('sha256', "{$header}.{$payload}", JWT_SECRET, true));
        if (!hash_equals($expected, $sig)) return null;
        $data = json_decode(base64_decode($payload), true);
        if (!$data || ($data['exp'] ?? 0) < time()) return null;
        return $data;
    }

    /**
     * Démarrer la session PHP
     */
    private function startSession(array $user): void {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email']= $user['email'];
    }

    private function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    private function publicUser(array $user): array {
        return [
            'id'       => $user['id'],
            'nom'      => $user['nom'],
            'prenom'   => $user['prenom'],
            'email'    => $user['email'],
            'telephone'=> $user['telephone'],
            'role'     => $user['role'],
            'avatar'   => $user['avatar'],
        ];
    }

    private function validateRegister(array $data): array {
        $errors = [];
        if (empty($data['nom']))    $errors[] = 'Le nom est requis.';
        if (empty($data['prenom'])) $errors[] = 'Le prénom est requis.';
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        return $errors;
    }
}
