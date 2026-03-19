-- ============================================================
-- SneakX E-Commerce Platform
-- Schéma Base de Données Complet
-- Projet de Mémoire Universitaire
-- MariaDB / MySQL 5.7+
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `sneakx_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `sneakx_db`;

-- ──────────────────────────────────────────────
-- TABLE : users
-- ──────────────────────────────────────────────
CREATE TABLE `users` (
  `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `nom`             VARCHAR(100)    NOT NULL,
  `prenom`          VARCHAR(100)    NOT NULL,
  `email`           VARCHAR(191)    NOT NULL,
  `telephone`       VARCHAR(25)     DEFAULT NULL,
  `password_hash`   VARCHAR(255)    NOT NULL,
  `role`            ENUM('client','admin','livreur') NOT NULL DEFAULT 'client',
  `avatar`          VARCHAR(255)    DEFAULT NULL,
  `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
  `email_verified`  TINYINT(1)      NOT NULL DEFAULT 0,
  `token_verif`     VARCHAR(100)    DEFAULT NULL,
  `reset_token`     VARCHAR(100)    DEFAULT NULL,
  `reset_expires`   DATETIME        DEFAULT NULL,
  `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : categories
-- ──────────────────────────────────────────────
CREATE TABLE `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom`         VARCHAR(100) NOT NULL,
  `slug`        VARCHAR(120) NOT NULL,
  `description` TEXT         DEFAULT NULL,
  `image`       VARCHAR(255) DEFAULT NULL,
  `parent_id`   INT UNSIGNED DEFAULT NULL,
  `ordre`       INT          NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)  NOT NULL DEFAULT 1,
  `created_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `fk_cat_parent` (`parent_id`),
  CONSTRAINT `fk_cat_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : products
