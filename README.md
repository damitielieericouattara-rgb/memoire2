# ⚡ SneakX — E-Commerce Intelligent v2.0

## 🚀 Installation

### Prérequis
- PHP 8.1+
- MySQL / MariaDB 10.4+
- Serveur Apache/Nginx avec mod_rewrite
- XAMPP / WAMP / Laragon (dev local)

### Étapes
1. Cloner le projet dans `htdocs/sneakx/`
2. Importer `database/schema_complet.sql` dans phpMyAdmin
3. Modifier `backend/config/database.php` avec vos identifiants
4. Accéder à `http://localhost/sneakx/frontend/pages/index.html`

### Compte Admin par défaut
- Email : `admin@sneakx.com`
- Mot de passe : `Admin1234!` (à changer)

## 📁 Structure
```
sneakx/
├── backend/
│   ├── config/         # Configuration DB, JWT, App
│   ├── controllers/    # AuthController, AdminController, CommandeController...
│   ├── core/           # Router, Controller, Model, Database, Response
│   ├── middleware/     # Auth, CORS, RateLimit, Role
│   ├── models/         # User, Produit, Commande, Wallet...
│   ├── services/       # Recommandation, Fraude, PDF, Email, Notification...
│   └── index.php       # Point d'entrée API
├── frontend/
│   ├── assets/
│   │   ├── css/        # design-system.css, accessibility.css
│   │   └── js/
│   │       ├── modules/ # api.js, voice.js, accessibility.js, notifications.js, maps.js
│   │       └── pages/   # catalogue.js, produit.js...
│   ├── pages/           # Toutes les pages HTML
│   │   └── admin/       # Dashboard admin
│   ├── manifest.json    # PWA
│   └── service-worker.js # Mode hors-ligne
├── database/
│   └── schema_complet.sql
└── storage/             # Fichiers générés (factures, QR codes...)
```

## ✅ 22 Fonctionnalités implémentées
1. 🧬 Profil d'achat intelligent (recommandations IA)
2. 🗺️ Carte GPS livraison (Leaflet + OpenStreetMap)
3. 🎙️ Assistance vocale complète (Web Speech API)
4. 📱 Notifications temps réel (long polling + Push)
5. 🧾 Factures PDF + QR Code automatiques
6. 🌍 Multi-langue (FR/EN) + Multi-devise (XOF/EUR/USD)
7. 🎥 Vidéo produit intégrée
8. 🧮 Simulateur crédit 3x
9. 🛡️ Anti-fraude comportemental
10. 📊 Statistiques client (graphiques)
11. 📦 Prévision stocks admin
12. 🌙 Dark/Light mode (détection système)
13. 🛒 Anti-abandon panier (email + remise auto)
14. 🔄 Marketplace d'échange/revente
15. 🗳️ Vote communautaire promotions
16. 💬 Chatbot vocal (commandes vocales)
17. 🧏 Accessibilité avancée (malvoyant, vocal, LSF)
18. 🛍️ Packs automatiques (bundles IA)
19. 💸 Wallet virtuel (recharge + paiement)
20. ✋ Mode hors-ligne PWA
21. 🎨 Écran choix accessibilité (premier accès)
22. 📊 Admin intelligent (analytics complètes)
