/**
 * SneakX — Module Cartes Leaflet.js v2
 * Sélection position livraison, suivi commande, carte admin
 */

async function ensureLeaflet() {
    if (window.L) return;
    await new Promise((resolve, reject) => {
        const link = document.createElement('link');
        link.rel  = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(link);
        const script    = document.createElement('script');
        script.src      = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        script.onload   = resolve;
        script.onerror  = reject;
        document.head.appendChild(script);
    });
}

const ABIDJAN = { lat: 5.3599517, lng: -4.0082563 };
const DEPOT   = { lat: 5.3196,    lng: -4.0160,   nom: 'Dépôt SneakX — Plateau' };

// ─── Icônes ─────────────────────────────────────────
function makeIcon(emoji, bg = '#FF5C00') {
    return window.L.divIcon({
        html: `<div style="width:38px;height:38px;border-radius:50%;background:${bg};border:3px solid #fff;
                display:flex;align-items:center;justify-content:center;font-size:18px;
                box-shadow:0 4px 14px rgba(0,0,0,0.4);">${emoji}</div>`,
        className: '', iconSize: [38,38], iconAnchor: [19,19], popupAnchor: [0,-22],
    });
}

// ─── Géocodage inverse ──────────────────────────────
export async function reverseGeocode(lat, lng) {
    try {
        const res  = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=fr`, { headers: {'User-Agent':'SneakX/2.0'} });
        const data = await res.json();
        if (data?.display_name) return data.display_name.split(',').slice(0,4).join(',').trim();
    } catch {}
    return `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
}

// ─── Carte de sélection livraison ───────────────────
export async function initDeliveryMap(containerId, onPositionSelect) {
    await ensureLeaflet();
    const L   = window.L;
    const el  = document.getElementById(containerId);
    if (!el) return null;
    el.style.cssText = 'height:340px;border-radius:12px;overflow:hidden;';

    const map = L.map(containerId).setView([ABIDJAN.lat, ABIDJAN.lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 19,
    }).addTo(map);

    // Marqueur dépôt
    L.marker([DEPOT.lat, DEPOT.lng], { icon: makeIcon('📦', '#1C1C1C') })
     .addTo(map)
     .bindPopup('<strong>Dépôt SneakX</strong><br>Plateau, Abidjan');

    // Zones
    L.circle([ABIDJAN.lat, ABIDJAN.lng], { radius:15000, color:'#FF5C00', fillOpacity:.04, weight:1, dashArray:'6 4' }).addTo(map);
    L.circle([ABIDJAN.lat, ABIDJAN.lng], { radius:8000,  color:'#10b981', fillOpacity:.04, weight:1, dashArray:'6 4' }).addTo(map);

    let marker = null, selectedPos = null;

    function placeMarker(lat, lng, popup = '') {
        if (marker) map.removeLayer(marker);
        marker = L.marker([lat, lng], { icon: makeIcon('🏠','#FF5C00'), draggable: true })
            .addTo(map)
            .bindPopup(popup || `<strong>Votre adresse</strong><br>${lat.toFixed(5)}, ${lng.toFixed(5)}`)
            .openPopup();
        marker.on('dragend', async e => {
            const p = e.target.getLatLng();
            selectedPos = { lat: p.lat, lng: p.lng };
            const addr  = await reverseGeocode(p.lat, p.lng);
            if (onPositionSelect) onPositionSelect({ lat: p.lat, lng: p.lng, adresse: addr });
        });
    }

    map.on('click', async e => {
        const { lat, lng } = e.latlng;
        selectedPos = { lat, lng };
        const addr  = await reverseGeocode(lat, lng);
        placeMarker(lat, lng, `<strong>Votre adresse</strong><br>${addr}`);
        if (onPositionSelect) onPositionSelect({ lat, lng, adresse: addr });
    });

    // Contrôle GPS
    const GpsBtn = L.Control.extend({
        onAdd() {
            const b = L.DomUtil.create('button');
            b.innerHTML  = '📍';
            b.title      = 'Ma position GPS';
            b.style.cssText = 'width:36px;height:36px;background:#FF5C00;border:none;border-radius:8px;font-size:18px;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.4);margin:6px;';
            L.DomEvent.disableClickPropagation(b);
            L.DomEvent.on(b, 'click', locateUser);
            return b;
        }
    });
    map.addControl(new GpsBtn({ position: 'topleft' }));

    function locateUser() {
        if (!navigator.geolocation) return alert("Géolocalisation non supportée.");
        navigator.geolocation.getCurrentPosition(async pos => {
            const lat = pos.coords.latitude, lng = pos.coords.longitude;
            selectedPos = { lat, lng };
            map.flyTo([lat, lng], 16, { duration: 1.5 });
            const addr = await reverseGeocode(lat, lng);
            placeMarker(lat, lng, `<strong>Votre position GPS</strong><br>${addr}`);
            if (onPositionSelect) onPositionSelect({ lat, lng, adresse: addr });
        }, err => alert("Impossible de récupérer votre position. Cliquez sur la carte."), { timeout: 10000 });
    }

    return { map, locateUser, getSelected: () => selectedPos,
             setPosition: (lat, lng) => { placeMarker(lat, lng); map.flyTo([lat, lng], 15); } };
}

// ─── Carte de suivi commande ─────────────────────────
export async function initTrackingMap(containerId, orderData) {
    await ensureLeaflet();
    const L  = window.L;
    const el = document.getElementById(containerId);
    if (!el) return null;
    el.style.cssText = 'height:400px;border-radius:14px;overflow:hidden;';

    const dLat = parseFloat(orderData.latitude_dest || ABIDJAN.lat);
    const dLng = parseFloat(orderData.longitude_dest || ABIDJAN.lng);
    const map  = L.map(containerId).setView([(DEPOT.lat+dLat)/2, (DEPOT.lng+dLng)/2], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution:'© OSM', maxZoom:19 }).addTo(map);
    L.marker([DEPOT.lat, DEPOT.lng], { icon: makeIcon('📦','#1C1C1C') }).addTo(map).bindPopup('<strong>Dépôt SneakX</strong>');
    L.marker([dLat,       dLng],      { icon: makeIcon('🏠','#10b981') }).addTo(map).bindPopup('<strong>Votre adresse</strong><br>' + (orderData.adresse_dest||''));

    const route = L.polyline([[DEPOT.lat,DEPOT.lng],[dLat,dLng]], { color:'#FF5C00', weight:3, dashArray:'8 6', opacity:.8 }).addTo(map);

    if (orderData.statut_livraison === 'en_route') {
        const midLat = (DEPOT.lat+dLat)/2 + (Math.random()-.5)*.01;
        const midLng = (DEPOT.lng+dLng)/2 + (Math.random()-.5)*.01;
        L.marker([midLat, midLng], { icon: makeIcon('🛵','#f59e0b') }).addTo(map).bindPopup('<strong>Votre livreur</strong><br>En route vers vous !');
    }

    map.fitBounds(route.getBounds().pad(.25));
    return { map };
}

