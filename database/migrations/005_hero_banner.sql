-- Apply once after 004_fulfillment. Existing images and hero selection are preserved.
ALTER TABLE products ADD hero_banner_url VARCHAR(500) NOT NULL DEFAULT '';
INSERT INTO schema_migrations(version) VALUES ('005_hero_banner');
