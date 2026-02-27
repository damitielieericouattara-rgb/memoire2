// Fichier: /frontend/assets/js/modules/utils.js

class Utils {
    /**
     * Formate une date
     */
    static formatDate(date, format = 'short') {
        const d = new Date(date);
        
        if (format === 'short') {
            return d.toLocaleDateString('fr-FR');
        } else if (format === 'long') {
            return d.toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } else if (format === 'time') {
            return d.toLocaleTimeString('fr-FR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        } else if (format === 'datetime') {
            return d.toLocaleString('fr-FR');
        }
        
        return d.toLocaleDateString('fr-FR');
    }
    
    /**
     * Formate un montant
     */
    static formatCurrency(amount, currency = 'XOF') {
        return new Intl.NumberFormat('fr-FR', {
            style: 'decimal',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount) + ' ' + currency;
    }
    
    /**
     * Affiche un loader
     */
    static showLoader() {
        const loader = document.createElement('div');
        loader.className = 'loading-overlay';
        loader.id = 'global-loader';
        loader.innerHTML = '<div class="spinner"></div>';
        document.body.appendChild(loader);
    }
    
    /**
     * Cache le loader
     */
    static hideLoader() {
        const loader = document.getElementById('global-loader');
        if (loader) loader.remove();
    }
    
    /**
     * Affiche un toast
     */
    static showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 20px;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#2563eb'};
            color: white;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    /**
     * Débounce une fonction
     */
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * Génère un ID de session
     */
    static getSessionId() {
        let sessionId = sessionStorage.getItem('session_id');
        
        if (!sessionId) {
            sessionId = this.generateId();
            sessionStorage.setItem('session_id', sessionId);
        }
        
        return sessionId;
    }
    
    /**
     * Génère un ID aléatoire
     */
    static generateId() {
        return Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
    }
    
    /**
     * Valide un email
     */
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * Escape HTML
     */
    static escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}

export default Utils;