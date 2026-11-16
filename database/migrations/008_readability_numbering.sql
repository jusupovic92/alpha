-- Apply once after 007_mail_discounts. Existing SKUs, order data and prices are preserved.
CREATE TABLE app_counters (counter_key VARCHAR(80) PRIMARY KEY, next_value BIGINT UNSIGNED NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO app_counters VALUES ('product_sku',1);
INSERT INTO settings VALUES ('store_font_scale','115'),('admin_font_scale','120');
INSERT INTO schema_migrations(version) VALUES ('008_readability_numbering');
