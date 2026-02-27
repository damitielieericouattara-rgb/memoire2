-- =============================================================
-- SNEAKX — SCHÉMA COMPLET v2.0
-- E-Commerce Intelligent, Vocal, Inclusif et Innovant
-- Compatible MariaDB 10.4+ / MySQL 8+
-- =============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `memoire_ecommerce_intelligent`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE `memoire_ecommerce_intelligent`;

-- ─────────────────────────────────────────────────────────────
-- TABLES UTILISATEURS & AUTH
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `users` (
  `id`               INT(11)      NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100) NOT NULL,
  `first_name`       VARCHAR(100) NOT NULL,
  `email`            VARCHAR(150) NOT NULL,
  `password`         VARCHAR(255) NOT NULL,
  `phone`            VARCHAR(20)  DEFAULT NULL,
  `role`             ENUM('CLIENT','ADMIN','LIVREUR') NOT NULL DEFAULT 'CLIENT',
  `status`           ENUM('ACTIVE','INACTIVE','BANNED','PENDING') NOT NULL DEFAULT 'PENDING',
  `accessibility_mode` ENUM('STANDARD','VOCAL','LOW_VISION','SIGN_LANGUAGE') NOT NULL DEFAULT 'STANDARD',
  `email_verified`   TINYINT(1)   NOT NULL DEFAULT 0,
  `verify_token`     VARCHAR(255) DEFAULT NULL,
  `preferred_lang`   VARCHAR(5)   NOT NULL DEFAULT 'fr',
  `preferred_currency` VARCHAR(3) NOT NULL DEFAULT 'XOF',
  `rgpd_consent`     TINYINT(1)   NOT NULL DEFAULT 0,
  `rgpd_date`        DATETIME     DEFAULT NULL,
  `avatar_url`       VARCHAR(500) DEFAULT NULL,
  `dark_mode`        TINYINT(1)   NOT NULL DEFAULT 1,
  `push_token`       TEXT         DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT current_timestamp(),
  `last_login`       DATETIME     DEFAULT NULL,
  `updated_at`       DATETIME     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profils accessibilité détaillés
