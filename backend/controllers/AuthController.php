<?php
// Fichier: /backend/controllers/AuthController.php

class AuthController extends Controller {
    private $userModel;
    private $authService;
    private $walletModel;

    public function __construct() {
        $this->userModel   = new User();
        $this->authService = new AuthService();
        $this->walletModel = new Wallet();
    }

    /** Inscription */
    public function register() {
        $data = $this->getJsonInput();
        if (!$data) {
            Response::error('Données JSON invalides', 400);
        }
        $this->validateRequired($data, ['name', 'first_name', 'email', 'password', 'password_confirm']);

        // Sanitize sauf mot de passe
        $name       = htmlspecialchars(strip_tags(trim($data['name'])), ENT_QUOTES, 'UTF-8');
        $first_name = htmlspecialchars(strip_tags(trim($data['first_name'])), ENT_QUOTES, 'UTF-8');
        $email      = htmlspecialchars(strip_tags(trim($data['email'])), ENT_QUOTES, 'UTF-8');
        $password         = $data['password'];
        $password_confirm = $data['password_confirm'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Adresse email invalide', 400);
        }
        if ($this->userModel->findByEmail($email)) {
            Response::error('Cette adresse email est déjà utilisée', 400);
        }
        if ($password !== $password_confirm) {
            Response::error('Les mots de passe ne correspondent pas', 400);
        }
        if (strlen($password) < 8) {
            Response::error('Le mot de passe doit contenir au moins 8 caractères', 400);
        }
        if (empty($data['rgpd_consent'])) {
            Response::error("Vous devez accepter les conditions d'utilisation", 400);
        }

        try {
            $userId = $this->userModel->createUser([
                'name'         => $name,
                'first_name'   => $first_name,
                'email'        => $email,
                'password'     => $password,
                'phone'        => $data['phone'] ?? null,
                'rgpd_consent' => 1,
                'rgpd_date'    => date('Y-m-d H:i:s'),
                'status'       => 'ACTIVE', // ACTIVE directement pour simplifier (pas besoin vérif email)
                'email_verified' => 1,
                'role'         => 'CLIENT',
            ]);

            // Crée le wallet
            $this->walletModel->createForUser($userId);

            // Crée profil accessibilité (ignore l'erreur si la table n'existe pas)
            try {
                $db   = Database::getInstance()->getConnection();
                $stmt = $db->prepare("INSERT INTO accessibility_profiles (user_id) VALUES (?)");
                $stmt->execute([$userId]);
            } catch (Exception $e) {
                // non bloquant
            }

            Response::success([
                'user_id' => $userId,
            ], 'Inscription réussie ! Vous pouvez vous connecter.', 201);

        } catch (Exception $e) {
            Response::error("Erreur lors de l'inscription : " . $e->getMessage(), 500);
        }
    }

    /** Connexion */
    public function login() {
        $data = $this->getJsonInput();
        if (!$data) {
            Response::error('Données JSON invalides', 400);
        }
        $this->validateRequired($data, ['email', 'password']);

        $email    = trim($data['email']);
        $password = $data['password'];

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            Response::error('Email ou mot de passe incorrect', 401);
        }
        if (!$this->userModel->verifyPassword($user, $password)) {
            Response::error('Email ou mot de passe incorrect', 401);
        }
        if ($user['status'] === 'BANNED') {
            Response::error('Votre compte a été banni', 403);
        }

        // Génère les tokens
        $accessToken  = $this->authService->generateToken($user['id'], $user['email'], $user['role']);
        $refreshToken = $this->authService->generateRefreshToken($user['id']);

        // Met à jour dernière connexion
        try { $this->userModel->updateLastLogin($user['id']); } catch (Exception $e) {}

        // Récupère le wallet
        $wallet = $this->walletModel->getByUserId($user['id']);

        Response::success([
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'user' => [
                'id'                 => $user['id'],
                'name'               => $user['name'],
                'first_name'         => $user['first_name'],
                'email'              => $user['email'],
                'role'               => $user['role'],
                'accessibility_mode' => $user['accessibility_mode'] ?? 'STANDARD',
            ],
            'wallet' => [
                'balance'  => $wallet ? (float)$wallet['balance'] : 0,
                'currency' => $wallet ? $wallet['currency'] : 'XOF',
            ],
        ], 'Connexion réussie');
    }

    /** Rafraîchit le token */
    public function refresh() {
        $data = $this->getJsonInput();
        if (!isset($data['refresh_token'])) {
            Response::error('Refresh token manquant', 400);
        }
        $payload = $this->authService->verifyRefreshToken($data['refresh_token']);
        if (!$payload) {
            Response::error('Refresh token invalide ou expiré', 401);
        }
        $user = $this->userModel->find($payload['user_id']);
        if (!$user || $user['status'] !== 'ACTIVE') {
            Response::error('Utilisateur non trouvé ou inactif', 401);
        }
        $accessToken = $this->authService->generateToken($user['id'], $user['email'], $user['role']);
        Response::success(['access_token' => $accessToken], 'Token rafraîchi');
    }

    /** Vérification email */
    public function verifyEmail() {
        $token = $_GET['token'] ?? null;
        if (!$token) { Response::error('Token manquant', 400); }
        if ($this->userModel->verifyEmail($token)) {
            Response::success(null, 'Email vérifié avec succès');
        } else {
            Response::error('Token invalide ou expiré', 400);
        }
    }

    /** Profil utilisateur */
    public function profile() {
        $user   = $this->getUser();
        $wallet = $this->walletModel->getByUserId($user['id']);
        Response::success([
            'user' => [
                'id'                 => $user['id'],
                'name'               => $user['name'],
                'first_name'         => $user['first_name'],
                'email'              => $user['email'],
                'phone'              => $user['phone'] ?? null,
                'role'               => $user['role'],
                'accessibility_mode' => $user['accessibility_mode'] ?? 'STANDARD',
                'created_at'         => $user['created_at'],
            ],
            'wallet' => $wallet,
        ]);
    }
}
