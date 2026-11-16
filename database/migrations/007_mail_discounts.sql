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
