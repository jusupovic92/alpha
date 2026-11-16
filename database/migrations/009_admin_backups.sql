-- Apply once after 008_readability_numbering. Existing audit records are preserved.
INSERT INTO settings VALUES ('admin_theme','emerald'),('backup_enabled','0'),('backup_hour','03:00'),('backup_keep','14');
INSERT INTO schema_migrations(version) VALUES ('009_admin_backups');
