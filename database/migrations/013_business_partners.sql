-- Alpha 1.7. Import into the existing 1.6 database after backup. Repeatable.
CREATE TABLE IF NOT EXISTS business_partners (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 company VARCHAR(180) NOT NULL, contact_name VARCHAR(180) NOT NULL,
 email VARCHAR(254) NOT NULL UNIQUE, phone VARCHAR(60) NOT NULL,
 address VARCHAR(255) NOT NULL, city VARCHAR(150) NOT NULL, tax_id VARCHAR(80) NOT NULL DEFAULT '',
 password_hash VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
 auth_version INT UNSIGNED NOT NULL DEFAULT 1, must_change_password TINYINT(1) NOT NULL DEFAULT 1,
 pricing_mode VARCHAR(20) NOT NULL DEFAULT 'discount', pricing_rate DECIMAL(7,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS partner_prices (
 partner_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL,
 pricing_mode VARCHAR(20) NOT NULL, pricing_rate DECIMAL(7,2) NOT NULL,
 PRIMARY KEY(partner_id,product_id),
 FOREIGN KEY(partner_id) REFERENCES business_partners(id),
 FOREIGN KEY(product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS partner_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, partner_id INT UNSIGNED NOT NULL,
 company VARCHAR(180) NOT NULL, contact_name VARCHAR(180) NOT NULL, email VARCHAR(254) NOT NULL,
 phone VARCHAR(60) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(150) NOT NULL,
 note VARCHAR(2000) NOT NULL DEFAULT '', status VARCHAR(30) NOT NULL DEFAULT 'new',
 total DECIMAL(12,2) NOT NULL, base_total DECIMAL(12,2) NOT NULL,
 shipping_method VARCHAR(150) NOT NULL, shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0,
 shipping_terms VARCHAR(1000) NOT NULL DEFAULT '', shipping_payer VARCHAR(20) NOT NULL DEFAULT 'customer',
 inventory_tracked TINYINT(1) NOT NULL, version INT UNSIGNED NOT NULL DEFAULT 1,
 checkout_token CHAR(64) NOT NULL UNIQUE,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL,
 FOREIGN KEY(partner_id) REFERENCES business_partners(id), INDEX(partner_id,created_at), INDEX(status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS partner_order_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NULL, sku VARCHAR(80) NOT NULL, product_name VARCHAR(200) NOT NULL,
 quantity INT UNSIGNED NOT NULL, fulfilled_qty INT UNSIGNED NOT NULL DEFAULT 0,
 cancelled_qty INT UNSIGNED NOT NULL DEFAULT 0, returned_qty INT UNSIGNED NOT NULL DEFAULT 0,
 base_price DECIMAL(12,2) NOT NULL, unit_price DECIMAL(12,2) NOT NULL,
 pricing_mode VARCHAR(20) NOT NULL, pricing_rate DECIMAL(7,2) NOT NULL,
 FOREIGN KEY(order_id) REFERENCES partner_orders(id), FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE SET NULL,
 INDEX(order_id,product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS partner_order_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL,
 kind VARCHAR(20) NOT NULL, note VARCHAR(1000) NOT NULL, courier VARCHAR(150) NOT NULL DEFAULT '',
 tracking_number VARCHAR(150) NOT NULL DEFAULT '', actor_id INT UNSIGNED NULL,
 event_token CHAR(64) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(order_id) REFERENCES partner_orders(id), FOREIGN KEY(actor_id) REFERENCES users(id), INDEX(order_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS partner_event_items (
 event_id BIGINT UNSIGNED NOT NULL, item_id BIGINT UNSIGNED NOT NULL, quantity INT UNSIGNED NOT NULL,
 PRIMARY KEY(event_id,item_id), FOREIGN KEY(event_id) REFERENCES partner_order_events(id),
 FOREIGN KEY(item_id) REFERENCES partner_order_items(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT IGNORE INTO settings(setting_key,setting_value) VALUES ('mail_partner_enabled','1'),('partner_recipients','');
INSERT IGNORE INTO schema_migrations(version) VALUES ('013_business_partners');
