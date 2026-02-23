-- idempotente MySQL/MariaDB
CREATE TABLE IF NOT EXISTS roles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (id, name, description) VALUES
(1,'ROLE_USER','Utilizador padrão'),
(2,'ROLE_SUPPORT','Suporte operacional'),
(3,'ROLE_ADMIN','Administrador global');

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(32) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('pending','active','suspended','deleted') NOT NULL DEFAULT 'pending',
  email_verification_token VARCHAR(128) NULL,
  email_verification_expires_at DATETIME NULL,
  reset_token VARCHAR(128) NULL,
  reset_token_expires_at DATETIME NULL,
  totp_secret VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified_at DATETIME NULL,
  INDEX idx_users_status_created (status, created_at),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_user (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  role_id BIGINT UNSIGNED NOT NULL,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_role_user (user_id, role_id),
  CONSTRAINT fk_role_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_user_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sessions (
  id CHAR(128) PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip VARCHAR(45) NOT NULL,
  user_agent VARCHAR(255) NULL,
  csrf_token CHAR(64) NULL,
  payload JSON NULL,
  last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  INDEX idx_sessions_user (user_id, expires_at),
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallets (
  wallet_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL UNIQUE,
  balance DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'MZN',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
  transaction_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  wallet_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  type ENUM('credit','debit') NOT NULL,
  status ENUM('pending','completed','failed','reversed') NOT NULL DEFAULT 'pending',
  reference VARCHAR(190) NOT NULL,
  external_provider VARCHAR(120) NULL,
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_transactions_wallet_date (wallet_id, created_at),
  INDEX idx_transactions_reference (reference),
  CONSTRAINT fk_transactions_wallet FOREIGN KEY (wallet_id) REFERENCES wallets(wallet_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payment_submissions (
  submission_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  submitted_reference VARCHAR(190) NOT NULL,
  submitted_amount DECIMAL(18,2) NOT NULL,
  submitted_phone VARCHAR(32) NOT NULL,
  attached_receipt_url VARCHAR(255) NULL,
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  admin_note TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  verified_at DATETIME NULL,
  INDEX idx_payment_submissions_filter (status, created_at, submitted_amount),
  INDEX idx_payment_submissions_ref (submitted_reference),
  CONSTRAINT fk_submission_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS games (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  shortcode VARCHAR(40) NOT NULL UNIQUE,
  min_bet DECIMAL(18,2) NOT NULL,
  max_bet DECIMAL(18,2) NOT NULL,
  house_edge DECIMAL(6,4) NOT NULL,
  volatility VARCHAR(32) NOT NULL,
  status ENUM('active','inactive','maintenance') NOT NULL DEFAULT 'active',
  seed_rotation_interval INT NOT NULL DEFAULT 100,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS game_configs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED NOT NULL,
  config_key VARCHAR(120) NOT NULL,
  config_value JSON NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_game_config (game_id, config_key),
  CONSTRAINT fk_game_configs_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rounds (
  round_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED NOT NULL,
  server_seed_hash CHAR(64) NOT NULL,
  server_seed_plain_encrypted TEXT NOT NULL,
  client_seed VARCHAR(190) NOT NULL,
  nonce BIGINT UNSIGNED NOT NULL,
  crash_multiplier DECIMAL(10,2) NOT NULL,
  resultado_raw JSON NOT NULL,
  started_at DATETIME NOT NULL,
  finished_at DATETIME NULL,
  INDEX idx_rounds_game_started (game_id, started_at),
  INDEX idx_rounds_seed_hash (server_seed_hash),
  CONSTRAINT fk_rounds_game FOREIGN KEY (game_id) REFERENCES games(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bets (
  bet_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  round_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(18,2) NOT NULL,
  auto_cashout DECIMAL(10,2) NULL,
  status ENUM('placed','pending','cashed_out','lost','refunded') NOT NULL DEFAULT 'placed',
  placed_at DATETIME NOT NULL,
  cashed_out_at DATETIME NULL,
  cashout_multiplier DECIMAL(10,2) NULL,
  INDEX idx_bets_round_status (round_id, status),
  INDEX idx_bets_user_date (user_id, placed_at),
  CONSTRAINT fk_bets_round FOREIGN KEY (round_id) REFERENCES rounds(round_id),
  CONSTRAINT fk_bets_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS provably_seeds (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED NOT NULL,
  seed_hash CHAR(64) NOT NULL,
  seed_encrypted TEXT NOT NULL,
  valid_from_round BIGINT UNSIGNED NOT NULL,
  valid_to_round BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_provably_seed_hash (seed_hash),
  CONSTRAINT fk_provably_seed_game FOREIGN KEY (game_id) REFERENCES games(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS provably_proofs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  round_id BIGINT UNSIGNED NOT NULL,
  published_hash CHAR(64) NOT NULL,
  revealed_seed TEXT NOT NULL,
  client_seed VARCHAR(190) NOT NULL,
  nonce BIGINT UNSIGNED NOT NULL,
  hmac_result CHAR(64) NOT NULL,
  calculation_steps JSON NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_provably_proof_round (round_id),
  CONSTRAINT fk_provably_proof_round FOREIGN KEY (round_id) REFERENCES rounds(round_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(140) NOT NULL,
  target_type VARCHAR(80) NOT NULL,
  target_id BIGINT UNSIGNED NULL,
  justification TEXT NULL,
  ip VARCHAR(45) NOT NULL,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  metadata JSON NULL,
  INDEX idx_audit_action_time (action, timestamp),
  INDEX idx_audit_target (target_type, target_id),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- sugestões de retenção/particionamento para alto volume:
-- ALTER TABLE rounds PARTITION BY RANGE (YEAR(started_at)*100 + MONTH(started_at)) (...);
-- ALTER TABLE bets PARTITION BY RANGE (TO_DAYS(placed_at)) (...);
