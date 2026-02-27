<?php
// Fichier: /backend/core/Database.php

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = DatabaseConfig::getConfig();

        try {
            $this->pdo = new PDO(
                DatabaseConfig::getDSN(),
                $config['user'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');

            // Message d'aide selon le type d'erreur
            $errCode = $e->getCode();
            $hint = match(true) {
                str_contains($e->getMessage(), 'Unknown database')
                    => "La base de données n'existe pas. Créez-la dans phpMyAdmin ou changez 'dbname' dans config/database.php",
                str_contains($e->getMessage(), 'Access denied')
                    => "Identifiants MySQL incorrects. Vérifiez 'user' et 'pass' dans config/database.php",
                str_contains($e->getMessage(), "Can't connect") || str_contains($e->getMessage(), 'Connection refused')
                    => "MySQL n'est pas démarré, ou 'host' est incorrect dans config/database.php",
                default => "Vérifiez config/database.php et que MySQL est bien lancé"
            };

            echo json_encode([
                'success' => false,
                'message' => 'Erreur de connexion à la base de données',
                'hint'    => $hint,
                'debug'   => $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function beginTransaction() { return $this->pdo->beginTransaction(); }
    public function commit()           { return $this->pdo->commit(); }
    public function rollBack()         { return $this->pdo->rollBack(); }

    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}
