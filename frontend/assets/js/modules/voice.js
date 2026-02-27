// Fichier: /frontend/assets/js/modules/voice.js

class Voice {
    constructor() {
        this.recognition = null;
        this.synthesis = window.speechSynthesis;
        this.isListening = false;
        this.commands = new Map();
        
        this.init();
    }
    
    /**
     * Initialise la reconnaissance vocale
     */
    init() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            console.warn('La reconnaissance vocale n\'est pas supportée');
            return;
        }
        
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.recognition = new SpeechRecognition();
        
        this.recognition.lang = 'fr-FR';
        this.recognition.continuous = false;
        this.recognition.interimResults = false;
        
        this.recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript.toLowerCase();
            console.log('Commande vocale:', transcript);
            this.processCommand(transcript);
        };
        
        this.recognition.onerror = (event) => {
            console.error('Erreur reconnaissance vocale:', event.error);
            this.stopListening();
        };
        
        this.recognition.onend = () => {
            this.isListening = false;
            this.updateUI();
        };
        
        // Enregistre les commandes par défaut
        this.registerDefaultCommands();
    }
    
    /**
     * Enregistre les commandes par défaut
     */
    registerDefaultCommands() {
        // Navigation
        this.register('accueil', () => window.location.href = 'dashboard.html');
        this.register('catalogue', () => window.location.href = 'catalogue.html');
        this.register('panier', () => window.location.href = 'panier.html');
        this.register('voir mon panier', () => window.location.href = 'panier.html');
        this.register('commandes', () => window.location.href = 'commandes.html');
        this.register('mes commandes', () => window.location.href = 'commandes.html');
        this.register('wallet', () => window.location.href = 'wallet.html');
        this.register('mon solde', () => window.location.href = 'wallet.html');
        
        // Actions
        this.register('aide', () => this.showHelp());
        this.register('quitter', () => this.stopListening());
    }
    
    /**
     * Enregistre une commande
     */
    register(keywords, action) {
        if (typeof keywords === 'string') {
            keywords = [keywords];
        }
        
        keywords.forEach(keyword => {
            this.commands.set(keyword.toLowerCase(), action);
        });
    }
    
    /**
     * Traite une commande
     */
    processCommand(transcript) {
        let executed = false;
        
        // Cherche une correspondance exacte
        for (let [keyword, action] of this.commands) {
            if (transcript.includes(keyword)) {
                action(transcript);
                this.speak(`Commande ${keyword} exécutée`);
                executed = true;
                break;
            }
        }
        
        if (!executed) {
            this.speak('Commande non reconnue. Dites "aide" pour voir les commandes disponibles.');
        }
    }
    
    /**
     * Démarre l'écoute
     */
    startListening() {
        if (!this.recognition) {
            alert('La reconnaissance vocale n\'est pas supportée par votre navigateur');
            return;
        }
        
        this.isListening = true;
        this.recognition.start();
        this.updateUI();
        this.speak('Je vous écoute');
    }
    
    /**
     * Arrête l'écoute
     */
    stopListening() {
        if (this.recognition && this.isListening) {
            this.recognition.stop();
            this.isListening = false;
            this.updateUI();
        }
    }
    
    /**
     * Synthèse vocale
     */
    speak(text, rate = 1.0) {
        if (!this.synthesis) return;
        
        // Arrête toute synthèse en cours
        this.synthesis.cancel();
        
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'fr-FR';
        utterance.rate = rate;
        
        this.synthesis.speak(utterance);
    }
    
    /**
     * Affiche l'aide vocale
     */
    showHelp() {
        const commands = [
            'Accueil',
            'Catalogue',
            'Panier',
            'Mes commandes',
            'Mon solde'
        ];
        
        const helpText = 'Commandes disponibles : ' + commands.join(', ');
        this.speak(helpText);
    }
    
    /**
     * Met à jour l'interface
     */
    updateUI() {
        const indicator = document.querySelector('.voice-indicator');
        const button = document.querySelector('.voice-button');
        
        if (this.isListening) {
            if (indicator) indicator.style.display = 'block';
            if (button) button.classList.add('listening');
        } else {
            if (indicator) indicator.style.display = 'none';
            if (button) button.classList.remove('listening');
        }
    }
}

export default Voice;