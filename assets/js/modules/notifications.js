// Fichier: /frontend/assets/js/modules/notifications.js

import API from './api.js';

class Notifications {
    /**
     * Récupère les notifications
     */
    static async get(unreadOnly = false) {
        try {
            const response = await API.get('/api/notifications', { unread_only: unreadOnly });
            return response.data;
        } catch (error) {
            console.error('Erreur récupération notifications:', error);
            throw error;
        }
    }
    
    /**
     * Marque comme lue
     */
    static async markAsRead(notificationId) {
        try {
            const response = await API.put(`/api/notifications/${notificationId}/read`);
            this.updateBadge();
            return response;
        } catch (error) {
            console.error('Erreur marquage notification:', error);
            throw error;
        }
    }
    
    /**
     * Marque toutes comme lues
     */
    static async markAllAsRead() {
        try {
            const response = await API.put('/api/notifications/read-all');
            this.updateBadge();
            return response;
        } catch (error) {
            console.error('Erreur marquage notifications:', error);
            throw error;
        }
    }
    
    /**
     * Met à jour le badge
     */
    static async updateBadge() {
        try {
            const notifications = await this.get(true);
            const badge = document.querySelector('#notif-badge');
            
            if (badge) {
                const count = notifications.length;
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (error) {
            console.error('Erreur mise à jour badge:', error);
        }
    }
}

export default Notifications;