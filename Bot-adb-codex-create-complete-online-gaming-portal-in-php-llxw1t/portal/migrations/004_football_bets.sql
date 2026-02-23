-- Tabela de apostas de futebol (idempotente)
CREATE TABLE IF NOT EXISTS football_bets (
  bet_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  match_code VARCHAR(64) NOT NULL,
  market_type VARCHAR(40) NOT NULL,
  selection VARCHAR(120) NOT NULL,
  odd DECIMAL(8,2) NOT NULL,
  stake DECIMAL(18,2) NOT NULL,
  potential_payout DECIMAL(18,2) NOT NULL,
  status ENUM('open','won','lost','cashed_out','void') NOT NULL DEFAULT 'open',
  cashout_amount DECIMAL(18,2) NULL,
  settled_by_admin BIGINT UNSIGNED NULL,
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  settled_at DATETIME NULL,
  INDEX idx_fb_user_created (user_id, created_at),
  INDEX idx_fb_status (status, created_at),
  CONSTRAINT fk_fb_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_fb_admin FOREIGN KEY (settled_by_admin) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;
