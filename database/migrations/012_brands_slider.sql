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
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='products' AND COLUMN_NAME='hero_selected_at')=0,'ALTER TABLE products ADD hero_selected_at DATETIME(6) NULL','SET @alpha_noop=1');
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
