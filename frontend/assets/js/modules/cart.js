// Fichier: /frontend/assets/js/modules/cart.js

import API from './api.js';

class Cart {
    /**
     * Récupère le panier
     */
    static async get() {
        try {
            const response = await API.get('/api/panier');
            return response.data;
        } catch (error) {
            console.error('Erreur lors de la récupération du panier:', error);
            throw error;
        }
    }
    
    /**
     * Ajoute un produit au panier
     */
    static async add(productId, quantity = 1) {
        try {
            const response = await API.post('/api/panier', {
                product_id: productId,
                quantity: quantity
            });
            
            // Met à jour le badge du panier
            this.updateBadge();
            
            return response;
        } catch (error) {
            console.error('Erreur lors de l\'ajout au panier:', error);
            throw error;
        }
    }
    
    /**
     * Met à jour la quantité
     */
    static async updateQuantity(itemId, quantity) {
        try {
            const response = await API.put(`/api/panier/${itemId}`, {
                quantity: quantity
            });
            return response;
        } catch (error) {
            console.error('Erreur lors de la mise à jour:', error);
            throw error;
        }
    }
    
    /**
     * Supprime un produit
     */
    static async remove(itemId) {
        try {
            const response = await API.delete(`/api/panier/${itemId}`);
            
            // Met à jour le badge
            this.updateBadge();
            
            return response;
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            throw error;
        }
    }
    
    /**
     * Vide le panier
     */
    static async clear() {
        try {
            const response = await API.delete('/api/panier');
            this.updateBadge();
            return response;
        } catch (error) {
            console.error('Erreur lors du vidage du panier:', error);
            throw error;
        }
    }
    
    /**
     * Met à jour le badge du panier dans le header
     */
    static async updateBadge() {
        try {
            const cart = await this.get();
            const badge = document.querySelector('#cart-badge');
            
            if (badge) {
                if (cart.count > 0) {
                    badge.textContent = cart.count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Erreur mise à jour badge:', error);
        }
    }
    
    /**
     * Calcule le total du panier
     */
    static calculateTotal(items) {
        return items.reduce((total, item) => {
            return total + item.subtotal;
        }, 0);
    }
}

export default Cart;