# SneakX — Guide d'Installation Complet
## Plateforme E-Commerce Africaine Intelligente
**Projet de Mémoire Universitaire**

---

## 🗂️ Structure du Projet

```
sneakx/
├── .htaccess                   ← Config Apache (URL rewriting)
├── index.html                  ← Redirection vers pages/index.html
│
├── assets/                     ← Frontend statique
│   ├── css/
│   │   ├── design-system.css   ← Design system partagé (dark/light)
│   │   ├── admin.css           ← Styles panneau admin
│   │   ├── accessibility.css   ← Styles accessibilité
│   │   └── ...
│   └── js/
│       ├── modules/
│       │   ├── api.js          ← Client API (connecté au backend PHP)
│       │   ├── sx-shared.js    ← Header, Auth Modal, Toasts
│       │   ├── voice.js        ← SpeechSynthesis + SpeechRecognition
│       │   ├── maps.js         ← Leaflet.js + géolocalisation
│       │   └── accessibility.js
│       └── pages/              ← Scripts par page
│
├── pages/                      ← Pages HTML
│   ├── index.html              ← Page d'accueil
│   ├── catalogue.html          ← Catalogue + filtres + recherche
│   ├── produit.html            ← Page produit + audio
│   ├── panier.html             ← Panier
│   ├── commande.html           ← Checkout + carte Leaflet + paiements
│   ├── paiement-simulation.html← Simulation paiement mobile money
│   ├── suivi.html              ← Suivi commande sur carte
│   ├── dashboard.html          ← Dashboard client
│   ├── connexion.html          ← Login
│   ├── inscription.html        ← Register
│   ├── profil.html             ← Mon profil
│   ├── commandes.html          ← Mes commandes
│   ├── wishlist.html           ← Wishlist
│   ├── notifications.html      ← Notifications
│   ├── wallet.html             ← Wallet / portefeuille
│   └── admin/
│       ├── dashboard.html      ← Dashboard admin (Chart.js)
│       ├── commandes.html      ← Gestion commandes
│       ├── produits.html       ← Gestion produits
│       ├── utilisateurs.html   ← Gestion utilisateurs
│       ├── analytics.html      ← Analytics + graphiques
│       └── fraude.html         ← Anti-fraude
│
├── backend/                    ← Backend PHP
│   ├── config/
│   │   ├── config.php          ← Configuration app + helpers
│   │   └── database.php        ← Connexion PDO (Singleton)
│   ├── classes/
│   │   ├── Model.php           ← Classe ORM de base
│   │   ├── Auth.php            ← Authentification JWT + session
│   │   ├── QRCode.php          ← Générateur QR Code
│   │   └── ReportGenerator.php ← Rapports PDF mensuels
│   ├── models/
│   │   ├── Product.php         ← Produits (recherche, tendances...)
│   │   ├── Order.php           ← Commandes (stats, dashboard...)
│   │   ├── Cart.php            ← Panier (session + user)
│   │   └── User.php            ← Utilisateurs
│   ├── controllers/
│   │   └── PaymentController.php ← Wave, OM, MTN, Moov, Carte
│   └── api/                    ← Points d'entrée REST
│       ├── auth.php            ← POST /api/auth?action=...
│       ├── produits.php        ← GET/POST /api/produits?action=...
│       ├── panier.php          ← GET/POST /api/panier?action=...
│       ├── commandes.php       ← GET/POST /api/commandes?action=...
│       ├── paiement.php        ← POST /api/paiement?action=...
│       ├── livraison.php       ← GET/POST /api/livraison?action=...
│       ├── notifications.php   ← GET/POST /api/notifications?action=...
│       ├── wishlist.php        ← GET/POST /api/wishlist?action=...
│       └── admin.php           ← GET/POST /api/admin?action=...
│
├── database/
│   └── schema.sql              ← Schéma BDD + données de démo
│
└── uploads/                    ← Uploads (créé automatiquement)
    ├── products/
    ├── avatars/
    ├── qrcodes/
    └── reports/
```

---

## ⚙️ Prérequis

| Composant | Version requise |
|-----------|----------------|
| XAMPP / Laragon | Dernière version |
| Apache | 2.4+ avec mod_rewrite |
| PHP | 8.1+ |
| MariaDB / MySQL | 10.4+ / 5.7+ |
| Navigateur | Chrome 90+, Firefox 88+, Edge 90+ |

---

## 🚀 Installation

### Étape 1 — Copier le projet

```bash
# Placer le dossier dans XAMPP ou Laragon
# XAMPP :
C:\xampp\htdocs\sneakx\

# Laragon :
C:\laragon\www\sneakx\
```

### Étape 2 — Créer la base de données

1. Ouvrir **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Cliquer **Nouvelle base de données** → nom : `sneakx_db`, encodage : `utf8mb4_unicode_ci`
3. Aller dans l'onglet **SQL** et importer le fichier :
```
database/schema.sql
```

### Étape 3 — Configurer la connexion BDD

Éditer `backend/config/database.php` :
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sneakx_db');
define('DB_USER', 'root');    // votre user MySQL
define('DB_PASS', '');        // votre mot de passe
```

### Étape 4 — Activer mod_rewrite Apache

Dans `httpd.conf` ou `httpd-vhosts.conf` :
```apache
<Directory "C:/xampp/htdocs/sneakx">
    AllowOverride All
    Options +FollowSymLinks
