// Fichier: /frontend/assets/js/modules/websocket.js

import API from './api.js';

class WebSocketClient {
    constructor() {
        this.pollInterval = null;
        this.callbacks = new Map();
    }
    
    /**
     * Démarre le polling (simulation WebSocket)
     */
    start() {
        // Polling toutes les 5 secondes
        this.pollInterval = setInterval(() => {
            this.poll();
        }, 5000);
    }
    
    /**
     * Arrête le polling
     */
    stop() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    }
    
    /**
     * Poll les messages
     */
    async poll() {
        try {
            const response = await API.get('/api/notifications/poll');
            
            if (response.success && response.data.length > 0) {
                response.data.forEach(message => {
                    this.handleMessage(message);
                });
            }
        } catch (error) {
            console.error('Erreur polling:', error);
        }
    }
    
    /**
     * Traite un message reçu
     */
    handleMessage(message) {
        const type = message.data.type;
        
        // Appelle les callbacks enregistrés
        if (this.callbacks.has(type)) {
            this.callbacks.get(type).forEach(callback => {
                callback(message.data);
            });
        }
        
        // Affiche une notification
        this.showNotification(message.data);
    }
    
    /**
     * Enregistre un callback pour un type de message
     */
    on(type, callback) {
        if (!this.callbacks.has(type)) {
            this.callbacks.set(type, []);
        }
        this.callbacks.get(type).push(callback);
    }
    
    /**
     * Affiche une notification
     */
    showNotification(data) {
        // Notification navigateur si supporté
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(data.title, {
                body: data.message,
                icon: '/frontend/assets/images/logo.png'
            });
        }
        
        // Notification dans l'interface
        const container = document.querySelector('#notifications-container');
        if (container) {
            const notif = document.createElement('div');
            notif.className = 'notification-toast';
            notif.innerHTML = `
                <div class="notification-content">
                    <strong>${data.title}</strong>
                    <p>${data.message}</p>
                </div>
            `;
            
            container.appendChild(notif);
            
            // Retire après 5 secondes
            setTimeout(() => {
                notif.remove();
            }, 5000);
        }
    }
    
    /**
     * Demande la permission pour les notifications
     */
    static async requestPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            await Notification.requestPermission();
        }
    }
}

export default WebSocketClient;