CREATE TABLE `accessibility_profiles` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`        INT(11)       NOT NULL,
  `low_vision_mode` TINYINT(1)  NOT NULL DEFAULT 0,
  `vocal_mode`     TINYINT(1)   NOT NULL DEFAULT 0,
  `sign_language`  TINYINT(1)   NOT NULL DEFAULT 0,
  `high_contrast`  TINYINT(1)   NOT NULL DEFAULT 0,
  `font_size`      INT(11)      NOT NULL DEFAULT 16,
  `speech_rate`    DECIMAL(3,1) NOT NULL DEFAULT 1.0,
  `subtitles`      TINYINT(1)   NOT NULL DEFAULT 0,
  `screen_reader_active` TINYINT(1) NOT NULL DEFAULT 0,
  `updated_at`     DATETIME     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_access_user` (`user_id`),
  CONSTRAINT `fk_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stats d'utilisation accessibilité (pour admin)
CREATE TABLE `accessibility_stats` (
  `id`         INT(11)     NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)     DEFAULT NULL,
  `session_id` VARCHAR(64) NOT NULL,
  `mode_used`  ENUM('STANDARD','VOCAL','LOW_VISION','SIGN_LANGUAGE') NOT NULL,
  `feature`    VARCHAR(100) NOT NULL COMMENT 'Ex: voice_search, tts_product, sign_video',
  `duration_sec` INT DEFAULT NULL,
  `created_at` DATETIME    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_acc_user` (`user_id`),
  KEY `idx_acc_mode` (`mode_used`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stats vocales (pour admin)
CREATE TABLE `voice_stats` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      DEFAULT NULL,
  `command`    VARCHAR(255) NOT NULL,
  `intent`     VARCHAR(100) NOT NULL COMMENT 'search, add_cart, navigate, confirm_order...',
  `success`    TINYINT(1)   NOT NULL DEFAULT 1,
  `lang`       VARCHAR(5)   NOT NULL DEFAULT 'fr',
  `created_at` DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_vs_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- ADRESSES
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `addresses` (
  `id`          INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     INT(11)      NOT NULL,
  `label`       VARCHAR(100) NOT NULL DEFAULT 'Domicile',
  `street`      VARCHAR(255) NOT NULL,
  `complement`  VARCHAR(255) DEFAULT NULL,
  `city`        VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20)  NOT NULL,
  `country`     VARCHAR(100) NOT NULL DEFAULT 'Cote d''Ivoire',
  `latitude`    DECIMAL(10,8) DEFAULT NULL,
  `longitude`   DECIMAL(11,8) DEFAULT NULL,
  `is_default`  TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_addr_user` (`user_id`),
  CONSTRAINT `fk_addr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- CATALOGUE PRODUITS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `categories` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `name_en`       VARCHAR(100) DEFAULT NULL,
  `description`   TEXT         DEFAULT NULL,
  `slug`          VARCHAR(150) NOT NULL,
  `image_url`     VARCHAR(500) DEFAULT NULL,
  `parent_id`     INT(11)      DEFAULT NULL,
  `display_order` INT(11)      NOT NULL DEFAULT 0,
  `active`        TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_category_slug` (`slug`),
  KEY `fk_category_parent` (`parent_id`),
  CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `products` (
  `id`                INT(11)      NOT NULL AUTO_INCREMENT,
  `name`              VARCHAR(255) NOT NULL,
  `name_en`           VARCHAR(255) DEFAULT NULL,
  `short_description` VARCHAR(500) DEFAULT NULL,
  `short_description_en` VARCHAR(500) DEFAULT NULL,
  `long_description`  TEXT         DEFAULT NULL,
  `long_description_en` TEXT       DEFAULT NULL,
  `price`             DECIMAL(10,2) NOT NULL,
  `promo_price`       DECIMAL(10,2) DEFAULT NULL,
  `stock`             INT(11)      NOT NULL DEFAULT 0,
  `stock_alert`       INT(11)      NOT NULL DEFAULT 5,
  `sku`               VARCHAR(100) NOT NULL,
  `weight`            DECIMAL(8,2) DEFAULT NULL,
  `average_rating`    DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  `reviews_count`     INT(11)      NOT NULL DEFAULT 0,
  `popularity_score`  INT(11)      NOT NULL DEFAULT 0,
  `video_url`         VARCHAR(500) DEFAULT NULL,
  `audio_url`         VARCHAR(500) DEFAULT NULL,
  `status`            ENUM('ACTIVE','INACTIVE','PREORDER','OUT_OF_STOCK') NOT NULL DEFAULT 'ACTIVE',
  `category_id`       INT(11)      NOT NULL,
  `allow_resale`      TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`        DATETIME     NOT NULL DEFAULT current_timestamp(),
  `updated_at`        DATETIME     NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_product_sku` (`sku`),
  KEY `idx_prod_category` (`category_id`),
  KEY `idx_prod_status` (`status`),
  FULLTEXT KEY `idx_prod_search` (`name`, `short_description`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_images` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `product_id`    INT(11)      NOT NULL,
  `url`           VARCHAR(500) NOT NULL,
  `alt_text`      VARCHAR(255) DEFAULT NULL,
  `display_order` INT(11)      NOT NULL DEFAULT 0,
  `is_main`       TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_img_product` (`product_id`),
  CONSTRAINT `fk_img_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Packs automatiques (produits souvent achetés ensemble)
CREATE TABLE `product_bundles` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `product_id`  INT(11) NOT NULL,
  `bundle_id`   INT(11) NOT NULL,
  `frequency`   INT(11) NOT NULL DEFAULT 1 COMMENT 'Nombre fois achetés ensemble',
  `discount_pct` DECIMAL(5,2) DEFAULT NULL COMMENT 'Réduction si acheté en pack',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bundle` (`product_id`, `bundle_id`),
  KEY `fk_bundle_product` (`product_id`),
  KEY `fk_bundle_related` (`bundle_id`),
  CONSTRAINT `fk_bundle_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bundle_related` FOREIGN KEY (`bundle_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Marché de revente/échange interne
CREATE TABLE `exchange_items` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `seller_id`    INT(11)       NOT NULL,
  `product_id`   INT(11)       DEFAULT NULL COMMENT 'Produit original (optionnel)',
  `title`        VARCHAR(255)  NOT NULL,
  `description`  TEXT          DEFAULT NULL,
  `price`        DECIMAL(10,2) NOT NULL,
  `condition`    ENUM('LIKE_NEW','GOOD','FAIR','FOR_PARTS') NOT NULL DEFAULT 'GOOD',
  `images`       JSON          DEFAULT NULL,
  `status`       ENUM('PENDING','ACTIVE','SOLD','REMOVED') NOT NULL DEFAULT 'PENDING',
  `type`         ENUM('SALE','EXCHANGE','BOTH') NOT NULL DEFAULT 'SALE',
  `views_count`  INT(11) NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at`   DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_exch_seller` (`seller_id`),
  KEY `fk_exch_product` (`product_id`),
  CONSTRAINT `fk_exch_seller` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exch_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- COMPORTEMENTS & RECOMMANDATIONS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `product_views` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`       INT(11)      DEFAULT NULL,
  `product_id`    INT(11)      NOT NULL,
  `action_type`   ENUM('VIEW','CLICK','SEARCH','CART_ADD','PURCHASE','VOCAL','CART_ABANDON') NOT NULL DEFAULT 'VIEW',
  `view_duration` INT(11)      DEFAULT NULL,
  `session_id`    VARCHAR(128) NOT NULL,
  `ip_anonymized` VARCHAR(20)  DEFAULT NULL,
  `page_url`      VARCHAR(500) DEFAULT NULL,
  `value_action`  VARCHAR(255) DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_view_user` (`user_id`),
  KEY `idx_views_product` (`product_id`),
  KEY `idx_views_session` (`session_id`),
  CONSTRAINT `fk_view_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_view_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `user_behaviors` (
  `id`           INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11)          DEFAULT NULL,
  `session_id`   VARCHAR(64)      DEFAULT NULL,
  `product_id`   INT(11)          DEFAULT NULL,
  `action`       ENUM('VIEW','SEARCH','CART_ADD','PURCHASE','WISHLIST') NOT NULL,
  `search_query` VARCHAR(255)     DEFAULT NULL,
  `ip_address`   VARCHAR(45)      DEFAULT NULL,
  `user_agent`   VARCHAR(500)     DEFAULT NULL,
  `created_at`   TIMESTAMP        NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `user_behaviors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_behaviors_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- PANIER & ABANDON PANIER
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `cart_items` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11) NOT NULL,
  `product_id` INT(11) NOT NULL,
  `quantity`   INT(11) NOT NULL DEFAULT 1,
  `added_at`   DATETIME NOT NULL DEFAULT current_timestamp(),
  `updated_at` DATETIME NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cart_user_product` (`user_id`, `product_id`),
  KEY `fk_cart_product` (`product_id`),
  CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Suivi abandon panier (pour emails automatiques)
CREATE TABLE `cart_abandonment` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `user_id`         INT(11) NOT NULL,
  `cart_snapshot`   JSON    NOT NULL COMMENT 'Snapshot JSON du panier',
  `total_amount`    DECIMAL(10,2) NOT NULL,
  `email_sent_at`   DATETIME DEFAULT NULL,
  `email_2_sent_at` DATETIME DEFAULT NULL,
  `recovered`       TINYINT(1) NOT NULL DEFAULT 0,
  `discount_offered` DECIMAL(5,2) DEFAULT NULL,
  `abandoned_at`    DATETIME NOT NULL DEFAULT current_timestamp(),
  `recovered_at`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_aband_user` (`user_id`),
  CONSTRAINT `fk_aband_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Wishlist
CREATE TABLE `wishlist` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL,
  `name`       VARCHAR(100) NOT NULL DEFAULT 'Ma liste',
  `is_public`  TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at` DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_wish_user` (`user_id`),
  CONSTRAINT `fk_wish_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `wishlist_items` (
  `id`            INT(11) NOT NULL AUTO_INCREMENT,
  `wishlist_id`   INT(11) NOT NULL,
  `product_id`    INT(11) NOT NULL,
  `personal_note` TEXT    DEFAULT NULL,
  `added_at`      DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wish_product` (`wishlist_id`, `product_id`),
  KEY `fk_witem_product` (`product_id`),
  CONSTRAINT `fk_witem_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_witem_wishlist` FOREIGN KEY (`wishlist_id`) REFERENCES `wishlist` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- COMMANDES & LIVRAISON
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `orders` (
  `id`                   INT(11)       NOT NULL AUTO_INCREMENT,
  `order_number`         VARCHAR(30)   NOT NULL,
  `user_id`              INT(11)       NOT NULL,
  `address_id`           INT(11)       DEFAULT NULL,
  `status`               ENUM('RECEIVED','PREPARING','SHIPPED','IN_DELIVERY','DELIVERED','CANCELLED','REFUNDED') NOT NULL DEFAULT 'RECEIVED',
  `amount_ht`            DECIMAL(10,2) NOT NULL,
  `vat_rate`             DECIMAL(5,2)  NOT NULL DEFAULT 18.00,
  `amount_ttc`           DECIMAL(10,2) NOT NULL,
  `shipping_cost`        DECIMAL(8,2)  NOT NULL DEFAULT 0.00,
  `payment_method`       ENUM('WALLET','CARD','CASH_ON_DELIVERY','MOBILE_MONEY','CREDIT_3X') NOT NULL,
  `delivery_address_snap` JSON         DEFAULT NULL,
  `notes`                TEXT          DEFAULT NULL,
  `is_preorder`          TINYINT(1)    NOT NULL DEFAULT 0,
  `estimated_delivery`   DATE          DEFAULT NULL,
  `currency_used`        VARCHAR(3)    NOT NULL DEFAULT 'XOF',
  `amount_foreign`       DECIMAL(12,2) DEFAULT NULL COMMENT 'Montant dans la devise étrangère',
  `created_at`           DATETIME      NOT NULL DEFAULT current_timestamp(),
  `updated_at`           DATETIME      NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_order_number` (`order_number`),
  KEY `fk_order_user` (`user_id`),
  KEY `fk_order_address` (`address_id`),
  CONSTRAINT `fk_order_address` FOREIGN KEY (`address_id`) REFERENCES `addresses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_items` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `order_id`     INT(11)       NOT NULL,
  `product_id`   INT(11)       DEFAULT NULL,
  `product_name` VARCHAR(255)  NOT NULL,
  `quantity`     INT(11)       NOT NULL,
  `unit_price`   DECIMAL(10,2) NOT NULL,
  `discount`     DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `subtotal`     DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_item_order` (`order_id`),
  KEY `fk_item_product` (`product_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tracking GPS livraison en temps réel
CREATE TABLE `order_tracking` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `order_id`    INT(11)       NOT NULL,
  `livreur_id`  INT(11)       DEFAULT NULL,
  `status`      VARCHAR(100)  NOT NULL,
  `description` TEXT          DEFAULT NULL,
  `latitude`    DECIMAL(10,8) DEFAULT NULL,
  `longitude`   DECIMAL(11,8) DEFAULT NULL,
  `event_date`  DATETIME      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_track_order` (`order_id`),
  KEY `fk_track_livreur` (`livreur_id`),
  CONSTRAINT `fk_track_livreur` FOREIGN KEY (`livreur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_track_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Précommandes
CREATE TABLE `preorders` (
  `id`                INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`           INT(11)       NOT NULL,
  `product_id`        INT(11)       NOT NULL,
  `quantity`          INT(11)       NOT NULL DEFAULT 1,
  `deposit_amount`    DECIMAL(10,2) DEFAULT NULL,
  `status`            ENUM('PENDING','CONFIRMED','CANCELLED','CONVERTED') NOT NULL DEFAULT 'PENDING',
  `availability_date` DATE          DEFAULT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_pre_user` (`user_id`),
  KEY `fk_pre_product` (`product_id`),
  CONSTRAINT `fk_pre_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_pre_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Paiement en crédit 3x
CREATE TABLE `credit_plans` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `order_id`       INT(11)       NOT NULL,
  `user_id`        INT(11)       NOT NULL,
  `total_amount`   DECIMAL(10,2) NOT NULL,
  `installments`   INT(11)       NOT NULL DEFAULT 3,
  `interest_rate`  DECIMAL(5,2)  NOT NULL DEFAULT 0.00 COMMENT 'Taux intérêts en %',
  `monthly_amount` DECIMAL(10,2) NOT NULL,
  `status`         ENUM('ACTIVE','COMPLETED','DEFAULTED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
  `created_at`     DATETIME      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_credit_order` (`order_id`),
  KEY `fk_credit_user` (`user_id`),
  CONSTRAINT `fk_credit_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `fk_credit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `credit_payments` (
  `id`          INT(11)       NOT NULL AUTO_INCREMENT,
  `plan_id`     INT(11)       NOT NULL,
  `installment` INT(11)       NOT NULL COMMENT 'Numéro de mensualité (1,2,3)',
  `amount`      DECIMAL(10,2) NOT NULL,
  `due_date`    DATE          NOT NULL,
  `paid_at`     DATETIME      DEFAULT NULL,
  `status`      ENUM('PENDING','PAID','LATE','CANCELLED') NOT NULL DEFAULT 'PENDING',
  PRIMARY KEY (`id`),
  KEY `fk_cp_plan` (`plan_id`),
  CONSTRAINT `fk_cp_plan` FOREIGN KEY (`plan_id`) REFERENCES `credit_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- AVIS & PROMOTIONS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `reviews` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL,
  `product_id` INT(11)      NOT NULL,
  `order_id`   INT(11)      NOT NULL,
  `rating`     TINYINT(4)   NOT NULL,
  `title`      VARCHAR(200) DEFAULT NULL,
  `comment`    TEXT         DEFAULT NULL,
  `status`     ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  `created_at` DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_user_order` (`user_id`, `product_id`, `order_id`),
  KEY `fk_rev_product` (`product_id`),
  KEY `fk_rev_order` (`order_id`),
  CONSTRAINT `fk_rev_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rev_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotions avec vote communautaire
CREATE TABLE `promotions` (
  `id`                   INT(11)       NOT NULL AUTO_INCREMENT,
  `code`                 VARCHAR(50)   NOT NULL,
  `title`                VARCHAR(200)  DEFAULT NULL,
  `description`          TEXT          DEFAULT NULL,
  `type`                 ENUM('PERCENTAGE','FIXED_AMOUNT','FREE_SHIPPING') NOT NULL,
  `value`                DECIMAL(8,2)  NOT NULL,
  `votes_count`          INT(11)       NOT NULL DEFAULT 0,
  `activation_threshold` INT(11)       NOT NULL DEFAULT 100,
  `active`               TINYINT(1)    NOT NULL DEFAULT 0,
  `community_promo`      TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Activée par vote',
  `start_date`           DATETIME      DEFAULT NULL,
  `end_date`             DATETIME      DEFAULT NULL,
  `max_uses`             INT(11)       DEFAULT NULL,
  `current_uses`         INT(11)       NOT NULL DEFAULT 0,
  `category_id`          INT(11)       DEFAULT NULL COMMENT 'Promo ciblée sur catégorie',
  `image_url`            VARCHAR(500)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_promo_code` (`code`),
  KEY `fk_promo_cat` (`category_id`),
  CONSTRAINT `fk_promo_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `votes_promotions` (
  `id`           INT(11)  NOT NULL AUTO_INCREMENT,
  `user_id`      INT(11)  NOT NULL,
  `promotion_id` INT(11)  NOT NULL,
  `voted_at`     DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vote_user_promo` (`user_id`, `promotion_id`),
  KEY `fk_vote_promotion` (`promotion_id`),
  CONSTRAINT `fk_vote_promotion` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `promo_codes` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`        VARCHAR(50)      NOT NULL,
  `type`        ENUM('PERCENT','FIXED') NOT NULL DEFAULT 'PERCENT',
  `value`       DECIMAL(10,2)    NOT NULL,
  `min_amount`  DECIMAL(12,2)    DEFAULT NULL,
  `max_uses`    INT(11)          DEFAULT NULL,
  `uses_count`  INT(11)          NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)       NOT NULL DEFAULT 1,
  `expires_at`  TIMESTAMP        NULL DEFAULT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- NOTIFICATIONS & WEBSOCKET
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `notifications` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)      NOT NULL,
  `type`       ENUM('ORDER','PAYMENT','PROMOTION','FRAUD_ALERT','SYSTEM','DELIVERY','STOCK','CART_ABANDON') NOT NULL,
  `title`      VARCHAR(200) NOT NULL,
  `message`    TEXT         NOT NULL,
  `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
  `channel`    ENUM('WEBSOCKET','EMAIL','PUSH','SMS') NOT NULL DEFAULT 'WEBSOCKET',
  `sent_at`    DATETIME     NOT NULL DEFAULT current_timestamp(),
  `read_at`    DATETIME     DEFAULT NULL,
  `action_url` VARCHAR(500) DEFAULT NULL COMMENT 'URL de l\'action liée',
  `icon`       VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`, `is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Abonnements push notifications
CREATE TABLE `push_subscriptions` (
  `id`         INT(11)  NOT NULL AUTO_INCREMENT,
  `user_id`    INT(11)  NOT NULL,
  `endpoint`   TEXT     NOT NULL,
  `p256dh`     TEXT     DEFAULT NULL,
  `auth`       TEXT     DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_push_user` (`user_id`),
  CONSTRAINT `fk_push_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- FACTURES & PAIEMENTS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `invoices` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `order_id`       INT(11)      NOT NULL,
  `invoice_number` VARCHAR(30)  NOT NULL,
  `pdf_url`        VARCHAR(500) NOT NULL,
  `qr_code_url`    VARCHAR(500) DEFAULT NULL,
  `amount_ht`      DECIMAL(10,2) NOT NULL,
  `vat`            DECIMAL(10,2) NOT NULL,
  `amount_ttc`     DECIMAL(10,2) NOT NULL,
  `issued_at`      DATETIME     NOT NULL DEFAULT current_timestamp(),
  `due_date`       DATE         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoice_number` (`invoice_number`),
  UNIQUE KEY `uq_invoice_order` (`order_id`),
  CONSTRAINT `fk_invoice_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- WALLET & TRANSACTIONS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `wallets` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `user_id`        INT(11)       NOT NULL,
  `balance`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`       VARCHAR(3)    NOT NULL DEFAULT 'XOF',
  `status`         ENUM('ACTIVE','BLOCKED','SUSPENDED') NOT NULL DEFAULT 'ACTIVE',
  `created_at`     DATETIME      NOT NULL DEFAULT current_timestamp(),
  `last_operation` DATETIME      DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wallet_user` (`user_id`),
  CONSTRAINT `fk_wallet_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `transactions` (
  `id`             INT(11)       NOT NULL AUTO_INCREMENT,
  `wallet_id`      INT(11)       NOT NULL,
  `order_id`       INT(11)       DEFAULT NULL,
  `type`           ENUM('CREDIT','DEBIT','REFUND','CANCELLATION') NOT NULL,
  `amount`         DECIMAL(10,2) NOT NULL,
  `balance_before` DECIMAL(10,2) NOT NULL,
  `balance_after`  DECIMAL(10,2) NOT NULL,
  `status`         ENUM('PENDING','VALIDATED','FAILED','SUSPECTED') NOT NULL DEFAULT 'PENDING',
  `external_ref`   VARCHAR(255)  DEFAULT NULL,
  `ip_address`     VARCHAR(45)   DEFAULT NULL,
  `description`    TEXT          DEFAULT NULL,
  `created_at`     DATETIME      NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trans_wallet` (`wallet_id`),
  KEY `idx_trans_status` (`status`),
  CONSTRAINT `fk_trans_wallet` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- ANTI-FRAUDE
-- ─────────────────────────────────────────────────────────────

CREATE TABLE `fraud_alerts` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `transaction_id` INT(11)      DEFAULT NULL,
  `order_id`       INT(11)      DEFAULT NULL,
  `user_id`        INT(11)      DEFAULT NULL,
  `anomaly_type`   VARCHAR(100) NOT NULL,
  `risk_score`     INT(11)      NOT NULL,
  `description`    TEXT         DEFAULT NULL,
  `status`         ENUM('OPEN','IN_PROGRESS','RESOLVED','FALSE_POSITIVE') NOT NULL DEFAULT 'OPEN',
  `action_taken`   TEXT         DEFAULT NULL,
  `detected_at`    DATETIME     NOT NULL DEFAULT current_timestamp(),
  `resolved_at`    DATETIME     DEFAULT NULL,
  `ip_address`     VARCHAR(45)  DEFAULT NULL,
  `user_agent`     TEXT         DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_fraud_transaction` (`transaction_id`),
  KEY `fk_fraud_order` (`order_id`),
  KEY `fk_fraud_user` (`user_id`),
  CONSTRAINT `fk_fraud_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fraud_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_fraud_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tentatives de connexion suspectes
CREATE TABLE `login_attempts` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `email`        VARCHAR(150) NOT NULL,
  `ip_address`   VARCHAR(45)  NOT NULL,
  `user_agent`   TEXT         DEFAULT NULL,
  `success`      TINYINT(1)   NOT NULL DEFAULT 0,
  `attempted_at` DATETIME     NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_la_email` (`email`),
  KEY `idx_la_ip` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- GESTION STOCKS
-- ─────────────────────────────────────────────────────────────

-- Alertes de stock pour l'admin
CREATE TABLE `stock_alerts` (
  `id`            INT(11)      NOT NULL AUTO_INCREMENT,
  `product_id`    INT(11)      NOT NULL,
  `current_stock` INT(11)      NOT NULL,
  `threshold`     INT(11)      NOT NULL,
  `alert_type`    ENUM('LOW_STOCK','OUT_OF_STOCK','REORDER_NEEDED') NOT NULL,
  `acknowledged`  TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`    DATETIME     NOT NULL DEFAULT current_timestamp(),
  `acked_at`      DATETIME     DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sa_product` (`product_id`),
  CONSTRAINT `fk_sa_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Prévision des ventes (analyse historique)
CREATE TABLE `sales_forecast` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `product_id`       INT(11) NOT NULL,
  `forecast_date`    DATE    NOT NULL,
  `predicted_sales`  INT(11) NOT NULL DEFAULT 0,
  `confidence_pct`   DECIMAL(5,2) DEFAULT NULL,
  `actual_sales`     INT(11) DEFAULT NULL COMMENT 'Rempli a posteriori',
  `generated_at`     DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sf_product` (`product_id`),
  CONSTRAINT `fk_sf_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- DONNÉES DE DÉMO (insertion initiale)
-- ─────────────────────────────────────────────────────────────

-- Admin système
INSERT INTO `users` (`id`,`name`,`first_name`,`email`,`password`,`role`,`status`,`email_verified`,`preferred_lang`,`rgpd_consent`,`rgpd_date`,`dark_mode`)
VALUES (1,'Admin','Système','admin@sneakx.com','$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewY5OfmWjSKPJeO6','ADMIN','ACTIVE',1,'fr',1,NOW(),1);

-- Catégories
INSERT INTO `categories` (`id`,`name`,`name_en`,`description`,`slug`,`active`) VALUES
(1,'Sneakers','Sneakers','Toutes nos sneakers originales','sneakers',1),
(2,'Running','Running','Chaussures de course et sport','running',1),
(3,'Lifestyle','Lifestyle','Mode urbaine et casual','lifestyle',1),
(4,'Accessoires','Accessories','Lacets, chaussettes et entretien','accessoires',1);

-- Produits de démo
INSERT INTO `products` (`id`,`name`,`name_en`,`short_description`,`price`,`promo_price`,`stock`,`stock_alert`,`sku`,`category_id`,`status`,`popularity_score`) VALUES
(1,'Air Max 270 React','Air Max 270 React','Confort exceptionnel avec coussin Air React','89500','79500',15,5,'SNX-001',1,'ACTIVE',150),
(2,'Ultra Boost 22','Ultra Boost 22','Énergie maximale pour vos foulées','125000',NULL,8,5,'SNX-002',2,'ACTIVE',120),
(3,'Jordan 1 Retro High','Jordan 1 Retro High','L''icône absolue du basketball','175000','159000',3,3,'SNX-003',1,'ACTIVE',200),
(4,'New Balance 574','New Balance 574','Le classique intemporel revisité','67500',NULL,20,5,'SNX-004',3,'ACTIVE',90),
(5,'Vans Old Skool','Vans Old Skool','Le skateboard streetwear original','45000','39000',30,10,'SNX-005',3,'ACTIVE',180),
(6,'Converse Chuck Taylor','Converse Chuck Taylor','L''authentique sneaker toile','38500',NULL,25,8,'SNX-006',3,'ACTIVE',160),
(7,'Lacets Oval Plats Blanc','Flat White Laces','Lacets plats ovales blancs premium','2500',NULL,100,20,'ACC-001',4,'ACTIVE',50),
(8,'Kit Nettoyage Sneakers','Sneaker Cleaning Kit','Kit complet entretien et nettoyage','8500','7500',50,10,'ACC-002',4,'ACTIVE',75);

-- Wallet admin
INSERT INTO `wallets` (`user_id`,`balance`,`currency`,`status`) VALUES (1, 0.00, 'XOF', 'ACTIVE');

-- Accessibilité admin
INSERT INTO `accessibility_profiles` (`user_id`) VALUES (1);

-- Promotions avec votes
INSERT INTO `promotions` (`id`,`code`,`title`,`description`,`type`,`value`,`votes_count`,`activation_threshold`,`active`,`community_promo`,`end_date`) VALUES
(1,'SUMMER20','Promo Été -20%','Votez pour activer une réduction de 20% sur toute la boutique !','PERCENTAGE',20,45,100,0,1,DATE_ADD(NOW(), INTERVAL 30 DAY)),
(2,'FREESHIP','Livraison Gratuite','Activez la livraison gratuite pour tous !','FREE_SHIPPING',0,78,100,0,1,DATE_ADD(NOW(), INTERVAL 15 DAY));

COMMIT;
