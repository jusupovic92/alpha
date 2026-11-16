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
