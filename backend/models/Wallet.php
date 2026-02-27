<?php
// Fichier: /backend/models/Wallet.php

class Wallet extends Model {
    protected $table = 'wallets';
    
    /**
     * Récupère le wallet d'un utilisateur
     */
    public function getByUserId($userId) {
        return $this->where(['user_id' => $userId], 1);
    }
    
    /**
     * Crée un wallet pour un utilisateur
     */
    public function createForUser($userId) {
        return $this->create([
            'user_id' => $userId,
            'balance' => 0.00,
            'currency' => 'XOF',
            'status' => 'ACTIVE',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Crédite le wallet (dans une transaction)
     */
    public function credit($walletId, $amount, $description = '') {
        Database::getInstance()->beginTransaction();
        
        try {
            // Verrouille et récupère le wallet
            $sql = "SELECT * FROM {$this->table} WHERE id = :id FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $walletId);
            $stmt->execute();
            $wallet = $stmt->fetch();
            
            if (!$wallet) {
                throw new Exception('Wallet non trouvé');
            }
            
            $balanceBefore = $wallet['balance'];
            $balanceAfter = $balanceBefore + $amount;
            
            // Met à jour le solde
            $sql = "UPDATE {$this->table} 
                    SET balance = :balance, last_operation = NOW() 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':balance', $balanceAfter);
            $stmt->bindValue(':id', $walletId);
            $stmt->execute();
            
            // Enregistre la transaction
            $transactionModel = new Transaction();
            $transactionId = $transactionModel->create([
                'wallet_id' => $walletId,
                'type' => 'CREDIT',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'VALIDATED',
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            Database::getInstance()->commit();
            
            return [
                'transaction_id' => $transactionId,
                'balance' => $balanceAfter
            ];
            
        } catch (Exception $e) {
            Database::getInstance()->rollBack();
            throw $e;
        }
    }
    
    /**
     * Débite le wallet (dans une transaction)
     */
    public function debit($walletId, $amount, $description = '', $orderId = null) {
        Database::getInstance()->beginTransaction();
        
        try {
            // Verrouille et récupère le wallet
            $sql = "SELECT * FROM {$this->table} WHERE id = :id FOR UPDATE";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $walletId);
            $stmt->execute();
            $wallet = $stmt->fetch();
            
            if (!$wallet) {
                throw new Exception('Wallet non trouvé');
            }
            
            $balanceBefore = $wallet['balance'];
            
            // Vérifie le solde
            if ($balanceBefore < $amount) {
                throw new Exception('Solde insuffisant');
            }
            
            $balanceAfter = $balanceBefore - $amount;
            
            // Met à jour le solde
            $sql = "UPDATE {$this->table} 
                    SET balance = :balance, last_operation = NOW() 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':balance', $balanceAfter);
            $stmt->bindValue(':id', $walletId);
            $stmt->execute();
            
            // Enregistre la transaction
            $transactionModel = new Transaction();
            $transactionId = $transactionModel->create([
                'wallet_id' => $walletId,
                'order_id' => $orderId,
                'type' => 'DEBIT',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'VALIDATED',
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            Database::getInstance()->commit();
            
            return [
                'transaction_id' => $transactionId,
                'balance' => $balanceAfter
            ];
            
        } catch (Exception $e) {
            Database::getInstance()->rollBack();
            throw $e;
        }
    }
}