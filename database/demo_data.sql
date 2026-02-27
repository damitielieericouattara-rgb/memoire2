-- Données de démonstration
-- Exécutez ce script dans phpMyAdmin pour avoir des produits à tester

USE memoire_ecommerce_intelligent;

-- Produits de démo
INSERT INTO products (name, short_description, description, price, promo_price, stock, status, category_id, popularity_score, created_at) VALUES
('iPhone 15 Pro', 'Smartphone Apple haut de gamme', 'iPhone 15 Pro avec puce A17 Pro, appareil photo 48MP', 850000, 799000, 15, 'ACTIVE', 1, 150, NOW()),
('Samsung Galaxy S24', 'Smartphone Android premium', 'Galaxy S24 avec IA intégrée, écran AMOLED', 720000, NULL, 8, 'ACTIVE', 1, 120, NOW()),
('MacBook Air M3', 'Ordinateur portable ultra-léger', 'MacBook Air avec puce M3, 8Go RAM, 256Go SSD', 1200000, 1150000, 5, 'ACTIVE', 1, 80, NOW()),
('AirPods Pro 2', 'Écouteurs sans fil', 'AirPods Pro avec réduction de bruit active', 180000, 160000, 25, 'ACTIVE', 1, 200, NOW()),
('Robe Wax Africaine', 'Tenue traditionnelle moderne', 'Robe en tissu wax, motifs africains, taille unique', 45000, NULL, 30, 'ACTIVE', 2, 90, NOW()),
('Chemise Kente', 'Chemise homme tissu kente', 'Chemise en tissu kente Ghana, coupe moderne', 35000, 29000, 20, 'ACTIVE', 2, 60, NOW()),
('Canapé 3 places', 'Canapé confortable salon', 'Canapé en tissu velours, couleur gris, 3 places', 350000, NULL, 3, 'ACTIVE', 3, 40, NOW()),
('Climatiseur 18000 BTU', 'Climatiseur split system', 'Climatiseur Gree 18000 BTU, inverter, classe A++', 420000, 390000, 7, 'ACTIVE', 1, 70, NOW());

SELECT COUNT(*) as produits_crees FROM products;