</Directory>
```

### Étape 5 — Accéder au site

| URL | Description |
|-----|-------------|
| `http://localhost/sneakx/` | Accueil boutique |
| `http://localhost/sneakx/pages/catalogue.html` | Catalogue |
| `http://localhost/sneakx/pages/connexion.html` | Connexion |
| `http://localhost/sneakx/pages/admin/dashboard.html` | Admin |

---

## 👤 Comptes de Test

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Admin | `admin@sneakx.ci` | `Admin@123` |
| Client | `eric@test.ci` | `Admin@123` |
| Client | `aminata@test.ci` | `Admin@123` |

---

## 🔌 API Endpoints

### Authentification
```
POST /backend/api/auth.php?action=login     → connexion
POST /backend/api/auth.php?action=register  → inscription
GET  /backend/api/auth.php?action=me        → profil connecté
```

### Produits
```
GET /backend/api/produits.php?action=catalogue&q=nike&sort=populaire
GET /backend/api/produits.php?action=search&q=iph   ← PRÉDICTIVE
GET /backend/api/produits.php?action=detail&slug=nike-air-max-270
GET /backend/api/produits.php?action=tendances
GET /backend/api/produits.php?action=categories
```

### Panier
```
GET  /backend/api/panier.php?action=get
POST /backend/api/panier.php?action=add    {product_id, quantite, taille, couleur}
POST /backend/api/panier.php?action=promo  {code, total}
```

### Commandes
```
POST /backend/api/commandes.php?action=create  → crée commande + paiement + QR
GET  /backend/api/commandes.php?action=suivi&numero=SNX-20240101-00001
```

### Paiements supportés
```
wave | orange_money | mtn_momo | moov_money | carte_bancaire | livraison
```

---

## 🌟 Fonctionnalités Clés

### 🔊 Lecture Audio des Produits
Sur chaque page produit, bouton **"Écouter la description"** utilisant la **Web Speech API** (SpeechSynthesis).

### 📶 Mode Économie Internet
Toggle **"Mode économie internet"** qui :
- Désactive les animations CSS
- Simplifie l'interface
- Charge des images compressées

### 🔍 Recherche Prédictive
L'utilisateur tape `iph` → suggestions en temps réel via AJAX → `produits.php?action=search`

### 🗺️ Carte de Livraison
- **Leaflet.js + OpenStreetMap** sur la page commande
- Click sur la carte → enregistre latitude/longitude
- `navigator.geolocation` → position GPS automatique
- L'admin voit toutes les positions de livraison

### 📱 Paiements Mobile Money
Le `PaymentController.php` gère :
- **Wave CI** → API Wave officielle (+ simulation)
- **Orange Money** → API Orange WebPay
- **MTN MoMo** → API MTN Developer
- **Moov Money** → Codes USSD
- **Carte bancaire** → (Intégration CinetPay prévue)
- **Livraison** → Confirmation immédiate

### 📊 Dashboard Admin
- KPIs en temps réel (CA, commandes, alertes stock)
- Graphiques **Chart.js** : ventes 7j/30j, statuts, catégories
- Alerte automatique si `stock < 5`

### 🏆 Produit Tendance
Calculé par : `(ventes × 3) + vues` → top de la semaine

### 📱 QR Code Commande
Généré automatiquement après chaque commande validée via `QRCode.php`

### 📄 Rapport PDF Mensuel
`ReportGenerator.php` → génère HTML + PDF (si wkhtmltopdf disponible)  
Endpoint : `GET /backend/api/admin.php?action=rapport&year=2024&month=12`

---

## 🛡️ Sécurité Implémentée

- ✅ `password_hash()` avec BCrypt (coût 12)
- ✅ Requêtes préparées PDO (protection injection SQL)
- ✅ Authentification JWT + session PHP
- ✅ Validation + sanitisation de toutes les entrées
- ✅ Headers de sécurité (X-Frame-Options, X-XSS-Protection...)
- ✅ Blocage accès direct aux dossiers backend
- ✅ CORS configuré

---

## 📝 Technologies Utilisées

| Couche | Technologie |
|--------|-------------|
| Frontend | HTML5, CSS3 Variables, JavaScript ES6+ |
| Styles | Design System custom (dark/light theme) |
| Icônes | FontAwesome 6 |
| Cartes | Leaflet.js + OpenStreetMap |
| Graphiques | Chart.js 4 |
| Audio | Web Speech API (SpeechSynthesis) |
| Backend | PHP 8.1 (OOP + procédural) |
| Base de données | MariaDB / MySQL |
| Serveur | Apache + mod_rewrite |
| QR Code | api.qrserver.com |
| PWA | Service Worker + manifest.json |

---

## 🎓 Contexte Africain — Points Différenciants

| Fonctionnalité | Description | Impact |
|---------------|-------------|--------|
| Mobile Money | Wave, Orange, MTN, Moov | Réalité des paiements CI |
| Mode connexion lente | Interface ultra-légère | Réseau 2G/3G instable |
| Lecture audio | SpeechSynthesis | Inclusion analphabétisme |
| Géolocalisation | Adresses libres + carte | Pas d'adresses postales formelles |
| Prix en FCFA | Devise locale | Pertinence locale |
| Support SMS | Notifications USSD | Alternatives sans internet |

---

**Développé dans le cadre d'un mémoire de fin d'études — Génie Informatique / Informatique de Gestion**
