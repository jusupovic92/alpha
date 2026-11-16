-- Apply ONCE after 002_management. Back up the database first.
ALTER TABLE products ADD hero_selected TINYINT(1) NOT NULL DEFAULT 0, ADD purchase_price DECIMAL(12,2) NOT NULL DEFAULT 0, ADD low_stock_threshold INT UNSIGNED NOT NULL DEFAULT 3, ADD INDEX idx_hero (active,hero_selected,created_at,id);
ALTER TABLE categories ADD show_on_home TINYINT(1) NOT NULL DEFAULT 1, ADD sort_order INT NOT NULL DEFAULT 0;
CREATE TABLE product_images (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, product_id INT UNSIGNED NOT NULL, image_url VARCHAR(500) NOT NULL, sort_order INT NOT NULL DEFAULT 0, FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE, INDEX(product_id,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE stock_movements ADD available_after INT UNSIGNED NULL, ADD reserved_after INT UNSIGNED NULL, ADD unit_cost DECIMAL(12,2) NULL;
INSERT INTO schema_migrations(version) VALUES ('003_storefront');
