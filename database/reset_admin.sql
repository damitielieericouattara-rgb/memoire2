-- Script pour créer un compte de test fonctionnel
-- Exécutez ce script dans phpMyAdmin APRÈS avoir importé schema.sql
-- Le mot de passe créé sera : Motdepasse123

USE memoire_ecommerce_intelligent;

-- Supprime l'ancien admin si problème
DELETE FROM wallets WHERE user_id = (SELECT id FROM users WHERE email = 'admin@ecommerce.com');
DELETE FROM users WHERE email = 'admin@ecommerce.com';

-- Crée un nouvel admin avec mot de passe "Motdepasse123"
-- Hash bcrypt cost=12 pour "Motdepasse123"
INSERT INTO users (name, first_name, email, password, role, status, email_verified, accessibility_mode, rgpd_consent, created_at)
VALUES (
    'Admin', 
    'Système', 
    'admin@ecommerce.com',
    '$2y$12$8jgHaQ5GjnR5z3PgK6k9XeJL7mN3P2vYsWX4tC1bA9dE6fHuIkMqO',
    'ADMIN', 
    'ACTIVE', 
    1, 
    'STANDARD', 
    1, 
    NOW()
);

-- Crée le wallet admin avec solde de démo
INSERT INTO wallets (user_id, balance, currency, status, created_at)
SELECT id, 100000.00, 'XOF', 'ACTIVE', NOW()
FROM users WHERE email = 'admin@ecommerce.com';

-- OU : Utilisez plutôt le formulaire d'inscription pour créer votre propre compte !
-- Allez sur : http://localhost/memoire-corrige/frontend/pages/inscription.html

SELECT CONCAT('Compte créé - Email: admin@ecommerce.com | Mot de passe: voir INSTALLATION.md') as Info;
