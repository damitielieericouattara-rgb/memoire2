# E-Commerce Intelligent — Guide d'installation

## Structure à placer dans htdocs (XAMPP) ou www (WAMP)

```
htdocs/
  memoire-corrige/
    backend/
    frontend/
    database/
    ...
```

## Étapes

### 1. Base de données
- Ouvrez phpMyAdmin
- Créez une base nommée : **memoire_ecommerce_intelligent**
- Importez le fichier `database/schema.sql`

### 2. Compte de test
Le schéma crée un compte admin :
- Email : `admin@ecommerce.com`
- Mot de passe : `password123`

> Si ce mot de passe ne fonctionne pas, créez un compte via le formulaire d'inscription.

### 3. Configuration
Vérifiez `backend/config/database.php` :
```php
private static $host   = 'localhost';
private static $dbname = 'memoire_ecommerce_intelligent';
private static $user   = 'root';
private static $pass   = '';  // vide sur XAMPP par défaut
```

### 4. Accès
Ouvrez dans votre navigateur :
```
http://localhost/memoire-corrige/frontend/pages/connexion.html
```

### 5. URL API
Si votre projet est dans un dossier différent de `memoire-corrige/`, modifiez :
`frontend/assets/js/modules/api.js` → ligne 5 :
```js
const API_URL = window.location.origin + '/VOTRE_DOSSIER/backend';
```

### 6. Mod_rewrite (Apache)
Activez `mod_rewrite` dans XAMPP :
- `httpd.conf` → décommentez `LoadModule rewrite_module modules/mod_rewrite.so`
- Redémarrez Apache

## Flux utilisateur fonctionnel

1. **Inscription** → `/frontend/pages/inscription.html`
2. **Connexion** → `/frontend/pages/connexion.html`
3. **Dashboard** → Wallet, commandes, recommandations
4. **Catalogue** → Browse produits, recherche, filtres
5. **Panier** → Ajouter/modifier/supprimer articles
6. **Commande** → Adresse + mode paiement → Confirmation
7. **Suivi** → État de la commande en temps réel
