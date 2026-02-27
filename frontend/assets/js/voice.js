/**
 * voice.js — Module Web Speech API
 * Gère la reconnaissance vocale (SpeechRecognition) et
 * la synthèse vocale (SpeechSynthesis) pour toute la plateforme.
 */

// ─── Vérification compatibilité ──────────────────────────────────────────────
export const speechSupported =
    'SpeechRecognition' in window || 'webkitSpeechRecognition' in window;
export const synthesisSupported = 'speechSynthesis' in window;

// ─── Synthèse vocale ─────────────────────────────────────────────────────────

/**
 * Lit un texte à voix haute.
 * @param {string} text       Texte à lire
 * @param {object} options    { lang, rate, pitch, volume }
 * @returns {Promise}         Résolu quand la lecture est terminée
 */
export function speak(text, options = {}) {
    return new Promise((resolve, reject) => {
        if (!synthesisSupported) { resolve(); return; }
        // Annule toute lecture en cours
        window.speechSynthesis.cancel();

        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang   = options.lang   ?? 'fr-FR';
        utterance.rate   = options.rate   ?? 0.9;
        utterance.pitch  = options.pitch  ?? 1.0;
        utterance.volume = options.volume ?? 1.0;

        // Sélection de voix française si disponible
        const voices = window.speechSynthesis.getVoices();
        const frVoice = voices.find(v => v.lang.startsWith('fr'));
        if (frVoice) utterance.voice = frVoice;

        utterance.onend   = () => resolve();
        utterance.onerror = (e) => reject(e);
        window.speechSynthesis.speak(utterance);
    });
}

/** Arrête toute synthèse en cours */
export function stopSpeech() {
    if (synthesisSupported) window.speechSynthesis.cancel();
}

// ─── Reconnaissance vocale ────────────────────────────────────────────────────

const SpeechRecognition =
    window.SpeechRecognition || window.webkitSpeechRecognition;

/**
 * Lance une écoute unique et retourne la transcription.
 * @param {object} options  { lang, continuous, interimResults, onInterim }
 * @returns {Promise<string>}  Transcription finale
 */
export function listenOnce(options = {}) {
    return new Promise((resolve, reject) => {
        if (!speechSupported) {
            reject(new Error('Web Speech API non supportée dans ce navigateur.'));
            return;
        }

        const recognition = new SpeechRecognition();
        recognition.lang            = options.lang            ?? 'fr-FR';
        recognition.continuous      = false;
        recognition.interimResults  = options.interimResults  ?? true;
        recognition.maxAlternatives = 1;

        recognition.onresult = (event) => {
            let interim = '';
            let final   = '';
            for (let i = event.resultIndex; i < event.results.length; i++) {
                const t = event.results[i][0].transcript;
                if (event.results[i].isFinal) final += t;
                else interim += t;
            }
            if (options.onInterim && interim) options.onInterim(interim);
            if (final) {
                recognition.stop();
                resolve(final.trim());
            }
        };

        recognition.onerror = (e) => {
            if (e.error === 'no-speech') resolve('');
            else reject(new Error(`Erreur reconnaissance vocale : ${e.error}`));
        };

        recognition.onend = () => resolve('');
        recognition.start();
    });
}

/**
 * Écoute en continu et appelle onResult à chaque phrase finale.
 * @returns {{ stop: Function }}  Objet avec méthode stop()
 */
export function listenContinuous(onResult, options = {}) {
    if (!speechSupported) {
        console.warn('Web Speech API non supportée.');
        return { stop: () => {} };
    }

    const recognition = new SpeechRecognition();
    recognition.lang           = options.lang ?? 'fr-FR';
    recognition.continuous     = true;
    recognition.interimResults = options.interimResults ?? false;

    recognition.onresult = (event) => {
        for (let i = event.resultIndex; i < event.results.length; i++) {
            if (event.results[i].isFinal) {
                onResult(event.results[i][0].transcript.trim().toLowerCase());
            }
        }
    };

    recognition.onerror = (e) => console.error('Erreur vocale :', e.error);
    recognition.onend   = () => {
        // Redémarre automatiquement si toujours actif
        if (recognition._active) recognition.start();
    };

    recognition._active = true;
    recognition.start();

    return {
        stop: () => {
            recognition._active = false;
            recognition.stop();
        }
    };
}

