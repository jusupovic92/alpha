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
