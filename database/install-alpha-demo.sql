-- NEW EMPTY DATABASE ONLY. Never import over an existing shop.
CREATE TABLE IF NOT EXISTS categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, slug VARCHAR(170) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS products (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT UNSIGNED NULL, sku VARCHAR(80) NOT NULL UNIQUE, name VARCHAR(200) NOT NULL, description TEXT NULL, price DECIMAL(12,2) NOT NULL, stock INT UNSIGNED NOT NULL DEFAULT 0, image_url VARCHAR(500) NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS orders (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, customer_name VARCHAR(180) NOT NULL, email VARCHAR(254) NOT NULL, phone VARCHAR(60) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(150) NOT NULL, note TEXT NULL, total DECIMAL(12,2) NOT NULL, status VARCHAR(30) NOT NULL DEFAULT 'new', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS order_items (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, product_id INT UNSIGNED NULL, product_name VARCHAR(200) NOT NULL, quantity INT UNSIGNED NOT NULL, unit_price DECIMAL(12,2) NOT NULL, FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE, FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO categories (name,slug) VALUES ('Općenito','opcenito') ON DUPLICATE KEY UPDATE name=VALUES(name);


-- Run ONCE after v0.1 schema, on a backed-up staging database first.
ALTER TABLE products ADD featured TINYINT(1) NOT NULL DEFAULT 1, ADD reserved INT UNSIGNED NOT NULL DEFAULT 0;
ALTER TABLE orders ADD shipping_method VARCHAR(150) NOT NULL DEFAULT 'Dostava', ADD shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0, ADD payment_method VARCHAR(30) NOT NULL DEFAULT 'cod', ADD payment_status VARCHAR(30) NOT NULL DEFAULT 'unpaid', ADD tracking_number VARCHAR(150) NULL, ADD inventory_state VARCHAR(20) NOT NULL DEFAULT 'legacy', ADD checkout_token CHAR(64) NULL UNIQUE, ADD updated_at TIMESTAMP NULL;
CREATE TABLE roles (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, permissions TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, role_id INT UNSIGNED NOT NULL, name VARCHAR(150) NOT NULL, email VARCHAR(254) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(role_id) REFERENCES roles(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE shipping_methods (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, cost DECIMAL(12,2) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE settings (setting_key VARCHAR(80) PRIMARY KEY, setting_value TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE stock_movements (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, order_id BIGINT UNSIGNED NULL, user_id INT UNSIGNED NULL, quantity INT NOT NULL, kind VARCHAR(40) NOT NULL, note VARCHAR(500) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(product_id,created_at), FOREIGN KEY(product_id) REFERENCES products(id), FOREIGN KEY(order_id) REFERENCES orders(id), FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE audit_log (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL, action VARCHAR(80) NOT NULL, entity_id BIGINT UNSIGNED NULL, details TEXT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE notifications (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, message VARCHAR(255) NOT NULL, emailed_at TIMESTAMP NULL, email_attempts INT UNSIGNED NOT NULL DEFAULT 0, last_email_attempt_at TIMESTAMP NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(order_id) REFERENCES orders(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE login_attempts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identity_hash CHAR(64) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(identity_hash,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE request_limits (identity_hash CHAR(64) PRIMARY KEY, window_started BIGINT UNSIGNED NOT NULL, attempts INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=InnoDB;
INSERT INTO roles(name,permissions) VALUES ('Super administrator','["*"]'),('Prodaja','["orders","shipping","print"]'),('Lager','["products","categories","inventory","print"]'),('Finansije','["orders","payments","print"]');
INSERT INTO shipping_methods(name,cost) VALUES ('Brza pošta',10.00);
INSERT INTO settings VALUES ('shop_name','WEBSHOP'),('hero_title','Sve što trebate, na jednom mjestu.'),('hero_text','Odaberite proizvode iz našeg lagera. Dostava na vašu adresu.'),('accent_color','#167655'),('contact_email',''),('sender_name',''),('sender_address',''),('sender_phone',''),('bank_details',''),('privacy_text','Podatke koristimo za obradu narudžbe i dostavu. Za pitanja obratite se prodavcu.');
CREATE TABLE schema_migrations (version VARCHAR(80) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
INSERT INTO schema_migrations(version) VALUES ('002_management');

-- Apply ONCE after 002_management. Back up the database first.
ALTER TABLE products ADD hero_selected TINYINT(1) NOT NULL DEFAULT 0, ADD purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0, ADD low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 3, ADD INDEX idx_hero (active,hero_selected,created_at,id);
ALTER TABLE categories ADD show_on_home TINYINT(1) NOT NULL DEFAULT 1, ADD sort_order INT NOT NULL DEFAULT 0;
CREATE TABLE product_images (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, image_url VARCHAR(500) NOT NULL, sort_order INT NOT NULL DEFAULT 0, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE, INDEX(product_id,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE stock_movements ADD available_after INT UNSIGNED NULL, ADD reserved_after INT UNSIGNED NULL, ADD unit_cost DECIMAL(12,2) NULL;
INSERT INTO schema_migrations(version) VALUES ('003_storefront');

-- Apply ONCE after 003_storefront, after backup and staging verification.
-- Existing order statuses, stock quantities and payment records are preserved.
ALTER TABLE orders MODIFY status VARCHAR(30) NOT NULL DEFAULT 'preparing', ADD courier_name VARCHAR(150) NULL, ADD dispatch_method VARCHAR(30) NULL, ADD dispatched_at TIMESTAMP NULL, ADD dispatch_note VARCHAR(1000) NULL;
CREATE TABLE stock_exits (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id INT UNSIGNED NOT NULL,
 product_name VARCHAR(200) NOT NULL,
 sku VARCHAR(80) NOT NULL,
 quantity INT UNSIGNED NOT NULL,
 reason VARCHAR(30) NOT NULL,
 unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
 total DECIMAL(12,2) NOT NULL DEFAULT 0,
 payment_status VARCHAR(30) NOT NULL DEFAULT 'not_applicable',
 recipient VARCHAR(180) NOT NULL DEFAULT '',
 document_ref VARCHAR(150) NOT NULL DEFAULT '',
 note VARCHAR(1000) NOT NULL,
 user_id INT UNSIGNED NOT NULL,
 idempotency_key CHAR(64) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 reversed_at TIMESTAMP NULL,
 reversal_note VARCHAR(1000) NULL,
 reversal_user_id INT UNSIGNED NULL,
 FOREIGN KEY(product_id) REFERENCES products(id),
 FOREIGN KEY(user_id) REFERENCES users(id),
 FOREIGN KEY(reversal_user_id) REFERENCES users(id),
 INDEX(reason,created_at), INDEX(product_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE stock_movements ADD stock_exit_id BIGINT UNSIGNED NULL, ADD CONSTRAINT fk_movement_exit FOREIGN KEY(stock_exit_id) REFERENCES stock_exits(id);
INSERT INTO schema_migrations(version) VALUES ('004_fulfillment');

-- Apply once after 004_fulfillment. Existing images and hero selection are preserved.
ALTER TABLE products ADD hero_banner_url VARCHAR(500) NOT NULL DEFAULT '';
INSERT INTO schema_migrations(version) VALUES ('005_hero_banner');

-- Apply once after 005_hero_banner. Existing orders and customer totals remain unchanged.
ALTER TABLE shipping_methods ADD courier_name VARCHAR(150) NOT NULL DEFAULT '', ADD terms VARCHAR(1000) NOT NULL DEFAULT '', ADD payer VARCHAR(20) NOT NULL DEFAULT 'customer', ADD deleted_at TIMESTAMP NULL;
ALTER TABLE orders ADD shipping_terms VARCHAR(1000) NULL, ADD shipping_payer VARCHAR(20) NULL, ADD planned_delivery_id INT UNSIGNED NULL, ADD planned_delivery_name VARCHAR(150) NULL, ADD planned_delivery_cost DECIMAL(12,2) NULL, ADD planned_delivery_terms VARCHAR(1000) NULL, ADD planned_delivery_payer VARCHAR(20) NULL;
INSERT INTO schema_migrations(version) VALUES ('006_delivery');

-- Apply once after 006_delivery. Existing prices, orders and inventory remain unchanged.
ALTER TABLE products ADD compare_price DECIMAL(12,2) NULL;
CREATE TABLE mail_jobs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_id BIGINT UNSIGNED NULL,
 recipient VARCHAR(254) NOT NULL,
 subject VARCHAR(255) NOT NULL,
 body TEXT NOT NULL,
 status VARCHAR(20) NOT NULL DEFAULT 'pending',
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 next_attempt_at TIMESTAMP NULL,
 sent_at TIMESTAMP NULL,
 last_error VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE(order_id,recipient), INDEX(status,next_attempt_at),
 FOREIGN KEY(order_id) REFERENCES orders(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO settings VALUES ('mail_configured','0'),('mail_enabled','0'),('mail_transport','smtp'),('mail_host',''),('mail_port','587'),('mail_security','tls'),('mail_username',''),('mail_password',''),('mail_from',''),('mail_from_name',''),('mail_recipients',''),('mail_base_url',''),('mail_immediate','1');
INSERT INTO schema_migrations(version) VALUES ('007_mail_discounts');

-- Apply once after 007_mail_discounts. Existing SKUs, order data and prices are preserved.
CREATE TABLE app_counters (counter_key VARCHAR(80) PRIMARY KEY, next_value BIGINT UNSIGNED NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO app_counters VALUES ('product_sku',1);
INSERT INTO settings VALUES ('store_font_scale','115'),('admin_font_scale','120');
INSERT INTO schema_migrations(version) VALUES ('008_readability_numbering');

-- Apply once after 008_readability_numbering. Existing audit records are preserved.
INSERT INTO settings VALUES ('admin_theme','emerald'),('backup_enabled','0'),('backup_hour','03:00'),('backup_keep','14');
INSERT INTO schema_migrations(version) VALUES ('009_admin_backups');

-- Alpha themes do not alter products, orders, stock or existing theme selections.
INSERT IGNORE INTO settings(setting_key,setting_value) VALUES ('store_theme','alpha-hybrid'),('brand_slogan','Za promjenu, probajte kvalitet!'),('alpha_demo_notice','0');
UPDATE settings SET setting_value='ALPHA refinish' WHERE setting_key='shop_name' AND setting_value='WEBSHOP';
UPDATE settings SET setting_value='#008c4c' WHERE setting_key='accent_color' AND setting_value='#167655';
INSERT IGNORE INTO schema_migrations(version) VALUES ('010_alpha_themes');

-- Optional demo only. Rerunning inserts missing SKUs without resetting edited prices or stock.
SET NAMES utf8mb4;
INSERT INTO categories(name,slug,show_on_home,sort_order) SELECT 'Boje i lakovi','boje-i-lakovi',1,0 WHERE NOT EXISTS(SELECT 1 FROM categories WHERE slug='boje-i-lakovi');
INSERT INTO categories(name,slug,show_on_home,sort_order) SELECT 'Priprema površine','priprema-povrsine',1,1 WHERE NOT EXISTS(SELECT 1 FROM categories WHERE slug='priprema-povrsine');
INSERT INTO categories(name,slug,show_on_home,sort_order) SELECT 'Brušenje','brusenje',1,2 WHERE NOT EXISTS(SELECT 1 FROM categories WHERE slug='brusenje');
INSERT INTO categories(name,slug,show_on_home,sort_order) SELECT 'Poliranje','poliranje',1,3 WHERE NOT EXISTS(SELECT 1 FROM categories WHERE slug='poliranje');
INSERT INTO categories(name,slug,show_on_home,sort_order) SELECT 'Detailing','detailing',1,4 WHERE NOT EXISTS(SELECT 1 FROM categories WHERE slug='detailing');
INSERT INTO categories(name,slug,show_on_home,sort_order) SELECT 'Alati i oprema','oprema',1,5 WHERE NOT EXISTS(SELECT 1 FROM categories WHERE slug='oprema');
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='poliranje'),'ALPHA-DEMO-001','MAXXT ekscentrična polirka 15 mm','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Naziv i referentna cijena po uzoru na Alpha Refinish MAXXT ponudu. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',300,10,'assets/alpha/polisher.svg',1,1,1 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-001');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-001' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='poliranje'),'ALPHA-DEMO-002','MAXXT rotaciona polirka','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Naziv i referentna cijena po uzoru na Alpha Refinish MAXXT ponudu. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',330,11,'assets/alpha/polisher.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-002');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-002' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='brusenje'),'ALPHA-DEMO-003','MAXXT ekscentrična šlajfarica','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Naziv i referentna cijena po uzoru na Alpha Refinish MAXXT ponudu. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',400,12,'assets/alpha/sander.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-003');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-003' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='brusenje'),'ALPHA-DEMO-004','MAXXT ekscentrična šlajfarica 5,0 mm','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Naziv i referentna cijena po uzoru na Alpha Refinish MAXXT ponudu. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',600,13,'assets/alpha/sander.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-004');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-004' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='brusenje'),'ALPHA-DEMO-005','MAXXT ekscentrična rašpa 198 × 70 mm, 3,0 mm','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Naziv i referentna cijena po uzoru na Alpha Refinish MAXXT ponudu. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',600,14,'assets/alpha/sander.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-005');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-005' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='oprema'),'ALPHA-DEMO-006','MAXXT usisivač','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Naziv i referentna cijena po uzoru na Alpha Refinish MAXXT ponudu. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',750,0,'assets/alpha/vacuum.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-006');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-006' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='boje-i-lakovi'),'ALPHA-DEMO-007','Bezbojni lak HS 2K – testni artikal','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Primjer artikla prema kategorijama Alpha Refinish ponude. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',49,16,'assets/alpha/can.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-007');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-007' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='priprema-povrsine'),'ALPHA-DEMO-008','Primer za pripremu površine – testni artikal','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Primjer artikla prema kategorijama Alpha Refinish ponude. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',35,17,'assets/alpha/primer.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-008');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-008' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='brusenje'),'ALPHA-DEMO-009','Brusni disk P320 Ø 150 mm – testni artikal','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Primjer artikla prema kategorijama Alpha Refinish ponude. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',1.5,18,'assets/alpha/disc.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-009');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-009' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='poliranje'),'ALPHA-DEMO-010','Pasta za poliranje – testni artikal','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Primjer artikla prema kategorijama Alpha Refinish ponude. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',39,19,'assets/alpha/bottle.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-010');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-010' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='detailing'),'ALPHA-DEMO-011','Mikrofiber krpa – testni artikal','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Primjer artikla prema kategorijama Alpha Refinish ponude. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',8,20,'assets/alpha/cloth.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-011');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-011' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO products(category_id,sku,name,description,price,stock,image_url,active,featured,hero_selected) SELECT (SELECT id FROM categories WHERE slug='detailing'),'ALPHA-DEMO-012','Sredstvo za pranje vozila – testni artikal','TESTNI ARTIKAL — probni podaci za provjeru izgleda i naručivanja. Cijena i stanje nisu potvrđena prodajna ponuda. Primjer artikla prema kategorijama Alpha Refinish ponude. Slika je ilustracija, a ne fotografija stvarnog proizvoda.',19,21,'assets/alpha/bottle.svg',1,1,0 WHERE NOT EXISTS(SELECT 1 FROM products WHERE sku='ALPHA-DEMO-012');
INSERT INTO stock_movements(product_id,quantity,kind,note,available_after,reserved_after,unit_cost) SELECT id,stock,'adjustment','Početno testno stanje Alpha demo',stock,reserved,0 FROM products WHERE sku='ALPHA-DEMO-012' AND NOT EXISTS(SELECT 1 FROM stock_movements m WHERE m.product_id=products.id);
INSERT INTO settings(setting_key,setting_value) VALUES('alpha_demo_notice','1') ON DUPLICATE KEY UPDATE setting_value='1';

-- Update bundled demo photographs only. Preserve custom photos, all prices, stock and orders.
SET NAMES utf8mb4;
UPDATE products SET image_url='assets/alpha/maxxt-polisher.png' WHERE sku='ALPHA-DEMO-001' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/maxxt-rotary.png' WHERE sku='ALPHA-DEMO-002' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/maxxt-sander.png' WHERE sku='ALPHA-DEMO-003' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/maxxt-sander.png' WHERE sku='ALPHA-DEMO-004' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/maxxt-sander.png' WHERE sku='ALPHA-DEMO-005' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/maxxt-vacuum.png' WHERE sku='ALPHA-DEMO-006' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/clearcoat.jpg', name=CASE WHEN name LIKE '%testni artikal%' THEN 'Mipa C UV lak 1 kg — testna ponuda' ELSE name END WHERE sku='ALPHA-DEMO-007' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/cleaner.jpg', name=CASE WHEN name LIKE '%testni artikal%' THEN 'APC Typhoon 0,5 L — testna ponuda' ELSE name END WHERE sku='ALPHA-DEMO-008' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/pads.jpg', category_id=(SELECT id FROM categories WHERE slug='poliranje' LIMIT 1), name=CASE WHEN name LIKE '%testni artikal%' THEN 'ChemicalWorkz Pad Box 75 mm, 6/1 — testna ponuda' ELSE name END WHERE sku='ALPHA-DEMO-009' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/polish.webp', name=CASE WHEN name LIKE '%testni artikal%' THEN 'P&S Therapy Final Polish 236 ml — testna ponuda' ELSE name END WHERE sku='ALPHA-DEMO-010' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/microfiber.jpg', name=CASE WHEN name LIKE '%testni artikal%' THEN 'EWOCAR mikrofiber krpa 400 GSM — testna ponuda' ELSE name END WHERE sku='ALPHA-DEMO-011' AND image_url LIKE 'assets/alpha/%.svg';
UPDATE products SET image_url='assets/alpha/shampoo.jpg', name=CASE WHEN name LIKE '%testni artikal%' THEN '3D Wash N Wax 3,8 L — testna ponuda' ELSE name END WHERE sku='ALPHA-DEMO-012' AND image_url LIKE 'assets/alpha/%.svg';
INSERT IGNORE INTO schema_migrations(version) VALUES ('011_alpha_photos');

-- Apply to an existing Alpha database after making a backup. Safe to repeat.
-- Does not modify prices, inventory, orders or uploaded product photos.
CREATE TABLE IF NOT EXISTS brands (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 slug VARCHAR(170) NOT NULL UNIQUE,
 logo_url VARCHAR(500) NOT NULL DEFAULT '',
 website VARCHAR(500) NOT NULL DEFAULT '',
 logo_dark TINYINT(1) NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 show_on_home TINYINT(1) NOT NULL DEFAULT 1,
 sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='brand_id')=0,'ALTER TABLE products ADD brand_id INT UNSIGNED NULL','SET @alpha_noop=1');
PREPARE alpha_stmt FROM @alpha_sql;
EXECUTE alpha_stmt;
DEALLOCATE PREPARE alpha_stmt;
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='hero_selected_at')=0,'ALTER TABLE products ADD hero_selected_at DATETIME NULL','SET @alpha_noop=1');
PREPARE alpha_stmt FROM @alpha_sql;
EXECUTE alpha_stmt;
DEALLOCATE PREPARE alpha_stmt;
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND INDEX_NAME='idx_products_brand')=0,'ALTER TABLE products ADD INDEX idx_products_brand(brand_id)','SET @alpha_noop=1');
PREPARE alpha_stmt FROM @alpha_sql;
EXECUTE alpha_stmt;
DEALLOCATE PREPARE alpha_stmt;
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='products' AND CONSTRAINT_NAME='fk_products_brand')=0,'ALTER TABLE products ADD CONSTRAINT fk_products_brand FOREIGN KEY(brand_id) REFERENCES brands(id) ON DELETE SET NULL','SET @alpha_noop=1');
PREPARE alpha_stmt FROM @alpha_sql;
EXECUTE alpha_stmt;
DEALLOCATE PREPARE alpha_stmt;
INSERT INTO brands(name,slug,logo_url,website,sort_order) VALUES
('MAXXT','maxxt','assets/alpha/brand-maxxt.png','https://maxxttools.com/',10),
('Mipa','mipa','assets/alpha/brand-mipa.jpg','https://www.mipa-paints.com/',20),
('ChemicalWorkz','chemicalworkz','assets/alpha/brand-chemicalworkz.png','https://www.chemicalworkz.de/',30),
('P&S','ps','assets/alpha/brand-ps.png','https://psdetailproducts.com/',40),
('EWOCAR','ewocar','assets/alpha/brand-ewocar.png','https://ewocar.com/',50),
('3D','3d','assets/alpha/brand-3d.png','https://3dproducts.com/',60)
ON DUPLICATE KEY UPDATE slug=VALUES(slug);
UPDATE brands SET logo_dark=1 WHERE slug IN ('maxxt','ewocar') AND NOT EXISTS(SELECT 1 FROM schema_migrations WHERE version='012_brands_slider');
UPDATE products p JOIN brands b ON (p.name LIKE CONCAT(b.name,' %') OR p.name=b.name) SET p.brand_id=b.id WHERE p.brand_id IS NULL AND NOT EXISTS(SELECT 1 FROM schema_migrations WHERE version='012_brands_slider');
UPDATE products SET hero_selected_at=created_at WHERE hero_selected=1 AND hero_selected_at IS NULL AND NOT EXISTS(SELECT 1 FROM schema_migrations WHERE version='012_brands_slider');
INSERT IGNORE INTO settings(setting_key,setting_value) VALUES ('slider_count','3'),('slider_interval','6'),('slider_effect','fade'),('slider_duration','500'),('slider_autoplay','1'),('slider_hover_pause','1');
INSERT IGNORE INTO schema_migrations(version) VALUES ('012_brands_slider');