// ─── Interpréteur de commandes vocales ───────────────────────────────────────

/**
 * Analyse une transcription et retourne l'intention détectée.
 * @param {string} text  Texte transcrit (en minuscules)
 * @returns {{ intent: string, params: object }}
 */
export function parseVoiceCommand(text) {
    const t = text.toLowerCase().trim();

    // Navigation
    if (/accueil|page d'accueil/.test(t))   return { intent: 'navigate', params: { page: 'home' } };
    if (/catalogue|produits|boutique/.test(t)) return { intent: 'navigate', params: { page: 'catalog' } };
    if (/panier/.test(t))                   return { intent: 'navigate', params: { page: 'cart' } };
    if (/commandes?|historique/.test(t))    return { intent: 'navigate', params: { page: 'orders' } };
    if (/wallet|solde|argent/.test(t))      return { intent: 'navigate', params: { page: 'wallet' } };
    if (/profil|compte/.test(t))            return { intent: 'navigate', params: { page: 'profile' } };
    if (/déconnect|quitter/.test(t))        return { intent: 'logout', params: {} };

    // Panier
    if (/ajouter? (.+) au panier/.test(t)) {
        const m = t.match(/ajouter? (.+) au panier/);
        return { intent: 'add_to_cart', params: { query: m[1] } };
    }
    if (/vider le panier|supprimer tout/.test(t)) return { intent: 'clear_cart', params: {} };
    if (/lire (le )?panier|qu['']est.ce.qu['']il y a dans/.test(t)) return { intent: 'read_cart', params: {} };

    // Commande
    if (/commander|passer la? commande|valider/.test(t)) return { intent: 'checkout', params: {} };

    // Recherche
    if (/cherche[rz]? (.+)|recherche (.+)/.test(t)) {
        const m = t.match(/(?:cherche[rz]?|recherche) (.+)/);
        return { intent: 'search', params: { query: m[1] } };
    }

    // Admin — Dashboard
    if (/chiffre d'affaires|ca du jour|ventes? d'aujourd'hui/.test(t)) return { intent: 'admin_ca', params: {} };
    if (/bénéfice|profit/.test(t))        return { intent: 'admin_profit', params: {} };
    if (/combien d'utilisateurs?|nombre d'utilisateurs?/.test(t)) return { intent: 'admin_users', params: {} };
    if (/top produits?|produits? populaires?/.test(t)) return { intent: 'admin_top_products', params: {} };
    if (/alertes? (de )?fraude/.test(t))  return { intent: 'admin_fraud', params: {} };
    if (/stock|rupture/.test(t))          return { intent: 'admin_stock', params: {} };

    return { intent: 'unknown', params: { text: t } };
}

// ─── Lecture vocale du panier ─────────────────────────────────────────────────

export async function readCart(items, total) {
    if (!items || items.length === 0) {
        await speak('Votre panier est vide.');
        return;
    }
    let msg = `Votre panier contient ${items.length} article${items.length > 1 ? 's' : ''}. `;
    items.forEach(item => {
        msg += `${item.product_name || item.name}, quantité ${item.quantity}, ${Number(item.subtotal || item.unit_price * item.quantity).toLocaleString('fr-FR')} francs CFA. `;
    });
    msg += `Total : ${Number(total).toLocaleString('fr-FR')} francs CFA.`;
    await speak(msg);
}

// ─── Lecture vocale d'un produit ──────────────────────────────────────────────

export async function readProduct(product) {
    const price = product.promo_price
        ? `${Number(product.promo_price).toLocaleString('fr-FR')} francs CFA, au lieu de ${Number(product.price).toLocaleString('fr-FR')}`
        : `${Number(product.price).toLocaleString('fr-FR')} francs CFA`;
    await speak(
        `${product.name}. ${product.short_description || ''}. Prix : ${price}. Stock disponible : ${product.stock} unités.`
    );
}
