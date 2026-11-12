-- =====================================================================
-- KopiKita – Coffee Shop Management System
-- "A Little Coffee, A Lot of Happiness."
-- Database : kopikita_db  (MySQL / MariaDB, InnoDB, utf8mb4)
-- Sumber   : docs/ERD.md (9 tabel)
--
-- Import lewat phpMyAdmin (tab Import) atau:
--   C:\xampp\mysql\bin\mysql.exe -u root < database/kopikita_db.sql
--
-- Catatan: tanggal pesanan contoh dibuat RELATIF terhadap tanggal import
-- (CURDATE() - INTERVAL n DAY) agar dashboard & laporan selalu berisi data.
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+08:00';
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `kopikita_db`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kopikita_db`;

DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `order_details`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `gallery_images`;
DROP TABLE IF EXISTS `newsletter_subscribers`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 3.1 users
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `username`      VARCHAR(50)  NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','cashier','customer') NOT NULL DEFAULT 'customer',
  `avatar`        VARCHAR(255) NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `last_login_at` DATETIME     NULL,
  `created_at`    DATETIME     NOT NULL,
  `updated_at`    DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.2 customers
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    BIGINT UNSIGNED NULL,
  `name`       VARCHAR(100) NOT NULL,
  `phone`      VARCHAR(25)  NULL,
  `email`      VARCHAR(150) NULL,
  `address`    TEXT         NULL,
  `created_at` DATETIME     NOT NULL,
  `updated_at` DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_user` (`user_id`),
  KEY `idx_customers_name` (`name`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.3 categories
-- ---------------------------------------------------------------------
CREATE TABLE `categories` (
  `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80) NOT NULL,
  `slug`        VARCHAR(90) NOT NULL,
  `description` TEXT        NULL,
  `icon`        VARCHAR(50) NULL,
  `sort_order`  INT         NOT NULL DEFAULT 0,
  `is_active`   TINYINT(1)  NOT NULL DEFAULT 1,
  `created_at`  DATETIME    NOT NULL,
  `updated_at`  DATETIME    NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_name` (`name`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.4 products
-- ---------------------------------------------------------------------
CREATE TABLE `products` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`     BIGINT UNSIGNED NOT NULL,
  `sku`             VARCHAR(40)  NOT NULL,
  `name`            VARCHAR(120) NOT NULL,
  `slug`            VARCHAR(140) NOT NULL,
  `description`     TEXT         NULL,
  `price`           DECIMAL(12,2) NOT NULL,
  `stock`           INT UNSIGNED NOT NULL DEFAULT 0,
  `min_stock`       INT UNSIGNED NOT NULL DEFAULT 10,
  `image`           VARCHAR(255) NULL,
  `has_size_option` TINYINT(1)   NOT NULL DEFAULT 1,
  `is_best_seller`  TINYINT(1)   NOT NULL DEFAULT 0,
  `is_available`    TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`      DATETIME     NOT NULL,
  `updated_at`      DATETIME     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_sku` (`sku`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_available` (`is_available`),
  KEY `idx_products_best` (`is_best_seller`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.5 orders
-- ---------------------------------------------------------------------
CREATE TABLE `orders` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code`      VARCHAR(30) NOT NULL,
  `customer_id`     BIGINT UNSIGNED NOT NULL,
  `processed_by`    BIGINT UNSIGNED NULL,
  `order_channel`   ENUM('online','cashier') NOT NULL DEFAULT 'online',
  `order_type`      ENUM('dine_in','takeaway') NOT NULL,
  `table_number`    VARCHAR(10) NULL,
  `order_status`    ENUM('pending','confirmed','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal`        DECIMAL(12,2) NOT NULL,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_amount`    DECIMAL(12,2) NOT NULL,
  `notes`           TEXT NULL,
  `cancel_reason`   VARCHAR(255) NULL,
  `stock_deducted`  TINYINT(1) NOT NULL DEFAULT 0,
  `ordered_at`      DATETIME NOT NULL,
  `completed_at`    DATETIME NULL,
  `updated_at`      DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_orders_code` (`order_code`),
  KEY `idx_orders_customer` (`customer_id`),
  KEY `idx_orders_processed_by` (`processed_by`),
  KEY `idx_orders_status` (`order_status`),
  KEY `idx_orders_ordered_at` (`ordered_at`),
  KEY `idx_orders_channel` (`order_channel`),
  CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_orders_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.6 order_details
-- ---------------------------------------------------------------------
CREATE TABLE `order_details` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`     BIGINT UNSIGNED NOT NULL,
  `product_id`   BIGINT UNSIGNED NOT NULL,
  `product_name` VARCHAR(120) NOT NULL,
  `size`         ENUM('regular','large') NULL,
  `unit_price`   DECIMAL(12,2) NOT NULL,
  `quantity`     INT UNSIGNED NOT NULL,
  `line_total`   DECIMAL(12,2) NOT NULL,
  `item_notes`   TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_od_order` (`order_id`),
  KEY `idx_od_product` (`product_id`),
  CONSTRAINT `fk_od_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_od_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `chk_od_qty` CHECK (`quantity` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.7 sales
-- ---------------------------------------------------------------------
CREATE TABLE `sales` (
  `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`         BIGINT UNSIGNED NOT NULL,
  `invoice_number`   VARCHAR(30) NOT NULL,
  `cashier_id`       BIGINT UNSIGNED NULL,
  `payment_method`   ENUM('cash','qris','bank_transfer') NOT NULL,
  `amount_due`       DECIMAL(12,2) NOT NULL,
  `amount_paid`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  `change_amount`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  `payment_status`   ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `reference_number` VARCHAR(100) NULL,
  `paid_at`          DATETIME NULL,
  `created_at`       DATETIME NOT NULL,
  `updated_at`       DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_order` (`order_id`),
  UNIQUE KEY `uq_sales_invoice` (`invoice_number`),
  KEY `idx_sales_cashier` (`cashier_id`),
  KEY `idx_sales_status` (`payment_status`),
  CONSTRAINT `fk_sales_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_sales_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.8 gallery_images
-- ---------------------------------------------------------------------
CREATE TABLE `gallery_images` (
  `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`      VARCHAR(120) NOT NULL,
  `collection` ENUM('coffee','drinks','food','ambience','desserts') NOT NULL,
  `image`      VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gallery_collection` (`collection`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3.9 newsletter_subscribers
-- ---------------------------------------------------------------------
CREATE TABLE `newsletter_subscribers` (
  `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`         VARCHAR(150) NOT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `subscribed_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_newsletter_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATA CONTOH
-- =====================================================================

-- Akun contoh (password: admin123 / kasir123 / pelanggan123) – hash bcrypt
INSERT INTO `users` (`id`,`name`,`username`,`email`,`password`,`role`,`is_active`,`created_at`,`updated_at`) VALUES
(1,'Administrator KopiKita','admin','admin@kopikita.test','$2y$10$Yd2UHs7bpWdzHI8sTuZpYup6pAQtbUChAeslyY6ar5cC199NDX/V6','admin',1,NOW(),NOW()),
(2,'Kasir KopiKita','kasir','kasir@kopikita.test','$2y$10$FyYd1Ir0h9m7v0satLyKBeDI/yVIsCUjSJu...td79SfLCIO9bMvu','cashier',1,NOW(),NOW()),
(3,'Pelanggan Setia','pelanggan','pelanggan@kopikita.test','$2y$10$PkF6GhBy8x.ie6oSM0ZrJOHibnas9XaSZt4EswzVC5bwZiKd3Xqx.','customer',1,NOW(),NOW());

INSERT INTO `customers` (`id`,`user_id`,`name`,`phone`,`email`,`address`,`created_at`,`updated_at`) VALUES
(1,NULL,'Pelanggan Umum',NULL,NULL,NULL,NOW(),NOW()),
(2,3,'Pelanggan Setia','081234567890','pelanggan@kopikita.test','Jl. Contoh No. 1, Makassar',NOW(),NOW()),
(3,NULL,'Budi Santoso','081311112222',NULL,NULL,NOW(),NOW()),
(4,NULL,'Sari Dewi','085233334444','sari@contoh.test',NULL,NOW(),NOW());

INSERT INTO `categories` (`id`,`name`,`slug`,`description`,`icon`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES
(1,'Coffee','coffee','Kopi klasik berbasis espresso dan kopi susu lokal.','coffee',1,1,NOW(),NOW()),
(2,'Non-Coffee','non-coffee','Minuman tanpa kopi: matcha, cokelat, teh, dan lainnya.','leaf',2,1,NOW(),NOW()),
(3,'Food & Pastry','food-pastry','Pastry dan kudapan pendamping kopi.','croissant',3,1,NOW(),NOW()),
(4,'Signature Drinks','signature-drinks','Racikan khas KopiKita yang hanya ada di sini.','star',4,1,NOW(),NOW());

INSERT INTO `products` (`id`,`category_id`,`sku`,`name`,`slug`,`description`,`price`,`stock`,`min_stock`,`image`,`has_size_option`,`is_best_seller`,`is_available`,`created_at`,`updated_at`) VALUES
(1, 1,'CF-001','Espresso','espresso','Satu shot espresso pekat dari biji arabika lokal, aroma cokelat dan karamel.',18000,80,10,'espresso.svg',1,0,1,NOW(),NOW()),
(2, 1,'CF-002','Americano','americano','Espresso dengan air panas, ringan dan bersih di lidah.',20000,75,10,'americano.svg',1,0,1,NOW(),NOW()),
(3, 1,'CF-003','Cappuccino','cappuccino','Espresso, susu steamed, dan foam tebal yang lembut.',25000,60,10,'cappuccino.svg',1,1,1,NOW(),NOW()),
(4, 1,'CF-004','Caffè Latte','caffe-latte','Espresso dengan susu segar yang creamy dan latte art cantik.',25000,60,10,'caffe-latte.svg',1,0,1,NOW(),NOW()),
(5, 1,'CF-005','Kopi Susu Aren','kopi-susu-aren','Kopi susu kekinian dengan gula aren asli, manis legit.',20000,90,15,'kopi-susu-aren.svg',1,1,1,NOW(),NOW()),
(6, 2,'NC-001','Matcha Latte','matcha-latte','Matcha Jepang premium dengan susu segar.',27000,40,10,'matcha-latte.svg',1,1,1,NOW(),NOW()),
(7, 2,'NC-002','Chocolate','chocolate','Cokelat pekat hangat atau dingin, cocok untuk semua umur.',24000,45,10,'chocolate.svg',1,0,1,NOW(),NOW()),
(8, 2,'NC-003','Red Velvet','red-velvet','Red velvet latte manis dengan sentuhan cream cheese.',25000,30,10,'red-velvet.svg',1,0,1,NOW(),NOW()),
(9, 2,'NC-004','Lemon Tea','lemon-tea','Teh hitam segar dengan perasan lemon.',18000,6,10,'lemon-tea.svg',1,0,1,NOW(),NOW()),
(10,3,'FP-001','Croissant','croissant','Croissant butter renyah, dipanggang setiap pagi.',15000,35,8,'croissant.svg',0,0,1,NOW(),NOW()),
(11,3,'FP-002','Cheesecake','cheesecake','New York cheesecake lembut dengan saus berry.',28000,20,5,'cheesecake.svg',0,1,1,NOW(),NOW()),
(12,3,'FP-003','Tiramisu','tiramisu','Tiramisu klasik dengan espresso dan mascarpone.',30000,4,5,'tiramisu.svg',0,0,1,NOW(),NOW()),
(13,3,'FP-004','Banana Bread','banana-bread','Roti pisang moist dengan taburan kenari.',18000,25,8,'banana-bread.svg',0,0,1,NOW(),NOW()),
(14,4,'SG-001','KopiKita Caramel Cloud','kopikita-caramel-cloud','Cold brew dengan cream karamel selembut awan. Racikan khas KopiKita.',32000,50,10,'kopikita-caramel-cloud.svg',1,1,1,NOW(),NOW()),
(15,4,'SG-002','Orange Espresso Tonic','orange-espresso-tonic','Espresso, tonic water, dan jeruk segar yang menyegarkan.',30000,40,10,'orange-espresso-tonic.svg',1,1,1,NOW(),NOW()),
(16,4,'SG-003','Hazelnut Velvet Latte','hazelnut-velvet-latte','Latte hazelnut dengan tekstur susu velvet yang halus.',32000,35,10,'hazelnut-velvet-latte.svg',1,0,1,NOW(),NOW()),
(17,4,'SG-004','Coconut Cold Brew','coconut-cold-brew','Cold brew 12 jam dengan susu kelapa dan gula aren.',29000,30,10,'coconut-cold-brew.svg',1,0,1,NOW(),NOW());

INSERT INTO `gallery_images` (`title`,`collection`,`image`,`sort_order`,`is_active`,`created_at`,`updated_at`) VALUES
('Pour over pagi hari','coffee','coffee-1.svg',1,1,NOW(),NOW()),
('Biji kopi sangrai segar','coffee','coffee-2.svg',2,1,NOW(),NOW()),
('Latte art rosetta','coffee','coffee-3.svg',3,1,NOW(),NOW()),
('Espresso shot','coffee','coffee-4.svg',4,1,NOW(),NOW()),
('Iced matcha','drinks','drinks-1.svg',1,1,NOW(),NOW()),
('Orange tonic','drinks','drinks-2.svg',2,1,NOW(),NOW()),
('Cokelat dingin','drinks','drinks-3.svg',3,1,NOW(),NOW()),
('Lemon tea segar','drinks','drinks-4.svg',4,1,NOW(),NOW()),
('Croissant butter','food','food-1.svg',1,1,NOW(),NOW()),
('Banana bread','food','food-2.svg',2,1,NOW(),NOW()),
('Sandwich sarapan','food','food-3.svg',3,1,NOW(),NOW()),
('Sudut ruang baca','ambience','ambience-1.svg',1,1,NOW(),NOW()),
('Bar espresso','ambience','ambience-2.svg',2,1,NOW(),NOW()),
('Teras sore','ambience','ambience-3.svg',3,1,NOW(),NOW()),
('Lampu hangat malam','ambience','ambience-4.svg',4,1,NOW(),NOW()),
('Cheesecake berry','desserts','desserts-1.svg',1,1,NOW(),NOW()),
('Tiramisu klasik','desserts','desserts-2.svg',2,1,NOW(),NOW()),
('Cookies cokelat','desserts','desserts-3.svg',3,1,NOW(),NOW());

INSERT INTO `newsletter_subscribers` (`email`,`is_active`,`subscribed_at`) VALUES
('penikmat.kopi@contoh.test',1,NOW() - INTERVAL 5 DAY),
('barista.rumahan@contoh.test',1,NOW() - INTERVAL 2 DAY);

-- ---------------------------------------------------------------------
-- Pesanan contoh. Tanggal relatif terhadap hari import.
-- unit_price = price + tambahan ukuran (Large +5.000)
-- ---------------------------------------------------------------------
INSERT INTO `orders` (`id`,`order_code`,`customer_id`,`processed_by`,`order_channel`,`order_type`,`table_number`,`order_status`,`subtotal`,`discount_amount`,`total_amount`,`notes`,`cancel_reason`,`stock_deducted`,`ordered_at`,`completed_at`,`updated_at`) VALUES
(1, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 11 DAY,'%Y%m%d'),'-0001'),2,2,'online','dine_in','03','completed',65000,5000,60000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 11 DAY,'09:15:00'),TIMESTAMP(CURDATE()-INTERVAL 11 DAY,'09:40:00'),TIMESTAMP(CURDATE()-INTERVAL 11 DAY,'09:40:00')),
(2, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 10 DAY,'%Y%m%d'),'-0001'),1,2,'cashier','takeaway',NULL,'completed',53000,0,53000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 10 DAY,'10:05:00'),TIMESTAMP(CURDATE()-INTERVAL 10 DAY,'10:20:00'),TIMESTAMP(CURDATE()-INTERVAL 10 DAY,'10:20:00')),
(3, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 9 DAY,'%Y%m%d'),'-0001'),3,2,'cashier','dine_in','05','completed',64000,0,64000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 9 DAY,'14:30:00'),TIMESTAMP(CURDATE()-INTERVAL 9 DAY,'14:50:00'),TIMESTAMP(CURDATE()-INTERVAL 9 DAY,'14:50:00')),
(4, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 8 DAY,'%Y%m%d'),'-0001'),2,2,'online','takeaway',NULL,'completed',68000,0,68000,'Tolong dibungkus rapi',NULL,1,TIMESTAMP(CURDATE()-INTERVAL 8 DAY,'08:10:00'),TIMESTAMP(CURDATE()-INTERVAL 8 DAY,'08:35:00'),TIMESTAMP(CURDATE()-INTERVAL 8 DAY,'08:35:00')),
(5, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 7 DAY,'%Y%m%d'),'-0001'),4,2,'cashier','dine_in','01','completed',85000,0,85000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 7 DAY,'16:00:00'),TIMESTAMP(CURDATE()-INTERVAL 7 DAY,'16:25:00'),TIMESTAMP(CURDATE()-INTERVAL 7 DAY,'16:25:00')),
(6, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 6 DAY,'%Y%m%d'),'-0001'),1,2,'cashier','takeaway',NULL,'completed',60000,0,60000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 6 DAY,'11:45:00'),TIMESTAMP(CURDATE()-INTERVAL 6 DAY,'12:00:00'),TIMESTAMP(CURDATE()-INTERVAL 6 DAY,'12:00:00')),
(7, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 5 DAY,'%Y%m%d'),'-0001'),2,2,'online','dine_in','02','completed',67000,0,67000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 5 DAY,'19:20:00'),TIMESTAMP(CURDATE()-INTERVAL 5 DAY,'19:45:00'),TIMESTAMP(CURDATE()-INTERVAL 5 DAY,'19:45:00')),
(8, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 4 DAY,'%Y%m%d'),'-0001'),1,2,'cashier','takeaway',NULL,'completed',64000,0,64000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 4 DAY,'07:50:00'),TIMESTAMP(CURDATE()-INTERVAL 4 DAY,'08:05:00'),TIMESTAMP(CURDATE()-INTERVAL 4 DAY,'08:05:00')),
(9, CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 3 DAY,'%Y%m%d'),'-0001'),3,2,'cashier','dine_in','04','completed',88000,0,88000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'13:10:00'),TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'13:35:00'),TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'13:35:00')),
(10,CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 3 DAY,'%Y%m%d'),'-0002'),2,2,'online','takeaway',NULL,'cancelled',27000,0,27000,NULL,'Pelanggan membatalkan pesanan',0,TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'15:00:00'),NULL,TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'15:10:00')),
(11,CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 2 DAY,'%Y%m%d'),'-0001'),1,2,'cashier','takeaway',NULL,'completed',87000,0,87000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 2 DAY,'10:30:00'),TIMESTAMP(CURDATE()-INTERVAL 2 DAY,'10:45:00'),TIMESTAMP(CURDATE()-INTERVAL 2 DAY,'10:45:00')),
(12,CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 1 DAY,'%Y%m%d'),'-0001'),2,2,'online','takeaway',NULL,'completed',57000,0,57000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'09:00:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'09:30:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'09:30:00')),
(13,CONCAT('KK-',DATE_FORMAT(CURDATE()-INTERVAL 1 DAY,'%Y%m%d'),'-0002'),4,2,'cashier','dine_in','06','completed',43000,0,43000,NULL,NULL,1,TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'17:15:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'17:35:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'17:35:00')),
(14,CONCAT('KK-',DATE_FORMAT(CURDATE(),'%Y%m%d'),'-0001'),1,2,'cashier','takeaway',NULL,'preparing',32000,0,32000,NULL,NULL,1,TIMESTAMP(CURDATE(),'08:00:00'),NULL,TIMESTAMP(CURDATE(),'08:02:00')),
(15,CONCAT('KK-',DATE_FORMAT(CURDATE(),'%Y%m%d'),'-0002'),2,NULL,'online','dine_in','03','pending',40000,0,40000,'Meja dekat jendela ya',NULL,0,TIMESTAMP(CURDATE(),'08:30:00'),NULL,TIMESTAMP(CURDATE(),'08:30:00'));

INSERT INTO `order_details` (`order_id`,`product_id`,`product_name`,`size`,`unit_price`,`quantity`,`line_total`,`item_notes`) VALUES
(1, 5,'Kopi Susu Aren','large',25000,2,50000,'Less sugar'),
(1, 10,'Croissant',NULL,15000,1,15000,NULL),
(2, 3,'Cappuccino','regular',25000,1,25000,NULL),
(2, 11,'Cheesecake',NULL,28000,1,28000,NULL),
(3, 14,'KopiKita Caramel Cloud','regular',32000,2,64000,NULL),
(4, 6,'Matcha Latte','large',32000,1,32000,'Oat milk'),
(4, 13,'Banana Bread',NULL,18000,2,36000,NULL),
(5, 15,'Orange Espresso Tonic','regular',30000,1,30000,NULL),
(5, 4,'Caffè Latte','regular',25000,1,25000,'Extra shot'),
(5, 12,'Tiramisu',NULL,30000,1,30000,NULL),
(6, 5,'Kopi Susu Aren','regular',20000,3,60000,NULL),
(7, 14,'KopiKita Caramel Cloud','large',37000,1,37000,NULL),
(7, 10,'Croissant',NULL,15000,2,30000,NULL),
(8, 2,'Americano','regular',20000,2,40000,NULL),
(8, 7,'Chocolate','regular',24000,1,24000,'Hangat'),
(9, 3,'Cappuccino','large',30000,2,60000,NULL),
(9, 11,'Cheesecake',NULL,28000,1,28000,NULL),
(10,6,'Matcha Latte','regular',27000,1,27000,NULL),
(11,15,'Orange Espresso Tonic','regular',30000,2,60000,NULL),
(11,6,'Matcha Latte','regular',27000,1,27000,NULL),
(12,5,'Kopi Susu Aren','large',25000,1,25000,NULL),
(12,14,'KopiKita Caramel Cloud','regular',32000,1,32000,NULL),
(13,8,'Red Velvet','regular',25000,1,25000,NULL),
(13,13,'Banana Bread',NULL,18000,1,18000,NULL),
(14,14,'KopiKita Caramel Cloud','regular',32000,1,32000,NULL),
(15,3,'Cappuccino','regular',25000,1,25000,NULL),
(15,10,'Croissant',NULL,15000,1,15000,NULL);

INSERT INTO `sales` (`order_id`,`invoice_number`,`cashier_id`,`payment_method`,`amount_due`,`amount_paid`,`change_amount`,`payment_status`,`reference_number`,`paid_at`,`created_at`,`updated_at`) VALUES
(1, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 11 DAY,'%Y%m%d'),'-0001'),2,'cash',60000,100000,40000,'paid',NULL,TIMESTAMP(CURDATE()-INTERVAL 11 DAY,'09:20:00'),TIMESTAMP(CURDATE()-INTERVAL 11 DAY,'09:15:00'),TIMESTAMP(CURDATE()-INTERVAL 11 DAY,'09:20:00')),
(2, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 10 DAY,'%Y%m%d'),'-0001'),2,'qris',53000,53000,0,'paid','QR-SIM-100201',TIMESTAMP(CURDATE()-INTERVAL 10 DAY,'10:06:00'),TIMESTAMP(CURDATE()-INTERVAL 10 DAY,'10:05:00'),TIMESTAMP(CURDATE()-INTERVAL 10 DAY,'10:06:00')),
(3, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 9 DAY,'%Y%m%d'),'-0001'),2,'cash',64000,70000,6000,'paid',NULL,TIMESTAMP(CURDATE()-INTERVAL 9 DAY,'14:31:00'),TIMESTAMP(CURDATE()-INTERVAL 9 DAY,'14:30:00'),TIMESTAMP(CURDATE()-INTERVAL 9 DAY,'14:31:00')),
(4, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 8 DAY,'%Y%m%d'),'-0001'),2,'bank_transfer',68000,68000,0,'paid','TRF-SIM-880412',TIMESTAMP(CURDATE()-INTERVAL 8 DAY,'08:15:00'),TIMESTAMP(CURDATE()-INTERVAL 8 DAY,'08:10:00'),TIMESTAMP(CURDATE()-INTERVAL 8 DAY,'08:15:00')),
(5, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 7 DAY,'%Y%m%d'),'-0001'),2,'cash',85000,100000,15000,'paid',NULL,TIMESTAMP(CURDATE()-INTERVAL 7 DAY,'16:01:00'),TIMESTAMP(CURDATE()-INTERVAL 7 DAY,'16:00:00'),TIMESTAMP(CURDATE()-INTERVAL 7 DAY,'16:01:00')),
(6, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 6 DAY,'%Y%m%d'),'-0001'),2,'qris',60000,60000,0,'paid','QR-SIM-600611',TIMESTAMP(CURDATE()-INTERVAL 6 DAY,'11:46:00'),TIMESTAMP(CURDATE()-INTERVAL 6 DAY,'11:45:00'),TIMESTAMP(CURDATE()-INTERVAL 6 DAY,'11:46:00')),
(7, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 5 DAY,'%Y%m%d'),'-0001'),2,'cash',67000,70000,3000,'paid',NULL,TIMESTAMP(CURDATE()-INTERVAL 5 DAY,'19:25:00'),TIMESTAMP(CURDATE()-INTERVAL 5 DAY,'19:20:00'),TIMESTAMP(CURDATE()-INTERVAL 5 DAY,'19:25:00')),
(8, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 4 DAY,'%Y%m%d'),'-0001'),2,'cash',64000,64000,0,'paid',NULL,TIMESTAMP(CURDATE()-INTERVAL 4 DAY,'07:51:00'),TIMESTAMP(CURDATE()-INTERVAL 4 DAY,'07:50:00'),TIMESTAMP(CURDATE()-INTERVAL 4 DAY,'07:51:00')),
(9, CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 3 DAY,'%Y%m%d'),'-0001'),2,'qris',88000,88000,0,'paid','QR-SIM-880913',TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'13:11:00'),TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'13:10:00'),TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'13:11:00')),
(10,CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 3 DAY,'%Y%m%d'),'-0002'),NULL,'cash',27000,0,0,'failed',NULL,NULL,TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'15:00:00'),TIMESTAMP(CURDATE()-INTERVAL 3 DAY,'15:10:00')),
(11,CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 2 DAY,'%Y%m%d'),'-0001'),2,'cash',87000,100000,13000,'paid',NULL,TIMESTAMP(CURDATE()-INTERVAL 2 DAY,'10:31:00'),TIMESTAMP(CURDATE()-INTERVAL 2 DAY,'10:30:00'),TIMESTAMP(CURDATE()-INTERVAL 2 DAY,'10:31:00')),
(12,CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 1 DAY,'%Y%m%d'),'-0001'),2,'bank_transfer',57000,57000,0,'paid','TRF-SIM-571201',TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'09:05:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'09:00:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'09:05:00')),
(13,CONCAT('INV-',DATE_FORMAT(CURDATE()-INTERVAL 1 DAY,'%Y%m%d'),'-0002'),2,'cash',43000,50000,7000,'paid',NULL,TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'17:16:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'17:15:00'),TIMESTAMP(CURDATE()-INTERVAL 1 DAY,'17:16:00')),
(14,CONCAT('INV-',DATE_FORMAT(CURDATE(),'%Y%m%d'),'-0001'),2,'cash',32000,50000,18000,'paid',NULL,TIMESTAMP(CURDATE(),'08:01:00'),TIMESTAMP(CURDATE(),'08:00:00'),TIMESTAMP(CURDATE(),'08:01:00')),
(15,CONCAT('INV-',DATE_FORMAT(CURDATE(),'%Y%m%d'),'-0002'),NULL,'cash',40000,0,0,'pending',NULL,NULL,TIMESTAMP(CURDATE(),'08:30:00'),TIMESTAMP(CURDATE(),'08:30:00'));

-- Selesai.
