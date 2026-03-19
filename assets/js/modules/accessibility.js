// Fichier: /frontend/assets/js/modules/accessibility.js

class Accessibility {
    /**
     * Active un mode d'accessibilité
     */
    static setMode(mode) {
        document.body.setAttribute('data-accessibility', mode);
        localStorage.setItem('accessibility_mode', mode);
        
        // Applique les ajustements
        this.applyMode(mode);
    }
    
    /**
     * Récupère le mode actuel
     */
    static getMode() {
        return localStorage.getItem('accessibility_mode') || 'STANDARD';
    }
    
    /**
     * Applique le mode
     */
    static applyMode(mode) {
        switch(mode) {
            case 'LOW_VISION':
                this.applyLowVisionMode();
                break;
            case 'VOCAL':
                this.applyVocalMode();
                break;
            case 'SIGN_LANGUAGE':
                this.applySignLanguageMode();
                break;
            default:
                this.applyStandardMode();
        }
    }
    
    /**
     * Mode malvoyant
     */
    static applyLowVisionMode() {
        // Le CSS s'applique automatiquement via data-accessibility
        console.log('Mode malvoyant activé');
    }
    
    /**
     * Mode vocal
     */
    static applyVocalMode() {
        // Active le bouton vocal
        const voiceBtn = document.querySelector('.voice-button');
        if (voiceBtn) voiceBtn.style.display = 'flex';
    }
    
    /**
     * Mode langue des signes
     */
    static applySignLanguageMode() {
        // Affiche l'avatar LSF
        const lsfAvatar = document.querySelector('.lsf-avatar');
        if (lsfAvatar) lsfAvatar.style.display = 'block';
    }
    
    /**
     * Mode standard
     */
    static applyStandardMode() {
        document.body.removeAttribute('data-accessibility');
    }
    
    /**
     * Active le mode économie internet
     */
    static setEcoMode(enabled) {
        if (enabled) {
            document.body.setAttribute('data-mode', 'ECO');
            localStorage.setItem('eco_mode', 'true');
        } else {
            document.body.removeAttribute('data-mode');
            localStorage.removeItem('eco_mode');
        }
    }
    
    /**
     * Initialise l'accessibilité au chargement
     */
    static init() {
        // Applique le mode sauvegardé
        const mode = this.getMode();
        this.setMode(mode);
        
        // Applique le mode éco si activé
        const ecoMode = localStorage.getItem('eco_mode');
        if (ecoMode === 'true') {
            this.setEcoMode(true);
        }
    }
    
    /**
     * Augmente la taille de la police
     */
    static increaseFontSize() {
        const currentSize = parseInt(getComputedStyle(document.documentElement).fontSize);
        document.documentElement.style.fontSize = (currentSize + 2) + 'px';
    }
    
    /**
     * Diminue la taille de la police
     */
    static decreaseFontSize() {
        const currentSize = parseInt(getComputedStyle(document.documentElement).fontSize);
        if (currentSize > 12) {
            document.documentElement.style.fontSize = (currentSize - 2) + 'px';
        }
    }
}

export default Accessibility;