// ─── Carte admin toutes livraisons ──────────────────
export async function initAdminMap(containerId, positions = []) {
    await ensureLeaflet();
    const L  = window.L;
    const el = document.getElementById(containerId);
    if (!el) return null;
    el.style.cssText = 'height:480px;border-radius:14px;overflow:hidden;';

    const map = L.map(containerId).setView([ABIDJAN.lat, ABIDJAN.lng], 12);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution:'© OSM © CARTO', maxZoom:19 }).addTo(map);

    const colors = { en_attente:'#f59e0b', confirmee:'#3b82f6', en_preparation:'#a855f7', expediee:'#FF5C00', livree:'#10b981', annulee:'#ef4444' };

    positions.forEach(pos => {
        const c = colors[pos.statut] || '#aaa';
        L.circleMarker([parseFloat(pos.latitude), parseFloat(pos.longitude)], {
            radius:8, fillColor:c, color:'rgba(255,255,255,.3)', weight:2, fillOpacity:.85,
        }).addTo(map).bindPopup(`<strong>#${pos.numero}</strong><br>${pos.prenom} ${pos.nom}<br><span style="color:${c}">${pos.statut}</span>`);
    });

    return { map };
}

export default { initDeliveryMap, initTrackingMap, initAdminMap, reverseGeocode, ABIDJAN, DEPOT };
