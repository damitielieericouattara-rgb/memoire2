// Fichier: /frontend/assets/js/modules/maps.js

class Maps {
    constructor(containerId, options = {}) {
        this.containerId = containerId;
        this.map = null;
        this.markers = new Map();
        this.options = {
            zoom: 13,
            lat: 5.3600,  // Abidjan par défaut
            lng: -4.0083,
            ...options
        };
    }

    /**
     * Initialise la carte Leaflet
     */
    init() {
        if (this.map) return;
        if (!document.getElementById(this.containerId)) {
            console.warn(`Conteneur #${this.containerId} introuvable`);
            return;
        }

        this.map = L.map(this.containerId).setView(
            [this.options.lat, this.options.lng],
            this.options.zoom
        );

        // Tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(this.map);

        return this;
    }

    /**
     * Ajoute un marqueur personnalisé
     */
    addMarker(id, lat, lng, options = {}) {
        if (!this.map) this.init();

        const { icon = '📍', title = '', popup = '', color = '#2563eb' } = options;

        const markerIcon = L.divIcon({
            html: `<div style="background:${color};color:white;border-radius:50%;width:42px;height:42px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.3);">${icon}</div>`,
            className: '',
            iconSize: [42, 42],
            iconAnchor: [21, 42]
        });

        const marker = L.marker([lat, lng], { icon: markerIcon, title });

        if (popup) {
            marker.bindPopup(`<div style="min-width:150px;">${popup}</div>`);
        }

        marker.addTo(this.map);
        this.markers.set(id, marker);

        return marker;
    }

    /**
     * Déplace un marqueur existant (livraison temps réel)
     */
    moveMarker(id, lat, lng, animate = true) {
        const marker = this.markers.get(id);
        if (!marker) return;

        marker.setLatLng([lat, lng]);

        if (animate) {
            this.map.panTo([lat, lng], { animate: true, duration: 1.0 });
        }
    }

    /**
     * Supprime un marqueur
     */
    removeMarker(id) {
        const marker = this.markers.get(id);
        if (marker) {
            this.map.removeLayer(marker);
            this.markers.delete(id);
        }
    }

    /**
     * Trace un itinéraire entre deux points
     */
    drawRoute(startLat, startLng, endLat, endLng, options = {}) {
        const { color = '#2563eb', weight = 4, dashed = false } = options;

        const line = L.polyline(
            [[startLat, startLng], [endLat, endLng]],
            {
                color,
                weight,
                opacity: 0.8,
                dashArray: dashed ? '10, 15' : null
            }
        ).addTo(this.map);

        // Ajuste la vue pour montrer toute la route
        this.map.fitBounds(line.getBounds(), { padding: [50, 50] });

        return line;
    }

    /**
     * Géolocalise l'utilisateur
     */
    getUserLocation(callback) {
        if (!navigator.geolocation) {
            console.warn('Géolocalisation non supportée');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude } = pos.coords;

                this.addMarker('user', latitude, longitude, {
                    icon: '📍',
                    title: 'Votre position',
                    popup: '<strong>Vous êtes ici</strong>',
                    color: '#10b981'
                });

                this.map.setView([latitude, longitude], 15);

                if (callback) callback(latitude, longitude);
            },
            (err) => {
                console.warn('Erreur géolocalisation:', err.message);
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
        );
    }

    /**
     * Affiche la carte de suivi de commande
     * avec position livreur et destination
     */
    initDeliveryTracking(deliveryLat, deliveryLng, destLat, destLng) {
        this.init();

        // Marqueur livreur
        this.addMarker('delivery', deliveryLat, deliveryLng, {
            icon: '🚚',
            title: 'Votre livreur',
            popup: '<strong>🚚 Livreur</strong><br>En route vers vous',
            color: '#2563eb'
        });

        // Marqueur destination
        this.addMarker('destination', destLat, destLng, {
            icon: '🏠',
            title: 'Votre adresse',
            popup: '<strong>📍 Destination</strong>',
            color: '#ef4444'
        });

        // Ligne pointillée entre les deux
        this.drawRoute(deliveryLat, deliveryLng, destLat, destLng, {
            color: '#2563eb',
            dashed: true
        });

        return this;
    }

    /**
     * Met à jour la position du livreur (polling)
     */
    updateDeliveryPosition(lat, lng) {
        this.moveMarker('delivery', lat, lng);
    }

    /**
     * Réinitialise la carte
     */
    destroy() {
        if (this.map) {
            this.map.remove();
            this.map = null;
            this.markers.clear();
        }
    }

    /**
     * Redimensionne la carte (après affichage)
     */
    invalidateSize() {
        if (this.map) {
            setTimeout(() => this.map.invalidateSize(), 100);
        }
    }
}

export default Maps;