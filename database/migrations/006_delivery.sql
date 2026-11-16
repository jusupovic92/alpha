-- Apply once after 005_hero_banner. Existing orders and customer totals remain unchanged.
ALTER TABLE shipping_methods ADD courier_name VARCHAR(150) NOT NULL DEFAULT '', ADD terms VARCHAR(1000) NOT NULL DEFAULT '', ADD payer VARCHAR(20) NOT NULL DEFAULT 'customer', ADD deleted_at TIMESTAMP NULL;
ALTER TABLE orders ADD shipping_terms VARCHAR(1000) NULL, ADD shipping_payer VARCHAR(20) NULL, ADD planned_delivery_id INT UNSIGNED NULL, ADD planned_delivery_name VARCHAR(150) NULL, ADD planned_delivery_cost DECIMAL(12,2) NULL, ADD planned_delivery_terms VARCHAR(1000) NULL, ADD planned_delivery_payer VARCHAR(20) NULL;
INSERT INTO schema_migrations(version) VALUES ('006_delivery');
