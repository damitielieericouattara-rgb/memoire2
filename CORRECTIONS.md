# SneakX — Corrections Appliquées

## Comptes de démonstration

| Rôle | Email | Mot de passe |
|---|---|---|
| **Admin** | admin@sneakx.ci | Admin@123 |
| **Client** | eric@test.ci | Admin@123 |

## Mode de fonctionnement

Cette application fonctionne **entièrement en mode statique** (sans backend PHP).  
Toutes les données sont stockées dans le `localStorage` du navigateur.

Pour ouvrir : ouvrir `pages/index.html` dans un navigateur (ou utiliser Live Server dans VS Code).

---

## Bugs corrigés

### 🔴 Critiques (app entièrement cassée)

1. **`assets/js/modules/api.js`** — Réécrit entièrement  
   - Avant : appelait `/backend/api/*.php` → erreur 404  
   - Après : API statique complète (localStorage) avec auth, produits, panier, commandes, wallet, wishlist, notifications, admin

2. **`pages/catalogue.html`** — Variables JS déclarées en double  
   - `const BG`, `const IMGS`, `const DEMO`, `const filters`, `function createCard` déclarées 2 fois → crash fatal  
   - Doublons supprimés + filtres filtrés + pagination + voiceSearch ajoutés

3. **`assets/js/modules/sx-shared.js`** — `isAuthenticated()` toujours false  
   - Token base64 simple mal décodé (tentait `.split('.')` pour JWT)  
   - Corrigé pour supporter les deux formats

4. **Tous les fichiers `assets/js/pages/*.js`** — Totalement vides  
   - `catalogue.js`, `panier.js`, `admin.js`, `dashboard.js`, `produit.js`, etc.  
   - L'API statique gère maintenant tout directement dans les pages HTML

### 🟠 Logique et données

5. **`pages/connexion.html`** — Format réponse incohérent, rôles `admin` vs `ADMIN`  
6. **`pages/inscription.html`** — Fallback cassé créant un faux token malformé  
7. **`pages/dashboard.html`** — Import `Cart` cassé supprimé, routes API inexistantes corrigées  
8. **`pages/panier.html`** — Normalisation champs (prix_effectif, quantite, nom)  
9. **`pages/produit.html`** — Champs manquants (nom→name, prix→price, images)  
10. **`pages/commandes.html`** — Mapping statut FR→ENUM, champ reference  
11. **`pages/commande.html`** — Payload normalisé, MapModule sécurisé  
12. **`pages/wishlist.html`** — Réécriture complète des appels API  
13. **`pages/profil.html`** — Champs prenom/nom, sauvegarde localStorage  
14. **`pages/notifications.html`** — Champs titre→title, lu→read  
15. **`pages/wallet.html`** — Clé `sx_wallet` → `wallet` (cohérence API)  
16. **`pages/suivi.html`** — Normalisation commande, champs statut  
17. **`pages/login.html`** — Import `static-auth.js` remplacé par `API.auth`  
18. **`pages/register.html`** — Idem + redirection vers dashboard  
19. **`pages/checkout.html`** — Import nommé `{Panier}` → import par défaut  
20. **`pages/orders.html`** — Import nommé `{Commandes}` → import par défaut  
21. **`pages/admin/dashboard.html`** — `Auth.isAuthenticated()` → `isAuthenticated()`  
22. **`pages/admin/login.html`** — Rôle `ADMIN` → `admin`, appel API corrigé  
23. **`pages/admin/produits.html`** — CRUD complet via localStorage  
24. **`pages/admin/commandes.html`** — Chargement depuis localStorage  
25. **`pages/admin/utilisateurs.html`** — Chargement + normalisation utilisateurs  
26. **`pages/admin/analytics.html`** — Données statiques depuis localStorage  
27. **`pages/admin/fraude.html`** — Routes API corrigées  
28. **`pages/admin.html`** — Double import supprimé, rôles corrigés  
29. **`assets/js/modules/cart.js`** — Réécrit pour wrapper `API.panier`  
30. **`pages/index.html`** — CSS dupliqué + video MP4 utilisé comme `background-image`  

