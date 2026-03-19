/**
 * SneakX — Module Cart (compatibilité statique)
 * Wraps API.panier pour une interface orientée classe
 */
import API from './api.js';

class Cart {
    static async get() {
        const res = await API.panier.get();
        return res;
    }

    static async add(productId, quantity = 1, taille = '', couleur = '') {
        const res = await API.panier.add(productId, quantity, taille, couleur);
        await this.updateBadge();
        return res;
    }

    static async updateQuantity(cartId, quantity) {
        return API.panier.update(cartId, quantity);
    }

    static async remove(cartId) {
        const res = await API.panier.remove(cartId);
        await this.updateBadge();
        return res;
    }

    static async clear() {
        const res = await API.panier.clear();
        await this.updateBadge();
        return res;
    }

    static async updateBadge() {
        try {
            const count = await API.panier.count();
            const badge = document.getElementById('cart-count');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        } catch(e) {}
    }

    static calculateTotal(items) {
        return items.reduce((t, i) => t + (i.prix_effectif || i.prix || i.price || 0) * (i.quantite || i.quantity || 1), 0);
    }
}

export default Cart;
