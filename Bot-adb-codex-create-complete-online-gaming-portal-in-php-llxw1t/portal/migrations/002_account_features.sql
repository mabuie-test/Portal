-- Extensões idempotentes para contas
SET @has_birth_date := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='birth_date');
SET @sql_birth := IF(@has_birth_date = 0, 'ALTER TABLE users ADD COLUMN birth_date DATE NULL', 'SELECT 1');
PREPARE stmt_birth FROM @sql_birth; EXECUTE stmt_birth; DEALLOCATE PREPARE stmt_birth;

SET @has_prefs := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='preferences_json');
SET @sql_prefs := IF(@has_prefs = 0, 'ALTER TABLE users ADD COLUMN preferences_json JSON NULL', 'SELECT 1');
PREPARE stmt_prefs FROM @sql_prefs; EXECUTE stmt_prefs; DEALLOCATE PREPARE stmt_prefs;

SET @has_phone_uq := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='uq_users_phone');
SET @sql_phone_uq := IF(@has_phone_uq = 0, 'CREATE UNIQUE INDEX uq_users_phone ON users(phone)', 'SELECT 1');
PREPARE stmt_phone_uq FROM @sql_phone_uq; EXECUTE stmt_phone_uq; DEALLOCATE PREPARE stmt_phone_uq;
