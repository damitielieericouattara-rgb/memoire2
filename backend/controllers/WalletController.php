<?php
// Fichier: /backend/controllers/WalletController.php

class WalletController extends Controller {
    private $walletModel;

    public function __construct() {
        $this->walletModel = new Wallet();
    }

    public function index() {
        $user   = $this->getUser();
        $wallet = $this->walletModel->getByUserId($user['id']);
        if (!$wallet) {
            // Crée le wallet s'il n'existe pas
            $this->walletModel->createForUser($user['id']);
            $wallet = $this->walletModel->getByUserId($user['id']);
        }
        Response::success($wallet, 'Wallet récupéré');
    }

    public function recharge() {
        $user = $this->getUser();
        $data = $this->getJsonInput();
        if (!$data || !isset($data['amount'])) {
            Response::error('Montant requis', 400);
        }
        $amount = (float)$data['amount'];
        if ($amount <= 0 || $amount > 1000000) {
            Response::error('Montant invalide (1 - 1 000 000 XOF)', 400);
        }

        $wallet = $this->walletModel->getByUserId($user['id']);
        if (!$wallet) {
            $this->walletModel->createForUser($user['id']);
            $wallet = $this->walletModel->getByUserId($user['id']);
        }

        try {
            $result = $this->walletModel->credit($wallet['id'], $amount, "Recharge wallet");
            Response::success($result, 'Wallet rechargé avec succès');
        } catch (Exception $e) {
            Response::error($e->getMessage(), 500);
        }
    }

    public function transactions() {
        $user   = $this->getUser();
        $wallet = $this->walletModel->getByUserId($user['id']);
        if (!$wallet) {
            Response::success([], 'Aucune transaction');
        }
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM transactions WHERE wallet_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$wallet['id']]);
            $transactions = $stmt->fetchAll();
            Response::success($transactions, 'Transactions récupérées');
        } catch (Exception $e) {
            Response::success([], 'Transactions récupérées');
        }
    }
}
