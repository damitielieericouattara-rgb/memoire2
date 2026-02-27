// Fichier: /frontend/assets/js/modules/wallet.js

import API from './api.js';

class Wallet {
    /**
     * Récupère le wallet
     */
    static async get() {
        try {
            const response = await API.get('/api/wallet');
            return response.data;
        } catch (error) {
            console.error('Erreur récupération wallet:', error);
            throw error;
        }
    }
    
    /**
     * Recharge le wallet
     */
    static async recharge(amount, paymentMethod = 'CARD') {
        try {
            const response = await API.post('/api/wallet/recharge', {
                amount: amount,
                payment_method: paymentMethod
            });
            
            // Met à jour le solde affiché
            this.updateBalance();
            
            return response;
        } catch (error) {
            console.error('Erreur recharge wallet:', error);
            throw error;
        }
    }
    
    /**
     * Récupère l'historique des transactions
     */
    static async getTransactions(limit = 50) {
        try {
            const response = await API.get('/api/wallet/transactions', { limit });
            return response.data;
        } catch (error) {
            console.error('Erreur récupération transactions:', error);
            throw error;
        }
    }
    
    /**
     * Met à jour le solde affiché dans le header
     */
    static async updateBalance() {
        try {
            const wallet = await this.get();
            const balanceElements = document.querySelectorAll('.wallet-balance');
            
            balanceElements.forEach(el => {
                el.textContent = this.formatAmount(wallet.balance);
            });
            
            // Sauvegarde en localStorage
            localStorage.setItem('wallet', JSON.stringify(wallet));
        } catch (error) {
            console.error('Erreur mise à jour solde:', error);
        }
    }
    
    /**
     * Formate un montant
     */
    static formatAmount(amount, currency = 'XOF') {
        return new Intl.NumberFormat('fr-FR', {
            style: 'decimal',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount) + ' ' + currency;
    }
}

export default Wallet;