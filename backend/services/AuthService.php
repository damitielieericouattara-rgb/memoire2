<?php
// Fichier: /backend/services/AuthService.php

require_once __DIR__ . '/../config/jwt.php';

class AuthService {
    /**
     * Génère un token JWT
     */
    public function generateToken($userId, $email, $role) {
        $issuedAt       = time();
        $expirationTime = $issuedAt + JWTConfig::getAccessTokenLifetime();

        $payload = [
            'user_id' => $userId,
            'email'   => $email,
            'role'    => $role,
            'iat'     => $issuedAt,
            'exp'     => $expirationTime
        ];

        return $this->encode($payload, JWTConfig::getSecretKey());
    }

    /**
     * Génère un refresh token
     */
    public function generateRefreshToken($userId) {
        $issuedAt       = time();
        $expirationTime = $issuedAt + JWTConfig::getRefreshTokenLifetime();

        $payload = [
            'user_id'  => $userId,
            'token_id' => bin2hex(random_bytes(16)),
            'iat'      => $issuedAt,
            'exp'      => $expirationTime,
            'type'     => 'refresh'
        ];

        return $this->encode($payload, JWTConfig::getRefreshSecretKey());
    }

    /**
     * Vérifie et décode un token
     */
    public function verifyToken($token) {
        try {
            return $this->decode($token, JWTConfig::getSecretKey());
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Vérifie un refresh token
     */
    public function verifyRefreshToken($token) {
        try {
            $payload = $this->decode($token, JWTConfig::getRefreshSecretKey());

            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                return null;
            }

            return $payload;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Encode un payload en JWT
     */
    private function encode($payload, $secret) {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];

        $headerEncoded  = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $secret,
            true
        );

        return $headerEncoded . '.' . $payloadEncoded . '.' . $this->base64UrlEncode($signature);
    }

    /**
     * Décode un JWT
     */
    private function decode($token, $secret) {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $signature         = $this->base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            $secret,
            true
        );

        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid signature');
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        if (isset($payload['exp']) && $payload['exp'] < time()) {
            throw new Exception('Token expired');
        }

        return $payload;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Envoie un email de vérification
     */
    public function sendVerificationEmail($email, $token) {
        // CORRECTION : AppConfig::get('BASE_URL') n'existe pas
        // Utiliser la propriété statique $appUrl directement
        $verificationLink = AppConfig::$appUrl . "/verify-email?token=$token";

        $subject = "Vérification de votre adresse email";
        $message = "
            <h2>Bienvenue sur E-Commerce Intelligent !</h2>
            <p>Veuillez cliquer sur le lien ci-dessous pour vérifier votre adresse email :</p>
            <p><a href='$verificationLink'>Vérifier mon email</a></p>
            <p>Ce lien expire dans 24 heures.</p>
        ";

        $emailService = new EmailService();
        return $emailService->send($email, $subject, $message);
    }
}
