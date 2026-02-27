<?php
// Fichier: /backend/services/WalletService.php

class WalletService {
    private $walletModel;
    private $fraudeService;
    
    public function __construct() {
        $this->walletModel = new Wallet();
        $this->fraudeService = new FraudeService();
    }
    
    /**
     * Recharge le wallet
     */
    public function recharge($userId, $amount, $paymentMethod = 'CARD') {
        // Validation
        if ($amount <= 0) {
            throw new Exception('Montant invalide');
        }
        
        if ($amount > 1000000) { // 1 million max
            throw new Exception('Montant trop élevé');
        }
        
        // Récupère le wallet
        $wallet = $this->walletModel->getByUserId($userId);
        
        if (!$wallet) {
            throw new Exception('Wallet non trouvé');
        }
        
        if ($wallet['status'] !== 'ACTIVE') {
            throw new Exception('Wallet bloqué ou suspendu');
        }
        
        // Analyse anti-fraude
        $fraudScore = $this->fraudeService->analyzeRecharge($userId, $amount);
        
        if ($fraudScore > 70) {
            // Alerte haute suspicion
            $this->fraudeService->createAlert([
                'user_id' => $userId,
                'anomaly_type' => 'RECHARGE_SUSPECTE',
                'risk_score' => $fraudScore,
                'description' => "Recharge de $amount XOF détectée comme suspecte"
            ]);
            
            throw new Exception('Transaction suspecte. Veuillez contacter le support.');
        }
        
        // Simule le paiement externe (en production, appeler l'API de paiement)
        $externalRef = 'PAY-' . time() . '-' . rand(1000, 9999);
        
        // Crédite le wallet
        $result = $this->walletModel->credit(
            $wallet['id'],
            $amount,
            "Recharge wallet via $paymentMethod - Réf: $externalRef"
        );
        
        // Envoie une notification
        $notificationService = new NotificationService();
        $notificationService->send($userId, [
            'type' => 'PAYMENT',
            'title' => 'Recharge confirmée',
            'message' => "Votre wallet a été crédité de $amount XOF",
            'channel' => 'WEBSOCKET'
        ]);
        
        return $result;
    }
    
    /**
     * Débite pour une commande
     */
    public function payForOrder($userId, $amount, $orderId) {
        $wallet = $this->walletModel->getByUserId($userId);
        
        if (!$wallet) {
            throw new Exception('Wallet non trouvé');
        }
        
        if ($wallet['status'] !== 'ACTIVE') {
            throw new Exception('Wallet bloqué');
        }
        
        if ($wallet['balance'] < $amount) {
            throw new Exception('Solde insuffisant');
        }
        
        // Analyse anti-fraude
        $fraudScore = $this->fraudeService->analyzeOrder($userId, $amount, $orderId);
        
        if ($fraudScore > 70) {
            throw new Exception('Transaction bloquée pour raisons de sécurité');
        }
        
        // Débite
        $result = $this->walletModel->debit(
            $wallet['id'],
            $amount,
            "Paiement commande #$orderId",
            $orderId
        );
        
        return $result;
    }
    
    /**
     * Récupère l'historique des transactions
     */
    public function getTransactionHistory($userId, $limit = 50) {
        $wallet = $this->walletModel->getByUserId($userId);
        
        if (!$wallet) {
            return [];
        }
        
        $transactionModel = new Transaction();
        return $transactionModel->getByWalletId($wallet['id'], $limit);
    }
}