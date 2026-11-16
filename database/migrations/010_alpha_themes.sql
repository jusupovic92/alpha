-- Alpha themes do not alter products, orders, stock or existing theme selections.
INSERT IGNORE INTO settings(setting_key,setting_value) VALUES ('store_theme','alpha-hybrid'),('brand_slogan','Za promjenu, probajte kvalitet!'),('alpha_demo_notice','0');
UPDATE settings SET setting_value='ALPHA refinish' WHERE setting_key='shop_name' AND setting_value='WEBSHOP';
UPDATE settings SET setting_value='#008c4c' WHERE setting_key='accent_color' AND setting_value='#167655';
INSERT IGNORE INTO schema_migrations(version) VALUES ('010_alpha_themes');
