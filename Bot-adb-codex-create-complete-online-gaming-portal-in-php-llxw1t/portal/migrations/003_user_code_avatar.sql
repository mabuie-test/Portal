-- idempotente: código fixo do utilizador e avatar
SET @has_user_code := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='user_code');
SET @sql_user_code := IF(@has_user_code = 0, 'ALTER TABLE users ADD COLUMN user_code CHAR(5) NULL', 'SELECT 1');
PREPARE stmt_user_code FROM @sql_user_code; EXECUTE stmt_user_code; DEALLOCATE PREPARE stmt_user_code;

SET @has_avatar := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='avatar_url');
SET @sql_avatar := IF(@has_avatar = 0, 'ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) NULL', 'SELECT 1');
PREPARE stmt_avatar FROM @sql_avatar; EXECUTE stmt_avatar; DEALLOCATE PREPARE stmt_avatar;

SET @has_ucode_uq := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND INDEX_NAME='uq_users_user_code');
SET @sql_ucode_uq := IF(@has_ucode_uq = 0, 'CREATE UNIQUE INDEX uq_users_user_code ON users(user_code)', 'SELECT 1');
PREPARE stmt_ucode_uq FROM @sql_ucode_uq; EXECUTE stmt_ucode_uq; DEALLOCATE PREPARE stmt_ucode_uq;