-- ──────────────────────────────────────────────
CREATE TABLE `products` (
  `id`            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `category_id`   INT UNSIGNED    NOT NULL,
  `nom`           VARCHAR(200)    NOT NULL,
  `slug`          VARCHAR(220)    NOT NULL,
  `description`   TEXT            DEFAULT NULL,
  `prix`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
  `prix_promo`    DECIMAL(12,2)   DEFAULT NULL,
  `stock`         INT             NOT NULL DEFAULT 0,
  `images`        JSON            DEFAULT NULL,
  `marque`        VARCHAR(100)    DEFAULT NULL,
  `reference`     VARCHAR(100)    DEFAULT NULL,
  `tailles`       JSON            DEFAULT NULL,
  `couleurs`      JSON            DEFAULT NULL,
  `poids_kg`      DECIMAL(6,3)    DEFAULT NULL,
  `is_active`     TINYINT(1)      NOT NULL DEFAULT 1,
  `is_featured`   TINYINT(1)      NOT NULL DEFAULT 0,
  `is_new`        TINYINT(1)      NOT NULL DEFAULT 0,
  `vues`          INT UNSIGNED    NOT NULL DEFAULT 0,
  `ventes`        INT UNSIGNED    NOT NULL DEFAULT 0,
  `note_moyenne`  DECIMAL(3,2)    DEFAULT NULL,
  `nb_avis`       INT UNSIGNED    NOT NULL DEFAULT 0,
  `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug` (`slug`),
  KEY `fk_prod_cat` (`category_id`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_stock` (`stock`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : product_views
-- ──────────────────────────────────────────────
CREATE TABLE `product_views` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED    NOT NULL,
  `user_id`    INT UNSIGNED    DEFAULT NULL,
  `session_id` VARCHAR(100)    DEFAULT NULL,
  `ip`         VARCHAR(45)     DEFAULT NULL,
  `viewed_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pv_product` (`product_id`),
  KEY `fk_pv_user` (`user_id`),
  KEY `idx_viewed_at` (`viewed_at`),
  CONSTRAINT `fk_pv_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pv_user`    FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : product_reviews
-- ──────────────────────────────────────────────
CREATE TABLE `product_reviews` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`  INT UNSIGNED NOT NULL,
  `user_id`     INT UNSIGNED NOT NULL,
  `note`        TINYINT      NOT NULL DEFAULT 5,
  `commentaire` TEXT         DEFAULT NULL,
  `is_approved` TINYINT(1)  NOT NULL DEFAULT 0,
  `created_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_rev_prod` (`product_id`),
  KEY `fk_rev_user` (`user_id`),
  CONSTRAINT `fk_rev_prod` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : cart
-- ──────────────────────────────────────────────
CREATE TABLE `cart` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `session_id` VARCHAR(100) DEFAULT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantite`   INT          NOT NULL DEFAULT 1,
  `taille`     VARCHAR(20)  DEFAULT NULL,
  `couleur`    VARCHAR(50)  DEFAULT NULL,
  `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_cart_prod` (`product_id`),
  KEY `fk_cart_user` (`user_id`),
  CONSTRAINT `fk_cart_prod` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : wishlists
-- ──────────────────────────────────────────────
CREATE TABLE `wishlists` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wish` (`user_id`, `product_id`),
  KEY `fk_wl_prod` (`product_id`),
  CONSTRAINT `fk_wl_user` FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  CONSTRAINT `fk_wl_prod` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : orders
-- ──────────────────────────────────────────────
CREATE TABLE `orders` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED NOT NULL,
  `numero`            VARCHAR(30)  NOT NULL,
  `statut`            ENUM('en_attente','confirmee','en_preparation','expediee','livree','annulee','remboursee') NOT NULL DEFAULT 'en_attente',
  `total_ht`          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `frais_livraison`   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `remise`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_ttc`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `methode_paiement`  ENUM('wave','mtn_momo','orange_money','moov_money','carte_bancaire','livraison') NOT NULL,
  `adresse_livraison` TEXT          DEFAULT NULL,
  `ville`             VARCHAR(100)  DEFAULT NULL,
  `quartier`          VARCHAR(100)  DEFAULT NULL,
  `telephone_livraison` VARCHAR(25) DEFAULT NULL,
  `latitude`          DECIMAL(10,7) DEFAULT NULL,
  `longitude`         DECIMAL(10,7) DEFAULT NULL,
  `code_promo`        VARCHAR(50)   DEFAULT NULL,
  `note_client`       TEXT          DEFAULT NULL,
  `qr_code`           VARCHAR(500)  DEFAULT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_numero` (`numero`),
  KEY `fk_ord_user` (`user_id`),
  KEY `idx_statut` (`statut`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_ord_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : order_items
-- ──────────────────────────────────────────────
CREATE TABLE `order_items` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_id`    INT UNSIGNED  NOT NULL,
  `product_id`  INT UNSIGNED  NOT NULL,
  `nom_produit` VARCHAR(200)  NOT NULL,
  `prix_unit`   DECIMAL(12,2) NOT NULL,
  `quantite`    INT           NOT NULL DEFAULT 1,
  `taille`      VARCHAR(20)   DEFAULT NULL,
  `couleur`     VARCHAR(50)   DEFAULT NULL,
  `sous_total`  DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_oi_order`   (`order_id`),
  KEY `fk_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : payments
-- ──────────────────────────────────────────────
CREATE TABLE `payments` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_id`        INT UNSIGNED  NOT NULL,
  `methode`         VARCHAR(50)   NOT NULL,
  `montant`         DECIMAL(12,2) NOT NULL,
  `statut`          ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
  `transaction_id`  VARCHAR(200)  DEFAULT NULL,
  `numero_paiement` VARCHAR(25)   DEFAULT NULL,
  `payload`         JSON          DEFAULT NULL,
  `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pay_order` (`order_id`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : deliveries
-- ──────────────────────────────────────────────
CREATE TABLE `deliveries` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `order_id`         INT UNSIGNED  NOT NULL,
  `livreur_id`       INT UNSIGNED  DEFAULT NULL,
  `statut`           ENUM('en_attente','assigne','en_route','livre','echec') NOT NULL DEFAULT 'en_attente',
  `latitude_depart`  DECIMAL(10,7) DEFAULT NULL,
  `longitude_depart` DECIMAL(10,7) DEFAULT NULL,
  `latitude_dest`    DECIMAL(10,7) DEFAULT NULL,
  `longitude_dest`   DECIMAL(10,7) DEFAULT NULL,
  `adresse_dest`     TEXT          DEFAULT NULL,
  `note_livreur`     TEXT          DEFAULT NULL,
  `date_livraison`   DATETIME      DEFAULT NULL,
  `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_del_order`   (`order_id`),
  KEY `fk_del_livreur` (`livreur_id`),
  CONSTRAINT `fk_del_order`   FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)  ON DELETE CASCADE,
  CONSTRAINT `fk_del_livreur` FOREIGN KEY (`livreur_id`) REFERENCES `users`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : promotions
-- ──────────────────────────────────────────────
CREATE TABLE `promotions` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(50)   NOT NULL,
  `type`        ENUM('pourcentage','fixe') NOT NULL DEFAULT 'pourcentage',
  `valeur`      DECIMAL(10,2) NOT NULL,
  `min_achat`   DECIMAL(12,2) DEFAULT NULL,
  `max_usage`   INT           DEFAULT NULL,
  `usages`      INT           NOT NULL DEFAULT 0,
  `date_debut`  DATE          DEFAULT NULL,
  `date_fin`    DATE          DEFAULT NULL,
  `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : notifications
-- ──────────────────────────────────────────────
CREATE TABLE `notifications` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `titre`      VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `type`       ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `is_read`    TINYINT(1)  NOT NULL DEFAULT 0,
  `lien`       VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_notif_user` (`user_id`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TABLE : reports (rapports mensuels)
-- ──────────────────────────────────────────────
CREATE TABLE `reports` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`        VARCHAR(50)  NOT NULL DEFAULT 'mensuel',
  `periode`     VARCHAR(20)  NOT NULL,
  `fichier`     VARCHAR(255) DEFAULT NULL,
  `donnees`     JSON         DEFAULT NULL,
  `created_at`  DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ──────────────────────────────────────────────
-- TRIGGERS
-- ──────────────────────────────────────────────
DELIMITER $$

-- Alerte stock faible
CREATE TRIGGER `trg_stock_alert` AFTER UPDATE ON `products`
FOR EACH ROW
BEGIN
  IF NEW.stock < 5 AND OLD.stock >= 5 THEN
    INSERT INTO `notifications`(`titre`, `message`, `type`, `lien`)
    VALUES (
      'Alerte stock faible',
      CONCAT('Le produit "', NEW.nom, '" n\'a plus que ', NEW.stock, ' unités.'),
      'warning',
      CONCAT('/backend/api/produits.php?id=', NEW.id)
    );
  END IF;
END$$

-- Compteur ventes
CREATE TRIGGER `trg_update_ventes` AFTER INSERT ON `order_items`
FOR EACH ROW
BEGIN
  UPDATE `products` SET `ventes` = `ventes` + NEW.quantite WHERE `id` = NEW.product_id;
  UPDATE `products` SET `stock`  = `stock`  - NEW.quantite WHERE `id` = NEW.product_id AND `stock` >= NEW.quantite;
END$$

-- Numéro commande auto
CREATE TRIGGER `trg_order_numero` BEFORE INSERT ON `orders`
FOR EACH ROW
BEGIN
  IF NEW.numero IS NULL OR NEW.numero = '' THEN
    SET NEW.numero = CONCAT('SNX-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND()*99999), 5, '0'));
  END IF;
END$$

DELIMITER ;

-- ──────────────────────────────────────────────
-- DONNÉES DE DÉMONSTRATION
-- ──────────────────────────────────────────────

-- Admin (mot de passe : Admin@123)
INSERT INTO `users` (`nom`,`prenom`,`email`,`password_hash`,`role`,`is_active`,`email_verified`) VALUES
('Admin','SneakX','admin@sneakx.ci','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin',1,1),
('Ouattara','Éric','eric@test.ci','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','client',1,1),
('Koné','Aminata','aminata@test.ci','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','client',1,1),
('Diallo','Mohamed','mohamed@test.ci','$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','client',1,1);

-- Catégories
INSERT INTO `categories` (`nom`,`slug`,`description`,`ordre`) VALUES
('Sneakers Nike','nike','Collection officielle Nike',1),
('Sneakers Adidas','adidas','Collection officielle Adidas',2),
('Jordan Brand','jordan','Collection Air Jordan',3),
('New Balance','new-balance','Collection New Balance',4),
('Puma','puma','Collection Puma',5),
('Accessoires','accessoires','Sacs, casquettes, chaussettes',6);

-- Produits
INSERT INTO `products` (`category_id`,`nom`,`slug`,`description`,`prix`,`prix_promo`,`stock`,`marque`,`tailles`,`couleurs`,`images`,`is_featured`,`is_new`,`vues`,`ventes`,`note_moyenne`,`nb_avis`) VALUES
(1,'Nike Air Max 270','nike-air-max-270','La Nike Air Max 270 offre un amorti révolutionnaire grâce à sa grande unité Air Max. Tige en mesh respirant pour garder vos pieds au frais toute la journée. Semelle en caoutchouc durable pour une traction optimale sur différentes surfaces.',45000,38000,15,'Nike','[40,41,42,43,44,45]','["Noir","Blanc","Rouge"]','["https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600&q=80","https://images.unsplash.com/photo-1556906781-9a412961d379?w=600&q=80"]',1,0,342,48,4.5,128),
(2,'Adidas Ultra Boost 22','adidas-ultra-boost-22','L\'Ultra Boost 22 combine la technologie Boost pour une restitution d\'énergie maximale et un amorti inégalé. La tige Primeknit s\'adapte parfaitement à la forme de votre pied pour un confort exceptionnel.',52000,NULL,8,'Adidas','[40,41,42,43,44,45]','["Bleu","Noir","Blanc"]','["https://images.unsplash.com/photo-1556905055-8f358a7a797e?w=600&q=80"]',1,1,289,31,4.7,89),
(3,'Air Jordan 1 Retro High OG','air-jordan-1-retro-high','La Jordan 1 Retro High est un classique intemporel qui a révolutionné la culture sneaker. Tige en cuir de qualité supérieure, amorti Nike Air intégré et semelle en caoutchouc pour une durabilité maximale.',75000,NULL,5,'Jordan','[40,41,42,43,44,45]','["Rouge/Noir","Blanc/Noir","Royal Bleu"]','["https://images.unsplash.com/photo-1549298916-b41d501d3772?w=600&q=80"]',1,0,893,67,4.9,256),
(4,'New Balance 574','new-balance-574','La 574 incarne le style rétro new balance avec un design intemporel. Tige en daim et mesh respirant, semelle ENCAP pour amorti et soutien. Le classique parfait pour le quotidien.',38000,32000,12,'New Balance','[39,40,41,42,43,44]','["Gris","Bleu marine","Vert"]','["https://images.unsplash.com/photo-1608231387022-66b8c6c0445c?w=600&q=80"]',0,0,156,19,4.3,67),
(5,'Puma RS-X³','puma-rs-x3','La Puma RS-X³ offre un style audacieux inspiré des années 80. Plateforme chunky tendance, mesh léger et superpositions en cuir synthétique. La chaussure parfaite pour se démarquer.',35000,28000,10,'Puma','[40,41,42,43,44]','["Noir","Blanc","Rose"]','["https://images.unsplash.com/photo-1600269452121-4f2416e55c28?w=600&q=80"]',0,1,124,15,4.1,34),
(1,'Nike Dunk Low Panda','nike-dunk-low-panda','La Nike Dunk Low Panda, avec son coloris noir et blanc épuré, est devenue un phénomène streetwear mondial. Tige en cuir authentique, semelle basse en caoutchouc. Un must-have absolu.',48000,NULL,3,'Nike','[38,39,40,41,42,43,44,45]','["Noir/Blanc"]','["https://images.unsplash.com/photo-1539185441755-769473a23570?w=600&q=80"]',1,0,521,89,4.8,189),
(6,'Sac à dos Nike Heritage','sac-dos-nike-heritage','Sac à dos Nike Heritage avec compartiment principal spacieux, poche avant zippée et sangles rembourrées. Capacité 25L, parfait pour le sport ou le quotidien.',18500,NULL,30,'Nike','[]','["Noir","Gris","Orange"]','["https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&q=80"]',0,0,87,22,4.2,45),
(2,'Adidas Stan Smith','adidas-stan-smith','Le Stan Smith, icône du tennis depuis les années 70. Tige en cuir lisse blanc, logo Adidas perforé sur les côtés. Un classique élégant et polyvalent pour toutes les occasions.',32000,NULL,20,'Adidas','[38,39,40,41,42,43,44,45]','["Blanc/Vert","Blanc/Navy","Tout Blanc"]','["https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=600&q=80"]',0,0,203,41,4.4,112);

-- Promotions
INSERT INTO `promotions` (`code`,`type`,`valeur`,`min_achat`,`max_usage`,`is_active`) VALUES
('SNEAKX10','pourcentage',10,20000,100,1),
('BIENVENUE20','pourcentage',20,50000,50,1),
('LIVRAISON0','fixe',2500,30000,200,1),
('MEMOIRE25','pourcentage',25,80000,20,1);

-- Notifications admin
INSERT INTO `notifications` (`titre`,`message`,`type`,`lien`) VALUES
('Alerte stock faible','Le produit "Air Jordan 1 Retro High OG" n\'a plus que 5 unités en stock.','warning','/admin/produits'),
('Nouveau rapport mensuel','Le rapport de décembre 2024 est disponible.','info','/admin/rapports');

COMMIT;
