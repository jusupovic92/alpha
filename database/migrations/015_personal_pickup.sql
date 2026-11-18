-- Alpha 1.9. Existing 1.8.x database only, after backup. Repeatable.
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='shipping_methods' AND COLUMN_NAME='delivery_type')=0,'ALTER TABLE shipping_methods ADD delivery_type VARCHAR(20) NOT NULL DEFAULT ''courier''','SET @alpha_noop=1');
PREPARE alpha_stmt FROM @alpha_sql;
EXECUTE alpha_stmt;
DEALLOCATE PREPARE alpha_stmt;
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='shipping_type')=0,'ALTER TABLE orders ADD shipping_type VARCHAR(20) NOT NULL DEFAULT ''courier''','SET @alpha_noop=1');
PREPARE alpha_stmt FROM @alpha_sql;
EXECUTE alpha_stmt;
DEALLOCATE PREPARE alpha_stmt;
SET @alpha_sql=IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='partner_orders' AND COLUMN_NAME='shipping_type')=0,'ALTER TABLE partner_orders ADD shipping_type VARCHAR(20) NOT NULL DEFAULT ''courier''','SET @alpha_noop=1');
PREPARE alpha_stmt FROM @alpha_sql;
EXECUTE alpha_stmt;
DEALLOCATE PREPARE alpha_stmt;
INSERT INTO shipping_methods(name,cost,courier_name,terms,payer,active,delivery_type)
 SELECT 'Lično preuzimanje',0,'','Preuzimanje u prodavnici po dogovoru. Kontaktirat ćemo vas kada narudžba bude spremna.','customer',1,'pickup'
 WHERE NOT EXISTS(SELECT 1 FROM shipping_methods WHERE delivery_type='pickup') AND NOT EXISTS(SELECT 1 FROM schema_migrations WHERE version='015_personal_pickup');
INSERT IGNORE INTO schema_migrations(version) VALUES('015_personal_pickup');
